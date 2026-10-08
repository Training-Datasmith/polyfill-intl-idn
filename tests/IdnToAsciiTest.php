<?php

namespace Symfony\Polyfill\Intl\Idn\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Idn\Idn;

class IdnToAsciiTest extends TestCase
{
    public function testAsciiPassthrough()
    {
        foreach (['foo.example', 'foo.example.'] as $domain) {
            $info = [];
            $result = Idn::idn_to_ascii($domain, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info);
            $this->assertSame($domain, $result);
            $this->assertSame($domain, $info['result']);
            $this->assertSame(0, $info['errors']);
            $this->assertFalse($info['isTransitionalDifferent']);
        }

        $this->assertSame('foo.example', Idn::idn_to_ascii('foo.example'));
    }

    public function testPunycodeEncodesOnlyNonAsciiLabels()
    {
        $cases = [
            ['中国.com', 'xn--fiqs8s.com'],
            ['рф.ru', 'xn--p1ai.ru'],
            ['δοκιμή.gr', 'xn--jxalpdlp.gr'],
            ['bücher.de', 'xn--bcher-kva.de'],
            ['guangdong.广东', 'guangdong.xn--xhq521b'],
        ];

        foreach ($cases as [$input, $expected]) {
            $this->assertSame($expected, Idn::idn_to_ascii($input, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46));
        }
    }

    public function testRfc3492Vectors()
    {
        $vectors = [
            'xn--egbpdaj6bu4bxfgehfvwxn',
            'xn--ihqwcrb4cv8a8dqg056pqjye',
        ];

        foreach ($vectors as $ascii) {
            $unicode = Idn::idn_to_utf8($ascii, 0, Idn::INTL_IDNA_VARIANT_UTS46);
            $this->assertIsString($unicode);
            $this->assertSame($ascii, Idn::idn_to_ascii($unicode, 0, Idn::INTL_IDNA_VARIANT_UTS46));
        }

        $this->assertSame(
            'xn--proprostnemluvesky-uyb24dma41a',
            Idn::idn_to_ascii('Pročprostěnemluvíčesky', 0, Idn::INTL_IDNA_VARIANT_UTS46)
        );
    }

    public function testUppercaseIsMapped()
    {
        $info = [];
        $result = Idn::idn_to_ascii('РФ.RU', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertSame('xn--p1ai.ru', $result);
        $this->assertSame([
            'result' => 'xn--p1ai.ru',
            'isTransitionalDifferent' => false,
            'errors' => 0,
        ], $info);

        $this->assertSame('bucher.de', Idn::idn_to_ascii('BUCHER.DE', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46));
    }

    public function testIgnoredCodePointIsRemoved()
    {
        $this->assertSame(
            'foobar.com',
            Idn::idn_to_ascii("foo\u{00AD}bar.com", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46)
        );
    }

    public function testDecomposedUmlautIsNormalized()
    {
        $this->assertSame(
            Idn::idn_to_ascii("\u{00FC}.de", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46),
            Idn::idn_to_ascii("u\u{0308}.de", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46)
        );
        $this->assertSame('xn--tda.de', Idn::idn_to_ascii("u\u{0308}.de", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46));
    }

    public function testTransitionalSharpSAndCapitalSharpS()
    {
        $info = [];
        $this->assertSame(
            'fass.de',
            Idn::idn_to_ascii('faß.de', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info)
        );
        $this->assertTrue($info['isTransitionalDifferent']);

        $this->assertSame(
            'fass.de',
            Idn::idn_to_ascii("fa\u{1E9E}.de", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46)
        );

        $this->assertSame(
            'xn--fa-hia.de',
            Idn::idn_to_ascii('faß.de', Idn::IDNA_NONTRANSITIONAL_TO_ASCII, Idn::INTL_IDNA_VARIANT_UTS46)
        );

        // Resources/unidata/mapped.php maps U+1E9E (7838) to "ss" per IDNA 15.1; nontransitional uses that table mapping.
        $this->assertSame(
            'fass.de',
            Idn::idn_to_ascii("Fa\u{1E9E}.de", Idn::IDNA_NONTRANSITIONAL_TO_ASCII, Idn::INTL_IDNA_VARIANT_UTS46)
        );
    }

    public function testFinalSigmaDeviation()
    {
        $input = "a\u{03C2}.com";

        $info = [];
        $transitional = Idn::idn_to_ascii($input, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertTrue($info['isTransitionalDifferent']);
        $this->assertNotSame($transitional, Idn::idn_to_ascii($input, Idn::IDNA_NONTRANSITIONAL_TO_ASCII, Idn::INTL_IDNA_VARIANT_UTS46));
    }

    public function testStd3Flag()
    {
        $this->assertSame('foo_bar.com', Idn::idn_to_ascii('foo_bar.com', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46));

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('foo_bar.com', Idn::IDNA_USE_STD3_RULES, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_DISALLOWED, $info['errors']);

        $this->assertSame(
            'xn--test@bcher-feb.de',
            Idn::idn_to_ascii('test@bücher.de', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46)
        );

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii('test@bücher.de', Idn::IDNA_USE_STD3_RULES, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_DISALLOWED, $info['errors']);
    }

    public function testNbspMappedOnlyWithoutStd3()
    {
        $input = "foo\u{00A0}bar.com";

        $this->assertSame('foo bar.com', Idn::idn_to_ascii($input, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46));

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii($input, Idn::IDNA_USE_STD3_RULES, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_DISALLOWED, $info['errors']);
    }

    public function testLabelAndDomainLength()
    {
        $this->assertSame(
            str_repeat('a', 63),
            Idn::idn_to_ascii(str_repeat('a', 63), Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46)
        );

        $info = [];
        $this->assertFalse(Idn::idn_to_ascii(str_repeat('a', 64), Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_LABEL_TOO_LONG, $info['errors']);

        $ok = str_repeat('a', 63).'.'.str_repeat('a', 63).'.'.str_repeat('a', 63).'.'.str_repeat('a', 61);
        $this->assertSame($ok, Idn::idn_to_ascii($ok, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46));

        $tooLong = str_repeat('a', 63).'.'.str_repeat('a', 63).'.'.str_repeat('a', 63).'.'.str_repeat('a', 62);
        $info = [];
        $this->assertFalse(Idn::idn_to_ascii($tooLong, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_DOMAIN_NAME_TOO_LONG, $info['errors']);

        $root = $ok.'.';
        $this->assertSame($root, Idn::idn_to_ascii($root, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46));
    }
}
