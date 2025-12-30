<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;

it('allows login with valid credentials and domain', function () {
    $user = User::factory()->create([
        'email' => 'jose@funac.mt.gov.br',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'jose@funac.mt.gov.br',
        'password' => 'secret123',
        'app_name' => 'erp-web',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['message', 'data' => ['user' => ['id', 'email'], 'token']]);
});

it('rejects login when email domain is not allowed', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'bad@notallowed.com',
        'password' => 'irrelevant',
        'app_name' => 'erp-web',
    ]);

    $response->assertStatus(422);
});

it('allows logout and deletes current token', function () {
    $user = User::factory()->create(['email' => 'ana@funac.mt.gov.br']);
    $token = $user->createToken('test-client')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertSuccessful();

    // Token should be deleted: subsequent request to me should be unauthorized
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertStatus(401);
});

it('sends generic response when requesting password reset and allows reset with token', function () {
    $user = User::factory()->create(['email' => 'maria@funac.mt.gov.br']);

    $response = $this->postJson('/api/auth/password/forgot', [
        'email' => 'maria@funac.mt.gov.br',
        'frontend_url' => 'https://erp.example.local/reset',
    ]);

    $response->assertSuccessful();

    // simulate token creation as Password broker would do
    $token = Password::createToken($user);

    $reset = $this->postJson('/api/auth/password/reset', [
        'email' => 'maria@funac.mt.gov.br',
        'token' => $token,
        'password' => 'newpass123',
        'password_confirmation' => 'newpass123',
    ]);

    $reset->assertSuccessful();
});
