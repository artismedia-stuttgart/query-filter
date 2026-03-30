/**
 * Editor Script for WooCommerce Blocks.
 */
( function( wp ) {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;

	// woocommerce
	if (
		greyd.data.post_id == greyd.woo.data.shop_page_id ||
		( greyd.data.post_type == "dynamic_template" && greyd.data.template_type == "woo" )
	) {
		wp.blocks.registerBlockType( 'greyd/woocontent', {
			title: __("Dynamic WooCommerce Content", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("Dynamic WooCommerce Content", 'greyd_hub')),
						),
					] ),
				] )
			},

			save: function( props ) {
				return "WOO_CONTENT"
			}
		} );
	}

	if ( greyd.data.post_id == greyd.woo.data.my_account_page_id) {
		wp.blocks.registerBlockType( 'greyd/woo-account', {
			title: __("My Account", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce: My Account", 'greyd_hub')),
						),
					] ),
				] )
			},

			save: function( props ) {
				return "[woocommerce_my_account]"
			}
		} );
	}

	if ( greyd.data.post_id == greyd.woo.data.checkout_page_id) {
		wp.blocks.registerBlockType( 'greyd/woo-checkout', {
			title: __("Checkout", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce: Checkout", 'greyd_hub')),
						),
					] ),
				] )
			},

			save: function( props ) {
				return "[woocommerce_checkout]"
			}
		} );
	}

	if ( greyd.data.post_id == greyd.woo.data.cart_page_id) {
		wp.blocks.registerBlockType( 'greyd/woo-cart', {
			title: __("Shopping Cart", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce: Cart", 'greyd_hub')),
						),
					] ),
				] )
			},

			save: function( props ) {
				return "[woocommerce_cart]"
			}
		} );
	}

	// woocommerce-germanized
	if (
		typeof greyd.data.plugins === 'object' &&
		greyd.data.plugins !== null &&
		Object.values(greyd.data.plugins).includes("woocommerce-germanized/woocommerce-germanized.php")
	) {
		wp.blocks.registerBlockType( 'greyd/gzd-payment-methods-info', {
			title: __("Payment Method Info", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo_de'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce Germanized: Payment Method Info", 'greyd_hub')),
						),
					] ),
				] )
			},
	
			save: function( props ) {
				return "[payment_methods_info]"
			}
		} );
		wp.blocks.registerBlockType( 'greyd/gzd-complaints', {
			title: __("Dispute Resolution", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo_de'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce Germanized: Dispute Resolution", 'greyd_hub')),
						),
					] ),
				] )
			},
	
			save: function( props ) {
				return "[gzd_complaints]"
			}
		} );
	
		wp.blocks.registerBlockType( 'greyd/gzd-revocation-form', {
			title: __("Revocation Form", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo_de'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce Germanized: Revocation Form", 'greyd_hub')),
						),
					] ),
				] )
			},
	
			save: function( props ) {
				return "[revocation_form]"
			}
		} );
		wp.blocks.registerBlockType( 'greyd/gzd-vat-info', {
			title: __("Tax Info", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo_de'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce Germanized: Tax Info", 'greyd_hub')),
						),
					] ),
				] )
			},
	
			save: function( props ) {
				return "[gzd_vat_info]"
			}
		} );
	
		wp.blocks.registerBlockType( 'greyd/gzd-sale-info', {
			title: __("Sale Info", 'greyd_hub'),
			icon: greyd.tools.getBlockIcon('woo_de'),
			category: 'greyd-blocks',
			edit: function( props ) {
				// preview
				return el( 'div', { className: props.className+' preview-info-wrapper' }, [
					el( 'div', { className: 'preview-info-tag flex' }, [
						greyd.tools.getBlockIcon('woo'),
						el( 'div', { className: 'preview-info-title' },
							el( 'strong', {}, __("WooCommerce Germanized: Tax Info", 'greyd_hub')),
						),
					] ),
				] )
			},
	
			save: function( props ) {
				return "[gzd_sale_info]"
			}
		} );
	}

} )( window.wp );