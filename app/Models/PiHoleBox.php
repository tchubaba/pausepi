<?php

declare(strict_types=1);

namespace App\Models;

use Crypt;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $api_key
 * @property string $password
 * @property integer $version
 * @property string $hostname
 * @property string $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|PiHoleBox newModelQuery()
 * @method static Builder|PiHoleBox newQuery()
 * @method static Builder|PiHoleBox query()
 * @method static Builder|PiHoleBox whereId($value)
 * @method static Builder|PiHoleBox whereName($value)
 * @method static Builder|PiHoleBox whereApiKey($value)
 * @method static Builder|PiHoleBox wherePassword($value)
 * @method static Builder|PiHoleBox whereVersion($value)
 * @method static Builder|PiHoleBox whereHostName($value)
 * @method static Builder|PiHoleBox whereDescription($value)
 * @method static Builder|PiHoleBox whereCreatedAt($value)
 * @method static Builder|PiHoleBox whereUpdatedAt($value)
 * @mixin Eloquent
 */
class PiHoleBox extends Model
{
    protected $table = 'pi_hole_boxes';

    protected string $pi5PauseUrlTemplate = 'http://%s/admin/api.php?disable=%s&auth=%s';

    protected string $pi6PauseUrlTemplate = 'https://%s/api/dns/blocking';

    protected $fillable = [
        'name',
        'api_key',
        'password',
        'version',
        'hostname',
        'description',
    ];

    public function setPasswordAttribute(?string $value): void
    {
        if ($value !== null) {
            $value = Crypt::encryptString($value);
        }

        $this->attributes['password'] = $value;
    }

    public function getPasswordAttribute($value): ?string
    {
        if ($value !== null) {
            $value = Crypt::decryptString($value);
        }

        return $value;
    }

    public function getPauseUrl(int $timeout = 30): string
    {
        if ($this->isVersion6()) {
            return sprintf(
                $this->pi6PauseUrlTemplate,
                $this->hostname,
            );
        } else {
            return sprintf(
                $this->pi5PauseUrlTemplate,
                $this->hostname,
                $timeout + 2,
                $this->api_key,
            );
        }
    }

    public function isVersion6(): bool
    {
        return $this->version >= 6;
    }

    public function requiresAuthentication(): bool
    {
        return $this->isVersion6();
    }

    public function getAuthUrl(): ?string
    {
        if ($this->requiresAuthentication()) {
            return sprintf(
                'https://%s/api/auth',
                $this->hostname,
            );
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'name'        => 'string',
            'api_key'     => 'string',
            'password'    => 'string',
            'version'     => 'integer',
            'hostname'    => 'string',
            'description' => 'string',
        ];
    }
}
