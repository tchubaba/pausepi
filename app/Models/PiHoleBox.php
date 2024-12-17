<?php

declare(strict_types=1);

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $api_key
 * @property string $hostname
 * @property string $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|PiHoleBox newModelQuery()
 * @method static Builder|PiHoleBox newQuery()
 * @method static Builder|PiHoleBox query()
 * @method static Builder|PiHoleBox whereName()
 * @method static Builder|PiHoleBox whereApiKey()
 * @method static Builder|PiHoleBox whereHostName()
 * @method static Builder|PiHoleBox whereDescription()
 * @method static Builder|PiHoleBox whereCreatedAt($value)
 * @method static Builder|PiHoleBox whereId($value)
 * @method static Builder|PiHoleBox whereUpdatedAt($value)
 * @mixin Eloquent
 */
class PiHoleBox extends Model
{
    protected $table = 'pi_hole_boxes';

    protected string $piUrlTemplate = 'http://%s/admin/api.php?disable=%s&auth=%s';

    protected $fillable = [
        'name',
        'api_key',
        'hostname',
        'description',
    ];

    public function getPauseUrl(int $timeout): string
    {
        return sprintf(
            $this->piUrlTemplate,
            $this->hostname,
            $timeout + 2,
            $this->api_key,
        );
    }

    protected function casts(): array
    {
        return [
            'name'        => 'string',
            'api_key'     => 'string',
            'hostname'    => 'string',
            'description' => 'string',
        ];
    }
}
