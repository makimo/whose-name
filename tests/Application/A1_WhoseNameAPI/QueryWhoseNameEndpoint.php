<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);


gest('usage', 'Querying the WhoseName API returns requested usernames', function ($u, $s, $q, $r) {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->get("/api/whose-name/query?u=$u&s=$s&q=$q");

    $response->assertStatus(200);
    $response->assertExactJson(['username' => $r]);
})->with([
    ['U123456', 'slack', 'jira', 'test@example.org'],
    ['U234567', 'slack', 'jira', 'other@example.org'],
    ['test@example.org', 'jira', 'slack', 'U123456'],
    ['other@example.org', 'jira', 'slack', 'U234567'],
]);


gest('usage', 'Querying a service with several names returns them as an array', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->get('/api/whose-name/query?u=U234567&s=slack&q=email');

    $response->assertStatus(200);
    $response->assertExactJson(['username' => ['other@example.org', 'new@example.org']]);
});


gest('usage', 'An identity can be looked up by any one of its several names', function ($u) {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->get("/api/whose-name/query?u=$u&s=email&q=slack");

    $response->assertStatus(200);
    $response->assertExactJson(['username' => 'U234567']);
})->with([
    'other@example.org',
    'new@example.org',
]);


gest('edge', 'Querying the WhoseName API with not known data returns a null value', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->get("/api/whose-name/query?u=unknown&s=unknown&q=unknown");

    $response->assertStatus(404);
    $response->assertExactJson(['username' => null]);
});