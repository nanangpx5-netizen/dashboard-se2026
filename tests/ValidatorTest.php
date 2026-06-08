<?php

declare(strict_types=1);

use App\Helpers\Validator;
use App\Helpers\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        $this->v = new Validator();
    }

    #[Test]
    public function required_passes_when_present(): void
    {
        $result = $this->v->validate(['name' => 'Ali'], ['name' => 'required|string']);
        $this->assertSame('Ali', $result['name']);
    }

    #[Test]
    public function required_fails_when_missing(): void
    {
        $result = $this->v->validate([], ['name' => 'required']);
        $this->assertTrue($this->v->hasErrors());
        $this->assertArrayNotHasKey('name', $result);
    }

    #[Test]
    public function string_trim_removes_whitespace(): void
    {
        $result = $this->v->validate(['x' => '  hello  '], ['x' => 'string|trim']);
        $this->assertSame('hello', $result['x']);
    }

    #[Test]
    public function int_casts_to_int(): void
    {
        $result = $this->v->validate(['age' => '42'], ['age' => 'int']);
        $this->assertSame(42, $result['age']);
        $this->assertIsInt($result['age']);
    }

    #[Test]
    public function numeric_accepts_float(): void
    {
        $result = $this->v->validate(['val' => '3.14'], ['val' => 'numeric']);
        $this->assertSame('3.14', $result['val']);
    }

    #[Test]
    public function email_validates_correctly(): void
    {
        $result = $this->v->validate(['email' => 'test@bps.go.id'], ['email' => 'email']);
        $this->assertSame('test@bps.go.id', $result['email']);
    }

    #[Test]
    public function email_rejects_invalid(): void
    {
        $result = $this->v->validate(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertTrue($this->v->hasErrors());
    }

    #[Test]
    public function min_checks_minimum_length(): void
    {
        $result = $this->v->validate(['pw' => 'ab'], ['pw' => 'min:3']);
        $this->assertTrue($this->v->hasErrors());
    }

    #[Test]
    public function max_checks_maximum_length(): void
    {
        $result = $this->v->validate(['pw' => 'abcdef'], ['pw' => 'max:5']);
        $this->assertTrue($this->v->hasErrors());
    }

    #[Test]
    public function in_accepts_allowed_values(): void
    {
        $result = $this->v->validate(['role' => 'admin'], ['role' => 'in:admin,operator,pegawai']);
        $this->assertSame('admin', $result['role']);
    }

    #[Test]
    public function in_rejects_invalid_value(): void
    {
        $result = $this->v->validate(['role' => 'superadmin'], ['role' => 'in:admin,operator']);
        $this->assertTrue($this->v->hasErrors());
    }

    #[Test]
    public function regex_matches_pattern(): void
    {
        $result = $this->v->validate(['kdkec' => '3509010'], ['kdkec' => 'regex:/^[0-9]{7}$/']);
        $this->assertSame('3509010', $result['kdkec']);
    }

    #[Test]
    public function regex_rejects_mismatch(): void
    {
        $result = $this->v->validate(['kdkec' => 'abc'], ['kdkec' => 'regex:/^[0-9]{7}$/']);
        $this->assertTrue($this->v->hasErrors());
    }

    #[Test]
    public function alpha_accepts_letters_only(): void
    {
        $result = $this->v->validate(['name' => 'Ali'], ['name' => 'alpha']);
        $this->assertSame('Ali', $result['name']);
    }

    #[Test]
    public function alpha_rejects_with_numbers(): void
    {
        $result = $this->v->validate(['name' => 'Ali123'], ['name' => 'alpha']);
        $this->assertTrue($this->v->hasErrors());
    }

    #[Test]
    public function alphanum_accepts_mixed(): void
    {
        $result = $this->v->validate(['code' => 'Kec01'], ['code' => 'alphanum']);
        $this->assertSame('Kec01', $result['code']);
    }

    #[Test]
    public function bool_accepts_true_string(): void
    {
        $result = $this->v->validate(['flag' => '1'], ['flag' => 'bool']);
        $this->assertSame('1', $result['flag']);
    }

    #[Test]
    public function multiple_rules_chain_correctly(): void
    {
        $result = $this->v->validate(
            ['email' => '  user@bps.go.id  '],
            ['email' => 'required|string|trim|email']
        );
        $this->assertSame('user@bps.go.id', $result['email']);
    }

    #[Test]
    public function optional_field_missing_returns_empty(): void
    {
        $result = $this->v->validate([], ['name' => 'string']);
        $this->assertArrayHasKey('name', $result);
    }

    #[Test]
    public function firstError_returns_message(): void
    {
        $this->v->validate(['x' => ''], ['x' => 'required']);
        $this->assertNotEmpty($this->v->firstError());
    }

    #[Test]
    public function static_validate_works(): void
    {
        $result = Validator::validateStatic(['a' => 'x'], ['a' => 'required']);
        $this->assertSame('x', $result['a']);
    }

    #[Test]
    public function validateOrFailStatic_throws_on_error(): void
    {
        $this->expectException(ValidationException::class);
        Validator::validateOrFailStatic([], ['name' => 'required']);
    }
}
