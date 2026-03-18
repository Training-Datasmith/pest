<?php

declare(strict_types=1);

use Laravel\Dusk\Browser;

it('has {name} page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/{name}')
            ->assertSee('{name}');
    });
});
