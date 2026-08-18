<?php

/**
 * @since 2605.2.0-bs
 *
 * @version 2605.4.1-bs
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * @var array<int, string>
     */
    #[\Override]
    protected $fillable = [
        'key',
        'value',
    ];
}
