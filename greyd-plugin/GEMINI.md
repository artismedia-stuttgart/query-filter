# Greyd Plugin

## Project Overview
The **Greyd Plugin** is the core component of the **Greyd.Suite**, a comprehensive platform designed to scale WordPress businesses. It extends the WordPress Block and Site Editors with powerful features including website management, custom post types, headless capabilities, forms, and more.

### Main Technologies
- **PHP**: Core plugin logic and WordPress integration.
- **JavaScript (JSX/ESNext)**: Used for building custom WordPress blocks and interactive features.
- **CSS/SCSS**: Styling for both the admin dashboard and frontend components.
- **WordPress**: Minimum version 5.8, tested up to 6.6.1.

### Architecture
The plugin follows a modular **Features** architecture. 
- **Initialization**: `init.php` defines core constants and includes necessary files from `inc/`.
- **Features System**: Managed by `inc/features/features.php`. Features are self-contained modules located in `src/features/`. They can be dynamically enabled or disabled via the Greyd dashboard.
- **Directory Structure**:
    - `inc/`: Core logic, including admin pages, AJAX handlers, helpers, and the features manager.
    - `src/`: Source files for features, including PHP logic and raw JS/JSX/CSS assets.
    - `build/`: Compiled and optimized assets used by the plugin in production.
    - `assets/`: Static assets like images, fonts, and legacy JS/CSS.
    - `patterns/`: Block patterns for the WordPress editor.
    - `languages/`: Translation files (Text Domain: `greyd_hub`).

## Building and Running

### Installation
1.  Place the `greyd-plugin` folder into your WordPress `wp-content/plugins/` directory.
2.  Activate the plugin through the WordPress Admin Dashboard.
3.  On activation, a setup wizard (`inc/wizard/`) will guide you through the initial configuration.

### Build Process
The plugin uses a build step to compile assets from `src/` to `build/`. 
- **Mapping**: The file `build/map.json` tracks the relationship between source and built files.
- **Tooling**: While a `package.json` is not present in this specific directory, the build process likely uses `@wordpress/scripts` or a similar Webpack-based configuration from a parent repository or external build environment.
- **TODO**: If local development of blocks or JS is required, verify the presence of a build environment in the root of the Greyd.Suite project.

## Development Conventions

### Namespacing & Coding Style
- **Namespace**: All PHP classes and functions should reside under the `Greyd` namespace (e.g., `namespace Greyd;`).
- **Coding Standards**: Adhere to [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).
- **Helpers**: Use the `Greyd\Helper` class (`inc/helper.php`) for common utility functions like checking active plugins or theme versions.

### Adding New Features
1.  Create a new directory or PHP file in `src/features/`.
2.  If it's a directory, include an `init.php` as the entry point.
3.  Add standard WordPress plugin headers to the feature's main file (Name, Version, Description, Author) for the features manager to recognize it.
4.  Optionally use headers like `Priority`, `Forced`, or `Hidden` to control its behavior in the Greyd dashboard.

### Blocks Development
- Custom blocks are primarily located in `src/features/blocks/`.
- Block metadata and registration follow the modern `block.json` standard where applicable.
- JSX components and view scripts are compiled into the `build/` directory.

### Translations
- Use `greyd_hub` as the text domain for all translatable strings.
- Translation files are stored in the `languages/` directory.
