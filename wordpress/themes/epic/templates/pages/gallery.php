<?php
/**
 * Media → Gallery.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page   = Epic_Data::current_page();
$albums = Epic_Data::paginate( 'epic_album', array(), 12 );
epic_trail( array( 'Media' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?><p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p><?php endif; ?>

			<?php if ( $albums->items ) : ?>
				<div class="grid grid-3 mt-4">
					<?php foreach ( $albums->items as $album ) : ?>
						<article class="card">
							<a class="card-media is-wide" href="<?php echo esc_url( $album->url ); ?>">
								<img src="<?php echo esc_url( epic_image( $album->cover, 'card' ) ); ?>" alt="<?php echo esc_attr( $album->title ); ?>" loading="lazy"<?php echo epic_pic_style( $album->cover ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
								<span class="badge badge-navy"><?php echo (int) count( $album->images ); ?> photos</span>
							</a>
							<div class="card-body">
								<h3><a href="<?php echo esc_url( $album->url ); ?>"><?php echo esc_html( $album->title ); ?></a></h3>
								<?php if ( $album->description ) : ?><p><?php echo esc_html( epic_summarise( $album->description, 110 ) ); ?></p><?php endif; ?>
								<div class="card-meta">
									<?php if ( $album->event_date ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $album->event_date->format( 'd M Y' ) ); ?></span><?php endif; ?>
									<?php if ( $album->location ) : ?><span><?php epic_the_icon( 'location' ); ?><?php echo esc_html( $album->location ); ?></span><?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<?php epic_pagination( $albums ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Photo albums coming soon', 'image', '<p style="margin:0">Photographs from EPIC dialogues, workshops, launches and field work.</p>' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

<?php
get_footer();
