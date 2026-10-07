<?php
/**
 * Get Involved → Subscribe.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page = Epic_Data::current_page();
epic_trail( array( 'Get Involved' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container container-narrow">
			<?php if ( $epic_page->intro ) : ?><p class="lead"><?php echo esc_html( $epic_page->intro ); ?></p><?php endif; ?>

			<div class="form-card mt-4">
				<?php epic_flash(); ?>

				<form action="<?php echo esc_url( get_permalink() ); ?>" method="post">
					<?php epic_form_fields( 'subscribe' ); ?>
					<input type="hidden" name="source" value="subscribe-page">

					<div class="form-grid">
						<div class="form-field">
							<label for="s-name">Name</label>
							<input class="form-control" id="s-name" type="text" name="name" value="<?php echo esc_attr( epic_old( 'name' ) ); ?>">
						</div>
						<div class="form-field">
							<label for="s-email">Email <span class="req">*</span></label>
							<input class="form-control" id="s-email" type="email" name="email" value="<?php echo esc_attr( epic_old( 'email' ) ); ?>" required>
						</div>
						<div class="form-field is-full">
							<label for="s-org">Organisation</label>
							<input class="form-control" id="s-org" type="text" name="organisation" value="<?php echo esc_attr( epic_old( 'organisation' ) ); ?>">
						</div>
					</div>

					<div class="form-actions">
						<button class="btn btn-primary btn-lg" type="submit">Subscribe <?php epic_the_icon( 'arrow-right' ); ?></button>
					</div>
					<p class="form-hint mt-3"><?php echo esc_html( epic_setting( 'subscribe_privacy_note', 'We use your details only to send EPIC research, events and updates. You can unsubscribe at any time.' ) ); ?></p>
				</form>
			</div>

			<div class="prose mt-4"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

<?php
get_footer();
