<?php
/**
 * Internal Link Manager Class
 *
 * @package ProgrammaticSEO\Linking
 */

namespace ProgrammaticSEO\Linking;

use ProgrammaticSEO\Database\Database_Manager;

/**
 * Class Internal_Link_Manager
 */
class Internal_Link_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'the_content', array( $this, 'add_contextual_links' ), 100 );
	}

	/**
	 * Generate internal links for a post
	 *
	 * @param int $post_id Source post ID.
	 * @param int $max_links Maximum links to add.
	 */
	public function generate_links_for_post( $post_id, $max_links = 5 ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Get related posts based on keywords
		$keywords = $this->extract_keywords( $post->post_content . ' ' . $post->post_title );
		$related_posts = $this->find_related_posts( $post_id, $keywords, $max_links );

		foreach ( $related_posts as $related_post ) {
			Database_Manager::insert_internal_link(
				$post_id,
				$related_post->ID,
				$related_post->post_title,
				'contextual'
			);
		}
	}

	/**
	 * Extract keywords from text
	 *
	 * @param string $text Text to extract keywords from.
	 * @return array Keywords
	 */
	private function extract_keywords( $text ) {
		// Remove HTML tags and special characters
		$text = wp_strip_all_tags( $text );

		// Convert to lowercase and split into words
		$words = preg_split( '/\s+/', strtolower( $text ), -1, PREG_SPLIT_NO_EMPTY );

		// Remove common stop words
		$stop_words = array(
			'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
			'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'been', 'be',
			'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
			'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those',
		);

		$keywords = array();
		foreach ( $words as $word ) {
			if ( ! in_array( $word, $stop_words ) && strlen( $word ) > 3 ) {
				$keywords[] = $word;
			}
		}

		// Return top 10 keywords
		return array_slice( array_unique( $keywords ), 0, 10 );
	}

	/**
	 * Find related posts based on keywords
	 *
	 * @param int   $post_id Current post ID.
	 * @param array $keywords Keywords to search for.
	 * @param int   $limit Maximum number of posts to return.
	 * @return array Related posts
	 */
	private function find_related_posts( $post_id, $keywords, $limit = 5 ) {
		if ( empty( $keywords ) ) {
			return array();
		}

		global $wpdb;

		// Build search query
		$search_terms = implode( ' OR ', array_map( function( $k ) {
			return 'post_title LIKE "%' . esc_sql( $k ) . '%"';
		}, $keywords ) );

		$query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE ID != %d
			AND post_type IN ('post', 'page', 'pseo_generated')
			AND post_status = 'publish'
			AND ($search_terms)
			LIMIT %d",
			$post_id,
			$limit
		);

		$query = str_replace( '%d', '%d', $query ); // Fix query variable binding
		$results = $wpdb->get_results( $query );

		return $results;
	}

	/**
	 * Add contextual internal links to content
	 *
	 * @param string $content Post content.
	 * @return string Modified content
	 */
	public function add_contextual_links( $content ) {
		if ( ! is_singular( 'pseo_generated' ) ) {
			return $content;
		}

		global $post;
		$links = Database_Manager::get_internal_links( $post->ID );

		if ( empty( $links ) ) {
			return $content;
		}

		// Add links to first occurrence of anchor text in content
		foreach ( $links as $link ) {
			if ( empty( $link['anchor_text'] ) ) {
				continue;
			}

			$anchor_text = $link['anchor_text'];
			$target_url  = get_permalink( $link['target_post_id'] );

			// Check if link text appears in content
			if ( stripos( $content, $anchor_text ) !== false ) {
				$replacement = '<a href="' . esc_url( $target_url ) . '" title="' . esc_attr( $anchor_text ) . '">' . esc_html( $anchor_text ) . '</a>';
				$content     = $this->replace_first_occurrence( $content, $anchor_text, $replacement );
			}
		}

		return $content;
	}

	/**
	 * Replace first occurrence of a string
	 *
	 * @param string $haystack Search in this string.
	 * @param string $needle Search for this string.
	 * @param string $replace Replace with this string.
	 * @return string Modified string
	 */
	private function replace_first_occurrence( $haystack, $needle, $replace ) {
		$pos = stripos( $haystack, $needle );
		if ( $pos !== false ) {
			return substr_replace( $haystack, $replace, $pos, strlen( $needle ) );
		}
		return $haystack;
	}

	/**
	 * Get related pages for a post
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit Number of related pages.
	 * @return array Related pages
	 */
	public function get_related_pages( $post_id, $limit = 5 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, p.post_excerpt
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}pseo_internal_links l ON p.ID = l.target_post_id
				WHERE l.source_post_id = %d
				LIMIT %d",
				$post_id,
				$limit
			)
		);
	}

	/**
	 * Auto-link keywords to relevant pages
	 *
	 * @param int   $post_id Post ID.
	 * @param array $keywords Keywords to link.
	 */
	public function auto_link_keywords( $post_id, $keywords = array() ) {
		global $wpdb;

		foreach ( $keywords as $keyword ) {
			// Find posts that match this keyword
			$related = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					WHERE ID != %d
					AND post_title LIKE %s
					AND post_type IN ('post', 'page', 'pseo_generated')
					AND post_status = 'publish'
					LIMIT 1",
					$post_id,
					'%' . $wpdb->esc_like( $keyword ) . '%'
				)
			);

			if ( ! empty( $related ) ) {
				Database_Manager::insert_internal_link(
					$post_id,
					$related[0]->ID,
					$keyword,
					'auto'
				);
			}
		}
	}

	/**
	 * Build internal link network
	 *
	 * @param array $post_ids Post IDs to connect.
	 * @param int   $links_per_post Number of links per post.
	 */
	public function build_link_network( $post_ids = array(), $links_per_post = 3 ) {
		if ( empty( $post_ids ) ) {
			// Get all programmatic SEO posts
			global $wpdb;
			$results = $wpdb->get_col(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'pseo_generated'
				AND post_status = 'publish'"
			);
			$post_ids = $results;
		}

		foreach ( $post_ids as $post_id ) {
			$this->generate_links_for_post( $post_id, $links_per_post );
		}
	}
}
