# War Plan: Debugging the Query Loop Filter Conflict

This document outlines a methodical approach to identify the conflict between `query-loop-filter`, the Greyd theme, and the Greyd plugin. The objective is not to fix the issue within this plugin, but to gather definitive evidence of what is causing the interference, so you can report it to the responsible developer.

We will proceed in phases, escalating our investigation from general setup to deep-level logging.

---

## Phase 1: Enable WordPress Debugging

First, we need to enable WordPress's built-in debugging to capture all PHP errors, warnings, and notices.

1.  Open your `wp-config.php` file, which is in the root directory of your WordPress installation.
2.  Find the line that says `define( 'WP_DEBUG', false );`.
3.  Replace it with the following code block:

```php
// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

// Enable Debug logging to the /wp-content/debug.log file
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );
```

4.  After adding this, perform the action that causes the bug (e.g., try to use a filter). A new file will be created at `/wp-content/debug.log`. Monitor this file for any clues.

---

## Phase 2: Add High-Visibility Logging to the Plugin

Now, we will modify `query-filter` to log exactly what it's doing. This will show us what query variables it's trying to set.

1.  **Open the file:** `inc/namespace.php` in the `query-filter` plugin directory.
2.  **Locate the function:** Find the `pre_get_posts_transpose_query_vars( $query )` function.
3.  **Add Logging Code:** At the very beginning of this function, add the following code:

```php
// WAR-PLAN-DEBUG: Log initial state
if ( ! empty( $_GET['queryId'] ) ) {
    error_log('--- WAR-PLAN: START ---');
    error_log('Target Query ID from URL: ' . sanitize_text_field($_GET['queryId']));
    error_log('Current Page URL: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    error_log('Is Main Query? ' . ($query->is_main_query() ? 'Yes' : 'No'));
    error_log('Original Query Vars: ' . print_r($query->query_vars, true));
}
```

4.  **Add More Logging:** At the very end of the same function, just before the `return $query;` line, add this:

```php
// WAR-PLAN-DEBUG: Log modified state
if ( ! empty( $_GET['queryId'] ) ) {
    error_log('Modified Query Vars: ' . print_r($query->query_vars, true));
    error_log('--- WAR-PLAN: END ---');
}
```

This will create detailed logs in `/wp-content/debug.log` every time the filter is triggered via a URL parameter.

---

## Phase 3: Monitor for Post-Modification Interference

It's possible another plugin is changing the query *after* our plugin has done its job. We'll add a separate, high-priority hook to check for this.

1.  **Open the file:** `inc/namespace.php`.
2.  **Add a new function:** At the end of the file, before the closing `}` of the namespace, add this new function:

```php
/**
 * WAR-PLAN-DEBUG: Final Query Check
 *
 * Checks the query vars at a very late stage to see if they have been
 * overwritten by another plugin or theme.
 *
 * @param \WP_Query $query The query object.
 */
function war_plan_final_query_check( $query ) {
    // Only run on the front-end, for the targeted query, and when our filter is active.
    if ( is_admin() || ! isset( $_GET['queryId'] ) || empty( $_GET['queryId'] ) ) {
        return;
    }
    
    $query_id = $query->get( 'queryId' );
    if ( empty( $query_id ) || $query_id !== $_GET['queryId'] ) {
        return;
    }

    error_log('--- WAR-PLAN: FINAL CHECK (Priority 9999) ---');
    error_log('Final Query Vars seen by WordPress: ' . print_r($query->query_vars, true));
    error_log('--- WAR-PLAN: FINAL CHECK END ---');
}
```
3.  **Hook it in:** Now, find the `bootstrap()` function in the same file. At the end of that function, add this line:

```php
// WAR-PLAN-DEBUG: Add the final checker hook.
add_action( 'pre_get_posts', __NAMESPACE__ . '\war_plan_final_query_check', 9999 );
```

This will log the *final* state of the query variables right before WordPress executes the database query.

---

## Phase 4: Data Collection and Analysis

You are now ready to collect the evidence.

1.  **Clear the log:** Delete the `/wp-content/debug.log` file if it exists.
2.  **Open Developer Tools:** Open your browser's Developer Tools and switch to the "Console" tab. Keep this open.
3.  **Reproduce the Bug:**
    *   Go to the page with your Query Loop and filters.
    *   Click on a filter option (e.g., a category).
    *   Observe that the content does not update correctly.
4.  **Gather the Evidence:**
    *   Copy the entire contents of the `/wp-content/debug.log` file.
    *   Copy any errors you see in the browser's JavaScript console.
5.  **Analyze the `debug.log`:**
    *   Look for the blocks starting with `--- WAR-PLAN: START ---`.
    *   **Inside this block**, compare the `Original Query Vars` with the `Modified Query Vars`. You should see your filter (e.g., `category_name` or `tax_query`) added in the "Modified" version.
    *   Now, find the `--- WAR-PLAN: FINAL CHECK ---` block for the same request.
    *   **Compare the `Modified Query Vars` from the first block with the `Final Query Vars` from this second block.**
    *   **If they are different**, you have found your culprit. Something between priority 10 (the plugin's default) and 9999 (our checker) is changing the query. This is strong evidence of interference.

The contents of this `debug.log` will be the "smoking gun" you need. It will show exactly how the query is being manipulated and by whom (implicitly).
