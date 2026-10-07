<?php
/**
 * Publications → Blogs & Articles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page     = Epic_Data::current_page();
$category = ( isset( $_GET['category'] ) && in_array( $_GET['category'], array( 'blog', 'article' ), true ) ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$posts    = Epic_Data::posts( $category ? array( $category ) : array( 'blog', 'article' ), 9 );
epic_trail( array( 'Publications' => epic_page_url( 'publications' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<div class="chip-row mt-4" style="margin-bottom:28px">
				<a class="chip <?php echo ! $category ? 'is-active' : ''; ?>" href="<?php echo esc_url( epic_page_url( 'blogs' ) ); ?>">All</a>
				<a class="chip <?php echo 'blog' === $category ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'category', 'blog', epic_page_url( 'blogs' ) ) ); ?>">Blogs</a>
				<a class="chip <?php echo 'article' === $category ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'category', 'article', epic_page_url( 'blogs' ) ) ); ?>">Articles</a>
			</div>

			<?php if ( $posts->items ) : ?>
				<div class="grid grid-3">
					<?php foreach ( $posts->items as $post_item ) { epic_card_post( $post_item ); } ?>
				</div>
				<?php epic_pagination( $posts ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Blogs and articles coming soon', 'edit', '<p style="margin:0">Commentary and analysis from the EPIC team and our network of experts.</p>' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
