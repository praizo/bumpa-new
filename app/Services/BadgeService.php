<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\AchievementRepositoryInterface;
use App\Repositories\Contracts\BadgeRepositoryInterface;
use Illuminate\Support\Collection;

class BadgeService
{
  public function __construct(
    protected BadgeRepositoryInterface $badgeRepository,
    protected AchievementRepositoryInterface $achievementRepository,
  ) {}

  public function checkAndUnlock(User $user): Collection
  {
    $achievementCount = $user->achievements()->count();
    $existingBadgeIds = $user->badges()->pluck('badges.id')->toArray();

    // Only fetch badges the user hasn't unlocked and now qualifies for
    $qualifyingBadges = $this->badgeRepository->all()
      ->whereNotIn('id', $existingBadgeIds)
      ->where('required_achievements', '<=', $achievementCount)
      ->values();

    if ($qualifyingBadges->isEmpty()) {
      return collect();
    }

    // Batch attach — single INSERT
    $toAttach = [];
    foreach ($qualifyingBadges as $badge) {
      $toAttach[$badge->id] = ['unlocked_at' => now()];
    }
    $user->badges()->attach($toAttach);

    return $qualifyingBadges;
  }

  public function getNextBadgeProgress(User $user): array
  {
    $currentCount = $user->achievements()->count();
    $nextBadge = $this->badgeRepository->getNextBadge($currentCount);

    $currentBadge = $user->badges()
      ->orderBy('badges.required_achievements', 'desc')
      ->first();

    $progress = [
      'next_badge' => null,
      'remaining_achievements' => 0,
      'current_badge' => $currentBadge ? $currentBadge->name : 'None',
      'next_achievement' => null,
    ];

    if ($nextBadge) {
      $progress['next_badge'] = $nextBadge->name;
      $progress['remaining_achievements'] = $nextBadge->required_achievements - $currentCount;

      $totalSpent = $user->total_spent ?? 0;
      $unlockedIds = $user->achievements()->pluck('achievements.id')->toArray();

      $nextAchievement = $this->achievementRepository->getNextAchievement($totalSpent, $unlockedIds);

      if ($nextAchievement) {
        $progress['next_achievement'] = [
          'name' => $nextAchievement->name,
          'required_spend' => (float) $nextAchievement->required_spend,
          'remaining_spend' => max(0, $nextAchievement->required_spend - $totalSpent),
        ];
      }
    }

    return $progress;
  }
}
