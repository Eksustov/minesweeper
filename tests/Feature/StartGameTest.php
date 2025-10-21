<?php

it('started a game', function () {
    $page = visit('/');
    $page->click('Register')
        ->type('name', 'ohio')
        ->type('email', 'ohio@ohio.com')
        ->type('password', 'ohios123')
        ->type('password_confirmation', 'ohios123')
        ->check('checkbox')
        ->press('Register')
        ->press('Create Room')
        ->press('Start Game')
        ->screenshot()
        ->assertSee('Minesweeper');
});
