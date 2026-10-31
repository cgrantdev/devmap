<?php

namespace Tests\Unit;

use App\Support\StoreUrlGuard;
use PHPUnit\Framework\TestCase;

class StoreUrlGuardTest extends TestCase
{
    public function test_blocks_private_loopback_and_link_local_literals(): void
    {
        foreach ([
            'http://127.0.0.1/shop',
            'http://10.1.2.3/shop',
            'http://192.168.1.9/shop',
            'http://172.16.4.4/shop',
            'http://169.254.1.1/shop',
            'http://0.0.0.0/shop',
            'http://[::1]/shop',
            'http://[fe80::1]/shop',
        ] as $url) {
            $this->assertTrue(StoreUrlGuard::isBlocked($url), $url);
        }
    }

    public function test_allows_a_public_literal(): void
    {
        $this->assertFalse(StoreUrlGuard::isBlocked('http://8.8.8.8/shop'));
        $this->assertFalse(StoreUrlGuard::isBlocked('http://1.1.1.1/shop'));
    }
}
