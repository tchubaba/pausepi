<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Clients\PiHoleAPIClient;
use App\Data\PiHole5API\Disable;
use App\Data\PiHole6API\Blocking;
use App\Data\PiHole6API\BlockingError;
use App\Enums\PauseResultStatus;
use App\Enums\PiHoleAPIClientPromiseResultState;
use App\Enums\V6BlockingStatus;
use App\Models\NonDB\CachedPauseRequest;
use App\Models\NonDB\PauseResult;
use App\Models\NonDB\PiHoleAPIClientPromiseResult;
use App\Repositories\PiHoleBoxRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Log;
use Psr\SimpleCache\InvalidArgumentException;

class PauseController extends BaseController
{
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
        PiHoleAPIClient     $client,
        PiHoleBoxRepository $piHoleBoxRepository,
        int                 $seconds = 30,
    ): View {
        /** @var ?CachedPauseRequest $cacheData */
        $cacheData = Cache::get(CachedPauseRequest::CACHE_KEY);

        if ($cacheData !== null) {
            $seconds   = $cacheData->seconds - (int) ($cacheData->date->diffInSeconds(Carbon::now()));
            $report    = $cacheData->report;
            $allFailed = false;
        } else {
            $seconds     = $seconds >= $this->minSec && $seconds <= $this->maxSec ? $seconds : $this->minSec;
            $report      = collect();
            $piholeBoxes = $piHoleBoxRepository->getPiHoleBoxes();
            $allFailed   = true;

            if ($piholeBoxes->isNotEmpty()) {
                $requestTime = Carbon::now();
                $results     = $client->pausePiHoles($piholeBoxes, $seconds);

                foreach ($results as $result) {
                    $errorReason       = null;
                    $pauseResultStatus = PauseResultStatus::FAILURE;
                    /** @var PiHoleAPIClientPromiseResult $promiseResult */
                    $promiseResult = $result['value'];
                    if ($promiseResult->state === PiHoleAPIClientPromiseResultState::FULFILLED) {
                        if ($promiseResult->box->isVersion6()) {
                            /** @var Blocking $blocking */
                            $blocking = $promiseResult->data;
                            if ($blocking->blocking === V6BlockingStatus::DISABLED) {
                                $pauseResultStatus = PauseResultStatus::SUCCESS;
                                $allFailed         = false;
                            } else {
                                $errorReason = sprintf(
                                    'Pi-Hole indicated ad blocking status of %s',
                                    $blocking->blocking->name
                                );
                            }
                        } else {
                            /** @var Disable $disable */
                            $disable = $promiseResult->data;
                            if ($disable->status === 'disabled') {
                                $pauseResultStatus = PauseResultStatus::SUCCESS;
                                $allFailed         = false;
                            } else {
                                $errorReason = 'Did not receive expected response from API';
                            }
                        }
                    } else {
                        // Handle rejected calls
                        if ($promiseResult->box->isVersion6()) {
                            /** @var BlockingError $blockingError */
                            $blockingError = $promiseResult->data;
                            $errorReason   = $blockingError->error->message;
                        } else {
                            $errorReason = sprintf(
                                'Received HTTP code %s from API',
                                $promiseResult->status
                            );
                        }
                    }

                    if ($pauseResultStatus !== PauseResultStatus::SUCCESS) {
                        Log::warning(sprintf(
                            'Could not pause Pi-Hole %s: %s',
                            $promiseResult->box->name,
                            $errorReason
                        ));
                    }

                    $report->add(new PauseResult($promiseResult->box, $pauseResultStatus, Carbon::now()));
                }

                $seconds = $seconds - (int)($requestTime->diffInSeconds(Carbon::now()));

                if ( ! $allFailed) {
                    Cache::set(
                        key: CachedPauseRequest::CACHE_KEY,
                        value: new CachedPauseRequest(
                            $seconds,
                            $requestTime,
                            $report,
                        ),
                        ttl: $seconds,
                    );
                }
            }
        }

        return view('home', [
            'seconds'   => $seconds,
            'report'    => $report,
            'allFailed' => $allFailed,
        ]);
    }
}
