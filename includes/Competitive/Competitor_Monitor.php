<?php
/**
 * Competitor Monitoring & Intelligence
 *
 * @package ProgrammaticSEO\Competitive
 */

namespace ProgrammaticSEO\Competitive;

/**
 * Class Competitor_Monitor
 * Track competitor keywords, backlinks, and content
 */
class Competitor_Monitor {

	/**
	 * Competitors table
	 *
	 * @var string
	 */
	private $competitors_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->competitors_table = $wpdb->prefix . 'pseo_competitors';

		$this->create_competitors_table();
		add_action( 'admin_init', array( $this, 'schedule_monitoring' ) );
		add_action( 'pseo_monitor_competitors', array( $this, 'run_monitoring' ) );
	}

	/**
	 * Create competitors table
	 */
	private function create_competitors_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->competitors_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			domain VARCHAR(255) NOT NULL,
			name VARCHAR(255),
			keywords JSON,
			backlinks JSON,
			traffic_estimate INT,
			last_checked DATETIME,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY domain (domain)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Add competitor to track
	 *
	 * @param string $domain Competitor domain.
	 * @param string $name Competitor name.
	 * @return int|false Competitor ID or false
	 */
	public function add_competitor( $domain, $name = '' ) {
		global $wpdb;

		// Validate domain
		if ( ! filter_var( 'http://' . $domain, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->competitors_table,
			array(
				'domain' => sanitize_text_field( $domain ),
				'name'   => sanitize_text_field( $name ),
			),
			array( '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get all competitors
	 *
	 * @return array Competitors
	 */
	public function get_competitors() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$this->competitors_table} ORDER BY last_checked DESC",
			ARRAY_A
		);
	}

	/**
	 * Get competitor keywords
	 *
	 * @param string $domain Competitor domain.
	 * @return array Keywords
	 */
	public function get_competitor_keywords( $domain ) {
		global $wpdb;

		$competitor = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT keywords FROM {$this->competitors_table} WHERE domain = %s",
				$domain
			),
			ARRAY_A
		);

		if ( ! $competitor || ! $competitor['keywords'] ) {
			return array();
		}

		return json_decode( $competitor['keywords'], true ) ?? array();
	}

	/**
	 * Analyze keyword gap
	 *
	 * @return array Keyword gap analysis
	 */
	public function analyze_keyword_gap() {
		global $wpdb;

		// Get our keywords
		$our_keywords = $wpdb->get_col(
			"SELECT DISTINCT keyword FROM {$wpdb->prefix}pseo_keyword_rankings
			WHERE position <= 100"
		);

		// Get competitor keywords
		$competitors = $this->get_competitors();
		$competitor_keywords = array();

		foreach ( $competitors as $competitor ) {
			if ( $competitor['keywords'] ) {
				$comp_kw = json_decode( $competitor['keywords'], true );
				$competitor_keywords = array_merge( $competitor_keywords, $comp_kw );
			}
		}

		// Find gaps
		$gaps = array(
			'opportunities'     => array(),
			'competitive'       => array(),
			'owned'             => array(),
			'not_competitive'   => array(),
		);

		$all_keywords = array_unique( array_merge( $our_keywords, $competitor_keywords ) );

		foreach ( $all_keywords as $keyword ) {
			$our_rank = in_array( $keyword, $our_keywords );
			$comp_count = 0;

			foreach ( $competitors as $competitor ) {
				if ( $competitor['keywords'] ) {
					$comp_kw = json_decode( $competitor['keywords'], true );
					if ( in_array( $keyword, $comp_kw ) ) {
						$comp_count++;
					}
				}
			}

			if ( ! $our_rank && $comp_count > 0 ) {
				$gaps['opportunities'][] = array(
					'keyword'        => $keyword,
					'competitors'    => $comp_count,
					'difficulty'     => $comp_count * 20, // Simplified
				);
			} elseif ( $our_rank && $comp_count > 0 ) {
				$gaps['competitive'][] = $keyword;
			} elseif ( $our_rank && $comp_count === 0 ) {
				$gaps['owned'][] = $keyword;
			} else {
				$gaps['not_competitive'][] = $keyword;
			}
		}

		// Sort opportunities by difficulty
		usort( $gaps['opportunities'], function( $a, $b ) {
			return $a['difficulty'] <=> $b['difficulty'];
		} );

		return $gaps;
	}

	/**
	 * Analyze content gaps
	 *
	 * @param string $competitor_domain Competitor domain.
	 * @return array Content gaps
	 */
	public function analyze_content_gaps( $competitor_domain ) {
		global $wpdb;

		$competitor = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->competitors_table} WHERE domain = %s",
				$competitor_domain
			),
			ARRAY_A
		);

		if ( ! $competitor ) {
			return array();
		}

		$comp_keywords = json_decode( $competitor['keywords'], true ) ?? array();

		// Get our topics
		$our_keywords = $wpdb->get_col(
			"SELECT DISTINCT keyword FROM {$wpdb->prefix}pseo_keyword_rankings"
		);

		// Find content we're missing
		$missing_topics = array();
		foreach ( $comp_keywords as $kw ) {
			if ( ! in_array( $kw, $our_keywords ) ) {
				$missing_topics[] = $kw;
			}
		}

		return array(
			'competitor'      => $competitor['name'] ?? $competitor_domain,
			'missing_topics'  => array_slice( $missing_topics, 0, 10 ),
			'count'           => count( $missing_topics ),
			'action'          => 'Create content for: ' . implode( ', ', array_slice( $missing_topics, 0, 5 ) ),
		);
	}

	/**
	 * Get backlink profile
	 *
	 * @param string $domain Domain.
	 * @return array Backlink data
	 */
	public function get_backlink_profile( $domain ) {
		global $wpdb;

		$competitor = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT backlinks FROM {$this->competitors_table} WHERE domain = %s",
				$domain
			),
			ARRAY_A
		);

		if ( ! $competitor || ! $competitor['backlinks'] ) {
			return array(
				'total_backlinks' => 0,
				'top_sources'     => array(),
				'profile'         => array(),
			);
		}

		$backlinks = json_decode( $competitor['backlinks'], true ) ?? array();

		// Analyze backlink sources
		$sources = array();
		foreach ( $backlinks as $link ) {
			$domain_parts = wp_parse_url( $link['source'] ?? '' );
			$source_domain = $domain_parts['host'] ?? 'unknown';

			if ( ! isset( $sources[ $source_domain ] ) ) {
				$sources[ $source_domain ] = 0;
			}
			$sources[ $source_domain ]++;
		}

		arsort( $sources );
		$top_sources = array_slice( $sources, 0, 10 );

		return array(
			'total_backlinks' => count( $backlinks ),
			'top_sources'     => $top_sources,
			'profile'         => array(
				'dofollow'   => count( array_filter( $backlinks, function( $l ) {
					return $l['follow'] ?? true;
				} ) ),
				'nofollow'   => count( array_filter( $backlinks, function( $l ) {
					return ! ( $l['follow'] ?? true );
				} ) ),
			),
		);
	}

	/**
	 * Compare with competitors
	 *
	 * @return array Comparison data
	 */
	public function compare_with_competitors() {
		global $wpdb;

		$competitors = $this->get_competitors();

		// Get our metrics
		$our_keywords = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT keyword) FROM {$wpdb->prefix}pseo_keyword_rankings WHERE position <= 100"
		);

		$our_traffic = (int) $wpdb->get_var(
			"SELECT SUM(views) FROM {$wpdb->prefix}pseo_analytics"
		);

		$comparison = array(
			'us'          => array(
				'domain'       => home_url(),
				'tracked_keywords' => $our_keywords,
				'estimated_traffic' => $our_traffic,
			),
			'competitors' => array(),
			'rank'        => 1,
		);

		foreach ( $competitors as $competitor ) {
			$comp_keywords = json_decode( $competitor['keywords'], true ) ?? array();

			$competitor_data = array(
				'domain'           => $competitor['domain'],
				'name'             => $competitor['name'],
				'tracked_keywords' => count( $comp_keywords ),
				'estimated_traffic' => $competitor['traffic_estimate'],
				'last_checked'     => $competitor['last_checked'],
			);

			$comparison['competitors'][] = $competitor_data;

			// Rank us
			if ( $our_keywords < count( $comp_keywords ) ) {
				$comparison['rank']++;
			}
		}

		// Sort competitors by keywords
		usort( $comparison['competitors'], function( $a, $b ) {
			return $b['tracked_keywords'] <=> $a['tracked_keywords'];
		} );

		return $comparison;
	}

	/**
	 * Get competitive advantages
	 *
	 * @return array Advantages
	 */
	public function get_competitive_advantages() {
		$gap_analysis = $this->analyze_keyword_gap();
		$comparison = $this->compare_with_competitors();

		$advantages = array();

		// We own these keywords
		if ( ! empty( $gap_analysis['owned'] ) ) {
			$advantages[] = array(
				'type'        => 'owned_keywords',
				'title'       => 'Owned Keywords',
				'count'       => count( $gap_analysis['owned'] ),
				'description' => 'Keywords we rank for that competitors don\'t',
			);
		}

		// Competitive keywords we're winning
		if ( ! empty( $gap_analysis['competitive'] ) ) {
			$advantages[] = array(
				'type'        => 'competitive_wins',
				'title'       => 'Competitive Wins',
				'count'       => count( $gap_analysis['competitive'] ),
				'description' => 'Keywords where we compete with rivals',
			);
		}

		// Market position
		if ( isset( $comparison['rank'] ) ) {
			$advantages[] = array(
				'type'        => 'market_position',
				'title'       => 'Market Position',
				'rank'        => $comparison['rank'],
				'description' => 'Your ranking vs competitors',
			);
		}

		return $advantages;
	}

	/**
	 * Schedule competitor monitoring
	 */
	public function schedule_monitoring() {
		if ( ! wp_next_scheduled( 'pseo_monitor_competitors' ) ) {
			wp_schedule_event( time(), 'weekly', 'pseo_monitor_competitors' );
		}
	}

	/**
	 * Run competitor monitoring
	 */
	public function run_monitoring() {
		$competitors = $this->get_competitors();

		foreach ( $competitors as $competitor ) {
			$this->update_competitor_data( $competitor['domain'] );
		}

		pseo_log( 'Competitor monitoring completed for ' . count( $competitors ) . ' domains', 'info' );
	}

	/**
	 * Update competitor data (would integrate with APIs)
	 *
	 * @param string $domain Competitor domain.
	 */
	private function update_competitor_data( $domain ) {
		global $wpdb;

		// Simulate fetching competitor data
		// In production, this would integrate with SEMrush, Ahrefs, SimilarWeb APIs

		$mock_keywords = array(
			'seo tips',
			'content marketing',
			'digital marketing',
			'web design',
			'social media',
		);

		$mock_backlinks = array(
			array( 'source' => 'https://example.com', 'follow' => true ),
			array( 'source' => 'https://blog.example.com', 'follow' => true ),
		);

		$wpdb->update(
			$this->competitors_table,
			array(
				'keywords'     => wp_json_encode( $mock_keywords ),
				'backlinks'    => wp_json_encode( $mock_backlinks ),
				'traffic_estimate' => rand( 1000, 10000 ),
				'last_checked' => current_time( 'mysql' ),
			),
			array( 'domain' => $domain ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%s' )
		);

		pseo_log( "Competitor data updated: {$domain}", 'info' );
	}

	/**
	 * Get intelligence report
	 *
	 * @return array Intelligence report
	 */
	public function get_intelligence_report() {
		$report = array(
			'summary'              => $this->compare_with_competitors(),
			'keyword_gaps'         => $this->analyze_keyword_gap(),
			'competitive_advantages' => $this->get_competitive_advantages(),
			'recommendations'      => array(),
		);

		// Generate recommendations
		$opportunities = $report['keyword_gaps']['opportunities'];
		if ( ! empty( $opportunities ) ) {
			$top_opp = array_slice( $opportunities, 0, 3 );
			foreach ( $top_opp as $opp ) {
				$report['recommendations'][] = array(
					'action'     => 'Target keyword: ' . $opp['keyword'],
					'priority'   => 'high',
					'difficulty' => $opp['difficulty'],
				);
			}
		}

		return $report;
	}

	/**
	 * Delete competitor
	 *
	 * @param int $competitor_id Competitor ID.
	 * @return bool Success
	 */
	public function delete_competitor( $competitor_id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->competitors_table,
			array( 'id' => $competitor_id ),
			array( '%d' )
		);
	}
}
