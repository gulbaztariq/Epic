<?php
/**
 * Page not found.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

	<section class="section" style="padding-block:clamp(60px,9vw,120px)">
		<div class="container container-narrow text-center">
			<p class="eyebrow" style="justify-content:center">Error 404</p>
			<h1>We couldn't find that page</h1>
			<p class="lead">The page you are looking for may have moved, or the link may be out of date.</p>
			<div class="hero-actions" style="justify-content:center">
				<a class="btn btn-primary btn-lg" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home</a>
				<a class="btn btn-outline btn-lg" href="<?php echo esc_url( epic_page_url( 'publications' ) ); ?>">Browse publications</a>
			</div>
		</div>
	</section>

<?php
get_footer();
