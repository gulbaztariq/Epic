<?php
/**
 * A page an editor created: header, introduction, main content and any sections.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page = Epic_Data::current_page();
epic_trail( array( $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<?php if ( $epic_page->intro || trim( (string) $epic_page->body ) ) : ?>
		<section class="section">
			<div class="container container-narrow">
				<?php if ( $epic_page->intro ) : ?><p class="lead"><?php echo esc_html( $epic_page->intro ); ?></p><?php endif; ?>
				<div class="prose"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
		</section>
	<?php endif; ?>

	<?php epic_sections( $epic_page ); ?>

<?php
get_footer();
