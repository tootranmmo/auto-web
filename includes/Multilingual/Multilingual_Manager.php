<?php
/**
 * Multi-language Content Generation & SEO
 *
 * @package ProgrammaticSEO\Multilingual
 */

namespace ProgrammaticSEO\Multilingual;

/**
 * Class Multilingual_Manager
 * Auto-translate content and optimize for multiple languages
 */
class Multilingual_Manager {

	/**
	 * Supported languages
	 *
	 * @var array
	 */
	private $languages = array(
		'en' => 'English',
		'es' => 'Spanish',
		'fr' => 'French',
		'de' => 'German',
		'it' => 'Italian',
		'pt' => 'Portuguese',
		'ru' => 'Russian',
		'ja' => 'Japanese',
		'zh' => 'Chinese',
		'ko' => 'Korean',
		'vi' => 'Vietnamese',
		'ar' => 'Arabic',
	);

	/**
	 * Translation API key
	 *
	 * @var string
	 */
	private $translation_api_key;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->translation_api_key = defined( 'PSEO_TRANSLATION_API_KEY' ) ? PSEO_TRANSLATION_API_KEY : '';
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Generate multilingual content
	 *
	 * @param int    $post_id Post ID.
	 * @param string $target_language Target language code.
	 * @return array|false Translated content or false
	 */
	public function generate_translation( $post_id, $target_language ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		if ( ! in_array( $target_language, array_keys( $this->languages ) ) ) {
			return false;
		}

		// Translate content
		$translated_content = $this->translate_text( $post->post_content, $target_language );
		$translated_title = $this->translate_text( $post->post_title, $target_language );
		$translated_excerpt = $this->translate_text( $post->post_excerpt, $target_language );

		if ( ! $translated_content ) {
			return false;
		}

		// Create translated post
		$translated_post_id = $this->create_translated_post(
			$post_id,
			$translated_title,
			$translated_content,
			$translated_excerpt,
			$target_language
		);

		if ( ! $translated_post_id ) {
			return false;
		}

		// Optimize SEO for target language
		$this->optimize_seo_for_language( $translated_post_id, $target_language );

		// Generate hreflang tags
		$this->update_hreflang_tags( $post_id, $translated_post_id, $target_language );

		return array(
			'original_post_id' => $post_id,
			'translated_post_id' => $translated_post_id,
			'language' => $target_language,
			'language_name' => $this->languages[ $target_language ],
		);
	}

	/**
	 * Translate text
	 *
	 * @param string $text Text to translate.
	 * @param string $target_language Target language code.
	 * @return string|false Translated text or false
	 */
	private function translate_text( $text, $target_language ) {
		if ( empty( $text ) ) {
			return '';
		}

		// Use Google Translate API if available
		if ( ! empty( $this->translation_api_key ) ) {
			return $this->translate_via_api( $text, $target_language );
		}

		// Fallback: Return original text (would need manual translation)
		return $text;
	}

	/**
	 * Translate via API
	 *
	 * @param string $text Text to translate.
	 * @param string $target_language Target language.
	 * @return string|false Translated text or false
	 */
	private function translate_via_api( $text, $target_language ) {
		$api_endpoint = 'https://translation.googleapis.com/language/translate/v2';

		$response = wp_remote_post(
			$api_endpoint,
			array(
				'body' => array(
					'key'        => $this->translation_api_key,
					'q'          => $text,
					'target'     => $target_language,
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			pseo_log( 'Translation API error: ' . $response->get_error_message(), 'error' );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['data']['translations'][0]['translatedText'] ) ) {
			return false;
		}

		return $body['data']['translations'][0]['translatedText'];
	}

	/**
	 * Create translated post
	 *
	 * @param int    $original_post_id Original post ID.
	 * @param string $title Translated title.
	 * @param string $content Translated content.
	 * @param string $excerpt Translated excerpt.
	 * @param string $language Language code.
	 * @return int|false New post ID or false
	 */
	private function create_translated_post( $original_post_id, $title, $content, $excerpt, $language ) {
		$original_post = get_post( $original_post_id );

		$post_data = array(
			'post_title'     => $title,
			'post_content'   => $content,
			'post_excerpt'   => $excerpt,
			'post_status'    => 'publish',
			'post_type'      => $original_post->post_type,
			'post_parent'    => $original_post_id, // Link to original
		);

		$new_post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $new_post_id ) ) {
			return false;
		}

		// Store language information
		update_post_meta( $new_post_id, '_pseo_language', $language );
		update_post_meta( $new_post_id, '_pseo_original_post_id', $original_post_id );

		// Copy featured image
		if ( has_post_thumbnail( $original_post_id ) ) {
			set_post_thumbnail( $new_post_id, get_post_thumbnail_id( $original_post_id ) );
		}

		return $new_post_id;
	}

	/**
	 * Optimize SEO for language
	 *
	 * @param int    $post_id Post ID.
	 * @param string $language Language code.
	 */
	private function optimize_seo_for_language( $post_id, $language ) {
		$post = get_post( $post_id );

		// Get original meta
		$original_meta = get_post_meta( get_post_field( 'post_parent', $post_id ), '_pseo_meta', true );

		if ( ! $original_meta ) {
			return;
		}

		// Translate meta
		$translated_meta = array(
			'title'       => $this->translate_text( $original_meta['title'] ?? '', $language ),
			'description' => $this->translate_text( $original_meta['description'] ?? '', $language ),
			'keywords'    => $this->translate_text( $original_meta['keywords'] ?? '', $language ),
			'og_title'    => $this->translate_text( $original_meta['og_title'] ?? '', $language ),
			'og_description' => $this->translate_text( $original_meta['og_description'] ?? '', $language ),
		);

		// Optimize for language length
		if ( strlen( $translated_meta['title'] ) > 60 ) {
			$translated_meta['title'] = substr( $translated_meta['title'], 0, 57 ) . '...';
		}

		if ( strlen( $translated_meta['description'] ) > 160 ) {
			$translated_meta['description'] = substr( $translated_meta['description'], 0, 157 ) . '...';
		}

		update_post_meta( $post_id, '_pseo_meta', $translated_meta );
	}

	/**
	 * Update hreflang tags
	 *
	 * @param int    $original_post_id Original post ID.
	 * @param int    $translated_post_id Translated post ID.
	 * @param string $language Language code.
	 */
	private function update_hreflang_tags( $original_post_id, $translated_post_id, $language ) {
		$hreflang = get_post_meta( $original_post_id, '_pseo_hreflang', true );

		if ( ! is_array( $hreflang ) ) {
			$hreflang = array();
		}

		$hreflang[ $language ] = array(
			'post_id' => $translated_post_id,
			'url'     => get_permalink( $translated_post_id ),
		);

		update_post_meta( $original_post_id, '_pseo_hreflang', $hreflang );
		update_post_meta( $translated_post_id, '_pseo_hreflang', $hreflang );
	}

	/**
	 * Generate hreflang HTML tags
	 *
	 * @param int $post_id Post ID.
	 * @return string HTML hreflang tags
	 */
	public function generate_hreflang_tags( $post_id ) {
		$hreflang = get_post_meta( $post_id, '_pseo_hreflang', true );

		if ( ! is_array( $hreflang ) || empty( $hreflang ) ) {
			return '';
		}

		$html = '';

		foreach ( $hreflang as $lang_code => $data ) {
			$html .= '<link rel="alternate" hreflang="' . esc_attr( $lang_code ) . '" href="' . esc_url( $data['url'] ) . '" />' . PHP_EOL;
		}

		// Add x-default
		$html .= '<link rel="alternate" hreflang="x-default" href="' . esc_url( get_permalink( $post_id ) ) . '" />' . PHP_EOL;

		return $html;
	}

	/**
	 * Get available translations for post
	 *
	 * @param int $post_id Post ID.
	 * @return array Available translations
	 */
	public function get_available_translations( $post_id ) {
		global $wpdb;

		$translations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, pm.meta_value as language
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE pm.meta_key = '_pseo_original_post_id'
				AND pm.meta_value = %d
				OR p.post_parent = %d",
				$post_id,
				$post_id
			),
			ARRAY_A
		);

		$available = array();

		foreach ( $translations as $translation ) {
			$language = get_post_meta( $translation['ID'], '_pseo_language', true );

			if ( $language && isset( $this->languages[ $language ] ) ) {
				$available[ $language ] = array(
					'post_id'        => $translation['ID'],
					'language'       => $language,
					'language_name'  => $this->languages[ $language ],
					'url'            => get_permalink( $translation['ID'] ),
				);
			}
		}

		return $available;
	}

	/**
	 * Generate locale-specific content variant
	 *
	 * @param int    $post_id Post ID.
	 * @param string $locale Locale code (e.g., en_US, es_ES).
	 * @return bool Success
	 */
	public function create_locale_variant( $post_id, $locale ) {
		// Extract language from locale
		$language = substr( $locale, 0, 2 );

		// Generate translation
		$result = $this->generate_translation( $post_id, $language );

		if ( ! $result ) {
			return false;
		}

		// Store locale information
		update_post_meta( $result['translated_post_id'], '_pseo_locale', $locale );

		// Customize for specific locale (currency, date format, etc.)
		$this->customize_for_locale( $result['translated_post_id'], $locale );

		return true;
	}

	/**
	 * Customize content for locale
	 *
	 * @param int    $post_id Post ID.
	 * @param string $locale Locale code.
	 */
	private function customize_for_locale( $post_id, $locale ) {
		// Customize based on locale
		// This could include currency symbols, date formats, etc.

		$customizations = array(
			'locale'     => $locale,
			'currency'   => $this->get_currency_for_locale( $locale ),
			'date_format' => $this->get_date_format_for_locale( $locale ),
		);

		update_post_meta( $post_id, '_pseo_locale_customizations', $customizations );
	}

	/**
	 * Get currency for locale
	 *
	 * @param string $locale Locale code.
	 * @return string Currency code
	 */
	private function get_currency_for_locale( $locale ) {
		$currencies = array(
			'en_US' => 'USD',
			'en_GB' => 'GBP',
			'es_ES' => 'EUR',
			'fr_FR' => 'EUR',
			'de_DE' => 'EUR',
			'ja_JP' => 'JPY',
			'zh_CN' => 'CNY',
		);

		return $currencies[ $locale ] ?? 'USD';
	}

	/**
	 * Get date format for locale
	 *
	 * @param string $locale Locale code.
	 * @return string Date format
	 */
	private function get_date_format_for_locale( $locale ) {
		$formats = array(
			'en_US' => 'm/d/Y',
			'en_GB' => 'd/m/Y',
			'de_DE' => 'd.m.Y',
			'fr_FR' => 'd/m/Y',
			'ja_JP' => 'Y年m月d日',
		);

		return $formats[ $locale ] ?? 'Y-m-d';
	}

	/**
	 * Get all supported languages
	 *
	 * @return array Languages
	 */
	public function get_supported_languages() {
		return $this->languages;
	}

	/**
	 * Bulk translate pages
	 *
	 * @param array  $post_ids Post IDs to translate.
	 * @param string $target_language Target language.
	 * @return array Results
	 */
	public function bulk_translate_pages( $post_ids, $target_language ) {
		$results = array(
			'success'  => 0,
			'failed'   => 0,
			'posts'    => array(),
		);

		foreach ( $post_ids as $post_id ) {
			$result = $this->generate_translation( $post_id, $target_language );

			if ( $result ) {
				$results['success']++;
				$results['posts'][] = $result;
			} else {
				$results['failed']++;
			}
		}

		return $results;
	}

	/**
	 * Register multilingual settings
	 */
	public function register_settings() {
		register_setting( 'programmatic_seo_settings', 'pseo_multilingual_settings' );

		add_settings_field(
			'pseo_translation_api_key',
			__( 'Translation API Key', 'programmatic-seo' ),
			array( $this, 'translation_api_key_callback' ),
			'programmatic_seo_settings',
			'pseo_general'
		);

		add_settings_field(
			'pseo_enabled_languages',
			__( 'Enable Languages', 'programmatic-seo' ),
			array( $this, 'enabled_languages_callback' ),
			'programmatic_seo_settings',
			'pseo_general'
		);
	}

	/**
	 * Translation API key callback
	 */
	public function translation_api_key_callback() {
		$options = get_option( 'pseo_multilingual_settings' );
		$value   = isset( $options['translation_api_key'] ) ? $options['translation_api_key'] : '';
		?>
		<input type="password" name="pseo_multilingual_settings[translation_api_key]" value="<?php echo esc_attr( $value ); ?>" placeholder="API Key..." />
		<p class="description"><?php esc_html_e( 'Google Translate API key for auto-translation', 'programmatic-seo' ); ?></p>
		<?php
	}

	/**
	 * Enabled languages callback
	 */
	public function enabled_languages_callback() {
		$options = get_option( 'pseo_multilingual_settings' );
		$enabled = isset( $options['enabled_languages'] ) ? $options['enabled_languages'] : array();

		foreach ( $this->languages as $code => $name ) {
			$checked = in_array( $code, (array) $enabled );
			?>
			<label>
				<input type="checkbox" name="pseo_multilingual_settings[enabled_languages][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( $checked ); ?> />
				<?php echo esc_html( $name ); ?>
			</label><br>
			<?php
		}
	}
}
