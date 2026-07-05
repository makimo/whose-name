<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);


gest('usage', 'Batch querying returns 200 with a username per query, in order, when all resolve', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->postJson('/api/whose-name/query/batch', [
        'queries' => [
            ['u' => 'test@example.org', 's' => 'jira',  'q' => 'slack'],
            ['u' => 'U234567',          's' => 'slack', 'q' => 'jira'],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertExactJson([
        ['username' => 'U123456'],
        ['username' => 'other@example.org'],
    ]);
});


gest('edge', 'Batch querying returns 207 with null for unknown entries alongside found ones', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->postJson('/api/whose-name/query/batch', [
        'queries' => [
            ['u' => 'test@example.org', 's' => 'jira',    'q' => 'slack'],
            ['u' => 'unknown',          's' => 'unknown', 'q' => 'unknown'],
        ],
    ]);

    $response->assertStatus(207);
    $response->assertExactJson([
        ['username' => 'U123456'],
        ['username' => null],
    ]);
});


gest('edge', 'Batch querying with a missing or empty queries array is rejected', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $this->postJson('/api/whose-name/query/batch', [])->assertStatus(422);
    $this->postJson('/api/whose-name/query/batch', ['queries' => []])->assertStatus(422);
});


gest('edge', 'Batch querying with a malformed query item is rejected', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $this->postJson('/api/whose-name/query/batch', [
        'queries' => [
            ['u' => 'test@example.org', 's' => 'jira'], // missing q
        ],
    ])->assertStatus(422);
});
