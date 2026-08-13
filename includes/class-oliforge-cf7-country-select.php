<?php
/**
 * Contact Form 7 country selector integration.
 *
 * @package OliForge_CF7_Country_Select
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OliForge_CF7_Country_Select {
	private const OPTION_ALLOWED_COUNTRIES = 'oliforge_cf7_country_select_allowed_countries';
	private const OPTION_DISPLAY_LANGUAGE  = 'oliforge_cf7_country_select_display_language';
	private const OPTION_VALIDATION_BORDER = 'oliforge_cf7_country_select_validation_border';

	/** @var array<string, string> */
	private static array $countries = array();

	/** @var array<string, array<string, string>> Country code => language code => name. */
	private static array $country_translations = array();

	private const SUPPORTED_LANGUAGES = array( 'en', 'de', 'es', 'fr', 'uk', 'ru' );

	public static function init(): void {
		$countries       = require OLIFORGE_CF7_COUNTRY_SELECT_DIR . 'includes/countries.php';
		self::$countries = is_array( $countries ) ? $countries : array();
		self::$countries = self::sanitize_country_map(
			apply_filters( 'oliforge_country_select_countries', self::$countries )
		);

		$translations = require OLIFORGE_CF7_COUNTRY_SELECT_DIR . 'includes/countries-i18n.php';
		self::$country_translations = is_array( $translations ) ? $translations : array();

		add_action( 'wpcf7_init', array( __CLASS__, 'register_form_tag' ) );
		add_action( 'wpcf7_admin_init', array( __CLASS__, 'register_tag_generator' ), 30 );
		add_filter( 'wpcf7_validate_country_select', array( __CLASS__, 'validate' ), 10, 2 );
		add_filter( 'wpcf7_validate_country_select*', array( __CLASS__, 'validate' ), 10, 2 );
		add_filter( 'wpcf7_mail_tag_replaced_country_select', array( __CLASS__, 'replace_mail_tag_with_country_name' ), 10, 4 );
		add_filter( 'wpcf7_mail_tag_replaced_country_select*', array( __CLASS__, 'replace_mail_tag_with_country_name' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}


	public static function register_tag_generator(): void {
		if ( ! class_exists( 'WPCF7_TagGenerator' ) ) {
			return;
		}

		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add(
			'country_select',
			__( 'Country Select', 'oliforge-cf7-country-select' ),
			array( __CLASS__, 'render_tag_generator' ),
			array( 'version' => '2' )
		);
	}

	public static function render_tag_generator( $contact_form, $args = array() ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$panel_id = isset( $args['content'] ) ? sanitize_html_class( (string) $args['content'] ) : 'tag-generator-panel-country_select';
		$ref = static function ( string $suffix ) use ( $panel_id ): string {
			return $panel_id . '-' . sanitize_html_class( $suffix );
		};
		?>

		<header class="description-box">
			<h3><?php echo esc_html__( 'OliForge Country Select', 'oliforge-cf7-country-select' ); ?></h3>
			<p><?php echo esc_html__( 'Generate a searchable country selector with local flags, translations, filtering and Contact Form 7 validation.', 'oliforge-cf7-country-select' ); ?></p>
		</header>
		<div class="control-box">
			<input type="hidden" data-tag-part="basetype" value="country_select" />

			<fieldset>
				<legend id="<?php echo esc_attr( $ref( 'type' ) ); ?>"><?php echo esc_html__( 'Field type', 'oliforge-cf7-country-select' ); ?></legend>
				<label><input type="checkbox" data-tag-part="type-suffix" value="*" /> <?php echo esc_html__( 'Required field', 'oliforge-cf7-country-select' ); ?></label>
			</fieldset>

			<fieldset>
				<legend id="<?php echo esc_attr( $ref( 'name' ) ); ?>"><?php echo esc_html__( 'Field name', 'oliforge-cf7-country-select' ); ?></legend>
				<input type="text" required data-tag-part="name" value="country" pattern="[A-Za-z][A-Za-z0-9_\-]*" aria-labelledby="<?php echo esc_attr( $ref( 'name' ) ); ?>" />
			</fieldset>

			<fieldset>
				<legend><?php echo esc_html__( 'Placeholder', 'oliforge-cf7-country-select' ); ?></legend>
				<input type="text" data-tag-part="value" value="<?php echo esc_attr__( 'Select a country', 'oliforge-cf7-country-select' ); ?>" />
				<input type="hidden" data-tag-part="option" data-tag-option="placeholder" value="1" />
			</fieldset>

			<fieldset>
				<legend><?php echo esc_html__( 'Language', 'oliforge-cf7-country-select' ); ?></legend>
				<select data-tag-part="option" data-tag-option="language:">
					<option value=""><?php echo esc_html__( 'Use plugin setting', 'oliforge-cf7-country-select' ); ?></option>
					<option value="en">English</option><option value="de">Deutsch</option><option value="es">Español</option>
					<option value="fr">Français</option><option value="uk">Українська</option><option value="ru">Русский</option>
				</select>
			</fieldset>

			<fieldset>
				<legend><?php echo esc_html__( 'Country lists', 'oliforge-cf7-country-select' ); ?></legend>
				<label><?php echo esc_html__( 'Preferred ISO codes', 'oliforge-cf7-country-select' ); ?><br><input type="text" data-tag-part="option" data-tag-option="preferred:" placeholder="UA,PL,DE" /></label><br>
				<label><?php echo esc_html__( 'Include only', 'oliforge-cf7-country-select' ); ?><br><input type="text" data-tag-part="option" data-tag-option="include:" placeholder="UA,PL,DE,FR" /></label><br>
				<label><?php echo esc_html__( 'Exclude', 'oliforge-cf7-country-select' ); ?><br><input type="text" data-tag-part="option" data-tag-option="exclude:" placeholder="RU,BY" /></label>
			</fieldset>

			<fieldset>
				<legend><?php echo esc_html__( 'Default and browser integration', 'oliforge-cf7-country-select' ); ?></legend>
				<label><?php echo esc_html__( 'Default country', 'oliforge-cf7-country-select' ); ?><br><input type="text" data-tag-part="option" data-tag-option="default:" placeholder="UA or auto" /></label><br>
				<label><?php echo esc_html__( 'Autocomplete', 'oliforge-cf7-country-select' ); ?><br><input type="text" data-tag-part="option" data-tag-option="autocomplete:" value="country" /></label><br>
				<label><?php echo esc_html__( 'Tab index', 'oliforge-cf7-country-select' ); ?><br><input type="number" data-tag-part="option" data-tag-option="tabindex:" /></label>
			</fieldset>

			<fieldset>
				<legend><?php echo esc_html__( 'Interface', 'oliforge-cf7-country-select' ); ?></legend>
				<label><input type="checkbox" data-tag-part="option" data-tag-option="nosearch" /> <?php echo esc_html__( 'Disable search', 'oliforge-cf7-country-select' ); ?></label><br>
				<label><input type="checkbox" data-tag-part="option" data-tag-option="noflags" /> <?php echo esc_html__( 'Hide flags', 'oliforge-cf7-country-select' ); ?></label><br>
				<label><input type="checkbox" data-tag-part="option" data-tag-option="nochevron" /> <?php echo esc_html__( 'Hide chevron', 'oliforge-cf7-country-select' ); ?></label>
			</fieldset>

			<fieldset>
				<legend><?php echo esc_html__( 'ID attribute', 'oliforge-cf7-country-select' ); ?></legend>
				<input type="text" data-tag-part="option" data-tag-option="id:" pattern="[A-Za-z][A-Za-z0-9_\-]*" />
			</fieldset>
			<fieldset>
				<legend><?php echo esc_html__( 'Class attribute', 'oliforge-cf7-country-select' ); ?></legend>
				<input type="text" data-tag-part="option" data-tag-option="class:" pattern="[A-Za-z0-9_\-\s]*" placeholder="cs-field cs-field--country" />
			</fieldset>
		</div>
		<footer class="insert-box">
			<div class="flex-container">
				<input type="text" class="code selectable" readonly data-tag-part="tag" aria-label="<?php echo esc_attr__( 'Generated form-tag', 'oliforge-cf7-country-select' ); ?>" />
				<button type="button" class="button button-primary" data-taggen="insert-tag"><?php echo esc_html__( 'Insert Tag', 'oliforge-cf7-country-select' ); ?></button>
			</div>
			<p class="mail-tag-tip"><?php echo esc_html__( 'Use this mail-tag in the Mail tab:', 'oliforge-cf7-country-select' ); ?> <input type="text" class="code selectable" readonly data-tag-part="mail-tag" /></p>
		</footer>
		<?php
	}

	public static function register_assets(): void {
		wp_register_style(
			'oliforge-cf7-country-select',
			OLIFORGE_CF7_COUNTRY_SELECT_URL . 'assets/css/country-select.min.css',
			array(),
			OLIFORGE_CF7_COUNTRY_SELECT_VERSION
		);

		wp_register_script(
			'oliforge-cf7-country-select',
			OLIFORGE_CF7_COUNTRY_SELECT_URL . 'assets/js/country-select.min.js',
			array(),
			OLIFORGE_CF7_COUNTRY_SELECT_VERSION,
			true
		);
	}

	public static function register_form_tag(): void {
		wpcf7_add_form_tag(
			array( 'country_select', 'country_select*' ),
			array( __CLASS__, 'render' ),
			array(
				'name-attr'         => true,
				'selectable-values' => true,
			)
		);
	}

	public static function render( $tag ): string {
		if ( empty( $tag->name ) ) {
			return '';
		}

		wp_enqueue_style( 'oliforge-cf7-country-select' );
		wp_enqueue_script( 'oliforge-cf7-country-select' );

		$name        = sanitize_key( $tag->name );
		$required    = $tag->is_required();
		$classes     = wpcf7_form_controls_class( $tag->type, 'oliforge-cf7-country-select__native' );
		$extra_class = $tag->get_class_option();
		$id          = sanitize_html_class( $tag->get_id_option() );
		$placeholder = $tag->get_option( 'placeholder', '', true );
		$language    = self::resolve_language( $tag->get_option( 'language', '', true ) );
		$show_validation_border = self::sanitize_validation_border( get_option( self::OPTION_VALIDATION_BORDER, '0' ) );

		if ( ! is_string( $placeholder ) || '' === trim( $placeholder ) ) {
			$placeholder = __( 'Select a country', 'oliforge-cf7-country-select' );
		}

		$classes   = trim( $classes . ' ' . $extra_class );
		$visible_classes = preg_replace( '/\b(?:oliforge-cf7-country-select__native|wpcf7-not-valid)\b/', '', $classes );
		$visible_classes = trim( preg_replace( '/\s+/', ' ', (string) $visible_classes ) );
		$countries = self::get_allowed_countries( $language );
		$include   = self::parse_country_option( $tag->get_option( 'include', '', true ) );
		$exclude   = self::parse_country_option( $tag->get_option( 'exclude', '', true ) );
		$preferred = self::parse_country_option( $tag->get_option( 'preferred', '', true ) );

		if ( $include ) {
			$countries = array_intersect_key( $countries, array_flip( $include ) );
		}
		if ( $exclude ) {
			$countries = array_diff_key( $countries, array_flip( $exclude ) );
		}
		$preferred = array_values( array_intersect( $preferred, array_keys( $countries ) ) );

		$selected = self::get_submitted_value( $name );
		if ( '' === $selected ) {
			$default = $tag->get_default_option( null, 'default:' );
			if ( is_string( $default ) && 'auto' === strtolower( trim( $default ) ) ) {
				$selected = self::get_locale_country_code();
			} else {
				$selected = self::normalize_country_code( $default );
			}
		}
		if ( '' !== $selected && ! isset( $countries[ $selected ] ) ) {
			$selected = '';
		}

		$validation_error = wpcf7_get_validation_error( $name );
		if ( $validation_error ) {
			$classes .= ' wpcf7-not-valid';
		}

		$atts = array(
			'name'                           => $name,
			'class'                          => $classes,
			'data-oliforge-country-select'   => '1',
			'data-placeholder'               => $placeholder,
			'data-visible-class'             => $visible_classes,
			'data-search-placeholder'        => __( 'Search country', 'oliforge-cf7-country-select' ),
			'data-no-results'                => __( 'No countries found', 'oliforge-cf7-country-select' ),
			'data-search-enabled'             => in_array( 'nosearch', (array) $tag->options, true ) ? '0' : '1',
			'data-show-flags'                 => in_array( 'noflags', (array) $tag->options, true ) ? '0' : '1',
			'data-show-chevron'               => in_array( 'nochevron', (array) $tag->options, true ) ? '0' : '1',
			'data-validation-border'           => $show_validation_border,
			'aria-required'                  => $required ? 'true' : 'false',
			'aria-invalid'                   => $validation_error ? 'true' : 'false',
		);

		if ( '' !== $id ) {
			$atts['id'] = $id;
		}
		if ( $required ) {
			$atts['required'] = 'required';
		}

		$tabindex = $tag->get_option( 'tabindex', 'signed_int', true );
		if ( is_string( $tabindex ) && '' !== $tabindex ) {
			$atts['tabindex'] = (string) (int) $tabindex;
		}
		$control_size = $tag->get_option( 'size', 'int', true );
		if ( ! is_string( $control_size ) || '' === $control_size || (int) $control_size < 1 ) {
			$control_size = '40';
		}
		$atts['data-control-size'] = (string) min( 100, (int) $control_size );

		$autocomplete = $tag->get_option( 'autocomplete', '', true );
		if ( is_string( $autocomplete ) && preg_match( '/^[a-zA-Z0-9_-]+$/D', $autocomplete ) ) {
			$atts['autocomplete'] = $autocomplete;
		}
		$atts = array_merge( $atts, self::get_safe_passthrough_attributes( $tag ) );

		// Contact Form 7 5.6+ uses data-name to locate the field wrapper and inject AJAX validation errors.
		// The legacy name class is retained for compatibility with older themes and CF7 versions.
		$html  = '<span class="wpcf7-form-control-wrap ' . esc_attr( $name ) . '" data-name="' . esc_attr( $name ) . '">';
		$html .= '<select ' . wpcf7_format_atts( $atts ) . '>';
		$html .= '<option value=""' . selected( $selected, '', false ) . ' data-placeholder-option="1">' . esc_html( $placeholder ) . '</option>';

		foreach ( $countries as $code => $country ) {
			$flag_url = OLIFORGE_CF7_COUNTRY_SELECT_URL . 'assets/flags/' . strtolower( $code ) . '.svg';
			$html    .= sprintf(
				'<option value="%1$s" data-flag="%2$s"%3$s%4$s>%5$s</option>',
				esc_attr( $code ),
				esc_url( $flag_url ),
				in_array( $code, $preferred, true ) ? ' data-preferred="1"' : '',
				selected( $selected, $code, false ),
				esc_html( $country )
			);
		}

		$html .= '</select>' . $validation_error . '</span>';
		return $html;
	}

	public static function validate( $result, $tag ) {
		$name        = sanitize_key( $tag->name );
		$is_present  = isset( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Contact Form 7 validates the form request.
		$is_string   = $is_present && is_string( $_POST[ $name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_value   = $is_string ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Contact Form 7 validates the form request.
		$value       = self::normalize_country_code( $raw_value );
		$raw_trimmed = trim( $raw_value );
		$allowed     = self::get_allowed_countries( 'en' );
		$include     = self::parse_country_option( $tag->get_option( 'include', '', true ) );
		$exclude     = self::parse_country_option( $tag->get_option( 'exclude', '', true ) );
		if ( $include ) {
			$allowed = array_intersect_key( $allowed, array_flip( $include ) );
		}
		if ( $exclude ) {
			$allowed = array_diff_key( $allowed, array_flip( $exclude ) );
		}

		if ( $is_present && ! $is_string ) {
			$result->invalidate( $tag, __( 'Please select a valid country.', 'oliforge-cf7-country-select' ) );
		} elseif ( $tag->is_required() && ( ! $is_present || '' === $raw_trimmed || '' === $value ) ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		} elseif ( '' !== $raw_trimmed && ( '' === $value || ! isset( $allowed[ $value ] ) ) ) {
			$result->invalidate( $tag, __( 'Please select a valid country.', 'oliforge-cf7-country-select' ) );
		} elseif ( '' !== $value ) {
			// Keep all Contact Form 7 mail tags and integrations on the canonical ISO alpha-2 value.
			$_POST[ $name ] = $value; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		return $result;
	}

	/**
	 * Replaces the ISO alpha-2 code in mail-tags (e.g. [country]) with
	 * "Country name (CODE)". The submitted value stays the canonical ISO
	 * code everywhere else (validation, webhooks, etc.).
	 */
	public static function replace_mail_tag_with_country_name( $replaced, $submitted, $html, $mail_tag ) {
		if ( ! is_string( $replaced ) || '' === $replaced ) {
			return $replaced;
		}

		$code     = strtoupper( trim( $replaced ) );
		$form_tag = $mail_tag->corresponding_form_tag();
		$language = $form_tag ? self::resolve_language( $form_tag->get_option( 'language', '', true ) ) : self::resolve_language();

		$name = self::translate_country_name( $code, $language );
		if ( '' === $name ) {
			return $replaced;
		}

		$label = sprintf( '%1$s (%2$s)', $name, $code );

		return $html ? esc_html( $label ) : $label;
	}

	public static function register_settings_page(): void {
		add_options_page(
			__( 'OliForge Country Select', 'oliforge-cf7-country-select' ),
			__( 'OliForge Country Select', 'oliforge-cf7-country-select' ),
			'manage_options',
			'oliforge-cf7-country-select',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings(): void {
		register_setting(
			'oliforge_cf7_country_select_settings',
			self::OPTION_ALLOWED_COUNTRIES,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_allowed_countries' ),
				'default'           => null,
			)
		);

		register_setting(
			'oliforge_cf7_country_select_settings',
			self::OPTION_DISPLAY_LANGUAGE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_display_language' ),
				'default'           => 'auto',
			)
		);


		register_setting(
			'oliforge_cf7_country_select_settings',
			self::OPTION_VALIDATION_BORDER,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_validation_border' ),
				'default'           => '0',
			)
		);
	}

	public static function sanitize_allowed_countries( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$valid = array();
		foreach ( $value as $code ) {
			$code = self::normalize_country_code( is_string( $code ) ? wp_unslash( $code ) : '' );
			if ( '' !== $code && isset( self::$countries[ $code ] ) ) {
				$valid[] = $code;
			}
		}
		return array_values( array_unique( $valid ) );
	}

	public static function sanitize_display_language( $value ): string {
		$value = is_string( $value ) ? strtolower( sanitize_key( $value ) ) : 'auto';
		return in_array( $value, array_merge( array( 'auto' ), self::SUPPORTED_LANGUAGES ), true ) ? $value : 'auto';
	}


	public static function sanitize_validation_border( $value ): string {
		return '1' === (string) $value ? '1' : '0';
	}

	public static function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'settings_page_oliforge-cf7-country-select' !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style(
			'oliforge-cf7-country-select-admin',
			OLIFORGE_CF7_COUNTRY_SELECT_URL . 'assets/css/admin.min.css',
			array(),
			OLIFORGE_CF7_COUNTRY_SELECT_VERSION
		);
		wp_enqueue_script(
			'oliforge-cf7-country-select-admin',
			OLIFORGE_CF7_COUNTRY_SELECT_URL . 'assets/js/admin.min.js',
			array(),
			OLIFORGE_CF7_COUNTRY_SELECT_VERSION,
			true
		);
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$saved            = get_option( self::OPTION_ALLOWED_COUNTRIES, null );
		$allowed          = null === $saved ? array_keys( self::$countries ) : self::sanitize_allowed_countries( $saved );
		$language_setting = self::sanitize_display_language( get_option( self::OPTION_DISPLAY_LANGUAGE, 'auto' ) );
		$validation_border = self::sanitize_validation_border( get_option( self::OPTION_VALIDATION_BORDER, '0' ) );
		$preview_language = self::resolve_language( $language_setting );
		$preview_countries = self::get_translated_countries( $preview_language );
		?>
		<div class="wrap oliforge-country-settings">
            <div style="display: flex; align-items: center; margin-bottom: 20px;">
                <img src="<?php echo esc_url( OLIFORGE_CF7_COUNTRY_SELECT_URL . 'src/OliForge_logo.png' ); ?>" alt="<?php esc_attr_e( 'OliForge', 'oliforge-cf7-country-select' ); ?>" width="64" height="64" style="vertical-align:middle;margin-right:8px;" />
                <h1 style="color:black; font-weight: bolder"><?php esc_html_e( 'OliForge Plugins', 'oliforge-cf7-country-select' ); ?></h1>
            </div>
			<h1><?php echo esc_html__( 'OliForge Country Select For Contact Form 7', 'oliforge-cf7-country-select' ); ?></h1>
			<p><?php echo esc_html__( 'Choose which countries are available in all Contact Form 7 country selectors. All countries are enabled by default.', 'oliforge-cf7-country-select' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'oliforge_cf7_country_select_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="oliforge-country-display-language"><?php echo esc_html__( 'Country name language', 'oliforge-cf7-country-select' ); ?></label></th>
						<td>
							<select id="oliforge-country-display-language" name="<?php echo esc_attr( self::OPTION_DISPLAY_LANGUAGE ); ?>">
								<option value="auto" <?php selected( $language_setting, 'auto' ); ?>><?php echo esc_html__( 'Automatic — use the current WordPress locale', 'oliforge-cf7-country-select' ); ?></option>
								<option value="en" <?php selected( $language_setting, 'en' ); ?>>English</option>
								<option value="de" <?php selected( $language_setting, 'de' ); ?>>Deutsch</option>
								<option value="es" <?php selected( $language_setting, 'es' ); ?>>Español</option>
								<option value="fr" <?php selected( $language_setting, 'fr' ); ?>>Français</option>
								<option value="uk" <?php selected( $language_setting, 'uk' ); ?>>Українська</option>
								<option value="ru" <?php selected( $language_setting, 'ru' ); ?>>Русский</option>
							</select>
							<p class="description"><?php echo esc_html__( 'A form tag can override this setting with language:en, language:de, language:es, language:fr, language:uk, or language:ru.', 'oliforge-cf7-country-select' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Validation appearance', 'oliforge-cf7-country-select' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_VALIDATION_BORDER ); ?>" value="1" <?php checked( $validation_border, '1' ); ?>>
								<?php echo esc_html__( 'Show a validation border on the visible country selector', 'oliforge-cf7-country-select' ); ?>
							</label>
							<p class="description"><?php echo esc_html__( 'Disabled by default. The Contact Form 7 validation message and accessibility state remain active.', 'oliforge-cf7-country-select' ); ?></p>
						</td>
					</tr>
				</table>
				<div class="oliforge-country-settings__toolbar">
					<input type="search" class="regular-text" id="oliforge-country-settings-search" placeholder="<?php echo esc_attr__( 'Search countries', 'oliforge-cf7-country-select' ); ?>">
					<button type="button" class="button" data-oliforge-select-all><?php echo esc_html__( 'Select all', 'oliforge-cf7-country-select' ); ?></button>
					<button type="button" class="button" data-oliforge-clear-all><?php echo esc_html__( 'Clear all', 'oliforge-cf7-country-select' ); ?></button>
					<span class="oliforge-country-settings__count" aria-live="polite"></span>
				</div>
				<div class="oliforge-country-settings__grid">
					<?php foreach ( $preview_countries as $code => $country ) : ?>
						<label class="oliforge-country-settings__item" data-country-search="<?php echo esc_attr( strtolower( $country . ' ' . $code ) ); ?>">
							<input type="checkbox" name="<?php echo esc_attr( self::OPTION_ALLOWED_COUNTRIES ); ?>[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $allowed, true ) ); ?>>
							<img src="<?php echo esc_url( OLIFORGE_CF7_COUNTRY_SELECT_URL . 'assets/flags/' . strtolower( $code ) . '.svg' ); ?>" alt="" width="24" height="18">
							<span><?php echo esc_html( $country ); ?></span>
							<code><?php echo esc_html( $code ); ?></code>
						</label>
					<?php endforeach; ?>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** Translate one ISO alpha-2 country code to a supported display language. */
	public static function translate_country_name( string $code, string $language = 'auto' ): string {
		$code = self::normalize_country_code( $code );
		if ( '' === $code || ! isset( self::$countries[ $code ] ) ) {
			return '';
		}
		$language = self::resolve_language( $language );
		$name     = self::$country_translations[ $code ][ $language ] ?? self::$country_translations[ $code ]['en'] ?? self::$countries[ $code ];
		$filtered = apply_filters( 'oliforge_country_select_country_name', $name, $code, $language );
		return is_string( $filtered ) ? sanitize_text_field( $filtered ) : sanitize_text_field( (string) $name );
	}

	/** @return array<string, string> */
	private static function get_translated_countries( string $language ): array {
		$language   = self::resolve_language( $language );
		$translated = array();
		foreach ( self::$countries as $code => $fallback ) {
			$translated[ $code ] = self::translate_country_name( $code, $language ) ?: $fallback;
		}
		return $translated;
	}

	private static function resolve_language( $requested = 'auto' ): string {
		$requested = is_string( $requested ) ? strtolower( sanitize_key( $requested ) ) : 'auto';
		if ( in_array( $requested, self::SUPPORTED_LANGUAGES, true ) ) {
			return $requested;
		}

		$setting = self::sanitize_display_language( get_option( self::OPTION_DISPLAY_LANGUAGE, 'auto' ) );
		if ( 'auto' !== $setting && in_array( $setting, self::SUPPORTED_LANGUAGES, true ) ) {
			return $setting;
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale = strtolower( str_replace( '-', '_', (string) $locale ) );
		$prefix = substr( $locale, 0, 2 );
		return in_array( $prefix, self::SUPPORTED_LANGUAGES, true ) ? $prefix : 'en';
	}

	/** @return array<string, string> */
	private static function get_allowed_countries( string $language = 'auto' ): array {
		$countries = self::get_translated_countries( $language );
		$saved = get_option( self::OPTION_ALLOWED_COUNTRIES, null );
		if ( null === $saved ) {
			$allowed = $countries;
		} else {
			$codes   = self::sanitize_allowed_countries( $saved );
			$allowed = array_intersect_key( $countries, array_flip( $codes ) );
		}

		$allowed = apply_filters( 'oliforge_country_select_allowed_countries', $allowed, $language );
		return self::sanitize_allowed_country_map( $allowed );
	}

	/** @return array<string, string> */
	private static function sanitize_country_map( $countries ): array {
		if ( ! is_array( $countries ) ) {
			return array();
		}

		$clean = array();
		foreach ( $countries as $code => $name ) {
			$code = self::normalize_country_code( is_string( $code ) ? $code : '' );
			if ( '' === $code || ! is_string( $name ) ) {
				continue;
			}
			$name = sanitize_text_field( $name );
			if ( '' !== $name ) {
				$clean[ $code ] = $name;
			}
		}
		return $clean;
	}

	/** @return array<string, string> */
	private static function sanitize_allowed_country_map( $countries ): array {
		if ( ! is_array( $countries ) ) {
			return array();
		}

		$clean = array();
		foreach ( $countries as $code => $name ) {
			$code = self::normalize_country_code( is_string( $code ) ? $code : '' );
			if ( '' === $code || ! isset( self::$countries[ $code ] ) || ! is_string( $name ) ) {
				continue;
			}
			$name = sanitize_text_field( $name );
			if ( '' !== $name ) {
				$clean[ $code ] = $name;
			}
		}
		return $clean;
	}


	/** @return string[] */
	private static function parse_country_option( $value ): array {
		if ( ! is_string( $value ) ) {
			return array();
		}
		$codes = preg_split( '/[\s,|]+/', wp_unslash( $value ), -1, PREG_SPLIT_NO_EMPTY );
		$clean = array();
		foreach ( is_array( $codes ) ? $codes : array() as $code ) {
			$code = self::normalize_country_code( $code );
			if ( '' !== $code && isset( self::$countries[ $code ] ) ) {
				$clean[] = $code;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	private static function get_locale_country_code(): string {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale = str_replace( '-', '_', (string) $locale );
		$parts  = explode( '_', $locale );
		$code   = isset( $parts[1] ) ? self::normalize_country_code( $parts[1] ) : '';
		return '' !== $code && isset( self::$countries[ $code ] ) ? $code : '';
	}

	/** @return array<string, string> */
	private static function get_safe_passthrough_attributes( $tag ): array {
		$atts = array();
		if ( empty( $tag->options ) || ! is_array( $tag->options ) ) {
			return $atts;
		}
		foreach ( $tag->options as $option ) {
			if ( ! is_string( $option ) || false === strpos( $option, ':' ) ) {
				continue;
			}
			list( $key, $value ) = array_pad( explode( ':', $option, 2 ), 2, '' );
			$key = strtolower( trim( $key ) );
			if ( ! preg_match( '/^(aria-[a-z0-9_-]+|data-[a-z0-9_-]+)$/D', $key ) ) {
				continue;
			}
			if ( 0 === strpos( $key, 'data-oliforge-' ) ) {
				continue;
			}

			$protected_aria_attributes = array(
				'aria-controls',
				'aria-describedby',
				'aria-expanded',
				'aria-invalid',
				'aria-required',
			);

			if ( in_array( $key, $protected_aria_attributes, true ) ) {
				continue;
			}

			$atts[ $key ] = sanitize_text_field( wp_unslash( $value ) );
		}
		return $atts;
	}

	private static function get_submitted_value( string $name ): string {
		if ( ! isset( $_POST[ $name ] ) || ! is_string( $_POST[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Contact Form 7 handles the form request.
			return '';
		}
		$value = sanitize_text_field( wp_unslash( $_POST[ $name ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Contact Form 7 handles the form request.
		return self::normalize_country_code( $value );
	}

	private static function normalize_country_code( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = strtoupper( trim( $value ) );
		return preg_match( '/^[A-Z]{2}$/D', $value ) ? $value : '';
	}
}
