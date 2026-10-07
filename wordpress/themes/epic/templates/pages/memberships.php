<?php
/**
 * Partnerships & MoUs → Memberships.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page     = Epic_Data::current_page();
$partners = Epic_Data::partners( 'membership' );
$networks = Epic_Data::list_items( 'memberships' );
epic_trail( array( 'Partnerships & MoUs' => epic_page_url( 'partnerships' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $networks ) : ?>
				<h2 class="section-title mt-4" style="font-size:1.45rem">Networks we engage with</h2>
				<?php epic_check_list( $networks, 3 ); ?>
			<?php endif; ?>

			<?php if ( $partners ) : ?>
				<h2 class="section-title" style="font-size:1.45rem;margin-top:52px">Our memberships</h2>
				<div class="logo-grid">
					<?php foreach ( $partners as $partner ) : ?>
						<?php if ( $partner->website ) : ?>
							<a class="logo-card" href="<?php echo esc_url( $partner->website ); ?>" target="_blank" rel="noopener">
						<?php else : ?>
							<div class="logo-card">
						<?php endif; ?>
							<?php if ( $partner->logo ) : ?>
								<img src="<?php echo esc_url( $partner->logo->url ); ?>" alt="<?php echo esc_attr( $partner->title ); ?>" loading="lazy">
							<?php else : ?>
								<?php epic_the_icon( 'network', 'icon', '1.5' ); ?>
							<?php endif; ?>
							<span class="logo-name"><?php echo esc_html( $partner->title ); ?></span>
							<?php if ( $partner->category ) : ?><span class="logo-cat"><?php echo esc_html( $partner->category ); ?></span><?php endif; ?>
						<?php echo $partner->website ? '</a>' : '</div>'; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( trim( (string) $epic_page->body ) ) : ?>
				<div class="prose mt-4"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
