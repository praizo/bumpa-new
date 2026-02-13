<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\AchievementRepositoryInterface;
use App\Services\BadgeService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        protected BadgeService $badgeService,
        protected AchievementRepositoryInterface $achievementRepository,
    ) {}

    public function show(Request $request, User $user)
    {
        if ($request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Eager load relations
        $user->load(['achievements', 'badges']);

        $progress = $this->badgeService->getNextBadgeProgress($user);

        // Get all achievements to determine which are locked
        $allAchievements = $this->achievementRepository->all();
        $unlockedIds = $user->achievements->pluck('id')->toArray();
        $nextAvailable = $allAchievements->whereNotIn('id', $unlockedIds)
            ->values()
            ->map(function ($achievement) use ($user) {
                return [
                    'name' => $achievement->name,
                    'required_spend' => (float) $achievement->required_spend,
                    'remaining_spend' => max(0, (float) $achievement->required_spend - (float) $user->total_spent),
                ];
            });

        return response()->json([
            'unlocked_achievements' => $user->achievements->pluck('name'),
            'next_available_achievements' => $nextAvailable,
            'current_badge' => $progress['current_badge'],
            'next_badge' => $progress['next_badge'],
            'remaining_to_unlock_next_badge' => $progress['remaining_achievements'],
            'next_achievement_progress' => $progress['next_achievement'],
        ]);
    }

    public function notifications(Request $request)
    {
        return response()->json([
            'notifications' => $request->user()->notifications,
        ]);
    }
}
