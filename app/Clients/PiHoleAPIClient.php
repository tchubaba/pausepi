<?php

namespace App\Clients;

use App\Data\PiHole6API\AuthErrorResponse;
use App\Data\PiHole6API\AuthResponse;
use App\Data\PiHole6API\Blocking;
use App\Data\PiHole6API\BlockingError;
use App\Models\PiHoleBox;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Collection;
use Log;
use Psr\Http\Message\ResponseInterface;

class PiHoleAPIClient
{
    protected Client $client;

    protected string $cacheKey = 'pihole_%s_sid';

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param Collection<int, PiHoleBox> $piHoleBoxes
     * @param int $seconds
     *
     * @return PromiseInterface|array
     */
    public function pausePiHoles(Collection $piHoleBoxes, int $seconds = 30): PromiseInterface|array
    {
        if ($piHoleBoxes->isEmpty()) {
            throw new InvalidArgumentException('piHoleBoxes collection must not be empty.');
        }

        $authPromises  = [];

        // First, trigger authentication requests for Pi-holes that require it.
        foreach ($piHoleBoxes as $box) {
            if ($box->requiresAuthentication() && ! $this->getSID($box)) {
                $authPromises[$box->id] = $this->authenticate($box);
            }
        }

        // If any need authentication, wait for them to complete before proceeding with pauses.
        if ( ! empty($authPromises)) {
            Utils::settle($authPromises)->wait();
        }

        $pausePromises = [];
        foreach ($piHoleBoxes as $box) {
            if ($box->requiresAuthentication()) {
                $sid = $this->getSID($box);
                if ($sid) {
                    $pausePromises[$box->id] = $this->client->postAsync($box->getPauseUrl(), [
                        RequestOptions::HEADERS => [
                            'X-FTL-SID' => $sid,
                        ],
                        RequestOptions::JSON => [
                            'blocking' => false,
                            'timer'    => $seconds,
                        ],
                        RequestOptions::TIMEOUT         => 2,
                        RequestOptions::CONNECT_TIMEOUT => 2,
                        RequestOptions::VERIFY          => false,
                        RequestOptions::HTTP_ERRORS     => false,
                    ])->then(function (ResponseInterface $response) use ($box) {
                        if ($response->getStatusCode() === 200) {
                            return Blocking::from($response->getBody()->getContents());
                        } else {
                            return BlockingError::from($response->getBody()->getContents());
                        }
                    });
                } else {
                    Log::warning(sprintf(
                        'Skipping pause request for Pi-hole %s due to missing SID',
                        $box->name,
                    ));
                }
            } else {
                $pausePromises[$box->id] = $this->client->getAsync($box->getPauseUrl($seconds), [
                    RequestOptions::HTTP_ERRORS => true,
                ]);
            }
        }

        return ! empty($pausePromises) ? Utils::settle($pausePromises)->wait() : [];
    }

    public function clearSID(PiHoleBox $piHoleBox): void
    {
        Cache::forget($this->getCacheKey($piHoleBox));
    }

    protected function getCacheKey(PiHoleBox $piHoleBox): string
    {
        return sprintf($this->cacheKey, $piHoleBox->id);
    }

    protected function getSID(PiHoleBox $piHoleBox): ?string
    {
        return Cache::get($this->getCacheKey($piHoleBox));
    }

    protected function storeSID(PiHoleBox $piHoleBox, string $sid, int $ttl = 30): void
    {
        Cache::put($this->getCacheKey($piHoleBox), $sid, now()->addSeconds($ttl));
    }

    protected function authenticate(PiHoleBox $piHoleBox): ?PromiseInterface
    {
        if ( ! $piHoleBox->requiresAuthentication()) {
            return null;
        }

        return $this->client->postAsync($piHoleBox->getAuthUrl(), [
            RequestOptions::TIMEOUT         => 2,
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::VERIFY          => false,
            RequestOptions::JSON            => [
                'password' => $piHoleBox->password,
            ],
            RequestOptions::HTTP_ERRORS => false,
        ])->then(function (ResponseInterface $response) use ($piHoleBox) {
            if ($response->getStatusCode() === 200
                || $response->getStatusCode() === 401) {
                $authResponse = AuthResponse::from($response->getBody()->getContents());

                if ($authResponse->sessionIsValid()) {
                    $this->storeSID($piHoleBox, $authResponse->session->sid, $authResponse->session->validity);
                    return $authResponse->session->sid;
                } else {
                    Log::error(sprintf(
                        'Authentication failed for Pi-Hole %s: %s',
                        $piHoleBox->name,
                        $authResponse->session->message,
                    ));

                    return null;
                }
            } else {
                $authErrorResponse = AuthErrorResponse::from($response->getBody()->getContents());

                Log::error(sprintf(
                    'Authentication failed for Pi-Hole %s: %s',
                    $piHoleBox->name,
                    $authErrorResponse->error->message,
                ));

                return null;
            }
        });
    }
}
