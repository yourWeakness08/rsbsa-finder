<?php

namespace App\Services;

use App\Models\ActivityLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityLogger
{
    public function log(
        int $userId = 0,
        string $table = '',
        string $message = '',
        string $action,
        string $status,
        ?Request $request = null
    ): ActivityLogs {
        $request ??= request();

        return ActivityLogs::create([
            'user_id' => $userId ?? auth()->id(),
            'table' => $table,
            'message' => $message,
            'action' => $action,
            'status' => $status,
            'ip' => $request?->ip(),
            'uuid' => Str::random(12)
        ]);
    }
}