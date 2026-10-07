<?php
/**
 * Search results: /search?q=…
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_term    = isset( $_GET['q'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$results = Epic_Data::search( $epic_term );
epic_trail( array( 'Search' => null ) );

get_header();
epic_hero(
	array(
		'title'    => 'Search',
		'subtitle' => $epic_term ? 'Results for “' . $epic_term . '”' : 'Search EPIC publications, events, projects and news.',
	)
);
?>

	<section class="section">
		<div class="container container-narrow">
			<form action="<?php echo esc_url( epic_page_url( 'search' ) ); ?>" method="get" class="subscribe-inline" style="margin-bottom:34px">
				<input class="form-control" type="search" name="q" value="<?php echo esc_attr( $epic_term ); ?>" placeholder="Search…" aria-label="Search" style="flex:1 1 240px">
				<button class="btn btn-primary" type="submit"><?php epic_the_icon( 'search' ); ?> Search</button>
			</form>

			<?php if ( '' === $epic_term ) : ?>
				<p class="text-muted">Enter a search term above to begin.</p>
			<?php elseif ( ! $results ) : ?>
				<?php epic_empty_state( 'No results found', 'search', '<p style="margin:0">We could not find anything for &ldquo;' . esc_html( $epic_term ) . '&rdquo;. Try a different keyword, or <a href="' . esc_url( epic_page_url( 'publications' ) ) . '">browse our publications</a>.</p>' ); ?>
			<?php else : ?>
				<p class="text-muted"><?php echo (int) count( $results ); ?> result<?php echo 1 === count( $results ) ? '' : 's'; ?> found.</p>
				<div class="grid" style="gap:14px;margin-top:20px">
					<?php foreach ( $results as $result ) : ?>
						<article class="card" style="padding:20px">
							<div class="card-meta" style="margin:0 0 8px">
								<span class="badge badge-blue"><?php echo esc_html( $result['type'] ); ?></span>
								<?php if ( $result['date'] ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $result['date']->format( 'M Y' ) ); ?></span><?php endif; ?>
							</div>
							<h3 style="font-size:1.12rem;margin-bottom:6px"><a href="<?php echo esc_url( $result['url'] ); ?>"><?php echo esc_html( $result['title'] ); ?></a></h3>
							<p style="font-size:.92rem;margin:0"><?php echo esc_html( $result['summary'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

<?php
get_footer();
