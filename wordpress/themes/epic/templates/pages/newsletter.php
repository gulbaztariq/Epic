<?php
/**
 * Publications → E-Newsletter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page   = Epic_Data::current_page();
$issues = Epic_Data::publications( 'newsletter', null, 9 );
epic_trail( array( 'Publications' => epic_page_url( 'publications' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $issues->items ) : ?>
				<div class="grid grid-3 mt-4">
					<?php foreach ( $issues->items as $issue ) : ?>
						<article class="card">
							<a class="card-media is-wide" href="<?php echo esc_url( $issue->url ); ?>">
								<img src="<?php echo esc_url( epic_image( $issue->cover_image, 'card' ) ); ?>" alt="<?php echo esc_attr( $issue->title ); ?>" loading="lazy"<?php echo epic_pic_style( $issue->cover_image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							</a>
							<div class="card-body">
								<h3><a href="<?php echo esc_url( $issue->url ); ?>"><?php echo esc_html( $issue->title ); ?></a></h3>
								<p><?php echo esc_html( epic_summarise( $issue->excerpt, 120 ) ); ?></p>
								<div class="card-meta">
									<?php if ( $issue->published_at ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $issue->published_at->format( 'M Y' ) ); ?></span><?php endif; ?>
									<?php if ( $issue->download_url ) : ?>
										<a class="section-link" href="<?php echo esc_url( $issue->download_url ); ?>" target="_blank" rel="noopener">Read issue <?php epic_the_icon( 'arrow-right' ); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<?php epic_pagination( $issues ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Newsletter issues coming soon', 'mail', '<p style="margin:0">Subscribe below and the EPIC e-newsletter will reach your inbox as soon as it is published.</p>' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<section class="section section-soft">
		<div class="container container-narrow">
			<div class="form-card">
				<h2 class="section-title" style="font-size:1.5rem">Subscribe to the e-newsletter</h2>
				<?php epic_flash(); ?>
				<form action="<?php echo esc_url( get_permalink() ); ?>" method="post">
					<?php epic_form_fields( 'subscribe' ); ?>
					<input type="hidden" name="source" value="newsletter-page">
					<div class="form-grid">
						<div class="form-field">
							<label for="nl-name">Name</label>
							<input class="form-control" id="nl-name" type="text" name="name" value="<?php echo esc_attr( epic_old( 'name' ) ); ?>">
						</div>
						<div class="form-field">
							<label for="nl-email">Email <span class="req">*</span></label>
							<input class="form-control" id="nl-email" type="email" name="email" value="<?php echo esc_attr( epic_old( 'email' ) ); ?>" required>
						</div>
					</div>
					<div class="form-actions">
						<button class="btn btn-primary" type="submit">Subscribe <?php epic_the_icon( 'arrow-right' ); ?></button>
					</div>
				</form>
			</div>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

<?php
get_footer();
