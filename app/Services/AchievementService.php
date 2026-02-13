<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use App\Repositories\Contracts\AchievementRepositoryInterface;
use Illuminate\Support\Collection;

class AchievementService
{
  public function __construct(
    protected AchievementRepositoryInterface $achievementRepository
  ) {}

  public function checkAndUnlock(User $user, Purchase $purchase): Collection
  {
    $totalSpent = $user->total_spent;
    $existingIds = $user->achievements()->pluck('achievements.id')->toArray();

    // Only fetch achievements the user hasn't unlocked and can now afford
    $qualifyingAchievements = $this->achievementRepository->getQualifying($totalSpent, $existingIds);

    if ($qualifyingAchievements->isEmpty()) {
      return collect();
    }

    // Batch attach — single INSERT for all qualifying achievements
    $toAttach = [];
    foreach ($qualifyingAchievements as $achievement) {
      $toAttach[$achievement->id] = ['unlocked_at' => now()];
    }
    $user->achievements()->attach($toAttach);

    return $qualifyingAchievements;
  }
}
