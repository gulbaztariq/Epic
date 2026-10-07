<?php
/**
 * Who We Are → Vision & Mission.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page = Epic_Data::current_page();
epic_trail( array( 'Who We Are' => epic_page_url( 'about-us' ), $epic_page->title => null ) );

$vision  = $epic_page->section( 'vision' );
$mission = $epic_page->section( 'mission' );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead text-center" style="max-width:70ch;margin-inline:auto"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<div class="grid grid-2 mt-4">
				<article class="card" style="padding:34px">
					<div class="principle-icon" style="width:60px;height:60px;margin-bottom:18px"><?php epic_the_icon( 'eye' ); ?></div>
					<h2 style="font-size:1.75rem"><?php echo esc_html( $vision->title ?: 'Vision' ); ?></h2>
					<div class="prose" style="font-size:1.03rem"><?php echo epic_content( $vision->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</article>

				<article class="card" style="padding:34px">
					<div class="principle-icon" style="width:60px;height:60px;margin-bottom:18px;background:#e9f6e4;color:var(--green-700)"><?php epic_the_icon( 'target' ); ?></div>
					<h2 style="font-size:1.75rem"><?php echo esc_html( $mission->title ?: 'Mission' ); ?></h2>
					<div class="prose" style="font-size:1.03rem"><?php echo epic_content( $mission->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</article>
			</div>

			<?php if ( trim( (string) $epic_page->body ) ) : ?>
				<div class="prose mt-4" style="max-width:80ch;margin-inline:auto"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $epic_page->quote ) : ?>
		<section class="section section-soft">
			<div class="container container-narrow text-center">
				<blockquote style="border:0;font-size:1.55rem;padding:0;margin:0">&ldquo;<?php echo esc_html( $epic_page->quote ); ?>&rdquo;</blockquote>
				<?php if ( $epic_page->quote_author ) : ?><p class="text-muted mt-3"><?php echo esc_html( $epic_page->quote_author ); ?></p><?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php epic_sections( $epic_page ); ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
