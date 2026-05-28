<?php

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
