<?php
/**
 * Dynamic Posttypes Utility functions
 */

namespace Greyd\Posttypes;

use Greyd\Helper as Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Posttype_Helper();
class Posttype_Helper {

	/**
	 * Get all dynamic posttypes.
	 * Result is cached ( 'dynamic_posttypes', 'greyd' )
	 * 
	 * @return array
	 */
	public static function get_dynamic_posttypes() {

		$cache = wp_cache_get( 'dynamic_posttypes', 'greyd' );
		if ( $cache ) {
			return $cache;
		}

		$post_types = [];
		$result = get_posts(array(
			'post_type'         => Admin::POST_TYPE,
			'posts_per_page'    => -1,
			'post_status'       => 'publish',
			'fields'            => 'ids'
		));
		if ( is_array($result) && !empty($result) ) {
			foreach ( $result as $post_id ) {
				$meta = self::get_posttype_settings( $post_id );

				if ( empty($meta) ) {
					$meta = array(
						'slug'      => '',
						'title'     => '',
						'singular'  => '',
						'plural'    => '',
						'fields'    => array()
					);
					if ( $post = get_post($post_id) ) {
						$meta = array(
							'slug'      => $post->post_name,
							'title'     => $post->post_title,
							'singular'  => $post->post_title,
							'plural'    => $post->post_title,
							'fields'    => array()
						);
					}
					$meta = array_merge( Admin::DEFAULTS, $meta );
				}
				$meta['id'] = $post_id;
				// debug($meta);
				if (isset($meta['is_taxonomy']) && $meta['is_taxonomy']) continue;

				$post_types[$post_id] = $meta;
			}
		}

		wp_cache_set( 'dynamic_posttypes', $post_types, 'greyd' );

		return $post_types;
	}
	
	/**
	 * Get all global taxonomies (posttype variation).
	 * 
	 * @param string $post_type     Posttype slug (optional)
	 *                              If set, only taxonomies for this posttype are returned.
	 * 
	 * @return array
	 */
	public static function get_global_taxonomies( $post_type=null ) {

		$taxonomies = [];
		$result = get_posts(array(
			'post_type'         => Admin::POST_TYPE,
			'posts_per_page'    => -1,
			'post_status'       => 'publish',
			'fields'            => 'ids'
		));
		if ( is_array($result) && !empty($result) ) {
			foreach ( $result as $post_id ) {
				$meta = self::get_posttype_settings( $post_id );

				if ( !empty($meta) ) {

					// debug($meta);
					if (isset($meta['is_taxonomy']) && $meta['is_taxonomy']) {

						// if a posttype is set, only return taxonomies for this posttype
						if ( !empty( $post_type ) ) {

							$supported_posttypes = isset($meta["posttypes"]) ? (array) $meta["posttypes"] : array();

							// if not in supported posttypes, skip
							if ( !in_array($post_type, $supported_posttypes) ) {
								continue;
							}
						}

						$meta['id'] = $post_id;
						$taxonomies[$post_id] = $meta;
					}
				}
			}
		}
		return $taxonomies;

	}

	/**
	 * Get dynamic posttype by slug.
	 * Result is cached ( 'dynamic_posttype_'.$slug, 'greyd' )
	 * 
	 * @param string $slug	Posttype slug
	 * @return object|bool	posttype or false
	 */
	public static function get_dynamic_posttype_by_slug( $slug ) {

		if ( empty($slug) ) return false;

		$cache = wp_cache_get( 'dynamic_posttype_'.$slug, 'greyd' );
		if ( $cache ) {
			return $cache;
		}

		$posttypes = self::get_dynamic_posttypes();
		foreach ($posttypes as $posttype) {
			if (isset($posttype["slug"]) && $posttype["slug"] === $slug) {

				wp_cache_set( 'dynamic_posttype_'.$slug, $posttype, 'greyd' );

				return $posttype;
			}
		}
		return false;
	}

	/**
	 * Get dynamic posttype by ID.
	 * 
	 * @param int $post_id	Posttype ID
	 * @return object		posttype
	 */
	public static function get_dynamic_posttype_by_id( $post_id ) {
		if (!isset($post_id)) return false;

		// get all posttypes
		$meta = self::get_posttype_settings( $post_id );

		foreach (Admin::DEFAULTS as $key => $val) {
			if (!isset($meta[$key])) {
				$meta[$key] = $val;
			}
			else {
				$default_type = gettype($val);
				$current_type = gettype($meta[$key]);
				if ( $default_type !== $current_type ) {
					settype($meta[$key], $default_type);
				}
			}
		}
		return $meta;
	}

	/**
	 * Get the correct posttype slug from the post_title.
	 * 
	 * @since 1.1   New posttypes like 'Häuser und Gebäude' now use the
	 *              slug 'haeuser-und-gebaeude' instead of 'huserundgebude'
	 * 
	 * @param string $title     Usually the post_title.
	 * @param bool $cut         Whether to cut the string after 20 characters.
	 * 
	 * @return string
	 */
	public static function get_posttype_slug_from_title( $title, $cut=true ) {
		$slug_the_old_way = self::get_clean_slug( $title, $cut );
		return self::get_dynamic_posttype_by_slug($slug_the_old_way) ? $slug_the_old_way : self::get_nice_slug( $title, $cut );
	}


	/**
	 * Get all editable posttypes.
	 * 'post' 'page' and other non-internal posttypes.
	 * 
	 * @return array
	 */
	public static function get_editable_posttypes() {

		$include = array(
			'post'    => 'post',
			'page'    => 'page'
		);

		$exclude = array(
			'tp_forms_entry',
			'vc_grid_item',
			'vc4_templates',
			'shop_order',
			'shop_order_refund',
			'shop_coupon',
			'scheduled-action',
			'acf-field-group',
			'acf-field',
			'tp_forms',
			'tp_forms_entry',
			'greyd_popup',
			'tp_posttypes',
			'greyd_posttypes',
			'dynamic_template',
			'meinposttype'
		);

		if (class_exists('woocommerce')) {
			$include['product'] = 'product';
		}

		$editable_posttypes = array_unique(array_diff($include, $exclude));

		// get the title/label of every editable post type
		foreach ($editable_posttypes as $posttype) {
			if ( $post_type_obj = get_post_type_object($posttype) ) {
				$editable_posttypes[$posttype] = $post_type_obj->label;
			}
		}
		// debug($editable_posttypes);
		return $editable_posttypes;
	}

	/**
	 * Check if post is an edited posttype or not.
	 * 
	 * @param string|WP_Post $string_or_post
	 * @return bool
	 */
	public static function is_edited_posttype( $string_or_post, $property='post_title' ) {
		if ( is_object($string_or_post) ) {
			$slug = isset($string_or_post->$property) ? strval($string_or_post->$property) : strval($string_or_post->post_title);
		}
		else $slug = strval($string_or_post);
		return isset(self::get_editable_posttypes()[$slug]);
	}


	/**
	 * Get posttype settings.
	 * 
	 * @param int $post_id	WP_Post ID.
	 * @return array
	 */
	public static function get_posttype_settings( $post_id ) {
		$meta = get_post_meta($post_id, Admin::OPTION, true);
		// make sure nested fields, supports, etc are all assoc arrays
		if ( !empty($meta) ) {
			$meta = json_decode(json_encode($meta), true);
		}
		else {
			$meta = array();
		}
		return $meta;
	}

	/**
	 * Update posttype settings.
	 * 
	 * @param int $post_id	WP_Post ID.
	 * @param array $settings
	 */
	public static function update_posttype_settings( $post_id, $settings ) {
		update_post_meta($post_id, Admin::OPTION, $settings);
	}


	/**
	 * Retrieve dynamic field values.
	 * 
	 * @param WP_Post|int $post WP_Post object or ID.
	 * @param array $args       Additional arguments
	 *      @property bool rawurlencode         Default: true
	 *      @property bool resolve_file_ids     Default: false
	 *      @property bool raw_date             Default: false
	 * 
	 * @return array
	 */
	public static function get_dynamic_values( $post, $args=array() ) {

		$dynamic_values = array();

		if ( is_object($post) && isset($post->ID) && !empty($post->ID) ) {

			// use cached fields
			if ( isset($post->dynamic_fields) ) {
				return (array) $post->dynamic_fields;
			}
		}
		else {
			$post = get_post( $post );
		}

		if ( empty($post->ID) ) return $dynamic_values;

		$posttype = self::get_dynamic_posttype_by_slug( $post->post_type );
		if ( !$posttype) return $dynamic_values;

		$fields = (array) $posttype["fields"];
		if ( count($fields) < 1 ) return $dynamic_values;

		$values = self::get_dynamic_meta($post->ID);

		$args = wp_parse_args( $args, array(
			'rawurlencode' => true,
			'resolve_file_ids' => false,
			/**
			 * option to not convert date values to human readable format
			 * @since 2.1
			 */
			'raw_date' => false,
		) );

		foreach ($fields as $field) {

			$field = (array) $field;

			$type   = isset($field['type']) ? trim($field['type']) : "text";
			$value  = isset($field['default']) ? trim($field['default']) : "";
			$name   = isset($field['name']) ? trim($field['name']) : "";

			if ( empty($name) || $type == 'headline' || $type == 'descr' || $type == 'hr') continue;

			if ( !empty($name) && isset($values[$name]) ) {
				$value = $values[$name];
			}

			if ( !empty($value) ) {

				switch ( $type ) {
					case 'date':
						if ( !$args['raw_date'] ) {
							$value = mysql2date(get_option('date_format'), $value);
						}
						break;
					case 'time':
						if ( !$args['raw_date'] ) {
							$value = mysql2date(get_option('time_format'), $value);
						}
						break;
					case 'datetime-local':
						if ( !$args['raw_date'] ) {
							$value = mysql2date(get_option('links_updated_date_format'), $value);
						}
						break;
					case 'url':
						if ( $args['rawurlencode'] ) {
							$value = rawurlencode($value);
						}
						break;
					case 'file':
						$file_url = wp_get_attachment_url( $value );

						// if the argument 'resolve_file_ids' is true, use the url as the value.
						if ( $args['resolve_file_ids'] ) {
							$value = $file_url;
						}

						if ( $args['rawurlencode'] ) {
							$file_url = rawurlencode($file_url);
						}

						$dynamic_values[ $name . '-link' ] = $file_url;

						break;
					case 'dropdown':
					case 'radio':
						if ( !$args['raw_date'] ) {
							// use label instead of value
							// not used for condition matching when 'raw_date' is set
							$options = isset($field['options']) ? explode(',', $field['options']) : array();
							foreach ($options as $option) {
								if ( empty(trim($option)) ) continue;
								if ( strpos( $option, '=' ) === false ) continue;

								$exploded       = explode('=', $option);
								$option_name    = trim($exploded[0]);
								$option_value   = count($exploded) > 1 ? trim($exploded[1]) : $option_name;

								if ( $option_name == $value ) {
									$value = $option_value;
									break;
								}
							}
						}
						break;
					/* all text values are now parsed and rendered in Greyd\Dynamic\Render_Blocks\::render_dynamic_tag */
					// case 'textarea':
					// 	$value = nl2br( $value );
					// 	break;
				}
			}
			$dynamic_values[ $name ] = apply_filters( 'greyd_posttypes_dynamic_value', $value, $name, $type, $post );
		}

		return $dynamic_values;
	}

	/**
	 * Get (and repair) metadata for posttype.
	 * 
	 * @param int $post_id	WP_Post ID.
	 * @return array
	 */
	public static function get_dynamic_meta( $post_id ) {

		$values = get_post_meta($post_id, Admin::META, true);
		if (empty($values)) {
			// debug("empty");
			global $wpdb;
			$m = $wpdb->get_results(
				"SELECT meta_value 
				   FROM {$wpdb->postmeta} 
				  WHERE post_id = {$post_id} AND meta_key = '".Admin::META."'", OBJECT );
			// debug($m[0]->meta_value, true);
			if( is_array($m) && isset($m[0]) && !unserialize($m[0]->meta_value) ) {
				// debug("corrupt");
				preg_match_all('/s:([0-9]+):"(.*?)"(?=;)/su', $m[0]->meta_value, $r);
				// debug($r);
				$values = array();
				if (is_array($r) && count($r[2]) > 0) {
					$keys = array();
					$vals = array();
					foreach ($r[2] as $i => $string) {
						if ($i % 2 == 0) $keys[] = $string; 
						else $vals[] = $string; 
					}
					for ($i=0; $i<count($keys); $i++) {
						$values[$keys[$i]] = $vals[$i];
					}
				}
				// debug($values);
				if (is_admin()) {
					echo '<div class="notice notice-info">
							<p>'.__("Corrupt post meta data found and fixed.", 'greyd_hub').'</p>
						</div>';
					// debug("corrupt metadata found");
					// debug($values);
					// $result = serialize($values);
					// debug($result);
					update_post_meta($post_id, Admin::META, $values);
				}
			}
		}
		return (array) $values;
	}

	/**
	 * Just save the meta option.
	 * 
	 * @param int $post_id	WP_Post ID.
	 * @param array $settings
	 */
	public static function update_dynamic_meta( $post_id, $settings ) {
		return update_post_meta($post_id, Admin::META, $settings);
	}

	/**
	 * Get all registered taxonomies from a dynamic posttype.
	 * 
	 * @param string $slug  Name of the posttype.
	 * @return array
	 */
	public static function get_dynamic_taxonomies( $slug ) {
		$taxonomies = array();
		$posttype   = self::get_dynamic_posttype_by_slug( $slug );
		if ( $posttype ) {

			// categories
			if ( isset($posttype['categories']) && $posttype['categories'] ) {
				$tax_name = $slug.'_category';
				$taxonomies[ $tax_name ] = array(
					'hierarchical' => true,
				);
			}

			// tags
			if ( isset($posttype['tags']) && $posttype['tags'] ) {
				$tax_name = $slug.'_tag';
				$taxonomies[ $tax_name ] = array(
					'hierarchical' => false,
				);
			}

			// custom taxonomies
			if ( isset($posttype['custom_taxonomies']) && !empty($posttype['custom_taxonomies']) ) {
				foreach( $posttype['custom_taxonomies'] as $custom_taxonomy ) {
					if ( is_object($custom_taxonomy) ) $custom_taxonomy = (array) $custom_taxonomy;
					$tax_name = $slug.'_'.$custom_taxonomy['slug'];
					$taxonomies[ $tax_name ] = array(
						'hierarchical' => isset($custom_taxonomy['hierarchical']) && $custom_taxonomy['hierarchical'] === 'hierarchical',
					);
				}
			}
		}

		// global taxonomies
		$global_taxonomies = self::get_global_taxonomies( $slug );
		if ( !empty($global_taxonomies) ) {
			foreach ($global_taxonomies as $tax_id => $tax) {

				$tax_name = $tax['slug'];

				$taxonomies[ $tax_name ] = array(
					'hierarchical' => isset($tax['arguments']) && isset($tax['arguments']['hierarchical']) && $tax['arguments']['hierarchical'],
				);
			}
		}

		return $taxonomies;
	}


	/**
	 * Convert a string into a clean slug.
	 * Replaces all chars except for letters, numbers, dash and underscore.
	 * 
	 * @param string $string    String to modify.
	 * @param bool $cut         Whether to cut the string after 20 characters.
	 * 
	 * @return string           Modified string.
	 */
	public static function get_clean_slug($string, $cut=false) {
		$string = preg_replace('/[^A-Za-z0-9_-]/', '', $string);
		$string = strtolower($string);
		if ($cut  && strlen($string) > 20) $string = substr($string, 0, 20);
		return $string;
	}

	/**
	 * Convert a string into a nice posttype slug.
	 * 
	 * @since 1.1   New posttypes like 'Häuser und Gebäude' now use the
	 *              slug 'haeuser-und-gebaeude' instead of 'huserundgebude'
	 * 
	 * @param string $string    String to modify.
	 * @param bool $cut         Whether to cut the string after 20 characters.
	 * 
	 * @return string           Modified string.
	 */
	public static function get_nice_slug($string, $cut=false) {
		$string = sanitize_title($string);
		if ($cut  && strlen($string) > 20) $string = substr($string, 0, 20);
		return $string;
	}


	/*
	====================================================================================
		Render Stuff
	====================================================================================
	*/

	public static function render_table_row($atts=[], $echo=true) {

		$title      = isset($atts["title"]) ? "<h3>".$atts["title"]."</h3>" : "";
		$desc       = isset($atts["desc"]) ? $atts["desc"] : "";
		$content    = isset($atts["content"]) ? $atts["content"] : "";
		$id         = isset($atts["id"]) ? ' id="'.$atts["id"].'"' : '';
		$classes    = isset($atts["classes"]) ? ' class="'.(is_array($atts["classes"]) ? implode(' ', $atts["classes"]) : $atts["classes"]).'"' : '';

		ob_start();
		echo '<tr'.$id.$classes.'>
				<th>
					'.$title.'
					'.$desc.'
				</th>
				<td>
					'.$content.'
				</td>
			</tr>';
		$return = ob_get_contents();
		ob_end_clean();
		if ($echo) echo $return;
		else return $return;
	}

	public static function render_list_as_options($options=array(), $default='', $echo=true) {
		$open = false;
		ob_start();
		foreach ( (array) $options as $key => $val) {
			if (strpos($key, "_group") === 0) {
				if ($open) echo "</optgroup>";
				echo "<optgroup label='".$val."'>";
				$open = true;
			} else {
				$selected = $default == strval($key) ? 'selected' : '';
				echo "<option value='$key' $selected>$val</option>";
			}
		}
		if ($open) echo "</optgroup>";

		$return = ob_get_contents();
		ob_end_clean();
		if ($echo) echo $return;
		else return $return;
	}

	public static function render_list_as_checkboxes($options=array(), $defaults=array(), $echo=true, $pre="", $after="") {

		// cast parameters
		$defaults = (array) $defaults;

		ob_start();
		foreach ( (array) $options as $value => $label_or_args ) {

			if ( is_array( $label_or_args ) ) {
				$label       = isset( $label_or_args['label'] ) ? $label_or_args['label'] : '';
				$description = isset( $label_or_args['description'] ) ? $label_or_args['description'] : '';
			} else {
				$label       = esc_attr( $label_or_args );
				$description = '';
			}

			$args = array(
				'name' => $pre.$value.$after,
				'value' => $value,
				'label' => $label,
				'checked' => isset($defaults[$value]) && $defaults[$value] == $value ? true : false,
				'description' => $description
			);

			echo self::render_checkbox( $args );
		}
		$return = ob_get_contents();
		ob_end_clean();
		if ( $echo ) echo $return;
		return $return;
	}

	/**
	 * Render a single checkbox control.
	 * 
	 * @param array $args Arguments for the checkbox.
	 * 
	 * @return string
	 */
	public static function render_checkbox( array $args ) {

		if ( !isset( $args['name'] ) || !isset( $args['value'] ) ) {
			return "";
		}

		$return = sprintf(
			'<label for="%1$s" style="white-space:nowrap;display:flex;margin-bottom:0.5em"><input type="checkbox" name="%1$s" id="%1$s" value="%2$s" %4$s %5$s autocomplete="off"><span>%3$s%6$s</span></label>',
			// name
			$args['name'],
			// value
			$args['value'],
			// label
			isset($args['label']) ? $args['label'] : $args['name'],
			// checked
			isset($args['checked']) && $args['checked'] ? 'checked="checked"' : '',
			// onclick
			isset($args['onclick']) && !empty($args['onclick']) ? 'onclick="'.$args['onclick'].'"' : '',
			// description
			isset($args['description']) && !empty($args['description']) ? '<br><small>'.$args['description'].'</small>' : ''
		);

		if ( isset($args['echo']) && $args['echo'] ) {
			echo $return;
		}
		return $return;
	}

	/**
	 * Render a submit button
	 */
	public static function render_submit($break_meta=true, $text=null) {

		// render hidden action
		echo '<input type="hidden" name="meta_action" value="wp_backend" />';

		if ($break_meta) echo '</div></div></div><div><div><div>';

		// don't render on gutenberg pages
		if ( Helper::is_gutenberg_page() ) return;

		// render submit button
		if (empty($text)) $text = __("Save changes", 'greyd_hub');

		if ( get_current_screen()->action !== "add") {
			submit_button( $text, 'primary large', 'save', true , ["id" => "submit"]);
		}
	}

	public static function render_filepicker($attachment_id, $name, $placeholder="", $echo=true) {

		if (empty($name)) return false;

		$ph     = empty(trim($placeholder)) ? __("Insert file", 'greyd_hub') : $placeholder;
		$thumb  = wp_get_attachment_image_src( $attachment_id, "thumbnail" );
		$thumb  = $thumb && isset( $thumb[0] ) ? $thumb[0] : "";
		$src    = wp_get_attachment_url( $attachment_id ) ?? "";
		$return = "<div class='custom_filepicker'>
						<img class='image_preview' src='{$thumb}'>
						<p class='image_url'>{$src}</p>
						<span class='button add'>{$ph}</span>
						<span class='button button-ghost remove ".( empty($thumb) ? 'hidden' : '' )."'>".__("Remove", 'greyd_hub')."</span>
						<input type='hidden' class='store_img' name='{$name}' value='{$attachment_id}' autocomplete='off'>
					</div>";

		if ($echo) echo $return;
		return $return;
	}

	public static function render_linkpicker($value, $name, $placeholder, $required='', $echo=true) {
		if (empty($name)) return false;
		$placeholder = empty(trim($placeholder)) ? __("Select URL", 'greyd_hub') : $placeholder;
		$return = '<div class="custom_wp_link">'.
					'<b>'.$value.'</b>'.
					'<input type="text" name="'.$name.'" id="'.$name.'" value="'.$value.'" style="opacity:0;position:absolute;z-index:-1;" '.$required.' autocomplete="off"/>'.
					'<span class="button add">'.$placeholder.'</span>'.
					'<span class="button remove button-ghost '.( empty($value) ? "hidden" : "" ).'" style="margin-left:10px;">'.__('Remove URL', 'greyd_hub').'</span>'.
				'</div>';

		if ($echo) echo $return;
		return $return;
	}

	public static function get_dashicons() {
		$return = [
			'admin-appearance',
			'admin-collapse',
			'admin-comments',
			'admin-customizer',
			'admin-generic',
			'admin-home',
			'admin-links',
			'admin-media',
			'admin-multisite',
			'admin-network',
			'admin-page',
			'admin-plugins',
			'admin-post',
			'admin-settings',
			'admin-site',
			'admin-site-alt',
			'admin-site-alt2',
			'admin-site-alt3',
			'admin-tools',
			'admin-users',
			'dashboard',
			'welcome-write-blog',
			'welcome-edit-page',
			'welcome-add-page',
			'welcome-view-site',
			'welcome-widgets-menus',
			'welcome-comments',
			'welcome-learn-more',
			'format-aside',
			'format-audio',
			'format-chat',
			'format-gallery',
			'format-image',
			'format-links',
			'format-quote',
			'format-standard',
			'format-status',
			'format-video',
			'media-archive',
			'media-audio',
			'media-code',
			'media-default',
			'media-document',
			'media-interactive',
			'media-spreadsheet',
			'media-text',
			'media-video',
			'playlist-audio',
			'playlist-video',
			'controls-play',
			'controls-pause',
			'controls-forward',
			'controls-skipforward',
			'controls-back',
			'controls-skipback',
			'controls-repeat',
			'controls-volumeon',
			'controls-volumeoff',
			'image-crop',
			'image-filter',
			'image-flip-horizontal',
			'image-flip-vertical',
			'image-rotate',
			'image-rotate-left',
			'image-rotate-right',
			'undo',
			'redo',
			'database',
			'database-add',
			'database-export',
			'database-import',
			'database-remove',
			'database-view',
			'align-full-width',
			'align-pull-left',
			'align-pull-right',
			'align-wide',
			'block-default',
			'button',
			'cloud-saved',
			'cloud-upload',
			'columns',
			'cover-image',
			'ellipsis',
			'embed-audio',
			'embed-generic',
			'embed-photo',
			'embed-post',
			'embed-video',
			'exit',
			'heading',
			'html',
			'info-outline',
			'insert',
			'insert-after',
			'insert-before',
			'remove',
			'saved',
			'shortcode',
			'table-col-after',
			'table-col-before',
			'table-col-delete',
			'table-row-after',
			'table-row-before',
			'table-row-delete',
			'editor-bold',
			'editor-break',
			'editor-code',
			'editor-contract',
			'editor-customchar',
			'editor-distractionfree',
			'editor-expand',
			'editor-help',
			'editor-indent',
			'editor-insertmore',
			'editor-italic',
			'editor-justify',
			'editor-kitchensink',
			'editor-ltr',
			'editor-ol',
			'editor-ol-rtl',
			'editor-outdent',
			'editor-paragraph',
			'editor-paste-text',
			'editor-paste-word',
			'editor-quote',
			'editor-removeformatting',
			'editor-rtl',
			'editor-spellcheck',
			'editor-strikethrough',
			'editor-table',
			'editor-textcolor',
			'editor-ul',
			'editor-underline',
			'editor-unlink',
			'editor-video',
			'align-center',
			'align-left',
			'align-none',
			'align-right',
			'calendar',
			'calendar-alt',
			'edit',
			'hidden',
			'lock',
			'post-status',
			'post-trash',
			'sticky',
			'trash',
			'unlock',
			'visibility',
			'arrow-down',
			'arrow-down-alt',
			'arrow-down-alt2',
			'arrow-left',
			'arrow-left-alt',
			'arrow-left-alt2',
			'arrow-right',
			'arrow-right-alt',
			'arrow-right-alt2',
			'arrow-up',
			'arrow-up-alt',
			'arrow-up-alt2',
			'arrow-up-duplicate',
			'excerpt-view',
			'external',
			'grid-view',
			'leftright',
			'list-view',
			'move',
			'randomize',
			'sort',
			'amazon',
			'email',
			'email-alt',
			'email-alt2',
			'facebook',
			'facebook-alt',
			'google',
			'googleplus',
			'instagram',
			'linkedin',
			'networking',
			'pinterest',
			'podio',
			'reddit',
			'rss',
			'share',
			'share1',
			'share-alt',
			'share-alt2',
			'spotify',
			'twitch',
			'twitter',
			'twitter-alt',
			'whatsapp',
			'xing',
			'youtube',
			'art',
			'code-standards',
			'hammer',
			'heart',
			'megaphone',
			'migrate',
			'nametag',
			'performance',
			'rest-api',
			'schedule',
			'tide',
			'tickets',
			'universal-access',
			'universal-access-alt',
			'buddicons-activity',
			'buddicons-bbpress-logo',
			'buddicons-buddypress-logo',
			'buddicons-community',
			'buddicons-forums',
			'buddicons-friends',
			'buddicons-groups',
			'buddicons-pm',
			'buddicons-replies',
			'buddicons-topics',
			'buddicons-tracking',
			'cart',
			'cloud',
			'feedback',
			'info',
			'pressthis',
			'screenoptions',
			'translation',
			'update',
			'update-alt',
			'wordpress',
			'wordpress-alt',
			'category',
			'tag',
			'tagcloud',
			'archive',
			'text',
			'text-page',
			'bell',
			'dismiss',
			'flag',
			'marker',
			'minus',
			'no',
			'no-alt',
			'plus',
			'plus-alt',
			'plus-alt2',
			'star-empty',
			'star-filled',
			'star-half',
			'warning',
			'yes',
			'yes-alt',
			'airplane',
			'album',
			'analytics',
			'awards',
			'backup',
			'bank',
			'beer',
			'building',
			'businessman',
			'businessperson',
			'businesswoman',
			'calculator',
			'car',
			'carrot',
			'clock',
			'clipboard',
			'coffee',
			'color-picker',
			'desktop',
			'download',
			'drumstick',
			'edit-large',
			'edit-page',
			'filter',
			'food',
			'forms',
			'fullscreen-alt',
			'fullscreen-exit-alt',
			'games',
			'groups',
			'hourglass',
			'id',
			'id-alt',
			'index-card',
			'laptop',
			'layout',
			'lightbulb',
			'location',
			'location-alt',
			'lock-duplicate',
			'microphone',
			'money',
			'money-alt',
			'open-folder',
			'paperclip',
			'pdf',
			'pets',
			'phone',
			'portfolio',
			'printer',
			'privacy',
			'products',
			'search',
			'shield',
			'shield-alt',
			'slides',
			'smartphone',
			'smiley',
			'sos',
			'store',
			'superhero',
			'superhero-alt',
			'tablet',
			'testimonial',
			'thumbs-down',
			'thumbs-up',
			'tickets',
			'tickets-alt',
			'upload',
			'vault',
			'video-alt',
			'video-alt2',
			'video-alt3',
			'menu',
			'menu-alt',
			'menu-alt2',
			'menu-alt3',
			'palmtree'
		];
		asort($return);
		return $return;
	}


}
