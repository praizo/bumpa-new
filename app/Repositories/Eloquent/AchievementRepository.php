<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\AchievementRepositoryInterface;
use App\Models\Achievement;
use Illuminate\Database\Eloquent\Collection;

class AchievementRepository implements AchievementRepositoryInterface
{
    public function all(): Collection
    {
        return Achievement::all();
    }

    public function find(int $id): ?Achievement
    {
        return Achievement::find($id);
    }

    public function getNextAchievement(float $totalSpent, array $excludeIds): ?Achievement
    {
        return Achievement::where('required_spend', '>', $totalSpent)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('required_spend', 'asc')
            ->first();
    }

    public function getQualifying(float $totalSpent, array $excludeIds): Collection
    {
        return Achievement::where('required_spend', '<=', $totalSpent)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('required_spend', 'asc')
            ->get();
    }
}
