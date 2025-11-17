<?php
/**
 * Predictive Analytics & ML Forecasting
 *
 * @package ProgrammaticSEO\Analytics
 */

namespace ProgrammaticSEO\Analytics;

/**
 * Class Predictive_Analytics
 * ML-powered predictions for traffic, rankings, and trends
 */
class Predictive_Analytics {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'schedule_predictions' ) );
		add_action( 'pseo_generate_predictions', array( $this, 'generate_all_predictions' ) );
	}

	/**
	 * Predict traffic for a post
	 *
	 * @param int   $post_id Post ID.
	 * @param int   $days_ahead Days to forecast.
	 * @return array Forecast data
	 */
	public function predict_traffic( $post_id, $days_ahead = 30 ) {
		global $wpdb;

		// Get historical data
		$history = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date, views, clicks FROM {$wpdb->prefix}pseo_analytics
				WHERE post_id = %d
				ORDER BY date DESC
				LIMIT 90",
				$post_id
			),
			ARRAY_A
		);

		if ( count( $history ) < 7 ) {
			// Not enough data, use simple average
			return $this->forecast_simple( $history, $days_ahead );
		}

		// Use exponential moving average for forecast
		return $this->forecast_exponential_smoothing( $history, $days_ahead );
	}

	/**
	 * Simple forecast using average
	 *
	 * @param array $history Historical data.
	 * @param int   $days_ahead Days to forecast.
	 * @return array Forecast
	 */
	private function forecast_simple( $history, $days_ahead ) {
		$views = array_column( $history, 'views' );
		$avg_views = ! empty( $views ) ? array_sum( $views ) / count( $views ) : 0;

		$forecast = array(
			'method'     => 'simple_average',
			'forecast'   => array(),
			'confidence' => 0.6,
		);

		$today = gmdate( 'Y-m-d' );
		for ( $i = 1; $i <= $days_ahead; $i++ ) {
			$date = gmdate( 'Y-m-d', strtotime( "+{$i} days" ) );
			$forecast['forecast'][] = array(
				'date'  => $date,
				'views' => round( $avg_views ),
			);
		}

		return $forecast;
	}

	/**
	 * Exponential smoothing forecast
	 *
	 * @param array $history Historical data.
	 * @param int   $days_ahead Days to forecast.
	 * @return array Forecast
	 */
	private function forecast_exponential_smoothing( $history, $days_ahead ) {
		$views = array_reverse( array_column( $history, 'views' ) );
		$alpha = 0.3; // Smoothing factor

		// Calculate exponential moving average
		$ema = $views[0];
		for ( $i = 1; $i < count( $views ); $i++ ) {
			$ema = $alpha * $views[ $i ] + ( 1 - $alpha ) * $ema;
		}

		// Calculate trend
		$trend = 0;
		if ( count( $views ) > 7 ) {
			$recent_avg = array_sum( array_slice( $views, 0, 7 ) ) / 7;
			$old_avg = array_sum( array_slice( $views, -14, 7 ) ) / 7;
			$trend = ( $recent_avg - $old_avg ) / $old_avg;
		}

		$forecast = array(
			'method'     => 'exponential_smoothing',
			'forecast'   => array(),
			'confidence' => 0.75,
			'trend'      => $trend,
		);

		$base = $ema;
		$today = gmdate( 'Y-m-d' );

		for ( $i = 1; $i <= $days_ahead; $i++ ) {
			$date = gmdate( 'Y-m-d', strtotime( "+{$i} days" ) );
			// Apply trend to forecast
			$predicted = $base * ( 1 + ( $trend * $i / 30 ) );
			$forecast['forecast'][] = array(
				'date'  => $date,
				'views' => max( 0, round( $predicted ) ),
			);
		}

		return $forecast;
	}

	/**
	 * Predict keyword rankings
	 *
	 * @param int   $post_id Post ID.
	 * @param int   $days_ahead Days to forecast.
	 * @return array Ranking predictions
	 */
	public function predict_rankings( $post_id, $days_ahead = 30 ) {
		global $wpdb;

		// Get keywords and their ranking history
		$keywords = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT keyword FROM {$wpdb->prefix}pseo_keyword_rankings
				WHERE post_id = %d
				ORDER BY date DESC
				LIMIT 50",
				$post_id
			),
			ARRAY_A
		);

		$predictions = array();

		foreach ( $keywords as $kw_data ) {
			$keyword = $kw_data['keyword'];

			$history = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT date, position FROM {$wpdb->prefix}pseo_keyword_rankings
					WHERE post_id = %d AND keyword = %s
					ORDER BY date DESC
					LIMIT 30",
					$post_id,
					$keyword
				),
				ARRAY_A
			);

			if ( ! empty( $history ) ) {
				$trend = $this->calculate_ranking_trend( $history );

				$predictions[] = array(
					'keyword'           => $keyword,
					'current_position'  => (int) $history[0]['position'],
					'trend'             => $trend,
					'predicted_position_30days' => max( 1, (int) $history[0]['position'] + round( $trend * 30 ) ),
					'improvement_likely' => $trend < 0, // Lower position = better
				);
			}
		}

		return array(
			'predictions' => array_slice( $predictions, 0, 10 ), // Top 10 keywords
			'total'       => count( $predictions ),
		);
	}

	/**
	 * Calculate ranking trend
	 *
	 * @param array $history Position history.
	 * @return float Trend (negative = improving)
	 */
	private function calculate_ranking_trend( $history ) {
		if ( count( $history ) < 2 ) {
			return 0;
		}

		$positions = array_column( $history, 'position' );
		$positions = array_reverse( $positions );

		// Calculate linear regression
		$n = count( $positions );
		$sum_x = $n * ( $n + 1 ) / 2;
		$sum_y = array_sum( $positions );
		$sum_xy = 0;
		$sum_x2 = $n * ( $n + 1 ) * ( 2 * $n + 1 ) / 6;

		for ( $i = 0; $i < $n; $i++ ) {
			$sum_xy += ( $i + 1 ) * $positions[ $i ];
		}

		$slope = ( $n * $sum_xy - $sum_x * $sum_y ) / ( $n * $sum_x2 - $sum_x * $sum_x );

		return $slope;
	}

	/**
	 * Detect trending keywords
	 *
	 * @param int $post_id Post ID.
	 * @return array Trending keywords
	 */
	public function detect_trending_keywords( $post_id ) {
		$predictions = $this->predict_rankings( $post_id, 30 );

		// Filter for keywords likely to improve
		$trending = array_filter( $predictions['predictions'], function( $p ) {
			return $p['improvement_likely'] && $p['trend'] < -0.5; // Strong upward trend
		} );

		return array(
			'trending'       => array_values( $trending ),
			'count'          => count( $trending ),
		);
	}

	/**
	 * Forecast page ROI
	 *
	 * @param int $post_id Post ID.
	 * @return array ROI forecast
	 */
	public function forecast_roi( $post_id ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		// Get current metrics
		$analytics = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(views) as total_views, SUM(clicks) as total_clicks
				FROM {$wpdb->prefix}pseo_analytics
				WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);

		$views = $analytics['total_views'] ?? 0;
		$clicks = $analytics['total_clicks'] ?? 0;
		$ctr = $views > 0 ? $clicks / $views : 0;

		// Predict future traffic
		$traffic_forecast = $this->predict_traffic( $post_id, 90 );
		$future_views = array_sum( array_column( $traffic_forecast['forecast'], 'views' ) );
		$predicted_clicks = round( $future_views * $ctr );

		// Estimate conversion value (assuming $2 per click, customizable)
		$value_per_conversion = apply_filters( 'pseo_value_per_conversion', 2 );
		$predicted_value = $predicted_clicks * $value_per_conversion;

		return array(
			'current_views'        => $views,
			'current_clicks'       => $clicks,
			'ctr'                  => round( $ctr * 100, 2 ) . '%',
			'predicted_90day_views' => $future_views,
			'predicted_90day_clicks' => $predicted_clicks,
			'predicted_90day_value' => '$' . number_format( $predicted_value, 2 ),
			'roi_status'           => $predicted_clicks > 0 ? 'positive' : 'needs_improvement',
		);
	}

	/**
	 * Get next best opportunity
	 *
	 * @param int $post_id Post ID.
	 * @return array Opportunity
	 */
	public function get_next_opportunity( $post_id ) {
		// Get ranking predictions
		$rankings = $this->predict_rankings( $post_id );

		if ( empty( $rankings['predictions'] ) ) {
			return array();
		}

		// Find keywords at position 11-20 (easy wins)
		$opportunities = array_filter( $rankings['predictions'], function( $p ) {
			return $p['current_position'] >= 11 && $p['current_position'] <= 20 && $p['trend'] < 0;
		} );

		if ( empty( $opportunities ) ) {
			// No easy wins, return top improving keyword
			$trending = array_filter( $rankings['predictions'], function( $p ) {
				return $p['trend'] < 0;
			} );

			usort( $trending, function( $a, $b ) {
				return $a['trend'] <=> $b['trend'];
			} );

			$best = reset( $trending );
		} else {
			usort( $opportunities, function( $a, $b ) {
				return $a['current_position'] <=> $b['current_position'];
			} );

			$best = reset( $opportunities );
		}

		return array(
			'keyword'        => $best['keyword'],
			'current_position' => $best['current_position'],
			'predicted_position_30days' => $best['predicted_position_30days'],
			'action'         => 'Optimize content for keyword: ' . $best['keyword'],
			'priority'       => $best['current_position'] <= 20 ? 'high' : 'medium',
		);
	}

	/**
	 * Analyze content performance patterns
	 *
	 * @return array Performance analysis
	 */
	public function analyze_performance_patterns() {
		global $wpdb;

		// Get all generated pages with their metrics
		$pages = $wpdb->get_results(
			"SELECT
				p.post_id,
				p.title,
				COUNT(a.id) as data_points,
				SUM(a.views) as total_views,
				AVG(a.views) as avg_views,
				AVG(a.bounce_rate) as avg_bounce_rate
			FROM {$wpdb->prefix}pseo_generated_pages p
			LEFT JOIN {$wpdb->prefix}pseo_analytics a ON p.post_id = a.post_id
			WHERE p.status = 'published'
			GROUP BY p.post_id
			ORDER BY total_views DESC",
			ARRAY_A
		);

		$patterns = array(
			'high_performers'    => array(),
			'underperformers'    => array(),
			'average_performance' => 0,
			'total_pages'        => count( $pages ),
		);

		if ( ! empty( $pages ) ) {
			$views = array_column( $pages, 'total_views' );
			$patterns['average_performance'] = round( array_sum( $views ) / count( $views ) );

			foreach ( $pages as $page ) {
				if ( $page['total_views'] > $patterns['average_performance'] * 1.5 ) {
					$patterns['high_performers'][] = $page;
				} elseif ( $page['total_views'] < $patterns['average_performance'] * 0.5 ) {
					$patterns['underperformers'][] = $page;
				}
			}
		}

		return $patterns;
	}

	/**
	 * Schedule predictions
		 */
	public function schedule_predictions() {
		if ( ! wp_next_scheduled( 'pseo_generate_predictions' ) ) {
			wp_schedule_event( time(), 'daily', 'pseo_generate_predictions' );
		}
	}

	/**
	 * Generate all predictions
	 */
	public function generate_all_predictions() {
		global $wpdb;

		// Get all published pages
		$pages = $wpdb->get_col(
			"SELECT post_id FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published' LIMIT 100"
		);

		foreach ( $pages as $post_id ) {
			// Generate predictions
			$traffic_forecast = $this->predict_traffic( $post_id );
			$ranking_forecast = $this->predict_rankings( $post_id );
			$roi_forecast = $this->forecast_roi( $post_id );

			// Store predictions
			update_post_meta( $post_id, '_pseo_traffic_forecast', $traffic_forecast );
			update_post_meta( $post_id, '_pseo_ranking_forecast', $ranking_forecast );
			update_post_meta( $post_id, '_pseo_roi_forecast', $roi_forecast );
		}

		pseo_log( 'Predictions generated for ' . count( $pages ) . ' pages', 'info' );
	}

	/**
	 * Get predictive insights
	 *
	 * @param int $post_id Post ID.
	 * @return array Insights
	 */
	public function get_insights( $post_id ) {
		$insights = array();

		// Traffic insight
		$traffic = $this->predict_traffic( $post_id, 30 );
		if ( ! empty( $traffic['forecast'] ) ) {
			$future_views = array_sum( array_column( $traffic['forecast'], 'views' ) );
			$insights[] = array(
				'type'     => 'traffic',
				'title'    => 'Traffic Forecast',
				'value'    => number_format( $future_views ),
				'change'   => $traffic['trend'] > 0 ? '+' . round( $traffic['trend'] * 100, 1 ) . '%' : round( $traffic['trend'] * 100, 1 ) . '%',
				'positive' => $traffic['trend'] > 0,
			);
		}

		// Ranking insight
		$rankings = $this->predict_rankings( $post_id, 30 );
		if ( ! empty( $rankings['predictions'] ) ) {
			$improving = count( array_filter( $rankings['predictions'], function( $p ) {
				return $p['improvement_likely'];
			} ) );

			$insights[] = array(
				'type'     => 'rankings',
				'title'    => 'Ranking Improvements',
				'value'    => $improving . ' keywords',
				'positive' => $improving > 0,
			);
		}

		// ROI insight
		$roi = $this->forecast_roi( $post_id );
		if ( ! empty( $roi ) ) {
			$insights[] = array(
				'type'     => 'roi',
				'title'    => 'Projected Value',
				'value'    => $roi['predicted_90day_value'],
				'positive' => $roi['roi_status'] === 'positive',
			);
		}

		return $insights;
	}
}
