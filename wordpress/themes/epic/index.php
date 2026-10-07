<?php
/**
 * Fallback template. Every address on the EPIC site has a template of its own; this is only
 * reached for something unexpected (for example an attachment page).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

status_header( 404 );
get_template_part( '404' );
