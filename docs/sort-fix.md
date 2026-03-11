# Analysis of Sort Block AJAX Compatibility

The Sort block currently faces issues when used with the "AJAX way" (Interactivity API / Router) of the Query Loop block. This document outlines the identified problems and proposed options for fixing them.

## Identified Issues

1.  **Namespace Conflict:** The plugin currently overrides the `data-wp-interactive` attribute on the `core/query` block with `query-filter`. This breaks the core `core/query` interactivity (like Enhanced Pagination) because pagination links inside the block will look for actions in the `query-filter` store instead of the `core/query` store.
2.  **Missing Router Regions:** The Sort block (and other filter blocks) are not marked as `data-wp-router-region`. If they are placed outside the `core/query` block (even if they are adjacent), they are not updated when the router fetches new content. This results in the Sort block not reflecting the current "active" state or updated URLs after a filter/sort action.
3.  **Dropdown State Management:** The dropdown state (`isOpen`) is stored in the block's context. While this works for opening/closing, a full region replacement resets this state.
4.  **Enqueuing Logic:** The Sort block relies on the taxonomy block's `view.js`. While functional, it might be cleaner to have a shared core interactivity file or ensure the sort block is self-contained.

---

## Proposed Options

### Option 1: Global Router Region Synchronization
Make every filter/sort block a unique router region.
*   **Pros:** Ensures UI remains in sync with the URL even when blocks are placed outside the main Query Loop.
*   **Action:** Add `data-wp-router-region` to the wrapper in `src/sort/render.php` and `src/taxonomy/render.php`. The ID should be derived from the `queryId` and the block type.
*   **Risk:** Requires consistent ID generation between the initial page load and AJAX requests.

### Option 2: Namespace Refactoring (Non-Invasive)
Stop overriding the `core/query` namespace and use the Interactivity API more surgically.
*   **Pros:** Restores compatibility with Core's Enhanced Pagination and other Query Loop features.
*   **Action:** 
    *   In `inc/namespace.php`, modify `render_block_query` to NOT set `data-wp-interactive`.
    *   Ensure the Query Loop *is* a region, but perhaps using a name that doesn't conflict with core if we want to manage it ourselves, or better yet, use core's naming convention if possible.
    *   Use the Interactivity API's ability to support multiple namespaces if needed.

### Option 3: Shared Interactivity Logic
Refactor the front-end logic into a dedicated "core" or "shared" module instead of pointing everything to `taxonomy/view.js`.
*   **Pros:** Better maintainability and avoids confusion.
*   **Action:** Create `src/shared/view.js` and update all `block.json` files to point to it.

### Option 4: State-Aware Sorting
Instead of relying solely on `$_GET` in PHP for the "active" state, use the Interactivity API `state` to track the current sort/filter.
*   **Pros:** Faster UI updates (immediate "active" state change before the fetch completes).
*   **Cons:** More complex logic to sync state with the server-side rendering.

---

## Recommended Strategy

I recommend a combination of **Option 1** and **Option 2**:
1.  **Fix the Namespace:** Remove the `data-wp-interactive` override on the query block. This is the most likely cause of breakage for users using Core's AJAX features.
2.  **Add Regions:** Add `data-wp-router-region` to the Sort and Taxonomy blocks. This ensures that when any filter is clicked, all filter blocks on the page update to show the correct active states based on the new URL.
3.  **Consolidate JS:** Move the shared logic to a central location to ensure all blocks have access to the `navigate` and `toggle` actions without cross-referencing other blocks.
