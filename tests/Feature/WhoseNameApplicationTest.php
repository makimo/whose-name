<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;


test('Querying the WhoseName API returns requested usernames', function ($u, $s, $q, $r) {
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


test('Querying the WhoseName API with not known data returns a null value', function () {
    Sanctum::actingAs(
        User::factory()->create(),
        ['whose-name']
    );

    $response = $this->get("/api/whose-name/query?u=unknown&s=unknown&q=unknown");

    $response->assertStatus(404);
    $response->assertExactJson(['username' => null]);
});