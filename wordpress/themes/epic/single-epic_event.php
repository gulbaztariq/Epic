<?php
/**
 * A single event.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$event   = Epic_Item::from_post( get_queried_object() );
$related = Epic_Data::other_events( $event, 3 );
epic_trail( array( 'Events' => epic_page_url( 'events' ), $event->title => null ) );

get_header();
epic_hero(
	array(
		'title'    => $event->title,
		'subtitle' => $event->excerpt,
		'eyebrow'  => $event->event_type,
	)
);
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div>
					<div class="detail-picture <?php echo epic_pic_adjusted( $event->image ) ? 'is-framed' : ''; ?>">
						<img src="<?php echo esc_url( epic_image( $event->image, 'wide' ) ); ?>" alt="<?php echo esc_attr( $event->title ); ?>"<?php echo epic_pic_style( $event->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					</div>

					<div class="meta-row">
						<?php if ( $event->starts_at ) : ?>
							<span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $event->starts_at->format( 'l, d F Y' ) ); ?></span>
							<span><?php epic_the_icon( 'clock' ); ?><?php echo esc_html( $event->starts_at->format( 'H:i' ) ); ?><?php echo $event->ends_at ? ' &ndash; ' . esc_html( $event->ends_at->format( 'H:i' ) ) : ''; ?></span>
						<?php endif; ?>
						<?php if ( $event->city || $event->location ) : ?>
							<span><?php epic_the_icon( 'location' ); ?><?php echo esc_html( $event->location ?: $event->city ); ?></span>
						<?php endif; ?>
						<span><?php epic_the_icon( 'people' ); ?><?php echo esc_html( $event->mode ); ?></span>
					</div>

					<div class="prose"><?php echo epic_content( $event->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

					<?php if ( $event->registration_url && $event->is_upcoming ) : ?>
						<a class="btn btn-green btn-lg mt-4" href="<?php echo esc_url( $event->registration_url ); ?>" target="_blank" rel="noopener">
							Register to attend <?php epic_the_icon( 'arrow-right' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<aside class="sidebar">
					<div class="sidebar-box">
						<h4>Event details</h4>
						<ul class="contact-lines" style="gap:14px">
							<?php if ( $event->starts_at ) : ?>
								<li><?php epic_the_icon( 'calendar' ); ?><div><strong>Date</strong><span><?php echo esc_html( $event->starts_at->format( 'd M Y' ) ); ?></span></div></li>
							<?php endif; ?>
							<?php if ( $event->location || $event->city ) : ?>
								<li><?php epic_the_icon( 'location' ); ?><div><strong>Venue</strong><span><?php echo esc_html( $event->location ?: $event->city ); ?></span></div></li>
							<?php endif; ?>
							<li><?php epic_the_icon( 'monitor' ); ?><div><strong>Format</strong><span><?php echo esc_html( $event->mode ); ?></span></div></li>
							<?php if ( $event->event_type ) : ?>
								<li><?php epic_the_icon( 'layers' ); ?><div><strong>Type</strong><span><?php echo esc_html( $event->event_type ); ?></span></div></li>
							<?php endif; ?>
						</ul>
						<?php if ( $event->registration_url && $event->is_upcoming ) : ?>
							<a class="btn btn-primary btn-block mt-3" href="<?php echo esc_url( $event->registration_url ); ?>" target="_blank" rel="noopener">Register</a>
						<?php else : ?>
							<a class="btn btn-outline btn-block mt-3" href="<?php echo esc_url( epic_page_url( 'contact' ) ); ?>">Enquire about this event</a>
						<?php endif; ?>
					</div>

					<?php if ( $related ) : ?>
						<div class="sidebar-box">
							<h4>More events</h4>
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
