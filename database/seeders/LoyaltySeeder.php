<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Badge;
use Illuminate\Database\Seeder;

class LoyaltySeeder extends Seeder
{
    public function run(): void
    {
        // Create Achievements (Amount Based)
        $achievements = [
            ['name' => 'First Steps', 'spend' => 10000, 'desc' => 'Spend over 1,000 NGN.'],
            ['name' => 'Moving Up', 'spend' => 50000, 'desc' => 'Spend over 5,000 NGN.'],
            ['name' => 'Big Spender I', 'spend' => 100000, 'desc' => 'Spend over 10,000 NGN.'],
            ['name' => 'Halfway Hero', 'spend' => 200000, 'desc' => 'Spend over 20,000 NGN.'],
            ['name' => 'Big Spender II', 'spend' => 300000, 'desc' => 'Spend over 30,000 NGN.'],
            ['name' => 'High Roller', 'spend' => 500000, 'desc' => 'Spend over 50,000 NGN.'],
        ];

        foreach ($achievements as $ach) {
            Achievement::firstOrCreate([
                'name' => $ach['name'],
                'required_spend' => $ach['spend'],
            ], [
                'description' => $ach['desc'],
            ]);
        }

        // Create Badges (Achievement Count Based)
        $badges = [
            ['name' => 'Bronze Badge', 'count' => 1, 'cashback' => 300, 'desc' => 'Unlock 1 achievement.'],
            ['name' => 'Gold Badge', 'count' => 2, 'cashback' => 500, 'desc' => 'Unlock 2 achievements.'],
            ['name' => 'Platinum Badge', 'count' => 3, 'cashback' => 700, 'desc' => 'Unlock 3 achievements.'],
            ['name' => 'Diamond Badge', 'count' => 6, 'cashback' => 1000, 'desc' => 'Unlock 6 achievements.'],
        ];

        foreach ($badges as $badge) {
            Badge::firstOrCreate([
                'name' => $badge['name'],
                'required_achievements' => $badge['count'],
            ], [
                'description' => $badge['desc'],
                'cashback_amount' => $badge['cashback'],
            ]);
        }
    }
}
