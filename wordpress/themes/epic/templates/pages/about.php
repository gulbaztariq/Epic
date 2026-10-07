<?php
/**
 * Who We Are → About Us.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page = Epic_Data::current_page();
epic_trail( array( 'Who We Are' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<div class="split">
				<div>
					<?php if ( $epic_page->intro ) : ?>
						<p class="lead" style="color:var(--navy);font-family:var(--font-display);font-size:1.28rem;line-height:1.55"><?php echo esc_html( $epic_page->intro ); ?></p>
					<?php endif; ?>
					<div class="prose"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</div>
				<div class="split-media">
					<img src="<?php echo esc_url( $epic_page->hero_image ? $epic_page->hero_image->url : epic_asset( 'images/hero-islamabad.svg' ) ); ?>"
						alt="<?php echo esc_attr( $epic_page->title ); ?>" loading="lazy">
					<?php if ( $epic_page->quote ) : ?>
						<blockquote class="mt-4"><?php echo esc_html( $epic_page->quote ); ?>
							<?php if ( $epic_page->quote_author ) : ?><cite style="display:block;font-size:.85rem;font-style:normal;color:var(--muted);margin-top:8px"><?php echo esc_html( $epic_page->quote_author ); ?></cite><?php endif; ?>
						</blockquote>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="section section-soft">
		<div class="container">
			<div class="grid grid-3">
				<a class="card" href="<?php echo esc_url( epic_page_url( 'vision-mission' ) ); ?>" style="padding:26px">
					<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'target' ); ?></div>
					<h3>Vision &amp; Mission</h3>
					<p style="font-size:.92rem">What EPIC is working towards, and how we get there.</p>
					<span class="section-link mt-3">Read more <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
				<a class="card" href="<?php echo esc_url( epic_page_url( 'epic-principles' ) ); ?>" style="padding:26px">
					<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'shield' ); ?></div>
					<h3>EPIC Principles</h3>
					<p style="font-size:.92rem">The values that guide our research, partnerships and policy engagement.</p>
					<span class="section-link mt-3">Read more <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
				<a class="card" href="<?php echo esc_url( epic_page_url( 'our-strengths' ) ); ?>" style="padding:26px">
					<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'star' ); ?></div>
					<h3>Our Strengths</h3>
					<p style="font-size:.92rem">The multidisciplinary capabilities we bring to every engagement.</p>
					<span class="section-link mt-3">Read more <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
			</div>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'           => 'Partner with EPIC',
			'text'            => 'We collaborate with universities, think tanks, government institutions, development partners and the private sector.',
			'primary_label'   => 'Get in touch',
			'secondary_label' => 'Our partnerships',
			'secondary_url'   => epic_page_url( 'partnerships' ),
		)
	);

get_footer();
