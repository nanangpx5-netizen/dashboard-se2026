<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    #[Test]
    public function e_escapes_html(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
    }

    #[Test]
    public function e_escapes_quotes(): void
    {
        $this->assertSame('&quot;test&quot;', e('"test"'));
        $this->assertSame("&#039;test&#039;", e("'test'"));
    }

    #[Test]
    public function e_handles_null(): void
    {
        $this->assertSame('-', e(null));
    }

    #[Test]
    public function e_handles_empty_string(): void
    {
        $this->assertSame('', e(''));
    }

    #[Test]
    public function e_handles_custom_default(): void
    {
        $this->assertSame('N/A', e(null, 'N/A'));
    }

    #[Test]
    public function e_passes_through_safe_string(): void
    {
        $this->assertSame('hello world', e('hello world'));
        $this->assertSame('123', e(123));
    }

    #[Test]
    public function e_uses_ent_substitute_flag(): void
    {
        $invalidUtf8 = "\xc3\x28";
        $result = e($invalidUtf8);
        $this->assertStringNotContainsString("\xc3\x28", $result);
    }
}
