<?php
/**
 * Schema Markup Generator Class
 *
 * @package ProgrammaticSEO\Schema
 */

namespace ProgrammaticSEO\Schema;

/**
 * Class Schema_Generator
 */
class Schema_Generator {

	/**
	 * Generate schema markup
	 *
	 * @param int $post_id Post ID.
	 * @return array Schema data
	 */
	public function generate_schema( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'WebPage',
			'headline'    => $post->post_title,
			'description' => wp_trim_excerpt( $post->post_content ),
			'url'         => get_permalink( $post_id ),
			'author'      => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $post->post_author ),
			),
			'datePublished' => mysql2date( 'c', $post->post_date_gmt ),
			'dateModified'  => mysql2date( 'c', $post->post_modified_gmt ),
			'publisher'     => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'logo'  => array(
					'@type' => 'ImageObject',
					'url'   => wp_get_attachment_url( get_theme_mod( 'custom_logo' ) ),
				),
			),
		);

		// Add image if available
		if ( has_post_thumbnail( $post_id ) ) {
			$image_id = get_post_thumbnail_id( $post_id );
			$schema['image'] = array(
				'@type' => 'ImageObject',
				'url'   => wp_get_attachment_url( $image_id ),
				'width' => (int) wp_get_attachment_metadata( $image_id )['width'] ?? 1200,
				'height' => (int) wp_get_attachment_metadata( $image_id )['height'] ?? 628,
			);
		}

		// Get custom schema from post meta
		$custom_schema = get_post_meta( $post_id, '_pseo_schema', true );
		if ( $custom_schema ) {
			$schema = array_merge( $schema, $custom_schema );
		}

		return $schema;
	}

	/**
	 * Render schema markup as JSON-LD
	 *
	 * @param int $post_id Post ID.
	 * @return string JSON-LD script tag
	 */
	public function render_schema( $post_id ) {
		$schema = $this->generate_schema( $post_id );

		if ( empty( $schema ) ) {
			return '';
		}

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . PHP_EOL;
	}

	/**
	 * Generate Article schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data Data from data source.
	 * @return array Article schema
	 */
	public function generate_article_schema( $post_id, $data = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'Article',
			'headline'   => $post->post_title,
			'description' => wp_trim_excerpt( $post->post_content ),
			'image'      => has_post_thumbnail( $post_id ) ? wp_get_attachment_url( get_post_thumbnail_id( $post_id ) ) : '',
			'datePublished' => mysql2date( 'c', $post->post_date_gmt ),
			'dateModified'  => mysql2date( 'c', $post->post_modified_gmt ),
			'author'     => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $post->post_author ),
			),
			'publisher'  => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			),
		);

		return $schema;
	}

	/**
	 * Generate Product schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data Product data.
	 * @return array Product schema
	 */
	public function generate_product_schema( $post_id, $data = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$schema = array(
			'@context'      => 'https://schema.org/',
			'@type'         => 'Product',
			'name'          => $data['name'] ?? $post->post_title,
			'description'   => $data['description'] ?? wp_trim_excerpt( $post->post_content ),
			'image'         => has_post_thumbnail( $post_id ) ? wp_get_attachment_url( get_post_thumbnail_id( $post_id ) ) : '',
			'brand'         => array(
				'@type' => 'Brand',
				'name'  => $data['brand'] ?? get_bloginfo( 'name' ),
			),
			'offers'        => array(
				'@type'         => 'Offer',
				'url'           => get_permalink( $post_id ),
				'priceCurrency' => $data['currency'] ?? 'USD',
				'price'         => $data['price'] ?? '0',
				'availability'  => 'https://schema.org/InStock',
				'seller'        => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
				),
			),
		);

		if ( isset( $data['rating'] ) && isset( $data['review_count'] ) ) {
			$schema['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $data['rating'],
				'reviewCount' => $data['review_count'],
			);
		}

		return $schema;
	}

	/**
	 * Generate BreadcrumbList schema
	 *
	 * @param int $post_id Post ID.
	 * @return array Breadcrumb schema
	 */
	public function generate_breadcrumb_schema( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$breadcrumbs = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => get_bloginfo( 'name' ),
				'item'     => home_url(),
			),
		);

		// Add parent post if exists
		if ( $post->post_parent ) {
			$breadcrumbs[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => get_the_title( $post->post_parent ),
				'item'     => get_permalink( $post->post_parent ),
			);
		}

		// Add current post
		$breadcrumbs[] = array(
			'@type'    => 'ListItem',
			'position' => count( $breadcrumbs ) + 1,
			'name'     => $post->post_title,
			'item'     => get_permalink( $post_id ),
		);

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $breadcrumbs,
		);
	}

	/**
	 * Generate FAQPage schema
	 *
	 * @param int   $post_id Post ID.
	 * @param array $faqs FAQ data.
	 * @return array FAQPage schema
	 */
	public function generate_faq_schema( $post_id, $faqs = array() ) {
		if ( empty( $faqs ) ) {
			return array();
		}

		$items = array();
		foreach ( $faqs as $faq ) {
			$items[] = array(
				'@type'          => 'Question',
				'name'           => $faq['question'] ?? '',
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $faq['answer'] ?? '',
				),
			);
		}

		return array(
			'@context'      => 'https://schema.org',
			'@type'         => 'FAQPage',
			'mainEntity'    => $items,
		);
	}
}
