<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Events\PurchaseMade;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'items' => 'sometimes|array'
        ]);

        $reference = 'PUR_' . Str::uuid()->toString();
        $user = $request->user();

        // Atomically update total spend
        $user->increment('total_spent', $request->amount);

        $items = $request->input('items', [
            ['name' => 'General Purchase', 'price' => $request->amount, 'qty' => 1]
        ]);

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'reference' => $reference,
            'items' => $items,
        ]);

        // Snapshot achievement/badge counts before event chain
        $achievementsBefore = $user->achievements()->pluck('achievements.id')->toArray();
        $badgesBefore = $user->badges()->pluck('badges.id')->toArray();

        // Dispatch event — synchronous listeners handle achievements, badges, and cashback
        PurchaseMade::dispatch($purchase, $user->fresh());

        // Read back newly unlocked items
        $user->refresh();
        $newAchievements = $user->achievements()->whereNotIn('achievements.id', $achievementsBefore)->get();
        $newBadges = $user->badges()->whereNotIn('badges.id', $badgesBefore)->get();

        return response()->json([
            'success' => true,
            'message' => 'Purchase recorded successfully',
            'data' => [
                'purchase_id' => $purchase->id,
                'reference' => $reference,
                'amount' => $purchase->amount,
                'unlocked_achievements' => $newAchievements,
                'unlocked_badges' => $newBadges,
            ]
        ], 201);
    }
}
