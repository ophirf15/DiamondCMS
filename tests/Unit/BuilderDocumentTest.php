<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Builder\Support\BuilderDocument;
use InvalidArgumentException;
use Tests\TestCase;

final class BuilderDocumentTest extends TestCase
{
    public function test_valid_builder_document_renders_server_side_html(): void
    {
        $document = BuilderDocument::empty('Hello');

        $html = (string) BuilderDocument::render($document);

        $this->assertStringContainsString('<section', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    public function test_invalid_block_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BuilderDocument::validate([
            'schema' => 1,
            'blocks' => [
                ['type' => 'script', 'props' => []],
            ],
        ]);
    }

    public function test_custom_html_is_sanitized(): void
    {
        $html = (string) BuilderDocument::render([
            'schema' => 1,
            'blocks' => [
                ['type' => 'html', 'props' => ['html' => '<p onclick="alert(1)">Safe</p><script>alert(1)</script>']],
            ],
        ]);

        $this->assertStringContainsString('<p>Safe</p>', $html);
        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }
}
