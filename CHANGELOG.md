# Changelog

All notable changes to **Side Cart Upsells** are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

_Nothing pending._

---

## [1.0.0] — 2026-04-15

### Added

- Initial release.
- `SCU_Upsells` singleton class with full OOP structure.
- `woocommerce_after_mini_cart_contents` hook to inject the upsell section after the mini-cart item list.
- `woocommerce_add_to_cart_fragments` integration so the section updates on every AJAX cart change without a page reload.
- Product logic: collects upsell IDs from all cart items, deduplicates, excludes cart items, filters to purchasable and in-stock products, caps at 4.
- Per-card rendering: thumbnail, linked product name, price (with sale formatting), add-to-cart button (simple products) or "Select options" link (variable products).
- Native WooCommerce `ajax_add_to_cart` mechanism reused for simple products — no custom AJAX endpoint required.
- CSS loading spinner (via `::after` pseudo-element) and green "Added!" state on successful add.
- Full CSS isolation: all rules scoped under `.scu-upsells` with BEM modifiers; no generic selectors.
- Full JS isolation: IIFE wrapper, event delegation on `scu-` prefixed selectors only, no global variables.
- Assets enqueued via `wp_enqueue_scripts` with versioned handles `scu-upsells-style` and `scu-upsells-script`.
- `wp_localize_script` passes AJAX URL, nonce, and translatable strings to JS.
- All output escaped with `esc_html`, `esc_url`, `esc_attr`, and `wp_kses_post`.
- Graceful bail when WooCommerce is not active.
- WordPress 6.0+, PHP 7.4+, WooCommerce 7.0+ compatibility declared.

---

[Unreleased]: https://github.com/mlmarklozano/ml-sidecart-upsell/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/mlmarklozano/ml-sidecart-upsell/releases/tag/v1.0.0
