# Query Loop Filters - Project Context

This project is a WordPress plugin that provides filter blocks for the Query Loop block, utilizing the WordPress Interactivity API for a seamless, client-side filtering experience.

## Project Overview

*   **Purpose:** Easily add filters (Taxonomy, Post Type, Search) to any Query Loop block.
*   **Key Features:**
    *   **Taxonomy Filter:** Filter queries by terms of a selected taxonomy.
    *   **Post Type Filter:** Filter queries by post type (requires the Advanced Query Loop plugin for full support).
    *   **Search Integration:** Enhances the core Search block to work with Query Loop filters.
    *   **Interactivity API:** Built with the WordPress Interactivity API for fast, AJAX-like updates without full page reloads.
*   **Main Technologies:**
    *   **PHP:** WordPress plugin architecture, `WP_Query` manipulation, and `WP_HTML_Tag_Processor` for block rendering.
    *   **JavaScript:** React for the Gutenberg block editor; Interactivity API for front-end behavior.
    *   **Build System:** `@wordpress/scripts` for transpiling JS/CSS and managing block assets.
    *   **Dependency Management:** npm for JavaScript; Composer for PHP (primarily for installers and update checker).

## Architecture

*   **`query-filter.php`:** The main entry point. Initializes the plugin and sets up the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).
*   **`inc/namespace.php`:** Contains the core PHP logic.
    *   `bootstrap()`: Hooks into WordPress (actions and filters).
    *   `register_blocks()`: Registers the custom blocks from the `build/` directory.
    *   `pre_get_posts_transpose_query_vars()`: The heart of the filtering logic, mapping URL parameters to `WP_Query` arguments.
    *   `render_block_search()`: Enhances the core Search block with Interactivity API attributes.
*   **`src/`:** Source code for the Gutenberg blocks.
    *   `post-type/`: Source for the Post Type filter block.
    *   `taxonomy/`: Source for the Taxonomy filter block.
*   **`build/`:** Generated assets (JS, CSS, `block.json`) created by `@wordpress/scripts`. These files are enqueued by the plugin.
*   **`plugin-update-checker/`:** Bundled library to handle updates from GitHub.

## Building and Running

The project uses `@wordpress/scripts` for all development tasks.

*   **Build Production Assets:**
    ```bash
    npm run build
    ```
*   **Start Development Mode (Watch):**
    ```bash
    npm run start
    ```
*   **Code Formatting:**
    ```bash
    npm run format
    ```
*   **Linting:**
    ```bash
    npm run lint:js
    ```
    ```bash
    npm run lint:css
    ```

## Development Conventions

*   **Interactivity API:** All front-end interactions should use the Interactivity API. Logic is typically found in `view.js` files within the block source.
*   **Asset Management:** Built files in the `build/` directory **must be committed** to the repository as part of any pull request, as there is currently no automated build process on release.
*   **PHP Coding Standards:** Follow standard WordPress PHP coding conventions. The project uses namespaces (`HM\Query_Loop_Filter`) to avoid collisions.
*   **Version Management:** Follow SemVer for major and minor releases. Releases are managed via GitHub.
*   **Query Identification:** The plugin uses `queryId` from the Query Loop block context to scope filters to specific queries on a page.
