<?php
/**
 * A single vacancy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$career = Epic_Item::from_post( get_queried_object() );
$others = Epic_Data::careers( true, 4, $career );
epic_trail( array( 'Get Involved' => null, 'Careers' => epic_page_url( 'careers' ), $career->title => null ) );

get_header();
epic_hero(
	array(
		'title'    => $career->title,
		'subtitle' => $career->excerpt,
		'eyebrow'  => $career->type,
	)
);
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div>
					<div class="prose"><?php echo epic_content( $career->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

					<?php if ( trim( (string) $career->requirements ) ) : ?>
						<h2 class="section-title mt-4" style="font-size:1.4rem">What we are looking for</h2>
						<div class="prose"><?php echo epic_rich( $career->requirements ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
				</div>

				<aside class="sidebar">
					<div class="sidebar-box">
						<h4>Role summary</h4>
						<ul class="contact-lines" style="gap:14px">
							<li><?php epic_the_icon( 'briefcase' ); ?><div><strong>Type</strong><span><?php echo esc_html( $career->type ); ?></span></div></li>
							<?php if ( $career->location ) : ?>
								<li><?php epic_the_icon( 'location' ); ?><div><strong>Location</strong><span><?php echo esc_html( $career->location ); ?></span></div></li>
							<?php endif; ?>
							<?php if ( $career->deadline ) : ?>
								<li><?php epic_the_icon( 'clock' ); ?><div><strong>Deadline</strong><span><?php echo esc_html( $career->deadline->format( 'd M Y' ) ); ?></span></div></li>
							<?php endif; ?>
						</ul>

						<?php if ( $career->is_open ) : ?>
							<?php if ( $career->apply_url ) : ?>
								<a class="btn btn-green btn-block mt-3" href="<?php echo esc_url( $career->apply_url ); ?>" target="_blank" rel="noopener">Apply now</a>
							<?php elseif ( $career->apply_email ) : ?>
								<a class="btn btn-green btn-block mt-3" href="mailto:<?php echo esc_attr( $career->apply_email ); ?>?subject=<?php echo rawurlencode( 'Application: ' . $career->title ); ?>">Apply by email</a>
							<?php else : ?>
								<a class="btn btn-green btn-block mt-3" href="<?php echo esc_url( epic_page_url( 'contact' ) ); ?>">Apply</a>
							<?php endif; ?>
						<?php else : ?>
							<p class="text-muted mt-3" style="font-size:.88rem">This position is now closed.</p>
						<?php endif; ?>
					</div>

					<?php if ( $others ) : ?>
						<div class="sidebar-box">
							<h4>Other opportunities</h4>
							<ul>
								<?php foreach ( $others as $item ) : ?>
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
