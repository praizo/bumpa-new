<?php

namespace App\Repositories\Contracts;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Collection;

interface AchievementRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Achievement;
    public function getNextAchievement(float $totalSpent, array $excludeIds): ?Achievement;
    public function getQualifying(float $totalSpent, array $excludeIds): Collection;
}
