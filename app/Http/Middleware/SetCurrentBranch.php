<?php

namespace App\Http\Middleware;

use App\Services\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menentukan cabang terpilih penyewa. `?cabang=slug` (GET) memilih cabang lalu
 * redirect ke URL yang sama tanpa parameter itu.
 */
class SetCurrentBranch
{
    public function __construct(private BranchContext $branches) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->branches->prefer([
            $request->session()->get(BranchContext::KEY),
            $request->cookie(BranchContext::KEY),
        ]);

        $requested = $request->query('cabang');

        if ($request->isMethod('GET') && is_string($requested) && $requested !== '') {
            $this->branches->select($requested);

            return redirect()->to($request->fullUrlWithoutQuery(['cabang']));
        }

        return $next($request);
    }
}
