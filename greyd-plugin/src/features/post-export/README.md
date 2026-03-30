# Post Export Feature

This feature enables advanced post exports inside the GREYD.SUITE. Posts of all supported post types can be exported via the WordPress backend (edit.php) and later be imported to any GREYD.SUITE site.

## Available Filters

### Post Export Filters

#### greyd_export_post_query_args
Filter to modify the query arguments before exporting posts from a post type.

This filter allows developers to customize the WordPress query arguments used when exporting multiple posts from a custom post type. It's useful for adding custom filters, ordering, or limiting the posts that get exported.

**Parameters:**
- array $query_args - The query arguments for get_posts() function.
- int $post_id - The post ID of the post type definition being exported.
- array $args - The export arguments including options like 'whole_posttype'.

**Returns:** 
array - Modified query arguments for post export.

```php
/**
 * Filter to modify the query arguments before exporting posts from a post type.
 * 
 * This filter allows developers to customize the WordPress query arguments used
 * when exporting multiple posts from a custom post type. It's useful for adding
 * custom filters, ordering, or limiting the posts that get exported.
 * 
 * @filter greyd_export_post_query_args
 * 
 * @param array $query_args  The query arguments for get_posts() function.
 * @param int   $post_id     The post ID of the post type definition being exported.
 * @param array $args        The export arguments including options like 'whole_posttype'.
 * 
 * @return array $query_args Modified query arguments for post export.
 */
$query_args = apply_filters( 'greyd_export_post_query_args', $query_args, $post_id, $args );
```

#### greyd_export_post_meta-dynamic_meta
Filter to modify dynamic meta values before export processing.

This filter allows developers to customize how dynamic meta values are processed during post export, particularly for custom post type fields like files, URLs, and HTML content. It's useful for modifying meta data structure or adding custom export logic for specific field types.

**Parameters:**
- mixed $meta_value - The meta value to be processed and exported.
- int $post_id - The post ID being exported.
- array $args - Export arguments including options like 'append_nested'.

**Returns:** 
mixed - Modified meta value ready for export.

```php
/**
 * Filter to modify dynamic meta values before export processing.
 * 
 * This filter allows developers to customize how dynamic meta values are processed
 * during post export, particularly for custom post type fields like files, URLs,
 * and HTML content. It's useful for modifying meta data structure or adding
 * custom export logic for specific field types.
 * 
 * @filter greyd_export_post_meta-dynamic_meta
 * 
 * @param mixed $meta_value  The meta value to be processed and exported.
 * @param int   $post_id     The post ID being exported.
 * @param array $args        Export arguments including options like 'append_nested'.
 * 
 * @return mixed $meta_value Modified meta value ready for export.
 */
$meta_value = apply_filters( 'greyd_export_post_meta-dynamic_meta', $meta_value, $post_id, $args );
```

### Post Import Filters

#### greyd_import_conflict_actions
Filter to modify conflict actions before processing post imports.

This filter allows developers to customize how conflicts between existing posts and posts being imported are handled. It's useful for implementing custom conflict resolution logic or modifying the default conflict handling behavior.

**Parameters:**
- array $conflict_actions - Array of existing posts with conflicts, keyed by post ID.
- array $posts - Array of posts to be imported, keyed by post ID.

**Returns:** 
array - Modified array of conflict actions.

```php
/**
 * Filter to modify conflict actions before processing post imports.
 * 
 * This filter allows developers to customize how conflicts between existing posts
 * and posts being imported are handled. It's useful for implementing custom
 * conflict resolution logic or modifying the default conflict handling behavior.
 * 
 * @filter greyd_import_conflict_actions
 * 
 * @param array $conflict_actions   Array of existing posts with conflicts, keyed by post ID.
 * @param array $posts              Array of posts to be imported, keyed by post ID.
 * 
 * @return array $conflict_actions  Modified array of conflict actions.
 */
$conflict_actions = apply_filters( 'greyd_import_conflict_actions', $conflict_actions, $posts );
```

#### greyd_import_postarr
Filter to modify the post array before importing a post.

This filter allows developers to customize the post data that will be used when creating or updating posts during import. It's useful for modifying post attributes, adding custom fields, or implementing custom import logic.

**Parameters:**
- array $postarr - Array of post parameters used for wp_insert_post().
- Preparred_Post $post - Preparred post object with meta, taxonomy terms, etc.
- bool $is_first_post - Whether this is the first post of the import batch.

**Returns:** 
array - Modified array of post parameters for import.

```php
/**
 * Filter to modify the post array before importing a post.
 * 
 * This filter allows developers to customize the post data that will be used
 * when creating or updating posts during import. It's useful for modifying
 * post attributes, adding custom fields, or implementing custom import logic.
 * 
 * @filter greyd_import_postarr
 * 
 * @param array $postarr        Array of post parameters used for wp_insert_post().
 * @param Preparred_Post $post  Preparred post object with meta, taxonomy terms, etc.
 * @param bool $is_first_post   Whether this is the first post of the import batch.
 * 
 * @return array $postarr       Modified array of post parameters for import.
 */
$postarr = apply_filters( 'greyd_import_postarr', $postarr, $post, $is_first_post );
```

#### greyd_import_action
Filter to determine the import action for a post.

This filter allows developers to customize how posts are handled during import, including whether to insert, update, set as draft, trash, or delete existing posts. It's useful for implementing custom import strategies or business logic.

**Parameters:**
- string $import_action - The import action to be taken ('insert'|'draft'|'trash'|'delete').
- Preparred_Post $post - The post object being imported.
- int $existing_post_id - The ID of the existing post if there's a conflict.

**Returns:** 
string - The import action to be taken.

```php
/**
 * Filter to determine the import action for a post.
 * 
 * This filter allows developers to customize how posts are handled during import,
 * including whether to insert, update, set as draft, trash, or delete existing posts.
 * It's useful for implementing custom import strategies or business logic.
 * 
 * @since 1.7.0
 * @filter greyd_import_action
 * 
 * @param string $import_action    The import action to be taken. ('insert'|'draft'|'trash'|'delete')
 *   @default 'insert'  Insert or update the post if it already exists.
 *   @value   'draft'   Set the post to draft status.
 *   @value   'trash'   Move the post to trash.
 *   @value   'delete'  Delete the post permanently.
 * @param Preparred_Post $post     The post object being imported.
 * @param int $existing_post_id    The ID of the existing post if there's a conflict.
 * 
 * @return string                  The import action to be taken.
 */
$import_action = apply_filters( 'greyd_import_action', $import_action, $post, $existing_post_id );
```

#### greyd_import_conflict_action
Filter to modify the conflict action for a specific post during import.

This filter allows developers to customize how conflicts are resolved for individual posts during import. It's useful for implementing custom conflict resolution logic or overriding default conflict handling behavior on a per-post basis.

**Parameters:**
- string $conflict_action - The conflict action to be taken ('replace'|'skip'|'keep').
- Preparred_Post $post - The post object being imported.
- int $existing_post_id - The ID of the existing post if there's a conflict.

**Returns:** 
string - The modified conflict action to be taken.

```php
/**
 * Filter to modify the conflict action for a specific post during import.
 * 
 * This filter allows developers to customize how conflicts are resolved for individual
 * posts during import. It's useful for implementing custom conflict resolution logic
 * or overriding default conflict handling behavior on a per-post basis.
 * 
 * @since new
 * @filter greyd_import_conflict_action
 * 
 * @param string $conflict_action    The conflict action to be taken ('replace'|'skip'|'keep').
 * @param Preparred_Post $post       The post object being imported.
 * @param int $existing_post_id      The ID of the existing post if there's a conflict.
 * 
 * @return string                    The modified conflict action to be taken.
 */
$conflict_action = apply_filters( 'greyd_import_conflict_action', $conflict_action, $post, $existing_post_id );
```

#### greyd_filter_post_content_before_post_import
Filter to modify post content before it's imported into the database.

This filter allows developers to customize post content during import, such as cleaning up HTML, replacing placeholders, or applying custom formatting before the content is saved to the database.

**Parameters:**
- string $content - The post content after string replacements.
- int $post_id - The ID of the newly created/updated post.
- object $post - The original Preparred_Post object.

**Returns:** 
string - The modified post content for import.

```php
/**
 * Filter to modify post content before it's imported into the database.
 * 
 * This filter allows developers to customize post content during import,
 * such as cleaning up HTML, replacing placeholders, or applying custom
 * formatting before the content is saved to the database.
 * 
 * @filter greyd_filter_post_content_before_post_import
 * 
 * @param string    $content    The post content after string replacements.
 * @param int       $post_id    The ID of the newly created/updated post.
 * @param object    $post       The original Preparred_Post object.
 * 
 * @return string               The modified post content for import.
 */
$content = apply_filters( 'greyd_filter_post_content_before_post_import', $content, $new_post_id, $post );
```

#### greyd_import_post_meta-{{meta_key}}
Filter to modify specific post meta values before import.

This filter allows developers to customize individual post meta values during import. The filter name is dynamic based on the meta key, allowing for targeted modifications of specific meta fields.

**Parameters:**
- mixed $meta_value - The meta value to be imported.
- int $post_id - The ID of the post being imported.
- object $post - The original Preparred_Post object.

**Returns:** 
mixed - The modified meta value for import.

```php
/**
 * Filter to modify specific post meta values before import.
 * 
 * This filter allows developers to customize individual post meta values
 * during import. The filter name is dynamic based on the meta key,
 * allowing for targeted modifications of specific meta fields.
 * 
 * @filter greyd_import_post_meta-{{meta_key}}
 * 
 * @param mixed $meta_value  The meta value to be imported.
 * @param int   $post_id     The ID of the post being imported.
 * @param object $post       The original Preparred_Post object.
 * 
 * @return mixed             The modified meta value for import.
 */
$meta_value = apply_filters( 'greyd_import_post_meta-' . $meta_key, $meta_value, $post_id, $post );
```

#### greyd_import_post_meta-dynamic_meta
Filter to modify dynamic meta values after import processing.

This filter allows developers to customize how dynamic meta values are processed after they've been imported. It's useful for implementing custom logic for handling dynamic post type fields, resolving placeholders, or applying post-import transformations.

**Parameters:**
- mixed $meta_value - The meta value after initial import processing.
- int $post_id - The ID of the post that was imported.

**Returns:** 
mixed - The modified meta value ready for final storage.

```php
/**
 * Filter to modify dynamic meta values after import processing.
 * 
 * This filter allows developers to customize how dynamic meta values are processed
 * after they've been imported. It's useful for implementing custom logic for
 * handling dynamic post type fields, resolving placeholders, or applying
 * post-import transformations.
 * 
 * @filter greyd_import_post_meta-dynamic_meta
 * 
 * @param mixed $meta_value  The meta value after initial import processing.
 * @param int   $post_id     The ID of the post that was imported.
 * 
 * @return mixed $meta_value  The modified meta value ready for final storage.
 */
$meta_value = apply_filters( 'greyd_import_post_meta-dynamic_meta', $meta_value, $post_id );
```

#### greyd_import_post_conflicts
Filter to modify the list of conflicting posts before returning them.

This filter allows developers to customize the list of posts that conflict with posts being imported. It's useful for adding custom conflict detection logic or filtering out certain types of conflicts.

**Parameters:**
- array $conflicts - Array of conflicting posts, keyed by post ID.
- array $posts - Array of posts being imported, keyed by post ID.

**Returns:** 
array - Modified array of conflicting posts.

```php
/**
 * Filter to modify the list of conflicting posts before returning them.
 * 
 * This filter allows developers to customize the list of posts that conflict
 * with posts being imported. It's useful for adding custom conflict detection
 * logic or filtering out certain types of conflicts.
 * 
 * @filter greyd_import_post_conflicts
 * 
 * @param array $conflicts  Array of conflicting posts, keyed by post ID.
 * @param array $posts      Array of posts being imported, keyed by post ID.
 * 
 * @return array            Modified array of conflicting posts.
 */
$conflicts = apply_filters( 'greyd_import_post_conflicts', $conflicts, $posts );
```

### Preparred_Post Class Filters

#### greyd_export_post_meta-{{meta_key}}
Filter to modify specific post meta values before export.

This filter allows developers to customize individual post meta values during export. The filter name is dynamic based on the meta key, allowing for targeted modifications of specific meta fields.

**Parameters:**
- mixed $meta_value - The meta value to be exported.
- int $post_id - The ID of the post being exported.
- array $export_arguments - The export arguments passed to the constructor.

**Returns:** 
mixed - The modified meta value for export.

```php
/**
 * Filter to modify specific post meta values before export.
 * 
 * This filter allows developers to customize individual post meta values
 * during export. The filter name is dynamic based on the meta key,
 * allowing for targeted modifications of specific meta fields.
 * 
 * @filter greyd_export_post_meta-{{meta_key}}
 * 
 * @param mixed $meta_value      The meta value to be exported.
 * @param int   $post_id        The ID of the post being exported.
 * @param array $export_arguments The export arguments passed to the constructor.
 * 
 * @return mixed                The modified meta value for export.
 */
$meta_value = apply_filters( 'greyd_export_post_meta-' . $meta_key, $meta_value, $post_id, $export_arguments );
```

#### greyd_post_export_resolve_menus
Filter to modify the post content after resolving navigation menus.

This filter allows developers to customize post content after navigation links have been converted to static links during export. It's useful for applying additional content modifications or custom formatting.

**Parameters:**
- string $subject - The post content after menu resolution.
- int $post_id - The ID of the post being exported.
- object $post - The Preparred_Post object.

**Returns:** 
string - The modified post content for export.

```php
/**
 * Filter to modify the post content after resolving navigation menus.
 * 
 * This filter allows developers to customize post content after navigation
 * links have been converted to static links during export. It's useful for
 * applying additional content modifications or custom formatting.
 * 
 * @filter greyd_post_export_resolve_menus
 * 
 * @param string $subject  The post content after menu resolution.
 * @param int    $post_id  The ID of the post being exported.
 * @param object $post     The Preparred_Post object.
 * 
 * @return string          The modified post content for export.
 */
$post_content = apply_filters( 'greyd_post_export_resolve_menus', $subject, $post_id, $post );
```

### Content Pattern Filters

#### greyd_regex_nested_posts
Filter to customize regex patterns for finding nested posts in post content.

This filter allows developers to add custom regex patterns for detecting and replacing nested post references in post content during export. It's useful for supporting custom block types or content structures.

**Parameters:**
- array $patterns - Array of regex pattern arguments for post detection.
- int $post_id - The WP_Post ID being exported.
- WP_Post $post - The WP_Post Object being exported.

**Returns:** 
array - Modified array of regex patterns for post detection.

```php
/**
 * Filter to customize regex patterns for finding nested posts in post content.
 * 
 * This filter allows developers to add custom regex patterns for detecting
 * and replacing nested post references in post content during export.
 * It's useful for supporting custom block types or content structures.
 * 
 * @filter greyd_regex_nested_posts
 * 
 * @param array   $patterns     Array of regex pattern arguments for post detection.
 * @param int     $post_id      The WP_Post ID being exported.
 * @param WP_Post $post         The WP_Post Object being exported.
 * 
 * @return array                Modified array of regex patterns for post detection.
 */
$patterns = apply_filters( 'greyd_regex_nested_posts', $patterns, $post_id, $post );
```

#### greyd_regex_nested_strings
Filter to customize string replacement patterns for post content export.

This filter allows developers to add custom string patterns that should be replaced with placeholders during export. It's useful for handling site-specific URLs, paths, or other dynamic content.

**Parameters:**
- string[] $strings - Array of strings to be replaced, keyed by placeholder name.
- string $content - The post content being processed.
- int $post_id - The ID of the post being exported.

**Returns:** 
string[] - Modified array of strings to be replaced.

```php
/**
 * Filter to customize string replacement patterns for post content export.
 * 
 * This filter allows developers to add custom string patterns that should
 * be replaced with placeholders during export. It's useful for handling
 * site-specific URLs, paths, or other dynamic content.
 * 
 * @filter greyd_regex_nested_strings
 * 
 * @param string[]  $strings   Array of strings to be replaced, keyed by placeholder name.
 * @param string    $content   The post content being processed.
 * @param int       $post_id   The ID of the post being exported.
 * 
 * @return string[]            Modified array of strings to be replaced.
 */
$strings = apply_filters( 'greyd_regex_nested_strings', $strings, $content, $post_id );
```

#### greyd_regex_nested_terms
Filter to customize regex patterns for finding nested terms in post content.

This filter allows developers to add custom regex patterns for detecting and replacing nested taxonomy term references in post content during export. It's useful for supporting custom term-based content structures.

**Parameters:**
- array $patterns - Array of regex pattern arguments for term detection.
- int $post_id - The WP_Post ID being exported.
- WP_Post $post - The WP_Post Object being exported.

**Returns:** 
array - Modified array of regex patterns for term detection.

```php
/**
 * Filter to customize regex patterns for finding nested terms in post content.
 * 
 * This filter allows developers to add custom regex patterns for detecting
 * and replacing nested taxonomy term references in post content during export.
 * It's useful for supporting custom term-based content structures.
 * 
 * @filter greyd_regex_nested_terms
 * 
 * @param array   $patterns     Array of regex pattern arguments for term detection.
 * @param int     $post_id      The WP_Post ID being exported.
 * @param WP_Post $post         The WP_Post Object being exported.
 * 
 * @return array                Modified array of regex patterns for term detection.
 */
$patterns = apply_filters( 'greyd_regex_nested_terms', $patterns, $post_id, $post );
```

#### greyd_regex_nested_menus
Filter to customize regex patterns for finding nested menus in post content.

This filter allows developers to add custom regex patterns for detecting and replacing nested menu references in post content during export. It's useful for supporting custom menu structures or shortcodes.

**Parameters:**
- array $patterns - Array of regex pattern arguments for menu detection.
- int $post_id - The WP_Post ID being exported.
- WP_Post $post - The WP_Post Object being exported.

**Returns:** 
array - Modified array of regex patterns for menu detection.

```php
/**
 * Filter to customize regex patterns for finding nested menus in post content.
 * 
 * This filter allows developers to add custom regex patterns for detecting
 * and replacing nested menu references in post content during export.
 * It's useful for supporting custom menu structures or shortcodes.
 * 
 * @filter greyd_regex_nested_menus
 * 
 * @param array   $patterns     Array of regex pattern arguments for menu detection.
 * @param int     $post_id      The WP_Post ID being exported.
 * @param WP_Post $post         The WP_Post Object being exported.
 * 
 * @return array                Modified array of regex patterns for menu detection.
 */
$patterns = apply_filters( 'greyd_regex_nested_menus', $patterns, $post_id, $post );
```

### Helper Class Filters

#### greyd_export_blacklisted_meta
Filter to customize the list of blacklisted meta keys for export.

This filter allows developers to add or remove meta keys that should be excluded from post exports. It's useful for preventing sensitive or site-specific meta data from being exported.

**Parameters:**
- array $blacklisted_meta - Array of meta keys to exclude from export.

**Returns:** 
array - Modified array of blacklisted meta keys.

```php
/**
 * Filter to customize the list of blacklisted meta keys for export.
 * 
 * This filter allows developers to add or remove meta keys that should
 * be excluded from post exports. It's useful for preventing sensitive
 * or site-specific meta data from being exported.
 * 
 * @filter greyd_export_blacklisted_meta
 * 
 * @param array $blacklisted_meta Array of meta keys to exclude from export.
 * 
 * @return array                   Modified array of blacklisted meta keys.
 */
$blacklisted_meta = apply_filters( 'greyd_export_blacklisted_meta', $blacklisted_meta );
```

#### greyd_export_maybe_skip_meta_option
Filter to determine whether a specific meta option should be skipped during export.

This filter allows developers to implement custom logic for determining whether specific meta keys or values should be excluded from export. It's useful for implementing site-specific export rules or business logic.

**Parameters:**
- bool $skip_meta - Whether to skip the meta option (default: false).
- string $meta_key - The meta key being evaluated.
- mixed $meta_value - The meta value being evaluated.

**Returns:** 
bool - Whether to skip the meta option.

```php
/**
 * Filter to determine whether a specific meta option should be skipped during export.
 * 
 * This filter allows developers to implement custom logic for determining
 * whether specific meta keys or values should be excluded from export.
 * It's useful for implementing site-specific export rules or business logic.
 * 
 * @filter greyd_export_maybe_skip_meta_option
 * 
 * @param bool   $skip_meta    Whether to skip the meta option (default: false).
 * @param string $meta_key     The meta key being evaluated.
 * @param mixed  $meta_value   The meta value being evaluated.
 * 
 * @return bool                Whether to skip the meta option.
 */
$skip_meta = apply_filters( 'greyd_export_maybe_skip_meta_option', false, $meta_key, $meta_value );
```

### Admin Class Filters

#### greyd_post_export_is_current_screen_supported
Filter to customize whether the current screen supports post export/import functionality.

This filter allows developers to extend or modify the logic that determines which admin screens should display post export/import options. It's useful for adding support to custom post type screens or implementing custom screen detection logic.

**Parameters:**
- bool $supported - Whether the current screen supports export/import.
- object $screen - The current WP_Screen object.

**Returns:** 
bool - Whether the current screen supports export/import.

```php
/**
 * Filter to customize whether the current screen supports post export/import functionality.
 * 
 * This filter allows developers to extend or modify the logic that determines
 * which admin screens should display post export/import options. It's useful
 * for adding support to custom post type screens or implementing custom
 * screen detection logic.
 * 
 * @filter greyd_post_export_is_current_screen_supported
 * 
 * @param bool  $supported Whether the current screen supports export/import.
 * @param object $screen   The current WP_Screen object.
 * 
 * @return bool           Whether the current screen supports export/import.
 */
$supported = apply_filters( 'greyd_post_export_is_current_screen_supported', $supported, $screen );
```
