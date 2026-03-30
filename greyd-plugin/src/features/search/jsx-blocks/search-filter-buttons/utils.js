const { has, isEmpty } = lodash;
const { __ } = wp.i18n;

const isButton = ( atts ) => {
	return atts.className?.indexOf( 'is-style-prim' ) > -1 
		|| atts.className?.indexOf( 'is-style-sec' ) > -1 
		|| atts.className?.indexOf( 'is-style-trd' ) > -1;
}

const isTabs = ( atts ) => {
	return atts.className?.indexOf( 'is-style-tabs' ) > -1;
}

const isChips = ( atts ) => {
	return atts.className?.indexOf( 'is-style-chips' ) > -1;
}

const getFilterOptions = ( postType ) => {

	let filterOptions = [
		{ label: __( "Please select", 'greyd_hub' ), value: '' }
	];
	if ( !isEmpty( postType ) ) {

		if ( postType === 'post' ) {
			let postTypeConfig = greyd.data.post_types.find( config => {
				return config.slug === postType;
			} );
			if ( postTypeConfig ) {
				filterOptions = [
					...filterOptions,
					...postTypeConfig.taxes.map( taxonomy => {
						return { label: taxonomy.title, value: taxonomy.slug };
					} )
				];
			} else {
				filterOptions.push( {
					label: __( 'Category' ),
					value: 'category'
				} );
				filterOptions.push( {
					label: __( 'Tag' ),
					value: 'post_tag'
				} );
			}
		}
		else {
			let postTypeConfig = greyd.data.post_types.find( config => {
				return config.slug === postType;
			} );
			if ( postTypeConfig ) {
				filterOptions = [
					...filterOptions,
					...postTypeConfig.taxes.map( taxonomy => {
						return { label: taxonomy.title, value: taxonomy.slug };
					} )
				];
			}
		}
	}
	return filterOptions;
}

const getTerms = ( taxonomy, postType ) => {
	let terms = [];
	if ( !isEmpty( taxonomy ) ) {
		let taxes = has( greyd.data?.all_taxes, postType ) ? greyd.data.all_taxes[postType] : null;
		let tax   = taxes ? taxes.find( tax => tax.slug === taxonomy ) : null;

		if ( tax ) {
			terms = tax.values;
		}
	}
	return terms;
}

export { getFilterOptions, isButton, isTabs, isChips, getTerms };