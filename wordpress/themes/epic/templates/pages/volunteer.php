<?php
/**
 * Get Involved → Volunteer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page = Epic_Data::current_page();
$ways = Epic_Data::list_items( 'get_involved' );
$interest = epic_old( 'interest' );
epic_trail( array( 'Get Involved' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div>
					<?php if ( $epic_page->intro ) : ?><p class="lead"><?php echo esc_html( $epic_page->intro ); ?></p><?php endif; ?>
					<div class="prose"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

					<div class="form-card mt-4">
						<h2 class="section-title" style="font-size:1.4rem">Volunteer application</h2>
						<?php epic_flash(); ?>

						<form action="<?php echo esc_url( get_permalink() ); ?>" method="post" enctype="multipart/form-data">
							<?php epic_form_fields( 'volunteer' ); ?>

							<div class="form-grid">
								<div class="form-field">
									<label for="v-name">Full name <span class="req">*</span></label>
									<input class="form-control" id="v-name" type="text" name="name" value="<?php echo esc_attr( epic_old( 'name' ) ); ?>" required>
								</div>
								<div class="form-field">
									<label for="v-email">Email <span class="req">*</span></label>
									<input class="form-control" id="v-email" type="email" name="email" value="<?php echo esc_attr( epic_old( 'email' ) ); ?>" required>
								</div>
								<div class="form-field">
									<label for="v-phone">Phone</label>
									<input class="form-control" id="v-phone" type="text" name="phone" value="<?php echo esc_attr( epic_old( 'phone' ) ); ?>">
								</div>
								<div class="form-field">
									<label for="v-city">City</label>
									<input class="form-control" id="v-city" type="text" name="city" value="<?php echo esc_attr( epic_old( 'city' ) ); ?>">
								</div>
								<div class="form-field">
									<label for="v-country">Country</label>
									<input class="form-control" id="v-country" type="text" name="country" value="<?php echo esc_attr( epic_old( 'country' ) ); ?>">
								</div>
								<div class="form-field">
									<label for="v-interest">Area of interest</label>
									<select class="form-control" id="v-interest" name="interest">
										<option value="">Please select…</option>
										<?php foreach ( $ways as $way ) : ?>
											<option value="<?php echo esc_attr( $way->title ); ?>"<?php selected( $interest, $way->title ); ?>><?php echo esc_html( $way->title ); ?></option>
										<?php endforeach; ?>
										<?php foreach ( array( 'Research support', 'Events and outreach', 'Communications', 'Other' ) as $extra ) : ?>
											<option value="<?php echo esc_attr( $extra ); ?>"<?php selected( $interest, $extra ); ?>><?php echo esc_html( $extra ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="form-field">
									<label for="v-availability">Availability</label>
									<input class="form-control" id="v-availability" type="text" name="availability" value="<?php echo esc_attr( epic_old( 'availability' ) ); ?>" placeholder="e.g. 10 hours per week">
								</div>
								<div class="form-field">
									<label for="v-cv">CV (PDF or Word, max 5 MB)</label>
									<input class="form-control" id="v-cv" type="file" name="cv" accept=".pdf,.doc,.docx">
								</div>
								<div class="form-field is-full">
									<label for="v-message">Tell us how you would like to contribute</label>
									<textarea class="form-control" id="v-message" name="message" rows="5"><?php echo esc_textarea( epic_old( 'message' ) ); ?></textarea>
								</div>
							</div>

							<div class="form-actions">
								<button class="btn btn-primary btn-lg" type="submit">Submit application <?php epic_the_icon( 'arrow-right' ); ?></button>
							</div>
						</form>
					</div>
				</div>

				<aside class="sidebar">
					<?php if ( $ways ) : ?>
						<div class="sidebar-box">
							<h4>Ways to engage</h4>
							<ul class="contact-lines" style="gap:14px">
								<?php foreach ( $ways as $way ) : ?>
									<li><?php epic_the_icon( $way->icon ?: 'check' ); ?><div><strong><?php echo esc_html( $way->title ); ?></strong><span><?php echo esc_html( $way->description ); ?></span></div></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<div class="sidebar-box">
						<h4>Prefer to talk first?</h4>
						<p style="font-size:.9rem">Write to us and a member of the EPIC team will get back to you.</p>
						<a class="btn btn-outline btn-block" href="<?php echo esc_url( epic_page_url( 'contact' ) ); ?>">Contact EPIC</a>
					</div>
				</aside>
			</div>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

<?php
get_footer();
