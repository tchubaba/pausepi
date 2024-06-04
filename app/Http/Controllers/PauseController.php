<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PiHoleBox;
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

    public function __construct()
    {
        $this->minSec = config('pihole.min_timeout_seconds');
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function pausePiHoles(int $seconds = 30): View
    {
        $cacheData = Cache::get($this->cacheKey);

        if ($cacheData !== null) {
            $seconds   = $cacheData['seconds'] - (int) (Carbon::parse($cacheData['date'])->diffInSeconds(Carbon::now()));
            $report    = $cacheData['report'];
            $allFailed = false;
        } else {
            $piholeBoxes = $this->getPiHoleBoxes();
            $seconds     = $seconds >= $this->minSec && $seconds <= 300 ? $seconds : $this->minSec;
            $client      = new Client();
            $report      = [];

            $requests = function (Collection $piholeBoxes) use ($client, $seconds) {
                /** @var PiHoleBox $box */
                foreach ($piholeBoxes as $box) {
                    $url = $box->getPauseUrl($seconds);
                    yield function () use ($client, $url) {
                        return $client->getAsync($url, [
                            RequestOptions::TIMEOUT         => 5,
                            RequestOptions::CONNECT_TIMEOUT => 5,
                        ]);
                    };
                }
            };

            $pool = new Pool($client, $requests($piholeBoxes), [
                'concurrency' => count($piholeBoxes),
                'fulfilled'   => function (Response $response, int $index) use ($seconds, $piholeBoxes, &$report) {
                    /** @var PiHoleBox $box */
                    $box      = $piholeBoxes[$index];
                    $contents = json_decode($response->getBody()->getContents());

                    $report[$box->name] = [
                        'ip'     => $box->ipAddress,
                        'result' => ! empty($contents)
                            && property_exists($contents, 'status')
                            && $contents->status === 'disabled',
                    ];
                },
                'rejected' => function ($reason, $index) use ($seconds, $piholeBoxes, &$report) {
                    $box = $piholeBoxes[$index];
                    if ($reason instanceof RequestException && $reason->hasResponse()) {
                        $errorMsg = sprintf('Got status code %', $reason->getResponse()->getStatusCode());
                    } else {
                        $errorMsg = $reason->getMessage();
                    }

                    $report[$box->name] = [
                        'ip'     => $box->ipAddress,
                        'result' => false,
                    ];

                    Log::warning(sprintf(
                        'Could not pause Pi-Hole box \'%s\': %s',
                        $box->name,
                        $errorMsg,
                    ));
                },
            ]);

            $requestTime = Carbon::now();
            $promise     = $pool->promise();
            $promise->wait();

            $allFailed = true;
            foreach ($report as $result) {
                if ($result['result'] === true) {
                    $allFailed = false;
                    break;
                }
            }

            $seconds = $seconds - (int) ($requestTime->diffInSeconds(Carbon::now()));

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

    /**
     * @return Collection<PiHoleBox>
     *
     * @throws Exception
     */
    protected function getPiHoleBoxes(): Collection
    {
        $boxesConfig = config('pihole.boxes');
        $piHoleBoxes = collect();

        foreach ($boxesConfig as $box) {
            try {
                $piHoleBoxes->add(new PiHoleBox($box['name'], $box['api_key'], $box['ip']));
            } catch (Exception $e) {
                Log::warning(
                    sprintf('Could not create pihole box: %s', $e->getMessage()),
                );
            }
        }

        if ($piHoleBoxes->isEmpty()) {
            throw new Exception(
                'Pihole boxes not properly configured! Ensure at least 1 Pihole box is configured correctly.'
            );
        }

        return $piHoleBoxes;
    }
}
