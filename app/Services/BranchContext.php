<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

/**
 * Cabang yang sedang dipilih penyewa (session -> cookie -> cabang aktif pertama).
 * Katalog, home, dan detail selalu terfilter ke satu cabang, tidak pernah "semua cabang".
 * Singleton per request: daftar cabang aktif hanya di-query sekali.
 */
class BranchContext
{
    public const KEY = 'branch';

    private ?Collection $active = null;

    private ?Branch $current = null;

    private bool $resolved = false;

    /** @var array<int, string> slug kandidat berurutan (query/session/cookie) */
    private array $preferred = [];

    /**
     * @return Collection<int, Branch> cabang aktif, urut nama
     */
    public function active(): Collection
    {
        return $this->active ??= Branch::query()->active()->orderBy('name')->get();
    }

    /**
     * Cabang terpilih; null hanya bila belum ada cabang aktif sama sekali.
     */
    public function current(): ?Branch
    {
        if (! $this->resolved) {
            $active = $this->active()->keyBy('slug');

            foreach ($this->preferred as $slug) {
                if ($slug !== null && $active->has($slug)) {
                    $this->current = $active->get($slug);
                    break;
                }
            }

            $this->current ??= $active->first();
            $this->resolved = true;
        }

        return $this->current;
    }

    public function currentId(): ?int
    {
        return $this->current()?->id;
    }

    /**
     * Slug kandidat dari request, urut prioritas. Dipanggil middleware di awal tiap request,
     * sekaligus membuang memo request sebelumnya (instance bisa hidup lintas request di tes/Octane).
     *
     * @param  array<int, string|null>  $slugs
     */
    public function prefer(array $slugs): void
    {
        $this->active = null;
        $this->setPreferred($slugs);
    }

    /**
     * @param  array<int, string|null>  $slugs
     */
    private function setPreferred(array $slugs): void
    {
        $this->preferred = $slugs;
        $this->resolved = false;
        $this->current = null;
    }

    /**
     * Simpan pilihan di session & cookie (1 tahun). False bila cabang tidak aktif.
     */
    public function select(string $slug): bool
    {
        $branch = $this->active()->firstWhere('slug', $slug);

        if ($branch === null) {
            return false;
        }

        session()->put(self::KEY, $branch->slug);
        Cookie::queue(self::KEY, $branch->slug, 60 * 24 * 365);
        $this->setPreferred([$branch->slug]);

        return true;
    }

    /**
     * Pindah ke cabang pemilik unit (mis. buka tautan detail motor cabang lain).
     */
    public function selectBranchOf(int $branchId): void
    {
        $branch = $this->active()->firstWhere('id', $branchId);

        if ($branch !== null && $branch->id !== $this->currentId()) {
            $this->select($branch->slug);
        }
    }
}
