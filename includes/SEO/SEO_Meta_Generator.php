<?php
/**
 * SEO Meta Generator Class
 *
 * @package ProgrammaticSEO\SEO
 */

namespace ProgrammaticSEO\SEO;

/**
 * Class SEO_Meta_Generator
 */
class SEO_Meta_Generator {

	/**
	 * Generate SEO meta tags
	 *
	 * @param array $data Row data.
	 * @param array $template Template data.
	 * @return array SEO meta
	 */
	public function generate_meta( $data, $template ) {
		$mapping = $template['data_mapping'] ?? array();

		return array(
			'title'             => $this->generate_title( $data, $mapping ),
			'description'       => $this->generate_description( $data, $mapping ),
			'keywords'          => $this->generate_keywords( $data, $mapping ),
			'og_title'          => $this->generate_og_title( $data, $mapping ),
			'og_description'    => $this->generate_og_description( $data, $mapping ),
			'og_image'          => $this->generate_og_image( $data, $mapping ),
			'twitter_card'      => 'summary_large_image',
			'twitter_title'     => $this->generate_title( $data, $mapping ),
			'twitter_description' => $this->generate_description( $data, $mapping ),
		);
	}

	/**
	 * Generate title tag
	 *
	 * @param array $data Row data.
	 * @param array $mapping Field mapping.
	 * @return string Title
	 */
	private function generate_title( $data, $mapping ) {
		$title = isset( $data[ $mapping['title'] ?? 'title' ] ) ? $data[ $mapping['title'] ?? 'title' ] : '';

		if ( empty( $title ) && isset( $data['title'] ) ) {
			$title = $data['title'];
		}

		// Ensure title is under 60 characters for SEO
		if ( strlen( $title ) > 60 ) {
			$title = substr( $title, 0, 57 ) . '...';
		}

		return sanitize_text_field( $title );
	}

	/**
	 * Generate meta description
	 *
	 * @param array $data Row data.
	 * @param array $mapping Field mapping.
	 * @return string Description
	 */
	private function generate_description( $data, $mapping ) {
		$description = isset( $data[ $mapping['description'] ?? 'description' ] ) ? $data[ $mapping['description'] ?? 'description' ] : '';

		if ( empty( $description ) && isset( $data['excerpt'] ) ) {
			$description = $data['excerpt'];
		}

		// Ensure description is under 160 characters for SEO
		if ( strlen( $description ) > 160 ) {
			$description = substr( $description, 0, 157 ) . '...';
		}

		return sanitize_text_field( $description );
	}

	/**
	 * Generate keywords
	 *
	 * @param array $data Row data.
	 * @param array $mapping Field mapping.
	 * @return string Keywords
	 */
	private function generate_keywords( $data, $mapping ) {
		$keywords = isset( $data[ $mapping['keywords'] ?? 'keywords' ] ) ? $data[ $mapping['keywords'] ?? 'keywords' ] : '';

		if ( empty( $keywords ) ) {
			// Auto-generate from title
			$keywords = $this->generate_keywords_from_text( $data[ $mapping['title'] ?? 'title' ] ?? '' );
		}

		return sanitize_text_field( $keywords );
	}

	/**
	 * Generate Open Graph title
	 *
	 * @param array $data Row data.
	 * @param array $mapping Field mapping.
	 * @return string OG Title
	 */
	private function generate_og_title( $data, $mapping ) {
		return $this->generate_title( $data, $mapping );
	}

	/**
	 * Generate Open Graph description
	 *
	 * @param array $data Row data.
	 * @param array $mapping Field mapping.
	 * @return string OG Description
	 */
	private function generate_og_description( $data, $mapping ) {
		return $this->generate_description( $data, $mapping );
	}

	/**
	 * Generate Open Graph image
	 *
	 * @param array $data Row data.
	 * @param array $mapping Field mapping.
	 * @return string Image URL
	 */
	private function generate_og_image( $data, $mapping ) {
		$image = isset( $data[ $mapping['image'] ?? 'image' ] ) ? $data[ $mapping['image'] ?? 'image' ] : '';

		if ( empty( $image ) ) {
			// Use site logo or default image
			$image = wp_get_attachment_url( get_theme_mod( 'custom_logo' ) );
		}

		return esc_url( $image );
	}

	/**
	 * Auto-generate keywords from text
	 *
	 * @param string $text Text to extract keywords from.
	 * @return string Keywords
	 */
	private function generate_keywords_from_text( $text ) {
		// Simple keyword extraction - can be enhanced
		$words = str_word_count( $text, 1 );
		$words = array_slice( $words, 0, 5 );
		return implode( ', ', $words );
	}

	/**
	 * Render meta tags
	 *
	 * @param array $meta Meta data.
	 * @return string HTML meta tags
	 */
	public function render_meta_tags( $meta ) {
		$output = '';

		// Title tag
		if ( ! empty( $meta['title'] ) ) {
			$output .= '<meta property="og:title" content="' . esc_attr( $meta['title'] ) . '" />' . PHP_EOL;
		}

		// Meta description
		if ( ! empty( $meta['description'] ) ) {
			$output .= '<meta name="description" content="' . esc_attr( $meta['description'] ) . '" />' . PHP_EOL;
		}

		// Keywords
		if ( ! empty( $meta['keywords'] ) ) {
			$output .= '<meta name="keywords" content="' . esc_attr( $meta['keywords'] ) . '" />' . PHP_EOL;
		}

		// OpenGraph tags
		if ( ! empty( $meta['og_title'] ) ) {
			$output .= '<meta property="og:title" content="' . esc_attr( $meta['og_title'] ) . '" />' . PHP_EOL;
		}

		if ( ! empty( $meta['og_description'] ) ) {
			$output .= '<meta property="og:description" content="' . esc_attr( $meta['og_description'] ) . '" />' . PHP_EOL;
		}

		if ( ! empty( $meta['og_image'] ) ) {
			$output .= '<meta property="og:image" content="' . esc_attr( $meta['og_image'] ) . '" />' . PHP_EOL;
		}

		// Twitter Card tags
		if ( ! empty( $meta['twitter_card'] ) ) {
			$output .= '<meta name="twitter:card" content="' . esc_attr( $meta['twitter_card'] ) . '" />' . PHP_EOL;
		}

		if ( ! empty( $meta['twitter_title'] ) ) {
			$output .= '<meta name="twitter:title" content="' . esc_attr( $meta['twitter_title'] ) . '" />' . PHP_EOL;
		}

		if ( ! empty( $meta['twitter_description'] ) ) {
			$output .= '<meta name="twitter:description" content="' . esc_attr( $meta['twitter_description'] ) . '" />' . PHP_EOL;
		}

		return $output;
	}
}
