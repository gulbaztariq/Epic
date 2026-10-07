<?php
/**
 * Contact.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page   = Epic_Data::current_page();
$social = epic_social_links();
epic_trail( array( $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div class="form-card">
					<h2 class="section-title" style="font-size:1.5rem">Send us a message</h2>
					<?php epic_flash(); ?>

					<form action="<?php echo esc_url( get_permalink() ); ?>" method="post">
						<?php epic_form_fields( 'contact' ); ?>

						<div class="form-grid">
							<div class="form-field">
								<label for="c-name">Full name <span class="req">*</span></label>
								<input class="form-control" id="c-name" type="text" name="name" value="<?php echo esc_attr( epic_old( 'name' ) ); ?>" required>
							</div>
							<div class="form-field">
								<label for="c-email">Email <span class="req">*</span></label>
								<input class="form-control" id="c-email" type="email" name="email" value="<?php echo esc_attr( epic_old( 'email' ) ); ?>" required>
							</div>
							<div class="form-field">
								<label for="c-phone">Phone</label>
								<input class="form-control" id="c-phone" type="text" name="phone" value="<?php echo esc_attr( epic_old( 'phone' ) ); ?>">
							</div>
							<div class="form-field">
								<label for="c-org">Organisation</label>
								<input class="form-control" id="c-org" type="text" name="organisation" value="<?php echo esc_attr( epic_old( 'organisation' ) ); ?>">
							</div>
							<div class="form-field is-full">
								<label for="c-subject">Subject</label>
								<input class="form-control" id="c-subject" type="text" name="subject" value="<?php echo esc_attr( epic_old( 'subject' ) ); ?>">
							</div>
							<div class="form-field is-full">
								<label for="c-message">Message <span class="req">*</span></label>
								<textarea class="form-control" id="c-message" name="message" rows="6" required><?php echo esc_textarea( epic_old( 'message' ) ); ?></textarea>
							</div>
						</div>

						<div class="form-actions">
							<button class="btn btn-primary btn-lg" type="submit">Send message <?php epic_the_icon( 'arrow-right' ); ?></button>
						</div>
					</form>
				</div>

				<aside class="sidebar">
					<div class="sidebar-box">
						<h4>Contact details</h4>
						<ul class="contact-lines">
							<?php if ( epic_setting( 'contact_address' ) ) : ?>
								<li><?php epic_the_icon( 'location' ); ?><div><strong>Address</strong><span><?php echo esc_html( epic_setting( 'contact_address' ) ); ?></span></div></li>
							<?php endif; ?>
							<?php if ( epic_setting( 'contact_email' ) ) : ?>
								<li><?php epic_the_icon( 'mail' ); ?><div><strong>Email</strong><a href="mailto:<?php echo esc_attr( epic_setting( 'contact_email' ) ); ?>"><?php echo esc_html( epic_setting( 'contact_email' ) ); ?></a></div></li>
							<?php endif; ?>
							<?php if ( epic_setting( 'contact_phone' ) ) : ?>
								<li><?php epic_the_icon( 'phone' ); ?><div><strong>Phone</strong><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) epic_setting( 'contact_phone' ) ) ); ?>"><?php echo esc_html( epic_setting( 'contact_phone' ) ); ?></a></div></li>
							<?php endif; ?>
							<?php if ( epic_setting( 'office_hours' ) ) : ?>
								<li><?php epic_the_icon( 'clock' ); ?><div><strong>Office hours</strong><span><?php echo esc_html( epic_setting( 'office_hours' ) ); ?></span></div></li>
							<?php endif; ?>
						</ul>

						<?php if ( $social ) : ?>
							<div class="social-row mt-3" style="margin-top:18px">
								<?php foreach ( $social as $link ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $link['label'] ); ?>"
										style="background:var(--bg-soft);color:var(--navy)"><?php epic_the_icon( $link['icon'] ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="sidebar-box">
						<h4>Other enquiries</h4>
						<ul>
							<li><a href="<?php echo esc_url( epic_page_url( 'careers' ) ); ?>">Careers at EPIC</a></li>
							<li><a href="<?php echo esc_url( epic_page_url( 'volunteer' ) ); ?>">Volunteer with us</a></li>
							<li><a href="<?php echo esc_url( epic_page_url( 'partnerships' ) ); ?>">Partnerships &amp; MoUs</a></li>
							<li><a href="<?php echo esc_url( epic_page_url( 'press-releases' ) ); ?>">Media &amp; press</a></li>
						</ul>
					</div>
				</aside>
			</div>

			<?php if ( epic_setting( 'map_embed' ) ) : ?>
				<div class="map-frame mt-4" style="margin-top:44px"><?php echo epic_setting( 'map_embed' ); // phpcs:ignore WordPress.Security.EscapeOutput -- administrator-entered embed code. ?></div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

<?php
get_footer();
