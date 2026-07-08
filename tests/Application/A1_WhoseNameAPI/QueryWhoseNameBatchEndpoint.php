<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);


gest('usage', 'Batch querying echoes each query and returns 200 with its answer, in order, when all resolve', function () {
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
        ['u' => 'test@example.org', 's' => 'jira',  'q' => 'slack', 'a' => 'U123456'],
        ['u' => 'U234567',          's' => 'slack', 'q' => 'jira',  'a' => 'other@example.org'],
    ]);
});


gest('usage', 'Batch querying returns a list of names where a service holds several', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->postJson('/api/whose-name/query/batch', [
        'queries' => [
            ['u' => 'U234567',        's' => 'slack', 'q' => 'email'],
            ['u' => 'new@example.org', 's' => 'email', 'q' => 'slack'],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertExactJson([
        ['u' => 'U234567',        's' => 'slack', 'q' => 'email', 'a' => ['other@example.org', 'new@example.org']],
        ['u' => 'new@example.org', 's' => 'email', 'q' => 'slack', 'a' => 'U234567'],
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
        ['u' => 'test@example.org', 's' => 'jira',    'q' => 'slack',   'a' => 'U123456'],
        ['u' => 'unknown',          's' => 'unknown', 'q' => 'unknown', 'a' => null],
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
