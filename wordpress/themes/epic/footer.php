<?php
/**
 * Site footer, mobile navigation, search panel and back-to-top button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_columns = Epic_Menus::tree( 'footer' );
$epic_legal   = Epic_Menus::tree( 'footer_legal' );
$epic_header  = Epic_Menus::tree( 'primary' );
$epic_social  = epic_social_links();
$epic_name    = epic_setting( 'site_name', 'EPIC' );
?>
</main>

<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div class="footer-brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( epic_site_logo( true ) ); ?>" alt="<?php echo esc_attr( $epic_name ); ?>"></a>
				<p><?php echo esc_html( epic_setting( 'footer_about', 'Independent research. Practical solutions. A more prosperous Pakistan.' ) ); ?></p>

				<?php if ( $epic_social ) : ?>
					<div class="social-row" style="margin-top:18px">
						<?php foreach ( $epic_social as $link ) : ?>
							<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $link['label'] ); ?>"><?php epic_the_icon( $link['icon'] ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php foreach ( $epic_columns as $column ) : ?>
				<div class="footer-col">
					<h4><?php echo esc_html( $column->label ); ?></h4>
					<ul>
						<?php foreach ( $column->children as $child ) : ?>
							<li><a href="<?php echo esc_url( $child->url ); ?>" target="<?php echo esc_attr( $child->target ); ?>"><?php echo esc_html( $child->label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>

			<div class="footer-col">
				<h4>Get in touch</h4>
				<ul class="footer-contact">
					<?php if ( epic_setting( 'contact_address' ) ) : ?>
						<li><?php epic_the_icon( 'location' ); ?><span><?php echo esc_html( epic_setting( 'contact_address' ) ); ?></span></li>
					<?php endif; ?>
					<?php if ( epic_setting( 'contact_email' ) ) : ?>
						<li><?php epic_the_icon( 'mail' ); ?><a href="mailto:<?php echo esc_attr( epic_setting( 'contact_email' ) ); ?>"><?php echo esc_html( epic_setting( 'contact_email' ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( epic_setting( 'contact_phone' ) ) : ?>
						<li><?php epic_the_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) epic_setting( 'contact_phone' ) ) ); ?>"><?php echo esc_html( epic_setting( 'contact_phone' ) ); ?></a></li>
					<?php endif; ?>
				</ul>

				<?php if ( epic_setting( 'footer_tagline' ) ) : ?>
					<p class="footer-tagline"><?php echo epic_nl2br( epic_setting( 'footer_tagline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="footer-bottom">
			<p style="margin:0">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( epic_setting( 'site_name_full', 'Economic Policy and Innovation Centre (EPIC)' ) ); ?>. <?php echo esc_html( epic_setting( 'footer_rights', 'All rights reserved.' ) ); ?></p>

			<?php if ( '1' === epic_setting( 'show_visitor_counter', '1' ) ) : ?>
				<?php
				$epic_views = 'views' === epic_setting( 'visitor_counter_metric', 'visitors' );
				$epic_count = $epic_views ? Epic_Visits::total_page_views() : Epic_Visits::total_visitors();
				?>
				<p class="visitor-counter" title="Since the website went live">
					<?php epic_the_icon( $epic_views ? 'eye' : 'people' ); ?>
					<strong><?php echo esc_html( number_format( $epic_count ) ); ?></strong>
					<span><?php echo esc_html( epic_setting( 'visitor_counter_label', $epic_views ? 'Page views' : 'Website visitors' ) ); ?></span>
				</p>
			<?php endif; ?>

			<ul>
				<?php foreach ( $epic_legal as $item ) : ?>
					<li><a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</footer>

<div class="nav-backdrop" aria-hidden="true"></div>

<nav class="mobile-nav" aria-label="Mobile">
	<div class="mobile-nav-head">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( epic_site_logo() ); ?>" alt="<?php echo esc_attr( $epic_name ); ?>"></a>
		<button class="icon-btn" type="button" data-nav-close aria-label="Close menu"><?php epic_the_icon( 'close' ); ?></button>
	</div>

	<ul>
		<?php foreach ( $epic_header as $item ) : ?>
			<li>
				<?php if ( ! empty( $item->children ) ) : ?>
					<button class="m-parent" type="button"><?php echo esc_html( $item->label ); ?> <?php epic_the_icon( 'chevron-down' ); ?></button>
					<ul class="m-sub">
						<li><a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->label ); ?> overview</a></li>
						<?php foreach ( $item->children as $child ) : ?>
							<li><a href="<?php echo esc_url( $child->url ); ?>"><?php echo esc_html( $child->label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->label ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="mobile-nav-actions">
		<?php if ( epic_setting( 'header_cta_label' ) ) : ?>
			<a class="btn btn-primary btn-block" href="<?php echo esc_url( epic_resolved_url( epic_setting( 'header_cta_url', '/contact' ) ) ); ?>"><?php echo esc_html( epic_setting( 'header_cta_label' ) ); ?></a>
		<?php endif; ?>
		<a class="btn btn-outline btn-block" href="<?php echo esc_url( epic_page_url( 'contact' ) ); ?>">Contact EPIC</a>
	</div>
</nav>

<div class="search-panel" role="dialog" aria-label="Search">
	<form action="<?php echo esc_url( epic_page_url( 'search' ) ); ?>" method="get">
		<input type="search" name="q" placeholder="Search publications, events, projects and news…" value="<?php echo esc_attr( isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification ?>" aria-label="Search">
		<button class="btn btn-primary" type="submit"><?php epic_the_icon( 'search' ); ?> Search</button>
	</form>
	<p class="search-hint">Press <strong>Esc</strong> to close</p>
</div>

<button class="back-to-top" type="button" aria-label="Back to top"><?php epic_the_icon( 'arrow-right' ); ?></button>

<?php wp_footer(); ?>
</body>
</html>
