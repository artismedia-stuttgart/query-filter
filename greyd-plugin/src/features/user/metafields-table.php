<?php
/**
 * Displays metafields as interactive table.
 * Skript 'meta-table.js' needed for interactions.
 * 
 * @since 1.5.4
 */
namespace Greyd\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Metafields_Table {

	/**
	 * vars.
	 */
	public $blacklisted_names = array();
	public $types             = array();
	public $h_units           = array();
	public $empty_field       = array();
	// data
	public $name   = '';
	public $fields = array();
	public $locked = false;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( !Manage::wp_up_to_date() ) {
			return;
		}
		
		$this->set_defaults();

		// add overlay contents for copy & paste fields
		add_filter( 'greyd_overlay_contents', array( $this, 'add_overlay_contents' ) );
	}

	/**
	 * Add overlay contents for user_meta wizard.
	 *
	 * @param array $contents
	 * @return array
	 */
	public function add_overlay_contents( $contents ) {

		// copy & paste operations
		$roles_options = array();
		$current_role_name = isset( $_GET['role'] ) ? $_GET['role'] : '';
		foreach ( Manage::get_roles() as $role ) {
			if ( $role['slug'] == $current_role_name || $role['slug'] == 'super' ) continue;
			$roles_options[] = '<option value="'.$role['slug'].'" data-fields=\''.(json_encode( $role['fields'] )).'\'>'.$role['title'].'</option>';
		}
		$roles_option_list = implode( '', $roles_options );
		
		$contents['user_meta'] = array(
			'loading' => array(
				'title'     => __('Please wait', 'greyd_hub'),
				'descr'   => '<span class="replace">'.__('Meta fields are being copied. This can take a while.', 'greyd_hub').'</span>'
			),
			'confirm' => array(
				'title'     => __('Copy fields', 'greyd_hub'),
				'descr'  => sprintf(
					__( 'Are you sure you want to %s?', 'greyd_hub' ),
					'<b class="replace"></b>'
				)
			),
			'decision' => array(
				"title"     => __("Copy meta fields", 'greyd_hub'),
				"descr"     => __("You can copy and paste field configurations from and to other user roles", 'greyd_hub'),
				"content"   => '<div class="inner_content" style="margin-bottom:20px">'.
					// render a select for the $roles_option_list
					'<div>'.
						'<span>'.__('From/to role', 'greyd_hub').'</span><br>'.
						'<select name="copyRole" id="copyRole">'.$roles_option_list.'</select>'.
						'<input type="hidden" name="currentRole" id="currentRole" value="'.$current_role_name.'">'.
					'</div>'.
					// add 2 radio button options with the name 'action'
					'<p>'.__('What do you want to do?', 'greyd_hub').'</p>'.
					'<fieldset>'.
						'<label for="copy-add" data-replace="'.__('Import and add the fields to here from the role:', 'greyd_hub').'">'.
							'<input type="radio" name="copyAction" value="copy-add" id="copy-add" checked>'.
							__('Import and add the fields here', 'greyd_hub').
						'</label>'.
						'<label for="copy-overwrite" data-replace="'.__('Import and overwrite all fields to here from the role:', 'greyd_hub').'">'.
							'<input type="radio" name="copyAction" value="copy-overwrite" id="copy-overwrite">'.
							__('Import and overwrite all fields here', 'greyd_hub').
						'</label>'.
						'<label for="paste-add" data-replace="'.__('Add fields to the role:', 'greyd_hub').'">'.
							'<input type="radio" name="copyAction" value="paste-add" id="paste-add">'.
							__('Add fields to the other role', 'greyd_hub').
						'</label>'.
						'<label for="paste-overwrite" data-replace="'.__('Overwrite all fields to the role:', 'greyd_hub').'">'.
							'<input type="radio" name="copyAction" value="paste-overwrite" id="paste-overwrite">'.
							__('Overwrite all fields to the other role', 'greyd_hub').
						'</label>'.
					'</fieldset>'.
					// add a checkbox for the option to delete old fields
					// '<div class="checkbox">'.
					// 	'<label for="delete_old"><input type="checkbox" name="delete_old" value="1" id="delete_old">'.__('Delete old fields', 'greyd_hub').'</label>'.
					// '</div>'.
				'</div>',
				'button1'   => __('Cancel', 'greyd_hub'),
				'button2'   => __('Copy fields', 'greyd_hub')
			),
			'success' => array(
				"title"     => __("Copied successful", 'greyd_hub'),
				"descr"     => __("Field configurations have successfully been copied.", 'greyd_hub')
			),
			'fail' => array(
				'title'     => __('Copying failed', 'greyd_hub'),
				'descr'     => '<span class="replace">'.__('Field configurations have not been copied.', 'greyd_hub').'</span>'
			)
		);

		return $contents;
	}

	/**
	 * set default values for all vars.
	 * can be overridden by call to prepare_data function.
	 */
	public function set_defaults() {

		$this->name   = 'settings[fields]';
		$this->fields = array();
		$this->locked = false;

		$this->blacklisted_names = array(
			'post-type',
			'post-link',
			'post-url',
			'title',
			'content',
			'excerpt',
			'author',
			'author-email',
			'author-link',
			'date',
			'categories',
			'tags',
			'image',
			'image-link',
			'post-views',
			'site-title',
			'site-url',
			'site-logo',
			'query',
			'filter',
			'category',
			'tag',
			'post-count',
			'posts-per-page',
			'page-num',
			'page-count',
		);
		$this->types             = array(
			'_group_input'  => __( 'Input field', 'greyd_hub' ),
			'text_short'    => _x( 'Text single line, short', 'small', 'greyd_hub' ),
			'text'          => _x( 'Text single line', 'small', 'greyd_hub' ),
			'text_long'     => _x( 'Text single line, long', 'small', 'greyd_hub' ),
			'textarea'      => _x( 'Text multi line', 'small', 'greyd_hub' ),
			'number'        => _x( 'Number', 'small', 'greyd_hub' ),
			'email'         => _x( 'Email', 'small', 'greyd_hub' ),
			'date'          => _x( 'Date', 'small', 'greyd_hub' ),
			'_group_wp'     => __( 'WordPress', 'greyd_hub' ),
			'text_html'     => _x( 'HTML editor', 'small', 'greyd_hub' ),
			'file'          => _x( 'File / media', 'small', 'greyd_hub' ),
			'url'           => _x( 'Link', 'small', 'greyd_hub' ),
			'_group_choose' => __( 'Selection', 'greyd_hub' ),
			'dropdown'      => _x( 'Dropdown', 'small', 'greyd_hub' ),
			'radio'         => _x( 'Radio buttons', 'small', 'greyd_hub' ),
			'_group_layout' => __( 'Backend layout', 'greyd_hub' ),
			'headline'      => _x( 'Headline', 'small', 'greyd_hub' ),
			'descr'         => _x( 'Description', 'small', 'greyd_hub' ),
			'hr'            => _x( 'Separator', 'small', 'greyd_hub' ),
			'space'         => _x( 'Empty space', 'small', 'greyd_hub' ),
		);
		$this->h_units           = array(
			'h1' => _x( 'H1', 'small', 'greyd_hub' ),
			'h2' => _x( 'H2', 'small', 'greyd_hub' ),
			'h3' => _x( 'H3', 'small', 'greyd_hub' ),
			'h4' => _x( 'H4', 'small', 'greyd_hub' ),
			'h5' => _x( 'H5', 'small', 'greyd_hub' ),
			'h6' => _x( 'H6', 'small', 'greyd_hub' ),
		);
		$this->empty_field       = array(
			'name'     => '',
			'label'    => __( 'Empty', 'greyd_hub' ),
			'type'     => 'text',
			'required' => false,
			'h_unit'   => 'h3',
			'hidden'   => true,
		);

	}

	/*
	=======================================================================
		render table to define fields (setup)
	=======================================================================
	*/

	/**
	 * Render the table.
	 * calls prepare_data and render_table functions.
	 *
	 * @param mixed $args
	 */
	public function render( $args ) {

		$this->prepare_data( $args );
		$this->render_table();

	}

	/**
	 * Prepare data from $args.
	 *
	 * @param mixed $args
	 *      required data:
	 *      @property string name
	 *      @property array fields
	 *      @property bool locked
	 *      optional overrides:
	 *      @property array blacklisted_names
	 *      @property array types
	 *      @property array h_units
	 *      @property mixed empty_field
	 */
	public function prepare_data( $args ) {

		// defaults
		$this->set_defaults();
		// overrides
		if ( isset( $args['blacklisted_names'] ) ) {
			$this->blacklisted_names = $args['blacklisted_names'];
		}
		if ( isset( $args['types'] ) ) {
			$this->types = $args['types'];
		}
		if ( isset( $args['h_units'] ) ) {
			$this->h_units = $args['h_units'];
		}
		if ( isset( $args['empty_field'] ) ) {
			$this->empty_field = $args['empty_field'];
		}
		// data
		if ( isset( $args['name'] ) ) {
			$this->name = $args['name'];
		}
		if ( isset( $args['fields'] ) ) {
			$this->fields = $args['fields'];
		}
		if ( isset( $args['locked'] ) ) {
			$this->locked = $args['locked'];
		}
		if ( ! $this->locked ) {
			// add dummy field
			array_push( $this->fields, $this->empty_field );
		}

	}

	/**
	 * Render table.
	 * Before calling, make sure to call prepare_data function.
	 */
	public function render_table() {

		// fields_table
		echo '<div class="greyd_table meta_table fields_table">';

			// thead
			echo '<div class="thead">
					<ul class="tr">
						<li class="th"></li>
						<li class="th">' . __( 'Field', 'greyd_hub' ) . '</li>
						<li class="th">' . __( 'Type', 'greyd_hub' ) . '</li>
						<!-- todo: extra column (position and visibility) -->
						<li class="th">' . __( 'Actions', 'greyd_hub' ) . '</li>
					</ul>
				</div>';

			// tbody
			$no_fields = __( 'No fields added', 'greyd_hub' );
		if ( ( $this->locked && count( $this->fields ) > 0 ) || ( ! $this->locked && count( $this->fields ) > 1 ) ) {
			$no_fields = '';
		}
			echo '<div class="tbody" id="greyd_sortable" data-empty="' . $no_fields . '">';
		foreach ( $this->fields as $i => $field ) {
			$this->render_field( (array) $field, $i );
		}
			echo '</div>'; // end of tbody

			// tfoot
		if ( ! $this->locked ) {
			echo '<div class="tfoot">
				<ul class="tr">
					<li class="td" colspan="2">
						<span class="button" onclick="greyd.metaTable.openWizard()"><span class="dashicons dashicons-admin-page"></span>&nbsp;&nbsp;' . __( 'Copy and paste fields', 'greyd_hub' ) . '</span>
					</li>
					<li class="td" colspan="2">
						<span class="button button-primary add_field"><span class="dashicons dashicons-plus"></span>&nbsp;&nbsp;' . __( 'Add field', 'greyd_hub' ) . '</span>
					</li>
				</ul>
			</div>';
		}

		echo '</div>'; // end of table

	}

	/**
	 * Render single metafield
	 *
	 * @param mixed $field  metafield args
	 * @param int   $index    Index of the metafield
	 */
	public function render_field( $field, $index ) {

		$hidden   = isset( $field['hidden'] ) && $field['hidden'] ? 'hidden' : '';
		$locked   = $this->locked || ( isset( $field['edit'] ) && $field['edit'] === false ) ? true : false;
		$required = isset( $field['required'] ) ? 'required' : '';
		$label    = $field['type'] === 'hr' || $field['type'] === 'space' ? $this->types[ $field['type'] ] : $field['label'];

		// outer row container
		echo '<div class="row_container ' . $hidden . '">';

			echo '<ul class="tr" ' . ( $locked ? 'data-locked="locked"' : '' ) . '>
					<li class="td sortable_handle">
						<span class="dashicons dashicons-menu"></span>
					</li>
					<li class="td">
						<a href="javascript:void(0)" class="edit_field field_label ' . $required . '"><span class="dyn_field_label">' . $label . '</span><span class="required-star" data-hide="type=headline,hr,descr">&nbsp;*</span></a>
					</li>
					<li class="td">
						<span class="dyn_field_type">' . $field['type'] . '</span>
					</li>
					<!-- todo: extra column (position and visibility) -->
					<li class="td">' . ( ! $locked ? '
						<span class="button button-secondary edit_field"><span class="dashicons dashicons-edit resize"></span>&nbsp;' . __( 'Edit', 'greyd_hub' ) . '</span>
						<span class="button button-ghost duplicate_field" title="' . __( 'Duplicate', 'greyd_hub' ) . '"><span class="dashicons dashicons-admin-page resize"></span></span>
						<span class="button button-danger delete_field" title="' . __( 'Delete', 'greyd_hub' ) . '"><span class="dashicons dashicons-trash resize"></span></span>
						' : '<span class="dashicons dashicons-lock"></span>' ) . '
					</li>
				</ul>';

		if ( ! $locked ) {
			// sub container
			$inner_rows = $this->make_table_rows( $field, $index );
			echo '<div class="sub" data-num="' . $index . '">
					<table class="greyd_table meta_table inner_table vertical">';
			foreach ( $inner_rows as $row ) {
				echo $this->render_table_row( $row );
			}
			echo '</table>
				</div>';
		}

		echo '</div>'; // end of row container

	}

	/**
	 * Make title and content for each field setting.
	 *
	 * @param mixed $field  metafield args
	 * @param int   $index    Index of the metafield
	 * @return array $rows
	 */
	public function make_table_rows( $field, $index ) {

		$name                    = $this->name . '[' . $index . ']';
		$blacklisted_field_names = implode( ',', $this->blacklisted_names );
		$change_name_alert       = ! isset( $field['hidden'] ) || ! $field['hidden'] ? '<div class="change_name_alert">' . __( "Attention! If you change the name of this field, the authors' previous entries will be lost!", 'greyd_hub' ) . '</div>' : '';
		$blacklisted_name_alert  = '<div class="blacklisted_name_alert">' . __( 'Attention! You have chosen a name used by WordPress. Conflicts can arise.', 'greyd_hub' ) . '</div>'; // Text ändern

		if ( ! isset( $field['label'] ) ) {
			$field['label'] = '';
		}
		if ( ! isset( $field['name'] ) ) {
			$field['name'] = '';
		}
		$rows = array();
		// title
		$rows[] = array(
			'title'   => __( 'Title', 'greyd_hub' ),
			'content' =>
				'<div class="flex input_section">
					<div data-hide="type=hr,space">
						<label class="required">' . __( 'Label', 'greyd_hub' ) . '<span class="required-star">&nbsp;*</span></label><br>
						<input type="text" name="' . $name . '[label]" value="' . $field['label'] . '">
					</div>
					<div data-hide="type=headline,hr,descr,space">
						<label class="required">' . __( 'Unique name', 'greyd_hub' ) . '<span class="required-star">&nbsp;*</span></label><br>
						<input type="text" name="' . $name . '[name]" value="' . $field['name'] . '" data-val="' . $field['name'] . '" data-blacklisted-fields="' . $blacklisted_field_names . '">
						' . $change_name_alert . '
						' . $blacklisted_name_alert . '
					</div>
				</div>',
		);
		// type
		$rows[] = array(
			'title'   => __( 'Field type', 'greyd_hub' ),
			'content' =>
				'<div class="flex input_section">
					<div>
						<label>' . __( 'Type', 'greyd_hub' ) . '</label><br>
						<select name="' . $name . '[type]" class="trigger_cond">'
							. $this->render_list_as_options( $this->types, $field['type'] ) .
						'</select>
					</div>
					<div data-hide="type=headline,hr,descr,space">
						<label>' . __( 'Required field', 'greyd_hub' ) . '</label><br>
						<label><input type="checkbox" name="' . $name . '[required]" ' . ( isset( $field['required'] ) && $field['required'] === 'required' ? 'checked' : '' ) . ' value="required">' . __( 'Yes', 'greyd_hub' ) . '</label>
					</div>
					<div data-show="type=headline">
						<label>' . __( 'Unit', 'greyd_hub' ) . '</label><br>
						<select name="' . $name . '[h_unit]">'
							. $this->render_list_as_options( $this->h_units, ( isset( $field['h_unit'] ) ? $field['h_unit'] : null ) ) .
						'</select>
					</div>
				</div>',
		);
		// support
		$rows[] = array(
			'title'   => __( 'Support', 'greyd_hub' ),
			'desc'    => __( 'Additional backend information for editors', 'greyd_hub' ),
			'content' =>
				'<div data-show="type=dropdown,radio">
					<label>' . __( 'Enter values', 'greyd_hub' ) . '</label><br>
					<textarea name="' . $name . '[options]" rows="5">' . ( isset( $field['options'] ) ? $field['options'] : null ) . '</textarea>
					<i>' . __( 'Separate options with a comma. Value and display can be separated by "=". (Example: "male = Mr, female = Mrs")', 'greyd_hub' ) . '</i>
				</div>
				<br data-show="type=dropdown,radio">
				<div class="flex input_section">
					<div data-hide="type=headline,hr,descr,radio,space,text_html">
						<label>' . __( 'Placeholder', 'greyd_hub' ) . '</label><br>
						<input type="text" name="' . $name . '[placeholder]" value="' . ( isset( $field['placeholder'] ) ? $field['placeholder'] : null ) . '">
					</div>
					<div data-hide="type=headline,hr,descr,space,file">
						<label>' . __( 'Default value', 'greyd_hub' ) . '</label><br>
						<input type="text" name="' . $name . '[default]" value="' . ( isset( $field['default'] ) ? $field['default'] : null ) . '">
					</div>
					<div data-show="type=file" style="flex:1">
						<label>' . __( 'Default value', 'greyd_hub' ) . '</label><br>
						' . $this->filepicker( isset( $field['default'] ) ? $field['default'] : null, $name . '[default_file]' ) . '
					</div>
				</div>
				<br data-show="type=number">
				<div class="flex input_section">
					<div data-show="type=number">
						<label>' . __( 'Minimum value', 'greyd_hub' ) . '</label><br>
						<input type="number" name="' . $name . '[min]" value="' . ( isset( $field['min'] ) ? $field['min'] : null ) . '" placeholder="' . __( 'e.g. 0', 'greyd_hub' ) . '">
					</div>
					<div data-show="type=number">
						<label>' . __( 'Maximum value', 'greyd_hub' ) . '</label><br>
						<input type="number" name="' . $name . '[max]" value="' . ( isset( $field['max'] ) ? $field['max'] : null ) . '" placeholder="' . __( 'e.g. 100', 'greyd_hub' ) . '">
					</div>
					<div data-show="type=number">
						<label>' . __( 'Steps', 'greyd_hub' ) . '</label><br>
						<input type="number" name="' . $name . '[steps]" value="' . ( isset( $field['steps'] ) ? $field['steps'] : null ) . '" placeholder="' . __( 'e.g. 0.1', 'greyd_hub' ) . '" min="0" step="0.01">
					</div>
				</div>
				<br data-hide="type=headline,hr,descr,space">
				<div data-hide="type=space">
					<label>' . __( 'Additional description', 'greyd_hub' ) . '</label><br>
					<textarea name="' . $name . '[description]" rows="4">' . ( isset( $field['description'] ) ? $field['description'] : null ) . '</textarea>
				</div>
				<div data-show="type=space">
					<label>' . __( 'Height (in px or em)', 'greyd_hub' ) . '</label><br>
					<input type="text" name="' . $name . '[height]" value="' . ( isset( $field['height'] ) ? $field['height'] : null ) . '" placeholder="' . __( 'Default: 30px', 'greyd_hub' ) . '">
				</div>'
		);

		return $rows;

	}

	/*
	=======================================================================
		render defined fields (e.g. on profile page)
	=======================================================================
	*/

	/**
	 * Render the metafields.
	 *
	 * @param mixed $args
	 *      @property WP_User $user user object
	 *      @property array fields
	 */
	public function render_meta_fields( $args ) {

		// $this->prepare_data($args);
		if ( isset( $args['user'] ) ) {
			$user = $args['user'];
		}
		if ( isset( $args['fields'] ) ) {
			$this->fields = $args['fields'];
		}

		echo '<table class="form-table" role="presentation">';

		foreach ( $this->fields as $field ) {
			$field = (array) $field;
			if ( ! isset( $field['name'] ) ) {
				$field['name'] = '';
				$value         = null;
			} else {
				$name  = 'greyd_' . $field['name'];
				$value = get_user_meta( $user->ID, $name, true );
			}
			// debug($field);
			$meta_field = $this->render_meta_field( $field, $value );

			echo '<tr class="user-' . $field['name'] . '-wrap">';
			if ( $meta_field['content'] == '' ) {
				echo '<th scope="row" colspan="2">' . $meta_field['head'] . '</th>';
			} else {
				echo '<th scope="row">' . $meta_field['head'] . '</th>';
				echo '<td>' . $meta_field['content'] . '</td>';
			}
			echo '</tr>';
		}

		echo '</table>';

	}

	/**
	 * Render single metafield.
	 *
	 * @param mixed  $field  metafield args
	 * @param string $value Value of the field (default: null)
	 * @return array
	 *      @property string head
	 *      @property string content
	 */
	public function render_meta_field( $field = array(), $value = null ) {
		if ( ! isset( $field ) || ! is_array( $field ) || count( $field ) === 0 ) {
			return false;
		}

		$echo        = true;
		$type        = ! empty( $field['type'] ) ? esc_attr( $field['type'] ) : 'text';
		$name        = $field['name'];
		$label       = ! empty( $field['label'] ) ? esc_attr( $field['label'] ) : null;
		$default     = ! empty( $field['default'] ) ? esc_attr( $field['default'] ) : null;
		$value       = ! empty( $value ) ? $value : $default;
		$required    = isset( $field['required'] ) && $field['required'] === 'required' ? 'required' : '';
		$description = ! empty( $field['description'] ) ? esc_attr( $field['description'] ) : null;
		$locked      = isset( $field['fill'] ) && $field['fill'] === false ? true : false;

		// placeholder
		if ( $type === 'file' ) {
			$placeholder = __( 'Insert file', 'greyd_hub' );
		} elseif ( $type === 'dropdown' ) {
			$placeholder = _x( 'Please select', 'small', 'greyd_hub' );
		} elseif ( $type === 'url' ) {
			$placeholder = __( 'Select URL', 'greyd_hub' );
		} else {
			$placeholder = __( 'Enter here', 'greyd_hub' );
		}
		$placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : $placeholder;

		// make label and content
		$head    = '';
		$content = '';

		if ( $type === 'hr' ) {
			$head = '<hr>';
			if ( ! empty( $description ) ) {
				$head = '<i class="descr">' . $description . '</i>';
			}
		} elseif ( $type === 'space' ) {
			$description = '';
			$height      = ! empty( $field['height'] ) ? esc_attr( $field['height'] ) : '30';
			$height      = strpos( $height, 'px' ) === false || strpos( $height, 'em' ) === false ? $height . 'px' : $height;
			$head        = "<div style='height:" . $height . ";'></div>";
		} elseif ( $type === 'headline' ) {
			$unit = isset( $field['h_unit'] ) ? $field['h_unit'] : 'h3';
			if ( ! empty( $label ) ) {
				$head = '<' . $unit . '>' . $label . '</' . $unit . '>';
			}
			if ( ! empty( $description ) ) {
				$head = '<i class="descr">' . $description . '</i>';
			}
		} elseif ( $type === 'descr' ) {
			if ( ! empty( $label ) ) {
				$head = '<p>' . $label . '<p>';
			}
			if ( ! empty( $description ) ) {
				$head = '<i class="descr">' . $description . '</i>';
			}
		} else {
			if ( empty( $field['name'] ) ) {
				// Helper::show_message( sprintf(__("A unique name has not been defined for field %s.", 'greyd_hub'), $label ) , 'danger');
				return false;
			}
			$value = ( $type === 'textarea' || $type === 'text_html' ) ? htmlspecialchars_decode( $value ) : html_entity_decode( $value );

			// wrapper
			// echo '<div class="input_wrapper" '.( $locked ? 'data-locked="locked"' : '' ).'>';

			// label
			if ( ! empty( $label ) ) {
				$required_star = $required != '' ? '<span class="required-star">&nbsp;*</span>' : '';
				$head          = '<label class="' . $required . '" for="' . $name . '">' . $label . $required_star . '</label>';
			}

			// textarea
			if ( $type === 'textarea' ) {
				$content = '<textarea id="' . $name . '" name="' . $name . '" placeholder="' . $placeholder . '" rows="5" ' . $required . '>' . $value . '</textarea>';
			}
			// file
			elseif ( $type === 'file' ) {
				$content = $this->filepicker( $value, $name, $placeholder, $required );
			}
			// html
			elseif ( $type === 'text_html' ) {
				$content      = wp_editor(
					$value,
					$name,
					$settings = array(
						'editor_height' => 100,
						'media_buttons' => false,
					)
				);
			}
			// link
			elseif ( $type === 'url' ) {
				$content = $this->linkpicker( $value, $name, $placeholder, $required );
			}
			// text
			elseif ( strpos( $type, 'text' ) !== false ) {
				$content = '<input type="text" class="' . $type . '" id="' . $name . '" name="' . $name . '" placeholder="' . $placeholder . '" value="' . $value . '" ' . $required . '>';
			}
			// number
			elseif ( $type === 'number' ) {
				$min     = ! empty( $field['min'] ) ? 'min="' . $field['min'] . '"' : '';
				$max     = ! empty( $field['max'] ) ? 'max="' . $field['max'] . '"' : '';
				$steps   = ! empty( $field['steps'] ) ? 'step="' . $field['steps'] . '"' : '';
				$content = '<input type="number" id="' . $name . '" name="' . $name . '" placeholder="' . $placeholder . '" value="' . $value . '" ' . $required . ' ' . $min . ' ' . $max . ' ' . $steps . '>';
			}
			// dropdown & radio
			elseif ( $type === 'dropdown' || $type === 'radio' ) {
				$options_input = isset( $field['options'] ) ? explode( ',', $field['options'] ) : '';
				if ( empty( $options_input ) ) {
					$content = __( 'No options set', 'greyd_hub' );
				} else {
					$options = array();
					foreach ( (array) $options_input as $option ) {
						$split = explode( '=', $option );
						if ( count( $split ) > 1 ) {
							$val  = $split[0];
							$show = $split[1];
						} else {
							$val = $show = $split[0];
						}
						array_push(
							$options,
							array(
								'value' => trim( $val ),
								'show'  => trim( $show ),
							)
						);
					}
				}
				// debug($options);

				if ( $type === 'dropdown' ) {
					$content  = '<select id="' . $name . '" name="' . $name . '" ' . $required . '>';
					$content .= '<option>' . $placeholder . '</option>';
					foreach ( $options as $option ) {
						$selected = $value === $option['value'] ? 'selected' : '';
						$content .= "<option value='" . $option['value'] . "' " . $selected . '>' . $option['show'] . '</option>';
					}
					$content .= '</select>';
				} else {
					$content = '<fieldset id="' . $name . '" ' . $required . '>';
					foreach ( $options as $option ) {
						$selected = $value === $option['value'] ? 'checked' : '';
						$content .= "<div><label for='" . $option['value'] . "'>" .
										"<input type='radio' name='" . $name . "' id='" . $option['value'] . "' value='" . $option['value'] . "' " . $required . ' ' . $selected . '>' .
										$option['show'] .
									'</label></div>';
					}

					$content .= '</fieldset>';
				}
			}
			// other: email, date ...
			else {
				$content = '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" placeholder="' . $placeholder . '" value="' . $value . '" ' . $required . '>';
			}

			if ( ! empty( $description ) ) {
				$content .= '<i class="descr">' . $description . '</i>';
			}
			// echo '</div>';
		}

		return array(
			'head'    => $head,
			'content' => $content,
		);

	}

	/*
	=======================================================================
		helper
	=======================================================================
	*/

	/**
	 * Render single row
	 *
	 * @param mixed $atts
	 *      @property string title
	 *      @property string desc
	 *      @property string content
	 *      @property string id
	 *      @property string|string[] classes
	 * @return string
	 */
	public function render_table_row( $atts = array() ) {

		$title   = isset( $atts['title'] ) ? '<h3>' . $atts['title'] . '</h3>' : '';
		$desc    = isset( $atts['desc'] ) ? $atts['desc'] : '';
		$content = isset( $atts['content'] ) ? $atts['content'] : '';
		$id      = isset( $atts['id'] ) ? ' id="' . $atts['id'] . '"' : '';
		$classes = isset( $atts['classes'] ) ? ' class="' . ( is_array( $atts['classes'] ) ? implode( ' ', $atts['classes'] ) : $atts['classes'] ) . '"' : '';

		// render row
		ob_start();
		echo '<tr' . $id . $classes . '>
				<th>' . $title . '' . $desc . '</th>
				<td>' . $content . '</td>
			</tr>';

		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}

	/**
	 * Render select options from array
	 *
	 * @param mixed[] $options
	 *      @property string key    Option name
	 *      @property string value  Option title (nice)
	 * @return string
	 */
	public function render_list_as_options( $options = array(), $default = '' ) {

		ob_start();
		$open = false;
		foreach ( (array) $options as $key => $val ) {
			if ( strpos( $key, '_group' ) === 0 ) {
				if ( $open ) {
					echo '</optgroup>';
				}
				echo "<optgroup label='" . $val . "'>";
				$open = true;
			} else {
				$selected = $default == strval( $key ) ? 'selected' : '';
				echo "<option value='" . $key . "' " . $selected . '>' . $val . '</option>';
			}
		}
		if ( $open ) {
			echo '</optgroup>';
		}

		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}

	/**
	 * Render file picker
	 *
	 * @param string $value
	 * @param string $name
	 * @param string $placeholder (optional)
	 * @param string $required (optional)
	 * @return string
	 */
	public function filepicker( $value, $name, $placeholder = '', $required = '' ) {
		if ( empty( $name ) ) {
			return;
		}

		$ph    = empty( trim( $placeholder ) ) ? __( 'Insert file', 'greyd_hub' ) : $placeholder;
		$thumb = wp_get_attachment_image_src( $value, 'thumbnail' );
		$thumb = $thumb && isset( $thumb[0] ) ? $thumb[0] : '';
		$src   = wp_get_attachment_url( $value ) ?? '';

		ob_start();
		echo "<div class='custom_filepicker'>
				<img class='image_preview' src='" . $thumb . "'>
				<p class='image_url'>" . $src . "</p>
				<span class='button add'>" . $ph . "</span>
				<span class='button button-ghost remove " . ( empty( $thumb ) ? 'hidden' : '' ) . "'>" . __( 'Remove', 'greyd_hub' ) . "</span>
				<input type='hidden' class='store_img' name='" . $name . "' value='" . $value . "' " . $required . '>
			</div>';

		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}

	/**
	 * Render icon picker
	 *
	 * @param string $value
	 * @param string $name
	 * @param string $placeholder (optional)
	 * @param string $required (optional)
	 * @return string
	 */
	public function linkpicker( $value, $name, $placeholder = '', $required = '' ) {
		if ( empty( $name ) ) {
			return false;
		}

		ob_start();
		$placeholder = empty( trim( $placeholder ) ) ? __( 'Enter URL', 'greyd_hub' ) : $placeholder;
		// echo '<div class="custom_wp_link">' .
		// 		'<b>' . $value . '</b>' .
		// 		'<input type="text" name="' . $name . '" id="' . $name . '" value="' . $value . '" style="opacity:0;position:absolute;z-index:-1;" ' . $required . '/>' .
		// 		'<span class="button add">' . $placeholder . '</span>' .
		// 		'<span class="button remove button-ghost ' . ( empty( $value ) ? 'hidden' : '' ) . '" style="margin-left:10px;">' . __( 'Remove URL', 'greyd_hub' ) . '</span>' .
		// 	'</div>';
		echo '<input type="url" class="regular-text code" name="' . $name . '" id="' . $name . '" value="' . $value . '" placeholder="' . $placeholder . '" ' . $required . '>';

		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}


}
