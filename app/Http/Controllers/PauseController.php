<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

    public function __construct()
    {
        $this->minSec = config('pihole.min_timeout_seconds');
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function pausePiHoles(
        Client $client,
        PiHoleBoxRepository $piHoleBoxRepository,
        int $seconds = 30
    ): View {
        $cacheData = Cache::get($this->cacheKey);

        if ($cacheData !== null) {
            $seconds   = $cacheData['seconds'] - (int) (Carbon::parse($cacheData['date'])->diffInSeconds(Carbon::now()));
            $report    = $cacheData['report'];
            $allFailed = false;
        } else {
            $piholeBoxes = $piHoleBoxRepository->getPiHoleBoxes();
            $seconds     = $seconds >= $this->minSec && $seconds <= 300 ? $seconds : $this->minSec;
            $report      = [];

            $requests = function (Collection $piholeBoxes) use ($client, $seconds) {
                /** @var PiHoleBox $box */
                foreach ($piholeBoxes as $box) {
                    $url = $box->getPauseUrl($seconds);
                    yield function () use ($client, $url) {
                        return $client->getAsync($url, [
                            RequestOptions::TIMEOUT         => 2,
                            RequestOptions::CONNECT_TIMEOUT => 2,
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
}
