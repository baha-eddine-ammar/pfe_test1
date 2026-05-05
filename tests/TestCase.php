<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('cache.default', 'array');
        config()->set('broadcasting.default', 'log');
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
