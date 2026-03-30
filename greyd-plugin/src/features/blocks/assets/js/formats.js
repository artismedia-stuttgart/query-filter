/**
 * Register block editor RichText formats.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;

	wp.richText.registerFormatType( 'greyd/versal', {
		title: __("Uppercase", 'greyd_hub'),
		tagName: 'span',
		className: 'is-upper',
		edit: function ( props ) {
			return el( wp.blockEditor.RichTextToolbarButton, {
				icon: el( 'svg', {}, el( 'path', { 
					d: "M12.9 6h-2l-4 11h1.9l1.1-3h4.2l1.1 3h1.9L12.9 6zm-2.5 6.5l1.5-4.9 1.7 4.9h-3.2z"
				} ) ),
				title: __("Uppercase", 'greyd_hub'),
				onClick: function() {
					// console.log( 'toggle versal' );
					props.onChange(
						wp.richText.toggleFormat( props.value, {
							type: 'greyd/versal',
						} )
					);
				},
				isActive: props.isActive,
		} ) },
	} );

} )( window.wp );