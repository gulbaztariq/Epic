<?php
/**
 * Who We Are → EPIC Principles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page       = Epic_Data::current_page();
$principles = Epic_Data::list_items( 'principles' );
epic_trail( array( 'Who We Are' => epic_page_url( 'about-us' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:74ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $principles ) : ?>
				<div class="principle-list mt-4">
					<?php foreach ( $principles as $principle ) : ?>
						<article class="principle">
							<div class="principle-icon"><?php epic_the_icon( $principle->icon ?: 'check' ); ?></div>
							<div>
								<h3><?php echo esc_html( $principle->title ); ?></h3>
								<p><?php echo esc_html( $principle->description ); ?></p>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php epic_empty_state( 'Principles coming soon', 'sparkle', 'Our guiding principles will be published here shortly.' ); ?>
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
			'title'           => 'See these principles in practice',
			'text'            => "Explore the research, projects and dialogues that put EPIC's principles to work.",
			'primary_label'   => 'Our publications',
			'primary_url'     => epic_page_url( 'publications' ),
			'secondary_label' => 'What we do',
			'secondary_url'   => epic_page_url( 'themes' ),
		)
	);

get_footer();
