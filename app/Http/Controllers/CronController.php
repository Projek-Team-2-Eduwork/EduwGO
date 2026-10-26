<?php

namespace App\Http\Controllers;

use App\Http\Requests\CronRunRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

class CronController extends Controller
{
    /**
     * Pengganti scheduler di hosting tanpa cron (Render free): dipanggil cron eksternal.
     */
    public function run(CronRunRequest $request): JsonResponse
    {
        $task = $request->task();

        // schedule:run menelan error tiap command; cek DB dulu supaya cron eksternal
        // melihat status gagal (dan mengirim notifikasi) saat database mati.
        try {
            DB::select('select 1');
        } catch (Throwable) {
            return response()->json([
                'status' => 'error',
                'task' => $task,
                'output' => 'Database tidak dapat dihubungi',
            ], 503);
        }

        $exitCode = Artisan::call($task);

        return response()->json([
            'status' => $exitCode === 0 ? 'ok' : 'error',
            'task' => $task,
            'output' => trim(Artisan::output()),
        ], $exitCode === 0 ? 200 : 500);
    }
}
