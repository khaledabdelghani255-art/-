# NEWGEN MARKET Product Center

An installable WordPress plugin for a Jumia Vendor Center-inspired product submission flow. It integrates with WooCommerce and keeps new seller products pending until a manager reviews them.

## Install
1. Download the ZIP from the GitHub Actions artifact named **newgen-market-product-center**.
2. WordPress Admin → Plugins → Add New Plugin → Upload Plugin.
3. Upload the ZIP and activate it. WooCommerce must be active.
4. Create a published page named “إضافة المنتجات” and add `[newgen_market_add_product]`.
5. Create a published page named “إدارة المنتجات” and add `[newgen_market_my_products]`.
6. Give approved seller accounts the WooCommerce capability `edit_products` using your existing role/vendor system. Do not grant it to every customer.
7. Test with a non-production account and confirm that new products are Pending and not publicly visible until approved.

## Current capabilities
- Arabic RTL responsive vendor product form
- Required Arabic product name, category, price, description and image
- Optional English name, SKU and stock quantity
- Server-side image MIME, 2 MB size, and 500–2000 px dimensions checks
- WooCommerce simple products, product category and featured image
- Products assigned to submitting user, pending review and hidden from catalog until approved
- Seller product list with price, stock and publication/review status

## Not included yet
This is a product intake module, not a complete marketplace. It does not yet provide full bilingual attributes, bulk CSV import, product edit/delete UI, seller commissions/payouts, shipping, order management, seller approval workflow, or the complete Jumia three-step listing wizard. It does not modify live pages automatically. Back up the site and test on staging before activation.
