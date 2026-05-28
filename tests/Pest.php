<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest Bootstrap
|--------------------------------------------------------------------------
|
| Configure Pest to use the Laravel test case by default for all tests
| in the Feature suite, so we can use $this->getJson(), mocking, etc.
|
*/

pest()->extend(TestCase::class)->in('Feature', 'Unit');
