<?php

it('is at welcome page', function () {
    $page = visit('/');
    $page->click('Register')
        ->type('name', 'ohio')
        ->type('email', 'ohio@ohio.com')
        ->type('password', 'ohios123')
        ->type('password_confirmation', 'ohios123')
        ->type('password_confirmation', 'ohios123')
        ->check('checkbox')
        ->click('Register')
        ->click('Create Room')
        ->screenshot()
        ->assertSee('host');
});
