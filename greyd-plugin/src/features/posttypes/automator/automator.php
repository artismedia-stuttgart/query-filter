<?php
/**
 * This is a reusable class to let a user setup an automated action.
 * 
 * You can use this class to call your own action hook. The user can then
 * setup the Automator via wp-admin, so that your function is called on
 * pageload (front- or backend) or with scheduled events using wp-cron.
 * 
 * You should create an instance of this class only inside the 'init' hook.
 * 
 * Example:
 * *    $Automator = new \Greyd\Automator([
 * *        'slug'   => 'custom_post_automator',
 * *        'action' => 'update_custom_posts',
 * *        'title'  => 'Update my custom posts',
 * *        'menu'   => 'edit.php?post_type=custom_post',
 * *        'reset'  => 'reset_all_custom_posts',
 * *    ]);
 */
namespace Greyd;

use Greyd\Helper as Helper;

use Greyd\Posttypes\Posttype_Helper;

if ( !defined( 'ABSPATH' ) ) exit;

class Automator {

	/**
	 * Construct the Automator class object.
	 * 
	 * @param array $setup
	 *                TYPE   NAME           DESCRIPTION                                         DEFAULT
	 *      @property string slug           Unique slug of the automator [required]             
	 *      @property string hook           Action hook to be called.                           $slug.'_event'
	 *      @property string option         Option name to save the user settings to.           $slug.'_option'
	 *      @property string timestamp      Option name of the saved timestamp.                 $slug.'_timestamp'
	 *      @property string reset          Optional Action hook for manual resets via backend. 
	 * 
	 *      @property string menu           Slug of the parent menu.                            'greyd_dashboard'
	 *      @property int    position       Priority of the menu item.                          85
	 *      @property string capability     User capability to use the automator.               'manage_options
	 * 
	 *      @property string title          Title of the automator page.                        'Automator'
	 *      @property string menu_title     Title of the menu item.                             'Automator'
	 *      @property string update_button  Text of the manual update button                    'jetzt aktualisieren'
	 *      @property string reset_button   Text of the manual reset button                     'zurücksetzen'
	 *      @property string textdomain     Register a textdomain.                              'greyd_hub'
	 */
	public function __construct( $setup ) {

		$this->setup = $setup;

		// init
		add_action( 'init', array($this, 'init'), 99 );
		add_action( 'admin_menu', array($this, 'add_menu_item'), 99 );
		add_action( 'admin_init', array($this, 'add_option') );

		// setup events
		add_action( 'init', array($this, 'init_events'), 99 );
		add_filter( 'cron_schedules', array($this, 'add_schedule_intervals') );

		// add events
		add_action( 'admin_init', array($this, 'create_scheduled_events') );

		// trigger events
		add_action( 'wp_footer', array($this, 'trigger_frontend_events'), 99 );
		add_action( 'admin_head', array($this, 'trigger_backend_events') );
		add_action( 'greyd_automator_hourly_event', array($this, 'trigger_event') );
		add_action( 'greyd_automator_daily_event', array($this, 'trigger_event') );
		add_action( 'greyd_automator_weekly_event', array($this, 'trigger_event') );
		add_action( 'greyd_automator_monthly_event', array($this, 'trigger_event') );

		// register ajax request
		add_action( 'wp_ajax_nopriv_trigger_automator_event', array( $this, 'handle_ajax_event' ) );
		add_action( 'wp_ajax_trigger_automator_event', array( $this, 'handle_ajax_event' ) );

		// remove events on plugin deactivation
		add_action( 'greyd_hub_deactivated', array($this, 'remove_scheduled_events') );
	}

	/**
	 * Setup configuration of the automator.
	 * 
	 * @var array
	 */
	public $setup = false;

	/**
	 * Whether the setup is incomplete. This prevents errors.
	 * 
	 * @var bool
	 */
	public $setup_incomplete = true;

	/**
	 * Contains all the debug logs.
	 * 
	 * @var array
	 */
	public $logs = array();

	/**
	 * Holds the scheduled events
	 * 
	 * @var array
	 */
	public $events = [
		'frontend'  => [],
		'backend'   => [],
		'schedules' => [
			'hourly' => null,
			'daily' => null,
			'weekly' => null,
			'monthly' => null
		]
	];

	/**
	 * Holds option cache
	 */
	public $cache = null;

	/**
	 * Holds whether the action is currently in progress. This prevents errors.
	 */
	public $action_in_progress = false;


	/**
	 * =================================================================
	 *                          INIT BACKEND
	 * =================================================================
	 */

	/**
	 * Init the class
	 */
	public function init() {

		$setup = $this->setup;

		if ( !isset($setup) || empty($setup) || !is_array($setup) || !isset($setup['slug']) || empty($setup['slug']) ) return false;

		// we've got all we need
		$this->setup_incomplete = false;

		// get defaults
		$slug = strval( $setup['slug'] );
		$default_setup = [
			// options
			'slug'          => $slug,
			'hook'          => $slug.'_event',
			'option'        => $slug.'_option',
			'timestamp'     => $slug.'_timestamp',
			'reset'         => '',

			// wp-admin
			'menu'          => 'greyd_dashboard',
			'position'      => 85,
			'capability'    => 'manage_options',

			// wordings
			'title'         => __('Automator', 'greyd_hub'),
			'menu_title'    => __('Automator', 'greyd_hub'),
			'update_button' => __("Update now", 'greyd_hub'),
			'reset_button'  => __("Reset", 'greyd_hub'),
		];

		// combine arrays
		$setup = array_merge( $default_setup, $setup );

		// set class args
		$this->setup = (object) $setup;

		// debugger
		add_action( $slug.'_log', array($this, 'log'), 10, 2 );
		add_action( $slug.'_send_mail', array($this, 'send_debug_email'), 10, 1 );
	}

	/**
	 * Init the menu item
	 */
	public function add_menu_item() {
		if ( $this->setup_incomplete ) return;

		global $submenu;
		if (
			isset( $submenu[ $this->setup->menu ] )
			&& in_array( $this->setup->slug, wp_list_pluck( $submenu[ $this->setup->menu ], 2 ) )
		) {
			// the submenu already exists
			return;
		}

		// Settings
		add_submenu_page(
			$this->setup->menu,  // parent slug
			$this->setup->title, // page title
			$this->setup->menu_title, // menu title
			$this->setup->capability, // capability
			$this->setup->slug, // slug
			[$this, 'render_page'], // function
			intval($this->setup->position) // position
		);
	}

	/**
	 * Render the page
	 * defined via add_menu_item()
	 */
	public function render_page() {

		echo '<div class="wrap settings_wrap">';

		// title
		echo '<h1>'.$this->setup->title.'</h1>';

		// settings form
		echo '<form method="post" action="options.php" class="'.$this->setup->slug.'">';
		settings_fields($this->setup->slug);
		do_settings_sections($this->setup->slug);
		submit_button(__("Save settings", 'greyd_hub'), 'button-primary huge indent-left');
		echo '</form>';

		// hidden forms
		echo "<form id='updateNow' method='post'><input type='hidden' name='update_".$this->setup->slug."' value='true'></form>";
		if ( !empty($this->setup->reset) ) {
			echo "<form id='resetNow' method='post'><input type='hidden' name='reset_".$this->setup->slug."' value='true'></form>";
		}

		// view all scheduled cron jobs
		// source: https://developer.wordpress.org/plugins/cron/simple-testing/
		// debug( _get_cron_array() );

		$this->render_custom_css();
		$this->render_custom_js();

		echo'</div>'; // end of wrap
	}

	/**
	 * Add WP option
	 */
	public function add_option() {
		if ( $this->setup_incomplete ) return;

		// Create Setting
		register_setting(
			$this->setup->slug, // option group
			$this->setup->option, // option name
			array($this, 'validation_callback') // sanitize callback
		);

		// Add Section
		add_settings_section(
			$this->setup->option, // id
			null, // title
			null, // callback
			$this->setup->slug // page slug
		);

		// Add Field
		add_settings_field(
			$this->setup->option, // id
			__("Setup events", 'greyd_hub'), // title
			array($this, 'render_events'), // callback
			$this->setup->slug, // page slug
			$this->setup->option // section
		);

		// Add Field
		add_settings_field(
			$this->setup->slug.'_admin_email', // id
			__("Admin email", 'greyd_hub'), // title
			array($this, 'render_admin_email'), // callback
			$this->setup->slug, // page slug
			$this->setup->option // section
		);
	}


	/**
	 * =================================================================
	 *                          RENDER BACKEND
	 * =================================================================
	 */

	/**
	 * Render the option
	 * called via add_options()
	 */
	public function render_events() {

		// get the option
		$option_name    = $this->setup->option;
		$option         = $this->get_option();
		$events         = $option['events'];
		$events[]       = []; // ghost element for copy function
		$default        = [
			"event" => "none",
			"front" => "any",
			"back"  => "any",
		];

		// selects
		$event_values       = $this->get_select_values("events");
		// $day_values         = $this->get_select_values("days");
		$front_page_values  = $this->get_select_values("front");
		$back_page_values   = $this->get_select_values("back");

		// get scheduled events
		$not_scheduled      = "<span style='opacity:0.5;'>".__("Event is created on save.", 'greyd_hub')."</span>";
		$scheduled_events   = array_merge( array(
			'hourly' => $not_scheduled,
			'daily' => $not_scheduled,
			'weekly' => $not_scheduled,
			'monthly' => $not_scheduled,
		), $this->get_scheduled_events(true) );


		// render
		echo "<div class='greyd_table greyd_automator_table'>
			<div class='thead'>
				<ul class='tr'>
					<li class='th'>".__("Type", 'greyd_hub')."</li>
					<li class='th'>".__("Trigger", 'greyd_hub')."</li>
					<li class='th'></li>
				</ul>
			</div>
			<div class='tbody'>
				<ul class='tr empty'>
					<li class='td'>".__("No events configured", 'greyd_hub')."</li>
				</ul>";

		$i = 1;
		foreach ( (array) $events as $event ) {
			$event = array_merge( $default, $event );

			echo "<div class='row ".( $i === count($events) ? "hidden" : "" )."'>
			<ul class='tr'>
					<li class='td'>
						<select name='".$option_name."[events][]' onchange='greyd_automator.selectChanged(this)'>".
							Posttype_Helper::render_list_as_options($event_values, $event["event"], false).
						"</select>
					</li>
					<li class='td'>";
					foreach ( $scheduled_events as $time => $display ) {
						echo "<div class='times_$time hidden'><small>$display</small></div>";
					}
					echo "<div class='times_frontend hidden'>
							&nbsp;".sprintf(
								__("When %s is loaded", 'greyd_hub'),
								"<select type='time' name='".$option_name."[front][]' style='max-width:150px;'>".
									Posttype_Helper::render_list_as_options($front_page_values, $event["front"], false).
								"</select>"
							)."&nbsp;
						</div>
						<div class='times_backend hidden'>
							&nbsp;".sprintf(
								__("When %s is loaded", 'greyd_hub'),
								" <select type='time' name='".$option_name."[back][]' style='max-width:150px;'>".
									Posttype_Helper::render_list_as_options($back_page_values, $event["back"], false).
								"</select>"
							)."&nbsp;
						</div>
					</li>
					<li class='td'>
						<span class='button small button-danger' onclick='greyd_automator.deleteRow(this)' title='".__("Delete event", 'greyd_hub')."'><span class='dashicons dashicons-trash'></span></span>
					</li>
				</ul>
			</div>";
			$i++;
		}

		echo "</div>
			<div class='tfoot'>
				<ul class='tr'>
					<li class='td' colspan='3'>
						<span class='button' onclick='greyd_automator.addRow()'><span class='dashicons dashicons-plus'></span>&nbsp;&nbsp;".__("Add event", 'greyd_hub')."</span>
					</li>
				</div>
			</div>
		</div>";

		echo "<small>";
			// show timestamp of last update
			if ( $timestamp = get_option( $this->setup->timestamp ) ) {
				echo sprintf( __("Last updated: %s ago", 'greyd_hub'), human_time_diff( current_time('timestamp'), $timestamp ) );
				echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
			};

			// manual update button
			echo "<a href='#' onclick='document.getElementById(\"updateNow\").submit();'>".$this->setup->update_button."</a>";

			// manual reset button
			if ( !empty($this->setup->reset) ) {
				echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
				echo "<a href='#' style='color:darkred' onclick='document.getElementById(\"resetNow\").submit();'>".$this->setup->reset_button."</a>";
			}
		echo "</small>";
	}

	public function render_admin_email() {

		$option = $this->get_option();
		echo "<input type='text' name='{$this->setup->option}[admin_email]' value='{$option['admin_email']}' class='regular-text' placeholder='example@greyd.io'>";
		echo "<br><small>".__("One or more emails (separate with commas) to send reports of the events to.", "greyd_hub")."</small>";
	}

	public function get_select_values($type="") {
		switch ( $type ) {
			case "events":
				return [
					"none" => __("Please select", 'greyd_hub'),
					"_group_times"  => __("Times", 'greyd_hub'),
						"hourly"    =>__("Hourly", 'greyd_hub'),
						"daily" => __("Daily", 'greyd_hub'),
						"weekly" => __("Weekly", 'greyd_hub'),
						"monthly" => __("Monthly", 'greyd_hub'),
					"_group_load"  => __("On page load", 'greyd_hub'),
						"frontend" => __("In the frontend", 'greyd_hub'),
						"backend" => __("In the backend", 'greyd_hub'),
				];
				break;
			case "days":
				return [
					"monday" => __("Monday", 'greyd_hub'),
					"tuesday" => __("Tuesday", 'greyd_hub'),
					"wednesday" => __("Wednesday", 'greyd_hub'),
					"thursday" => __("Thursday", 'greyd_hub'),
					"friday" => __("Friday", 'greyd_hub'),
					"saturday" => __("Saturday", 'greyd_hub'),
					"sunday" => __("Sunday", 'greyd_hub'),
				];
				break;
			case "front":
				$front_pages = [
					"any" => __("Any page", 'greyd_hub')
				];
				$pages = Helper::get_all_posts('page');
				foreach ( $pages as $page ) {
					$front_pages[$page->id] = $page->title;
				}
				return $front_pages;
				break;
			case "back":
				$back_pages =[
					"_group"  => __("General", 'greyd_hub'),
						"any" => __("Any admin page", 'greyd_hub'),
						"this" => __("This page", 'greyd_hub'),
					"_group_types"  => __("Post types", 'greyd_hub'),
				];
				$posttypes = get_post_types( array(), 'objects' );
				$posttypes = array_filter($posttypes, function($k) {
					return $k !== 'revision'
						&& $k !== 'nav_menu_item'
						&& $k !== 'custom_css'
						&& $k !== 'customize_changeset'
						&& $k !== 'oembed_cache'
						&& $k !== 'user_request'
						&& $k !== 'wp_block'
						&& $k !== 'vc_grid_item'
						&& $k !== 'vc4_templates';
				}, ARRAY_FILTER_USE_KEY);
				foreach ( $posttypes as $posttype ) {
					$back_pages[$posttype->name] = $posttype->label;
				}
				return $back_pages;
				break;
		}
	}

	public function render_custom_css() {
		?>
		<style>
			.greyd_table.greyd_automator_table .tr > *:first-child:not(:last-child) {
				flex: 0 1 200px;
			}
			.greyd_table.greyd_automator_table .tr > *:last-child:not(:first-child) {
				flex: 0 0 40px;
				padding-left: 0px;
			}
			.greyd_table.greyd_automator_table .td {
				white-space: nowrap;
			}
			.greyd_table.greyd_automator_table .tfoot .td {
				text-align: right;
			}
			.greyd_table.greyd_automator_table .empty {
				display: none;
			}
			.greyd_table.greyd_automator_table .empty:nth-last-child(2) {
				display: block;
			}
			.greyd_table.greyd_automator_table .empty .td {
				opacity: .5;
				text-align: center;
			}
			@media screen and (min-width: 875px) {
				.form_indent {
					display: -webkit-box;
					display: -ms-flexbox;
					display: flex;
					-webkit-box-pack: justify;
					-ms-flex-pack: justify;
					justify-content: space-between;
					-webkit-box-align: center;
					-ms-flex-align: center;
					align-items: center;
					padding: 0 10px 0 220px;
				}
				.form_indent > p.submit {
					margin: 0;
					padding: 0;
				}
				.form_indent > small {
					text-align: right;
				}
			}
		</style>
		<?php
	}

	public function render_custom_js() {
		?>
		<script type="text/javascript">
			if (typeof(greyd_automator) === 'undefined') {
				var greyd_automator = new function() {

					this.select = ".greyd_automator_table select[name='<?php echo $this->setup->option; ?>[events][]']";

					this.init = function() {
						this.updateSelects();
					}

					this.addRow = function() {
						jQuery(".greyd_automator_table .tbody > *:last-child").clone().appendTo(".greyd_automator_table .tbody");
						jQuery(".greyd_automator_table .tbody > *:nth-last-child(2)").removeClass("hidden");
					}

					this.deleteRow = function(elem) {
						jQuery(elem).closest(".row").remove();
						greyd_automator.updateSelects();
					}

					this.updateSelects = function() {
						jQuery(greyd_automator.select).each(function() {
							greyd_automator.selectChanged(this);
						});
					}

					this.selectChanged = function(elem) {
						var value = elem.value;
						var target = jQuery(elem).parent().next();
						target.find("[class*='times_']:not(.times_"+value+")").addClass("hidden");
						target.find(".times_"+value).removeClass("hidden");

						greyd_automator.updateDisabledOptions();
					}

					this.updateDisabledOptions = function() {
						var values = [];
						jQuery(greyd_automator.select).each(function() {
							values.push(this.value);
						});
						jQuery(greyd_automator.select).each(function() {
							var select = jQuery(this);
							var sel_val = this.value;
							jQuery.each(values, function(i, value){
								select.find("option").each(function(){
									jQuery(this).removeAttr("disabled");
									if ( jQuery(this).attr("value") !== sel_val && values.indexOf( jQuery(this).attr("value") ) >= 0 ) {
										if ( jQuery(this).attr("value") !== "none" && jQuery(this).attr("value") !== "frontend" && jQuery(this).attr("value") !== "backend" )
										jQuery(this).attr("disabled", "disabled");
									}  
								});
							});
						});
					}
				}
				jQuery(function() {
					greyd_automator.init();
				});
			}
		</script>
		<?php
	}


	/**
	 * =================================================================
	 *                          SAVE SETTINGS
	 * =================================================================
	 */

	/**
	 * Get the saved options
	 */
	public function get_option() {

		$default = array(
			'events' => array(),
			'admin_email' => ''
		);
		$saved_value = get_option($this->setup->option);

		if ( $saved_value && is_array($saved_value) ) {
			return array_merge( $default, $saved_value );
		}
		else {
			return $default;
		}
	}

	/**
	 * Validate WP option
	 * defined via add_option() - called when page is submitted
	*/
	public function validation_callback( $option ) {

		// set transient to create events inside create_scheduled_events()
		set_transient( $this->setup->slug.'_option_changed', true );

		// error_log( "raw option:" );
		// var_error_log( $option );

		// default
		$value = array(
			'events' => array(),
			'admin_email' => ''
		);

		// events
		if ( isset($option['events']) && is_array($option['events']) ) {

			foreach( (array) $option['events'] as $i => $event ) {

				/**
				 * already sanitized
				 * @since 2.3.0
				 */
				if ( is_array($event) && isset($event['sanitized']) && $event['sanitized'] === true ) {
					// error_log( "already sanitized" );
					// var_error_log( $event );
					return $option;
				}

				$value['events'][$i] = [
					'event' => $event,
					'time'  => isset($option['times'])  ? esc_attr( $option['times'][$i] )  : "",
					'day'   => isset($option['days'])   ? esc_attr( $option['days'][$i] )   : "",
					'front' => isset($option['front'])  ? esc_attr( $option['front'][$i] )  : "",
					'back'  => isset($option['back'])   ? esc_attr( $option['back'][$i] )   : "",
					'sanitized' => true
				];
			}
			// remove ghost element
			array_pop($value['events']);
		}

		// admin email
		if ( isset($option['admin_email']) ) {
			$value['admin_email'] = esc_attr( $option['admin_email'] );
		}

		// error_log( "value:" );
		// var_error_log( $value );

		// return
		return $value;
	}


	/**
	 * =================================================================
	 *                          SETUP EVENTS
	 * =================================================================
	 */

	/**
	 * Set the class var 'events'
	*/
	public function init_events() {

		if ( $this->setup_incomplete ) return;

		$option = $this->get_option();
		if ( empty($option) || !isset($option['events']) || !is_array($option['events']) || count($option['events']) <= 0 ) return;

		foreach ( $option['events'] as $trigger ) {
			$event = isset( $trigger['event'] ) ? $trigger['event'] : null;
			$detail = null;
			switch ( $event ) {
				case "hourly":
				case "daily":
				case "weekly":
				case "monthly":
					$detail = "";
					break;
				case "frontend":
					$detail = isset( $trigger['front'] ) ? $trigger['front'] : null;
					break;
				case "backend":
					$detail = isset( $trigger['back'] ) ? $trigger['back'] : null;
					break;
			}

			if ( $detail === null ) continue;

			// set events for front- & backend
			if ( $event === 'backend' || $event === 'frontend' ) {
				$this->events[$event][] = $detail;
			}
			// schedule events
			else {
				$this->events['schedules'][$event] = $detail;
			}
		}
	}

	/**
	 *  Trigger the event
	*/
	public function trigger_event( $type="cron" ) {

		// don't call this function multiple times
		if ( $this->action_in_progress ) return false;
		$this->action_in_progress = true;

		// get timestamp
		$last_timestamp = get_option( $this->setup->timestamp );
		$start_time     = current_time('timestamp');

		// trigger the action hook
		do_action( $this->setup->hook, $last_timestamp, $type );

		// save new timestamp
		$end_time = current_time('timestamp');
		update_option( $this->setup->timestamp, $end_time );

		/**
		 * Send the debug email to the admin.
		 * 
		 * @filter 'greyd_automator_email_message'
		 */
		$message = apply_filters( $this->setup->slug.'_mail_message', sprintf(
			"<h3>".__("The automator was successfully completed (duration: %s).", "greyd_hub")."</h3>",
			human_time_diff( $end_time, $start_time )
		) );
		do_action( $this->setup->slug.'_send_mail', $message );

		return $end_time;
	}

	/**
	 *  Trigger the reset event (optional)
	 */
	public function trigger_reset_event() {

		if ( empty($this->setup->reset) || $this->action_in_progress ) return;
		$this->action_in_progress = true;

		// do reset action
		do_action( $this->setup->reset );

		// delete timestamp
		delete_option( $this->setup->timestamp );

	}


	/**
	 * =================================================================
	 *                          FRONTEND EVENTS
	 * =================================================================
	 */

	/**
	 * Trigger frontend events
	 * called on 'wp_head'
	*/
	public function trigger_frontend_events() {
		if ( is_admin() ) return;

		global $post;
		$post_id    = is_object($post) && isset($post->ID) ? $post->ID : 0;
		$events     = isset($this->events['frontend']) ? $this->events['frontend'] : array();
		$ajax_url   = admin_url( 'admin-ajax.php' );

		if ( empty($post_id) || empty($events) ) return;

		$trigger = $this->check_frontend_events($events, $post_id);
		if ( $trigger !== true ) return;

		echo "<script>
			if ( typeof sendAutomatorRequest === 'undefined' ) {
				var sendAutomatorRequest = function( hook, postId ) {
					if ( typeof hook === 'undefined' || typeof jQuery === 'undefined' ) return;

					const data = {
						action: 'trigger_automator_event',
						type: 'frontend',
						hook: hook,
						post_id: typeof postId === 'undefined' ? 0 : postId
					};

					console.log( '%c➔ AutomatorRequest started...', 'color:blue')

					jQuery.post(
						'{$ajax_url}',
						data,
						function ( response ) {
							if ( typeof response === 'string' && response.indexOf( '{\"status\":' ) > -1 ) {
								let responseString = response.split( '{\"status\":' )[1];
								response = JSON.parse( '{\"status\":' + responseString );
							}
							
							if ( response && response.status === 'completed' ) {
								console.log( '%c✅ AutomatorRequest completed', 'color:green' );
							} else {
								console.log( '%c❌ AutomatorRequest failed', 'color:red' );
								console.warn( response );
							}
							return;
						}
					);
				}
			}
			sendAutomatorRequest( '{$this->setup->hook}', {$post_id} );
		</script>";
	}

	/**
	 * Handle the ajax request
	 */
	public function handle_ajax_event() {

		$data = wp_parse_args( $_POST, array(
			'action' => '',
			'type' => '',
			'hook' => '',
			'post_id' => 0,
		) );

		// debug( $this->setup );
		// debug( $data );

		if (
			$data['action'] === 'trigger_automator_event'
			&& $data['type'] === 'frontend'
			&& $data['hook'] === $this->setup->hook
		) {

			$response = array(
				'status' => 'started'
			);

			$end_time = $this->trigger_event( 'frontend' );

			if ( $end_time ) {
				$response = array(
					'status'   => 'completed',
					'end_time' => $end_time
				);
			}
		}

		wp_send_json( $response );
		exit;
	}

	public function check_frontend_events($events, $post_id) {
		foreach ( $events as $event ) {
			if ($event === 'any') return true;
			else if ($event == $post_id) return true;
		}
		return false;
	}


	/**
	 * =================================================================
	 *                          BACKEND EVENTS
	 * =================================================================
	 */

	/**
	 * Trigger backend events if true
	 * called on 'admin_head'
	*/
	public function trigger_backend_events() {

		// manual reset
		if ( isset($_POST["reset_".$this->setup->slug]) && $_POST["reset_".$this->setup->slug] === "true" ) {
			$this->trigger_reset_event();
			return;
		}
		// manual update
		else if ( isset($_POST["update_".$this->setup->slug]) && $_POST["update_".$this->setup->slug] === "true" ) {
			$this->trigger_event( 'manual' );
			return;
		}

		// normal events
		$screen     = function_exists('get_current_screen') ? get_current_screen() : null;
		$events     = isset($this->events['backend']) ? $this->events['backend'] : array();

		if ( empty($screen) || empty($events) ) return;

		$trigger = $this->check_backend_events($events, $screen);
		if ( $trigger === true ) $this->trigger_event( 'backend' );
	}

	public function check_backend_events($events, $screen) {
		foreach ( $events as $event ) {
			if ($event === 'any') return true;
			else if ($event === 'this' && strpos($screen->base, $this->setup->slug) !== false ) return true;
			else if ($event === $screen->post_type) return true;
		}
		return false;
	}


	/**
	 * =================================================================
	 *                          SCHEDULED EVENTS
	 * =================================================================
	 */

	/**
	 * Add custom intervals for wp_schedule_event()
	 * called via filter 'cron_schedules'
	 */
	public function add_schedule_intervals($schedules) {

		if ( $this->setup_incomplete ) return $schedules;

		// add a 'weekly' interval
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __('Weekly', 'greyd_hub')
		);
		// add a 'monthly' interval
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display' => __('Monthly', 'greyd_hub')
		);
		return $schedules;
	}

	/**
	 * Schedule events when option is changed.
	 */
	public function create_scheduled_events() {

		if ( $this->setup_incomplete ) return;

		// abort if option wasn't changed
		if ( !get_transient( $this->setup->slug.'_option_changed' ) ) return false;
		delete_transient( $this->setup->slug.'_option_changed' );

		// get all scheduled events
		$events = isset($this->events['schedules']) ? $this->events['schedules'] : null;
		if ( empty($events) || !is_array($events) ) return;

		// set events
		foreach ( $events as $event => $detail ) {
			$hook = sprintf( 'greyd_automator_%s_event', $event );

			// remove event
			if ( $detail === null ) {
				wp_clear_scheduled_hook( $hook );
			}
			// setup event
			else if ( ! wp_next_scheduled ( $hook ) ) {
				wp_schedule_event( current_time('timestamp'), $event, $hook );
			}
		}
	}

	/**
	 * Get all scheduled greyd_automator events
	 */
	public function get_scheduled_events($human=false) {

		$return = array();

		$events = isset($this->events['schedules']) ? $this->events['schedules'] : null;
		if ( empty($events) || !is_array($events) ) return false;

		foreach ( $events as $event => $detail ) {
			$hook = sprintf( 'greyd_automator_%s_event', $event );
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) $return[$event] = $human ? self::format_time($timestamp) : $timestamp;
		}
		return $return;
	}

	public static function format_time($timestamp=null) {
		if ( isset($timestamp) && !empty($timestamp) )
			return sprintf( __('%1$s at %2$s'), wp_date( get_option('date_format'), $timestamp ), wp_date( get_option('time_format'), $timestamp ) );
		else
			return false;
	}

	/**
	 * Remove all scheduled events
	 * call this function on plugin deactivation
	*/
	public function remove_scheduled_events() {
		foreach ( $this->events['schedules'] as $event => $detail ) {
			$hook = sprintf( 'greyd_automator_%s_event', $event );
			wp_clear_scheduled_hook( $hook );
		}
	}


	/**
	 * =================================================================
	 *                          DEBUGGER
	 * =================================================================
	 */

	/**
	 * Add a debug log to the email.
	 * Use the action 'automator_debug' to call this function
	 * 
	 * @param string message    The message to log.
	 * @param mixed  variable   An additional variable to debug.
	 */
	public function log( $message, $variable='do_not_log' ) {

		$log = "";
		if ( $variable !== 'do_not_log' ) {
			ob_start();
			"<pre>";
			print_r( $variable );
			echo "</pre>";
			$log .= ob_get_contents();
			ob_end_clean();
		}

		if ( !empty($message) && is_string($message) ) {
			$this->logs[] = $message.$log;
		}
	}

	/**
	 * Send the email with all the debug logs to an admin.
	 * 
	 * @action 'send_automator_debug_mail'
	 */
	public function send_debug_email( $message="" ) {

		if ( empty($this->logs) ) return;

		// get basic mail info
		$subject        = apply_filters( 'greyd_automator_email_title', sprintf( __("Automator of the site '%s' was successfully completed.", "greyd_hub" ), get_site_url() ) );

		// mail content
		$mail_content = "
			".( empty($message) || !is_string($message) ? "" : "$message<br>" )."
			<h3>".__("Here are the logs of the different tasks:", "greyd_hub")."</h3>
			<ul><li>".
			implode( "</li><li>", $this->logs ).
			"</li></ul>
			<br><a href='".wp_login_url()."'>".__("Log in to WordPress", 'greyd_hub')."</a>
		";

		// on localhost we display the logs directly because mails are usually not supported.
		if ( strpos( explode("://", get_site_url(), 2)[1], 'localhost:' ) === 0 && is_admin() ) {
			echo "<div style='margin-left:180px;border-left:2px solid currentColor;padding-left:20px;'>".$mail_content."</div>";
			return;
		}

		$option = $this->get_option();

		if ( empty($option) || !isset($option['admin_email']) || empty($option['admin_email']) ) {
			return false;
		}

		add_filter( 'wp_mail_content_type', array($this, 'wpdocs_set_html_mail_content_type') );
		add_action( 'wp_mail_failed', array($this, 'display_mail_error'), 10, 1 );

		// Send Mails to every address
		foreach (  explode(",", $option['admin_email']) as $address ) {
			$return = wp_mail(trim($address), $subject, $mail_content);
		}

		remove_filter( 'wp_mail_content_type', array($this, 'wpdocs_set_html_mail_content_type') );
		remove_action( 'wp_mail_failed', array($this, 'display_mail_error') );

		return $return;
	}

	/**
	 * Display an email error
	 */
	function display_mail_error( $wp_error ) {
		var_error_log( $wp_error );
	} 

	/**
	 * set mails to html
	 * @source (see comments): https://developer.wordpress.org/reference/functions/wp_mail/
	 */
	public function wpdocs_set_html_mail_content_type() {
		return 'text/html';
	}
}