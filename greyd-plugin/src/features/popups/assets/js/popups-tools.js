/**
 * Backend Block Editor Utils & Tools for Popups.
 * 
 * This file is loaded in the editor only.
 */

greyd.tools.popup = new function() {


	/**
	 * =================================================================
	 *                          Utils
	 * =================================================================
	 */

	this.hex2rgba = function(hex) {
		// Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
		hex = hex.replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i, function(m, r, g, b) {
			return r + r + g + g + b + b;
		});
		// decode hex
		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16),
			a: 1
		} : false;
	}

	this.rgb2rgba = function(rgb) {
		// console.log(rgb);
		var tmp = rgb.split('(');
		tmp = tmp[1].split(')');
		tmp = tmp[0].split(',');
		if (tmp.length == 3) return { r: parseInt(tmp[0]), g: parseInt(tmp[1]), b: parseInt(tmp[2]), a: 1 };
		if (tmp.length == 4) return { r: parseInt(tmp[0]), g: parseInt(tmp[1]), b: parseInt(tmp[2]), a: parseFloat(tmp[3]) };
		return false;
	}

	this.rgb2hex = function(rgb) {
		var { r, g, b } = rgb;
		return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
	}


	/**
	 * =================================================================
	 *                          Global Styles
	 * =================================================================
	 */

	// this.globalStyles = { id: null, defaults: null, variations: null };
	// this.loadGlobalStyles = function() {
	// 	// console.log( "loadGlobalStyles" );
	// 	this.globalStyles = {
	// 		id: wp.data.select("core").__experimentalGetCurrentGlobalStylesId(),
	// 		defaults: wp.data.select("core").__experimentalGetCurrentThemeBaseGlobalStyles(),
	// 		variations: wp.data.select("core").__experimentalGetCurrentThemeGlobalStylesVariations(),
	// 	};
	// };
	this.getGlobalStylesColors = function() {
		var colors = { theme: [], default: [] };
		greyd.data.getColors().forEach( (color) => {
			if ( color.name == 'standard' ) colors['default'] = color.colors;
			else colors[color.name] = color.colors;
		} );
		return colors;
		// // check if global styles are loaded
		// if (this.globalStyles.defaults == null) {
		// 	this.loadGlobalStyles();
		// 	return { theme: [], default: [] };
		// }
		// // console.log(this.globalStyles);
		// // return colors
		// if (typeof this.globalStyles.defaults.settings.color.palette === 'undefined') 
		// 	return { theme: [], default: [] };
		// else return JSON.parse(JSON.stringify(this.globalStyles.defaults.settings.color.palette));
	}
	this.getGlobalStylesGradients = function() {
		var gradients = { theme: [], default: [] };
		greyd.data.getGradients().forEach( (gradient) => {
			if ( gradient.name == 'standard' ) gradients['default'] = gradient.gradients;
			else gradients[gradient.name] = gradient.gradients;
		} );
		return gradients;
		// if (this.globalStyles.defaults == null) {
		// 	this.loadGlobalStyles();
		// 	return { theme: [], default: [] };
		// }
		// // console.log(this.globalStyles);
		// // return gradients
		// if (typeof this.globalStyles.defaults.settings.color.gradients === 'undefined') 
		// 	return { theme: [], default: [] };
		// else return JSON.parse(JSON.stringify(this.globalStyles.defaults.settings.color.gradients));
	}


	/**
	 * =================================================================
	 *                          Colors
	 * =================================================================
	 */

	this.getColors = function() {
		var colors = this.getGlobalStylesColors();
		var allColors = [
			{ name: "theme", colors: colors.theme },
			{ name: "standard", colors: colors.default }
		];
		if ( colors.custom ) allColors.push( { name: "custom", colors: colors.custom } );
		return allColors;
	};

	this.getColorValue = function(color) {
		var colors = this.getGlobalStylesColors();
		var slug = typeof color === "undefined" ? "" : color.replace("var(--wp--preset--color--", "").replace(")", "");
		colors.theme.forEach((element) => {
			if (slug == element.slug) {
				color = element.color;
			}
		});
		colors.default.forEach((element) => {
			if (slug == element.slug) {
				color = element.color;
			}
		});
		if ( colors.custom ) colors.custom.forEach( ( element ) => {
			if ( slug == element.slug ) {
				color = element.color;
			}
		} );
		return color;
	}

	this.getColorVariable = function(color) {
		var colors = this.getGlobalStylesColors();
		colors.theme.forEach((element) => {
			if (color == element.color) {
				color = "var(--wp--preset--color--"+element.slug+")";
			}
		});
		colors.default.forEach((element) => {
			if (color == element.color) {
				color = "var(--wp--preset--color--"+element.slug+")";
			}
		});
		if ( colors.custom ) colors.custom.forEach( ( element ) => {
			if ( color == element.color ) {
				color = "var(--wp--preset--color--" + element.slug + ")";
			}
		} );
		return color;
	}


	/**
	 * =================================================================
	 *                          Gradients
	 * =================================================================
	 */

	this.getGradients = function() {
		var gradients = this.getGlobalStylesGradients();
		var gradients_theme = JSON.parse(JSON.stringify(gradients.theme));
		gradients_theme.forEach((element, index) => {
			// vars to colors
			gradients_theme[index].gradient = this.getGradientValue(element.gradient);
		});
		var allGradients = [
			{ name: "theme", gradients: gradients_theme },
			{ name: "standard", gradients: gradients.default }
		];
		if ( gradients.custom ) allGradients.push( { name: "custom", gradients: gradients.custom } );
		return allGradients;
	}

	this.getGradientValue = function(gradient) {
		var colors = this.getGlobalStylesColors();
		colors.theme.forEach((element) => {
			var color = "var(--wp--preset--color--"+element.slug+")";
			gradient = gradient.split(color).join(element.color);
		});
		colors.default.forEach((element) => {
			var color = "var(--wp--preset--color--"+element.slug+")";
			gradient = gradient.split(color).join(element.color);
		});
		return gradient;
	}

	this.getGradientVariable = function(gradient) {
		var colors = this.getGlobalStylesColors();
		colors.theme.forEach((element) => {
			var color = "var(--wp--preset--color--"+element.slug+")";
			gradient = gradient.split(element.color).join(color);
		});
		colors.default.forEach((element) => {
			var color = "var(--wp--preset--color--"+element.slug+")";
			gradient = gradient.split(element.color).join(color);
		});

		return gradient;
	}

	// console.log("popup editor tools loaded.");

};
