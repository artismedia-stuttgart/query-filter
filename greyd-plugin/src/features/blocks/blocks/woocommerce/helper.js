/**
 * Editor Helper Script for WooCommerce Notices.
 */
( function( wp ) {

	var { __ } = wp.i18n;

	var wizardURL = greyd.woo.data.wizard_url

	if (greyd.data.post_id == greyd.woo.data.shop_page_id) {
		wp.data.dispatch( 'core/notices' ).createNotice(
			'info', // Can be one of: success, info, warning, error.
			__("This page is used by default for displaying products and all shop overviews. If you want more control over each page, you can create special WooCommerce templates under Templates > New Template.", "greyd_hub"), 
			{
				isDismissible: true, 
				actions: [
					{
						url: wizardURL,
						label: 'Template erstellen',
					},
				],
			}
		);
	}
	else if (greyd.data.post_id == greyd.woo.data.my_account_page_id) {
		wp.data.dispatch( 'core/notices' ).createNotice(
			'info', // Can be one of: success, info, warning, error.
			__("The individual subitems and content of the account area are created automatically. However, you can create different templates under \"Templates > New Template\" to be displayed above or below the content in the respective sub pages. Select \"My Account\" as the type of template and set the desired position.", "greyd_hub"), 
			{
				isDismissible: true, 
				actions: [
					{
						url: wizardURL,
						label: 'Template erstellen',
					},
				],
			}
		);
	}

} )( window.wp );