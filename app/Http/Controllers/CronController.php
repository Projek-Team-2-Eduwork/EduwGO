<?php

namespace App\Http\Controllers;

use App\Http\Requests\CronRunRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class CronController extends Controller
{
    /**
     * Pengganti scheduler di hosting tanpa cron (Render free): dipanggil cron eksternal.
     */
    public function run(CronRunRequest $request): JsonResponse
    {
        $task = $request->task();
        $exitCode = Artisan::call($task);

        return response()->json([
            'status' => $exitCode === 0 ? 'ok' : 'error',
            'task' => $task,
            'output' => trim(Artisan::output()),
        ], $exitCode === 0 ? 200 : 500);
    }
}
