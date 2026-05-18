<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('legacy:hello', function () {
    $this->info('Legacy command');
});
