<?php

namespace Symfony\Polyfill\Intl\Idn\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Idn\Idn;

class IdnValidationTest extends TestCase
{
    public function testEmptyDomain()
    {
        foreach (['idn_to_ascii', 'idn_to_utf8'] as $method) {
            $info = [];
            $this->assertFalse(Idn::$method('', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
            $this->assertSame(Idn::ERROR_EMPTY_LABEL, $info['errors']);
            $this->assertSame('', $info['result']);
        }
    }

    public function testEmptyLabel()
    {
        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('a..b', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_EMPTY_LABEL, $info['errors']);
        $this->assertSame('a..b', $info['result']);
    }

    public function testHyphens()
    {
        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('ab--cd.com', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_HYPHEN_3_4, $info['errors']);

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('-label.com', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_LEADING_HYPHEN, $info['errors']);

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('label-.com', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_TRAILING_HYPHEN, $info['errors']);

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('a---b', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_HYPHEN_3_4, $info['errors']);

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('a---', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_HYPHEN_3_4 | Idn::ERROR_TRAILING_HYPHEN, $info['errors']);
    }

    public function testLeadingCombiningMark()
    {
        $info = [];
        $this->assertFalse(Idn::idn_to_ascii("\u{0308}a.com", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_LEADING_COMBINING_MARK, Idn::ERROR_LEADING_COMBINING_MARK & $info['errors']);
    }

    public function testDisallowedRange()
    {
        $info = [];
        $this->assertFalse(Idn::idn_to_ascii("a\u{0080}.com", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_DISALLOWED, Idn::ERROR_DISALLOWED & $info['errors']);
    }

    public function testBidiOffByDefaultOnByFlag()
    {
        $domain = "a\u{0628}";

        $info = [];
        $this->assertIsString(Idn::idn_to_ascii($domain, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(0, Idn::ERROR_BIDI & $info['errors']);

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii($domain, Idn::IDNA_CHECK_BIDI, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_BIDI, $info['errors']);
    }

    public function testBidiRtlEnAndAnTogether()
    {
        // RFC 5893 step 4: an RTL label must not contain both EN and AN digit types.
        $domain = "\u{0628}\u{0661}1";

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii($domain, Idn::IDNA_CHECK_BIDI, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_BIDI, $info['errors']);
    }

    public function testVariant2003EmitsDeprecation()
    {
        $deprecations = [];
        $previous = set_error_handler(static function ($severity, $message) use (&$deprecations) {
            $deprecations[] = [$severity, $message];

            return true;
        });

        try {
            $result = @Idn::idn_to_ascii('example.com', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_2003);
            $this->assertSame('example.com', $result);
            $this->assertCount(1, $deprecations);
            $this->assertSame(\E_USER_DEPRECATED, $deprecations[0][0]);
            $this->assertStringContainsString('INTL_IDNA_VARIANT_2003', $deprecations[0][1]);

            $deprecations = [];
            @Idn::idn_to_ascii('example.com', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46);
            $this->assertCount(0, $deprecations);
        } finally {
            restore_error_handler();
            if (null !== $previous) {
                set_error_handler($previous);
            }
        }
    }

    public function testContextJZwjRequiresVirama()
    {
        $options = Idn::IDNA_CHECK_CONTEXTJ | Idn::IDNA_NONTRANSITIONAL_TO_ASCII;
        $unicodeOptions = Idn::IDNA_CHECK_CONTEXTJ | Idn::IDNA_NONTRANSITIONAL_TO_UNICODE;

        $invalid = "\u{0628}\u{200D}\u{0628}";
        $info = [];
        $this->assertFalse(Idn::idn_to_ascii($invalid, $options, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_CONTEXTJ, $info['errors']);

        $valid = "\u{092C}\u{094D}\u{200D}\u{092C}";
        $info = [];
        $this->assertIsString(Idn::idn_to_ascii($valid, $options, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(0, $info['errors']);

        $info = [];
        $this->assertFalse(Idn::idn_to_utf8($invalid, $unicodeOptions, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_CONTEXTJ, $info['errors']);
    }

    /**
     * @dataProvider contextJJoinerProvider
     */
    public function testContextJChecksThisJoiner($domain, $expectedErrors)
    {
        $asciiOptions = Idn::IDNA_CHECK_CONTEXTJ | Idn::IDNA_NONTRANSITIONAL_TO_ASCII;
        $unicodeOptions = Idn::IDNA_CHECK_CONTEXTJ | Idn::IDNA_NONTRANSITIONAL_TO_UNICODE;

        $info = [];
        if (0 === $expectedErrors) {
            $this->assertIsString(Idn::idn_to_ascii($domain, $asciiOptions, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        } else {
            $this->assertFalse(Idn::idn_to_ascii($domain, $asciiOptions, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        }
        $this->assertSame($expectedErrors, $info['errors']);

        $info = [];
        if (0 === $expectedErrors) {
            $this->assertIsString(Idn::idn_to_utf8($domain, $unicodeOptions, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        } else {
            $this->assertFalse(Idn::idn_to_utf8($domain, $unicodeOptions, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        }
        $this->assertSame($expectedErrors, $info['errors']);
    }

    public static function contextJJoinerProvider()
    {
        return [
            ["\u{0627}\u{200C}\u{0627}\u{0628}\u{200C}\u{0628}", Idn::ERROR_CONTEXTJ],
            ["\u{0627}\u{200C}\u{0628}\u{094D}\u{200C}\u{0628}", Idn::ERROR_CONTEXTJ],
            ["\u{0628}\u{200C}\u{0628}\u{200C}\u{0628}", 0],
            ["aaaa\u{0628}\u{200C}\u{0628}\u{200C}\u{0628}", 0],
            ["\u{0628}\u{094D}\u{200C}\u{0628}\u{200C}\u{0628}", 0],
        ];
    }

    /**
     * @dataProvider asciiOnlyAceProvider
     */
    public function testAsciiOnlyAceLabelIsRejected($input)
    {
        $asciiOptions = Idn::IDNA_USE_STD3_RULES | Idn::IDNA_CHECK_BIDI | Idn::IDNA_CHECK_CONTEXTJ | Idn::IDNA_NONTRANSITIONAL_TO_ASCII;
        $unicodeOptions = Idn::IDNA_USE_STD3_RULES | Idn::IDNA_CHECK_BIDI | Idn::IDNA_CHECK_CONTEXTJ | Idn::IDNA_NONTRANSITIONAL_TO_UNICODE;

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii($input, $asciiOptions, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_INVALID_ACE_LABEL, Idn::ERROR_INVALID_ACE_LABEL & $info['errors']);
        $this->assertIsString($info['result']);
        $this->assertNotSame('poc.kc1zs4.com', $info['result']);

        $info = [];
        $this->assertFalse(Idn::idn_to_utf8($input, $unicodeOptions, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_INVALID_ACE_LABEL, Idn::ERROR_INVALID_ACE_LABEL & $info['errors']);
    }

    public static function asciiOnlyAceProvider()
    {
        return [
            ['poc.xn--kc1zs4-.com'],
            ['poc.kc1zs4.xn--'],
        ];
    }

    /**
     * @dataProvider invalidCodePointPunycodeProvider
     */
    public function testPunycodeAboveUnicodeOrSurrogateIsRejected($input)
    {
        $this->assertFalse(Idn::idn_to_ascii($input, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_PUNYCODE, $info['errors']);

        $this->assertFalse(Idn::idn_to_utf8($input, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_PUNYCODE, $info['errors']);
    }

    public static function invalidCodePointPunycodeProvider()
    {
        return [
            ['xn--4gqz7135h'],
            ['xn--8016146o'],
            ['example.xn--en32g.com'],
            ['xn--9ca0421i'],
            ['xn--9caa3442o'],
        ];
    }
}
