<?php

declare(strict_types=1);

namespace App\Domains\Activity\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ActivityLogger
{
    public static function log(string $event, ?object $subject = null, array $properties = [], ?Request $request = null): void
    {
        try {
            DB::table('activity_logs')->insert([
                'user_id' => ($request !== null ? $request->user()?->id : null) ?? auth()->id(),
                'event' => $event,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject->id ?? null,
                'properties' => json_encode($properties, JSON_THROW_ON_ERROR),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable) {
            if (config('app.debug')) {
                report(new \RuntimeException('Activity logging failed.'));
            }
        }
    }
}
