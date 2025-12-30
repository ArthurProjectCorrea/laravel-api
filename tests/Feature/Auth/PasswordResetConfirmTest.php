<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\PasswordReset;

beforeEach(function () {
    Event::fake();
});

it('resets password with valid token', function () {
    $user = User::factory()->create(['email' => 'paula@funac.mt.gov.br', 'password' => bcrypt('oldpass')]);

    $token = Password::createToken($user);

    $response = $this->postJson('/api/auth/password/reset', [
        'email' => 'paula@funac.mt.gov.br',
        'token' => $token,
        'password' => 'newsecurepass',
        'password_confirmation' => 'newsecurepass',
    ]);

    $response->assertSuccessful()->assertJson(['message' => 'Senha redefinida com sucesso.']);

    // login with new password works
    $this->postJson('/api/auth/login', ['email' => 'paula@funac.mt.gov.br', 'password' => 'newsecurepass', 'app_name' => 'erp-web'])
        ->assertSuccessful();

    Event::assertDispatched(PasswordReset::class);
});

it('fails with invalid token', function () {
    $user = User::factory()->create(['email' => 'fran@funac.mt.gov.br']);

    $response = $this->postJson('/api/auth/password/reset', [
        'email' => 'fran@funac.mt.gov.br',
        'token' => 'not-a-valid-token',
        'password' => 'whatever123',
        'password_confirmation' => 'whatever123',
    ]);

    $response->assertStatus(400)->assertJson(['message' => 'Token inválido ou expirado.']);
});

it('token cannot be reused', function () {
    $user = User::factory()->create(['email' => 'reuse@funac.mt.gov.br']);

    $token = Password::createToken($user);

    $this->postJson('/api/auth/password/reset', [
        'email' => 'reuse@funac.mt.gov.br',
        'token' => $token,
        'password' => 'firstpass',
        'password_confirmation' => 'firstpass',
    ])->assertSuccessful();

    // reuse should fail
    $this->postJson('/api/auth/password/reset', [
        'email' => 'reuse@funac.mt.gov.br',
        'token' => $token,
        'password' => 'secondpass',
        'password_confirmation' => 'secondpass',
    ])->assertStatus(400);
});

it('expired token fails', function () {
    $user = User::factory()->create(['email' => 'old@funac.mt.gov.br']);

    // insert token with old timestamp directly
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => 'oldtoken123',
        'created_at' => now()->subHours(5),
    ]);

    $this->postJson('/api/auth/password/reset', [
        'email' => $user->email,
        'token' => 'oldtoken123',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertStatus(400);
});
