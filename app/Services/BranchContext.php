<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

class BranchContext
{
    public const KEY = 'branch';

    private ?Collection $active = null;

    private ?Branch $current = null;

    private bool $resolved = false;

    private array $preferred = [];

    public function active(): Collection
    {
        return $this->active ??= Branch::query()->active()->orderBy('name')->get();
    }

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

    public function prefer(array $slugs): void
    {
        $this->active = null;
        $this->setPreferred($slugs);
    }

    private function setPreferred(array $slugs): void
    {
        $this->preferred = $slugs;
        $this->resolved = false;
        $this->current = null;
    }

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

    public function selectBranchOf(int $branchId): void
    {
        $branch = $this->active()->firstWhere('id', $branchId);

        if ($branch !== null && $branch->id !== $this->currentId()) {
            $this->select($branch->slug);
        }
    }
}
