<?php

namespace App\Clients;

use App\Enums\V6BlockingStatus;
use App\Models\PiHoleBox;
use Cache;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\RequestOptions;
use http\Exception\InvalidArgumentException;
use Illuminate\Support\Collection;
use Log;
use Psr\Http\Message\ResponseInterface;

class PiHoleClient
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
                    ]);
                } else {
                    Log::warning(sprintf(
                        'Skipping pause request for Pi-hole %s due to missing SID',
                        $box->name,
                    ));
                }
            } else {
                $pausePromises[$box->id] = $this->client->getAsync($box->getPauseUrl($seconds));
            }

            if (isset($pausePromises[$box->id])) {
                $pausePromises[$box->id] = $pausePromises[$box->id]->otherwise(
                    function (RequestException $e) use ($box) {
                        Log::error(sprintf(
                            'Failed to pause Pi-Hole %s: %s',
                            $box->name,
                            $e->getMessage(),
                        ));
                    }
                );
            }
        }

        return ! empty($pausePromises) ? Utils::settle($pausePromises)->wait() : [];
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
            RequestOptions::JSON            => [
                'password' => $piHoleBox->password,
            ],
            RequestOptions::VERIFY => false,
        ])->then(function (ResponseInterface $response) use ($piHoleBox) {
            $data     = json_decode($response->getBody()->getContents(), true);
            $sid      = $data['session']['sid'] ?? null;
            $validity = $data['session']['validity'] ?? null;

            if ($sid) {
                $this->storeSID($piHoleBox, $sid, $validity);
                return $sid;
            }

            throw new Exception('No SID received.');
        })->otherwise(function (RequestException $e) use ($piHoleBox) {
            Log::error(sprintf(
                'Authentication failed for Pi-Hole %s: %s',
                $piHoleBox->name,
                $e->getMessage(),
            ));
            return null;
        });
    }
}
