# Changelog

All notable changes to `padosoft/laravel-rebel-recovery` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0] - 2026-06-03

### Added
- **`RecoveryCodeManager`**: issue, verify and consume single-use recovery (backup) codes.
  - `generate()` returns the plaintext codes **once** (show/download), storing only a keyed
    HMAC of `(salt|code)` with a `key_version` (pepper rotation friendly); regenerating
    invalidates all previous unconsumed codes.
  - `verify()` is atomic + single-use (row lock), **constant-time** across the candidate set,
    and tolerant of input casing/separators.
  - `remaining()` counts unconsumed codes.
- **`RecoveryCodeGenerator`**: CSPRNG Crockford-style codes (~100 bits, `XXXX-…-XXXX`).
- Migration (`rebel_recovery_codes`, UUID), model, config, audit of generate/complete/fail.
- CI matrix (PHP 8.3/8.4/8.5 × Laravel 12/13), Pest suite, PHPStan level max, Pint.

[Unreleased]: https://github.com/padosoft/laravel-rebel-recovery/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/padosoft/laravel-rebel-recovery/releases/tag/v0.1.0
