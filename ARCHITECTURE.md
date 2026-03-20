# Architecture: polyfill-intl-idn

## Purpose

Provides a pure-PHP fallback for the `idn_to_ascii()` and `idn_to_utf8()` functions
from the `intl` extension. Enables Internationalized Domain Name (IDN) encoding/decoding
on systems without `intl`.

## Directory Structure

```
Idn.php       # Pure-PHP implementation of idn_to_ascii() and idn_to_utf8() as static methods
bootstrap.php # Defines global idn_* functions if the intl extension is absent
Resources/    # IDNA mapping tables and Unicode data required for the conversion
```

## Key Design Decisions

IDN conversion requires large Unicode mapping tables. These are stored as PHP arrays in
`Resources/` and loaded on demand. The implementation follows RFC 5891 (IDNA2008) with
UTS#46 compatibility mapping, matching the behavior of modern ICU-based implementations.

## Extension Points

None — drop-in function polyfill.
