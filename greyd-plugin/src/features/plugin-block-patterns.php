<?php
/*
Feature Name:   Enable block pattern registration for plugins.
Description:    This enables the same functionality as the core block patterns for themes, but for plugins. Plugin authors can place their patterns in the `./patterns/` directory and they will be automatically registered.
Plugin URI:     https://greyd.io
Author:         Greyd
Author URI:     https://greyd.io
Version:        1.9.7
Text Domain:    greyd_hub
Domain Path:    /languages/
Priority:       99
Forced:         true
*/
if ( ! function_exists( 'greyd_init_plugin_block_patterns' ) ) {
	function greyd_init_plugin_block_patterns() {

		/**
		 * Filter the plugins to register block patterns for.
		 * 
		 * @since 1.9.7
		 * 
		 * @param array $plugins An array of plugins to register block patterns for.
		 *                   Each plugin should be an array with the following keys:
		 *                   - path: The path to the plugin directory.
		 *                   - text_domain: The plugin text domain.
		 * 
		 * @return array An array of plugins to register block patterns for.
		 */
		$plugins = apply_filters( 'greyd_block_pattern_plugins', array() );

		if ( !empty( $plugins ) && is_array( $plugins ) ) {
			foreach ( $plugins as $plugin ) {

				if ( ! isset( $plugin['path'] ) || ! isset( $plugin['text_domain'] ) ) {
					continue;
				}

				greyd_register_plugin_block_patterns( $plugin['path'], $plugin['text_domain'] );
			}
		}
	}
}

add_action( 'init', 'greyd_init_plugin_block_patterns' );


if ( ! function_exists( 'greyd_register_plugin_block_patterns' ) ) {

	/**
	 * Register any patterns under the `./patterns/` directory.
	 *
	 * @since 1.9.7
	 *
	 * @see wp-includes/block-patterns.php
	 *
	 * Each pattern is defined as a PHP file and defines its metadata
	 * using plugin-style headers. The minimum required definition is:
	 *
	 *     /**
	 *      * Title: My Pattern
	 *      * Slug: my-theme/my-pattern
	 *      *
	 *
	 * The output of the PHP source corresponds to the content of the pattern, e.g.:
	 *
	 *     <main><p><?php echo "Hello"; ?></p></main>
	 *
	 * Other settable fields include:
	 *
	 *   - Description
	 *   - Viewport Width
	 *   - Inserter         (yes/no)
	 *   - Categories       (comma-separated values)
	 *   - Keywords         (comma-separated values)
	 *   - Block Types      (comma-separated values)
	 *   - Post Types       (comma-separated values)
	 *   - Template Types   (comma-separated values)
	 */
	function greyd_register_plugin_block_patterns( $plugin_path, $text_domain = '' ) {
		
		$default_headers = array(
			'title'         => 'Title',
			'slug'          => 'Slug',
			'description'   => 'Description',
			'viewportWidth' => 'Viewport Width',
			'inserter'      => 'Inserter',
			'categories'    => 'Categories',
			'keywords'      => 'Keywords',
			'blockTypes'    => 'Block Types',
			'postTypes'     => 'Post Types',
			'templateTypes' => 'Template Types',
		);

		$dirpath = untrailingslashit( $plugin_path ) . '/patterns/';

		if ( ! is_dir( $dirpath ) || ! is_readable( $dirpath ) ) {
			return;
		}
		if ( file_exists( $dirpath ) ) {
			$files = glob( $dirpath . '*.php' );
			if ( $files ) {
				foreach ( $files as $file ) {
					$pattern_data = get_file_data( $file, $default_headers );

					if ( empty( $pattern_data['slug'] ) ) {
						_doing_it_wrong(
							'_register_theme_block_patterns',
							sprintf(
								/* translators: %s: file name. */
								__( 'Could not register file "%s" as a block pattern ("Slug" field missing)' ),
								$file
							),
							'6.0.0'
						);
						continue;
					}

					if ( ! preg_match( '/^[A-z0-9\/_-]+$/', $pattern_data['slug'] ) ) {
						_doing_it_wrong(
							'_register_theme_block_patterns',
							sprintf(
								/* translators: %1s: file name; %2s: slug value found. */
								__( 'Could not register file "%1$s" as a block pattern (invalid slug "%2$s")' ),
								$file,
								$pattern_data['slug']
							),
							'6.0.0'
						);
					}

					if ( ! class_exists( '\WP_Block_Patterns_Registry' ) || \WP_Block_Patterns_Registry::get_instance()->is_registered( $pattern_data['slug'] ) ) {
						// echo "<pre>";
						// var_dump( $pattern_data );
						// echo "</pre>";
						// exit;
						// continue;
					}

					// Title is a required property.
					if ( ! $pattern_data['title'] ) {
						_doing_it_wrong(
							'_register_theme_block_patterns',
							sprintf(
								/* translators: %1s: file name; %2s: slug value found. */
								__( 'Could not register file "%s" as a block pattern ("Title" field missing)' ),
								$file
							),
							'6.0.0'
						);
						continue;
					}

					// For properties of type array, parse data as comma-separated.
					foreach ( array( 'categories', 'keywords', 'blockTypes', 'postTypes', 'templateTypes' ) as $property ) {
						if ( ! empty( $pattern_data[ $property ] ) ) {
							$pattern_data[ $property ] = array_filter(
								preg_split(
									'/[\s,]+/',
									(string) $pattern_data[ $property ]
								)
							);
						} else {
							unset( $pattern_data[ $property ] );
						}
					}

					// Parse properties of type int.
					foreach ( array( 'viewportWidth' ) as $property ) {
						if ( ! empty( $pattern_data[ $property ] ) ) {
							$pattern_data[ $property ] = (int) $pattern_data[ $property ];
						} else {
							unset( $pattern_data[ $property ] );
						}
					}

					// Parse properties of type bool.
					foreach ( array( 'inserter' ) as $property ) {
						if ( ! empty( $pattern_data[ $property ] ) ) {
							$pattern_data[ $property ] = in_array(
								strtolower( $pattern_data[ $property ] ),
								array( 'yes', 'true' ),
								true
							);
						} else {
							unset( $pattern_data[ $property ] );
						}
					}

					// Translate the pattern metadata.
					if ( ! empty( $text_domain ) ) {
						//phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.LowLevelTranslationFunction
						$pattern_data['title'] = translate_with_gettext_context( $pattern_data['title'], 'Pattern title', $text_domain );
						if ( ! empty( $pattern_data['description'] ) ) {
							//phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.LowLevelTranslationFunction
							$pattern_data['description'] = translate_with_gettext_context( $pattern_data['description'], 'Pattern description', $text_domain );
						}
					}

					// The actual pattern content is the output of the file.
					ob_start();
					include $file;
					$pattern_data['content'] = ob_get_clean();
					if ( ! $pattern_data['content'] ) {
						continue;
					}

					register_block_pattern( $pattern_data['slug'], $pattern_data );
				}
			}
		}
	}
}