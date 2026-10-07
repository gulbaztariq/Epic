<?php
/**
 * Partnerships & MoUs → Partnerships.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page       = Epic_Data::current_page();
$partners   = Epic_Data::partners( 'partnership' );
$categories = Epic_Data::list_items( 'partner_types' );
epic_trail( array( 'Partnerships & MoUs' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $categories ) : ?>
				<h2 class="section-title mt-4" style="font-size:1.45rem">We work with</h2>
				<?php epic_check_list( $categories, 3 ); ?>
			<?php endif; ?>

			<?php if ( $partners ) : ?>
				<h2 class="section-title" style="font-size:1.45rem;margin-top:52px">Our partners</h2>
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
								<?php epic_the_icon( 'partnership', 'icon', '1.5' ); ?>
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

	<section class="section section-soft">
		<div class="container">
			<div class="grid grid-2">
				<a class="card" href="<?php echo esc_url( epic_page_url( 'mous' ) ); ?>" style="padding:28px">
					<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'document' ); ?></div>
					<h3>Memoranda of Understanding</h3>
					<p style="font-size:.93rem">Strategic MoUs for joint research, student engagement, co-publication and capacity building.</p>
					<span class="section-link mt-3">View MoUs <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
				<a class="card" href="<?php echo esc_url( epic_page_url( 'memberships' ) ); ?>" style="padding:28px">
					<div class="principle-icon" style="margin-bottom:14px;background:#e9f6e4;color:var(--green-700)"><?php epic_the_icon( 'network' ); ?></div>
					<h3>Memberships</h3>
					<p style="font-size:.93rem">The national and international networks EPIC participates in.</p>
					<span class="section-link mt-3">View memberships <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
			</div>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'         => 'Propose a partnership',
			'text'          => 'EPIC believes sustainable impact is built through collaboration. Tell us what you would like to build together.',
			'primary_label' => 'Contact our partnerships team',
		)
	);

get_footer();
