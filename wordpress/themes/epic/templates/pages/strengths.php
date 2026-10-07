<?php
/**
 * Who We Are → Our Strengths.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page      = Epic_Data::current_page();
$strengths = Epic_Data::list_items( 'strengths' );
epic_trail( array( 'Who We Are' => epic_page_url( 'about-us' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:74ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<h2 class="section-title mt-4" style="font-size:1.5rem">Our key institutional strengths</h2>

			<?php if ( $strengths ) : ?>
				<?php epic_check_list( $strengths, 2 ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Content coming soon', 'sparkle', 'Institutional strengths will be listed here.' ); ?>
			<?php endif; ?>

			<?php if ( trim( (string) $epic_page->body ) ) : ?>
				<div class="prose mt-4"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'         => 'Looking for a research or delivery partner?',
			'text'          => 'EPIC combines research, policy engagement, academic expertise, entrepreneurship and implementation experience within one multidisciplinary platform.',
			'primary_label' => 'Talk to our team',
		)
	);

get_footer();
