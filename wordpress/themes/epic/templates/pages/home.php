<?php
/**
 * Home page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page         = Epic_Data::current_page();
$focus_areas  = Epic_Data::focus_areas();
$pillars      = Epic_Data::list_items( 'entrepreneurship_pillars' );
$stats        = Epic_Data::stats();
$publications = Epic_Data::featured_publications( 5 );
$events       = Epic_Data::home_events( 3 );

$hero_lines = preg_split( '/\r\n|\r|\n/', trim( (string) ( $epic_page->hero_title ?: 'Evidence. Innovation. Opportunity.' ) ) );
$hero_lines = array_values( array_filter( array_map( 'trim', $hero_lines ), 'strlen' ) );
$last_line  = count( $hero_lines ) - 1;

get_header();
?>

	<?php /* ---------------------------------------------------------------- Hero */ ?>
	<section class="hero">
		<div class="container">
			<div class="hero-grid">
				<div class="hero-copy">
					<?php if ( $epic_page->eyebrow ) : ?>
						<p class="eyebrow"><?php echo esc_html( $epic_page->eyebrow ); ?></p>
					<?php endif; ?>

					<h1 class="hero-title">
						<?php foreach ( $hero_lines as $i => $line ) : ?>
							<span class="<?php echo ( $i === $last_line && count( $hero_lines ) > 1 ) ? 'accent' : ''; ?>"><?php echo esc_html( $line ); ?></span>
						<?php endforeach; ?>
					</h1>

					<?php if ( $epic_page->hero_subtitle ) : ?>
						<p class="hero-lead"><?php echo esc_html( $epic_page->hero_subtitle ); ?></p>
					<?php endif; ?>

					<div class="hero-actions">
						<a class="btn btn-primary btn-lg" href="<?php echo esc_url( $epic_page->cta_url ? epic_resolved_url( $epic_page->cta_url ) : epic_page_url( 'publications' ) ); ?>">
							<?php echo esc_html( $epic_page->cta_text ?: 'Explore Our Research' ); ?> <?php epic_the_icon( 'arrow-right' ); ?>
						</a>
						<a class="btn btn-outline btn-lg" href="<?php echo esc_url( epic_setting( 'home_cta2_url' ) ? epic_resolved_url( epic_setting( 'home_cta2_url' ) ) : epic_page_url( 'about-us' ) ); ?>">
							<?php echo esc_html( epic_setting( 'home_cta2_label', 'About EPIC' ) ); ?>
						</a>
					</div>
				</div>

				<div class="hero-media">
					<div class="hero-media-frame">
						<img src="<?php echo esc_url( $epic_page->hero_image ? $epic_page->hero_image->url : epic_asset( 'images/hero-islamabad.svg' ) ); ?>"
							alt="<?php echo esc_attr( epic_setting( 'site_name', 'EPIC' ) ); ?>" width="1200" height="750"<?php echo epic_pic_style( $epic_page->hero_image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					</div>

					<?php if ( $epic_page->quote ) : ?>
						<figure class="hero-quote">
							<p>&ldquo;<?php echo esc_html( $epic_page->quote ); ?>&rdquo;</p>
							<?php if ( $epic_page->quote_author ) : ?>
								<cite><?php echo esc_html( $epic_page->quote_author ); ?></cite>
							<?php endif; ?>
						</figure>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<?php /* -------------------------------------------------------- Focus areas */ ?>
	<?php if ( $focus_areas ) : ?>
		<?php $focus_section = $epic_page->section( 'focus' ); ?>
		<section class="section focus-strip">
			<div class="container">
				<div class="section-head">
					<h2 class="section-title is-plain"><?php echo esc_html( $focus_section->title ?: 'Our Focus Areas' ); ?></h2>
					<?php if ( $focus_section->link_text ) : ?>
						<a class="section-link" href="<?php echo esc_url( $focus_section->link_url ? epic_resolved_url( $focus_section->link_url ) : epic_page_url( 'themes' ) ); ?>">
							<?php echo esc_html( $focus_section->link_text ); ?> <?php epic_the_icon( 'arrow-right' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="focus-grid reveal">
					<?php foreach ( $focus_areas as $area ) : ?>
						<a class="focus-item <?php echo 'green' === $area->color ? 'is-green' : ''; ?>" href="<?php echo esc_url( $area->url ? epic_resolved_url( $area->url ) : epic_page_url( 'themes' ) ); ?>">
							<?php epic_the_icon( $area->icon ); ?>
							<h3><?php echo esc_html( $area->title ); ?></h3>
							<p><?php echo esc_html( $area->description ); ?></p>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ------------------------------------------------ Featured publications */ ?>
	<?php if ( $publications ) : ?>
		<?php $pub_section = $epic_page->section( 'publications' ); ?>
		<section class="section">
			<div class="container">
				<div class="section-head">
					<h2 class="section-title is-plain"><?php echo esc_html( $pub_section->title ?: 'Featured Publications' ); ?></h2>
					<a class="section-link" href="<?php echo esc_url( $pub_section->link_url ? epic_resolved_url( $pub_section->link_url ) : epic_page_url( 'publications' ) ); ?>">
						<?php echo esc_html( $pub_section->link_text ?: 'View All Publications' ); ?> <?php epic_the_icon( 'arrow-right' ); ?>
					</a>
				</div>

				<div class="grid grid-auto-sm reveal">
					<?php foreach ( $publications as $publication ) { epic_card_publication( $publication ); } ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ------------------------------------------------------------- Events */ ?>
	<?php if ( $events ) : ?>
		<?php $event_section = $epic_page->section( 'events' ); ?>
		<section class="section section-soft">
			<div class="container">
				<div class="section-head">
					<h2 class="section-title is-plain"><?php echo esc_html( $event_section->title ?: 'Upcoming Events & Dialogues' ); ?></h2>
					<a class="section-link" href="<?php echo esc_url( $event_section->link_url ? epic_resolved_url( $event_section->link_url ) : epic_page_url( 'events' ) ); ?>">
						<?php echo esc_html( $event_section->link_text ?: 'View All Events' ); ?> <?php epic_the_icon( 'arrow-right' ); ?>
					</a>
				</div>

				<div class="grid grid-3 reveal">
					<?php foreach ( $events as $event ) { epic_card_event( $event ); } ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* --------------------------------------------------- Entrepreneurship */ ?>
	<?php $band = $epic_page->section( 'entrepreneurship' ); ?>
	<?php if ( $band->title || $pillars ) : ?>
		<section class="section feature-band">
			<div class="container">
				<div class="feature-grid">
					<div>
						<h2 class="section-title is-plain" style="display:block"><?php echo epic_nl2br( $band->title ?: 'Entrepreneurship for a Brighter Pakistan' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
						<p style="margin-top:14px"><?php echo esc_html( epic_plain( $band->body ) ?: 'We support evidence-based policies, partnerships and programmes that enable entrepreneurs, scale innovation, develop human capital and create quality jobs across Pakistan.' ); ?></p>
						<a class="btn btn-primary mt-3" href="<?php echo esc_url( $band->link_url ? epic_resolved_url( $band->link_url ) : epic_page_url( 'themes' ) ); ?>">
							<?php echo esc_html( $band->link_text ?: 'Our Entrepreneurship Agenda' ); ?> <?php epic_the_icon( 'arrow-right' ); ?>
						</a>
					</div>

					<?php if ( $pillars ) : ?>
						<div class="pillars">
							<?php foreach ( $pillars as $pillar ) : ?>
								<div class="pillar">
									<?php epic_the_icon( $pillar->icon ?: 'rocket' ); ?>
									<h4><?php echo esc_html( $pillar->title ); ?></h4>
									<p><?php echo esc_html( $pillar->description ); ?></p>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( $band->subheading ) : ?>
						<blockquote class="pull-quote" style="margin:0"><?php echo esc_html( $band->subheading ); ?></blockquote>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* -------------------------------------------------------- Data & insights */ ?>
	<?php if ( $stats ) : ?>
		<?php $stat_section = $epic_page->section( 'stats' ); ?>
		<section class="section">
			<div class="container">
				<div class="section-head">
					<h2 class="section-title is-plain"><?php echo esc_html( $stat_section->title ?: 'Data & Insights' ); ?></h2>
					<a class="section-link" href="<?php echo esc_url( $stat_section->link_url ? epic_resolved_url( $stat_section->link_url ) : epic_page_url( 'publications' ) ); ?>">
						<?php echo esc_html( $stat_section->link_text ?: 'Explore More Insights' ); ?> <?php epic_the_icon( 'arrow-right' ); ?>
					</a>
				</div>

				<div class="stats-grid reveal">
					<?php foreach ( $stats as $stat ) : ?>
						<div class="stat-card">
							<span class="stat-label"><?php echo esc_html( $stat->title ); ?></span>
							<div class="stat-row">
								<span class="stat-value"><?php echo esc_html( $stat->value ); ?></span>
								<?php epic_the_icon( $stat->icon ); ?>
							</div>
							<span class="stat-caption"><?php echo esc_html( $stat->caption ); ?></span>
						</div>
					<?php endforeach; ?>

					<?php if ( epic_plain( $stat_section->body ) ) : ?>
						<p class="stats-note"><?php echo esc_html( epic_plain( $stat_section->body ) ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
