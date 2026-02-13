<?php

use App\Models\User;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Purchase;
use App\Events\PurchaseMade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

test('purchase unlocks achievement and badge based on spend', function () {
  // 1. Setup Data
  $user = User::factory()->create();

  // Create Achievement requiring 5000 NGN
  $achievement = Achievement::factory()->create([
    'name' => 'First Buy',
    'required_spend' => 5000,
  ]);

  // Create Badge requiring 1 achievement
  $badge = Badge::factory()->create([
    'name' => 'Novice Badge',
    'required_achievements' => 1,
    'cashback_amount' => 50,
  ]);

  // 2. Perform Action (Simulate Purchase of 5000)
  $purchase = Purchase::create([
    'user_id' => $user->id,
    'amount' => 5000,
    'reference' => 'REF-' . uniqid(),
  ]);

  // Update total_spent (controller does this atomically, but test bypasses it)
  $user->increment('total_spent', 5000);

  // Dispatch purchase event — synchronous listeners handle the chain
  PurchaseMade::dispatch($purchase, $user->fresh());

  // 3. Assertions

  // Assert Achievement Unlocked (Spend 5000 >= Required 5000)
  $this->assertDatabaseHas('user_achievements', [
    'user_id' => $user->id,
    'achievement_id' => $achievement->id,
  ]);

  // Assert Badge Unlocked (1 Achievement >= Required 1)
  $this->assertDatabaseHas('user_badges', [
    'user_id' => $user->id,
    'badge_id' => $badge->id,
  ]);

  // Assert User Total Spent Updated
  $this->assertDatabaseHas('users', [
    'id' => $user->id,
    'total_spent' => 5000,
  ]);
});

test('api returns correct progress', function () {
  $user = User::factory()->create();
  $achievement = Achievement::factory()->create(['name' => 'First Buy', 'required_spend' => 1000]);
  $nextAchievement = Achievement::factory()->create(['name' => 'Big Spender', 'required_spend' => 5000]);

  $badge = Badge::factory()->create([
    'name' => 'Gold',
    'required_achievements' => 2
  ]);

  // Manually attach one achievement
  DB::table('user_achievements')->insert([
    'user_id' => $user->id,
    'achievement_id' => $achievement->id,
    'unlocked_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
  ]);

  $response = $this->actingAs($user)->getJson("/api/users/{$user->id}/achievements");

  $response->assertStatus(200)
    ->assertJson([
      'unlocked_achievements' => ['First Buy'],
      'next_available_achievements' => [
        [
          'name' => 'Big Spender',
          'required_spend' => 5000,
          'remaining_spend' => 5000,
        ]
      ],
      'current_badge' => 'None',
      'next_badge' => 'Gold',
      'remaining_to_unlock_next_badge' => 1,
      'next_achievement_progress' => [
        'name' => 'Big Spender',
        'required_spend' => 5000,
        'remaining_spend' => 5000,
      ]
    ]);
});

test('purchase response contains unlocked achievements and badges', function () {
  $user = User::factory()->create();

  // Create Achievement requiring 5000 NGN
  $achievement = Achievement::factory()->create([
    'name' => 'Big Spender',
    'required_spend' => 5000,
  ]);

  // Create Badge requiring 1 achievement
  $badge = Badge::factory()->create([
    'name' => 'Spender Badge',
    'required_achievements' => 1,
    'cashback_amount' => 100,
  ]);

  $response = $this->actingAs($user)->postJson('/api/purchase', [
    'amount' => 5000,
    'items' => [['name' => 'Item 1', 'price' => 5000, 'qty' => 1]],
  ]);

  $response->assertStatus(201)
    ->assertJsonStructure([
      'success',
      'message',
      'data' => [
        'purchase_id',
        'reference',
        'amount',
        'unlocked_achievements',
        'unlocked_badges',
      ]
    ])
    ->assertJsonFragment([
      'amount' => 5000,
    ]);

  // Verify specific content in the response
  $responseData = $response->json('data');

  // Check unlocked achievement
  expect($responseData['unlocked_achievements'])->toHaveCount(1);
  expect($responseData['unlocked_achievements'][0]['name'])->toBe('Big Spender');

  // Check unlocked badge
  expect($responseData['unlocked_badges'])->toHaveCount(1);
  expect($responseData['unlocked_badges'][0]['name'])->toBe('Spender Badge');
});
