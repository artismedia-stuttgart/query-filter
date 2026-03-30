## 2.18.3 - 2026-02-05

**Improvements**

- Removed minimum and maximum limits for "space outside" settings in groups and columns.

**Bugfixes**

- Fixed an issue where ACF taxonomies were not displayed in the frontend despite being selectable in the editor.
- Fixed an issue where adding a Greyd Search block to a Query Loop set to "inherit" caused a fatal error.
- Fixed an issue where custom language switcher triggers did not work correctly.
- Fixed an issue where the focus point in cover blocks was not applied in Dynamic Templates.
- Fixed an issue where detaching a Dynamic Template with a heading set to a different heading level caused a block error.
- Fixed an issue where authors could not be changed for Dynamic Post Types.
- Fixed an issue where multiline text fields (textarea) had double line breaks due to duplicate nl2br() processing.
- Fixed an issue where sites added to a multisite network did not appear in Greyd.Hub and could not be disconnected properly.
- Removed obsolete title attribute from the Greyd Search button for improved accessibility.
- Removed deprecated "Has Results" block.
- Fixed dead links in the Greyd dashboard.
- Fixed an issue where aria-labels were not correctly applied to core buttons and Greyd Search blocks.


## 2.18.2 - 2025-11-21

**Improvements**

- Added option to Post Slider pagination to change the URL Parameter. (enables browser back navigation that restores the previous page in Query Loop and Post Slider)
- Updated naming in the "Image" panel of the Hotspot block for better clarity.
- Enhanced Hotspot block accessibility.
- Added option to limit the number of displayed pages for Query Loop and Post Slider pagination.
- Added support for making dynamic links available as optional downloads in Trigger settings.
- Added dynamic max-width handling for Paragraphs, Headlines, and similar elements inside Dynamic Templates.

**Bugfixes**

- Fixed an issue where custom CSS classes were not applied in the Post Slider frontend.
- Resolved a problem that prevented editing a Template-in-Template when made dynamic.
- Fixed a Popover issue where adding another Navigation block inside the overlay caused layout breakage.
- Adjusted frontend CSS for Pop-ups to properly allow gradient overlays.
- Fixed excessive spacing between Pop-up content and Pop-up settings.
- Resolved a problem where Scroll-to and Trigger Events were unavailable when “Showing template” was active.
- Fixed incorrect behaviour where sorting by ACF meta data appeared selectable but did not work in Advanced Filter.
- Corrected Site Connector still appearing after a license downgrade.
- Fixed a display issue where “Globally enabled in network admin” always appeared, even when not applicable.
- Ensured that the “No Results” block renders correctly when a Query Loop block is placed inside Tabs.
- Fixed core pagination not updating Post Slider content.
- Resolved an input field cursor navigation issue.
- Corrected ARIA attribute behaviour in combination with the Post Slider.
- Fixed the Separator block’s “Display as dots” switch not working as expected.
- Fixed unexpected inheritance of background images from parent Hotspot blocks.
- Resolved an issue where the CPT preview button only worked via right-click.


## 2.18.1 - 2025-11-06
 
**Bugfixes**
 
* Dynamic Tags: enable additional settings for Global Dynamic Tags fields
* Fixed an issue with the floating box inside the block editor due to a type validation error.

## 2.18.0 - 2025-10-15

**Features**

* Conditional Content: Added support for dynamic taxonomy conditions
* Conditional Content Block / Post Meta: Added available values for dropdown and radio buttons
* Theme Assets: Added user warning when “Trash” is clicked
* Popover: Allow Groups & Content Box as “Trigger”
* Popover: Added option to trigger on hover
* Accessibility (A11y): Fixed Content Box adjustments

**Improvements**

* Customized Editor preview: Adjusting title no longer causes unnecessary line breaks
* Query + Filter: JavaScript now re-triggers to improve third-party plugin compatibility

**Bugfixes**

* Dynamic Templates: Selecting image from media library no longer breaks the editor
* Accordion with icon now displays correctly when heading is used; outline visible again
* Dynamic Templates: Fixed issue where properties were not made dynamic
* Animations now work properly again
* Group: Fixed `.group_wrap` class overwriting alignment in frontend
* Core Pagination: Page numbers now react correctly to current page in archives
* Global Content + ACF: Field clearing now works correctly on synchronization
* Hub: Export / Import templates visible again
* Tabs Block: Tabs alignment now works correctly with Space-between
* Post Import / Export: Export now respects original or scaled image version
* Navigation in Popover: Fixed JS warnings with `useSelect` and unnecessary re-renders
* Fixed a crash in the Conditional Content Block


## 2.17.5 - 2025-08-21

**Features**

- Added support for revisions for custom post types.
- Added option to configure popover animations.
- Added ability to set aria attributes on blocks.
- Extended Conditional Content to support Global Dynamic Tags outside of query loops.
- Improved visual appearance of features in post type editing.

**Improvements**

- Updated Dashicon Picker to include new icons and provide a larger dropdown.
- Added aria-labels to Filter Buttons and Chips block.

**Bugfixes**

- Fixed issue where Query Loop crashed in Site Editor Single-Template.
- Fixed accessibility issue where filters in the Search block were not accessible.
- Fixed issue where accordion with alternative heading tags could not be focused by keyboard.
- Fixed issue where post template wide width was not applied in the editor.
- Fixed issue where the datepicker broke when setting minimum or maximum dates.
- Fixed issue where Post Slider received 40px left padding when switched to `ul`.
- Fixed styling issues with Post Slider inside a group block.
- Fixed PHP warnings related to new aria-label property for popups.
- Fixed CSS issue with Dynamic Template inline styling not being applied correctly at parent element level.
- Fixed typos and translations.
- Fixed issue with Greyd.Hub migration where extra folders and files were added to uploads directory.


## 2.17.4 - 2025-07-31

**Features**

- Added option to set aria-labels for popups.
- Added new aspect ratios for the “Dynamic image” block.

**Improvements**

- Deprecated the Template Library feature.

**Bugfixes**

- Fixed missing focus outlines on elements triggered via hidden links (e.g. content boxes).
- Fixed issue where the “Media & Text” block was not supported in post exports.
- Fixed issue where the search term was not executed correctly in the post slider query.
- Resolved performance issues in the popover block.
- Fixed issue in the Tabs block where content became nearly uneditable.
- Fixed inconsistency between frontend and backend rendering of the anchor block.


## 2.17.3 - 2025-06-27

**Bugfixes**

- Fixed an issue that crashed a Greyd Button or Input field block when trying to overwrite the Font Family.


## 2.17.2 - 2025-06-26

**Bugfixes**

- Fixed an issue where assigning `<ul>` and `<li>` elements inside a Query Loop block was not possible
- Fixed multiple small bugs and errors


## 2.17.1 - 2025-06-18

**Bugfixes**

- Fixed conditional logic for dropdown/radio fields


## 2.17.0 - 2025-06-11

**Features**

- Added support for dynamic phone number links.

**Bugfixes**

- Fixed issue where Polylang auto-translated block attributes were not recognized.
- Resolved accessibility issue where duplicating accordion blocks did not generate a new unique id.
- Fixed issue with Advanced Filter not recognizing terms of current archive in taxonomy archive view.
- Resolved issue where live filtering in archive templates did not update pagination.
- Fixed invalid links shown in the license downgrade notice.
- Fixed an issue in Dynamic Templates that made pages almost uneditable.


## 2.16.2 - 2025-05-28

**Features**

- Added support for manipulating the “Most read posts” logic.
- Introduced additional options for “Most read” post count settings.
- Conditional Content now supports localStorage.
- Accordion elements now open with a smooth transition.

**Bugfixes**

- Fixed issue with Advanced Filter showing "No results" when “current items” were empty.
- Resolved issue in Site Editor where pages with excluded terms still appeared in search results.
- Fixed ACF integration issues within templates.
- Fixed accessibility issue where trigger links lacked descriptive text, causing PageSpeed warnings.
- Resolved issue where WPBakery autosearch dropdown did not appear on the frontend.

## 2.16.1 – 2025-05-14

**Bugfixes**

* Fixed an issue where Dynamic Tags did not work in the Site Editor anymore


## 2.16.0 – 2025-04-16

**Features**

* Conditional Content: Added support for "in future" and "in past" checks on meta fields.
* Hidden Fields: The “avoid caching” option now also applies to the URL setting.
* Extended filtering: ACF fields can now be used in post meta filters.
* Accordion Block: It's now possible to change the html tag for the title element.

**Improvements**

* A11Y: Improved keyboard accessibility for form tooltips.
* A11Y: Fixed multiple issues flagged during accessibility testing and from user feedback.
* Improved Dynamic Tags support for meta field date formats.
* Added default alt text field for image tiles in Greyd.Forms.
* Implemented a list view when staging is active in Greyd.Hub.
* Use the original post ID during post import if available.
* Increased priority for Greyd List in typeahead suggestions.

**Bugfixes**

* Fixed incorrect sorting when using meta dates.
* Fixed an issue where Dynamic Images no longer appeared in Dynamic Templates in Greyd.Suite Classic.
* Resolved inconsistent positioning of pop-ups in the Hotspot block.
* Fixed an issue where decoupling a Dynamic Template did not work.
* Resolved an issue with the AJAX URL in forms when using WPML.
* A11Y: Fixed an issue where ElegantIcons were read aloud by screen readers.
* Fixed a fatal error when using WPBakery with single pages.
* Fixed an issue where duplicating a menu crashed the editor.
* Custom trigger events no longer strip numbers from the event name.
* Scroll-to-top was being triggered even when the Post Slider was not in view.
* Fixed flickering in the Site Editor caused by the Conditional Content block.
* Fixed a bug in the List view of the Hub interface when staging was active.
* Corrected behavior of radio buttons where values were handled incorrectly.
* Fixed animation duration issues for background gradients.
* Fixed a bug where list items in Dynamic Templates were displayed even when empty.
* Fixed issues with checkboxes and radio buttons in forms.
* Fixed mobile popover issue in the Hotspot block.
* Fixed rendering issues with checkboxes, radio buttons, and iOS switches in user forms.
* Fixed a crash caused by the Image Tiles block in Greyd.Forms.
* Fixed a bug where frontend button styles weren’t updated.
* Prevented browser default outlines from appearing on hotspot popovers with hover triggers.
* Fixed a bug where the Customizer was visible in the menu with Greyd Theme + Greyd Plugin.
* Fixed font inheritance issues during site migration.
* Fixed broken Dynamic Tags in the editor.
* Removed notification to install Gutenberg.

## 2.15.1 - 2025-03-19

**Bugfixes**
 
* Fixed an issue where Dynamic Tags were not functioning correctly in the editor.

## 2.15.0 - 2025-03-17
 
**Features**
 
* Responsive Query: Added the option to choose a default device.
* Lottie Animations: Expanded to support the on scroll trigger.
* GC Canonical Link: Added Rankmath support.
* Greyd.Forms Backend: Introduced a new option to set the date format.
* Dynamic Tags: Extended to support meta field date formats.
* Patterns: Added Header & Footer Patterns to the Greyd Plugin.
* Patterns: Added Grid Patterns to the Greyd Plugin.
* Patterns: Added Popup Patterns to the Greyd Plugin.
* Patterns: Introduced Card Patterns for both Dynamic Templates and standard usage.
 
**Bugfixes**
 
* Fixed an issue where TinyMCE editor did not wrap text in `<p>` tags.
* Textarea fields now display a validation icon for errors.
* Greyd Search Filters, introduced in version 2.13.2, are now displayed correctly.
* Fixed a naming conflict that caused WP Hub Import to fail when encountering a custom `wp_xxx_options` table.
* Corrected an issue in Query Loop where the same background was applied to every post from the mobile breakpoint onward.
* GIFs in Greyd Dynamic Image will now animate properly.
* Resolved layout issues caused by combining a Dynamic Tag and a dynamic link with a list element.
* Condition on post index in loops now functions correctly.
* Ensured Rankmath compatibility for posts.
* Fixed an error in height calculation for conditional fields when used inside a multi-step container.
* Greyd Border Radius settings now correctly apply to WordPress native buttons.
* Duplicating and moving form fields in Greyd.Forms no longer causes issues.
* Filter dropdown labels now inherit colors as intended.
* Fixed a bug where the mobile slider setting in Query Loop’s grid configuration was not applied.
* Fixed an issue where changed breakpoints had no effect on columns.
* Popover Block: Addressed ARIA label visibility and editability issues.
* Fixed an issue where the edit context for WP taxonomy archive templates was not applied correctly.
* Resolved an issue where certain forms broke the layout in the editor.
* Improved live search behavior in archive templates by fixing query context on the search block.
* Loop pagination settings now work as expected.
* Fixed issues with image panels in multi-step forms.
* Resolved staging to live push errors.
* Fixed a bug where a live filter in one loop affected another loop.
* The alignment of content in content boxes now works correctly.


## 2.14.1 - 2025-02-25

**Bugfixes**

* Fixed a critical error in Greyd.Hub import/export

## 2.14.0 - 2025-02-11

**Improvements**

* Implemented a new options "avoid caching" for conditional content and hidden fields to resolve conflicts with caching plugins
* Added support for comments on custom post types.
* Introduced more RichText options for image tiles in Greyd.Forms.
* Integrated ACF fields into Dynamic Tags.
* Added support for custom font sizing in buttons using the new WP 6.7 controls.
* Added the ability to export Greyd Plugins as part of the Greyd.Hub export.
* Dynamic date filtering now supports "from today" in Advanced Filter.

**Bugfixes**

* Fixed an issue where popover blocks in the content were not correctly migrated.
* Resolved layout issues caused by triggers inside row blocks.
* Fixed an editor error caused by dynamic text highlighting with `<mark>` in Dynamic Templates.
* User management: Corrected an issue where specific custom post types could not be displayed
* Fixed incorrect post counts for filters & chips with hierarchical taxonomies
* Addressed an issue where decoupling Dynamic Templates with columns retained template lock.
* Fixed ineffective inline styling on blocks when core-specific styles were applied.
* Fixed multiple issues of the Hotspot block.
* Resolved incorrect data output from dynamic tag matching.
* Fixed height calculation errors in the query loop that resulted in cropped elements.
* Fixed pagination issues in the Greyd query loop when used with core pagination.
* Fixed incorrect behavior of Dynamic Tags in single post templates within the Classic Suite.
* Fixed issues with Dynamic Tag "post content" not working on smaller breakpoints in query loops.

## 2.13.0 - 2025-01-16

**Features**

* Introduced Section Patterns in the Greyd Plugin.
* Added support for Form Patterns in Greyd Forms.
* Updated existing patterns to improve consistency and usability.

**Improvements**

* Performance Improvements

**Bugfixes**

* Fixed inconsistent behavior of loops.
* Corrected an issue where loops on pages displayed too many terms.
* Resolved a problem where theme CSS was overriding block settings in the Button Block.
* Fixed an issue where a live filter in one loop affected another loop.
* Fixed an issue with the ajax url in wpml setups

## 2.12.0 - 2024-12-11

**Improvements**

* Introduced a design preset for TwentyTwentyFive.
* Enhanced backend performance for Post Slider in Webshops.
* Improved the accessibility of tabs content sections
* Renamed the session storage cookie for popups to ensure compliance with privacy scanners.
* Added the ability to configure decimal and thousand separators in Forms Math Fields.
* Introduced a button to manually regenerate classes with `greydClass`.
* Enabled a "Show All" functionality for Live Search results.

**Bugfixes**

* Fixed an issue where post template blocks were not functioning in WordPress 6.4.5.
* Resolved a problem where menu icons in Classic and WPBakery were no longer displayed.
* Fixed a crash in Dynamic Templates.
* Corrected alignment issues and crashes in the Popover Editor.
* Resolved missing pagination back button in Greyd.Hub.
* Fixed a vertical stretching issue with main sections in the Classic Customizer.
* Expanded grid settings in loops from 12 to more columns.
* Corrected missing icons in the Classic Suite menu.
* Fixed a crash caused by border controls in the Accordion Block.
* Resolved problems with rendering of Conditional Block.
* Fixed dynamic templates causing block errors during text/background highlighting.
* Resolved an issue where templates imported globally displayed encoded characters.
* Fixed an issue where popups were uneditable
* Corrected a bug where YouTube videos did not work as dynamic background elements.
* Resolved issues with incorrect IDs in post loops.
* Fixed visibility of user management feature in the Features section.
* Resolved errors when installing FSE Theme Template Library.
* Addressed bugs in image editing functions in dynamic templates.
* Fixed the buggy behavior of the Hub search function.
* Corrected a screen reader issue where Greyd List icons were read as letters.

## 2.11.0 - 2024-11-21
**Improvements**

* Fixed some wrong wordings
* Popover buttons and icons are visible in templates, even when dynamic values are empty. 
* Improved Conditional Content by adding support for post IDs and template-specific conditions. 
* Added Basic Theme Styles availability in the plugin.  

**Bugfixes**

* Fixed an issue where the loop did not recognize terms in imported post types.  
* Fixed a bug where popover content scrolled horizontally on mobile devices.  
* Fixed the active state of Accordion titles, which was not displayed correctly.  
* Fixed a mobile display issue caused by misaligned button text.  
* Added support for taxonomy descriptions in Dynamic Tag features.  
* Fixed an issue with dynamic spacer values not updating correctly in the frontend. 
* Fixed a bug where hyphens in search terms resulted in no results in Auto Search.  
* Fixed inconsistent behavior of the Dynamic Image Block in the backend.  
* Fixed a non-functional skip link in forms.  
* Improved the automatic assignment of theme assets during post imports.  
* Fixed a compatibility issue with the "Simply Schedule Appointments" plugin.  
* Fixed an issue where entering "10.000" in Dynamic Templates erased the text.  
* Fixed a problem where closing a pop-up in a pinned box scrolled to the end of the page.  
* Fixed an additional span tag issue causing line breaks in Gravity Forms
* Fixed a Classic Theme issue where centered alignment in the editor was not applied to Greyd Buttons.  
* Fixed a Cover Block issue in Dynamic Templates where dynamic background images did not display correctly in the frontend.  
* Fixed an issue with the "Space to Text" Feature in Greyd Lists
* Added an option to save references in Dynamic Templates as slugs.  
* Fixed an issue where the focus point in "Media & Text" was not dynamic.  

## 2.10.0 - 2024-10-30
**Features**

* dynamic templates can now manually be reloaded in editor 
* dynamic templates can now be created from the editor
* dynamic templates can now be detached in editor
* dynamic templates can now be resetted in editor


**Improvements**

* configure admin mails like user mails
* added the possibility to add a reply to header to admin mails
* include all terms in export if the taxonomy is hierarchical 
* improved backend performance of the tabs block
* multiple improvements and fixes for animations


**Bugfixes**

* Fixed an issue with Dynamic tags for dynamic taxonomies
* Fixed an issue with anchor links inside popovers is clicked
* Fixed an issue with CSS selector specificity in theme.css
* Fixed an issue with css of the query loop
* Fixed an issue where css animations where not re-inited in live queries
* Fixed an issue in popups with spacing values
* Fixed an issue with dynamic images in cover block
* FIxed an issue with accordions in responsive queries
* Fixed an issue with the alignment of radio buttons
* Fixed an issue with download triggers in post export and global content
* Fixed an issue where new dynamic templates were delcared as system templates


## 2.9.2 - 2024-10-07
Minor addition to the last update: Fixed an issue that could prevent the "Post Content" dynamic tag from rendering if the WP_Query was rebuilt inside the post template block.

## 2.9.1 - 2024-10-04
Fixed high prio issue preventing the single post content from being rendered when using the "Post Content" dynamic tag.

## 2.9.0 - 2024-10-01
**Features**

* Render accordion content as structured JSON-LD data

**Improvements**

* Adjust dashboard changelog for newer format
* A11Y: remove unnecessary title attribute on Submit Button block
* Dynamic image: make sizeSlug dynamic
* Popup admin script error handling
* Interactions with hidden fields are not saved anymore
* Wrap greyd popover in list element when it is child of core/navigation
* Render shortcodes in dynamic tags from posttype custom fields
* Posttypes meta field placeholder now control text
* Improved rendering of submenues in navigation

**Bugfixes**

* Fixed the rendering of custom meta-fields media-links for posttype 'product'
* Fixed autosearch when rest-search response is a string
* Fixed fatal error on post import when 'parent' is a WP_Term object
* Fixed rendering of icon if trigger is used on core button block
* Hide anything in :before on hidden-trigger-link
* Fixed dynamic support in greyd/popover-button block
* Fixed getDisplayVal function in Greyd.Forms Public JS
* Fixed loading spinner position in live search
* Fixed groups ignoring max-width
* Stretch img tag if SVG is used (editor only)
* Fixed advanced filter reversed order
* Fixed rendering of global dynamic tags
* Fixed alignment of greyd/button block in editor
* Changed unique id of popover block to avoid duplicates
* Fixed potential endless loop caused by updating block attributes from within the child and parent at the same time
* Dynamic Template Embeds were not displayed in frontend when original embed block is empty

## 2.8.0 - 2024-09-03
**Features**

* The HTML structure of the post slider block can now be customized
* New button options to align the icon separately to the content
* New button option to keep a button optional even with an icon set (eg. when used in dynamic templates)
* Added theme support for the new section stylings in groups and columns

**Improvements**

* Reworked translations accross all plugins
* Group backgrounds can now be made dynamic
* Video backgrounds can now be made dynamic
* Videos can now be dynamically be replaced using post data (such as meta options)
* Improved a contrast ratio inside the license banner
* Improved the handling of polylang translations with post import and global content
* Hierarchy inside taxonomy terms is not being lost after post import, even when the post does not have a parent term relationship
* Selected terms inside query loop advanced filter are not being lost on post import
* Form entry export can be accessed using a separate capability, allowig for further customization
* New notification directky inside the editor, when a wp-template is not using a main-element
* Accessibility improvements for: range-slider, form after message, dynamic form tags, conditional container
* The link picker now supports aria-labels

**Bugfixes**

* Fixed various issues in the greyd list inside the editor after the latest apiVersion update
* Fixed an issue that lead to animations being gone inside the editor after a reload
* Fixed an issue that lead to trigger being gone inside the editor after a reload
* Fixed a Javascript error inside popups
* Fixed an issue not displaying gradients when a preset color was removed in the global styles
* Image block aspect ratio was incorrectly labelled '3:4' twice
* Accordion icons were not display correctly in the editor
* Fixed accordion horizontal overflow
* Border in accordions was affecting parent and child element at the same time
* Fixed an error with registration linsks from within form mails
* Fixed downgrade links inside license admin page
* Fixed an issue with different ImagePanel options when they have the same name
* Fixed a Javascript error on ImagePanel Select inside popup
* Fixed margins inside radio buttons on classic sites
* Fixed a potential fatal error after the latest when using a WPBakery setup
* Fixed a compatibility issue calling the the_content filter incorrectly when using the dynamic tag 'content'
* Dynamic images in media and text block where not compatible with the latest WordPress update

## 2.7.2 - 2024-08-13
**Bugfixes**

* Fixed form script enqueue in classic_suite and autoptimize setups
* Fixed greyd.data for advanced filter
* Fixed a javascript error in editor
* Fixed perPage/items sync in nested query loops
* Enabled advancedFilter for core Post-Template
* Fixed issues exporting forms that link to pages by ID

## 2.7.1 - 2024-08-02
**Bugfixes**

* Fixed an issue that lead to classes and IDs not being saved in the content box markup
* Fixed an issue where a post slider was not filtered live, when the search bar was wrapped in multiple blocks

## 2.7.0 - 2024-08-01
**Features**

* New Query & Search Features:
* Added a date picker and several filter buttons blocks
* New post slider features
* Improved support for core query loop and search features
* Live search now works with every query loop independently from the search template

**Improvements**

* Adjustments for compatibility with WordPress 6.6
* Hide empty accordion items
* Breaking Change: The default query loop is no longer rendered as a slider with pagination. This affects only the completely empty default state, which was previously incorrect.

**Bugfixes**

* Fixed a permissions issue with the Greyd installer
* Added missing blocks-query.css
* Fixed too small iframe in template editing mode
* Fixed a problem with background CSS classes in content boxes
* Fixed an issue with popups with several triggers
* Fixed a padding issue in navigation sub menus
* Fixed some issues that could occur when switching to a child theme
* Fixed an issue with Dynamic Tags

## 2.6.0 - 2024-07-10
**Improvements**

* Theme JSON 3.0: Utilize new Features
* Conditional Content: Time-Based Live Conditions

**Bugfixes**

* fixed some problems with wordings
* Single Post Content is not rendered as expected with ARMember Plugin
* fixed some issues with the theme assets import/export
* fixed an issue with the Dynamic Tag "number of posts"
* fixed an compatibility issue with Gutenberg 18.3
* fixed a javascript issue when a wp-template was included inside a dynamic template

## 2.5.1 - 2024-06-21
* Adhoc fix for block validation error inside columns

## 2.5.0 - 2024-06-21
**Improvements**

* Greyd blocks now support spacing preset sizes
* added gap settings for buttons
* added buttons focus outline
* added uppercase & letter spacing as global styling option for greyd buttons
* moved popover block to navigation menus (beta)
* moved popup block rendering to php

**Bugfixes**

* fixed an issue where meta fields disappeared in forms
* fixed multiple issues with the hub import
* fixed an issue with alignments in frontend and backend in greyd blocks
* fixed an issue with the pinned box
* fixed an issue with max and min attributes in forms
* fixed an issue with the edit template button
* fixed an issue with the cover block and dynamic variables
* fixed an issue with the fluid option settings
* fixed an issue with the bulk export of templates and template parts
* fixed an issue with the alignment of list icons
* fixed an issue with the converter
* fixed an issue with lottie animations
* fixed a critical error in single page templates
* fixed an issue with missing break points

## 2.4.2 - 2024-06-10
* Patch for creating bootstrap-styled responsive columns

## 2.4.0 - 22.05.2024
* Re-added index.php to the Greyd Theme to prevent error when using child themes.

## 2.4.0 - 22.05.2024
**Improvements**

* Release of the new Greyd Theme in the official repo
* Make Google and custom fonts deprecation notice always visible in Greyd Global Styles
* Anchor active navigation do now support all types of anchor links

**Bugfixes**

* Added 'classic' flag to all template library system templates
* Fixed an issue not closing all popups using the ESC key
* Fixed an issue where the second header does not open the selected offmenu
* Fixed an issue displayinmg form tooltips outside of the screen
* Removed z-index overwrite for floating boxes.
* Fixed an issue where custom fonts did not display dotted query paginations

## 2.3.0 - 08.05.2024
**Bugfixes**

* Resolved a fatal error in the Site Editor caused by the Headless feature.
* Fixed SSL error occurring during Post Export.
* Corrected post type filter on the global content page.
* Addressed unrecognized conflicts during Global Content Import.
* Implemented filtering of selected query live filter terms.
* Fixed incorrect display of Theme Status.
* Set the slider max-width for new layout controls to 1200.
* Corrected the incorrect display of checkbox styles as radio-buttons or switch inputs.
* Removed a misleading error message within form interfaces.
* Added the plugin filter to filter_post_id function in Greyd.Forms.
* Removed background-isolation & z-index causing z-stacking errors after
* Removed orphaned search result styling leading to errors
* Fixed Reference Error in Search & Replace Feature to Post_Export feature.
* Fixed an issue with gradient presets after migrating the theme converter to the plugin.
* Fixed isolation for backgrounds in classic versions.
* Fixed errors in post export regex patterns if anchor attribute is set (usually as the first attribute).
* Fixed some issues in the Automator and Synced_Posttype classes used for the Headless Posttype features.
* Fixed caching issues on global content list pages with remote connection contents.
* Fixed rendering of forms when the filtered post-id does not exist anymore (e.g., in polylang after manual import).
* Only send verification mail during redo-option action when the user is not verified.
* Fixed an error during Hub-Import when Global Content is not verified.
* Fixed an issue where Safari-based browsers could not calculate the height of images correctly.
* Fixed an issue calculating the default height of post slider pages.

**Improvements**

* Updated Readme Files.
* Added the ability to manually repair global styles.
* Improved UI of Greyd-tabs components inside the block editor.
* Added architecture documentation to Global Content Readme.
* Adjusted spacing scaling controls for new layoutControl components.
* Added license filtering for better security & auto-validation through constant support.
* Added the possibility to customize the animation speed via the global var 'greydSliderSpeed'.
* Removed orphaned greyd_tp_management references.
* Improved error handling in Greyd.Forms Hubspot API.


## 2.2.0 - 12.04.2024
**Bugfixes**

* fixed a bug where authors could not edit dynamic templates in post content
* fixed a problem with conditional fields 
* fixed a problem with multistep forms
* fixed multiple compatibility issues with woocommerce
* fixed a compatibility issue with gutenberg where the navigation block was causing a block error when clicking on the typography panel
* fixed an issue with the site connector
* fixed a compatibility issue with polylang
* fixed an issue with revisions and dynamic tags
* fixed an issue where the query loop crashed in page templates
* fixed a block error with the query loop when selecting a single post 
* fixed an issue with wrong alignment of greyd buttons in the frontend
* fixed an issue with videos in frontend when lazyload was no activated
* fixed multiple issues with small differences between frontend and editor
* fixed an issue with custom selects not wrapping correctly
* fixed a block error with greyd buttons

**Improvements**

* added an extra incompatibility notice for old classic fullsite templates
