<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('scaffold.{jobId}', function ($user, $jobId) {
    // TODO: Add authorization logic
    return true;
});
