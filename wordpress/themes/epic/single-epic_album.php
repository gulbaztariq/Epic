<?php
/**
 * A photo album.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$album = Epic_Item::from_post( get_queried_object() );
$epic_more  = Epic_Data::other_albums( $album, 3 );
epic_trail( array( 'Media' => null, 'Gallery' => epic_page_url( 'gallery' ), $album->title => null ) );

get_header();
epic_hero(
	array(
		'title'    => $album->title,
		'subtitle' => $album->description,
		'eyebrow'  => $album->event_date ? $album->event_date->format( 'd F Y' ) : '',
	)
);
?>

	<section class="section">
		<div class="container">
			<?php if ( $album->images ) : ?>
				<div class="gallery-grid">
					<?php foreach ( $album->images as $image ) : ?>
						<figure class="gallery-item" data-lightbox="<?php echo esc_url( $image->url ); ?>" style="cursor:zoom-in">
							<img src="<?php echo esc_url( $image->url ); ?>" alt="<?php echo esc_attr( $image->caption ?: $album->title ); ?>" loading="lazy"<?php echo $image->style(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<?php if ( $image->caption ) : ?><figcaption><?php echo esc_html( $image->caption ); ?></figcaption><?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php epic_empty_state( 'Photos coming soon', 'image' ); ?>
			<?php endif; ?>

			<?php if ( $epic_more ) : ?>
				<div style="margin-top:56px">
					<h2 class="section-title" style="font-size:1.4rem">More albums</h2>
					<div class="grid grid-3">
						<?php foreach ( $epic_more as $item ) : ?>
							<article class="card">
								<a class="card-media is-wide" href="<?php echo esc_url( $item->url ); ?>">
									<img src="<?php echo esc_url( epic_image( $item->cover, 'card' ) ); ?>" alt="<?php echo esc_attr( $item->title ); ?>" loading="lazy"<?php echo epic_pic_style( $item->cover ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
								</a>
								<div class="card-body"><h3><a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a></h3></div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>

<?php
get_footer();
