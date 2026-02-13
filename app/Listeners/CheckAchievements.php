<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Events\PurchaseMade;
use App\Services\AchievementService;

class CheckAchievements
{
    public function __construct(
        protected AchievementService $achievementService
    ) {}

    public function handle(PurchaseMade $event): void
    {
        $unlockedAchievements = $this->achievementService->checkAndUnlock($event->user, $event->purchase);

        foreach ($unlockedAchievements as $achievement) {
            AchievementUnlocked::dispatch($achievement, $event->user);
        }
    }
}
