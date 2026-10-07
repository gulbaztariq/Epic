<?php
/**
 * Get Involved → Careers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page   = Epic_Data::current_page();
$open   = Epic_Data::careers( true );
$closed = Epic_Data::careers( false, 6 );
$careers_email = epic_setting( 'careers_email', epic_setting( 'contact_email', 'info@epic.org.pk' ) );
epic_trail( array( 'Get Involved' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $open ) : ?>
				<div class="grid mt-4" style="gap:18px">
					<?php foreach ( $open as $career ) : ?>
						<article class="list-card is-wide" style="grid-template-columns:1fr auto;align-items:center">
							<div class="list-body">
								<div class="card-meta" style="margin:0">
									<span class="badge badge-green"><?php echo esc_html( $career->type ); ?></span>
									<?php if ( $career->location ) : ?><span><?php epic_the_icon( 'location' ); ?><?php echo esc_html( $career->location ); ?></span><?php endif; ?>
									<?php if ( $career->deadline ) : ?><span><?php epic_the_icon( 'clock' ); ?>Apply by <?php echo esc_html( $career->deadline->format( 'd M Y' ) ); ?></span><?php endif; ?>
								</div>
								<h3><a href="<?php echo esc_url( $career->url ); ?>"><?php echo esc_html( $career->title ); ?></a></h3>
								<?php if ( $career->excerpt ) : ?><p style="font-size:.94rem;margin:0"><?php echo esc_html( epic_summarise( $career->excerpt, 200 ) ); ?></p><?php endif; ?>
							</div>
							<a class="btn btn-primary" href="<?php echo esc_url( $career->url ); ?>">View role <?php epic_the_icon( 'arrow-right' ); ?></a>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php
				epic_empty_state(
					'No open positions right now',
					'briefcase',
					'<p style="margin:0">We are always glad to hear from researchers, analysts and practitioners. Send your CV to <a href="mailto:' . esc_attr( $careers_email ) . '">' . esc_html( $careers_email ) . '</a> or <a href="' . esc_url( epic_page_url( 'volunteer' ) ) . '">register your interest</a>.</p>'
				);
				?>
			<?php endif; ?>

			<?php if ( $closed ) : ?>
				<div style="margin-top:52px">
					<h2 class="section-title" style="font-size:1.35rem">Recently closed</h2>
					<ul class="check-list is-2col">
						<?php foreach ( $closed as $career ) : ?>
							<li><?php epic_the_icon( 'clock' ); ?><div><strong><?php echo esc_html( $career->title ); ?></strong><span><?php echo esc_html( $career->type ); ?> <?php echo $career->location ? '&middot; ' . esc_html( $career->location ) : ''; ?></span></div></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( trim( (string) $epic_page->body ) ) : ?>
				<div class="prose mt-4"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'           => 'Other ways to work with EPIC',
			'text'            => 'Volunteer, join our research network, or collaborate with us as an institution.',
			'primary_label'   => 'Volunteer with EPIC',
			'primary_url'     => epic_page_url( 'volunteer' ),
			'secondary_label' => 'Contact us',
			'secondary_url'   => epic_page_url( 'contact' ),
		)
	);

get_footer();
