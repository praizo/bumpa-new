<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Services\BadgeService;

class CheckBadges
{
    public function __construct(
        protected BadgeService $badgeService
    ) {}

    public function handle(AchievementUnlocked $event): void
    {
        $unlockedBadges = $this->badgeService->checkAndUnlock($event->user);

        foreach ($unlockedBadges as $badge) {
            BadgeUnlocked::dispatch($badge, $event->user);
        }
    }
}
