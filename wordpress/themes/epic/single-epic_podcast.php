<?php
/**
 * A single podcast episode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$episode = Epic_Item::from_post( get_queried_object() );
$related = Epic_Data::other_podcasts( $episode, 4 );
epic_trail( array( 'Media' => null, 'Podcast' => epic_page_url( 'podcast' ), $episode->title => null ) );

get_header();
epic_hero(
	array(
		'title'   => $episode->title,
		'eyebrow' => $episode->episode_number ? 'Episode ' . $episode->episode_number : 'EPIC Podcast',
	)
);
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div>
					<?php if ( $episode->embed_url ) : ?>
						<div class="video-embed" style="aspect-ratio:auto;min-height:180px;background:var(--bg-soft)">
							<iframe src="<?php echo esc_url( $episode->embed_url ); ?>" title="<?php echo esc_attr( $episode->title ); ?>" loading="lazy" allow="autoplay; clipboard-write; encrypted-media"></iframe>
						</div>
					<?php elseif ( $episode->audio_url ) : ?>
						<audio controls style="width:100%" src="<?php echo esc_url( $episode->audio_url ); ?>">Your browser does not support audio playback.</audio>
					<?php else : ?>
						<div class="detail-picture <?php echo epic_pic_adjusted( $episode->cover_image ) ? 'is-framed' : ''; ?>" style="margin-bottom:0">
							<img src="<?php echo esc_url( epic_image( $episode->cover_image, 'wide' ) ); ?>" alt="<?php echo esc_attr( $episode->title ); ?>"<?php echo epic_pic_style( $episode->cover_image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						</div>
					<?php endif; ?>

					<div class="meta-row mt-4">
						<?php if ( $episode->guest ) : ?><span><?php epic_the_icon( 'people' ); ?><?php echo esc_html( $episode->guest ); ?></span><?php endif; ?>
						<?php if ( $episode->published_at ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $episode->published_at->format( 'd F Y' ) ); ?></span><?php endif; ?>
						<?php if ( $episode->duration ) : ?><span><?php epic_the_icon( 'clock' ); ?><?php echo esc_html( $episode->duration ); ?></span><?php endif; ?>
					</div>

					<div class="prose"><?php echo epic_content( $episode->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</div>

				<aside class="sidebar">
					<?php if ( $related ) : ?>
						<div class="sidebar-box">
							<h4>More episodes</h4>
							<ul>
								<?php foreach ( $related as $item ) : ?>
									<li><a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</aside>
			</div>
		</div>
	</section>

<?php
get_footer();
