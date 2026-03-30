<?php
/**
 * ACF Dynamic Tags feature.
 */

namespace Greyd\Posttypes\ACF;

use Greyd\Posttypes\Posttype_Helper;

if ( !defined( 'ABSPATH' ) ) exit;

new Render();
class Render {

	/**
	 * Constructor
	 */
	public function __construct() {

		add_filter( 'greyd_render_dynamic_tag', array( $this, 'render_acf_dynamic_tag' ), 10, 5 );
		add_filter( 'greyd_render_dynamic_tag', array( $this, 'render_acf_dynamic_tag_taxonomy' ), 10, 5 );

		add_filter( 'greyd_get_dynamic_url', array( $this, 'get_acf_dynamic_url' ), 10, 4 );

		add_filter( 'greyd_query_get_dynamic_fields', array( $this, 'get_acf_dynamic_fields' ), 10, 4 );

		add_filter( 'greyd_conditional_content_condition_is', array( $this, 'condition_evaluate_acf_fields' ), 10, 2 );
	}


	/*
	=======================================================================
		Dynamic Tags
	=======================================================================
	*/

	/**
	 * Render a dynamic tag for Advanced Custom Fields (ACF).
	 *
	 * For detailed usage and examples, refer to the README.md file.
	 * Allows customization via the `greyd_acf_dynamic_tag` and `greyd_acf_dynamic_tag_rendered` filters.
	 * 
	 * @filter greyd_render_dynamic_tag
	 *
	 * @param string  $html      HTML content of the parsed tag.
	 * @param string  $name      Name of the dynamic tag
	 * @param string  $params    Dynamic Tag Paras as json string.
	 * @param object  $block     Parsed Block.
	 * @param WP_Post $post      Post object.
	 *
	 * @return string $html
	 */
	public function render_acf_dynamic_tag( $html, $name, $params, $block, $post ) {

		if ( !function_exists( 'get_field' ) ) return $html;

		if ( strpos( $name, 'acf-' ) !== 0 ) return $html;

		$tagName = explode( 'acf-', $name, 2 )[1];

		if ( ! $post ) {
			$post = get_post();
		}

		/**
		 * Filter the HTML content of the parsed tag.
		 * 
		 * @filter greyd_acf_dynamic_tag
		 *
		 * @param string  $html     HTML content of the parsed tag.
		 * @param string  $tagName  Identifier of the dynamic tag, usually the ACF field name.
		 *                          Subfields are in the format groupName[subFieldName].
		 * @param WP_Post $post     Post object.
		 *
		 * @return string $html
		 */
		$html = apply_filters( 'greyd_acf_dynamic_tag', null, $tagName, $post );

		// if no filter is set, render the field using our fallback
		if ( $html === null ) {
			$html = $this->render_field( $tagName, $post );
		}

		if ( !is_string( $html ) ) {

			// if user is logged in, display a message that a filter is missing
			if ( current_user_can( 'edit_posts' ) ) {
				$message = sprintf(
					__(
						'The ACF field %1$s could not be rendered. Use the filter %2$s to render the field.',
						'greyd_hub'
					),
					'<strong>'.$tagName.'</strong>',
					'<strong>greyd_acf_dynamic_tag</strong>'
				);
				$html    = '<div class="message info">'.$message.'</div>';
			} else {
				$html = '<!-- field value (encoded): '.json_encode( $html ).' -->';
			}
		}

		/**
		 * Filter the HTML content of the parsed tag after it has been rendered.
		 * 
		 * @filter greyd_acf_dynamic_tag_rendered
		 *
		 * @param string  $html     HTML content of the parsed tag.
		 * @param string  $tagName  Identifier of the dynamic tag, usually the ACF field name.
		 *                          Subfields are in the format groupName[subFieldName].
		 * @param WP_Post $post     Post object.
		 *
		 * @return string $html
		 */
		$html = apply_filters( 'greyd_acf_dynamic_tag_rendered', $html, $tagName, $post );

		return $html;
	}

	/**
	 * Renders the HTML for an ACF field based on its tag name and the associated post object.
	 * Supports grouped fields and single fields.
	 * 
	 * This function operates as a fallback if no custom filter is set.
	 * 
	 * @param string $tagName   The tag name of the ACF field.
	 *                          Subfields are in the format groupName[subFieldName].
	 * @param WP_Post $post     The post object.
	 * 
	 * @return string $html
	 */
	public function render_field( $tagName, $post ) {

		$field_object = $this->get_field_object( $tagName, $post->ID );
		$html = $this->render_field_object( $field_object );

		/**
		 * Filter the fallback for a field if there has been no custom filter set.
		 * 
		 * @filter greyd_acf_dynamic_tag_fallback
		 *
		 * @param string  $html     HTML content of the parsed tag.
		 * @param string  $tagName  Identifier of the dynamic tag, usually the ACF field name.
		 *                          Subfields are in the format groupName[subFieldName].
		 * @param WP_Post $post     Post object.
		 */
		$html = apply_filters( 'greyd_acf_dynamic_tag_fallback', $html, $tagName, $post );

		if ( is_string( $html ) ) {
			$html = '<!-- Use apply_filters( "greyd_acf_dynamic_tag", $html, $tagName, $post ) to render the field -->'.$html;
		}

		return $html;
	}

	/**
	 * Renders an ACF field object based on its type and value.
	 * 
	 * @param array $field_object The ACF field object.
	 * 
	 * @return string $html
	 */
	public function render_field_object( $field_object ) {
		
		if (
			!$field_object
			|| !isset( $field_object['value'] )
			|| !isset( $field_object['type'] )
		) {
			return '';
		}

		$value = $field_object['value'];
		$type  = $field_object['type'];

		if ( !$type ) {
			return $value ?: '';
		}
		
		if ( !$value ) {
			return '';
		}

		$html = '';
		
		switch ( $type ) {
			
			case 'url':
				$html = '<a href="'.$value.'">'.$value.'</a>';
				break;
				
			case 'link':
				if ( is_array( $value ) ) {
					$html = '<a href="'.$value['url'].'" '.( $value['target'] ? 'target="_blank"' : '' ).'>'.$value['title'].'</a>';
				} else if ( is_string( $value ) ) {
					if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
						$html = '<a href="'.$value.'">'.$value.'</a>';
					} else {
						$html = $value;
					}
				}
				break;

			case 'image':
				if ( is_array( $value ) ) {
					$html = '<img src="'.$value['url'].'" alt="'.$value['alt'].'" />';
				} else if ( is_numeric( $value ) ) {
					$html = wp_get_attachment_image( $value, 'full' );
				} else if ( is_string( $value ) ) {
					if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
						$html = '<img src="'.$value.'" />';
					} else {
						$html = $value;
					}
				}
				break;

			case 'file':
				if ( is_array( $value ) ) {
					$html = '<a href="'.$value['url'].'">'.$value['filename'].'</a>';
				} else if ( is_numeric( $value ) ) {
					$html = wp_get_attachment_link( $value, 'full' );
				} else if ( is_string( $value ) ) {
					if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
						$html = '<a href="'.$value.'">'.__( 'Download', 'greyd_hub' ).'</a>';
					} else {
						$html = $value;
					}
				}
				break;

			case 'select':
			case 'radio':
			case 'checkbox':
			case 'button_group':
				if ( is_array( $value ) ) {
					foreach ( $value as $v ) {
						if ( !empty( $html ) ) {
							$html .= ', ';
						}
						if ( is_array( $v ) ) {
							$html .= $v['label'];
						} else {
							$html .= $v;
						}
					}
				} else {
					$html = $value;
				}
				break;

			case 'true_false':
				if ( $value ) {
					$html = __( 'Yes', 'greyd_hub' );
				} else {
					$html = __( 'No', 'greyd_hub' );
				}
				break;

			case 'color_picker':
				if ( is_array( $value ) ) {
					// red, green, blue, alpha
					$value = 'rgba('.$value['red'].','.$value['green'].','.$value['blue'].','.$value['alpha'].')';
				}
				$html = '<span style="color:'.esc_attr( $value ).';">'.$value.'</span>';
				break;

			case 'icon_picker':
				if ( is_array( $value ) ) {
					if ( !isset( $value['type'] ) ) {
						$html = $value;
					} else if ( $value['type'] === 'dashicons' ) {
						$html = '<span class="dashicons '.$value['value'].'"></span>';
					} else if ( $value['type'] === 'media_library' ) {
						$html = '<img class="acf-icon" style="display:inline-block;height:1em;" src="'.$value['value']['url'].'" />';
					}
				} else if ( is_string( $value ) ) {
					if ( strpos( $value, 'dashicons-' ) === 0 ) {
						$html = '<span class="dashicons '.$value.'"></span>';
					} else if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
						$html = '<img class="acf-icon" style="display:inline-block;height:1em;" src="'.$value.'" />';
					} else {
						$html = $value;
					}
				}
				break;

			case 'page_link':
				if ( is_array( $value ) ) {
					$links = array_map(
						function ( $url ) {
							return '<a href="'.$url.'">'.get_the_title( url_to_postid( $url ) ).'</a>';
						},
						$value
					);
					$html  = implode( ', ', $links );
				} else {
					$html = '<a href="'.$value.'">'.get_the_title( url_to_postid( $value ) ).'</a>';
				}
				break;

			case 'taxonomy':
				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$value = array( $value );
					}
					$links = array_map(
						function ( $term ) {
							if ( is_numeric( $term ) ) {
								$term = get_term( $term );
							} else 
							if ( !$term || !is_object( $term ) ) {
								return '';
							}
							return '<a href="'.get_term_link( $term ).'">'.$term->name.'</a>';
						},
						$value
					);
					$html  = implode( ', ', $links );
				} else if ( is_object( $value ) ) {
					$html = '<a href="'.get_term_link( $value ).'">'.$value->name.'</a>';
				}
				break;
				
			case 'post_object':
			case 'relationship':
				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$value = array( $value );
					}
					$links = array_map(
						function ( $item ) {
							if ( is_numeric( $item ) ) {
								$item = get_post( $item );
							}
							return '<a href="'.get_permalink( $item ).'">'.get_the_title( $item ).'</a>';
						},
						$value
					);
					$html  = implode( ', ', $links );
				} else if ( is_object( $value ) ) {
					$html = '<a href="'.get_permalink( $value->ID ).'">'.get_the_title( $value->ID ).'</a>';
				} else if ( is_numeric( $value ) ) {
					$html = '<a href="'.get_permalink( $value ).'">'.get_the_title( $value ).'</a>';
				}
				break;

			case 'user':
				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$value = array( $value );
					}
					$links = array_map(
						function ( $user ) {
							if ( is_numeric( $user ) ) {
								$user = get_user_by( 'ID', $user );
							}
							if ( !$user ) {
								return '';
							}
							if ( is_array( $user ) ) {
								$user = (object) $user;
							}
							return '<a href="'.get_author_posts_url( $user->ID ).'">'.$user->display_name.'</a>';
						},
						$value
					);
					$html  = implode( ', ', $links );
				} else if ( is_object( $value ) ) {
					$html = '<a href="'.get_author_posts_url( $value->ID ).'">'.$value->display_name.'</a>';
				} else if ( is_numeric( $value ) ) {
					$user = get_user_by( 'ID', $value );
					$html = '<a href="'.get_author_posts_url( $user->ID ).'">'.$user->display_name.'</a>';
				}
				break;

			default:
				// debug( $field_object );
				$html = $value;
				break;
		}

		/**
		 * Filter the HTML content of an ACF field object based on its type and value before it is rendered.
		 * 
		 * @filter greyd_acf_render_field_object
		 *
		 * @param string  $html     HTML content of the parsed tag.
		 * @param array $field_object The ACF field object.
		 * 
		 */
		return apply_filters( 'greyd_acf_render_field_object', $html, $field_object );
	}


	/**
	 * Render a custom taxonomy dynamic tag for Advanced Custom Fields (ACF)
	 * when Posttype and Taxonomy are both registered by ACF.
	 * 
	 * @filter greyd_render_dynamic_tag
	 *
	 * @param string  $html      HTML content of the parsed tag.
	 * @param string  $name      Name of the dynamic tag
	 * @param string  $params    Dynamic Tag Paras as json string.
	 * @param object  $block     Parsed Block.
	 * @param WP_Post $post      Post object.
	 *
	 * @return string $html
	 */
	public function render_acf_dynamic_tag_taxonomy( $html, $name, $params, $block, $post ) {

		if ( !class_exists( 'ACF' ) ) return $html;

		if (
			strpos($name, 'taxonomy-') === 0 && empty($html) &&
			$post && isset($post->post_type) &&
			Posttype_Helper::get_dynamic_posttype_by_slug($post->post_type) === false
		) {
			// debug("render taxonomy: ".$name);
			$html = \Greyd\Dynamic\Dynamic_Helper::make_taxonomy_string($post, $params, $name);
		}

		return $html;
	}

	
	/*
	=======================================================================
		Dynamic URLs
	=======================================================================
	*/

	/**
	 * @filter greyd_get_dynamic_url
	 *
	 * @param string  $url       URL.
	 * @param string  $name      Name of the dynamic tag
	 * @param string  $params    Dynamic Tag Paras as json string.
	 * @param object  $block     Parsed Block.
	 * @param WP_Post $post  Post object.
	 *
	 * @return string $url
	 */
	public function get_acf_dynamic_url( $url, $name, $block, $post ) {

		if ( !class_exists( 'ACF' ) ) return $url;

		if ( strpos( $name, 'acf-' ) !== 0 ) return $url;

		$tagName = explode( 'acf-', $name, 2 )[1];

		if ( ! $post ) {
			$post = get_post();
		}

		/**
		 * Filter the URL of an ACF field based on its tag name and the associated post object.
		 * 
		 * @filter greyd_acf_dynamic_url
		 *
		 * @param string  $url      URL.
		 * @param string  $tagName  Identifier of the dynamic tag, usually the ACF field name.
		 *                          Subfields are in the format groupName[subFieldName].
		 * @param WP_Post $post     Post object.
		 *
		 * @return string $url
		 */
		$url = apply_filters( 'greyd_acf_dynamic_url', null, $tagName, $post );

		// if no filter is set, render the field using our fallback
		if ( $url === null ) {
			$url = $this->get_field_url( $tagName, $post );
		}

		if ( !is_string( $url ) ) {
			$url = '';
		}

		return $url;
	}

	/**
	 * Get the URL for an ACF field based on its tag name and the associated post object.
	 * 
	 * Supports grouped fields and single fields.
	 * 
	 * This function operates as a fallback if no custom filter is set.
	 * 
	 * @param string $tagName   The tag name of the ACF field.
	 *                          Subfields are in the format groupName[subFieldName].
	 * @param WP_Post $post     The post object.
	 * 
	 * @return string $url
	 */
	public function get_field_url( $tagName, $post ) {

		$field_object = $this->get_field_object( $tagName, $post->ID );
		$url = $this->get_field_url_by_object( $field_object );

		/**
		 * Filter the fallback for a link URL if there has been no custom filter set.
		 * 
		 * @filter greyd_acf_dynamic_url_fallback
		 *
		 * @param string  $url      URL.
		 * @param string  $tagName  Identifier of the dynamic tag, usually the ACF field name.
		 *                          Subfields are in the format groupName[subFieldName].
		 * @param WP_Post $post     Post object.
		 */
		$url = apply_filters( 'greyd_acf_dynamic_url_fallback', $url, $tagName, $post );

		return $url;
	}

	/**
	 * Get the URL from an ACF field object based on its type and value.
	 * 
	 * @param array $field_object The ACF field object.
	 * 
	 * @return string $url
	 */
	public function get_field_url_by_object( $field_object ) {
		
		if (
			!$field_object
			|| !isset( $field_object['value'] )
			|| !isset( $field_object['type'] )
		) {
			return '';
		}

		$value = $field_object['value'];
		$type  = $field_object['type'];

		if ( !$type ) {
			return $value ?: '';
		}
		
		if ( !$value ) {
			return '';
		}

		$url = '';
		
		switch ( $type ) {
			
			case 'url':
				$url = $value;
				break;
				
			case 'link':
				if ( is_array( $value ) ) {
					$url = $value['url'];
				} else if ( is_string( $value ) ) {
					$url = $value;
				}
				break;

			case 'image':
				if ( is_array( $value ) ) {
					$url = $value['url'];
				} else if ( is_numeric( $value ) ) {
					$url = wp_get_attachment_image_url( $value, 'full' );
				} else if ( is_string( $value ) ) {
					$url = $value;
				}
				break;

			case 'file':
				if ( is_array( $value ) ) {
					$url = $value['url'];
				} else if ( is_numeric( $value ) ) {
					$url = wp_get_attachment_url( $value );
				} else if ( is_string( $value ) ) {
					$url = $value;
				}
				break;

			case 'page_link':
				if ( is_array( $value ) ) {
					$url = $value[0];
				} else {
					$url = $value;
				}
				break;

			case 'taxonomy':
				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$value = array( $value );
					}
					$links = array_map(
						function ( $term ) {
							if ( is_numeric( $term ) ) {
								$term = get_term( $term );
							} else 
							if ( !$term || !is_object( $term ) ) {
								return '';
							}
							return get_term_link( $term );
						},
						$value
					);
					$url  = reset( $links );
				} else if ( is_object( $value ) ) {
					$url = get_term_link( $value );
				}
				break;

			case 'post_object':
			case 'relationship':
				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$value = array( $value );
					}
					$links = array_map(
						function ( $item ) {
							if ( is_numeric( $item ) ) {
								$item = get_post( $item );
							}
							return get_permalink( $item );
						},
						$value
					);
					$url  = reset( $links );
				} else if ( is_object( $value ) ) {
					$url = get_permalink( $value->ID );
				} else if ( is_numeric( $value ) ) {
					$url = get_permalink( $value );
				}
				break;

			case 'user':
				if ( is_array( $value ) ) {
					if ( isset( $value['ID'] ) ) {
						$value = array( $value );
					}
					$links = array_map(
						function ( $user ) {
							if ( is_numeric( $user ) ) {
								$user = get_user_by( 'ID', $user );
							}
							if ( !$user ) {
								return '';
							}
							if ( is_array( $user ) ) {
								$user = (object) $user;
							}
							return get_author_posts_url( $user->ID );
						},
						$value
					);
					$url  = reset( $links );
				} else if ( is_object( $value ) ) {
					$url = get_author_posts_url( $value->ID );
				} else if ( is_numeric( $value ) ) {
					$user = get_user_by( 'ID', $value );
					$url = get_author_posts_url( $user->ID );
				}
				break;

			default:
				// debug( $field_object );
				$url = $value;
				break;

		}

		/**
		 * Filter the URL of an ACF field based on the field object before it is rendered.
		 * 
		 * @filter greyd_acf_get_field_url_by_object
		 *
		 * @param string  $url, $field_object );
		 * @param array $field_object The ACF field object.
		 * 
		 * @return string $url
		 */
		return apply_filters( 'greyd_acf_get_field_url_by_object', $url, $field_object );
	}


	/*
	=======================================================================
		Query (Advanced Filter)
	=======================================================================
	*/

	/**
	 * Get all ACF field values in one array.
	 * @filter greyd_query_get_dynamic_fields
	 * 
	 * @param array $meta_fields
	 * @param WP_Post[] $posts
	 * @param string $posttype
	 * @param array $args
	 * 
	 * @return array $meta_fields
	 */
	public function get_acf_dynamic_fields( $meta_fields, $posts, $posttype, $args ) {

		if ( !class_exists( 'ACF' ) ) return $meta_fields;

		if ($posts) {
			// debug($posttype);
			// debug($posts);
			foreach ($posts as $post) {
				$fields = $this->get_fields( $post->id, $posttype );
				// debug($fields);
				if ( $fields ) {
					$field_values = $this->get_all_field_values( $post->id, $fields );
					$found = false;
					foreach ( $meta_fields as $i => $_meta_fields ) {
						if ( $_meta_fields['post_id'] == $post->id ) {
							$meta_fields[$i] = array_merge(
								$meta_fields[$i],
								$field_values
							);
							$found = true;
							break;
						}
					}
					if ( !$found ) {
						$field_values['post_id'] = $post->id;
						$meta_fields[] = $field_values;
					}
				}
			}
		}
		// debug($meta_fields);

		return $meta_fields;
	}


	/*
	=======================================================================
		Conditional Content
	=======================================================================
	*/

	/**
	 * Re-evaluate 'field' condition with ACF field values.
	 * @filter 'greyd_conditional_content_condition_is'
	 * 
	 * @param any $is	Evaluated "is" state of the condition
	 * @param array $condition
	 * 
	 * @return any $is 
	 */
	public function condition_evaluate_acf_fields($is, $condition) {

		if ( $condition['type'] == 'field' && strpos( $condition['custom'], 'acf-' ) === 0 ) {
			// debug($condition);
				
			global $post;
			$detail = isset( $condition['detail'] ) ? esc_attr( $condition['detail'] ) : '';
			$postid = false;
			// If the posttype is the same as the detail.
			if ( get_post_type( $post ) == $detail ) {
				$postid = $post->ID;
			}
			// If the posttype is not the same as the user-selected posttype, check if it is a "global dynamic tag".
			else {
				$posttype = \Greyd\Posttypes\Posttype_Helper::get_dynamic_posttype_by_slug( $detail );
				if (
					isset($posttype['arguments'])
					&& isset($posttype['arguments']['is_global_dynamic_tag'])
					&& $posttype['arguments']['is_global_dynamic_tag']
				) {
					$recent_posts = get_posts( array(
						'post_type'   => $detail,
						'numberposts' => 1,
						'fields'      => 'ids'
					) );
					if ( $recent_posts && isset($recent_posts[0]) ) {
						$postid = $recent_posts[0]->ID;
					}
				}
			}

			// debug($postid);
			if ( $postid ) {
				$field_name = explode( 'acf-', $condition['custom'], 2 )[1];
				$field_object = $this->get_field_object( $field_name, $postid );
				// debug($field_object);
					
				if ( $field_object && isset( $field_object['value'] ) ) {
					// debug($field_object['value']);
					$is = $field_object['value'];
					if ( is_array($field_object['value']) ) {
						$values = array_filter( array_values($field_object['value']), function($val) { return !is_array($val); } );
						// debug($values);
						if ( $condition['operator'] == 'is' || $condition['operator'] == 'is_not' ) {
							$should = $condition['value'];
							$values = in_array( $should, $values ) ? array($should) : $values;
						}
						$is = implode( ', ', $values );
					}
				}
			}

		}

		return $is;
	}


	/*
	=======================================================================
		Functions
	=======================================================================
	*/

	/**
	 * Get all ACF fields.
	 * 
	 * @param int $post_id		The ID of the Post
	 * @param string $posttype
	 * 
	 * @return array $values	name - value pairs
	 */
	public function get_fields( $post_id, $posttype ) {
		
		$fields = get_fields( $post_id );
		if ( !$fields ) {
			// if no fields are found, the key might be lost (e.g. gc-sync or import/export)
			// we try to get the fields from posttype
			$fields = array();
			foreach ( acf_get_field_groups( array( 'post_type' => $posttype ) ) as $group ) {
				// Check if the field group applies to the specified post type
				foreach ( $group['location'] as $rules ) {
					foreach ( $rules as $rule ) {
						if ( $rule['param'] === 'post_type' && $rule['operator'] === '==' && $rule['value'] === $posttype ) {
							// Get all fields for the group
							$group_fields = acf_get_fields( $group['ID'] );
							$fields = $this->get_group_fields( $post_id, $group_fields );
						}
					}
				}
			}
		}
		return $fields;
	}
	public function get_group_fields( $post_id, $group_fields ) {
		$fields = array();
		if ( $group_fields ) {
			// debug($group_fields);
			foreach ( $group_fields as $field_group ) {
				// get values
				$name = $field_group['name'];
				if ( isset($field_group['sub_fields']) ) {
					$value = $this->get_group_fields( $post_id, $field_group['sub_fields'] );
				}
				else {
					$value = $field_group['value'];
				}
				$fields[$name] = $value;
			}
		}
		return $fields;
	}

	/**
	 * Get all ACF field values recursively.
	 * 
	 * @param array $fields
	 * @param string $prefix
	 * 
	 * @return array $values
	 */
	public function get_all_field_values( $post_id, $fields, $prefix = '' ) {
		$values = array();
		if ( is_array($fields) ) foreach ( $fields as $key => $value ) {
			$slug = empty($prefix) ? 'acf-'.$key : $prefix.'['.$key.']';
			if ( empty($value) ) {
				// if no value is set we try to get it directly
				$name = str_replace( [ '[', ']' ], [ '_', '' ], substr($slug, 4) );
				$value = get_field($name, $post_id);
			}
			if ( is_array($value) ) {
				$values = array_merge( $values, $this->get_all_field_values( $post_id, $value, $slug ) );
				continue;
			}
			$values[$slug] = $value;
		}
		return $values;
	}


	/**
	 * Get field value (field_object)
	 * 
	 * @param string $name	Name of ACF field
	 * @param int $postid	The ID of the Post
	 * 
	 * @return object $field_object
	 */
	public function get_field_object( $name, $postid ) {

		// grouped fields
		if ( strpos( $name, '[' ) > 0 ) {

			// get group name & sub filed name from format: groupName[subFieldName]...[subFieldName]
			$groupName     = substr( $name, 0, strpos( $name, '[' ) );
			$subFieldName = substr( $name, strpos( $name, '[' ) + 1, -1 );
			$subFieldNames = explode( '][', $subFieldName );

			// get field object
			$field_object = $this->get_sub_field( $groupName, $subFieldNames );
			$field_object = reset( $field_object );

			if ( empty($field_object) ) {
				// if no field object is found, the key might be lost (e.g. gc-sync or import/export)
				// we try to get it with acfs maybe function
				$maybe = acf_maybe_get_field( $groupName, $postid, false );
				if ( $maybe && isset($maybe['key']) ) {
					$maybe = get_field_object( $maybe['key'], $postid );
					$maybe_sub = $this->maybe_get_sub_field( $maybe['sub_fields'], $subFieldNames );
					if ( $maybe_sub && isset($maybe_sub['key']) ) {
						// debug($maybe_sub);
						if ( empty($maybe_sub['value']) ) {
							// if no value is set we try to get it directly
							$maybe_sub['value'] = get_field(str_replace( [ '[', ']' ], [ '_', '' ], $name ), $postid);
						}
						$field_object = $maybe_sub;
					}
				}
			}
		}
		// single fields
		else {
			$field_object = get_field_object( $name, $postid );
			if ( empty($field_object) ) {
				// if no field object is found, the key might be lost (e.g. gc-sync or import/export)
				// we try to get it with acfs maybe function
				$maybe = acf_maybe_get_field( $name, $postid, false );
				if ( $maybe && isset($maybe['key']) ) {
					$field_object = get_field_object( $maybe['key'], $postid );
				}
			}
		}
		return $field_object;

	}
	public function maybe_get_sub_field( $sub_fields, $subFieldNames ) {
		$field_object = false;
		$first = array_shift($subFieldNames);
		foreach ( $sub_fields as $subfield ) {
			if ( $subfield['name'] == $first ) {
				// debug($subfieldNames)
				if ( !empty($subFieldNames) && isset($subfield['sub_fields']) ) {
					$field_object = $this->maybe_get_sub_field( $subfield['sub_fields'], $subFieldNames );
				}
				else {
					$field_object = $subfield;
				}
				break;
			}
		}
		return $field_object;
	}

	/**
	 * Get field value of nested subfield in group.
	 * 
	 * @param string $groupName
	 * @param array $subfieldNames
	 * 
	 * @return array $result
	 */
	public function get_sub_field( $groupName, $subFieldNames ) {
		$result = array();
		if ( have_rows( $groupName ) ) {
			while ( have_rows( $groupName ) ) {
				the_row();
				if ( count($subFieldNames) > 1) {
					$first = array_shift($subFieldNames);
					$result = $this->get_sub_field( $first, $subFieldNames );
				}
				else {
					$field_object = get_sub_field_object( $subFieldNames[0] );
					if ( $field_object ) {
						$result[] = $field_object;
						// get only first value
						break;
					}
				}
			}
			reset_rows();
		}
		return $result;
	}

}
