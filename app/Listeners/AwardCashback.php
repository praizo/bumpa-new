<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class AwardCashback implements ShouldQueue
{
    public function handle(BadgeUnlocked $event): void
    {
        $amount = $event->badge->cashback_amount ?? 0;

        if ($amount > 0) {
            // Mock Payment / Wallet Top-up Logic
            Log::info("CASHBACK AWARDED: User {$event->user->id} earned {$amount} Naira for badge '{$event->badge->name}'.");
            
            // In a real system, you would call a WalletService here:
            // $walletService->credit($event->user, $amount, "Badge Reward: {$event->badge->name}");
        }
    }
}
