# wc-variations-switch-tiles v3.0 — Implementation Plan

## Background

The plugin currently **replaces** WooCommerce's entire `add-to-cart-variation.js` with a forked copy. This fork drifts out of sync with every WC update, causing breakage (most recently: WC 10.5.1 added a `disabled` attribute to the button that our fork didn't know how to remove).

The plugin's actual customizations are small — it swaps `<select>` dropdowns for `<input type="radio">` buttons, styled as switch tiles via CSS. Everything else (image gallery updates, template rendering, AJAX variation loading, qty handling, etc.) is identical to WC core.

### What the plugin actually customizes (vs WC core JS)

| Area | WC Core | Plugin Override |
|------|---------|-----------------|
| `$attributeFields` | `.variations select` | `.variations input[type=radio]` |
| `getChosenAttributes()` | Reads `select.val()` | Reads `input:checked.val()` |
| `onUpdateAttributes()` | Rebuilds `<option>` elements | Enables/disables radio `<input>` elements |
| `onReset()` | Sets selects to `val('')` | Unchecks all radios |
| `onChange` event | Listens on `.variations select` | Listens on `.variations input[type=radio]` |

Everything else (500+ lines of image handling, template rendering, gallery, qty, etc.) is **identical** to WC core and should not be forked.

### Current file structure

```
wc-variations-switch-tiles/
├── wc-variations-radio-buttons.php          # Main plugin file
├── assets/
│   ├── radio-variations.css                 # Switch tile CSS (the visual magic)
│   └── js/frontend/add-to-cart-variation.js # FULL FORK of WC core JS (765 lines)
├── templates/
│   └── single-product/add-to-cart/
│       ├── variable.php                     # Renders radios instead of selects
│       └── variation-add-to-cart-button.php # Button without disabled attr
└── docs/
    └── v3-implementation-plan.md            # This file
```

---

## Strategy: Let WC own its JS, add a thin bridge script

Instead of forking 765 lines of WC JS, we:

1. **Keep WC's `wc-add-to-cart-variation` script** (stop deregistering it)
2. **Add hidden `<select>` elements** in the template alongside radio buttons
3. **Add a small bridge JS** (~50 lines) that syncs radio → select on change
4. WC core JS sees the selects, does all its logic, and everything works
5. CSS already handles the visual tile styling

This approach is **future-proof** — WC can update their JS freely and our bridge keeps working.

---

## Implementation Steps

Each step is independently committable and testable.

### Step 1: Add hidden selects to the variable.php template

**File:** `templates/single-product/add-to-cart/variable.php`

**What:** For each attribute, render a hidden `<select>` alongside the radio buttons. The select contains the same options as the radios. WC core JS needs these selects to function.

**Markup pattern:**
```html
<td class="value">
  <!-- Hidden select for WC core JS -->
  <select name="attribute_pa_variant" data-attribute_name="attribute_pa_variant" style="display:none !important">
    <option value="">Velg variant</option>
    <option value="kapsler">Kapsler</option>
    <option value="pulver">Pulver</option>
  </select>
  <!-- Visible radio tiles -->
  <label for="..."><input type="radio" ...><div>...</div></label>
  ...
</td>
```

**Test:** Load product page, verify both hidden selects and visible radio tiles render. WC core JS should initialize on the selects.

**Commit:** `feat: add hidden selects alongside radio tiles in variable.php template`

---

### Step 2: Create the bridge JS script

**File:** `assets/js/frontend/radio-bridge.js` (NEW — ~60 lines)

**What:** A small script that:
- On radio `change`: sets the corresponding hidden select value and triggers `change`
- On select `change` (from WC core, e.g. reset): updates radio checked state
- On form `reset_data`: unchecks all radios

```js
(function($) {
  $(function() {
    $('.variations_form').each(function() {
      var $form = $(this);

      // Radio → Select sync
      $form.on('change', '.variations input[type=radio]', function() {
        var $radio = $(this);
        var name = $radio.attr('name');
        var value = $radio.val();
        var $select = $form.find('select[name="' + name + '"]');
        if ($select.length && $select.val() !== value) {
          $select.val(value).trigger('change');
        }
      });

      // Select → Radio sync (for WC core reset/update)
      $form.on('change', '.variations select', function() {
        var $select = $(this);
        var name = $select.attr('name');
        var value = $select.val();
        var $radios = $form.find('input[type=radio][name="' + name + '"]');
        if (value) {
          $radios.filter('[value="' + value + '"]').prop('checked', true);
        } else {
          $radios.prop('checked', false);
        }
      });

      // Sync disabled state: when WC disables select options, disable matching radios
      $form.on('woocommerce_update_variation_values', function() {
        $form.find('.variations select').each(function() {
          var $select = $(this);
          var name = $select.attr('name');
          $form.find('input[type=radio][name="' + name + '"]').each(function() {
            var $radio = $(this);
            var $option = $select.find('option[value="' + $radio.val() + '"]');
            var isDisabled = !$option.length || $option.prop('disabled');
            $radio.prop('disabled', isDisabled);
            $radio.closest('label').toggleClass('inactive', isDisabled);
          });
        });
      });

      // Initial sync: if a radio is pre-checked, sync to select
      $form.find('.variations input[type=radio]:checked').trigger('change');
    });
  });
})(jQuery);
```

**Test:** Click radio tiles → hidden select updates → WC core detects variation → button enables → add to cart works.

**Commit:** `feat: add radio-bridge.js to sync radio tiles with WC core variation logic`

---

### Step 3: Update PHP to use WC core JS + bridge

**File:** `wc-variations-radio-buttons.php`

**What:**
- **Remove** `wp_deregister_script('wc-add-to-cart-variation')` and `wp_register_script(...)` — stop replacing WC core JS
- **Enqueue** `radio-bridge.js` as a new script with dependency on `wc-add-to-cart-variation`
- Keep CSS enqueue and SVG footer as-is

```php
function load_scripts() {
    if (!(wc_get_product() && wc_get_product()->is_type('bundle'))) {
        // Enqueue bridge script AFTER WC core variation JS
        wp_enqueue_script(
            'wc-variation-radio-bridge',
            $this->get_plugin_url() . 'assets/js/frontend/radio-bridge.js',
            array('jquery', 'wc-add-to-cart-variation'),
            self::VERSION,
            true
        );
    }
    wp_enqueue_style('radio-variations', $this->get_plugin_url() . 'assets/radio-variations.css', '', self::VERSION);
    // ... SVG footer code stays ...
}
```

**Test:** Full flow — select variation via tiles, add to cart, verify FunnelKit cart opens.

**Commit:** `refactor: use WC core variation JS with bridge instead of full fork`

---

### Step 4: Clean up variable.php template

**File:** `templates/single-product/add-to-cart/variable.php`

**What:**
- Remove debug comments and `$GLOBALS['wc_variation_debug']` code
- Remove `[INACTIVE]` debug labels from option names
- Clean up the valid_options filtering (keep the logic, remove debug output)
- Update template `@version` tag to match current WC version

**Test:** Verify clean HTML output, no debug comments visible in source.

**Commit:** `chore: clean up debug code from variable.php template`

---

### Step 5: Evaluate if variable.php template can be replaced with hooks

**Analysis needed:** The template currently:
1. Renders radio buttons instead of selects (via `print_attribute_radio()`)
2. Filters options by purchasable/in-stock variations
3. Adds SVG icons inside radio labels

**Can this be done with hooks?**
- `woocommerce_dropdown_variation_attribute_options_html` filter — could inject radio HTML after the select
- But we'd still need the select to exist (which it does in WC core)
- The radios would be purely visual, selects hidden via CSS

**If feasible:** Remove the `variable.php` template override entirely, use the filter to append radio HTML after each select. This eliminates template version conflicts.

**If not feasible:** Keep the template but document why.

**Commit:** `refactor: replace variable.php template with hooks (if feasible)` or `docs: document why template override is needed`

---

### Step 6: Remove the old forked JS file

**File:** `assets/js/frontend/add-to-cart-variation.js` → DELETE

**What:** Remove the 765-line forked WC JS. It's no longer loaded.

**Also remove:** `variation-add-to-cart-button.php` template override (no longer needed since we're not deregistering WC's script, so the `disabled` attr logic works correctly with WC core JS).

**Test:** Full regression test — all variation products, add to cart, FunnelKit cart.

**Commit:** `chore: remove legacy forked WC variation JS and button template`

---

### Step 7: Version bump and final cleanup

- Bump version to `3.0.0` in plugin header and `VERSION` constant
- Update `WC tested up to` to current version
- Update `readme.txt` if present
- Remove `wc-variations-radio-buttons-inline-js-addition-test.php` (test file)
- Remove `wc-variations-radio-buttons copy.php` if present

**Commit:** `release: v3.0.0 — use WC core JS with radio bridge`

---

## Target file structure (v3.0)

```
wc-variations-switch-tiles/
├── wc-variations-radio-buttons.php          # Main plugin file (simplified)
├── assets/
│   ├── radio-variations.css                 # Switch tile CSS (unchanged)
│   └── js/frontend/radio-bridge.js          # NEW: thin bridge (~60 lines)
├── templates/
│   └── single-product/add-to-cart/
│       └── variable.php                     # Radios + hidden selects (or removed if hooks work)
└── docs/
    └── v3-implementation-plan.md            # This file
```

## Risk mitigation

- **Each step is independently testable** — if step 5 (hooks) doesn't work, we keep the template
- **The bridge pattern is proven** — many variation swatch plugins use this exact approach
- **Rollback is easy** — git revert any step independently
- **Bundle products excluded** — existing `is_type('bundle')` check preserved
