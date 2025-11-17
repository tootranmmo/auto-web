<?php
/**
 * AI Content Optimization Engine
 *
 * @package ProgrammaticSEO\AI
 */

namespace ProgrammaticSEO\AI;

/**
 * Class AI_Content_Optimizer
 * AI-powered content optimization using external APIs
 */
class AI_Content_Optimizer {

	/**
	 * OpenAI API key
	 *
	 * @var string
	 */
	private $openai_key;

	/**
	 * OpenAI API endpoint
	 *
	 * @var string
	 */
	private $openai_endpoint = 'https://api.openai.com/v1/';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->openai_key = defined( 'PSEO_OPENAI_KEY' ) ? PSEO_OPENAI_KEY : '';

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'pseo_optimize_content', array( $this, 'optimize_post_content' ) );
	}

	/**
	 * Generate optimized content using AI
	 *
	 * @param string $topic Topic/title.
	 * @param string $keywords Target keywords.
	 * @param int    $word_count Target word count.
	 * @param string $tone Tone (professional, casual, technical).
	 * @return string|false Generated content or false
	 */
	public function generate_content( $topic, $keywords = '', $word_count = 1000, $tone = 'professional' ) {
		if ( ! $this->openai_key ) {
			pseo_log( 'OpenAI API key not configured', 'error' );
			return false;
		}

		$prompt = $this->build_content_prompt( $topic, $keywords, $word_count, $tone );

		return $this->call_openai( 'gpt-3.5-turbo', $prompt, 2000 );
	}

	/**
	 * Build content generation prompt
	 *
	 * @param string $topic Topic.
	 * @param string $keywords Keywords.
	 * @param int    $word_count Word count.
	 * @param string $tone Tone.
	 * @return string Prompt
	 */
	private function build_content_prompt( $topic, $keywords, $word_count, $tone ) {
		$prompt = "Write a comprehensive, SEO-optimized article about '{$topic}' in {$tone} tone.

Requirements:
- Target word count: {$word_count} words
- Primary keywords: {$keywords}
- Include an engaging introduction and conclusion
- Use clear headings and subheadings
- Include relevant examples and data
- Write naturally for humans, not for search engines
- Ensure proper heading hierarchy (H1 > H2 > H3)
- Add internal linking suggestions where relevant

Format the response in HTML with proper semantic tags.";

		return $prompt;
	}

	/**
	 * Optimize existing content
	 *
	 * @param int $post_id Post ID.
	 * @return array Optimization results
	 */
	public function optimize_post_content( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$meta = get_post_meta( $post_id, '_pseo_meta', true );
		$keywords = isset( $meta['keywords'] ) ? $meta['keywords'] : '';

		// Get optimization suggestions
		$suggestions = $this->get_optimization_suggestions(
			$post->post_title,
			$post->post_content,
			$keywords
		);

		// Store suggestions
		update_post_meta( $post_id, '_pseo_optimization_suggestions', $suggestions );

		return $suggestions;
	}

	/**
	 * Get content optimization suggestions
	 *
	 * @param string $title Post title.
	 * @param string $content Post content.
	 * @param string $keywords Target keywords.
	 * @return array Suggestions
	 */
	public function get_optimization_suggestions( $title, $content, $keywords ) {
		if ( ! $this->openai_key ) {
			return $this->get_local_suggestions( $title, $content, $keywords );
		}

		$prompt = "Analyze this content and provide SEO optimization suggestions:

Title: {$title}
Keywords: {$keywords}
Content: " . substr( $content, 0, 2000 ) . "

Provide suggestions in JSON format with the following structure:
{
  'title_suggestions': [...],
  'keyword_placement': '...',
  'readability': '...',
  'content_gaps': [...],
  'internal_links': [...],
  'meta_description': '...'
}";

		$response = $this->call_openai( 'gpt-3.5-turbo', $prompt, 500 );

		if ( ! $response ) {
			return $this->get_local_suggestions( $title, $content, $keywords );
		}

		// Parse JSON response
		try {
			return json_decode( $response, true );
		} catch ( \Exception $e ) {
			pseo_log( 'Failed to parse AI suggestions: ' . $e->getMessage(), 'error' );
			return $this->get_local_suggestions( $title, $content, $keywords );
		}
	}

	/**
	 * Get local optimization suggestions (fallback)
	 *
	 * @param string $title Title.
	 * @param string $content Content.
	 * @param string $keywords Keywords.
	 * @return array Suggestions
	 */
	private function get_local_suggestions( $title, $content, $keywords ) {
		$suggestions = array();

		// Title suggestions
		if ( strlen( $title ) < 30 ) {
			$suggestions['title_suggestions'][] = 'Consider making title longer (30-60 characters)';
		}
		if ( strlen( $title ) > 70 ) {
			$suggestions['title_suggestions'][] = 'Consider shortening title (max 70 characters)';
		}

		// Keyword placement
		$content_lower = strtolower( $content );
		$keyword = reset( explode( ',', strtolower( $keywords ) ) );

		if ( strpos( $content_lower, trim( $keyword ) ) === false ) {
			$suggestions['keyword_placement'][] = "Primary keyword '{$keyword}' not found in content";
		} else {
			$suggestions['keyword_placement'][] = "Primary keyword found in content";
		}

		// Readability
		$word_count = str_word_count( $content );
		if ( $word_count < 300 ) {
			$suggestions['readability'][] = 'Content is too short (minimum 300 words)';
		} elseif ( $word_count > 3000 ) {
			$suggestions['readability'][] = 'Content is very long, consider breaking into sections';
		}

		// Content gaps
		$suggestions['content_gaps'][] = 'Consider adding statistics and data';
		$suggestions['content_gaps'][] = 'Consider adding case studies or examples';

		// Internal links
		if ( substr_count( $content, '<a' ) < 3 ) {
			$suggestions['internal_links'][] = 'Add at least 3-5 relevant internal links';
		}

		// Meta description
		$suggestions['meta_description'] = substr( $content, 0, 160 );

		return $suggestions;
	}

	/**
	 * Generate title variations for A/B testing
	 *
	 * @param string $topic Topic.
	 * @param string $keywords Keywords.
	 * @return array Title variations
	 */
	public function generate_title_variations( $topic, $keywords = '' ) {
		if ( ! $this->openai_key ) {
			return $this->generate_local_titles( $topic, $keywords );
		}

		$prompt = "Generate 5 SEO-optimized title variations for: {$topic}
Keywords: {$keywords}

Each title should be 50-60 characters. Return as a simple list, one per line.";

		$response = $this->call_openai( 'gpt-3.5-turbo', $prompt, 300 );

		if ( ! $response ) {
			return $this->generate_local_titles( $topic, $keywords );
		}

		return array_filter( array_map( 'trim', explode( "\n", $response ) ) );
	}

	/**
	 * Generate local title variations (fallback)
	 *
	 * @param string $topic Topic.
	 * @param string $keywords Keywords.
	 * @return array Titles
	 */
	private function generate_local_titles( $topic, $keywords ) {
		$titles = array(
			ucfirst( $topic ) . ' - Complete Guide',
			'The Ultimate Guide to ' . $topic,
			ucfirst( $topic ) . ' Tips & Tricks',
			'How to Master ' . $topic . ' in 2024',
			'Everything You Need to Know About ' . $topic,
		);

		return array_slice( $titles, 0, 5 );
	}

	/**
	 * Generate meta description variations
	 *
	 * @param string $topic Topic.
	 * @param string $keywords Keywords.
	 * @return array Meta descriptions
	 */
	public function generate_meta_descriptions( $topic, $keywords = '' ) {
		if ( ! $this->openai_key ) {
			return $this->generate_local_meta( $topic, $keywords );
		}

		$prompt = "Generate 3 compelling meta descriptions (150-160 characters) for: {$topic}
Keywords: {$keywords}

Include a call-to-action. Return as a list, one per line.";

		$response = $this->call_openai( 'gpt-3.5-turbo', $prompt, 300 );

		if ( ! $response ) {
			return $this->generate_local_meta( $topic, $keywords );
		}

		return array_filter( array_map( 'trim', explode( "\n", $response ) ) );
	}

	/**
	 * Generate local meta descriptions (fallback)
	 *
	 * @param string $topic Topic.
	 * @param string $keywords Keywords.
	 * @return array Meta descriptions
	 */
	private function generate_local_meta( $topic, $keywords ) {
		return array(
			"Learn everything about {$topic}. Expert tips, strategies, and best practices. Start your journey today.",
			"Discover the secrets to mastering {$topic}. Comprehensive guide with actionable insights.",
			"Complete {$topic} resource. Expert advice, tips, and proven strategies to succeed.",
		);
	}

	/**
	 * Calculate content score
	 *
	 * @param int $post_id Post ID.
	 * @return float Content score (0-100)
	 */
	public function calculate_content_score( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return 0;
		}

		$score = 0;
		$max_score = 0;

		// Word count (25 points)
		$word_count = str_word_count( $post->post_content );
		$max_score += 25;
		if ( $word_count >= 300 && $word_count <= 3000 ) {
			$score += 25;
		} elseif ( $word_count > 300 ) {
			$score += 15;
		}

		// Keyword optimization (25 points)
		$meta = get_post_meta( $post_id, '_pseo_meta', true );
		if ( isset( $meta['keywords'] ) ) {
			$keyword = reset( explode( ',', $meta['keywords'] ) );
			$keyword_count = substr_count( strtolower( $post->post_content ), strtolower( $keyword ) );
			$density = ( $keyword_count / $word_count ) * 100;

			$max_score += 25;
			if ( $density >= 1 && $density <= 2.5 ) {
				$score += 25;
			} elseif ( $density > 0.5 ) {
				$score += 15;
			}
		}

		// Images (15 points)
		$max_score += 15;
		if ( substr_count( $post->post_content, '<img' ) > 0 ) {
			$score += 15;
		}

		// Headings (15 points)
		$max_score += 15;
		if ( preg_match( '/<h[2-6][^>]*>/i', $post->post_content ) ) {
			$score += 15;
		}

		// Internal links (20 points)
		$max_score += 20;
		$internal_link_count = substr_count( $post->post_content, 'href="' . home_url() );
		if ( $internal_link_count >= 3 ) {
			$score += 20;
		} elseif ( $internal_link_count > 0 ) {
			$score += 10;
		}

		return ( $score / $max_score ) * 100;
	}

	/**
	 * Call OpenAI API
	 *
	 * @param string $model Model name.
	 * @param string $prompt Prompt text.
	 * @param int    $max_tokens Maximum tokens.
	 * @return string|false Response or false
	 */
	private function call_openai( $model, $prompt, $max_tokens = 1000 ) {
		if ( ! $this->openai_key ) {
			return false;
		}

		$body = wp_json_encode(
			array(
				'model'       => $model,
				'messages'    => array(
					array(
						'role'    => 'system',
						'content' => 'You are an expert SEO and content optimization specialist.',
					),
					array(
						'role'    => 'user',
						'content' => $prompt,
					),
				),
				'max_tokens'  => $max_tokens,
				'temperature' => 0.7,
			)
		);

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->openai_key,
			),
			'body'    => $body,
			'timeout' => 30,
		);

		$response = wp_remote_post( $this->openai_endpoint . 'chat/completions', $args );

		if ( is_wp_error( $response ) ) {
			pseo_log( 'OpenAI API error: ' . $response->get_error_message(), 'error' );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
			return false;
		}

		return $body['choices'][0]['message']['content'];
	}

	/**
	 * Register AI settings
	 */
	public function register_settings() {
		register_setting( 'programmatic_seo_settings', 'pseo_ai_settings' );

		add_settings_field(
			'pseo_openai_key',
			__( 'OpenAI API Key', 'programmatic-seo' ),
			array( $this, 'openai_key_callback' ),
			'programmatic_seo_settings',
			'pseo_general'
		);
	}

	/**
	 * OpenAI key callback
	 */
	public function openai_key_callback() {
		$options = get_option( 'pseo_ai_settings' );
		$value   = isset( $options['openai_key'] ) ? $options['openai_key'] : '';
		?>
		<input type="password" name="pseo_ai_settings[openai_key]" value="<?php echo esc_attr( $value ); ?>" placeholder="sk-..." />
		<p class="description"><?php esc_html_e( 'Your OpenAI API key for content generation. Get it from https://platform.openai.com', 'programmatic-seo' ); ?></p>
		<?php
	}
}
