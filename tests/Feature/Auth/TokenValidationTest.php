<?php

use App\Models\User;

it('returns 401 for protected route without token', function () {
    $this->getJson('/api/auth/me')->assertStatus(401);
});

it('allows access with a valid token', function () {
    $user = User::factory()->create(['email' => 'ok@funac.mt.gov.br']);
    $token = $user->createToken('client')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertSuccessful();
});

it('revoked token is rejected', function () {
    $user = User::factory()->create(['email' => 'rev@funac.mt.gov.br']);
    $tokenPlain = $user->createToken('client')->plainTextToken;

    // mark as revoked
    $t = $user->tokens()->first();
    $t->revoked = true;
    $t->save();

    $this->withHeader('Authorization', 'Bearer '.$tokenPlain)
        ->getJson('/api/auth/me')
        ->assertStatus(401);
});
