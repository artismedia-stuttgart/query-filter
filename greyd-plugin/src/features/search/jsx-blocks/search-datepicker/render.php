<?php
namespace greyd\blocks;

	// Fallback ranges array
	$fallbackRanges = array(
		'Today' => array(
			'label' => __('Today', 'greyd_hub'),
			'state' => false
		),
		'Next 7 Days' => array(
			'label' => __('Next 7 days', 'greyd_hub'),
			'state' => false
		),
		'Last 7 Days' => array(
			'label' => __('Last 7 days', 'greyd_hub'),
			'state' => false
		),
		'Next 30 Days' => array(
			'label' => __('Next 30 days', 'greyd_hub'),
			'state' => false
		),
		'Last 30 Days' => array(
			'label' => __('Last 30 days', 'greyd_hub'),
			'state' => false
		),
		'This Week' => array(
			'label' => __('This week', 'greyd_hub'),
			'state' => false
		),
		'Next Week' => array(
			'label' => __('Next week', 'greyd_hub'),
			'state' => false
		),
		'Last Week' => array(
			'label' => __('Last week', 'greyd_hub'),
			'state' => false
		),
		'This Month' => array(
			'label' => __('This month', 'greyd_hub'),
			'state' => false
		),
		'Next Month' => array(
			'label' => __('Next month', 'greyd_hub'),
			'state' => false
		),
		'Last Month' => array(
			'label' => __('Last month', 'greyd_hub'),
			'state' => false
		),
		'This Year' => array(
			'label' => __('This year', 'greyd_hub'),
			'state' => false
		),
		'Next Year' => array(
			'label' => __('Next year', 'greyd_hub'),
			'state' => false
		),
		'Last Year' => array(
			'label' => __('Last year', 'greyd_hub'),
			'state' => false
		)
	);

	// Datepicker attributes
	$mode         = isset($attributes['mode']) ? esc_attr( $attributes['mode'] ) : 'range';
	$date_format  = isset($attributes['dateFormat']) && !empty($attributes['dateFormat']) ? $attributes['dateFormat'] : 'Y-m-d';
	$enable_time  = $mode !== 'range' && isset($attributes['enableTime']) ? $attributes['enableTime'] : 'false';
	$locale       = substr(get_locale(), 0, 2);
	// hard validate to en & de
	if ( $locale !== 'en' && $locale !== 'de' ) {
		$locale = 'en';
	}
	$max_date     = isset($attributes['maxDate']) ? $attributes['maxDate'] : '';
	$min_date     = isset($attributes['minDate']) ? $attributes['minDate'] : '';
	$position     = isset($attributes['position']) ? $attributes['position'] : 'auto left';
	$ranges       = isset($attributes['ranges']) ? $attributes['ranges'] : $fallbackRanges;
	$time_24hr    = isset($attributes['time_24hr']) ? $attributes['time_24hr'] : 'false';
	$week_numbers = isset($attributes['weekNumbers']) ? $attributes['weekNumbers'] : 'false';
	$placeholder  = isset($attributes['placeholder']) ? $attributes['placeholder'] : __( 'Pick a date', 'greyd_hub' );

	// map ranges to only include the ones that are active
	$activeRanges = array();
	foreach ($fallbackRanges as $key => $range) {
		if ( isset($ranges[$key]) && $ranges[$key]['state'] ) {
			$activeRanges[$key] = array(
				'state' => true,
				'label' => $fallbackRanges[$key]['label']
			);
		}
	}
	
	// Filtering attributes
	$filterBy     = isset($attributes['filterBy']) ? $attributes['filterBy'] : 'post_date';
	$label        = isset($attributes['label']) ? $attributes['label'] : '';
	$field        = isset($attributes['field']) ? $attributes['field'] : '';

	// Style attributes
	$datepickerStyles            = isset($attributes['datepickerStyles']) ? $attributes['datepickerStyles'] : array();
	$datepickerActiveStyles      = isset($attributes['datepickerActiveStyles']) ? $attributes['datepickerActiveStyles'] : array();
	$datepickerRangeButtonStyles = isset($attributes['datepickerRangeButtonStyles']) ? $attributes['datepickerRangeButtonStyles'] : array();
	$greydClass                  = isset($attributes['greydClass']) ? $attributes['greydClass'] : '';
	$greydStyles                 = isset($attributes['greydStyles']) ? $attributes['greydStyles'] : array();
	$labelStyles                 = isset($attributes['labelStyles']) ? $attributes['labelStyles'] : array();
	$className                   = isset($attributes['className']) ? $attributes['className'] : '';
	$customStyles                = isset($attributes['custom']) && $attributes['custom'] && isset($attributes['customStyles']) ? $attributes['customStyles'] : array();

	// set initial values on loading
	global $wp;
	$query_vars = $wp->query_vars;
	$inputs = '';
	$id = wp_unique_id( 'datepicker-' );

	// Set hidden filtering inputs
	if ( !empty( $query_vars['post_date'] ) ) {
		$after = isset($query_vars['post_date']['after']) ? $query_vars['post_date']['after'] : '';
		$before = isset($query_vars['post_date']['before']) ? $query_vars['post_date']['before'] : '';

		if ( $after && $before) {
			$inputs = "<input type='hidden' name='post_date[after]' value='$after'>";
			$inputs .= "<input type='hidden' name='post_date[before]' value='$before'>";
		}
	} elseif ( !empty( $query_vars['meta_date'] ) ) {
		$from = isset($query_vars['meta_date']['from']) ? $query_vars['meta_date']['from'] : '';
		$to = isset($query_vars['meta_date']['to']) ? $query_vars['meta_date']['to'] : '';
		$field = isset($query_vars['meta_date']['field']) ? $query_vars['meta_date']['field'] : '';

		if ( $from && $field ) {
			$inputs = "<input type='hidden' name='meta_date[from]' value='$from'>";
			$inputs .= "<input type='hidden' name='meta_date[to]' value='$to'>";
			$inputs .= "<input type='hidden' name='meta_date[field]' value='$field'>";
		}
	} elseif ( !empty( $query_vars['dynamic_meta_date'] ) ) {
		$from = isset($query_vars['dynamic_meta_date']['from']) ? $query_vars['dynamic_meta_date']['from'] : '';
		$to = isset($query_vars['dynamic_meta_date']['to']) ? $query_vars['dynamic_meta_date']['to'] : '';
		$field = isset($query_vars['dynamic_meta_date']['field']) ? $query_vars['dynamic_meta_date']['field'] : '';

		if ( $from && $field) {
			$inputs = "<input type='hidden' name='dynamic_meta_date[from]' value='$from'>";
			$inputs .= "<input type='hidden' name='dynamic_meta_date[to]' value='$to'>";
			$inputs .= "<input type='hidden' name='dynamic_meta_date[field]' value='$field'>";
		}
	}
?>

<div class="input-outer-wrapper <?php echo $greydClass; ?>">
	<?php
		if ( ! empty( $label ) ) {
			echo sprintf( '<div class="label_wrap"><label for="%s">%s</label></div>', $id, $label );
			if ( ! empty( $labelStyles ) ) {
				render::enqueue_custom_style(
					".{$greydClass} label",
					$labelStyles
				);
			}
		}
	?>
	<div class="input-inner-wrapper">
		<input
			class="greyd-datepicker-input<?php echo $className ? ' ' . $className : ''; ?>"
			id="<?php echo $id; ?>"
			type="text"
			data-date-format="<?php echo $date_format; ?>"
			data-enable-time="<?php echo $enable_time; ?>"
			data-locale="<?php echo $locale; ?>"
			data-max-date="<?php echo $max_date; ?>"
			data-min-date="<?php echo $min_date; ?>"
			data-mode="<?php echo $mode; ?>"
			data-position="<?php echo $position; ?>"
			data-ranges='<?php echo json_encode($activeRanges); ?>'
			data-time-24hr="<?php echo $time_24hr; ?>"
			data-week-numbers="<?php echo $week_numbers; ?>"
			data-filter-by="<?php echo $filterBy; ?>"
			data-field='<?php echo $field; ?>'
			placeholder="<?php echo $placeholder; ?>"
			>
		<button class="greyd-datepicker-clear" tabindex="0" type="button" role="button" aria-label="<?php echo __( 'Reset the date', 'greyd_hub' );?>"><span aria-hidden="true">✕</span></button>
		<?php echo $inputs; ?>
		<?php 
			// Render the styles
			if ( ! empty( $datepickerStyles ) ) {
				render::enqueue_custom_style(
					".flatpickr-calendar",
					$datepickerStyles
				);
			}
			if ( isset( $datepickerStyles['background'] ) && ! empty( $datepickerStyles['background'] ) ) {
				render::enqueue_custom_style(
					".flatpickr-calendar.arrowTop:after,
					.flatpickr-calendar.arrowTop:before",
					array(
						'border-bottom-color' => $datepickerStyles['background']
					)
				);
				render::enqueue_custom_style(
					".flatpickr-calendar.arrowBottom:after,
					.flatpickr-calendar.arrowBottom:before",
					array(
						'border-top-color' => $datepickerStyles['background']
					)
				);
			}
			if ( ! empty( $datepickerRangeButtonStyles ) ) {
				render::enqueue_custom_style(
					".flatpickr-calendar .flatpickr-predefined-ranges button",
					$datepickerRangeButtonStyles
				);
			}
			if ( ! empty( $datepickerActiveStyles ) ) {
				render::enqueue_custom_style(
					".flatpickr-day.selected,
					.flatpickr-day.startRange,
					.flatpickr-day.endRange,
					.flatpickr-day.selected.inRange,
					.flatpickr-day.startRange.inRange,
					.flatpickr-day.endRange.inRange,
					.flatpickr-day.selected:focus,
					.flatpickr-day.startRange:focus,
					.flatpickr-day.endRange:focus,
					.flatpickr-day.selected:hover,
					.flatpickr-day.startRange:hover,
					.flatpickr-day.endRange:hover,
					.flatpickr-day.selected.prevMonthDay,
					.flatpickr-day.startRange.prevMonthDay,
					.flatpickr-day.endRange.prevMonthDay,
					.flatpickr-day.selected.nextMonthDay,
					.flatpickr-day.startRange.nextMonthDay,
					.flatpickr-day.endRange.nextMonthDay,
					.flatpickr-calendar .flatpickr-predefined-ranges button.active",
					$datepickerActiveStyles
				);
			}
			if ( ! empty( $customStyles ) ) {
				render::enqueue_custom_style(
					".$greydClass .greyd-datepicker-input",
					$customStyles
				);
			}
		?>
	</div>
</div>
