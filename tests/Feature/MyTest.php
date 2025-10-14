<?php

it('is at welcome page', function () {
    $page = visit('/');
    $page->click('Create Room')
        ->screenshot()
        ->assertSee('Minesweeper');
});
