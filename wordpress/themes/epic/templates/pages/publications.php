<?php
/**
 * Publications → Our Collection.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page             = Epic_Data::current_page();
$active_type      = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$publications     = Epic_Data::publications( 'collection', $active_type ?: null, 9 );
$types            = Epic_Data::publication_types();
$collection_types = Epic_Data::list_items( 'publication_types' );
epic_trail( array( 'Publications' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $types ) : ?>
				<div class="chip-row mt-4" style="margin-bottom:30px">
					<a class="chip <?php echo ! $active_type ? 'is-active' : ''; ?>" href="<?php echo esc_url( epic_page_url( 'publications' ) ); ?>">All</a>
					<?php foreach ( $types as $type ) : ?>
						<a class="chip <?php echo $active_type === $type ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'type', rawurlencode( $type ), epic_page_url( 'publications' ) ) ); ?>"><?php echo esc_html( $type ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $publications->items ) : ?>
				<div class="grid grid-auto-sm">
					<?php foreach ( $publications->items as $publication ) { epic_card_publication( $publication ); } ?>
				</div>
				<?php epic_pagination( $publications ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Publications coming soon', 'document', '<p style="margin:0">EPIC research reports, policy briefs and working papers will appear here.</p>' ); ?>
			<?php endif; ?>

			<?php if ( $collection_types ) : ?>
				<div style="margin-top:56px">
					<h2 class="section-title" style="font-size:1.45rem">Our collection includes</h2>
					<?php epic_check_list( $collection_types, 3 ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
