<?php

namespace App\Support;

use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisHealth
{
    public static function ping(): bool
    {
        if (! self::isConfigured()) {
            return false;
        }

        try {
            $response = Redis::connection()->ping();

            return in_array($response, [true, 'PONG', '+PONG'], true);
        } catch (Throwable) {
            return false;
        }
    }

    public static function isConfigured(): bool
    {
        return config('cache.default') === 'redis'
            || config('queue.default') === 'redis';
    }
}
