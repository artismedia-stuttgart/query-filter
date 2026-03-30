/**
 * flatpickr plugin to add predefined ranges to the calendar.
 */

import moment from 'moment/dist/moment';
import 'moment/dist/locale/de';
import './predefinedRange.css';

export const predefinedRanges =  ( locale ) => {
	return function (fp) {

		let rangesNav = document.createElement('ul');
		rangesNav.className = "nav flex-column flatpickr-predefined-ranges";

		const pluginData = {
			ranges: typeof fp.config.ranges !== 'undefined' ? fp.config.ranges : {},
			rangesOnly: typeof fp.config.rangesOnly === 'undefined' || fp.config.rangesOnly,
			rangesAllowCustom: typeof fp.config.rangesAllowCustom === 'undefined' || fp.config.rangesAllowCustom,
			rangesCustomLabel: typeof fp.config.rangesCustomLabel !== 'undefined' ? fp.config.rangesCustomLabel : 'Custom Range',
			rangesNav: rangesNav,
			rangesButtons: {}
		};

		// Set the locale for moment.js to set the correct start of the week (Monday for German, Sunday for English)
		locale === 'de' ? moment.locale('de') : moment.locale('en');

		for (const [key, value] of Object.entries(pluginData.ranges)) {
			// Set the formula for the predefined ranges
			if ( key === 'Today' ) { value.formula = [new Date(), new Date()]; }
			if ( key === 'Next 7 Days' ) { value.formula = [new Date(), moment().add(6, 'days').toDate()]; }
			if ( key === 'Last 7 Days' ) { value.formula = [moment().subtract(6, 'days').toDate(), new Date()]; }
			if ( key === 'Next 30 Days' ) { value.formula = [new Date(), moment().add(29, 'days').toDate()]; }
			if ( key === 'Last 30 Days' ) { value.formula = [moment().subtract(29, 'days').toDate(), new Date()]; }
			if ( key === 'This Week' ) { value.formula = [moment().startOf('week').toDate(), moment().endOf('week').toDate()]; }
			if ( key === 'Next Week' ) { value.formula = [moment().add(1, 'weeks').startOf('week').toDate(), moment().add(1, 'weeks').endOf('week').toDate()]; }
			if ( key === 'Last Week' ) { value.formula = [moment().subtract(1, 'weeks').startOf('week').toDate(), moment().subtract(1, 'weeks').endOf('week').toDate()]; }
			if ( key === 'This Month' ) { value.formula = [moment().startOf('month').toDate(), moment().endOf('month').toDate()]; }
			if ( key === 'Next Month' ) { value.formula = [moment().add(1, 'months').startOf('month').toDate(), moment().add(1, 'months').endOf('month').toDate()]; }
			if ( key === 'Last Month' ) { value.formula = [moment().subtract(1, 'months').startOf('month').toDate(), moment().subtract(1, 'months').endOf('month').toDate()]; }
			if ( key === 'This Year' ) { value.formula = [moment().startOf('year').toDate(), moment().endOf('year').toDate()]; }
			if ( key === 'Next Year' ) { value.formula = [moment().add(1, 'years').startOf('year').toDate(), moment().add(1, 'years').endOf('year').toDate()]; }
			if ( key === 'Last Year' ) { value.formula = [moment().subtract(1, 'years').startOf('year').toDate(), moment().subtract(1, 'years').endOf('year').toDate()]; }
		}

		/**
		 * @param {string} label
		 * @returns HTML Element
		 */
		const addRangeButton = function (label) {

			let button = document.createElement('button');
			button.type = "button";
			button.className = "nav-link btn btn-link";
			button.innerText = label;

			pluginData.rangesButtons[label] = button;

			let item = document.createElement('li');
			item.className = "nav-item d-grid";

			item.appendChild(pluginData.rangesButtons[label]);

			pluginData.rangesNav.appendChild(item);

			return pluginData.rangesButtons[label];
		};

		/**
		 * Loop the ranges and check for one that matches the selected dates, adding
		 * an active class to its corresponding button.
		 *
		 * If there are selected dates and a range is not matched, check for a custom
		 * range button and set it to active.
		 *
		 * If there are no selected dates or a range is not matched, check if the
		 * rangeOnly option is true and if so hide the calendar.
		 *
		 * @param {Array} selectedDates
		 */
		const selectActiveRangeButton = function (selectedDates) {
			let isPredefinedRange = false;
			let current = pluginData.rangesNav.querySelector('.active');

			if (current) {
				current.classList.remove('active');
			}

			if (selectedDates.length > 0) {
				let startDate = moment(selectedDates[0]);
				let endDate = selectedDates.length > 1 ? moment(selectedDates[1]) : startDate;
				for (const [label, range] of Object.entries(pluginData.ranges)) {
				if (startDate.isSame(moment(range.formula[0]), 'day') && endDate.isSame(moment(range.formula[1]), 'day')) {
					pluginData.rangesButtons[range.label].classList.add('active');
					isPredefinedRange = true;
					break;
				}
				}
			}

			// not sure if this code still works after changing the ranges object
			if (selectedDates.length > 0 &&
				!isPredefinedRange &&
				pluginData.rangesButtons.hasOwnProperty(pluginData.rangesCustomLabel)
			) {
				pluginData.rangesButtons[pluginData.rangesCustomLabel].classList.add('active');
				fp.calendarContainer.classList.remove('flatpickr-predefined-ranges-only');
			} else if (pluginData.rangesOnly) {
				fp.calendarContainer.classList.add('flatpickr-predefined-ranges-only');
			}
		};

		return {
		/**
		 * Loop the ranges and add buttons for each range which a click handler to set the date.
		 * Also adds a custom range button if the rangesAllowCustom option is set to true.
		 */
		onReady(selectedDates) {
			for (const [key, value] of Object.entries(pluginData.ranges)) {
			addRangeButton(value.label).addEventListener('click', function () {

				let start = moment(value.formula[0]).toDate();
				let end = moment(value.formula[1]).toDate();

				if (!start) {
					fp.clear();
				} else {
					fp.setDate([start, end], true);
				}

				fp.close();
				});
			}

			if (pluginData.rangesNav.children.length > 0) {
			if (pluginData.rangesOnly && pluginData.rangesAllowCustom) {
				let customButton = addRangeButton(pluginData.rangesCustomLabel);
				customButton.addEventListener('click', function () {
					let current = pluginData.rangesNav.querySelector('.active');
					if (current) {
					current.classList.remove('active');
					}
					customButton.classList.add('active');
					fp.calendarContainer.classList.remove('flatpickr-predefined-ranges-only');
				});
			}

			fp.calendarContainer?.prepend(pluginData.rangesNav);
			fp.calendarContainer?.classList.add('flatpickr-has-predefined-ranges');
			// make sure the right range button is active for the default value
			selectActiveRangeButton(selectedDates);
			}
		},

		/**
		 * Make sure the right range button is active when a value is manually entered
		 *
		 * @param {Array} selectedDates
		 */
		onValueUpdate(selectedDates) {
			selectActiveRangeButton(selectedDates);
		}
		};
	};
}