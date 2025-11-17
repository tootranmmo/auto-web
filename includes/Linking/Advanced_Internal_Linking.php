<?php
/**
 * Advanced Internal Linking Strategy
 *
 * @package ProgrammaticSEO\Linking
 */

namespace ProgrammaticSEO\Linking;

/**
 * Class Advanced_Internal_Linking
 * Semantic linking, anchor diversity, and link velocity management
 */
class Advanced_Internal_Linking {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'schedule_link_optimization' ) );
		add_action( 'pseo_optimize_links', array( $this, 'run_link_optimization' ) );
	}

	/**
	 * Build semantic link clusters
	 *
	 * @return array Link clusters
	 */
	public function build_semantic_clusters() {
		global $wpdb;

		// Get all published pages
		$pages = $wpdb->get_results(
			"SELECT post_id, title FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published'",
			ARRAY_A
		);

		$clusters = array();

		foreach ( $pages as $page ) {
			$keywords = get_post_meta( $page['post_id'], '_pseo_meta', true );
			$page_keywords = isset( $keywords['keywords'] ) ? $keywords['keywords'] : '';

			if ( ! $page_keywords ) {
				continue;
			}

			// Find semantically related pages
			$keywords_array = array_map( 'trim', explode( ',', $page_keywords ) );

			foreach ( $keywords_array as $keyword ) {
				if ( ! isset( $clusters[ $keyword ] ) ) {
					$clusters[ $keyword ] = array();
				}

				$clusters[ $keyword ][] = $page['post_id'];
			}
		}

		// Create pillar-cluster relationships
		$semantic_structure = array();
		foreach ( $clusters as $pillar => $cluster_pages ) {
			if ( count( $cluster_pages ) > 2 ) {
				$semantic_structure[ $pillar ] = array(
					'pillar_post'  => $cluster_pages[0],
					'cluster_posts' => array_slice( $cluster_pages, 1 ),
					'link_type'    => 'topic_cluster',
				);
			}
		}

		return $semantic_structure;
	}

	/**
	 * Calculate link velocity
	 *
	 * @param int $post_id Post ID.
	 * @return array Link velocity data
	 */
	public function calculate_link_velocity( $post_id ) {
		global $wpdb;

		// Get links by day
		$links_by_day = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as date, COUNT(*) as count
				FROM {$wpdb->prefix}pseo_internal_links
				WHERE source_post_id = %d OR target_post_id = %d
				GROUP BY DATE(created_at)
				ORDER BY date DESC
				LIMIT 30",
				$post_id,
				$post_id
			),
			ARRAY_A
		);

		$velocity = array(
			'daily_links'   => $links_by_day,
			'total_links'   => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}pseo_internal_links
					WHERE source_post_id = %d OR target_post_id = %d",
					$post_id,
					$post_id
				)
			),
			'avg_daily'     => 0,
			'status'        => 'normal',
		);

		if ( ! empty( $links_by_day ) ) {
			$counts = array_column( $links_by_day, 'count' );
			$velocity['avg_daily'] = round( array_sum( $counts ) / count( $counts ), 2 );

			// Check for spike (velocity spike could trigger review)
			$recent = $counts[0] ?? 0;
			$older_avg = count( $counts ) > 1 ? array_sum( array_slice( $counts, 1 ) ) / ( count( $counts ) - 1 ) : 0;

			if ( $recent > $older_avg * 2 ) {
				$velocity['status'] = 'spike';
			}
		}

		return $velocity;
	}

	/**
	 * Optimize anchor text diversity
	 *
	 * @param int $post_id Post ID.
	 * @return array Anchor text analysis
	 */
	public function optimize_anchor_diversity( $post_id ) {
		global $wpdb;

		// Get all links to this post
		$incoming_links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT anchor_text, COUNT(*) as count
				FROM {$wpdb->prefix}pseo_internal_links
				WHERE target_post_id = %d
				GROUP BY anchor_text",
				$post_id
			),
			ARRAY_A
		);

		$post = get_post( $post_id );
		$total_links = array_sum( array_column( $incoming_links, 'count' ) );

		$analysis = array(
			'total_links'       => $total_links,
			'anchor_texts'      => array(),
			'diversity_score'   => 0,
			'issues'            => array(),
		);

		// Analyze anchor text distribution
		foreach ( $incoming_links as $link ) {
			$percentage = $total_links > 0 ? ( $link['count'] / $total_links ) * 100 : 0;

			$analysis['anchor_texts'][] = array(
				'text'       => $link['anchor_text'],
				'count'      => $link['count'],
				'percentage' => round( $percentage, 2 ),
			);

			// Over-optimization check (>15% of anchors should be exact match)
			if ( $link['anchor_text'] === $post->post_title && $percentage > 15 ) {
				$analysis['issues'][] = 'Over-optimized exact match anchors: ' . round( $percentage, 1 ) . '%';
			}
		}

		// Calculate diversity score (1 - concentration)
		// Lower Herfindahl index = higher diversity
		$concentration = 0;
		foreach ( $incoming_links as $link ) {
			$share = $total_links > 0 ? $link['count'] / $total_links : 0;
			$concentration += $share ** 2;
		}

		$analysis['diversity_score'] = round( ( 1 - $concentration ) * 100, 2 );

		// Recommendations
		if ( $analysis['diversity_score'] < 40 ) {
			$analysis['recommendations'][] = 'Increase anchor text diversity';
			$analysis['recommendations'][] = 'Use natural language anchors';
		}

		return $analysis;
	}

	/**
	 * Find orphaned pages (no internal links)
	 *
	 * @return array Orphaned pages
	 */
	public function find_orphaned_pages() {
		global $wpdb;

		$orphaned = $wpdb->get_results(
			"SELECT p.post_id, p.title, p.slug
			FROM {$wpdb->prefix}pseo_generated_pages p
			WHERE p.status = 'published'
			AND p.post_id NOT IN (
				SELECT DISTINCT source_post_id FROM {$wpdb->prefix}pseo_internal_links
				UNION
				SELECT DISTINCT target_post_id FROM {$wpdb->prefix}pseo_internal_links
			)",
			ARRAY_A
		);

		return array(
			'orphaned_pages' => $orphaned,
			'count'          => count( $orphaned ),
		);
	}

	/**
	 * Get link distribution metrics
	 *
	 * @return array Link metrics
	 */
	public function get_link_distribution() {
		global $wpdb;

		$distribution = array(
			'total_links'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_internal_links" ),
			'by_type'          => array(),
			'pages_with_links' => 0,
			'avg_links_per_page' => 0,
		);

		// Links by type
		$by_type = $wpdb->get_results(
			"SELECT link_type, COUNT(*) as count FROM {$wpdb->prefix}pseo_internal_links GROUP BY link_type",
			ARRAY_A
		);

		foreach ( $by_type as $type ) {
			$distribution['by_type'][ $type['link_type'] ] = (int) $type['count'];
		}

		// Pages with links
		$distribution['pages_with_links'] = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT source_post_id) FROM {$wpdb->prefix}pseo_internal_links"
		);

		// Average links per page
		if ( $distribution['pages_with_links'] > 0 ) {
			$distribution['avg_links_per_page'] = round( $distribution['total_links'] / $distribution['pages_with_links'], 2 );
		}

		return $distribution;
	}

	/**
	 * Recommend link targets for a page
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit Number of recommendations.
	 * @return array Link recommendations
	 */
	public function recommend_link_targets( $post_id, $limit = 5 ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		// Get keywords from current page
		$meta = get_post_meta( $post_id, '_pseo_meta', true );
		$keywords = isset( $meta['keywords'] ) ? array_map( 'trim', explode( ',', $meta['keywords'] ) ) : array();

		$recommendations = array();

		// Find pages with matching keywords
		foreach ( array_slice( $keywords, 0, 3 ) as $keyword ) {
			$related_pages = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT pg.post_id, pg.title, GROUP_CONCAT(kr.keyword) as keywords
					FROM {$wpdb->prefix}pseo_generated_pages pg
					LEFT JOIN {$wpdb->prefix}pseo_keyword_rankings kr ON pg.post_id = kr.post_id
					WHERE pg.post_id != %d
					AND pg.status = 'published'
					AND (pg.title LIKE %s OR kr.keyword LIKE %s)
					GROUP BY pg.post_id
					LIMIT 10",
					$post_id,
					'%' . $wpdb->esc_like( $keyword ) . '%',
					'%' . $wpdb->esc_like( $keyword ) . '%'
				),
				ARRAY_A
			);

			foreach ( $related_pages as $page ) {
				// Check if link already exists
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}pseo_internal_links
						WHERE source_post_id = %d AND target_post_id = %d",
						$post_id,
						$page['post_id']
					)
				);

				if ( ! $exists ) {
					$recommendations[] = array(
						'target_post_id' => $page['post_id'],
						'title'          => $page['title'],
						'keyword'        => $keyword,
						'anchor_text'    => $keyword,
						'priority'       => 'high',
					);
				}
			}
		}

		return array_slice( $recommendations, 0, $limit );
	}

	/**
	 * Create contextual links automatically
	 *
	 * @param int $post_id Source post ID.
	 * @param int $max_links Maximum links to create.
	 */
	public function create_contextual_links( $post_id, $max_links = 5 ) {
		$recommendations = $this->recommend_link_targets( $post_id, $max_links );

		foreach ( $recommendations as $rec ) {
			$link_id = $this->create_internal_link(
				$post_id,
				$rec['target_post_id'],
				$rec['anchor_text'],
				'contextual'
			);

			if ( $link_id ) {
				pseo_log( "Contextual link created: {$post_id} -> {$rec['target_post_id']}", 'info' );
			}
		}
	}

	/**
	 * Create internal link
	 *
	 * @param int    $source_post_id Source post ID.
	 * @param int    $target_post_id Target post ID.
	 * @param string $anchor_text Anchor text.
	 * @param string $link_type Link type.
	 * @return int|false Link ID or false
	 */
	private function create_internal_link( $source_post_id, $target_post_id, $anchor_text = '', $link_type = 'contextual' ) {
		global $wpdb;

		// Check if link already exists
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}pseo_internal_links
				WHERE source_post_id = %d AND target_post_id = %d",
				$source_post_id,
				$target_post_id
			)
		);

		if ( $exists ) {
			return false;
		}

		return $wpdb->insert(
			$wpdb->prefix . 'pseo_internal_links',
			array(
				'source_post_id' => $source_post_id,
				'target_post_id' => $target_post_id,
				'anchor_text'    => $anchor_text,
				'link_type'      => $link_type,
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Analyze link authority flow (simplified PageRank)
	 *
	 * @return array Authority scores
	 */
	public function analyze_link_authority() {
		global $wpdb;

		// Simple link authority calculation
		// Count incoming links as authority indicator
		$authority = $wpdb->get_results(
			"SELECT
				target_post_id,
				COUNT(*) as inbound_links
			FROM {$wpdb->prefix}pseo_internal_links
			GROUP BY target_post_id
			ORDER BY inbound_links DESC
			LIMIT 20",
			ARRAY_A
		);

		$scores = array();
		foreach ( $authority as $item ) {
			$post = get_post( $item['target_post_id'] );
			if ( $post ) {
				$scores[] = array(
					'post_id'        => $item['target_post_id'],
					'title'          => $post->post_title,
					'inbound_links'  => $item['inbound_links'],
					'authority'      => min( 100, $item['inbound_links'] * 5 ), // Simplified score
				);
			}
		}

		return $scores;
	}

	/**
	 * Schedule link optimization
	 */
	public function schedule_link_optimization() {
		if ( ! wp_next_scheduled( 'pseo_optimize_links' ) ) {
			wp_schedule_event( time(), 'weekly', 'pseo_optimize_links' );
		}
	}

	/**
	 * Run link optimization
	 */
	public function run_link_optimization() {
		global $wpdb;

		// Get orphaned pages
		$orphaned = $this->find_orphaned_pages();

		// Create links for orphaned pages
		foreach ( $orphaned['orphaned_pages'] as $page ) {
			$this->create_contextual_links( $page['post_id'], 3 );
		}

		// Analyze anchor diversity
		$all_pages = $wpdb->get_col(
			"SELECT post_id FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published' LIMIT 50"
		);

		foreach ( $all_pages as $post_id ) {
			$diversity = $this->optimize_anchor_diversity( $post_id );
			update_post_meta( $post_id, '_pseo_anchor_diversity', $diversity );
		}

		pseo_log( 'Link optimization completed', 'info' );
	}

	/**
	 * Get link health report
	 *
	 * @return array Health report
	 */
	public function get_link_health_report() {
		$report = array(
			'distribution'      => $this->get_link_distribution(),
			'orphaned_pages'    => $this->find_orphaned_pages(),
			'authority_scores'  => $this->analyze_link_authority(),
			'recommendations'   => array(),
		);

		// Generate recommendations
		if ( $report['orphaned_pages']['count'] > 0 ) {
			$report['recommendations'][] = $report['orphaned_pages']['count'] . ' orphaned pages need linking';
		}

		if ( $report['distribution']['avg_links_per_page'] < 2 ) {
			$report['recommendations'][] = 'Average links per page is low. Increase internal linking.';
		}

		return $report;
	}
}
