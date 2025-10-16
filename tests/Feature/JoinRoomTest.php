<?php

it('Created a Room', function () {
    $page = visit('/');
    $page->click('Register')
        ->type('name', 'ohio')
        ->type('email', 'ohio@ohio.com')
        ->type('password', 'ohios123')
        ->type('password_confirmation', 'ohios123')
        ->check('checkbox')
        ->press('Register')
        ->press('Create Room')
        ->press('ohio')
        ->click('Log Out')
        //Making a new account
        ->click('Register')
        ->type('name', 'ohio2')
        ->type('email', 'ohio2@ohio.com')
        ->type('password', 'ohios123')
        ->type('password_confirmation', 'ohios123')
        ->check('checkbox')
        ->press('Register')
        ->press('.bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors shadow-md hover:shadow-lg')
        ->wait(2)
        ->screenshot()
        ->assertSee('Room');
});
