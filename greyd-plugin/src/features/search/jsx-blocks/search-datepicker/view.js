import flatpickr from "flatpickr";
import { english } from "flatpickr/dist/l10n/default.js"
import { German } from "flatpickr/dist/l10n/de.js"
import './flatpickr-light.css';
import { predefinedRanges } from "./predefinedRange.js";
import './view.css';

document.addEventListener("DOMContentLoaded", function() {
	const datepickerInputs = document.querySelectorAll('.greyd-datepicker-input');

	for (const input of datepickerInputs) {
		const dateFormat = input.getAttribute('data-date-format');
		const enableTime = input.getAttribute('data-enable-time');
		const filterBy = input.getAttribute('data-filter-by');
		const locale = input.getAttribute('data-locale');
		const maxDate = input.getAttribute('data-max-date');
		const minDate = input.getAttribute('data-min-date');
		const mode = input.getAttribute('data-mode');
		const position = input.getAttribute('data-position');
		const time_24hr = input.getAttribute('data-time-24hr');
		const weekNumbers = input.getAttribute('data-week-numbers');

		const defaultDate = getDefaultDateFromInputs(input, mode, filterBy);
		
		const ranges = input.getAttribute('data-ranges') ? JSON.parse(input.getAttribute('data-ranges')) : {};

		// Filter the ranges object to only include the ones where the status is true but keep the entire object
		const activeRanges = Object.keys( ranges ).reduce( ( acc, key ) => {
			if ( ranges[key].state ) {
				acc[key] = ranges[key];
			}
			return acc;
		}, {} );

		let config = {
			onChange: onChange,
			dateFormat: dateFormat ? dateFormat : 'Y-m-d',
			defaultDate: defaultDate ? defaultDate : null,
			enableTime: enableTime === "1" ? true : false,
			locale: english,
			maxDate: maxDate ? convertDateFormat(maxDate, dateFormat) : null,
			minDate: minDate ? convertDateFormat(minDate, dateFormat) : null,
			mode: mode ? mode : 'range',
			position: position ? position : 'auto center',
			plugins: [ predefinedRanges( locale ) ],
			ranges: activeRanges,
			rangesOnly: false,
			time_24hr: time_24hr === "1" ? true : false,
			weekNumbers: weekNumbers === "1" ? true : false,
		}
		
		new flatpickr(input, config);


		const clearButton = input.parentNode.querySelector('.greyd-datepicker-clear');
		clearButton.addEventListener('click', () => {
			const instance = input._flatpickr;
			instance.clear();
			removePreviousInputFields(input.parentNode);
			input.dispatchEvent(new Event('change'));
		});

		if ( isMobile() && mode === 'single') {
			const mobileDatepickerInputs = document.querySelectorAll('.greyd-datepicker-input.flatpickr-mobile');
			for (const input of mobileDatepickerInputs) {
				const flatpickrInput = input.parentElement.querySelector('.flatpickr-input:not(.flatpickr-mobile)');
				const placeholder = flatpickrInput.getAttribute('placeholder');
				input.addEventListener('change', () => {
					// remove placeholder
					if (input.value) { 
						input.placeholder = '';
					} else { 
						input.placeholder = placeholder; 
					}
				});
			}
		}
	} 
});

/**
 * Custom onChange event to add hidden input fields for the selected date range
 * 
 * @param {Array} selectedDates 
 * @param {String} dateStr 
 * @param {Object} instance 
 */
const onChange = (selectedDates, dateStr, instance) => {
	let from = selectedDates[0];
	let to = selectedDates[1];

	if ( from && instance.config.mode === 'single' ) to = from;

	const input = instance.element;
	
	const field = input.getAttribute('data-field');
	const filterBy = input.getAttribute('data-filter-by');

	removePreviousInputFields(input.parentNode);

	if ( from && to ) {
		
		if ( !instance.config.enableTime ) {
			to = convertTimezone(to).toISOString().split(/[T ]/i, 1)[0];
			from = convertTimezone(from).toISOString().split(/[T ]/i, 1)[0];
		} else {
			to = convertTimezone(to).toISOString();
			from = convertTimezone(from).toISOString();
		}
		
		if ( filterBy === 'post_date' ) {
			createInputField(input.parentNode, 'post_date[after]', from);
			createInputField(input.parentNode, 'post_date[before]', to);
		
		} else if ( filterBy === 'meta_date' ) {
			if ( field ) {
				createInputField(input.parentNode, 'meta_date[field]', field);
				createInputField(input.parentNode, 'meta_date[from]', from);
				createInputField(input.parentNode, 'meta_date[to]', to);
			}
		} else if ( filterBy === 'dynamic_meta_date' ) {
			if ( field ) {
				createInputField(input.parentNode, 'dynamic_meta_date[field]', field);
				createInputField(input.parentNode, 'dynamic_meta_date[from]', from);
				createInputField(input.parentNode, 'dynamic_meta_date[to]', to);
			}
		}
	}
};

const getDefaultDateFromInputs = (input, mode, filterBy) => {

	const types = ["post_date", "meta_date", "dynamic_meta_date"];

	if ( types.indexOf(filterBy) === -1 ) return;

	const inputs = input.parentNode.querySelectorAll(`input[name^="${filterBy}"]`);

	if ( inputs.length === 0 ) return;

	if ( filterBy === "post_date") {
		const after = inputs[0].value;
		const before = inputs[1].value;

		if (after && before) {
			return [ Date.parse(after), Date.parse(before) ];
		}
	} else {
		let from, to;

		for (const input of inputs) {
			if ( input.matches(`input[name="${filterBy}[from]"`) ) {
				from = input.value;
			} else if ( input.matches(`input[name="${filterBy}[to]"`) ) {
				to = input.value;
			} 
		}

		if ( from && to) {
			return [ Date.parse(from), Date.parse(to) ];
		} else if (from) {
			return Date.parse(from);
		}
	}
}

const removePreviousInputFields = (parentNode) => {
	// remove all input fields except the one with classname .greyd-datepicker-input
	const inputs = parentNode.querySelectorAll('input');
	inputs.forEach(input => {
		if (!input.classList.contains('greyd-datepicker-input')) {
			input.remove();
		}
	});
}

const createInputField = (parentNode, name, value) => {
	const input = document.createElement('input');
	input.setAttribute('type', 'hidden');
	input.setAttribute('name', name);
	input.setAttribute('value', value);
	parentNode.appendChild(input);
}

const convertTimezone = (date) => {
	const offset = date.getTimezoneOffset()
	date = new Date(date.getTime() - (offset*60*1000))
	return date
}

const isMobile = () => {
	let check = false;
	(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
	return check;
}

const convertDateFormat = (date, format) => {
	// minDate and maxDate is always in the format YYYY-MM-DD
	// they need to be converted to the format of the date format setting in order for the min/max functionality to work
	const newDate = new Date(date);

	// Handle empty or null date
	if (!date || !format) {
		return '';
	}

	// PHP date format mapping to JavaScript
	const formatMap = {
		// Day formats
		'd': () => String(newDate.getDate()).padStart(2, '0'),
		'D': () => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][newDate.getDay()],
		'j': () => String(newDate.getDate()),
		'l': () => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][newDate.getDay()],
		'N': () => newDate.getDay() === 0 ? 7 : newDate.getDay(),
		'S': () => {
			const day = newDate.getDate();
			if (day >= 11 && day <= 13) return 'th';
			switch (day % 10) {
				case 1: return 'st';
				case 2: return 'nd';
				case 3: return 'rd';
				default: return 'th';
			}
		},
		'w': () => newDate.getDay(),
		'z': () => Math.floor((newDate - new Date(newDate.getFullYear(), 0, 0)) / (1000 * 60 * 60 * 24)),

		// Week formats
		'W': () => {
			const d = new Date(newDate);
			d.setHours(0, 0, 0, 0);
			d.setDate(d.getDate() + 4 - (d.getDay() || 7));
			const yearStart = new Date(d.getFullYear(), 0, 1);
			return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
		},

		// Month formats
		'F': () => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][newDate.getMonth()],
		'm': () => String(newDate.getMonth() + 1).padStart(2, '0'),
		'M': () => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][newDate.getMonth()],
		'n': () => String(newDate.getMonth() + 1),
		't': () => new Date(newDate.getFullYear(), newDate.getMonth() + 1, 0).getDate(),

		// Year formats
		'L': () => {
			const year = newDate.getFullYear();
			return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0) ? 1 : 0;
		},
		'o': () => {
			const d = new Date(newDate);
			d.setDate(d.getDate() - d.getDay() + 4);
			return d.getFullYear();
		},
		'Y': () => newDate.getFullYear(),
		'y': () => String(newDate.getFullYear()).slice(-2)
	};

	// Replace format characters with their values
	let result = format;
	
	// Process each format character individually to avoid conflicts
	for (const [char, formatter] of Object.entries(formatMap)) {
		// Escape special regex characters
		const escapedChar = char.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		// Create a regex that matches the exact character (not part of a word)
		const regex = new RegExp(`(?<!\\w)${escapedChar}(?!\\w)`, 'g');
		result = result.replace(regex, formatter());
	}

	return result;
}