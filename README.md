# Side Cart Upsells

A production-ready WordPress plugin that adds a dynamic **"Don't miss out"** upsell section inside the Elementor WooCommerce side cart (mini cart drawer) — fully AJAX-driven, with zero page reloads.

---

## Requirements

| Dependency | Minimum version |
|---|---|
| WordPress | 6.0 |
| PHP | 7.4 |
| WooCommerce | 7.0 |
| Elementor | Any version with side cart enabled |

---

## Installation

1. Upload the `sidecart-upsells/` folder to `wp-content/plugins/`.
2. Activate the plugin from **WordPress Admin → Plugins**.
3. No configuration required — the upsell section appears automatically once products in the cart have upsells assigned.

---

## How It Works

### Upsell product selection

On every cart state (initial load and after each AJAX cart update) the plugin:

1. Reads all products currently in the cart.
2. Collects each product's configured upsell IDs (set via **Product → Linked Products → Upsells** in WooCommerce).
3. Merges and deduplicates the list.
4. Removes any product already in the cart.
5. Filters to only **purchasable** and **in-stock** products.
6. Caps the result at **4 products**.

### Rendering

The upsell section is injected after the mini-cart item list via the `woocommerce_after_mini_cart_contents` hook. Each product card shows:

- Thumbnail image
- Product name (links to product page)
- Price (including sale formatting)
- **Add to cart** button (simple products) or **Select options** link (variable products)

### AJAX / fragment refresh

The section is registered as a WooCommerce cart fragment under the `.scu-upsells` key. Every time the cart changes (add, remove, update quantity) WooCommerce's own fragment system replaces the section in the DOM — no page reload, no custom AJAX endpoint.

Add-to-cart for simple products uses WooCommerce's native `ajax_add_to_cart` mechanism (`wc-add-to-cart.js`). Variable products link directly to their product page so the user can choose a variation.

---

## Setting Up Upsells in WooCommerce

Upsells are configured per product inside the WooCommerce product editor:

1. Go to **Products → Edit** any product.
2. Open the **Linked Products** tab.
3. Search for and add products in the **Upsells** field.
4. Save the product.

Those linked products will appear in the side cart when the parent product is in the cart.

---

## File Structure

```
sidecart-upsells/
├── sidecart-upsells.php          # Plugin header and bootstrap
├── README.md
├── CHANGELOG.md
├── includes/
│   └── class-scu-upsells.php    # Core class (hooks, logic, rendering)
└── assets/
    ├── css/
    │   └── scu-upsells.css      # Scoped styles (BEM under .scu-upsells)
    └── js/
        └── scu-upsells.js       # IIFE-wrapped feedback layer
```

---

## Hooks Reference

| Hook | Type | Purpose |
|---|---|---|
| `woocommerce_after_mini_cart_contents` | action | Renders the upsell section in the mini cart |
| `woocommerce_add_to_cart_fragments` | filter | Returns updated HTML fragment on cart change |
| `wp_enqueue_scripts` | action | Enqueues CSS and JS on the front end |

---

## CSS Customisation

All styles are scoped under `.scu-upsells` and use BEM naming, making them safe to override from a child theme or custom CSS without touching the plugin files.

Example — change the button colour:

```css
.scu-upsells__add-btn {
    background-color: #e63946;
}

.scu-upsells__add-btn:hover {
    background-color: #c1121f;
}
```

---

## JavaScript Events

The plugin listens to standard WooCommerce DOM events — no custom events are introduced.

| Event | Source | Plugin response |
|---|---|---|
| `click` on `.scu-upsells__add-btn` | User | Shows loading spinner, disables button |
| `added_to_cart` | WooCommerce | Shows "Added!" confirmation |
| `ajax_error` | WooCommerce | Resets stuck loading buttons |

---

## Security

- All HTML output is escaped with `esc_html`, `esc_url`, `esc_attr`, or `wp_kses_post`.
- `wp_create_nonce` is called on every page load and passed to JS via `wp_localize_script`.
- No raw `$_POST` / `$_GET` values are read directly — add-to-cart is delegated entirely to WooCommerce's own validated handler.
- The plugin creates no database tables and writes no options.

---

## Compatibility Notes

- **Elementor side cart:** The plugin targets the standard WooCommerce mini cart template hooks that Elementor's side cart widget renders through. No Elementor-specific API is required.
- **Caching plugins:** Because the upsell HTML is delivered as a WooCommerce cart fragment (AJAX), it is not affected by full-page HTML caching.
- **Other mini cart plugins:** Any plugin or theme that renders the standard WooCommerce mini cart template and does not suppress `woocommerce_after_mini_cart_contents` will display the upsell section.

---

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
