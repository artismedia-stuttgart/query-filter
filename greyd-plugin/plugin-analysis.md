# Greyd Plugin Analysis: Asset Enqueueing & Performance

## Overview
This analysis investigates how the Greyd Plugin handles the enqueueing of JavaScript and CSS resources to the WordPress frontend, specifically addressing concerns about redundant asset loading.

## Key Findings

### 1. Unconditional Enqueueing
In several core features, assets are enqueued globally on the frontend without checking for the actual presence of related blocks or content on the current page.

- **Animations Feature (`src/features/animations/enqueue.php`):**
  The `add_frontend_assets` method enqueues `greyd-animation-public-style`, `greyd-scroll-observer`, and `greyd-animation-public-script` on the `wp_enqueue_scripts` hook. There is no conditional check (like `has_block()`) to see if any element on the page actually uses animations.
  
- **Popups Feature (`src/features/popups/frontend.php`):**
  The plugin checks if any popups are "active" for the current page based on settings (post type, categories, etc.) via `get_active_popups()`. If any are found, it enqueues `popups_css` and `popups_js`. While this is better than "always on," it still loads the full popup engine even if the popup is only triggered by a specific button or event that might not be used.

- **Layout & Blocks (`src/features/layout/render.php`, `src/features/blocks/render.php`):**
  Many block-related styles are enqueued during the `render_block` filter or globally. While some features use `render_block` to inject styles, the "framework" scripts (like scroll observers or layout tools) are often loaded regardless of the specific blocks used.

### 2. Mechanism for Asset Delivery
The plugin relies heavily on:
- **`wp_enqueue_scripts`**: For global or semi-global assets.
- **`render_block` filter**: Used to inject inline styles or occasionally enqueue scripts when a specific block is rendered.
- **`src/features/` Structure**: Each feature is responsible for its own enqueueing, leading to a fragmented and sometimes redundant loading pattern where multiple features might enqueue similar "helper" scripts (e.g., scroll observers).

### 3. Optimization Lack
There is a noticeable absence of modern WordPress performance optimizations such as:
- **Block-level asset loading**: Using `viewScript` or `style` in `block.json` (though some newer parts of the plugin are moving towards `register_block_type`).
- **`has_block()` checks**: Very few instances of checking for specific blocks before enqueueing scripts.

## Answers to Specific Questions

### Are the developers just enqueueing everything?
Mostly, yes. While some features like Popups have basic "is this feature active for this page" checks, they load the entire feature's frontend payload once that condition is met. The plugin prioritizes functionality and "it just works" compatibility over granular performance optimization.

### Are there mechanisms to slim down assets?
There are no robust built-in mechanisms to slim down the delivered assets. The "Features" manager (`inc/features/features.php`) allows disabling entire features globally, which *does* prevent their assets from loading, but it's an "all-or-nothing" approach.

### Could a third-party plugin fix this?
**Yes, but it would be complex.** A third-party plugin could:
1.  **Intercept Enqueueing**: Use `wp_dequeue_script` and `wp_dequeue_style` on the `wp_enqueue_scripts` hook (with a high priority) to remove Greyd's default assets.
2.  **Conditional Re-enqueueing**: Implement the missing `has_block()` or `has_shortcode()` logic to re-enqueue assets only when needed.
3.  **Filter `render_block`**: Hook into `render_block` to detect Greyd blocks and enqueue their dependencies JIT (Just-In-Time).
4.  **Consolidate Helpers**: Detect if multiple features are loading different versions of similar utilities (like scroll observers) and force a single, shared version.

**Challenges:** Because Greyd uses some assets for "global" things like animations that can be applied to *any* block via attributes (not just specific Greyd blocks), a simple `has_block()` check might not be enough. You would need to parse the content for specific Greyd-related attributes as well.

## Conclusion
The Greyd Plugin follows a "Feature-First" architecture where ease of use and feature availability are prioritized over frontend performance. This leads to the "asset bloat" observed. Optimization is possible via hooks, but requires a deep understanding of the interdependencies between Greyd's features and their shared utility scripts.
