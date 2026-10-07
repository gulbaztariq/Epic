<?php
/**
 * Media → YouTube.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page     = Epic_Data::current_page();
$videos   = Epic_Data::paginate( 'epic_video', array(), 9 );
$featured = Epic_Data::featured_video() ?: ( $videos->items[0] ?? null );
epic_trail( array( 'Media' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?><p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p><?php endif; ?>

			<?php if ( $featured && $featured->embed_url ) : ?>
				<div class="video-embed mt-4" style="margin-bottom:34px">
					<iframe src="<?php echo esc_url( $featured->embed_url ); ?>" title="<?php echo esc_attr( $featured->title ); ?>" loading="lazy"
						allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
				</div>
				<h2 class="section-title" style="font-size:1.35rem"><?php echo esc_html( $featured->title ); ?></h2>
				<?php if ( $featured->description ) : ?><p style="max-width:76ch"><?php echo esc_html( $featured->description ); ?></p><?php endif; ?>
			<?php endif; ?>

			<?php if ( $videos->items ) : ?>
				<div class="grid grid-3" style="margin-top:34px">
					<?php foreach ( $videos->items as $video ) : ?>
						<article class="card">
							<a class="video-thumb" href="<?php echo esc_url( (string) $video->watch_url ); ?>" target="_blank" rel="noopener">
								<img src="<?php echo esc_url( $video->poster ?: epic_image( null, 'card' ) ); ?>" alt="<?php echo esc_attr( $video->title ); ?>" loading="lazy"<?php echo epic_pic_style( $video->thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
								<span class="play"><?php epic_the_icon( 'play' ); ?></span>
							</a>
							<div class="card-body">
								<h3 style="font-size:1.02rem"><a href="<?php echo esc_url( (string) $video->watch_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $video->title ); ?></a></h3>
								<?php if ( $video->description ) : ?><p><?php echo esc_html( epic_summarise( $video->description, 110 ) ); ?></p><?php endif; ?>
								<?php if ( $video->published_at ) : ?>
									<div class="card-meta"><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $video->published_at->format( 'd M Y' ) ); ?></span></div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<?php epic_pagination( $videos ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Videos coming soon', 'youtube', '<p style="margin:0">Recordings of EPIC dialogues, seminars and expert conversations will be published here.</p>' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

<?php
get_footer();
