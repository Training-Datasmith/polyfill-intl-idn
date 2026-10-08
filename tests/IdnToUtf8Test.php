<?php

namespace Symfony\Polyfill\Intl\Idn\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Idn\Idn;

class IdnToUtf8Test extends TestCase
{
    public function testDecodesAceLabels()
    {
        $cases = [
            ['xn--bcher-kva.de', 'bücher.de'],
            ['xn--p1ai.ru', 'рф.ru'],
            ['guangdong.xn--xhq521b', 'guangdong.广东'],
        ];

        foreach ($cases as [$input, $expected]) {
            $info = [];
            $this->assertSame($expected, Idn::idn_to_utf8($input, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
            $this->assertSame(0, $info['errors']);
        }
    }

    public function testAsciiRoundTrip()
    {
        $info = [];
        $this->assertSame('foo.example.', Idn::idn_to_utf8('foo.example.', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(0, $info['errors']);
    }

    public function testTransitionalUtf8()
    {
        $info = [];
        $this->assertSame('fass.de', Idn::idn_to_utf8('Faß.de', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertTrue($info['isTransitionalDifferent']);

        $info = [];
        $this->assertSame('faß.de', Idn::idn_to_utf8('Faß.de', Idn::IDNA_NONTRANSITIONAL_TO_UNICODE, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertTrue($info['isTransitionalDifferent']);
    }

    public function testInvalidPunycodeSetsErrorAndKeepsLabel()
    {
        $info = [];
        $this->assertFalse(Idn::idn_to_utf8('example.xn--?.de', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_PUNYCODE, $info['errors']);
        $this->assertIsString($info['result']);
        $this->assertStringContainsString('xn--?', $info['result']);
    }

    public function testXnPrefixWithNonAsciiIsSkipped()
    {
        $info = [];
        $this->assertFalse(Idn::idn_to_utf8('xn--🌈.xn--fa-hia.de', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertSame(Idn::ERROR_PUNYCODE, Idn::ERROR_PUNYCODE & $info['errors']);
        $this->assertSame('xn--🌈.faß.de', $info['result']);
    }

    public function testAceThatDoesNotRoundTrip()
    {
        $utf8Info = [];
        Idn::idn_to_utf8('xn--xn---epa.', Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $utf8Info);

        $asciiInfo = [];
        $this->assertFalse(Idn::idn_to_ascii($utf8Info['result'], Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $asciiInfo));
        $this->assertSame(Idn::ERROR_PUNYCODE, Idn::ERROR_PUNYCODE & $asciiInfo['errors']);
    }

    public function testInvalidUtf8IsRejected()
    {
        $info = [];
        $this->assertFalse(Idn::idn_to_utf8("\xFF.com", Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $info));
        $this->assertNotSame(0, Idn::ERROR_DISALLOWED & $info['errors']);
        $this->assertIsString($info['result']);
    }

    public function testUtf8DoesNotApplyDnsLength()
    {
        $label = str_repeat("\u{00FC}", 64);
        $domain = $label;

        $asciiInfo = [];
        $this->assertFalse(Idn::idn_to_ascii($domain, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $asciiInfo));
        $this->assertSame(Idn::ERROR_LABEL_TOO_LONG, $asciiInfo['errors']);

        $utf8Info = [];
        $this->assertSame($label, Idn::idn_to_utf8($domain, Idn::IDNA_DEFAULT, Idn::INTL_IDNA_VARIANT_UTS46, $utf8Info));
        $this->assertSame(0, $utf8Info['errors']);
    }
}
