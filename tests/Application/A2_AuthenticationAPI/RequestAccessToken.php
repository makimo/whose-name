<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;


use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);


test('A valid email, password and title yields an access token (as `token` in response JSON)', function () {
    $password = Str::random(16);

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    
    $response = $this->postJson('/api/request-token', [
        'email' => $user->email,
        'title' => 'Title',
        'password' => $password,
    ]);

    $response
        ->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('token')
                ->has('title')
                ->where('abilities', [])
                ->missing('errors')
        );

});

test('A whose-name ability on a token allows access to whose-name service', function () {
    $user = User::factory()->create([
        'password' => Hash::make('test'),
    ]);
    
    $response = $this->postJson('/api/request-token', [
        'email' => $user->email,
        'title' => 'Title',
        'password' => 'test',
        'abilities' => [ 'whose-name' ]
    ]);

    $response
        ->assertStatus(201)
        ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('token')
                ->has('title')
                ->where('abilities', [ 'whose-name'])
                ->missing('errors')
        );
    
    // Testing code removed
    // Due to how Laravel tests requests here it is misleading
    // to put any code accessing the whose-name service
    // as it would yield false positives or negatives
});


test('To request a token, email, password and title must be present', function () {
    $response = $this->postJson('/api/request-token', [
        'email' => 'someone@example.org',
        'title' => 'Title',
    ]);

    $response2 = $this->postJson('/api/request-token', [
        'title' => 'Title',
        'password' => 'test',
    ]);

    $response3 = $this->postJson('/api/request-token', [
        'email' => 'someone@example.org',
        'password' => 'test',
    ]);

    $response
        ->assertStatus(422)
        ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('errors.password')
                ->has('message')
        );
    
    $response2
        ->assertStatus(422)
        ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('errors.email')
                ->has('message')
        );
    
    $response3
        ->assertStatus(422)
        ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('errors.title')
                ->has('message')
        );
});

test('If e-mail and password does not match database, a 401 error is returned', function() {
    $response = $this->postJson('/api/request-token', [
        'email' => 'someone@example.org',
        'title' => 'Title',
        'password' => 'test',
    ]);

    $response
        ->assertStatus(401)
        ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has('errors.login')
                ->has('message')
        );
});
