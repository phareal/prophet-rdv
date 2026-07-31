<?php

declare(strict_types=1);

namespace ProphetCore\Tests\Unit\Support;

use ProphetCore\Support\Version;
use ProphetCore\Tests\TestCase;

final class VersionTest extends TestCase
{
    public function test_la_version_du_plugin_est_exposee(): void
    {
        $this->assertSame('1.0.0', Version::current());
    }
}
