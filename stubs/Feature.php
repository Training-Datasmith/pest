<?php

declare(strict_types=1);

it('has {name} page', function () {
    $response = $this->get('/{name}');

    $response->assertStatus(200);
});
