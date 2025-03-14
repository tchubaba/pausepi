<?php

declare(strict_types=1);

namespace App\Models\NonDB;

use App\Enums\PiHoleAPIClientPromiseResultState;
use App\Models\PiHoleBox;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Spatie\LaravelData\Data;

readonly class PiHoleAPIClientPromiseResult
{
    public PiHoleAPIClientPromiseResultState $state;

    public int $status;

    public PiHoleBox $box;

    public ?Data $data;

    public ?ResponseInterface $response;

    public ?RequestException $exception;

    public function __construct(
        PiHoleAPIClientPromiseResultState $state,
        int $status,
        PiHoleBox $box,
        Data $data = null,
        ResponseInterface $response = null,
        RequestException $exception = null,
    ) {
        $this->state     = $state;
        $this->status    = $status;
        $this->box       = $box;
        $this->data      = $data;
        $this->response  = $response;
        $this->exception = $exception;
    }
}
