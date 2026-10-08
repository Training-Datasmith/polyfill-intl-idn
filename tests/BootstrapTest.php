<?php

namespace Symfony\Polyfill\Intl\Idn\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Idn\Idn;

class BootstrapTest extends TestCase
{
    public function testIntlIsNotLoaded()
    {
        $this->assertFalse(extension_loaded('intl'));
        $this->assertTrue(function_exists('idn_to_ascii'));
        $this->assertTrue(function_exists('idn_to_utf8'));
    }

    public function testGlobalFunctionsMatchTheClass()
    {
        $expected = Idn::idn_to_ascii('bücher.de', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($expected, idn_to_ascii('bücher.de', \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46));
        $this->assertSame('xn--bcher-kva.de', $expected);

        $expectedUtf8 = Idn::idn_to_utf8('xn--bcher-kva.de', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($expectedUtf8, idn_to_utf8('xn--bcher-kva.de', \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46));
        $this->assertSame('bücher.de', $expectedUtf8);
    }

    public function testPhp72GlobalDefaultVariantForcesBidi()
    {
        if (\PHP_VERSION_ID >= 70400) {
            $this->markTestSkipped('Global default variant is UTS46 on PHP 7.4+.');
        }

        $domain = "a\u{0628}";
        $info = [];
        $this->assertFalse(idn_to_ascii($domain, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_2003, $info));
        $this->assertSame(Idn::ERROR_BIDI, $info['errors']);

        $info = [];
        $this->assertIsString(Idn::idn_to_ascii($domain, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(0, Idn::ERROR_BIDI & $info['errors']);
    }

    public function testBootstrapConstantsMatchTheClass()
    {
        $pairs = [
            'IDNA_ERROR_EMPTY_LABEL' => Idn::ERROR_EMPTY_LABEL,
            'IDNA_ERROR_LABEL_TOO_LONG' => Idn::ERROR_LABEL_TOO_LONG,
            'IDNA_ERROR_DOMAIN_NAME_TOO_LONG' => Idn::ERROR_DOMAIN_NAME_TOO_LONG,
            'IDNA_ERROR_LEADING_HYPHEN' => Idn::ERROR_LEADING_HYPHEN,
            'IDNA_ERROR_TRAILING_HYPHEN' => Idn::ERROR_TRAILING_HYPHEN,
            'IDNA_ERROR_HYPHEN_3_4' => Idn::ERROR_HYPHEN_3_4,
            'IDNA_ERROR_LEADING_COMBINING_MARK' => Idn::ERROR_LEADING_COMBINING_MARK,
            'IDNA_ERROR_DISALLOWED' => Idn::ERROR_DISALLOWED,
            'IDNA_ERROR_PUNYCODE' => Idn::ERROR_PUNYCODE,
            'IDNA_ERROR_LABEL_HAS_DOT' => Idn::ERROR_LABEL_HAS_DOT,
            'IDNA_ERROR_INVALID_ACE_LABEL' => Idn::ERROR_INVALID_ACE_LABEL,
            'IDNA_ERROR_BIDI' => Idn::ERROR_BIDI,
            'IDNA_ERROR_CONTEXTJ' => Idn::ERROR_CONTEXTJ,
            'IDNA_DEFAULT' => Idn::IDNA_DEFAULT,
            'IDNA_ALLOW_UNASSIGNED' => Idn::IDNA_ALLOW_UNASSIGNED,
            'IDNA_USE_STD3_RULES' => Idn::IDNA_USE_STD3_RULES,
            'IDNA_CHECK_BIDI' => Idn::IDNA_CHECK_BIDI,
            'IDNA_CHECK_CONTEXTJ' => Idn::IDNA_CHECK_CONTEXTJ,
            'IDNA_NONTRANSITIONAL_TO_ASCII' => Idn::IDNA_NONTRANSITIONAL_TO_ASCII,
            'IDNA_NONTRANSITIONAL_TO_UNICODE' => Idn::IDNA_NONTRANSITIONAL_TO_UNICODE,
            'INTL_IDNA_VARIANT_UTS46' => Idn::INTL_IDNA_VARIANT_UTS46,
        ];

        foreach ($pairs as $name => $classValue) {
            $this->assertTrue(defined($name), $name);
            $this->assertSame($classValue, constant($name));
        }

        $this->assertSame(0, \INTL_IDNA_VARIANT_2003);
    }
}
