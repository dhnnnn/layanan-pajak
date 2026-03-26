<?php

test('debug home page', function () {
    $response = $this->get('/');
    dump($response->getStatusCode());
    if ($response->getStatusCode() === 500) {
        $response->dump();
    }
    $response->assertStatus(302); // Expecting redirect to login
});
