<?php

it('is at welcome page', function () {
    $page = visit('/');
    $page->click('Register')
        ->screenshot()
        ->assertSee('Register');
});
