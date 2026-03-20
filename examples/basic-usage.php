<?php

declare(strict_types=1);

/**
 * Example: Using symfony/polyfill-intl-idn.
 *
 * This polyfill provides idn_to_ascii() and idn_to_utf8() for environments
 * where the intl extension is not available. Implements IDNA2008 (RFC 5891)
 * for converting internationalized domain names (IDN).
 *
 * Install:
 *   composer require symfony/polyfill-intl-idn
 */

// --- idn_to_ascii(): convert Unicode domain to ACE/Punycode form ---
// Useful for DNS lookups, HTTP requests, and email validation with Unicode domains

$unicodeDomain = 'münchen.de';
$ascii = idn_to_ascii($unicodeDomain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
var_dump($ascii); // string "xn--mnchen-3ya.de"

// Chinese domain
$chineseDomain = '中文.com';
$asciiChinese = idn_to_ascii($chineseDomain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
var_dump($asciiChinese); // string "xn--fiq228c.com"

// Arabic domain
$arabicDomain = 'مثال.com';
$asciiArabic = idn_to_ascii($arabicDomain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
var_dump($asciiArabic); // "xn--mgbh0fb.com" (or similar)

// --- idn_to_utf8(): convert ACE/Punycode domain back to Unicode ---
$punycode = 'xn--mnchen-3ya.de';
$unicode = idn_to_utf8($punycode, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
var_dump($unicode); // string "münchen.de"

// --- Practical: normalizing domains for display vs. DNS ---
function normalize_domain_for_dns(string $domain): string|false
{
    return idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
}

function normalize_domain_for_display(string $domain): string|false
{
    // Convert Punycode to human-readable if needed
    if (str_starts_with($domain, 'xn--') || str_contains($domain, '.xn--')) {
        return idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
    }
    return $domain;
}

$userInput = 'münchen.de';
$dnsForm   = normalize_domain_for_dns($userInput);
$display   = normalize_domain_for_display($dnsForm ?: $userInput);

echo "DNS form: $dnsForm\n";    // "xn--mnchen-3ya.de"
echo "Display:  $display\n";   // "münchen.de"

// --- idn_to_ascii() with info array (IDNA_CHECK_*) ---
$info = [];
$result = idn_to_ascii('münchen.de', IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $info);
var_dump($info['errors']); // int(0) — no errors

// Invalid domain returns false
$invalid = idn_to_ascii('xn--nxasmq6b.com.', IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
