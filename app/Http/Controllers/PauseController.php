<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PauseResultStatus;
use App\Models\PauseResult;
use App\Models\PiHoleBox;
use App\Repositories\PiHoleBoxRepository;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Log;
use Psr\SimpleCache\InvalidArgumentException;

class PauseController extends BaseController
{
    protected string $cacheKey = 'piholepauser:pause';

    protected int $minSec;

    protected int $maxSec;

    public function __construct()
    {
        $this->minSec = config('pausepi.min_timeout_seconds');
        $this->maxSec = config('pausepi.max_timeout_seconds');
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function pausePiHoles(
        Client $client,
        PiHoleBoxRepository $piHoleBoxRepository,
        int $seconds = 30,
    ): View {
        $cacheData = Cache::get($this->cacheKey);

        if ($cacheData !== null) {
            $seconds   = $cacheData['seconds'] - (int) (Carbon::parse($cacheData['date'])->diffInSeconds(Carbon::now()));
            $report    = $cacheData['report'];
            $allFailed = false;
        } else {
            $seconds     = $seconds >= $this->minSec && $seconds <= $this->maxSec ? $seconds : $this->minSec;
            $report      = [];
            $piholeBoxes = $piHoleBoxRepository->getPiHoleBoxes();
            $allFailed   = true;

            if ($piholeBoxes->isNotEmpty()) {
                $requests = function (Collection $piholeBoxes) use ($client, $seconds) {
                    /** @var PiHoleBox $box */
                    foreach ($piholeBoxes as $box) {
                        $url = $box->getPauseUrl($seconds);
                        yield function () use ($client, $url, $box, $seconds) {
                            if ($box->version < 6) {
                                return $client->getAsync($url, [
                                    RequestOptions::TIMEOUT         => 2,
                                    RequestOptions::CONNECT_TIMEOUT => 2,
                                ]);
                            } else {
                                return $client->postAsync($url, [
                                    'json' => [
                                        'blocking' => false,
                                        'timer'    => $seconds,
                                    ],
                                    'headers' => [
                                        'X-FTL-SID' => $this->getV6PiHoleAuthSid($box),
                                    ],
                                    RequestOptions::TIMEOUT         => 2,
                                    RequestOptions::CONNECT_TIMEOUT => 2,
                                ]);
                            }
                        };
                    }
                };

                $pool = new Pool($client, $requests($piholeBoxes), [
                    'concurrency' => count($piholeBoxes),
                    'fulfilled'   => function (Response $response, int $index) use ($seconds, $piholeBoxes, &$report) {
                        /** @var PiHoleBox $box */
                        $box      = $piholeBoxes[$index];
                        $contents = json_decode($response->getBody()->getContents());

                        if ( ! empty($contents)
                            && property_exists($contents, 'status')
                            && $contents->status === 'disabled'
                        ) {
                            $result = PauseResultStatus::SUCCESS;
                        } else {
                            $result = PauseResultStatus::INVALID_RESPONSE;
                            Log::warning(
                                sprintf(
                                    'Did not receive the expected response from the Pi-hole box at %s. Is the API'
                                    . ' key correct?',
                                    $box->ipAddress,
                                )
                            );
                        }

                        $report[$index] = new PauseResult($box, $result, Carbon::now());
                    },
                    'rejected' => function ($reason, $index) use ($seconds, $piholeBoxes, &$report) {
                        /** @var PiHoleBox $box */
                        $box = $piholeBoxes[$index];
                        if ($reason instanceof RequestException && $reason->hasResponse()) {
                            $errorMsg = sprintf('Got status code %', $reason->getResponse()->getStatusCode());
                        } else {
                            $errorMsg = $reason->getMessage();
                        }

                        $report[$index] = new PauseResult($box, PauseResultStatus::TIMEOUT, Carbon::now());

                        Log::warning(sprintf(
                            'Could not pause Pi-Hole box \'%s\': %s',
                            $box->name,
                            $errorMsg,
                        ));
                    },
                ]);

                $requestTime = Carbon::now();
                $pool->promise()->wait();

                /** @var PauseResult $result */
                foreach ($report as $result) {
                    if ($result->status === PauseResultStatus::SUCCESS) {
                        $allFailed = false;
                        break;
                    }
                }

                ksort($report);
                $seconds = $seconds - (int)($requestTime->diffInSeconds(Carbon::now()));
            }

            if ( ! $allFailed) {
                Cache::set($this->cacheKey, [
                    'seconds' => $seconds,
                    'date'    => Carbon::now()->toDateTimeString(),
                    'report'  => $report,
                ], $seconds);
            }
        }

        return view('home', [
            'seconds'   => $seconds,
            'report'    => $report,
            'allFailed' => $allFailed,
        ]);
    }

    protected function getV6PiHoleAuthSid(PiHoleBox $box): ?string
    {
        if ($box->version < 6) {
            return null;
        }


    }
}
