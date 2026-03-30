/**
 * Live Filter features frontend script.
 */
document.addEventListener( "DOMContentLoaded", function () {

	const searches = document.querySelectorAll( ".greyd-search-form" );

	searches.forEach( search => {

		const sortingDropdown = search.querySelector( ".sorting select" );

		if ( ! sortingDropdown ) return;

		const hiddenOrder     = search.querySelector( "input[name='order']" ) || {};
		const hiddenOrderBy   = search.querySelector( "input[name='orderby']" ) || {};

		// default values
		if (sortingDropdown.querySelector("option[value^='relevance']")) {
			if (hiddenOrderBy) hiddenOrderBy.value = "relevance";
			if (hiddenOrder) hiddenOrder.value = "desc";
		} else {
			if (hiddenOrderBy) hiddenOrderBy.value = "date";
			if (hiddenOrder) hiddenOrder.value = "asc";
		}


		sortingDropdown.addEventListener( "change", function () {

			let values = [];

			if ( this.value.includes( "_" ) ) {
				values = this.value.split( "_" );
			} else {
				values = this.value.split( " " );
			}

			hiddenOrderBy.value = values[ 0 ];
			hiddenOrder.value = values[ 1 ];
		} );
	} );
} );