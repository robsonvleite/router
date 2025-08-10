# Changelog

All notable changes to this project will be documented in this file.

This project is a modern fork of the original CoffeeCode Router by Robson V. Leite / UpInside. Credits to the initial version.

## [3.0.0] - 2025-08-10
### Added
- Autonomous routing (no .htaccess/Nginx required) with compatibility fallback to `?route=`.
- Robust HTTP semantics:
  - HEAD (headers-only, body suppressed for handlers; assets send Content-Length).
  - OPTIONS automatic response with Allow header.
  - 405 Method Not Allowed when path matches other methods, including Allow header.
- Route constraints: parameter patterns like `{id:\d+}` and `{slug:[a-z0-9-]+}`.
- Secure assets pipeline:
  - Root-anchored realpath traversal protection.
  - Last-Modified/ETag with 304 Not Modified handling.
  - Cache-Control with configurable TTL.
  - Byte-range (206 Partial Content).
  - HEAD support for assets.
- Assets hardening options:
  - Allowlist extensions `setAssetAllowExtensions([...])`.
  - Symlink follow policy `setAssetFollowSymlinks(false by default)`.
- Dispatcher improvements:
  - Request data prepared early; parameters are mapped on match at dispatch time.
  - Block TRACE/CONNECT by default.
  - Debug headers option `setDebugHeaders(true)` outputs X-Router-* headers.
- DX improvements:
  - Global helpers autoloaded via Composer files:
    - router_auto, router_base_url, router_url, router_asset
    - router_request_method, router_is_method, router_request_path
    - router_json, router_redirect, router_route
  - CLI route lister `bin/list-routes` with `--bootstrap`, `--format=table|json`, `--base`.
  - New examples: API, Webhook (with demo HMAC), Assets (serving CSS/JS).
- Documentation overhaul:
  - README rebranded and expanded.
  - CONTRIBUTING and TESTING updated.
  - Added explicit instructions for specifying domain/subpath or using Router::auto().

### Changed
- Route registration normalization (root route `/`, double slash cleanup).
- Namespace casing normalization for controllers.
- Internal matching loop short-circuits on first match.

### Fixed
- Prevented sending Content-Length/body on 304 Not Modified responses.
- HEAD no longer emits body for handlers; assets reply with headers only.
- Safer file serving paths (no traversal) and consistent MIME-type determination.

### Migration Notes
- Namespace `CoffeeCode\Router` preserved for compatibility.
- Behavior change: paths that exist for other methods now return 405 (previously 501 in some cases).
- If you used `?route=...` rewrites, they still work; otherwise Router derives from REQUEST_URI automatically.
- New helpers are optional; they reduce bootstrap boilerplate.

### Credits
- Original: CoffeeCode Router — Robson V. Leite / UpInside
- Fork Author: HellFiveOsborn (anonimo@mail.com) — Lightning: cuttinggate97@walletofsatoshi.com