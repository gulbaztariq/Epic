<?php
/**
 * Events: upcoming and past.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page   = Epic_Data::current_page();
$filter = ( isset( $_GET['show'] ) && 'past' === $_GET['show'] ) ? 'past' : 'upcoming'; // phpcs:ignore WordPress.Security.NonceVerification
$events = 'past' === $filter ? Epic_Data::events_past( 9 ) : Epic_Data::events_upcoming( 9 );
$event_types    = Epic_Data::list_items( 'event_types' );
$upcoming_count = Epic_Data::count_events( true );
$past_count     = Epic_Data::count_events( false );
epic_trail( array( $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<div class="chip-row mt-4" style="margin-bottom:28px">
				<a class="chip <?php echo 'upcoming' === $filter ? 'is-active' : ''; ?>" href="<?php echo esc_url( epic_page_url( 'events' ) ); ?>">
					<?php epic_the_icon( 'calendar' ); ?> Upcoming (<?php echo (int) $upcoming_count; ?>)
				</a>
				<a class="chip <?php echo 'past' === $filter ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'show', 'past', epic_page_url( 'events' ) ) ); ?>">
					<?php epic_the_icon( 'clock' ); ?> Past events (<?php echo (int) $past_count; ?>)
				</a>
			</div>

			<?php if ( $events->items ) : ?>
				<div class="grid grid-3">
					<?php foreach ( $events->items as $event ) { epic_card_event( $event ); } ?>
				</div>
				<?php epic_pagination( $events ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'past' === $filter ? 'No past events listed yet' : 'No upcoming events scheduled', 'calendar', '<p style="margin:0">Subscribe to hear first about EPIC policy dialogues, roundtables, seminars and webinars.</p>' ); ?>
			<?php endif; ?>

			<?php if ( $event_types ) : ?>
				<div style="margin-top:56px">
					<h2 class="section-title" style="font-size:1.45rem">Our events include</h2>
					<?php epic_check_list( $event_types, 3 ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php epic_subscribe_band(); ?>

<?php
get_footer();
