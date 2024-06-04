<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PiHoleBox;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Log;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

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

            $seconds = $seconds >= $this->minSec && $seconds <= 300 ? $seconds : $this->minSec;
            $client  = new Client();
            $report  = [];

            $piholeBoxes->each(function (PiHoleBox $box) use ($client, $seconds, &$report) {
                try {
                    $res = $client->request('GET', $this->getPauseUrl($box, $seconds), [
                        RequestOptions::TIMEOUT         => 5,
                        RequestOptions::CONNECT_TIMEOUT => 5,
                    ]);
                    $callResult = $res->getStatusCode() === Response::HTTP_OK;
                } catch (Exception $e) {
                    $callResult = false;
                }

                $report[$box->name] = [
                    'ip'     => $box->ipAddress,
                    'result' => $callResult,
                ];
            });

            $allFailed = true;
            foreach ($report as $result) {
                if ($result['result'] === true) {
                    $allFailed = false;
                    break;
                }
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

    protected function getPauseUrl(PiHoleBox $box, int $timeout): string
    {
        return sprintf(
            config('pihole.pause_url_template'),
            $box->ipAddress,
            $timeout + 2,
            $box->apiKey,
        );
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
