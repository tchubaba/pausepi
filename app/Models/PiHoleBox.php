<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Validation\ValidationException;
use Validator;

class PiHoleBox
{
    public readonly string $name;

    public readonly string $apiKey;

    public readonly string $ipAddress;

    protected string $piUrlTemplate = 'http://%s/admin/api.php?disable=%s&auth=%s';

    /**
     * @throws Exception
     */
    public function __construct(string $name, string $apiKey, string $ipAddress)
    {
        $this->validateInputs($name, $apiKey, $ipAddress);

        $this->name      = $name;
        $this->apiKey    = $apiKey;
        $this->ipAddress = $ipAddress;
    }

    public function getPauseUrl(int $timeout): string
    {
        return sprintf(
            $this->piUrlTemplate,
            $this->ipAddress,
            $timeout + 2,
            $this->apiKey,
        );
    }

    /**
     * @throws ValidationException
     */
    protected function validateInputs(string $name, string $apiKey, string $ipAddress): void
    {
        $validator = Validator::make(
            [
                'name'      => $name,
                'apiKey'    => $apiKey,
                'ipAddress' => $ipAddress,
            ],
            [
                'name'      => ['required'],
                'apiKey'    => ['required'],
                'ipAddress' => ['required', 'ip'],
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
