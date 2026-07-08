<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);


// --- GET /pool : one {"username": ...} per member, 200/207/404 ---

gest('usage', 'Querying a pool resolves each member on the asked service, 200 when all resolve', function () {
    Sanctum::actingAs(User::factory()->create(), ['whose-name']);

    // Slackers = [U123456, U234567] on slack, asked email:
    //   U123456 -> single@example.org (one name)
    //   U234567 -> [other@example.org, new@example.org] (several)
    $response = $this->get('/api/whose-name/pool?p=Slackers&q=email');

    $response->assertStatus(200);
    $response->assertExactJson([
        ['u' => 'U123456', 's' => 'slack', 'q' => 'email', 'a' => 'single@example.org'],
        ['u' => 'U234567', 's' => 'slack', 'q' => 'email', 'a' => ['other@example.org', 'new@example.org']],
    ]);
});


gest('usage', 'Querying a pool for its own field returns its names verbatim', function () {
    Sanctum::actingAs(User::factory()->create(), ['whose-name']);

    $response = $this->get('/api/whose-name/pool?p=Slackers&q=slack');

    $response->assertStatus(200);
    $response->assertExactJson([
        ['u' => 'U123456', 's' => 'slack', 'q' => 'slack', 'a' => 'U123456'],
        ['u' => 'U234567', 's' => 'slack', 'q' => 'slack', 'a' => 'U234567'],
    ]);
});


gest('edge', 'A pool with a member that does not resolve returns 207 with a null in place', function () {
    Sanctum::actingAs(User::factory()->create(), ['whose-name']);

    // Mixed = [single@example.org, ghost@example.org] on email, asked jira:
    //   single@example.org -> test@example.org
    //   ghost@example.org  -> null (unknown)
    $response = $this->get('/api/whose-name/pool?p=Mixed&q=jira');

    $response->assertStatus(207);
    $response->assertExactJson([
        ['u' => 'single@example.org', 's' => 'email', 'q' => 'jira', 'a' => 'test@example.org'],
        ['u' => 'ghost@example.org',  's' => 'email', 'q' => 'jira', 'a' => null],
    ]);
});


gest('edge', 'Querying an unknown pool returns 404 with an empty array', function () {
    Sanctum::actingAs(User::factory()->create(), ['whose-name']);

    $response = $this->get('/api/whose-name/pool?p=Nobody&q=jira');

    $response->assertStatus(404);
    $response->assertExactJson([]);
});


// --- GET /pool/names : flattened, distinct list of names, 200/404 ---

gest('usage', 'Querying a pool\'s names returns a flat, distinct list', function () {
    Sanctum::actingAs(User::factory()->create(), ['whose-name']);

    $response = $this->get('/api/whose-name/pool/names?p=Slackers&q=email');

    $response->assertStatus(200);
    $response->assertExactJson([
        'names' => ['single@example.org', 'other@example.org', 'new@example.org'],
    ]);
});


gest('edge', 'A name reached through several members appears once', function () {
    Sanctum::actingAs(User::factory()->create(), ['whose-name']);

    // Aliases = [other@example.org, new@example.org] both belong to the same
    // identity, so both resolve to its single jira name.
    $response = $this->get('/api/whose-name/pool/names?p=Aliases&q=jira');

    $response->assertStatus(200);
    $response->assertExactJson([
        'names' => ['other@example.org'],
    ]);
});


gest('edge', 'Querying an unknown pool\'s names returns 404 with an empty list', function () {
    Sanctum::actingAs(User::factory()->create(), ['whose-name']);

    $response = $this->get('/api/whose-name/pool/names?p=Nobody&q=jira');

    $response->assertStatus(404);
    $response->assertExactJson(['names' => []]);
});
