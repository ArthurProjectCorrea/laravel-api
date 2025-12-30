<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('sends notification for valid request and returns generic response', function () {
    $user = User::factory()->create(['email' => 'maria@funac.mt.gov.br']);

    $response = $this->postJson('/api/auth/password/forgot', [
        'email' => 'maria@funac.mt.gov.br',
        'frontend_url' => 'https://erp.example.local/reset',
    ]);

    $response->assertSuccessful()->assertJson(['message' => 'Se um usuário com esse email existir, enviaremos um link de redefinição.']);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('returns generic response for non-existing email and does not send notification', function () {
    Notification::fake();

    $response = $this->postJson('/api/auth/password/forgot', [
        'email' => 'unknown@funac.mt.gov.br',
        'frontend_url' => 'https://erp.example.local/reset',
    ]);

    $response->assertSuccessful();

    Notification::assertNothingSent();
});

it('blocks emails outside domain and does not send notification', function () {
    $response = $this->postJson('/api/auth/password/forgot', [
        'email' => 'intruder@example.com',
        'frontend_url' => 'https://erp.example.local/reset',
    ]);

    $response->assertStatus(422);

    Notification::assertNothingSent();
});

it('validates frontend_url', function () {
    $response = $this->postJson('/api/auth/password/forgot', [
        'email' => 'maria@funac.mt.gov.br',
    ]);

    $response->assertStatus(422);
});
