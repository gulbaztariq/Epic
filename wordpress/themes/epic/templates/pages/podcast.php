<?php
/**
 * Media → Podcast.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page     = Epic_Data::current_page();
$episodes = Epic_Data::paginate( 'epic_podcast', array(), 9 );
epic_trail( array( 'Media' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?><p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p><?php endif; ?>

			<?php if ( $episodes->items ) : ?>
				<div class="grid grid-3 mt-4">
					<?php foreach ( $episodes->items as $episode ) : ?>
						<article class="card">
							<a class="card-media is-wide" href="<?php echo esc_url( $episode->url ); ?>">
								<img src="<?php echo esc_url( epic_image( $episode->cover_image, 'card' ) ); ?>" alt="<?php echo esc_attr( $episode->title ); ?>" loading="lazy"<?php echo epic_pic_style( $episode->cover_image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
								<?php if ( $episode->episode_number ) : ?><span class="badge badge-navy">EP <?php echo esc_html( $episode->episode_number ); ?></span><?php endif; ?>
							</a>
							<div class="card-body">
								<h3><a href="<?php echo esc_url( $episode->url ); ?>"><?php echo esc_html( $episode->title ); ?></a></h3>
								<?php if ( $episode->guest ) : ?><p class="text-muted" style="font-size:.85rem;margin:0">With <?php echo esc_html( $episode->guest ); ?></p><?php endif; ?>
								<p><?php echo esc_html( epic_summarise( $episode->body, 120 ) ); ?></p>
								<div class="card-meta">
									<?php if ( $episode->published_at ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $episode->published_at->format( 'd M Y' ) ); ?></span><?php endif; ?>
									<?php if ( $episode->duration ) : ?><span><?php epic_the_icon( 'clock' ); ?><?php echo esc_html( $episode->duration ); ?></span><?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<?php epic_pagination( $episodes ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'The EPIC podcast is coming soon', 'mic', "<p style=\"margin:0\">Conversations with researchers, entrepreneurs and policymakers on the ideas shaping Pakistan's economy.</p>" ); ?>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
