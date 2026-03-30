/**
 * Animation editor controls.
 * This file is loaded in block editor pages and modifies the editor experience.
 * 
 * @since 1.6.0
 * 
 * @todo Start- & end-offsets for scrolling animations.
 * @todo load animations.
 */

/**
 * @namespace greyd
 */
var greyd = greyd || {};
greyd.components = greyd.components || {};

( function ( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;
	
	/**
	 * @supports
	 */
	const backgroundSupportBlocks = [
		'core/columns',
		'core/cover',
		'greyd/box'
	]

	/**
	 * Build the HTML attributes.
	 * @param {object} animation Animation attribute
	 * @returns {object} HTML attributes.
	 */
	const buildAnimationHtmlAttributes = ( animation ) => {

		const htmlAttributes = {
			'data-anim-action': animation.action,
			'data-anim-event': animation.event,
		};

		// action
		if ( animation.action == 'hide' || animation.action == 'show' ) {
			if ( !_.isEmpty( animation.preset ) ) {
				htmlAttributes[ 'data-anim-action' ] = animation.preset;
			}
		}
		
		return htmlAttributes;
	};

	/**
	 * Build the animation styles.
	 * @param {object} animation Animation attribute.
	 * @returns {object} CSS style as object.
	 */
	const buildAnimationStyles = ( animation ) => {

		let animationStyles = {};

		// action
		if ( animation.action == 'hide' || animation.action == 'show' ) {
			
		}
		else if ( animation.action == 'rotate' ) {
			animationStyles = {
				transform: animation.action + '(' + animation.from + 'deg)',
				hover: {
					transform: animation.action + '(' + animation.to + 'deg)'
				}
			}
		}
		else if ( animation.action == 'changeColor' ) {
			animationStyles = {
				hover: {
					...( animation?.color ? { color: animation.color } : {} ),
					...( animation?.background ? { background: animation.background } : {} )
				}
			}
		}
		else if ( animation.action == 'filter' ) {
			const unit = animation.preset == 'blur' ? 'px' : '%';
			animationStyles = {
				filter: animation.preset + '(' + animation.from + unit + ')',
				hover: {
					filter: animation.preset + '(' + animation.to + unit + ')'
				}
			}
		}
		else if ( animation.action == 'custom' ) {
			animationStyles = {
				...greyd.tools.cssStringToObject( animation.from ),
				hover: {
					...greyd.tools.cssStringToObject( animation.to ),
				}
			}
		}
		else { // translateX, translateY, scale
			animationStyles = {
				transform: animation.action + '(' + animation.from + ')',
				hover: {
					transform: animation.action + '(' + animation.to + ')'
				}
			}

			if ( animation.origin !== 'center center' ) {
				animationStyles.transformOrigin = animation.origin;
			}
		}

		// transition
		if ( animation.duration != 200 ) {
			animationStyles[ 'transition-duration' ] = animation.duration + 'ms';
		}
		if ( animation.delay != 0 ) {
			animationStyles[ 'transition-delay' ] = animation.delay + 'ms';
		}
		if ( animation.timing != 'ease' ) {
			animationStyles[ 'transition-timing-function' ] = animation.timing;
		}

		// console.log( 'animationStyles', animationStyles, 'animation', animation );
		return animationStyles;
	};


	/**
	 * AnimationControl component.
	 * 
	 * @property {string} value Current value.
	 * @property {string} label Label.
	 * @property {callback} onChange Callback function to be called when value is changed (params: value, event).
	 * @property {string} greydClass Optionals greydclass as selector
	 * @property {object} events extra events to trigger the animation.
	 * 
	 * @returns {object} AnimationControl component.
	 */
	greyd.components.AnimationControl = class extends wp.element.Component {

		constructor () {
			super();
			this.state = {
				...this.getDefaultState()
			};
		}

		getDefaultState() {
			return {
				isOpen: false,
				isPlaying: false,
				tempValue: false
			};
		}

		getDefaultValue() {
			return {
				// action
				action: 'hide',
				preset: 'fadeOut',
				from: '',
				to: '',
				origin: 'center center',

				// event
				event: 'hover',
				parent: '',
				start: '50%',
				end: '50%',
				reverse: false,

				// animation
				duration: 200,
				delay: 0,
				timing: 'ease',

				// unused
				// id: '', /** @todo ID */
				// iterationCount: 1, /** @todo iterationCount */
			};
		}

		getActions() {
			return {
				hide: {
					label: __( "Hide", 'greyd_hub' ),
					value: 'hide',
					default: { preset: 'fadeOut' },
					controls: [
						{
							label: __( "Effect", 'greyd_hub' ),
							value: 'preset',
							component: wp.components.SelectControl,
							options: [
								{
									label: __( 'Fade out', 'greyd_hub' ),
									value: 'fadeOut'
								},
								{
									label: __( 'Fade out, down', 'greyd_hub' ),
									value: 'fadeOutDown'
								},
								{
									label: __( 'Fade out, up', 'greyd_hub' ),
									value: 'fadeOutUp'
								},
								{
									label: __( 'Fade out, right', 'greyd_hub' ),
									value: 'fadeOutRight'
								},
								{
									label: __( 'Fade out, left', 'greyd_hub' ),
									value: 'fadeOutLeft'
								},
								{
									label: __( "No effect", 'greyd_hub' ),
									value: ''
								}
							]
						}
					]
				},
				show: {
					label: __( "Show", 'greyd_hub' ),
					value: 'show',
					default: { preset: 'fadeIn' },
					controls: [
						{
							label: __( "Effect", 'greyd_hub' ),
							value: 'preset',
							component: wp.components.SelectControl,
							options: [
								{
									label: __( 'Fade in', 'greyd_hub' ),
									value: 'fadeIn'
								},
								{
									label: __( 'Fade in, down', 'greyd_hub' ),
									value: 'fadeInDown'
								},
								{
									label: __( 'Fade in, up', 'greyd_hub' ),
									value: 'fadeInUp'
								},
								{
									label: __( 'Fade in, right', 'greyd_hub' ),
									value: 'fadeInRight'
								},
								{
									label: __( 'Fade in, left', 'greyd_hub' ),
									value: 'fadeInLeft'
								},
								{
									label: __( "No effect", 'greyd_hub' ),
									value: ''
								}
							]
						}
					]
				},
				changeColor: {
					label: __( "Change color", 'greyd_hub' ),
					value: 'changeColor',
					default: { from: '', to: '', color: '', background: '' },
					controls: [
						{
							label: __( "Text color", 'greyd_hub' ),
							value: 'color',
							component: greyd.components.ColorGradientPopupControl,
							mode: 'color'
						},
						{
							label: __( "Background color", 'greyd_hub' ),
							value: 'background',
							component: greyd.components.ColorGradientPopupControl
						}
					]
				},
				translateX: {
					label: __( "Move (horizontally)", 'greyd_hub' ),
					value: 'translateX',
					default: { from: '0px', to: '42px' },
					controls: [
						{
							label: __( "Initial state", 'greyd_hub' ),
							value: 'from',
							component: wp.components.__experimentalUnitControl,
						},
						{
							label: __( "Final state", 'greyd_hub' ),
							value: 'to',
							component: wp.components.__experimentalUnitControl,
						}
					]
				},
				translateY: {
					label: __( "Move (vertical)", 'greyd_hub' ),
					value: 'translateY',
					default: { from: '0px', to: '-10px' },
					controls: [
						{
							label: __( "Initial state", 'greyd_hub' ),
							value: 'from',
							component: wp.components.__experimentalUnitControl,
						},
						{
							label: __( "Final state", 'greyd_hub' ),
							value: 'to',
							component: wp.components.__experimentalUnitControl,
						}
					]
				},
				scale: {
					label: __( "Scale", 'greyd_hub' ),
					value: 'scale',
					default: { from: 1, to: 1.3, origin: 'center center' },
					controls: [
						{
							label: __( "Initial state", 'greyd_hub' ),
							value: 'from',
							component: wp.components.RangeControl,
							min: 0,
							max: 3,
							step: 0.1
						},
						{
							label: __( "Final state", 'greyd_hub' ),
							value: 'to',
							component: wp.components.RangeControl,
							min: 0,
							max: 3,
							step: 0.1
						},
						{
							label: __( "Origin", 'greyd_hub' ),
							value: 'origin',
							component: wp.components.SelectControl,
							options: [
								{
									label: __( "Top left", 'greyd_hub' ),
									value: 'top left'
								},
								{
									label: __( "Top center", 'greyd_hub' ),
									value: 'top center'
								},
								{
									label: __( "Top right", 'greyd_hub' ),
									value: 'top right'
								},
								{
									label: __( "Center left", 'greyd_hub' ),
									value: 'center left'
								},
								{
									label: __( "Center center", 'greyd_hub' ),
									value: 'center center'
								},
								{
									label: __( "Center right", 'greyd_hub' ),
									value: 'center right'
								},
								{
									label: __( "Bottom left", 'greyd_hub' ),
									value: 'bottom left'
								},
								{
									label: __( "Bottom center", 'greyd_hub' ),
									value: 'bottom center'
								},
								{
									label: __( "Bottom right", 'greyd_hub' ),
									value: 'bottom right'
								}
							]
						}
					]
				},
				rotate: {
					label: __( "Rotate", 'greyd_hub' ),
					value: 'rotate',
					default: { from: 0, to: 24 },
					controls: [
						{
							label: __( "Initial state", 'greyd_hub' ),
							value: 'from',
							component: wp.components.AnglePickerControl,
						},
						{
							label: __( "Final state", 'greyd_hub' ),
							value: 'to',
							component: wp.components.AnglePickerControl,
						}
					]
				},
				filter: {
					label: __( 'Filter', 'greyd_hub' ),
					value: 'filter',
					default: { preset: 'blur', from: 0, to: 4 },
					controls: [
						{
							label: __( "Effect", 'greyd_hub' ),
							value: 'preset',
							component: wp.components.SelectControl,
							options: [
								{
									label: __( 'Blur', 'greyd_hub' ),
									value: 'blur'
								},
								{
									label: __( 'Brightness', 'greyd_hub' ),
									value: 'brightness'
								},
								{
									label: __( 'Contrast', 'greyd_hub' ),
									value: 'contrast'
								},
								{
									label: __( 'Grayscale', 'greyd_hub' ),
									value: 'grayscale'
								},
								{
									label: __( 'Invert', 'greyd_hub' ),
									value: 'invert'
								},
								{
									label: __( 'Saturate', 'greyd_hub' ),
									value: 'saturate'
								},
								{
									label: __( 'Sepia', 'greyd_hub' ),
									value: 'sepia'
								},
								{
									label: __( "No effect", 'greyd_hub' ),
									value: ''
								}
							]
						},
						{
							label: __( "Initial state", 'greyd_hub' ),
							value: 'from',
							component: wp.components.RangeControl,
							min: 0,
							max: 100
						},
						{
							label: __( "Final state", 'greyd_hub' ),
							value: 'to',
							component: wp.components.RangeControl,
							min: 0,
							max: 100
						}
					]
				},
				// move: {},
				custom: {
					label: __( "Custom", 'greyd_hub' ),
					value: 'custom',
					default: { from: 'scale: 0.8;\nopacity: 0.3;\nfilter: blur(2px)', to: 'scale: 1;\nopacity: 1;\nfilter: blur(0px)' },
					controls: [
						{
							label: __( "Initial state", 'greyd_hub' ),
							value: 'from',
							component: wp.components.TextareaControl,
							className: 'code-input-control'
						},
						{
							label: __( "Final state", 'greyd_hub' ),
							value: 'to',
							component: wp.components.TextareaControl,
							className: 'code-input-control'
						}
					]
				}
			};
		}

		getEvents( events ) {
			return {
				hover: {
					label: __( 'Hover', 'greyd_hub' ),
					value: 'hover'
				},
				click: {
					label: __( "Click", 'greyd_hub' ),
					value: 'click'
				},
				onScroll: {
					label: __( 'On scroll', 'greyd_hub' ),
					value: 'onScroll',
					default: { start: '50%' },
					controls: [
						{
							label: __( "Start", 'greyd_hub' ),
							value: 'start',
							component: greyd.components.SelectCustomControl,
							options: [
								{
									label: __( "0% - element at top edge", 'greyd_hub' ),
									value: '0%'
								},
								{ label: __( '10%', 'greyd_hub' ), value: '10%' },
								{ label: __( '20%', 'greyd_hub' ), value: '20%' },
								{ label: __( '30%', 'greyd_hub' ), value: '30%' },
								{ label: __( '40%', 'greyd_hub' ), value: '40%' },
								{
									label: __( "50% - element in the center of the screen", 'greyd_hub' ),
									value: '50%'
								},
								{ label: __( '60%', 'greyd_hub' ), value: '60%' },
								{ label: __( '70%', 'greyd_hub' ), value: '70%' },
								{ label: __( '80%', 'greyd_hub' ), value: '80%' },
								{ label: __( '90%', 'greyd_hub' ), value: '90%' },
								{
									label: __( "100% - element at bottom edge", 'greyd_hub' ),
									value: '100%'
								}
							],
							/**
							 * @since 1.7.1 We added the option to use individual scroll points.
							 */
							customLabel: __( "Absolute scroll point", 'greyd_hub' ),
							customPlaceholder: __( 'Value in pixels, e.g. 42px', 'greyd_hub' ),
						},
						{
							label: __( "Trigger in both directions?", 'greyd_hub' ),
							value: 'reverse',
							component: wp.components.ToggleControl
						}
					]
				},
				parentHover: {
					label: __( "Parent hover", 'greyd_hub' ),
					value: 'parentHover',
					controls: [
						{
							label: __( "Select parent", 'greyd_hub' ),
							value: 'parent',
							component: greyd.components.ParentSelectControl
						}
					]
				},
				parentClick: {
					label: __( "Parent click", 'greyd_hub' ),
					value: 'parentClick',
					controls: [
						{
							label: __( "Select parent", 'greyd_hub' ),
							value: 'parent',
							component: greyd.components.ParentSelectControl
						}
					]
				},
				whileScroll: {
					label: __( "While scrolling", 'greyd_hub' ),
					value: 'whileScroll',
					default: { start: 'bottom', end: 'top', start: '100', end: '0', duration: 0, delay: 0, timing: 'ease' },
					controls: [
						{
							label: __( "Start", 'greyd_hub' ),
							value: 'start',
							component: wp.components.SelectControl,
							options: [
								{
									label: __( "-50% - element above window", 'greyd_hub' ),
									value: '-50'
								},
								{ label: __( '-40%', 'greyd_hub' ), value: '-40' },
								{ label: __( '-30%', 'greyd_hub' ), value: '-30' },
								{ label: __( '-20%', 'greyd_hub' ), value: '-20' },
								{ label: __( '-10%', 'greyd_hub' ), value: '-10' },
								{
									label: __( "0% - element center on upper edge", 'greyd_hub' ),
									value: '0'
								},
								{ label: __( '10%', 'greyd_hub' ), value: '10' },
								{ label: __( '20%', 'greyd_hub' ), value: '20' },
								{ label: __( '30%', 'greyd_hub' ), value: '30' },
								{ label: __( '40%', 'greyd_hub' ), value: '40' },
								{
									label: __( "50% - element in the center of the screen", 'greyd_hub' ),
									value: '50'
								},
								{ label: __( '60%', 'greyd_hub' ), value: '60' },
								{ label: __( '70%', 'greyd_hub' ), value: '70' },
								{ label: __( '80%', 'greyd_hub' ), value: '80' },
								{ label: __( '90%', 'greyd_hub' ), value: '90' },
								{
									label: __( "100% - element center on bottom edge", 'greyd_hub' ),
									value: '100'
								},
								{ label: __( '110%', 'greyd_hub' ), value: '110' },
								{ label: __( '120%', 'greyd_hub' ), value: '120' },
								{ label: __( '130%', 'greyd_hub' ), value: '130' },
								{ label: __( '140%', 'greyd_hub' ), value: '140' },
								{
									label: __( "150% - element below window", 'greyd_hub' ),
									value: '150'
								}
							]
						},
						{
							label: __( "End", 'greyd_hub' ),
							value: 'end',
							component: wp.components.SelectControl,
							options: [
								{
									label: __( "-50% - element above window", 'greyd_hub' ),
									value: '-50'
								},
								{ label: __( '-40%', 'greyd_hub' ), value: '-40' },
								{ label: __( '-30%', 'greyd_hub' ), value: '-30' },
								{ label: __( '-20%', 'greyd_hub' ), value: '-20' },
								{ label: __( '-10%', 'greyd_hub' ), value: '-10' },
								{
									label: __( "0% - element center on upper edge", 'greyd_hub' ),
									value: '0'
								},
								{ label: __( '10%', 'greyd_hub' ), value: '10' },
								{ label: __( '20%', 'greyd_hub' ), value: '20' },
								{ label: __( '30%', 'greyd_hub' ), value: '30' },
								{ label: __( '40%', 'greyd_hub' ), value: '40' },
								{
									label: __( "50% - element in the center of the screen", 'greyd_hub' ),
									value: '50'
								},
								{ label: __( '60%', 'greyd_hub' ), value: '60' },
								{ label: __( '70%', 'greyd_hub' ), value: '70' },
								{ label: __( '80%', 'greyd_hub' ), value: '80' },
								{ label: __( '90%', 'greyd_hub' ), value: '90' },
								{
									label: __( "100% - element center on bottom edge", 'greyd_hub' ),
									value: '100'
								},
								{ label: __( '110%', 'greyd_hub' ), value: '110' },
								{ label: __( '120%', 'greyd_hub' ), value: '120' },
								{ label: __( '130%', 'greyd_hub' ), value: '130' },
								{ label: __( '140%', 'greyd_hub' ), value: '140' },
								{
									label: __( "150% - element below window", 'greyd_hub' ),
									value: '150'
								}
							]
						}
					]
				},

				/**
				 * @since 2.10.0 load animations.
				 */
				load: {
					label: __( 'On page load', 'greyd_hub' ),
					value: 'load',
					default: { reverse: false },
					controls: [
						{
							label: __( "Loop the animation", 'greyd_hub' ),
							value: 'reverse',
							component: wp.components.ToggleControl
						}
					]
				},

				/**
				 * @since 1.7.2 Add greyd/is-position-variation context
				 */
				...events?.isSticky ? {
					isSticky: {
						label: __( 'Sticky', 'greyd_hub' ),
						value: 'isSticky',
						// controls: [
						// 	{
						// 		component: wp.components.Tip,
						// 		children: __("Wenn das Element sticky ist.", 'greyd_hub'),
						// 	}
						// ]
					}
				} : {},
				...events?.parentSticky ? {
					parentSticky: {
						label: __( "Parent sticky", 'greyd_hub' ),
						value: 'parentSticky',
						// controls: [
						// 	{
						// 		component: wp.components.Tip,
						// 		children: __("Wenn ein Parent Element sticky ist.", 'greyd_hub'),
						// 	}
						// ]
					}
				} : {}
			};
		}

		render() {

			// props
			const {
				onChange = () => { },
				label = ''
			} = this.props;

			const getDefaultValue = this.getDefaultValue();
			const hasValue = !_.isEmpty( this.props.value );
			const value = hasValue ? this.props.value : getDefaultValue;
			// const id = _.isEmpty( greydClass ) ? generateRandomID() : greydClass;
			const actions = this.getActions();
			const events = this.getEvents( this.props?.events );

			// state
			const {
				isOpen,
				tempValue = {}
			} = this.state;

			// error warning
			const errors = [];
			if ( tempValue?.event === 'whileScroll' ) {

				if ( tempValue?.action === 'custom' ) {
					errors.push( __( "Make sure that the CSS rules for the start and end states are as identical as possible, otherwise the animation will not work. All numerical values are changed incrementally.", 'greyd_hub' ) );
				}
				else if ( tempValue?.action === 'changeColor' ) {
					errors.push( __( "Color changes cannot be animated while scrolling, as only numerical values can be changed incrementally. Please select a different action.", 'greyd_hub' ) );
				}
			}
			if ( tempValue?.action === 'changeColor' && tempValue?.background.includes('gradient') ) {
				errors.push( __("Please note that due to browser limitations, transition settings do not work when a gradient background is used.", "greyd_hub" ) );
			}

			const open = () => this.setState( {
				...this.state,
				isOpen: true,
				...( _.isEmpty(tempValue) ? { tempValue: value } : {} )
			} )
			const close = () => this.setState( {
				...this.state,
				isOpen: false
			} )
			const edit = ( val ) => this.setState( {
				...this.state,
				tempValue: val
			} )
			const reset = () => this.setState( {
				...this.state,
				tempValue: this.getDefaultValue()
			} )
			const cancel = ( val ) => this.setState( {
				...this.state,
				isOpen: false,
				tempValue: value
			} )
			const save = ( val ) => {

				/**
				 * @since 1.7.6 Convert values before saving
				 */
				if ( _.has( val, 'convert' ) ) {
					
					/**
					 * @since 1.7.6 Add suffix '%' to absolute values.
					 * 
					 * The convert flag is set on change of the start value of the onScroll event.
					 */
					if ( val.convert === 'onScrollStartAbsolute' ) {
						if ( val?.event === 'onScroll' && val?.start && val?.start.indexOf( 'px' ) === -1 ) {
							val.start = parseInt( val.start ) + 'px';
						}
					}

					// remove convert flag
					console.log( 'remove convert flag' )
					delete val.convert;
				}

				this.setState( {
					...this.state,
					tempValue: val,
					isOpen: false
				} )
				onChange( val )
			}

			return el( wp.element.Fragment, {
				// className: 'greyd-animation-control'
			}, [

				( label.length > 0 ? el( wp.components.BaseControl.VisualLabel, { }, label ) : null ),

				// summary
				el( wp.components.BaseControl, {
					className: 'greyd-animation-control--summary',
				}, (
					hasValue
						? [
							el( wp.components.Button, {
								// onClick: () => this.setState( { ...this.state, isPlaying: !isPlaying } )
							}, [
								el( wp.components.Tooltip, {
									// text: __( 'Animation abspielen', 'greyd_hub' ),
									placement: 'top left'
								}, el( 'span', {}, ( _.isEmpty( events[ value.event ]?.label ) ? value.event : events[ value.event ]?.label ) + ': ' + actions[ value.action ].label ) )
							] ),
							el( wp.components.Button, {
								onClick: open
							}, greyd.tools.getCoreIcon( 'edit' ) ),
							el( wp.components.Button, {
								style: { '--wp-components-color-accent': '#cc1818' },
								onClick: () => save( false )
							}, greyd.tools.getCoreIcon( 'trash' ) ),
						]
						: [
							el( wp.components.Button, {
								icon: greyd.tools.getCoreIcon( 'add' ),
								onClick: open
							}, __( "Add animation", 'greyd_hub' ) )
						]
				) ),

				_.isEmpty( events[ value.event ]?.label ) && el( wp.components.BaseControl, {}, [
					el( wp.components.Notice, {
						status: "warning",
						isDismissible: false
					}, __( "Attention: The trigger '%s' may not be supported in this block. Please check the animation.", 'greyd_hub' ).replace( "%s", value.event ) )
				] ),

				// popover
				isOpen && el( wp.components.Modal, {
					focusOnMount: true,
					shouldCloseOnEsc: true,
					shouldCloseOnClickOutside: true,
					overlayClassName: "greyd-animation-control--overlay",
					title: __( "Edit animation", 'greyd_hub' ),
					onRequestClose: close
				}, [

					// preview
					el( wp.element.Fragment, {}, [
						el( 'div', {
							className: 'greyd-animation-control--preview-description'
						}, [
							// el( wp.components.BaseControl.VisualLabel, {}, __( "Preview", 'greyd_hub' ) ),
							el( 'small', {}, '↓ ' + __( "Touch to play animation.", 'greyd_hub' ) ),
						] ),
						el( 'div', {
							className: 'greyd-animation-control--preview'
						}, [
							el( 'div', {
								className: 'greyd-animation-control--preview-element',
								...buildAnimationHtmlAttributes( tempValue ),
							}, [
								el( 'small', {}, __( "Preview", 'greyd_hub' ) ),
								el( 'span', { style: { '--clr': 'var(--wp--preset--color--primary)' } } ),
								el( 'span', { style: { '--clr': 'var(--wp--preset--color--secondary)' } } ),
								el( 'span', { style: { '--clr': 'var(--wp--preset--color--tertiary)' } } ),
								el( 'span', { style: { '--clr': 'var(--wp--preset--color--base)' } } ),
								el( 'span', { style: { '--clr': 'var(--wp--preset--color--foreground)' } } ),
							] ),
							el( greyd.components.RenderSavedStyles, {
								selector: 'greyd-animation-control--preview',
								styles: {
									" .greyd-animation-control--preview-element": buildAnimationStyles( tempValue )
								}
							} )
						] )
					] ),

					// action
					el( 'div', {
						className: 'greyd-animation-control--row'
					}, [
						el( wp.components.SelectControl, {
							label: __( "Action", 'greyd_hub' ),
							value: tempValue.action,
							options: Object.values( actions ),
							onChange: ( val ) => edit( {
								...tempValue,
								action: val,
								...(
									_.has( actions[ val ], 'default' )
									? actions[ val ].default
									: {}
								)
							} )
						} ),
						...(
							_.has( actions[ tempValue.action ], 'controls' )
							? actions[ tempValue.action ].controls.map( ( control ) => {

								const {
									component: controlComponent,
									value: controlValue,
									options: controlOptions = [],
									children: controlChildren = null,
									...controlProps
								} = control;

								return el( controlComponent, {
									value: tempValue[ controlValue ],
									onChange: ( val ) => {
										edit( {
											...tempValue,
											[ controlValue ]: val
										} )
									},
									options: controlOptions,
									...controlProps
								}, controlChildren );
							} )
							: []
						)
					] ),

					// errors
					errors.length > 0 && el( 'div', {
						className: 'greyd-animation-control--errors'
					}, errors.map( ( error ) => el( wp.components.Notice, { status: "warning", isDismissible: false }, error ) ) ),

					// event
					el( 'div', {
						className: 'greyd-animation-control--row'
					}, [
						el( wp.components.SelectControl, {
							label: __( "Trigger", 'greyd_hub' ),
							value: tempValue.event,
							options: Object.values( events ),
							onChange: ( val ) => edit( {
								...tempValue,
								event: val,
								...(
									_.has( events[ val ], 'default' )
									? events[ val ].default
									: {}
								)
							} )
						} ),
						...(
							_.has( events[ tempValue.event ], 'controls' )
								? events[ tempValue.event ].controls.map( ( control ) => {

									const {
										component: controlComponent,
										value: controlValue,
										options: controlOptions = [],
										children: controlChildren = null,
										...controlProps
									} = control;

									/**
									 * @since 1.7.1 We added the option to use individual scroll points.
									 * 
									 * Relative values have the suffix '%', absolute values have the suffix 'px'.
									 * Those were added for proper backward compatibility, because we used simple
									 * numeric values before, like '50' or '100', that are now converted to '50%'
									 * and '100px' respectively.
									 */
									if ( controlValue === 'start' && tempValue.event === 'onScroll' ) {
										if ( _.isEmpty( tempValue?.convert ) && /^\d+$/.test( tempValue[ controlValue ] ) ) {
											console.log( 'convert animation onScroll start to relative value (from ' + tempValue[ controlValue ] + ' to ' + tempValue[ controlValue ] + '%)' )
											tempValue[ controlValue ] = tempValue[ controlValue ] + '%';
										}
									}

									return el( controlComponent, {
										value: tempValue[ controlValue ],
										checked: tempValue[ controlValue ],
										onChange: ( val ) => {

											/**
											 * @since 1.7.6 Log that we have an absolute value
											 * This convert flag is used inside the save() function to add
											 * the suffix 'px' to the value.
											 * Otherwise it would be converted into a relative value on the
											 * next load through the code above.
											 */
											if ( controlValue === 'start' && tempValue.event === 'onScroll' ) {
												if ( _.isEmpty( tempValue?.convert ) && /^\d+$/.test( val ) ) {
													tempValue.convert = 'onScrollStartAbsolute';
													console.log( 'set convert flag to onScrollStartAbsolute' )
												}
											}

											edit( {
												...tempValue,
												[ controlValue ]: val
											} )
										},
										options: controlOptions,
										...controlProps
									}, controlChildren );
								} )
								: []
						)
					] ),

					// animation
					el( 'div', {
						className: 'greyd-animation-control--row'
					}, [
						el( wp.components.SelectControl, {
							label: __( 'Transition', 'greyd_hub' ),
							value: tempValue.timing,
							options: [
								{
									label: __( 'Ease', 'greyd_hub' ),
									value: 'ease'
								},
								{
									label: __( "Ease in out", 'greyd_hub' ),
									value: 'ease-in-out'
								},
								{
									label: __( 'Ease in', 'greyd_hub' ),
									value: 'ease-in'
								},
								{
									label: __( "Ease out", 'greyd_hub' ),
									value: 'ease-out'
								},
								{
									label: __( 'Linear', 'greyd_hub' ),
									value: 'linear'
								},
							],
							onChange: ( val ) => edit( {
								...tempValue,
								timing: val
							} ),
							// disabled: tempValue.event === 'whileScroll'
						} ),
						el( wp.components.RangeControl, {
							label: __( "Duration (ms)", 'greyd_hub' ),
							value: tempValue.duration,
							step: 50,
							min: 0,
							max: 3000,
							onChange: ( val ) => edit( {
								...tempValue,
								duration: val
							} ),
							// disabled: tempValue.event === 'whileScroll'
						} ),
						el( wp.components.RangeControl, {
							label: __( "Delay (ms)", 'greyd_hub' ),
							value: tempValue.delay,
							step: 50,
							min: 0,
							max: 3000,
							onChange: ( val ) => edit( {
								...tempValue,
								delay: val
							} ),
							// disabled: tempValue.event === 'whileScroll'
						} )
					] ),

					// save / reset / cancel
					el( wp.components.Flex, {
						justify: 'flex-end',
						gap: 4
					}, [
						el( wp.components.FlexBlock, {}, [
							el( wp.components.Button, {
								icon: 'undo',
								onClick: reset,
								variant: 'tertiary'
							}, __( "Reset", 'greyd_hub' ) ),
						] ),
						el( wp.components.FlexItem, {}, [
							el( wp.components.Button, {
								onClick: cancel,
								variant: 'tertiary'
							}, __( "Cancel", 'greyd_hub' ) ),
						] ),
						el( wp.components.FlexItem, {}, [
							el( wp.components.Button, {
								disabled: tempValue === value,
								onClick: () => save( tempValue ),
								variant: 'primary'
							}, __( "Save", 'greyd_hub' ) )
						] )
					] )

				] )
			] );
		}
	};

	/**
	 * Register animation attribute to suppported blocks.
	 * 
	 * @hook blocks.registerBlockType
	 */
	var registerAnimationControl = function ( settings, name ) {

		if ( !_.has( settings, 'apiVersion' ) ) return settings;

		// do not support excluded blocks
		if ( [
			'core/query',
			'core/post-template',
			'core/spacer',
			'greyd/popover',
			'greyd/popover-popup',
			'greyd/conditional-content',
			'greyd-forms/hidden-field',
		].indexOf( name ) !== -1 ) {
			return settings;
		}

		if (
			typeof settings?.supports?.customClassName === 'undefined'
			|| settings?.supports?.customClassName === true
		) {
			settings.attributes.greydAnim = { type: 'object' };

			// add attribute to all deprecations
			if ( typeof settings.deprecated !== 'undefined' ) {
				for (var i=0; i < settings.deprecated.length; i++) {
					if ( settings.deprecated[i] && typeof settings.deprecated[i].attributes !== 'undefined' ) {
						settings.deprecated[i].attributes = {
							...settings.deprecated[i]?.attributes,
							greydAnim: { type: 'object' }
						}
					}
				}
			}

			if ( typeof settings.usesContext === 'undefined' ) {
				settings.usesContext = [];
			}
			else if ( typeof settings.usesContext.push === 'undefined' ) {
				settings.usesContext = [];
			}

			/**
			 * @since 1.7.2 Add greyd/is-position-variation context
			 * @see greyd_blocks/inc/layout/assets/js/editor.js
			 */
			settings.usesContext.push( 'greyd/is-position-variation' );
		}

		if ( backgroundSupportBlocks.indexOf( name ) !== -1 ) {
			settings.attributes.greydBackgroundAnim = { type: 'object' };

			// add attribute to all deprecations
			if ( typeof settings.deprecated !== 'undefined' ) {
				for (var i=0; i < settings.deprecated.length; i++) {
					if ( settings.deprecated[i] && typeof settings.deprecated[i].attributes !== 'undefined' ) {
						settings.deprecated[i].attributes = {
							...settings.deprecated[i]?.attributes,
							greydBackgroundAnim: { type: 'object' }
						}
					}
				}
			}
		}

		return settings;
	};

	wp.hooks.addFilter(
		'blocks.registerBlockType',
		'greyd/hook/trigger',
		registerAnimationControl
	);


	/**
	 * Add animation editor controls to suppported blocks.
	 * 
	 * @hook editor.BlockEdit
	 */
	const addAnimationControl = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {

		return function ( props ) {

			const blockType = wp.blocks.getBlockType( props.name );
			
			let holdsChange = false;
			let controls = [];

			// greydAnim support
			if ( _.has( blockType.attributes, 'greydAnim' ) ) {

				const greydAnim    = _.get( props.attributes, 'greydAnim' );

				/**
				 * @since 1.7.2 Add greyd/is-position-variation context
				 */
				const parentSticky = _.get( props, 'context["greyd/is-position-variation"]' ) === 'sticky';
				const isSticky     = _.has( props.attributes, 'variation' ) && props.attributes.variation === 'sticky';

				holdsChange = holdsChange || !_.isEmpty( greydAnim );
				controls.push(
					el( greyd.components.AnimationControl, {
						label: _.has( blockType.attributes, 'greydBackgroundAnim' ) ? __( 'Block', 'greyd_hub' ) : '',
						value: greydAnim,
						onChange: ( val ) => {
							props.setAttributes( { greydAnim: val } );
						},
						/**
						 * @since 1.7.2 Add greyd/is-position-variation context
						 */
						events: {
							parentSticky: parentSticky,
							isSticky: isSticky
						}
					} )
				);
			}

			// greydBackgroundAnim support
			if ( _.has( blockType.attributes, 'greydBackgroundAnim' ) ) {

				const greydBackgroundAnim = _.get( props.attributes, 'greydBackgroundAnim' );
				holdsChange = holdsChange || !_.isEmpty( greydBackgroundAnim );

				// content box & columns
				let isBackgroundSet = _.has( props.attributes, 'background' ) && _.has( props.attributes.background, 'type' ) && !_.isEmpty( props.attributes.background.type );
				if ( !isBackgroundSet ) {
					// cover block
					isBackgroundSet = _.has( props.attributes, 'backgroundType' ) && !_.isEmpty( props.attributes.backgroundType );
					if ( ! isBackgroundSet ) {
						isBackgroundSet = _.has( props.attributes, 'useFeaturedImage' ) && props.attributes.useFeaturedImage;
					}
				}

				controls.push(
					el( isBackgroundSet ? wp.element.Fragment : wp.components.Disabled , { style: { opacity: 0.5 } }, [
						el( greyd.components.AnimationControl, {
							label: __( "Background", 'greyd_hub' ),
							value: greydBackgroundAnim,
							onChange: ( val ) => {
								props.setAttributes( { greydBackgroundAnim: val } );
							}
						} )
					] ),

					// if props?.attributes?.hasParallax -> show warning for 'transform' animations
					(
						props?.attributes?.hasParallax
						? el( wp.components.Notice, {
							status: 'warning',
							isDismissible: false
						}, __( "Parallax backgrounds are not compatible with most 'transform' animations. Please use use other animations or remove the parallax effect.", 'greyd_hub' ) )
						: null
					)
				);
			}

			// not supported
			if ( controls.length === 0 ) {
				return el( BlockEdit, props );
			}

			// render
			return el( wp.element.Fragment, {}, [
				el( BlockEdit, {...props } ),
				el( wp.blockEditor.InspectorControls, {}, [
					el( greyd.components.AdvancedPanelBody, {
						title: __( 'Animation', 'greyd_hub' ),
						holdsChange: holdsChange,
						initialOpen: false
					}, controls )
				] ),
			] );
		};

	}, 'editBlockHook' );

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'greyd/hook/animations/edit',
		addAnimationControl,
		99
	);

} )( window.wp );