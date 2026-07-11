<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Core\Support\Version;
use Tests\TestCase;

final class VersionTest extends TestCase
{
    public function test_current_version_is_readable(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', Version::current());
    }

    public function test_builder_schema_version_is_configured(): void
    {
        $this->assertSame(1, Version::builderSchema());
    }
}
