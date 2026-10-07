<?php
/**
 * Router for `php -S`, standing in for the .htaccess rewrite rules of a real web server.
 *
 * PHP's built-in server answers its own 404 for a missing file whose name has an extension
 * (sitemap.xml, robots.txt) instead of passing it to WordPress. Existing files are served as
 * they are; every other address goes to index.php, as Apache's rewrite rules do.
 */

$root = $_SERVER['DOCUMENT_ROOT'];
$path = rawurldecode( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
$file = realpath( $root . $path );

if ( false !== $file && 0 === strpos( $file, realpath( $root ) ) && is_file( $file ) ) {
	return false;
}

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';

require $root . '/index.php';
