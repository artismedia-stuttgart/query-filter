<?php

$multiselect = isset( $attributes['multiselect'] ) && $attributes['multiselect'] ? 'true' : 'false';
$showCount = isset( $attributes['showCount'] ) ? (bool)$attributes['showCount'] : false;
$inherit     = isset( $attributes['inherit'] ) ? (bool)$attributes['inherit'] : false;
$label       = isset( $attributes['label'] ) ? $attributes['label'] : '';
$placeholder = isset( $attributes['placeholder'] ) ? $attributes['placeholder'] : '';
$layout      = isset( $attributes['layout'] ) ? $attributes['layout'] : '';
$font_size   = isset( $attributes['font_size'] ) ? $attributes['font_size'] : '';
$style       = isset( $attributes['style'] ) ? $attributes['style'] : '';
$css_class   = isset( $attributes['css_class'] ) ? $attributes['css_class'] : '';
$filterBy    = isset( $attributes['filterBy'] ) ? $attributes['filterBy'] : '';
$title       = isset( $attributes['title'] ) ? $attributes['title'] : '';
$posttype    = isset( $attributes['parentPosttype'] ) ? $attributes['parentPosttype'] : '';

// styles
$greydClass   = isset( $attributes['greydClass'] ) ? $attributes['greydClass'] : wp_unique_id( 'filter_' );
$greydStyles  = isset( $attributes['greydStyles'] ) ? (array)$attributes['greydStyles'] : array();
$customStyles = isset( $attributes['customStyles'] ) && isset( $attributes['customStyles'] ) ? (array)$attributes['customStyles'] : array();

 
$live_region_id = wp_unique_id( 'filter_live_' );
$filter_group_id = wp_unique_id( 'filter_group_' );


$filter_description = '';
if ( !empty( $label ) ) {
	$filter_description = $label;
} else if ( $filterBy === 'category_name' ) {
	$filter_description = __( 'Filter by Category', 'greyd_hub' );
} else if ( $filterBy === 'tag' ) {
	$filter_description = __( 'Filter by Tag', 'greyd_hub' );
} else if ( $filterBy === 'post_type' ) {
	$filter_description = __( 'Filter by Post Type', 'greyd_hub' );
} else {
	$filter_description = sprintf( __( 'Filter by %s', 'greyd_hub' ), ucfirst( $filterBy ) );
}

// Add multiselect information
if ( $multiselect === 'true' ) {
	$filter_description .= ' ' . __( '(Multiple selections allowed)', 'greyd_hub' );
}

// debug($wrapper_attributes);

$extraClass = '';
if ( isset( $attributes['className'] ) ) {
	if ( strpos( $attributes['className'], 'is-style-prim' ) !== false ) {
		$extraClass = 'button is-style-prim';
	} else if ( strpos( $attributes['className'], 'is-style-sec' ) !== false ) {
		$extraClass = 'button is-style-sec';
	} else if ( strpos( $attributes['className'], 'is-style-trd' ) !== false ) {
		$extraClass = 'button is-style-trd';
	} else if ( strpos( $attributes['className'], 'is-style-tabs' ) !== false ) {
		$extraClass = 'tabs is-style-tabs';
	} else if ( strpos( $attributes['className'], 'is-style-chips' ) !== false ) {
		$extraClass = 'chips is-style-chips';
	} else {
		$extraClass = '';
	}
}

$wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => $greydClass )
);

global $wp;
$query_vars = $wp->query_vars;

// inherit posttype from query
if ( empty($posttype) && $inherit ) {
	if ( isset( $_GET['post_type'] ) ) {
		$posttype = $_GET['post_type'];
	} else {
		$posttype = get_post_type( get_the_ID() );
	}
}

/**
 * By default, WordPress search supports the URL query parameter 'category_name' for category search.
 * In order to properly support the filter dropdown, we need to change the filterBy value to 'category_name'.
 */
if ( $filterBy === 'categories' || $filterBy === 'category' ) {
	$filterBy = 'category_name';
}

$error_message = '';

$options  = array();
$selected = array();

/**
 * Get options
 */
if ( empty( $posttype ) ) {
	if ( isset( $query_vars['post_type'] ) ) {
		$selected = explode( ',', $query_vars['post_type'] );
	}

	$searchable_posttypes = get_post_types(
		array(
			'exclude_from_search' => false,
		),
		'objects'
	);

	foreach ( $searchable_posttypes as $pt ) {
		$options[ $pt->name ] = $pt->label;
	}
} else {
	$terms = array();
	// get all post ids to filter by global taxonomy
	$ids = get_posts(
		array(
			'post_type' => $posttype,
			'posts_per_page' => -1,
			'fields' => 'ids',
		)
	);

	// post_tag
	if ( $filterBy === 'tag' ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'hide_empty' => true,
				'object_ids' => $ids
			)
		);
	}
	// category
	else if ( $filterBy === 'category' || $filterBy === 'categories' || $filterBy === 'category_name' ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'object_ids' => $ids
			)
		);
	}
	// custom taxonomy
	else {
		$terms = get_terms(
			array(
				'taxonomy'   => $filterBy,
				'hide_empty' => true,
				'object_ids' => $ids
			)
		);
	}

	if ( is_wp_error( $terms ) ) {
		$error_message  = sprintf(
			__( 'Error loading the filter options for %s', 'greyd_hub' ),
			$filterBy
		);
		$error_message .= ': '.$terms->get_error_message();
		$options        = array();
	}

	// map options
	if ( empty( $terms ) ) {
		$options = array();
	} else {
		foreach ( $terms as $term ) {
			if ( is_a( $term, 'WP_Term' ) && isset( $term->name ) && isset( $term->slug ) && $term->count > 0 ) {
				// search ids to get correct count for global taxonomy
				$count = 0;
				foreach ( $ids as $id ) if ( has_term( $term->slug, $term->taxonomy, $id ) ) $count++;
				if ( $count > 0 ) $options[ $term->slug ] = $term->name . ( $showCount ? ' <span class="count">('.$count.')</span>' : '' );
			}
		}
	}

	$selected = $inherit && isset( $query_vars[ $filterBy ] ) ? explode( ',', $query_vars[ $filterBy ] ) : array();

	// look for query var in URL param
	if ( $inherit && empty( $selected ) && isset( $_GET[ $filterBy ] ) ) {
		$selected = explode( ',', $_GET[ $filterBy ] );
	}
}

// fix responsive flex alignment
if ( isset( $greydStyles ) &&
	( isset( $greydStyles['alignItems'] ) ||
	isset( $greydStyles['justifyContent'] ) )
) {
	if ( !isset( $greydStyles['flexDirection'] ) || $greydStyles['flexDirection'] !== 'column' ) {
		$greydStyles['justifyContent'] = $greydStyles['alignItems'];
		unset( $greydStyles['alignItems'] );
	} else if ( $greydStyles['flexDirection'] === 'column' && isset( $greydStyles['justifyContent'] ) ) {
		$greydStyles['alignItems'] = $greydStyles['justifyContent'];
		unset( $greydStyles['justifyContent'] );
	}
}

\greyd\blocks\render::enqueue_custom_style(
	".{$greydClass}",
	$greydStyles
);

\greyd\blocks\render::enqueue_custom_style(
	".{$greydClass} .greyd_filter_button",
	$customStyles,
	array(
		'pseudo_active' => '.is-active',
		'important'     => true,
	)
);

$resetButtons = array( 'before' => '', 'after' => '' );

if (
	isset( $attributes['resetButton'] )
	&& is_array( $attributes['resetButton'] )
	&& isset( $attributes['resetButton']['enabled'] )
	&& $attributes['resetButton']['enabled']
) {
	$resetLabel    = isset( $attributes['resetButton']['label'] ) && !empty( $attributes['resetButton']['label'] ) ? $attributes['resetButton']['label'] : __( "Select All", 'greyd_hub' );
	$resetPosition = isset( $attributes['resetButton']['position'] ) && !empty( $attributes['resetButton']['position'] ) ? $attributes['resetButton']['position'] : 'before';
	$defaultActive = empty($selected) && isset( $attributes['resetButton']['defaultActive'] ) && $attributes['resetButton']['defaultActive'] ? ' is-active' : '';
	$reset_input_id = wp_unique_id( 'filter_reset_' );

	$resetButtons[ $resetPosition ] = "<div class='greyd_filter_button reset-button{$defaultActive}' role='button' tabindex='0' aria-pressed='" . ($defaultActive ? 'true' : 'false') . "' aria-describedby='{$live_region_id}'>".
		"<input type='radio' id='{$reset_input_id}' name='{$filterBy}_group' value='' " . ($defaultActive ? 'checked' : '') . " tabindex='-1' />".
		"<div class='option {$css_class}'></div>".
		"<label class='label' for='{$reset_input_id}'>{$resetLabel}</label>".
	"</div>";
}

?>

<div <?php echo $wrapper_attributes; ?> data-multiselect="<?php echo $multiselect; ?>" role="group" aria-labelledby="<?php echo $filter_group_id; ?>" aria-describedby="<?php echo $live_region_id; ?>">

	<!-- Hidden label for screen readers -->
	<div id="<?php echo $filter_group_id; ?>" class="visually-hidden"><?php echo esc_html( $filter_description ); ?></div>

	<?php if ( !empty( $error_message ) ) : ?>
		<div class="error-message" role="alert" aria-live="polite"><?php echo $error_message; ?></div>
	<?php endif; ?>

	<!-- Live region for announcing filter changes -->
	<div id="<?php echo $live_region_id; ?>" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

	<input type="hidden" name="<?php echo $filterBy; ?>" value="<?php echo implode( ',', $selected ); ?>" />

	<?php echo $resetButtons['before']; ?>

	<?php foreach ( $options as $val => $text ) : 
		$input_id = wp_unique_id( 'filter_' . sanitize_title( $val ) . '_' );
		$is_selected = in_array( $val, $selected );
	?>
		<div class="greyd_filter_button <?php echo $extraClass; ?>" role="button" tabindex="0" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>" aria-describedby="<?php echo $live_region_id; ?>">
			<input type="radio" id="<?php echo $input_id; ?>" name="<?php echo $filterBy; ?>_group" value="<?php echo esc_attr( $val ); ?>" <?php echo $is_selected ? 'checked' : ''; ?> tabindex="-1" />
			<div class="option <?php echo $css_class; ?>"></div>
			<label class="label" for="<?php echo $input_id; ?>"><?php echo $text; ?></label>
		</div>
	<?php endforeach; ?>

	<?php echo $resetButtons['after']; ?>

</div>
