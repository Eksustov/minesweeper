<?php

it('created a Room', function () {
    $page = visit('/');
    $page->click('Create Room')
        ->click('Create an account')
        ->screenshot()
        ->assertSee('Register')
        ->type('name', 'ohio')
        ->type('email', 'ohio@ohio.com')
        ->type('password', 'ohios123')
        ->type('password_confirmation', 'ohios123')
        ->type('password_confirmation', 'ohios123')
        ->check('checkbox')
        ->click('Register')
        ->click('Create Room')
        ->assertSee('host');
});
