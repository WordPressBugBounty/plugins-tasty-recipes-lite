<?php
/**
 * Template for generating the AdThrive oEmbed response.
 *
 * @package Tasty_Recipes
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

echo wp_json_encode(
	array(
		'provider_url'  => 'https://www.adthrive.com/',
		'title'         => $title,
		'description'   => $description,
		'type'          => 'video',
		'thumbnail_url' => sprintf( 'https://content.jwplatform.com/thumbs/%s-720.jpg', $video_id ),
		'content_url'   => sprintf( 'https://content.jwplatform.com/videos/%s.mp4', $video_id ),
		'video_id'      => $video_id,
		'upload_date'   => $upload_date,
	)
);
