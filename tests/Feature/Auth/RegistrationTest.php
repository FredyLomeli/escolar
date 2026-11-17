<?php

test('public registration screen is disabled', function () {
    $this->get('/register')->assertNotFound();
});

test('public registration post endpoint is disabled', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});
