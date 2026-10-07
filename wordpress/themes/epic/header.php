<?php
/**
 * Site header: <head>, skip link and the sticky menu bar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_menu    = Epic_Menus::tree( 'primary' );
$epic_current = epic_current_url();
$epic_name    = epic_setting( 'site_name', 'EPIC' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $epic_name ); ?> home">
			<img src="<?php echo esc_url( epic_site_logo() ); ?>" alt="<?php echo esc_attr( epic_setting( 'site_name', 'EPIC — Economic Policy and Innovation Centre' ) ); ?>" width="240" height="122">
		</a>

		<nav class="primary-nav" aria-label="Primary">
			<ul class="nav-list">
				<?php foreach ( $epic_menu as $item ) : ?>
					<?php $has_children = ! empty( $item->children ); ?>
					<?php $active = '#' !== $item->url && 0 === strpos( $epic_current, $item->url ); ?>
					<li class="<?php echo $has_children ? 'has-drop' : ''; ?>">
						<a class="nav-link <?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $item->url ); ?>" target="<?php echo esc_attr( $item->target ); ?>">
							<?php echo esc_html( $item->label ); ?>
							<?php if ( $has_children ) { epic_the_icon( 'chevron-down' ); } ?>
						</a>
						<?php if ( $has_children ) : ?>
							<ul class="nav-drop">
								<?php foreach ( $item->children as $child ) : ?>
									<li><a href="<?php echo esc_url( $child->url ); ?>" target="<?php echo esc_attr( $child->target ); ?>"><?php echo esc_html( $child->label ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="header-actions">
			<button class="icon-btn" type="button" data-search-open aria-label="Search the website"><?php epic_the_icon( 'search' ); ?></button>

			<?php if ( epic_setting( 'header_cta_label' ) ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( epic_resolved_url( epic_setting( 'header_cta_url', '/contact' ) ) ); ?>">
					<?php echo esc_html( epic_setting( 'header_cta_label' ) ); ?> <?php epic_the_icon( 'arrow-right' ); ?>
				</a>
			<?php endif; ?>

			<button class="icon-btn nav-toggle" type="button" data-nav-open aria-label="Open menu"><?php epic_the_icon( 'menu' ); ?></button>
		</div>
	</div>
</header>

<main id="main">
