<?php

it('throttles repeated login attempts', function () {
    $email = 'ratelimit@funac.mt.gov.br';

    for ($i = 0; $i < 5; $i++) {
        $res = $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'x', 'app_name' => 'r']);
        $this->assertContains($res->status(), [401, 422]);
    }

    // 6th attempt should be throttled (429)
    $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'x', 'app_name' => 'r'])->assertStatus(429);
});

it('throttles repeated password reset requests', function () {
    $email = 'ratelimit2@funac.mt.gov.br';

    for ($i = 0; $i < 3; $i++) {
        $res = $this->postJson('/api/auth/password/forgot', ['email' => $email, 'frontend_url' => 'https://erp/reset']);
        $this->assertContains($res->status(), [200, 422]);
    }

    // 4th attempt should be throttled
    $this->postJson('/api/auth/password/forgot', ['email' => $email, 'frontend_url' => 'https://erp/reset'])->assertStatus(429);
});
