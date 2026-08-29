<?php

namespace Hawkiq\LaravelPsnApi\Facades;

use Illuminate\Support\Facades\Facade;

class PsnApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'psnapi';
    }
}
