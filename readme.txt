=== OliForge Country Select for Contact Form 7 ===
Contributors: oliforge
Tags: contact form 7, countries, flags, multilingual, searchable select
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: contact-form-7
Stable tag: 3.2.2
License: GPLv2 or later

Adds a searchable, multilingual country selector with local SVG flags and server-side validation to Contact Form 7.

== Description ==

OliForge Country Select for Contact Form 7 provides a custom country_select form tag for Contact Form 7.

Features:
* Search countries by translated name or ISO alpha-2 code.
* Locally bundled SVG flags with no CDN or remote API dependency.
* Animated toggle chevron and selected-country flag.
* Administrator settings for enabling or disabling countries globally.
* English, German, Spanish, French, Ukrainian and Russian country names.
* Automatic language selection from the current WordPress locale.
* Per-field language overrides.
* Stable ISO alpha-2 values for email, CRM, webhook and database integrations.
* Server-side validation against the administrator-approved allowlist.
* Keyboard navigation, accessible native select fallback and RTL-ready layout.
* Developer filters for country data, translated names and allowed countries.
* Minified production CSS and JavaScript.
* Per-field include, exclude, preferred-country and default-country controls.
* Support for id, class, tabindex, autocomplete, aria-* and data-* form-tag attributes.
* Placeholder can be selected again to clear the field; required fields then fail validation correctly.
* Save named country lists (list:slug) from the settings page and restrict any field to one list.
* Mail-tag output shows the full translated country name with its ISO code, e.g. Ukraine (UA); submitted values used by integrations remain the plain ISO code.

== Installation ==
1. Install and activate Contact Form 7.
2. Upload and activate this plugin.
3. Open Settings > OliForge Country Select to configure language and available countries.
4. Add a country field to a Contact Form 7 form:
   [country_select* your-country placeholder "Select a country"]
5. Add [your-country] to the Contact Form 7 Mail template.

The submitted value is an ISO 3166-1 alpha-2 country code such as UA, DE or US.

== Form Tag Usage ==

Required field:
[country_select* your-country placeholder "Select a country"]

Optional field:
[country_select your-country]

Language overrides:
[country_select* your-country language:en]
[country_select* your-country language:de]
[country_select* your-country language:es]
[country_select* your-country language:fr]
[country_select* your-country language:uk]
[country_select* your-country language:ru]

Advanced examples:
[country_select* country id:country class:form-control tabindex:5 autocomplete:country language:de]
[country_select country include:UA,PL,DE,FR]
[country_select country exclude:RU,BY]
[country_select country preferred:UA,PL,DE default:UA]
[country_select country default:auto]
[country_select country list:eu]

The default:auto option uses the region part of the current WordPress locale when available, for example uk_UA => UA.

== Settings ==
Open Settings > OliForge Country Select.

Available display languages:
* Automatic — follows the current WordPress locale.
* English.
* Deutsch.
* Español.
* Français.
* Українська.
* Русский.

All countries are enabled by default. Administrators may search, select all, clear all or enable countries individually.

Administrators can also create named country lists (a slug plus a subset of countries) using a two-column picker. Each list saves and deletes independently of the others and of the settings above. A list's slug can then be referenced from any field with list:slug to show only that list.

== Developer API ==

Translate an ISO code after the plugin has initialized:
OliForge_CF7_Country_Select::translate_country_name( 'UA', 'uk' );
OliForge_CF7_Country_Select::translate_country_name( 'UA', 'ru' );

Available filters:

oliforge_country_select_countries
Filters the canonical ISO code => English fallback country array during initialization.

oliforge_country_select_country_name
Filters one translated country name. Arguments: $name, $code, $language.

oliforge_country_select_allowed_countries
Filters the final enabled ISO code => translated name array. Arguments: $allowed, $language.

== Frequently Asked Questions ==

= Will existing country_select tags continue to work? =
Yes. The country_select and country_select* Contact Form 7 tags are preserved.

= Does the plugin use external services for flags or translations? =
No. Flags and all supported country-name translations are bundled locally. No CDN or external API is required.

= What value is submitted? =
The ISO 3166-1 alpha-2 code is submitted. Translation affects display only.

= What happens for an unsupported WordPress locale? =
Automatic mode falls back to English.

== Changelog ==

= 3.2.2 =
* Load dropdown flag images only when their rows enter or approach the visible scroll area, with a compatibility fallback for browsers without IntersectionObserver.

= 3.2.1 =
* Deferred dropdown flag loading until the country selector is first opened, significantly reducing initial page requests.

= 3.2.0 =
* Added named country lists: save a slug plus a subset of countries from the settings page and restrict any field to it with list:slug.
* Each country list now has its own Save and Delete buttons and saves independently of the other lists and of the settings above, with duplicate-slug protection on both the browser and the server.
* Country pickers (allowed countries and each list) use a two-column "available / selected" transfer control with per-column search.
* [country] and other country_select mail-tags now output the full translated country name with its ISO code, e.g. "Ukraine (UA)", instead of the bare code; submitted values used by integrations are unaffected and remain the plain ISO code.
* Redesigned the settings page with the OliForge brand system: header, cards, buttons, selects, checkboxes and a self-dismissing save confirmation.

= 3.1.1 =
* Sanitized submitted country values before validation and retrieval.
* Updated WordPress compatibility metadata to 7.0.
* Aligned the plugin name with readme.txt.
* Reduced tags to the WordPress.org maximum of five.
* Shortened the readme short description to under 150 characters.

= 3.1.0 =
* Added protected full-column geometry for the visible country control in grid and flex layouts.
* Prevented theme rules such as fixed `.custom-field` widths from separating the input from its flag and chevron.
* Kept author classes on the visible input for typography, colors, borders and border-radius while protecting only layout dimensions.
* Made the dropdown follow the exact width of the country field column.
* Avoided copying author field classes to the outer wrapper, preventing duplicate borders and backgrounds.

= 3.0.2 =
* Removed JavaScript-generated inline widths from the enhanced country selector.
* The component, visible control, and dropdown now follow the width of their CF7 grid or flex column using CSS.
* Fixed narrow 110px rendering in responsive three-column forms.

= 2.1.2 =
* Added a global validation-appearance setting; message-only validation is now the default.
* The visible selector no longer receives a red validation border unless explicitly enabled.
* Kept Contact Form 7 validation messages and ARIA invalid state active in both modes.
* Clears the country field error immediately after a valid country is selected.
* Closes open country dropdowns when Contact Form 7 reports a validation error.

= 2.1.1 =
* Prevented form-tag options from overriding internal accessibility state attributes: aria-invalid, aria-required, aria-describedby, aria-controls and aria-expanded.
* Preserved support for safe custom aria-* and data-* attributes while retaining the data-oliforge-* namespace reservation.
* Restored readable development CSS sources and rebuilt genuinely minified production CSS files.
* Added release verification for protected ARIA attributes and CSS source/minified-file separation.

= 2.1.0 =
* Added a native Contact Form 7 Tag Generator v2 dialog named Country Select.
* Generator supports required, name, placeholder, language, preferred/include/exclude lists, default, autocomplete, tabindex, ID and classes.
* Added per-field nosearch, noflags and nochevron interface options.
* Added lifecycle synchronization for wpcf7invalid, wpcf7submit, wpcf7mailsent, wpcf7mailfailed and wpcf7reset.
* Replaced broad repeated DOM rescanning with added-node initialization for dynamically inserted forms.
* Preserved existing country_select tags and mail-tags without migration.

= 2.0.4 =
* Fixed Contact Form 7 AJAX validation-message placement for the custom country field.
* Added the required data-name attribute to the CF7 form-control wrapper.
* Added server-rendered validation error fallback and synchronized aria-invalid state.
* No additional Contact Form 7 setting is required.

= 2.0.3 =
* Fixed an infinite MutationObserver loop after Contact Form 7 AJAX validation.
* Validation state now observes only the native select and error-tip DOM changes.
* Added requestAnimationFrame batching for stable error-state synchronization.
* Required empty country fields now show the Contact Form 7 validation message without freezing the page.

= 2.0.2 =
* Fixed production CSS minification that removed the descendant combinator before :where(), causing search and country options to fall back to browser button styles.
* Restored isolated dropdown styling while keeping Contact Form 7/theme classes only on the visible toggle control.
* Added release verification that source and production selectors preserve the required descendant combinators.

= 2.0.1 =
* Apply Contact Form 7 custom classes to the visible country selector control.
* Synchronize CF7 invalid state, aria-invalid, and error message association with the custom control.
* Lower default style specificity so theme field styles can override the plugin defaults.


= 2.0.0 =
* Added per-field include:, exclude: and preferred: ISO country lists.
* Added default:XX and locale-based default:auto selection.
* Added support for tabindex:, autocomplete:, aria-* and data-* attributes while preserving id: and class:.
* Added a preferred-country section to the enhanced dropdown without duplicating native select values.
* Fixed placeholder clearing so selecting “Select a country” always submits an empty value.
* Strengthened required-field validation so missing, blank and non-ISO placeholder values cannot pass.
* Synchronized the custom UI after native change and form reset events.
* Updated plugin header, version constant, stable tag and changelog to 2.0.0.

= 1.5.1 =
* Canonicalized valid submitted country values to uppercase ISO alpha-2 codes before Contact Form 7 mail tags and integrations process them.
* Rejected arrays and other invalid submitted types even for optional country fields.
* Revalidated and sanitized country maps returned by developer filters.
* Sanitized filtered country labels before returning them through the public translation API.
* Added explicit direct-access guards to all PHP data files.
* Added uninstall cleanup for the plugin's country allowlist and display-language options.
* Synchronized plugin header, version constant, stable tag and changelog at 1.5.1.

= 1.5.0 =
* Added complete Ukrainian country-name translations.
* Added complete Russian country-name translations.
* Reorganized translations into one country-centric structure: ISO code => language => name.
* Added automatic detection for uk, uk_UA, ru and ru_RU locales.
* Added Ukrainian and Russian options to global settings and per-field language overrides.
* Added the oliforge_country_select_countries developer filter.
* Added the oliforge_country_select_country_name developer filter.
* Added the oliforge_country_select_allowed_countries developer filter.
* Added RTL-ready frontend alignment rules for future right-to-left languages.
* Expanded plugin header and readme descriptions for the OliForge plugin suite.
* Updated plugin header, constant, stable tag and changelog versioning to 1.5.0.

= 1.4.0 =
* Added locally bundled English, German, Spanish and French country names.
* Added automatic language selection based on the current WordPress locale.
* Added a global country-name language setting.
* Added per-field language overrides.
* Added a public PHP translation helper for ISO alpha-2 country codes.

= 1.3.0 =
* Added an animated toggle chevron.
* Added the selected-country flag to the field button.
* Added a settings page for enabling or disabling countries globally.
* Added server-side validation against the administrator-approved country list.

= 1.2.0 =
* Rebranded and packaged for the OliForge plugin suite.
* Added unique OliForge constants, classes, handles and selectors.
* Preserved existing Contact Form 7 tags for backward compatibility.

= 1.1.1 =
* Hardened country validation and rebuilt minified production assets.

= 1.1.0 =
* Added country search and keyboard navigation.

= 1.0.0 =
* Initial release.
