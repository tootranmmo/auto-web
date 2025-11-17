<?php
/**
 * Advanced Caching Manager - Redis Integration
 *
 * @package ProgrammaticSEO\Cache
 */

namespace ProgrammaticSEO\Cache;

/**
 * Class Cache_Manager
 * Handles multi-layer caching with Redis/Memcached
 */
class Cache_Manager {

	/**
	 * Cache instance
	 *
	 * @var \Redis|\Memcached
	 */
	private $cache;

	/**
	 * Cache type
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Default TTL
	 *
	 * @var int
	 */
	private $ttl = 3600;

	/**
	 * Cache key prefix
	 *
	 * @var string
	 */
	private $prefix = 'pseo_';

	/**
	 * Cache stats
	 *
	 * @var array
	 */
	private $stats = array(
		'hits'    => 0,
		'misses'  => 0,
		'sets'    => 0,
		'deletes' => 0,
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_cache();
		add_action( 'admin_init', array( $this, 'register_cache_settings' ) );
	}

	/**
	 * Initialize cache layer
	 */
	private function init_cache() {
		// Check if Redis is available
		if ( extension_loaded( 'redis' ) ) {
			$this->type = 'redis';
			$this->init_redis();
		} elseif ( extension_loaded( 'memcached' ) ) {
			$this->type = 'memcached';
			$this->init_memcached();
		} else {
			$this->type = 'wordpress';
			// Fallback to WordPress transients
		}

		pseo_log( "Cache initialized: {$this->type}", 'info' );
	}

	/**
	 * Initialize Redis connection
	 */
	private function init_redis() {
		try {
			$this->cache = new \Redis();
			$host = defined( 'PSEO_REDIS_HOST' ) ? PSEO_REDIS_HOST : 'localhost';
			$port = defined( 'PSEO_REDIS_PORT' ) ? PSEO_REDIS_PORT : 6379;

			if ( ! $this->cache->connect( $host, $port ) ) {
				throw new \Exception( 'Failed to connect to Redis' );
			}

			// Test connection
			$this->cache->ping();
		} catch ( \Exception $e ) {
			pseo_log( "Redis connection failed: {$e->getMessage()}", 'error' );
			$this->type = 'wordpress';
		}
	}

	/**
	 * Initialize Memcached connection
	 */
	private function init_memcached() {
		try {
			$this->cache = new \Memcached();
			$host = defined( 'PSEO_MEMCACHED_HOST' ) ? PSEO_MEMCACHED_HOST : 'localhost';
			$port = defined( 'PSEO_MEMCACHED_PORT' ) ? PSEO_MEMCACHED_PORT : 11211;

			if ( ! $this->cache->addServer( $host, $port ) ) {
				throw new \Exception( 'Failed to connect to Memcached' );
			}
		} catch ( \Exception $e ) {
			pseo_log( "Memcached connection failed: {$e->getMessage()}", 'error' );
			$this->type = 'wordpress';
		}
	}

	/**
	 * Get value from cache
	 *
	 * @param string $key Cache key.
	 * @param mixed  $default Default value.
	 * @return mixed Cached value or default
	 */
	public function get( $key, $default = false ) {
		$key = $this->prefix . $key;

		switch ( $this->type ) {
			case 'redis':
				$value = $this->cache->get( $key );
				break;

			case 'memcached':
				$value = $this->cache->get( $key );
				break;

			case 'wordpress':
			default:
				$value = get_transient( $key );
				break;
		}

		if ( $value !== false ) {
			$this->stats['hits']++;
			return $value;
		}

		$this->stats['misses']++;
		return $default;
	}

	/**
	 * Set value in cache
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl Time to live in seconds.
	 * @return bool
	 */
	public function set( $key, $value, $ttl = null ) {
		$key = $this->prefix . $key;
		$ttl = $ttl ?? $this->ttl;

		$this->stats['sets']++;

		switch ( $this->type ) {
			case 'redis':
				return $this->cache->setex( $key, $ttl, wp_json_encode( $value ) );

			case 'memcached':
				return $this->cache->set( $key, wp_json_encode( $value ), $ttl );

			case 'wordpress':
			default:
				return set_transient( $key, $value, $ttl );
		}
	}

	/**
	 * Delete from cache
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( $key ) {
		$key = $this->prefix . $key;
		$this->stats['deletes']++;

		switch ( $this->type ) {
			case 'redis':
				return (bool) $this->cache->del( $key );

			case 'memcached':
				return $this->cache->delete( $key );

			case 'wordpress':
			default:
				return delete_transient( $key );
		}
	}

	/**
	 * Clear all cache
	 *
	 * @return bool
	 */
	public function flush() {
		switch ( $this->type ) {
			case 'redis':
				return $this->cache->flushDb();

			case 'memcached':
				return $this->cache->flush();

			case 'wordpress':
			default:
				// Clear WordPress transients
				global $wpdb;
				$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%{$this->prefix}%'" );
				return true;
		}
	}

	/**
	 * Cache with tags (for grouped invalidation)
	 *
	 * @param string $tag Cache tag.
	 * @param string $key Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl TTL in seconds.
	 * @return bool
	 */
	public function set_tagged( $tag, $key, $value, $ttl = null ) {
		// Store value
		$this->set( $key, $value, $ttl );

		// Store tag reference
		$tag_key = $this->prefix . 'tag_' . $tag;
		if ( $this->type === 'redis' ) {
			$this->cache->sAdd( $tag_key, $key );
		} else {
			// Fallback for non-Redis
			$keys = (array) $this->get( 'tag_' . $tag, array() );
			$keys[] = $key;
			$this->set( 'tag_' . $tag, $keys, $ttl );
		}

		return true;
	}

	/**
	 * Invalidate by tag
	 *
	 * @param string $tag Cache tag.
	 * @return bool
	 */
	public function invalidate_tag( $tag ) {
		$tag_key = $this->prefix . 'tag_' . $tag;

		if ( $this->type === 'redis' ) {
			$keys = $this->cache->sMembers( $tag_key );
			foreach ( $keys as $key ) {
				$this->cache->del( $key );
			}
			$this->cache->del( $tag_key );
		} else {
			$keys = (array) $this->get( 'tag_' . $tag, array() );
			foreach ( $keys as $key ) {
				$this->delete( str_replace( $this->prefix, '', $key ) );
			}
			$this->delete( 'tag_' . $tag );
		}

		return true;
	}

	/**
	 * Cache result of expensive operation
	 *
	 * @param string   $key Cache key.
	 * @param callable $callback Operation to cache.
	 * @param int      $ttl TTL in seconds.
	 * @return mixed Cached or fresh result
	 */
	public function remember( $key, $callback, $ttl = null ) {
		$value = $this->get( $key );

		if ( $value !== false ) {
			return $value;
		}

		$value = call_user_func( $callback );
		$this->set( $key, $value, $ttl );

		return $value;
	}

	/**
	 * Cache array/object as key-value pairs
	 *
	 * @param string $prefix Key prefix.
	 * @param array  $data Data to cache.
	 * @param int    $ttl TTL in seconds.
	 * @return bool
	 */
	public function set_many( $prefix, $data, $ttl = null ) {
		foreach ( $data as $key => $value ) {
			$this->set( $prefix . '_' . $key, $value, $ttl );
		}
		return true;
	}

	/**
	 * Get multiple cache values
	 *
	 * @param string $prefix Key prefix.
	 * @param array  $keys Cache keys.
	 * @return array Retrieved values
	 */
	public function get_many( $prefix, $keys ) {
		$results = array();

		foreach ( $keys as $key ) {
			$results[ $key ] = $this->get( $prefix . '_' . $key );
		}

		return $results;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats
	 */
	public function get_stats() {
		$total_requests = $this->stats['hits'] + $this->stats['misses'];
		$hit_rate       = $total_requests > 0 ? ( $this->stats['hits'] / $total_requests ) * 100 : 0;

		return array(
			'type'           => $this->type,
			'hits'           => $this->stats['hits'],
			'misses'         => $this->stats['misses'],
			'sets'           => $this->stats['sets'],
			'deletes'        => $this->stats['deletes'],
			'hit_rate'       => round( $hit_rate, 2 ),
			'total_requests' => $total_requests,
		);
	}

	/**
	 * Reset statistics
	 */
	public function reset_stats() {
		$this->stats = array(
			'hits'    => 0,
			'misses'  => 0,
			'sets'    => 0,
			'deletes' => 0,
		);
	}

	/**
	 * Register cache settings in admin
	 */
	public function register_cache_settings() {
		register_setting( 'programmatic_seo_settings', 'pseo_cache_settings' );

		add_settings_field(
			'pseo_cache_enabled',
			__( 'Enable Caching', 'programmatic-seo' ),
			array( $this, 'cache_enabled_callback' ),
			'programmatic_seo_settings',
			'pseo_general'
		);

		add_settings_field(
			'pseo_cache_ttl',
			__( 'Cache TTL (seconds)', 'programmatic-seo' ),
			array( $this, 'cache_ttl_callback' ),
			'programmatic_seo_settings',
			'pseo_general'
		);
	}

	/**
	 * Cache enabled callback
	 */
	public function cache_enabled_callback() {
		$options = get_option( 'pseo_cache_settings' );
		$value   = isset( $options['enabled'] ) ? $options['enabled'] : 1;
		?>
		<input type="checkbox" name="pseo_cache_settings[enabled]" value="1" <?php checked( $value, 1 ); ?> />
		<label><?php esc_html_e( 'Enable caching system', 'programmatic-seo' ); ?></label>
		<?php
	}

	/**
	 * Cache TTL callback
	 */
	public function cache_ttl_callback() {
		$options = get_option( 'pseo_cache_settings' );
		$value   = isset( $options['ttl'] ) ? $options['ttl'] : 3600;
		?>
		<input type="number" name="pseo_cache_settings[ttl]" value="<?php echo esc_attr( $value ); ?>" />
		<label><?php esc_html_e( 'Default cache time in seconds', 'programmatic-seo' ); ?></label>
		<?php
	}

	/**
	 * Get cache info
	 *
	 * @return array Cache info
	 */
	public function get_info() {
		$info = array(
			'type'       => $this->type,
			'prefix'     => $this->prefix,
			'stats'      => $this->get_stats(),
		);

		if ( $this->type === 'redis' && $this->cache ) {
			$info['redis_info'] = $this->cache->info();
		}

		return $info;
	}
}
