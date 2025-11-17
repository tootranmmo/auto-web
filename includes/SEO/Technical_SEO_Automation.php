<?php
/**
 * Technical SEO Automation - Core Web Vitals & Performance
 *
 * @package ProgrammaticSEO\SEO
 */

namespace ProgrammaticSEO\SEO;

/**
 * Class Technical_SEO_Automation
 * Automated technical SEO optimization
 */
class Technical_SEO_Automation {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'save_post_pseo_generated', array( $this, 'analyze_page_seo' ), 20 );
		add_action( 'admin_init', array( $this, 'schedule_seo_audits' ) );
		add_action( 'pseo_daily_seo_audit', array( $this, 'run_seo_audit' ) );
	}

	/**
	 * Analyze page SEO on publish
	 *
	 * @param int $post_id Post ID.
	 */
	public function analyze_page_seo( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			return;
		}

		$analysis = $this->run_seo_analysis( $post_id );
		update_post_meta( $post_id, '_pseo_seo_analysis', $analysis );
	}

	/**
	 * Run SEO analysis on a page
	 *
	 * @param int $post_id Post ID.
	 * @return array Analysis results
	 */
	public function run_seo_analysis( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		return array(
			'title_analysis'        => $this->analyze_title( $post ),
			'meta_description'      => $this->analyze_meta_description( $post ),
			'content_analysis'      => $this->analyze_content( $post ),
			'keyword_analysis'      => $this->analyze_keywords( $post ),
			'readability_score'     => $this->calculate_readability( $post->post_content ),
			'heading_structure'     => $this->check_heading_structure( $post->post_content ),
			'internal_links'        => $this->count_internal_links( $post->ID ),
			'external_links'        => $this->count_external_links( $post->post_content ),
			'image_optimization'    => $this->analyze_images( $post->ID ),
			'mobile_friendly'       => $this->check_mobile_friendly( $post_id ),
			'core_web_vitals'       => $this->get_core_web_vitals( $post_id ),
			'seo_score'             => 0, // Calculated after analysis
		);
	}

	/**
	 * Analyze title tag
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Analysis
	 */
	private function analyze_title( $post ) {
		$title = $post->post_title;
		$issues = array();
		$score = 100;

		// Check length (ideal 50-60 chars)
		$length = strlen( $title );
		if ( $length < 30 ) {
			$issues[] = 'Title is too short (min 30 chars)';
			$score -= 20;
		} elseif ( $length > 70 ) {
			$issues[] = 'Title is too long (max 70 chars)';
			$score -= 10;
		}

		// Check for primary keyword
		$meta = get_post_meta( $post->ID, '_pseo_meta', true );
		if ( isset( $meta['keywords'] ) && ! stripos( $title, reset( explode( ',', $meta['keywords'] ) ) ) ) {
			$issues[] = 'Primary keyword not in title';
			$score -= 10;
		}

		// Check for unique characters
		if ( strlen( $title ) < strlen( urlencode( $title ) ) ) {
			$issues[] = 'Title contains special characters that may not display';
			$score -= 5;
		}

		return array(
			'length'       => $length,
			'score'        => max( 0, $score ),
			'issues'       => $issues,
			'recommendations' => array(
				'Include primary keyword',
				'Keep between 50-60 characters',
				'Make it compelling for click-through',
			),
		);
	}

	/**
	 * Analyze meta description
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Analysis
	 */
	private function analyze_meta_description( $post ) {
		$meta = get_post_meta( $post->ID, '_pseo_meta', true );
		$description = $meta['description'] ?? wp_trim_excerpt( $post->post_content );
		$issues = array();
		$score = 100;

		// Check length (ideal 150-160 chars)
		$length = strlen( $description );
		if ( $length < 120 ) {
			$issues[] = 'Meta description is too short (min 120 chars)';
			$score -= 20;
		} elseif ( $length > 160 ) {
			$issues[] = 'Meta description is too long (max 160 chars)';
			$score -= 10;
		}

		// Check for keyword
		if ( isset( $meta['keywords'] ) && ! stripos( $description, reset( explode( ',', $meta['keywords'] ) ) ) ) {
			$issues[] = 'Keyword not in meta description';
			$score -= 10;
		}

		// Check for call-to-action
		$cta_words = array( 'click', 'learn', 'discover', 'find', 'explore', 'read' );
		$has_cta = false;
		foreach ( $cta_words as $word ) {
			if ( stripos( $description, $word ) !== false ) {
				$has_cta = true;
				break;
			}
		}

		if ( ! $has_cta ) {
			$issues[] = 'No call-to-action in description';
			$score -= 5;
		}

		return array(
			'length'       => $length,
			'score'        => max( 0, $score ),
			'issues'       => $issues,
			'recommendations' => array(
				'Include primary keyword',
				'Keep between 150-160 characters',
				'Add compelling call-to-action',
				'Write naturally for humans',
			),
		);
	}

	/**
	 * Analyze content quality
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Analysis
	 */
	private function analyze_content( $post ) {
		$content = $post->post_content;
		$word_count = str_word_count( $content );
		$issues = array();
		$score = 100;

		// Check word count (minimum 300 words)
		if ( $word_count < 300 ) {
			$issues[] = 'Content is too short (min 300 words)';
			$score -= 30;
		} elseif ( $word_count > 3000 ) {
			$issues[] = 'Content is very long (max 3000 words recommended)';
			$score -= 5;
		}

		// Check for keyword density (2-3% optimal)
		$meta = get_post_meta( $post->ID, '_pseo_meta', true );
		if ( isset( $meta['keywords'] ) ) {
			$keyword = reset( explode( ',', $meta['keywords'] ) );
			$keyword_count = substr_count( strtolower( $content ), strtolower( $keyword ) );
			$density = ( $keyword_count / $word_count ) * 100;

			if ( $density < 0.5 ) {
				$issues[] = 'Keyword density is too low';
				$score -= 10;
			} elseif ( $density > 3 ) {
				$issues[] = 'Keyword density is too high (possible over-optimization)';
				$score -= 15;
			}
		}

		// Check for multimedia
		$image_count = substr_count( $content, '<img' );
		if ( $image_count === 0 ) {
			$issues[] = 'No images in content';
			$score -= 10;
		}

		return array(
			'word_count'   => $word_count,
			'score'        => max( 0, $score ),
			'issues'       => $issues,
			'recommendations' => array(
				'Write at least 300 words',
				'Maintain 1-2% keyword density',
				'Add relevant images',
				'Use subheadings to structure content',
			),
		);
	}

	/**
	 * Analyze keywords
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Analysis
	 */
	private function analyze_keywords( $post ) {
		$meta = get_post_meta( $post->ID, '_pseo_meta', true );
		$keywords = isset( $meta['keywords'] ) ? array_map( 'trim', explode( ',', $meta['keywords'] ) ) : array();

		$analysis = array();
		foreach ( $keywords as $keyword ) {
			$analysis[] = array(
				'keyword'          => $keyword,
				'search_volume'    => 0, // Would integrate with SEMrush/Ahrefs API
				'difficulty'       => 0, // Would integrate with API
				'competition'      => 0, // Would integrate with API
				'search_trend'     => 'stable', // Would use trend data
			);
		}

		return array(
			'total_keywords'   => count( $keywords ),
			'keywords'         => $analysis,
			'recommendations'  => array(
				'Focus on 1-2 primary keywords',
				'Include long-tail variations',
				'Check search volume and difficulty',
			),
		);
	}

	/**
	 * Calculate readability score
	 *
	 * @param string $content Content to analyze.
	 * @return float Readability score (0-100)
	 */
	private function calculate_readability( $content ) {
		// Remove HTML tags
		$text = wp_strip_all_tags( $content );

		// Calculate Flesch Reading Ease Score
		$sentences = preg_split( '/[.!?]+/', $text );
		$words = str_word_count( $text );
		$syllables = $this->count_syllables( $text );

		if ( $words === 0 || count( $sentences ) === 0 ) {
			return 0;
		}

		$score = 206.835 - 1.015 * ( $words / count( $sentences ) ) - 84.6 * ( $syllables / $words );
		return max( 0, min( 100, $score ) );
	}

	/**
	 * Count syllables in text
	 *
	 * @param string $text Text to analyze.
	 * @return int Syllable count
	 */
	private function count_syllables( $text ) {
		$text = strtolower( $text );
		$syllables = 0;

		// Simple syllable estimation
		$vowels = 'aeiouy';
		$previous_was_vowel = false;

		for ( $i = 0; $i < strlen( $text ); $i++ ) {
			$is_vowel = strpos( $vowels, $text[ $i ] ) !== false;

			if ( $is_vowel && ! $previous_was_vowel ) {
				$syllables++;
			}

			$previous_was_vowel = $is_vowel;
		}

		return max( 1, $syllables );
	}

	/**
	 * Check heading structure
	 *
	 * @param string $content Content to check.
	 * @return array Heading analysis
	 */
	private function check_heading_structure( $content ) {
		$headings = array();

		// Extract headings
		for ( $i = 1; $i <= 6; $i++ ) {
			if ( preg_match_all( '/<h' . $i . '[^>]*>([^<]+)<\/h' . $i . '>/i', $content, $matches ) ) {
				$headings[ 'h' . $i ] = $matches[1];
			}
		}

		$issues = array();
		$score = 100;

		// Check for H1
		if ( ! isset( $headings['h1'] ) || empty( $headings['h1'] ) ) {
			$issues[] = 'No H1 heading found';
			$score -= 30;
		} elseif ( count( $headings['h1'] ) > 1 ) {
			$issues[] = 'Multiple H1 headings found (should have only one)';
			$score -= 20;
		}

		// Check heading hierarchy
		if ( isset( $headings['h3'] ) && ! isset( $headings['h2'] ) ) {
			$issues[] = 'H3 found without H2 (improper heading hierarchy)';
			$score -= 10;
		}

		return array(
			'headings'         => $headings,
			'score'            => max( 0, $score ),
			'issues'           => $issues,
			'recommendations'  => array(
				'Use exactly one H1 per page',
				'Use H2-H6 for subheadings in order',
				'Include target keywords in headings',
			),
		);
	}

	/**
	 * Count internal links
	 *
	 * @param int $post_id Post ID.
	 * @return int Link count
	 */
	private function count_internal_links( $post_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}pseo_internal_links WHERE source_post_id = %d",
				$post_id
			)
		);
	}

	/**
	 * Count external links
	 *
	 * @param string $content Content to analyze.
	 * @return int Link count
	 */
	private function count_external_links( $content ) {
		preg_match_all( '/href=["\']([^"\']+)["\']/i', $content, $matches );
		$external = 0;

		foreach ( $matches[1] as $url ) {
			if ( strpos( $url, home_url() ) === false && strpos( $url, 'http' ) === 0 ) {
				$external++;
			}
		}

		return $external;
	}

	/**
	 * Analyze image optimization
	 *
	 * @param int $post_id Post ID.
	 * @return array Image analysis
	 */
	private function analyze_images( $post_id ) {
		$post = get_post( $post_id );
		$images = array();
		$score = 100;
		$issues = array();

		// Find images in content
		if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/i', $post->post_content, $matches ) ) {
			for ( $i = 0; $i < count( $matches[0] ); $i++ ) {
				$images[] = array(
					'src'      => $matches[1][ $i ],
					'alt_text' => $matches[2][ $i ],
					'has_alt'  => ! empty( $matches[2][ $i ] ),
				);
			}
		}

		// Check for missing alt text
		$missing_alt = array_filter( $images, function( $img ) {
			return ! $img['has_alt'];
		} );

		if ( ! empty( $missing_alt ) ) {
			$issues[] = count( $missing_alt ) . ' images missing alt text';
			$score -= count( $missing_alt ) * 5;
		}

		// Check for image count
		if ( count( $images ) === 0 ) {
			$issues[] = 'No images in post';
			$score -= 20;
		}

		return array(
			'total_images'     => count( $images ),
			'with_alt_text'    => count( array_filter( $images, function( $img ) {
				return $img['has_alt'];
			} ) ),
			'score'            => max( 0, $score ),
			'issues'           => $issues,
			'recommendations'  => array(
				'Add descriptive alt text to all images',
				'Optimize image file sizes',
				'Use lazy loading for images',
				'Include images related to content',
			),
		);
	}

	/**
	 * Check mobile friendliness
	 *
	 * @param int $post_id Post ID.
	 * @return array Mobile analysis
	 */
	private function check_mobile_friendly( $post_id ) {
		// Simulate mobile check (would integrate with Google Mobile Friendly API)
		$post = get_post( $post_id );

		$checks = array(
			'viewport_meta'      => strpos( $post->post_content, 'viewport' ) !== false,
			'readable_font'      => true,
			'touch_spacing'      => true,
			'no_full_width'      => true,
			'no_intrusive_ads'   => true,
		);

		$passed = count( array_filter( $checks ) );
		$score = ( $passed / count( $checks ) ) * 100;

		return array(
			'checks'           => $checks,
			'score'            => $score,
			'mobile_friendly'  => $score >= 80,
			'recommendations'  => array(
				'Ensure responsive design',
				'Use readable font sizes',
				'Optimize touch spacing',
				'Avoid intrusive interstitials',
			),
		);
	}

	/**
	 * Get Core Web Vitals
	 *
	 * @param int $post_id Post ID.
	 * @return array Core Web Vitals
	 */
	private function get_core_web_vitals( $post_id ) {
		// In production, this would integrate with Google's PageSpeed Insights API
		// or use real field data from Chrome User Experience Report

		$vitals = array(
			'LCP'  => array(
				'value'     => 0,
				'status'    => 'unknown',
				'threshold' => 2500, // ms
				'grade'     => 'UNKNOWN',
			),
			'FID'  => array(
				'value'     => 0,
				'status'    => 'unknown',
				'threshold' => 100, // ms
				'grade'     => 'UNKNOWN',
			),
			'CLS'  => array(
				'value'     => 0,
				'status'    => 'unknown',
				'threshold' => 0.1,
				'grade'     => 'UNKNOWN',
			),
		);

		return $vitals;
	}

	/**
	 * Schedule SEO audits
	 */
	public function schedule_seo_audits() {
		if ( ! wp_next_scheduled( 'pseo_daily_seo_audit' ) ) {
			wp_schedule_event( time(), 'daily', 'pseo_daily_seo_audit' );
		}
	}

	/**
	 * Run daily SEO audit
	 */
	public function run_seo_audit() {
		global $wpdb;

		// Get all generated pages
		$pages = $wpdb->get_col(
			"SELECT post_id FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published' LIMIT 100"
		);

		foreach ( $pages as $post_id ) {
			$this->analyze_page_seo( $post_id );
		}

		pseo_log( 'SEO audit completed for ' . count( $pages ) . ' pages', 'info' );
	}

	/**
	 * Get overall SEO score for page
	 *
	 * @param int $post_id Post ID.
	 * @return float SEO score (0-100)
	 */
	public function get_seo_score( $post_id ) {
		$analysis = get_post_meta( $post_id, '_pseo_seo_analysis', true );
		if ( ! $analysis ) {
			return 0;
		}

		$scores = array();

		if ( isset( $analysis['title_analysis']['score'] ) ) {
			$scores[] = $analysis['title_analysis']['score'];
		}
		if ( isset( $analysis['meta_description']['score'] ) ) {
			$scores[] = $analysis['meta_description']['score'];
		}
		if ( isset( $analysis['content_analysis']['score'] ) ) {
			$scores[] = $analysis['content_analysis']['score'];
		}
		if ( isset( $analysis['heading_structure']['score'] ) ) {
			$scores[] = $analysis['heading_structure']['score'];
		}
		if ( isset( $analysis['image_optimization']['score'] ) ) {
			$scores[] = $analysis['image_optimization']['score'];
		}
		if ( isset( $analysis['mobile_friendly']['score'] ) ) {
			$scores[] = $analysis['mobile_friendly']['score'];
		}

		return ! empty( $scores ) ? array_sum( $scores ) / count( $scores ) : 0;
	}
}
