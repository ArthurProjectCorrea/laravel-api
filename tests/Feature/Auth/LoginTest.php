<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;

beforeEach(function () {
    Notification::fake();
    Event::fake();
});

it('allows login with valid credentials and issues a token', function () {
    $user = User::factory()->create([
        'email' => 'test@funac.mt.gov.br',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@funac.mt.gov.br',
        'password' => 'secret123',
        'app_name' => 'erp-web',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['message', 'data' => ['user' => ['id', 'email'], 'token']]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'erp-web',
    ]);

    Event::assertDispatched(Login::class);
});

it('returns generic message for incorrect password and does not create token', function () {
    $user = User::factory()->create([
        'email' => 'jane@funac.mt.gov.br',
        'password' => bcrypt('rightpass'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'jane@funac.mt.gov.br',
        'password' => 'wrongpass',
        'app_name' => 'erp-web',
    ]);

    $response->assertStatus(401)->assertJson(['message' => 'Credenciais inválidas.']);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'erp-web',
    ]);

    Event::assertDispatched(Failed::class);
});

it('blocks emails outside allowed domain and returns validation error', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'bad@example.com',
        'password' => 'irrelevant',
        'app_name' => 'erp-web',
    ]);

    $response->assertStatus(422);
});

it('validates required fields for login', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response->assertStatus(422);
});

it('allows multiple tokens to coexist for the same user', function () {
    $user = User::factory()->create(['email' => 'multi@funac.mt.gov.br']);

    $user->createToken('app-a');
    $user->createToken('app-b');

    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'app-a']);
    $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'app-b']);
});
