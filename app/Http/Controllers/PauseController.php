<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Clients\PiHoleClient;
use App\Enums\PauseResultStatus;
use App\Models\PauseResult;
use App\Models\PiHoleBox;
use App\Repositories\PiHoleBoxRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
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
        PiHoleClient $client,
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
                $requestTime = Carbon::now();
                $results     = $client->pausePiHoles($piholeBoxes);

                foreach ($results as $id => $result) {
                    /** @var PiHoleBox $box */
                    $box = $piholeBoxes->firstWhere('id', $id);

                    if ($result['state'] === 'fulfilled') {
                        $pauseResultStatus = PauseResultStatus::SUCCESS;
                        $allFailed         = false;
                    } elseif ($result['state'] === 'rejected') {
                        $pauseResultStatus = PauseResultStatus::TIMEOUT;
                    } else {
                        $pauseResultStatus = PauseResultStatus::INVALID_RESPONSE;
                    }

                    if ($pauseResultStatus !== PauseResultStatus::SUCCESS) {
                        Log::warning(sprintf(
                            'Could not pause for pihole %s: %s',
                            $box->name,
                            $result['reason']->getMessage(),
                        ));
                    }

                    $report[$id] = new PauseResult($box, $pauseResultStatus, Carbon::now());
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
}
