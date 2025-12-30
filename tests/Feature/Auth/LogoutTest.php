<?php

use App\Models\User;

it('invalidates only the token used for logout', function () {
    $user = User::factory()->create(['email' => 'ana@funac.mt.gov.br']);

    $t1 = $user->createToken('client-a')->plainTextToken;
    // create second token
    $t2 = $user->createToken('client-b')->plainTextToken;

    // use token 1 to logout
    $this->withHeader('Authorization', 'Bearer '.$t1)
        ->postJson('/api/auth/logout')
        ->assertSuccessful();

    // Refresh tokens from DB
    $tokens = $user->tokens()->get();

    $this->assertTrue((bool) $tokens->firstWhere('name', 'client-a')->revoked);
    $this->assertFalse((bool) $tokens->firstWhere('name', 'client-b')->revoked);
});

it('logout without token returns unauthorized (protected endpoint)', function () {
    $this->postJson('/api/auth/logout')->assertStatus(401);
});

it('revoked token cannot access protected route', function () {
    $user = User::factory()->create(['email' => 'revoked@funac.mt.gov.br']);

    $token = $user->createToken('to-revoke')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertSuccessful();

    // same token should be rejected
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertStatus(401);
});
