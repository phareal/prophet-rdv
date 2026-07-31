<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\PostTypes;

use Brain\Monkey\Functions;
use ProphetCore\PostTypes\Service;
use ProphetCore\Tests\TestCase;

final class ServiceTest extends TestCase
{
    public function test_le_type_est_enregistre_public_et_ordonnable(): void
    {
        $capture = [];

        Functions\when('register_post_type')->alias(function ($slug, $args) use (&$capture) {
            $capture = ['slug' => $slug, 'args' => $args];
        });
        Functions\when('__')->returnArg();
        Functions\when('add_action')->alias(fn ($hook, $callback) => $callback());

        Service::register();

        $this->assertSame('service', $capture['slug']);
        $this->assertTrue($capture['args']['public']);
        $this->assertContains('page-attributes', $capture['args']['supports']);
        $this->assertSame('dashicons-star-filled', $capture['args']['menu_icon']);
    }
}
