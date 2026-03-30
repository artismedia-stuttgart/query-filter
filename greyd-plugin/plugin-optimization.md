# Greyd Plugin Optimization Suggestions

## 1. Conditional Asset Loading (Critical)
The current implementation enqueues many frontend assets (JS/CSS) globally via `wp_enqueue_scripts` without checking if the page actually contains the relevant blocks.

**Suggestions:**
- **Implement `has_block()` checks:** In `wp_enqueue_scripts` hooks, wrap enqueues in a check to see if the block is present in the current post content.
  ```php
  if ( has_block( 'greyd/popup-trigger' ) || has_block( 'greyd/popup-close' ) ) {
      wp_enqueue_script( 'popups_js' );
  }
  ```
- **Adopt `block.json`:** Transition block registration to `block.json` to leverage WordPress's built-in asset management, which automatically handles conditional loading for scripts and styles.

## 2. Refactoring jQuery to Vanilla JS
The plugin currently relies heavily on jQuery for basic frontend tasks (DOM selection, event handling, simple animations). While jQuery is "safe" in WordPress, it adds a 30KB+ (gzipped) dependency that is often unnecessary in modern environments.

**Suggestions:**
- **Migrate Popups to Vanilla JS:** The `popups/assets/js/frontend.js` uses jQuery for simple things like `$(el).hasClass('hidden')`. These can be easily replaced with `el.classList.contains('hidden')`.
- **Vanilla JS Observer:** The `greyd-scroll-observer` should use the native `IntersectionObserver` API instead of jQuery-based scroll listeners for significantly better performance.
- **Justification Analysis:** Is jQuery justified? Currently, **no**. Most of the tasks performed in `frontend.js` files (Popups, Lottie, Layout) are well-supported by native modern Browser APIs. Removing this dependency would improve the "Core Web Vitals" (LCP, FID) of sites using Greyd.

## 3. Script Consolidation & JIT
Multiple features (Animations, Layout, Popups) use separate scroll listeners or observers.

**Suggestions:**
- **Centralized Registry:** Create a single, lightweight "Greyd Core Frontend" script that manages global events (scroll, resize, intersection) and provides a unified API for other features.
- **Just-In-Time (JIT) Assets:** For features like Popups, consider loading the JS only when a trigger is actually interacted with, or via a small inline "loader" script that fetches the full library on demand.

## 4. Leverage WordPress Interactivity API
For interactive components (like popups or dynamic filters), consider adopting the **WordPress Interactivity API** (introduced in WP 6.5).

**Suggestions:**
- This would allow for a more declarative way of handling frontend state and interactions, often resulting in much smaller and more performant JS bundles.

## 5. CSS Optimization
The plugin currently injects significant amounts of inline CSS via `render_block`.

**Suggestions:**
- **CSS Variables:** Move more layout-specific styles to global CSS variables.
- **Style Engine:** Ensure all block-level styles are processed through the WordPress Style Engine to allow for better minification and consolidation with other block styles.
