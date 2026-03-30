import { store, getElement, getContext } from '@wordpress/interactivity';

const updateURL = async ( action, value, name ) => {
	const url = new URL( action );
	if ( value || name === 's' ) {
		url.searchParams.set( name, value );
	} else {
		url.searchParams.delete( name );
	}
	const { actions } = await import( '@wordpress/interactivity-router' );
	await actions.navigate( url.toString() );
};

store( 'query-filter', {
	state: {
		searchValue: '',
	},
	actions: {
		toggleSorting() {
			const context = getContext();
			context.isOpen = ! context.isOpen;
		},
		closeSorting( e ) {
			const context = getContext();
			const element = getElement();

			if (
				context.isOpen &&
				element?.ref &&
				! element.ref.contains( e.target )
			) {
				context.isOpen = false;
			}
		},
		*navigate( e ) {
			e.preventDefault();
			const { actions } = yield import(
				'@wordpress/interactivity-router'
			);

			const el = e.currentTarget || e.target;
			const url =
				el?.href ||
				el?.dataset?.href ||
				el?.closest( 'a' )?.href ||
				el?.closest( 'button' )?.dataset?.href ||
				el?.closest( '[data-href]' )?.dataset?.href;

			if ( ! url ) {
				return;
			}

			// Try to close dropdown on navigate.
			try {
				const context = getContext();
				if ( context && typeof context.isOpen !== 'undefined' ) {
					context.isOpen = false;
				}
			} catch ( err ) {}

			yield actions.navigate( url );
		},
		*search( e ) {
			e.preventDefault();
			const { ref } = getElement();
			let action, name, value;
			if ( ref.tagName === 'FORM' ) {
				const input = ref.querySelector( 'input[type="search"]' );
				action = ref.action;
				name = input.name;
				value = input.value;
			} else {
				action = ref.closest( 'form' ).action;
				name = ref.name;
				value = ref.value;
			}

			const { state } = store( 'query-filter' );
			if ( value === state.searchValue ) return;

			state.searchValue = value;

			yield updateURL( action, value, name );
		},
	},
} );
