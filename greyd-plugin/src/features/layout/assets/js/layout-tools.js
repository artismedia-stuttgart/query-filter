/**
 * Backend Block Editor Utils & Tools for Layout Blocks.
 *
 * This file is loaded in the editor only.
 */

greyd.tools.layout = new function() {

	var { createElement: el } = wp.element;
	var { __ } = wp.i18n;
	var _ = lodash;

	this.getBackgroundDefaults = function(sets={}) {
		if (!_.has(sets, 'overlay')) sets.overlay = true;
		if (!_.has(sets, 'pattern')) sets.pattern = true;
		if (!_.has(sets, 'seperator')) sets.seperator = true;
		// make defaults
		var defaults = {
			background: {
				type: "",
				opacity: 100,
				scroll: { type: "scroll", parallax: 30, parallax_mobile: false },
				image: { id: -1, url: "", size: "cover", repeat: "no-repeat", position: "center center" },
				anim: { id: -1, url: "", anim: "auto", width: "auto", position: 'top_center' },
				video: { url: '', aspect: '16/9' },
			}
		};
		if (sets.overlay) defaults.overlay = { type: "", opacity: 100, color: "", gradient: "" };
		if (sets.pattern) defaults.pattern = { type: "", id: -1, url: "", opacity: 50, size: "10px", scroll: "scroll" };
		if (sets.seperator) defaults.seperator = { type: "", height: "60px", position: "top", zposition: "0", opacity: 100, color: "" };
		return defaults;
	}
	this.cleanBackgroundvalues = function(background) {
		// cleaning
		if (background.type != 'image' && _.has(background, 'image')) delete background.image;
		if (background.type != 'video' && _.has(background, 'video')) delete background.video;
		if (background.type != 'animation' && _.has(background, 'anim')) delete background.anim;
		if (background.type == '') {
			if (_.has(background, 'opacity')) delete background.opacity;
			if (_.has(background, 'scroll')) delete background.scroll;
			delete background.type;
		}
		if (_.has(background, 'overlay') && (!_.has(background.overlay, 'type') || background.overlay.type == '')) delete background.overlay;
		if (_.has(background, 'pattern') && (!_.has(background.pattern, 'type') || background.pattern.type == '')) delete background.pattern;
		if (_.has(background, 'seperator') && (!_.has(background.seperator, 'type') || background.seperator.type == '')) delete background.seperator;
		// if (isEmpty(background)) background = undefined;
		return background;
	}


	/**
	 * background rendering inside the editor
	 */

	this.getPattern = function(type) {
		// console.log("editor script getPattern");
		var patterns = {
			"pattern_01": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAADklEQVQIW2NggID/cAIADwMB/zSts64AAAAASUVORK5CYII=",
			"pattern_02": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAE0lEQVR4AWMAgv9gjAMgFAxrAADDbwP9sI02swAAAABJRU5ErkJggg==",
			"pattern_03": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAEklEQVR4AWMAgs0YGEygAQoFAc1BBkwbUqhOAAAAAElFTkSuQmCC",
			"pattern_04": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAEklEQVR4AWMYKuA/jMariGwTACH1BPyE5LjvAAAAAElFTkSuQmCC",
			"pattern_05": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAQAAAAziH6sAAAADklEQVR4AWNg+M/AwAAABAIBAMMz9ccAAAAASUVORK5CYII=",
			"pattern_06": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAABAQMAAADO7O3JAAAABlBMVEUAAAAAAAClZ7nPAAAAAXRSTlMAQObYZgAAAApJREFUCNdjcAAAAEIAQYO57K0AAAAASUVORK5CYII=",
			"pattern_07": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAECAYAAABGM/VAAAAADklEQVR4AWMgBP6TrgIARBAB/8Id9pYAAAAASUVORK5CYII=",
			"pattern_08": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAADklEQVR4AWMAgv8MowAABBMBANYOezsAAAAASUVORK5CYII=",
			"pattern_09": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAD0lEQVQIW2NgYGD4z4AMAA4EAQAMvbGxAAAAAElFTkSuQmCC",
		};
		return patterns[type];
	}

	this.getSeperators = function() {
		let color, height = '';
		const seperators = {
			// triangles
			triangle_seperator: {
				slug: "triangle_seperator",
				title: __( "Triangle", 'greyd_hub' ),
				svg: {
					atts: { fill: color, width: "100%", height: height, viewBox: "0 0 0.156661 0.1" },
					content: [
						{ type: 'polygon', atts: { points: "0.156661,3.93701e-006 0.156661,0.000429134 0.117665,0.05 0.0783307,0.0999961 0.0389961,0.05 -0,0.000429134 -0,3.93701e-006 0.0783307,3.93701e-006 " } }
					]
				}
			},
			triangle_big_seperator: {
				slug: "triangle_big_seperator",
				title: __( "Big triangle", 'greyd_hub' ),
				svg: {
					atts: { fill: color, width: "100%", height: height, viewBox: "0 0 4.66666 0.333331", preserveAspectRatio: "none" },
					content: [
						{ type: 'path', atts: { d: "M-0 0.333331l4.66666 0 0 -3.93701e-006 -2.33333 0 -2.33333 0 0 3.93701e-006zm0 -0.333331l4.66666 0 0 0.166661 -4.66666 0 0 -0.166661zm4.66666 0.332618l0 -0.165953 -4.66666 0 0 0.165953 1.16162 -0.0826181 1.17171 -0.0833228 1.17171 0.0833228 1.16162 0.0826181z" } }
					]
				}
			},
			triangle_big_left_seperator: {
				slug: "triangle_big_left_seperator",
				title: __( "Big triangle, left", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_y", fill: color, width: "100%", height: height, viewBox: "0 0 2000 90", preserveAspectRatio: "none" },
					content: [
						{ type: 'polygon', atts: { points: "535.084,64.886 0,0 0,90 2000,90 2000,0 " } }
					]
				}
			},
			triangle_big_right_seperator: {
				slug: "triangle_big_right_seperator",
				title: __( "Big triangle, right", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_xy", fill: color, width: "100%", height: height, viewBox: "0 0 2000 90", preserveAspectRatio: "none" },
					content: [
						{ type: 'polygon', atts: { points: "535.084,64.886 0,0 0,90 2000,90 2000,0 " } }
					]
				}
			},
			// curves
			circle_seperator: {
				slug: "circle_seperator",
				title: __( "Half circle", 'greyd_hub' ),
				svg: {
					atts: { fill: color, width: "100%", height: height, viewBox: "0 0 0.2 0.1" },
					content: [
						{ type: 'path', atts: { d: "M0.200004 0c-3.93701e-006,0.0552205 -0.0447795,0.1 -0.100004,0.1 -0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1l0.200004 0z" } }
					]
				}
			},
			curve_seperator: {
				slug: "curve_seperator",
				title: __( "Curve center", 'greyd_hub' ),
				svg: {
					atts: { fill: color, width: "100%", height: height, viewBox: "0 0 4.66666 0.333331", preserveAspectRatio: "none" },
					content: [
						{ type: 'path', atts: { d: "M4.66666 0l0 7.87402e-006 -3.93701e-006 0c0,0.0920315 -1.04489,0.166665 -2.33333,0.166665 -1.28844,0 -2.33333,-0.0746339 -2.33333,-0.166665l-3.93701e-006 0 0 -7.87402e-006 4.66666 0z" } }
					]
				}
			},
			curve_left_seperator: {
				slug: "curve_left_seperator",
				title: __( "Curve left", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_y", fill: color, width: "100%", height: height, viewBox: "0 0 4.66666 0.333331", preserveAspectRatio: "none" },
					content: [
						{ type: 'path', atts: { d: "M-7.87402e-006 0.0148858l0.00234646 0c0.052689,0.0154094 0.554437,0.154539 1.51807,0.166524l0.267925 0c0.0227165,-0.00026378 0.0456102,-0.000582677 0.0687992,-0.001 1.1559,-0.0208465 2.34191,-0.147224 2.79148,-0.165524l0.0180591 0 0 0.166661 -7.87402e-006 0 0 0.151783 -4.66666 0 0 -0.151783 -7.87402e-006 0 0 -0.166661z" } }
					]
				}
			},
			curve_right_seperator: {
				slug: "curve_right_seperator",
				title: __( "Curve right", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_xy", fill: color, width: "100%", height: height, viewBox: "0 0 4.66666 0.333331", preserveAspectRatio: "none" },
					content: [
						{ type: 'path', atts: { d: "M-7.87402e-006 0.0148858l0.00234646 0c0.052689,0.0154094 0.554437,0.154539 1.51807,0.166524l0.267925 0c0.0227165,-0.00026378 0.0456102,-0.000582677 0.0687992,-0.001 1.1559,-0.0208465 2.34191,-0.147224 2.79148,-0.165524l0.0180591 0 0 0.166661 -7.87402e-006 0 0 0.151783 -4.66666 0 0 -0.151783 -7.87402e-006 0 0 -0.166661z" } }
					]
				}
			},
			// cuts
			tilt_left_seperator: {
				slug: "tilt_left_seperator",
				title: __( "Tilt left", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_xy", fill: color, width: "100%", height: height, viewBox: "0 0 4 0.266661", preserveAspectRatio: "none" },
					content: [
						{ type: 'polygon', atts: { points: "4,0 4,0.266661 -0,0.266661 " } }
					]
				}
			},
			tilt_right_seperator: {
				slug: "tilt_right_seperator",
				title: __( "Tilt right", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_y", fill: color, width: "100%", height: height, viewBox: "0 0 4 0.266661", preserveAspectRatio: "none" },
					content: [
						{ type: 'polygon', atts: { points: "4,0 4,0.266661 -0,0.266661 " } }
					]
				}
			},
			// fancy
			waves_seperator: {
				slug: "waves_seperator",
				title: __( "Waves", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_y", fill: color, width: "100%", height: height, viewBox: "0 0 6 0.1", preserveAspectRatio: "none" },
					content: [
						{ type: 'path', atts: { d: "M0.199945 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c-0.0541102,0 -0.0981929,-0.0430079 -0.0999409,-0.0967008l0 0.0967008 0.0999409 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm2.00004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm-0.1 0.1l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c-0.0552126,0 -0.0999921,-0.0447795 -0.1,-0.1 -7.87402e-006,0.0552205 -0.0447874,0.1 -0.1,0.1l0.2 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm-0.400008 0l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1 3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1zm1.90004 -0.1c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.200004 0c7.87402e-006,0.0552205 0.0447874,0.1 0.1,0.1l-0.2 0c0.0552126,0 0.0999921,-0.0447795 0.1,-0.1zm0.200004 0c3.93701e-006,0.0552205 0.0447795,0.1 0.100004,0.1l-0.200008 0c0.0552244,0 0.1,-0.0447795 0.100004,-0.1zm0.199945 0.00329921l0 0.0967008 -0.0999409 0c0.0541102,0 0.0981929,-0.0430079 0.0999409,-0.0967008z" } }
					]
				}
			},
			clouds_seperator: {
				slug: 'clouds_seperator',
				title: __( "Clouds", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_y", fill: color, width: "100%", height: height, viewBox: "0 0 2.23333 0.1", preserveAspectRatio: "none" },
					content: [
						{ type: 'path', atts: { d: "M2.23281 0.0372047c0,0 -0.0261929,-0.000389764 -0.0423307,-0.00584252 0,0 -0.0356181,0.0278268 -0.0865354,0.0212205 0,0 -0.0347835,-0.00524803 -0.0579094,-0.0283701 0,0 -0.0334252,0.0112677 -0.0773425,-0.00116929 0,0 -0.0590787,0.0524724 -0.141472,0.000779528 0,0 -0.0288189,0.0189291 -0.0762362,0.0111535 -0.00458268,0.0141024 -0.0150945,0.040122 -0.0656811,0.0432598 -0.0505866,0.0031378 -0.076126,-0.0226614 -0.0808425,-0.0308228 -0.00806299,0.000854331 -0.0819961,0.0186969 -0.111488,-0.022815 -0.0076378,0.0114843 -0.059185,0.0252598 -0.083563,-0.000385827 -0.0295945,0.0508661 -0.111996,0.0664843 -0.153752,0.019 -0.0179843,0.00227559 -0.0571181,0.00573622 -0.0732795,-0.0152953 -0.027748,0.0419646 -0.110602,0.0366654 -0.138701,0.00688189 0,0 -0.0771732,0.0395709 -0.116598,-0.0147677 0,0 -0.0497598,0.02 -0.0773346,-0.00166929 0,0 -0.0479646,0.0302756 -0.0998937,0.00944094 0,0 -0.0252638,0.0107874 -0.0839488,0.00884646 0,0 -0.046252,0.000775591 -0.0734567,-0.0237087 0,0 -0.046252,0.0101024 -0.0769567,-0.00116929 0,0 -0.0450827,0.0314843 -0.118543,0.0108858 0,0 -0.0715118,0.0609803 -0.144579,0.00423228 0,0 -0.0385787,0.00770079 -0.0646299,0.000102362 0,0 -0.0387559,0.0432205 -0.125039,0.0206811 0,0 -0.0324409,0.0181024 -0.0621457,0.0111063l-3.93701e-005 0.0412205 2.2323 0 0 -0.0627953z" } }
					]
				}
			},
			multi_triangle_seperator: {
				slug: 'multi_triangle_seperator',
				title: __( "Multi triangle", 'greyd_hub' ),
				svg: {
					atts: { className: "svg_mirror_y", fill: color, width: "100%", height: height, viewBox: "0 0 100 100", preserveAspectRatio: "none" },
					content: [
						{ type: 'path', atts: { d: "M0 0 L50 50 L0 100", opacity: "0.1" } },		// large left
						{ type: 'path', atts: { d: "M100 0 L50 50 L100 100", opacity: "0.1" } },	// large right
						{ type: 'path', atts: { d: "M0 100 L50 50 L0 33.3", opacity: "0.3" } },		// medium left
						{ type: 'path', atts: { d: "M100 100 L50 50 L100 33.3", opacity: "0.3" } },	// medium right
						{ type: 'path', atts: { d: "M0 100 L50 50 L0 66.6", opacity: "0.5" } },		// small left
						{ type: 'path', atts: { d: "M100 100 L50 50 L100 66.6", opacity: "0.5" } },	// small right
						{ type: 'path', atts: { d: "M0 99.9 L50 49.9 L100 99.9 L0 99.9" } },
						{ type: 'path', atts: { d: "M48 52 L50 49 L52 52 L48 52" } },
					]
				}
			},
			zigzag_seperator: {
				slug: 'zigzag_seperator',
				title: __( "Zig zag", 'greyd_hub' ),
				svg: {
					atts: { fill: color, width: "100%", height: height, overflow: "hidden" },
					content: []
				}
			},
		}

		/**
		 * @filter greyd_editor_getSeperators
		 */
		return wp.hooks.applyFilters( 'greyd_editor_getSeperators', seperators );
	}

	this.getSeperator = function(type, height, color) {

		// get color
		color = greyd.tools.getColorValue(color);

		// get seperators
		const seperators = greyd.tools.layout.getSeperators();

		if ( !_.has(seperators, type) ) {
			return false;
		}

		// get svg
		var sep = seperators[type]['svg'];
		sep.atts.fill = color;
		sep.atts.height = height;

		// push to inner
		var inner = [];
		for (var i=0; i<sep["content"].length; i++) {
			inner.push(el( sep["content"][i]["type"], sep["content"][i]["atts"] ));
		}

		// adjust for zigzag
		if (type == 'zigzag_seperator') {
			var id = greyd.tools.generateRandomID();
			inner = el( 'svg', { width: "100%", height: "100%", viewBox: "-0.675 0 0.1 1", preserveAspectRatio: "xMidYMid meet", overflow: "visible" }, [
				el( 'pattern', { id: id, viewBox: "0 0 100 80", height: "1", width: "1.25", patternUnits: "userSpaceOnUse" }, [
					el( 'svg', { width: "100", height: "80", viewBox: "0 0 18 14.4" }, [
						el( 'polygon', { points: "8.98762301 0 0 9.12771969 0 14.519983 9 5.40479869 18 14.519983 18 9.12771969" } )
					] )
				] ),
				el( 'rect', { fill: "url(#"+id+")", x: "-500", y: "0", width: "1000", height: "1" } )
			] );
		}
		return el( 'svg', sep["atts"], inner );
	}


	this.makeBackground = function(props) {

		// var id = props.attributes.anchor ? props.attributes.anchor : props.clientId;
		var id = props.clientId;
		var style = "";
		var blocks = [];

		// new color and gradient
		var color = "";
		var gradient = "";
		if (_.has(props.attributes, 'style') && typeof props.attributes.style !== "undefined" && _.has(props.attributes.style, 'color')) {
			if (_.has(props.attributes.style.color, 'background')) {
				color = props.attributes.style.color.background;
			}
			if (_.has(props.attributes.style.color, 'gradient')) {
				gradient = props.attributes.style.color.gradient;
			}
		}
		if (_.has(props.attributes, 'backgroundColor') && typeof props.attributes.backgroundColor !== "undefined") {
			color = props.attributes.backgroundColor;
		}
		if (_.has(props.attributes, 'gradient') && typeof props.attributes.gradient !== "undefined") {
			gradient = props.attributes.gradient;
		}
		if (color != "") {
			// console.log(color);
			var classes = [ 'bg_color' ];
			color = greyd.tools.convertColorToVar(color);
			style += "#bg-"+id+" .bg_color { background-color: "+color+"; } ";
			blocks.push(el( 'div', { className: classes.join(' ') } ));
		}
		if (gradient != "") {
			// console.log(gradient);
			var classes = [ 'bg_gradient' ];
			gradient = greyd.tools.getGradientValue(gradient);
			style += "#bg-"+id+" .bg_gradient { background-image: "+gradient+"; } ";
			blocks.push(el( 'div', { className: classes.join(' ') } ));
		}


		if (_.has(props.attributes, 'background') &&
			!_.isEmpty(props.attributes.background) &&
			_.has(props.attributes.background, 'type')) {

			var el_atts = { };
			var el_classes = [ ];
			var image_style = "";

			// opacity
			var opacity = "";
			if (_.has(props.attributes.background, 'opacity')) {
				opacity = "opacity: "+props.attributes.background.opacity/100.0+"; ";
			}

			// old color
			if (props.attributes.background.type != "gradient" &&
				_.has(props.attributes.background, 'color') && props.attributes.background.color != "") {
				var classes = [ 'bg_color' ];
				var color = greyd.tools.convertColorToVar(props.attributes.background.color);
				style += "#bg-"+id+" .bg_color { background-color: "+color+"; } ";
				if (opacity != "") style += "#bg-"+id+" .bg_color { "+opacity+" } ";
				blocks.push(el( 'div', { className: classes.join(' ') } ));
			}
			// old gradient
			if (props.attributes.background.type == "gradient" &&
				_.has(props.attributes.background, 'gradient') && props.attributes.background.gradient != "") {
				var classes = [ 'bg_gradient' ];
				var gradient = greyd.tools.getGradientValue(props.attributes.background.gradient);
				style += "#bg-"+id+" .bg_gradient { background-image: "+gradient+"; } ";
				if (opacity != "") style += "#bg-"+id+" .bg_gradient { "+opacity+" } ";
				blocks.push(el( 'div', { className: classes.join(' ') } ));
			}

			// scroll
			if (_.has(props.attributes.background, 'scroll') && _.has(props.attributes.background.scroll, 'type')) {

				if (props.attributes.background.scroll.type.indexOf('parallax') == -1)
					image_style += "background-attachment: "+props.attributes.background.scroll.type+"; ";
				else {
					if (props.attributes.background.type == "animation" || props.attributes.background.type == "video")
						el_classes.push('div_parallax');
					el_classes.push('bg_'+props.attributes.background.scroll.type);
					var speed = _.has(props.attributes.background.scroll, 'parallax') ? props.attributes.background.scroll.parallax : 30;
					el_atts['data-bg_parallax_speed'] = speed;
					el_atts['data-bg_parallax_enable_mobile'] = props.attributes.background.scroll.parallax_mobile;
					setTimeout(function() {
						greyd.backgrounds.init();
					}, 0);
				}

			}

			// image
			if (props.attributes.background.type == "image" &&
				_.has(props.attributes.background, 'image') && !_.isEmpty(props.attributes.background.image) &&
				_.has(props.attributes.background.image, 'id') && props.attributes.background.image.id != -1) {

				var defaults = greyd.tools.layout.getBackgroundDefaults();
				var image = greyd.tools.getValues(defaults.background.image, props.attributes.background.image);
				if (image.url == "" && greyd.data.media_urls[image.id]) image.url = greyd.data.media_urls[image.id].src;

				// console.log(props);
				el_classes.push('bg_image');
				if (props.attributes.background.image.id.toString().indexOf('_') === 0) {

					// dynamic tag placeholder
					// var placeholder = 'content: "'+image.id+'"; font-size: 500%; font-weight: 800; line-height: 70%; position: absolute; transform: rotate(-5deg); text-shadow: 5px 5px 10px gray; height: 80px; opacity: 0.2';
					// style += "#bg-"+id+" .bg_image::before, #bg-"+id+" .bg_image::after { "+placeholder+" } ";
					// style += "#bg-"+id+" .bg_image::after { bottom: 0; right: 0; } ";
					el_classes.push('dynamic_img__preview');
					style += "#bg-"+id+" .bg_image { background-image: url('"+greyd.tools.getBlockIcon('dyn_img')+"'); opacity: 0.5 } ";
				}
				else {
					image_style += "background-image: url("+image.url+"); ";
					if (_.has(image, 'size')) image_style += "background-size: "+image.size+"; ";
					if (_.has(image, 'repeat')) image_style += "background-repeat: "+image.repeat+"; ";
					if (_.has(image, 'position')) image_style += "background-position: "+image.position+"; ";
					style += "#bg-"+id+" .bg_image { "+opacity+image_style+" } ";
				}

				el_atts['className'] = el_classes.join(' ');
				blocks.push(el( 'div', el_atts ));

			}

			// animation
			else if (typeof bodymovin !== 'undefined' && props.attributes.background.type == "animation" &&
				_.has(props.attributes.background, 'anim') && !_.isEmpty(props.attributes.background.anim) &&
				_.has(props.attributes.background.anim, 'url') && props.attributes.background.anim.url != "") {

				var defaults = greyd.tools.layout.getBackgroundDefaults();
				var anim = greyd.tools.getValues(defaults.background.anim, props.attributes.background.anim);

				var mode = (_.has(anim, 'anim') && anim.anim != "") ? anim.anim : 'auto' ;
				var pos = (_.has(anim, 'position') && anim.position != "") ? anim.position : 'top_center' ;
				el_classes.push('lottie-animation '+mode);
				el_classes.push(pos);
				el_atts['id'] = "anim_"+id;
				el_atts['style'] = { display: "inline-block", width: anim.width };
				el_atts["data-anim"] = mode;
				el_atts['data-src'] = anim.url ;

				style += "#bg-"+id+" .bg_animation { "+opacity+image_style+" } ";
				el_atts['className'] = el_classes.join(' ');
				blocks.push(el( 'div', { className: 'bg_animation' }, el( 'div', el_atts ) ));

			}

			// video
			else if (props.attributes.background.type == "video" &&
				_.has(props.attributes.background, 'video') && !_.isEmpty(props.attributes.background.video) &&
				_.has(props.attributes.background.video, 'url') && props.attributes.background.video.url != "") {

				var defaults = greyd.tools.layout.getBackgroundDefaults();
				var video = greyd.tools.getValues(defaults.background.video, props.attributes.background.video);

				var vid = { src: "", id: "", content: "Video not recognized." };
				var url = (_.has(video, 'url') && video.url != "") ? video.url : '';
				if (url.indexOf('youtu') > -1) {
					vid.src = 'youtube';
					if (url.indexOf('youtu.be') > -1) {
						var tmp = url.split('.be/');
						vid.id = tmp[1];
					}
					if (url.indexOf('?v=') > -1) {
						var tmp = url.split('?v=');
						vid.id = tmp[1];
					}
					if (url.indexOf('embed/') > -1) {
						var tmp = url.split('embed/');
						var tmp = tmp[1].split('?');
						vid.id = tmp[0];
					}
					if (url.indexOf('shorts/') > -1) {
						var tmp = url.split('shorts/');
						vid.id = tmp[1];
					}
					vid.content = el( 'div', { id: "video_"+id } );
				}
				else if (url.indexOf('vimeo') > -1) {
					vid.src = 'vimeo';
					var tmp = url.split('vimeo.com/');
					vid.id = tmp[1];
					vid.content = el( 'iframe', {
						id: "video_"+id,
						src: "https://player.vimeo.com/video/"+vid.id+"?autoplay=1&loop=1&muted=1&background=1&controls=0&title=0&byline=0&portrait=0",
						frameborder: '0',
						allow: 'autoplay; fullscreen'
					} );
				}
				var aspect = (_.has(video, 'aspect') && video.aspect != "") ? video.aspect : '16/9';
				el_atts['style'] = { width: "100%", height: "100%", textAlign: "center" };
				el_atts["data-bg_video_ar"] = aspect;
				if (vid.id != "") {
					el_atts['data-bg_video_src'] = vid.src ;
					el_atts['data-bg_video_id'] = vid.id ;
				}
				if (greyd.backgrounds.bgs.length > 0 && greyd.backgrounds.bgs["bg-"+id] && greyd.backgrounds.bgs["bg-"+id].video) {
					if (greyd.backgrounds.bgs["bg-"+id].video.video_id != vid.id && typeof greyd.backgrounds.bgs["bg-"+id].video.player !== 'undefined')
						greyd.backgrounds.bgs["bg-"+id].video.player.destroy();
					if (vid.id == "") greyd.backgrounds.bgs["bg-"+id].video = false;
				}

				style += "#bg-"+id+" .bg_video { "+opacity+image_style+" } ";
				el_atts['className'] = el_classes.join(' ');
				blocks.push(el( 'div', { className: 'bg_video' }, el( 'div', el_atts, vid.content ) ));

				setTimeout(function() {
					greyd.backgrounds.init();
				}, 0);
			}

		}

		return {
			'style': style,
			'blocks': blocks
		}
	}

	this.makeOverlay = function(props) {

		var style = "";
		var bg_el_blocks = [];

		if (_.has(props.attributes, 'background') &&
			!_.isEmpty(props.attributes.background) &&
			_.has(props.attributes.background, 'overlay')) {

			var defaults = greyd.tools.layout.getBackgroundDefaults();
			var overlay = greyd.tools.getValues(defaults.overlay, props.attributes.background.overlay);

			if (_.has(overlay, 'type') && overlay.type != "") {
				var opacity = "";
				if (_.has(overlay, 'opacity')) {
					opacity = "opacity: "+overlay.opacity/100.0+"; ";
				}
				if (overlay.type == "color" && _.has(overlay, 'color') && overlay.color != "") {
					var el_classes = [ 'bg_overlay_color' ];
					var color = greyd.tools.convertColorToVar(overlay.color);
					style += "#bg-"+props.clientId+" .bg_overlay_color { background-color: "+color+"; } ";
					if (opacity != "") style += "#bg-"+props.clientId+" .bg_overlay_color { "+opacity+" } ";
					bg_el_blocks.push(el( 'div', { className: el_classes.join(' ') } ));
				}
				if (overlay.type == "gradient" && _.has(overlay, 'gradient') && overlay.gradient != "") {
					var el_classes = [ 'bg_overlay_gradient' ];
					var gradient = greyd.tools.getGradientValue(overlay.gradient);
					style += "#bg-"+props.clientId+" .bg_overlay_gradient { background-image: "+gradient+"; } ";
					if (opacity != "") style += "#bg-"+props.clientId+" .bg_overlay_gradient { "+opacity+" } ";
					bg_el_blocks.push(el( 'div', { className: el_classes.join(' ') } ));
				}
			}
		}

		return {
			'style': style,
			'blocks': bg_el_blocks
		}
	}

	this.makePattern = function(props) {

		var style = "";
		var bg_el_blocks = [];

		if (_.has(props.attributes, 'background') &&
			!_.isEmpty(props.attributes.background) &&
			_.has(props.attributes.background, 'pattern')) {

			var defaults = greyd.tools.layout.getBackgroundDefaults();
			var pattern = greyd.tools.getValues(defaults.pattern, props.attributes.background.pattern);

			if (_.has(pattern, 'type') && pattern.type != "") {
				var el_classes = [ 'bg_pattern' ];
				var pattern_image = pattern.type;
				if (pattern_image == "pattern_custom") pattern_image = pattern.url;
				else pattern_image = greyd.tools.layout.getPattern(pattern_image);
				if (typeof pattern_image !== 'undefined' && pattern_image != "") {
					var pattern_style = "background-image: url("+pattern_image+"); ";
					if (_.has(pattern, 'size')) pattern_style += "background-size: "+pattern.size+"; ";
					if (_.has(pattern, 'scroll')) pattern_style += "background-attachment: "+pattern.scroll+"; ";
					if (_.has(pattern, 'opacity')) pattern_style += "opacity: "+pattern.opacity/100.0+"; ";
					style += "#bg-"+props.clientId+" .bg_pattern { "+pattern_style+" } ";
					bg_el_blocks.push(el( 'div', { className: el_classes.join(' ') } ));
				}
			}

		}

		return {
			'style': style,
			'blocks': bg_el_blocks
		}
	}

	this.makeSeperator = function(props) {

		var style = "";
		var bg_el_blocks = [];

		if (_.has(props.attributes, 'background') &&
			!_.isEmpty(props.attributes.background) &&
			_.has(props.attributes.background, 'seperator')) {

			var defaults = greyd.tools.layout.getBackgroundDefaults();
			var seperator = greyd.tools.getValues(defaults.seperator, props.attributes.background.seperator);

			if (_.has(seperator, 'type') && seperator.type != "") {
				var el_classes = [ 'bg_seperator' ];
				var el_content = [ ];

				var height = "60px";
				var color = "";
				var position = "top";
				var zposition = "0";
				if (_.has(seperator, 'height')) height = seperator.height;
				if (_.has(seperator, 'color')) color = seperator.color;
				if (_.has(seperator, 'position')) position = seperator.position;
				if (_.has(seperator, 'zposition')) zposition = seperator.zposition;
				var seperator_image = greyd.tools.layout.getSeperator(seperator.type, '100%', color);
				if (position.indexOf('top') > -1) {
					el_content.push(el( 'div', { className: 'sep_top', style: { height: height } }, seperator_image ));
				}
				if (position.indexOf('bottom') > -1) {
					var sep_class = 'sep_bottom';
					if (position.indexOf('asym') > -1) sep_class = 'sep_bottom_mirror';
					el_content.push(el( 'div', { className: sep_class, style: { height: height } }, seperator_image ));
				}
				if (_.has(seperator, 'opacity')) style += "#bg-"+props.clientId+" .bg_seperator { opacity: "+seperator.opacity/100.0+";  } ";
				bg_el_blocks.push(el( 'div', { className: el_classes.join(' '), style: { zIndex: zposition } }, el_content ));
			}

		}

		return {
			'style': style,
			'blocks': bg_el_blocks
		}
	}

	// console.log( "additional layout tools: loaded" );

};

greyd.cssAnims = new function() {
	
	this.observer = false;
	this.elements = [];

	this.init = function() {

		if (!greyd.cssAnims.observer) {
			// define the observer
			greyd.cssAnims.observer = new IntersectionObserver(
				function onIntersection(entries, opts) {
					// console.log(entries, opts);
					entries.forEach(entry => {
						// get correct triggered class
						var triggerClass = "start-animation";
						if (entry.target.classList.contains("wpb_animate_when_almost_visible")) {
							triggerClass = "wpb_start_animation";
						}
						if (entry.isIntersecting && !entry.target.classList.contains(triggerClass)) {
							// console.log("trigger animation")
							entry.target.classList.add(triggerClass);
							entry.target.classList.add("animated");
							// remove entry from observer and re-init when finished
							greyd.cssAnims.observer.unobserve( entry.target );
							entry.target.addEventListener("animationend", greyd.cssAnims.init, {once : true});
						}
						else if (!entry.isIntersecting && entry.target.classList.contains(triggerClass)) {
							// console.log("reset animation");
							entry.target.classList.remove(triggerClass);
							entry.target.classList.remove("animated");
						}
					} );
				}, 
				{
					root: document.querySelector('.interface-interface-skeleton__content'),
					threshold: 0, // percentage of target's visible area. Triggers "onIntersection"
				}
			);
		}

		// remove all elements from observer
		if (greyd.cssAnims.elements.length > 0) {
			greyd.cssAnims.elements.forEach( (element) => {
				greyd.cssAnims.observer.unobserve( element );
			} );
		}

		// add all current elements with css animation to observer
		greyd.cssAnims.elements = [];
		var objs = document.querySelectorAll('.wpb_animate_when_almost_visible, .animate-when-almost-visible');
		if (objs.length === 0) return;
		objs.forEach( (element) => {
			greyd.cssAnims.observer.observe( element );
			greyd.cssAnims.elements.push( element );
		} );
	}

	// console.log( "css animation tools: loaded" );

};


function onYouTubeIframeAPIReady() {
	greyd.backgrounds.initYoutubeVideos();
}
greyd.backgrounds = new function() {

	this.bgs = {};

	this.init = function() {

		if ( typeof $ === 'undefined' ) $ = jQuery;

		// get background
		var youtubefound = false;
		$('.vc_bg, .greyd-background').each(function() {
			var id = $(this).attr('id');
			while (typeof id === 'undefined') id = $(this).parent().attr('id');
			var resize = false;
			if ($(this).hasClass('vc_bg_full') || $(this).hasClass('vc_bg_window'))
				resize = true;
			var scroll = [];
			// console.log(id);
			$('#'+id+' .vc_bg_vparallax, #'+id+' .bg_vparallax').each(function() {
				scroll.push({
					container: $(this),
					scroll: 'vparallax'
				});
			});
			$('#'+id+' .vc_bg_hparallax, #'+id+' .bg_hparallax').each(function() {
				scroll.push({
					container: $(this),
					scroll: 'hparallax'
				});
			});
			var video = false;
			$('#'+id+' .vc_bg_video').each(function() {
				video = {
					src: $(this).children(0)[0].dataset['vc_bg_video_src'],
					video_id: $(this).children(0)[0].dataset['vc_bg_video_id'],
					video_aspect: $(this).children(0)[0].dataset['vc_bg_video_ar'],
					container_id: $(this).children(0).children(0).attr('id'),
				};
				if (video.src == "youtube") youtubefound = true;
			});
			$('#'+id+' .bg_video').each(function() {
				video = {
					src: $(this).children(0)[0].dataset['bg_video_src'],
					video_id: $(this).children(0)[0].dataset['bg_video_id'],
					video_aspect: $(this).children(0)[0].dataset['bg_video_ar'],
					container_id: $(this).children(0).children(0).attr('id'),
				};
				if (video.src == "youtube") youtubefound = true;
			});
			greyd.backgrounds.bgs[id] = {
				container: $(this),
				resize: resize,
				scroll: scroll,
				video: video
			};
		});
		if (youtubefound) {
			if (typeof YT === 'undefined') {
				var tag = document.createElement('script');
				tag.src = "https://www.youtube.com/iframe_api";
				var firstScriptTag = document.getElementsByTagName('script')[0];
				firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
			}
			else greyd.backgrounds.initYoutubeVideos();
		}

		// scroll
		$('.interface-interface-skeleton__content').off('scroll.bg').on('scroll.bg', this.scroll);
		//this.scroll(); // already gets called via this.resize()
		// resize
		$('.interface-interface-skeleton__content').off('resize.bg').on('resize.bg', this.resize);
		this.resize();

	}
	this.initYoutubeVideos = function() {
		for (var i in greyd.backgrounds.bgs) {
			// all backgrounds
			if (greyd.backgrounds.bgs[i].video && greyd.backgrounds.bgs[i].video.src == "youtube") {
				// all youtube videos
				greyd.backgrounds.bgs[i].video.player = new YT.Player(greyd.backgrounds.bgs[i].video.container_id, {
					height: '100%',
					width: '100%',
					videoId: greyd.backgrounds.bgs[i].video.video_id,
					playerVars: {
						autoplay: '1',
						loop: '1',
						controls: '0',
						modestbranding: '1',
						disablekb: '1'
					},
					events: {
						'onReady': greyd.backgrounds.onPlayerReady,
						'onStateChange': greyd.backgrounds.onPlayerStateChange
					}
				});
			}
		}
		console.log("Block Background youtube: loaded");
	}
	this.onPlayerReady = function(event) {
		// console.log("player ready");
		event.target.mute();
		event.target.playVideo();
		greyd.backgrounds.resize();
		//greyd.backgrounds.scroll(); // already gets called via greyd.backgrounds.resize()
	}
	this.onPlayerStateChange = function(event) {
		if (event.data == YT.PlayerState.ENDED) {
			event.target.seekTo(0);
			event.target.playVideo();
		}
	}

	this.resize = function () {
		// console.log("resizing");
		for (var i in greyd.backgrounds.bgs) {
			if (greyd.backgrounds.bgs[i].resize) {
				// console.log("background resizing");
				var bg = greyd.backgrounds.bgs[i].container;
				// var margin = $(bg).parent().css('margin-left');
				// if (margin != "0px") margin = '-'+margin;
				var margin = -($(bg).parent()[0].getBoundingClientRect().left);
				margin += $('body')[0].getBoundingClientRect().left;

				//if the margin is greater than half the window-width, chances are the bg sits outside the viewport
				if ( Math.abs(parseInt(margin)) * 2 <= window.innerWidth ) {
					if ( Math.abs(parseInt(margin)) !== 0 ) {
						$(bg).css('left', margin);
						$(bg).css('right', margin);
					}
				}
			}
			if (greyd.backgrounds.bgs[i].video) {
				// console.log(greyd.backgrounds.bgs[i].video);
				var bg = greyd.backgrounds.bgs[i].container;
				var pheight = $(bg).css('height').replace("px","");
				var pwidth = $(bg).css('width').replace("px","");

				var ar = greyd.backgrounds.bgs[i].video.video_aspect.split("/");
				ar = ar[0]/ar[1]; // ar = 16/9;
				var height = pheight;
				var width = height * (ar);
				var margin_left = margin_top = 0;

				if (width >= pwidth) {
					margin_left = (pwidth - width) / 2;
				}
				else {
					width = pwidth;
					height = width / (ar);
					margin_top = (pheight - height) / 2;
				}
				$("#"+greyd.backgrounds.bgs[i].video.container_id).css('height', height+"px");
				$("#"+greyd.backgrounds.bgs[i].video.container_id).css('width', width+"px");
				$("#"+greyd.backgrounds.bgs[i].video.container_id).css('margin-left', margin_left+"px");
				$("#"+greyd.backgrounds.bgs[i].video.container_id).css('margin-top', margin_top+"px");
			}
		}
		greyd.backgrounds.scroll();
	}
	this.scroll = function () {
		// console.log("scrolling");

		var is_mobile = ($('html').hasClass('ios') || $('html').hasClass('android'));
		var viewportTop = $('.interface-interface-skeleton__content').scrollTop();
		var viewportHeight = $('.interface-interface-skeleton__content').height();
		var viewportBottom = viewportTop + viewportHeight;

		for (var i in greyd.backgrounds.bgs) {
			// all backgrounds
			if (greyd.backgrounds.bgs[i].scroll.length > 0) {
				for (var j=0; j<greyd.backgrounds.bgs[i].scroll.length; j++) {
					// all parallax layers
					var bg = greyd.backgrounds.bgs[i].scroll[j].container;
					var scroll = greyd.backgrounds.bgs[i].scroll[j].scroll;
					var enable_mobile = $(bg)[0].dataset['bg_parallax_enable_mobile'];
					if (!is_mobile || (is_mobile && enable_mobile)) {
						// enable on desktop and check mobile
						var elementTop = $(bg).offset().top;
						var elementHeight = $(bg).outerHeight();
						var speed = $(bg)[0].dataset['bg_parallax_speed'];
						if (viewportBottom-elementTop > 0 && elementTop+elementHeight > 0) {
							var delta = (viewportHeight - elementHeight) / 2;
							var pos = ((viewportBottom-elementTop-elementHeight-delta)*(speed/100.0));
							if (scroll == "vparallax") {
								// vertical parallax
								if ($(bg).hasClass('div_parallax'))
									$(bg).children(0).css('transform', 'translateY('+pos.toFixed(2)+'px)');
								else
									$(bg).css('background-position-y', pos.toFixed(2)+'px');
							}
							if (scroll == "hparallax") {
								// horizontal parallx
								if ($(bg).hasClass('div_parallax'))
									$(bg).children(0).css('transform', 'translateX('+pos.toFixed(2)+'px)');
								else
									$(bg).css('background-position-x', pos.toFixed(2)+'px');
							}
						}
					}
				}
			}
		}
	}

	// console.log( "background tools: loaded" );

}