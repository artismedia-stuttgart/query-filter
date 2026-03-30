document.addEventListener( "DOMContentLoaded", function () {

	const tabBlocks = [].slice.call( document.querySelectorAll( ".wp-block-greyd-tabs" ) );

	tabBlocks.forEach( tabBlock => {

		const tabs = [].slice.call( tabBlock.querySelectorAll( '.greyd_tab' ) );
		const panels = [].slice.call( tabBlock.querySelectorAll( '.panel' ) );

		if ( !tabs.length || !panels.length ) return;

		tabs.forEach( ( tab, index ) => {

			// set active state on first element
			if ( index === 0 ) {
				panels[ index ].classList.add( 'is-active' );
				tab.classList.add( 'is-active' );
			}

			// control with mouse
			tab.addEventListener( 'click', function ( e ) {
				handleTabSwitch( tabs, panels, index );
			} );

			// control with arrow keys
			tab.addEventListener( 'keydown', function ( e ) {
				let flag = false;
				switch ( e.key ) {
					case 'ArrowRight':
						handleTabSwitch( tabs, panels, index + 1 );
						if ( index === tabs.length - 1 ) {
							handleTabSwitch( tabs, panels, 0 );
						}
						flag = true;
						break;

					case 'ArrowLeft':
						handleTabSwitch( tabs, panels, index - 1 );
						if ( index === 0 ) {
							handleTabSwitch( tabs, panels, tabs.length - 1 );
						}
						flag = true;
						break;

					case 'Home':
						handleTabSwitch( tabs, panels, 0);
						flag = true;
						break;
				
					case 'End':
						handleTabSwitch( tabs, panels, tabs.length - 1 );
						flag = true;
						break;

					default:
						break;
				}
				if ( flag ) {
					e.stopPropagation();
					e.preventDefault();
				}
			} );
		} );
	} );


	const handleTabSwitch = ( tabs, panels, index ) => {

		const wrapper = tabs[ 0 ].parentNode;
		const activeTab = wrapper.querySelector( "[aria-selected=true]" );
		const activeTabIndex = tabs.indexOf( activeTab );

		// console.log( activeTab, activeTabIndex, index );

		if ( index < 0 || index > tabs.length || !tabs[ index ] || !panels[ index ] || index === activeTabIndex ) return;

		tabs[ activeTabIndex ].setAttribute( 'aria-selected', 'false' );
		tabs[ activeTabIndex ].setAttribute( 'tabindex', "-1" );
		tabs[ activeTabIndex ].classList.remove( 'is-active' );
		panels[ activeTabIndex ].classList.remove( 'is-active' );

		tabs[ index ].setAttribute( 'aria-selected', 'true' );
		tabs[ index ].removeAttribute( 'tabindex' );
		tabs[ index ].classList.add( 'is-active' );
		panels[ index ].classList.add( 'is-active' );

		tabs[ index ].focus();

		greyd?.query?.slider?.calc();
	};
} );