<?php
/**
 * What We Do → Themes of EPIC Work.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page   = Epic_Data::current_page();
$themes = Epic_Data::list_items( 'themes' );
epic_trail( array( 'What We Do' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $themes ) : ?>
				<div class="grid grid-2 mt-4">
					<?php foreach ( $themes as $theme ) : ?>
						<article class="principle">
							<div class="principle-icon"><?php epic_the_icon( $theme->icon ?: 'layers' ); ?></div>
							<div>
								<h3><?php echo esc_html( $theme->title ); ?></h3>
								<p><?php echo esc_html( $theme->description ); ?></p>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php epic_empty_state( 'Thematic areas coming soon' ); ?>
			<?php endif; ?>

			<?php if ( trim( (string) $epic_page->body ) ) : ?>
				<div class="prose mt-4"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</section>

	<section class="section section-soft">
		<div class="container">
			<div class="grid grid-3">
				<a class="card" href="<?php echo esc_url( epic_page_url( 'projects' ) ); ?>" style="padding:26px">
					<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'briefcase' ); ?></div>
					<h3>Projects</h3>
					<p style="font-size:.92rem">Research, policy, capacity-building and development projects.</p>
					<span class="section-link mt-3">View projects <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
				<a class="card" href="<?php echo esc_url( epic_page_url( 'international-chapters' ) ); ?>" style="padding:26px">
					<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'globe' ); ?></div>
					<h3>International Chapters</h3>
					<p style="font-size:.92rem">Our growing global network of chapters and collaborators.</p>
					<span class="section-link mt-3">Explore chapters <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
				<a class="card" href="<?php echo esc_url( epic_page_url( 'publications' ) ); ?>" style="padding:26px">
					<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'document' ); ?></div>
					<h3>Publications</h3>
					<p style="font-size:.92rem">Reports, policy briefs, working papers and journal articles.</p>
					<span class="section-link mt-3">Read our research <?php epic_the_icon( 'arrow-right' ); ?></span>
				</a>
			</div>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
