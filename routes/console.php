<?php

/**
 * @since 2605.1.0-bs
 *
 * @version 2605.4.1-bs
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
