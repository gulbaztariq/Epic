<?php
/**
 * Media → Press Releases.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page  = Epic_Data::current_page();
$posts = Epic_Data::posts( array( 'press_release' ), 9 );
$media_email = epic_setting( 'media_email', epic_setting( 'contact_email', 'info@epic.org.pk' ) );
epic_trail( array( 'Media' => null, $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?><p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p><?php endif; ?>

			<?php if ( $posts->items ) : ?>
				<div class="grid mt-4" style="gap:18px">
					<?php foreach ( $posts->items as $post_item ) : ?>
						<article class="list-card is-wide">
							<div class="list-media">
								<img src="<?php echo esc_url( epic_image( $post_item->image, 'card' ) ); ?>" alt="<?php echo esc_attr( $post_item->title ); ?>" loading="lazy"<?php echo epic_pic_style( $post_item->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							</div>
							<div class="list-body">
								<div class="card-meta" style="margin:0">
									<span class="badge badge-navy">Press Release</span>
									<?php if ( $post_item->published_at ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $post_item->published_at->format( 'd M Y' ) ); ?></span><?php endif; ?>
								</div>
								<h3><a href="<?php echo esc_url( $post_item->url ); ?>"><?php echo esc_html( $post_item->title ); ?></a></h3>
								<p style="font-size:.94rem;margin:0"><?php echo esc_html( epic_summarise( $post_item->summary, 220 ) ); ?></p>
								<div class="list-actions">
									<a class="btn btn-outline btn-sm" href="<?php echo esc_url( $post_item->url ); ?>">Read release <?php epic_the_icon( 'arrow-right' ); ?></a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<?php epic_pagination( $posts ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Press releases will appear here', 'document', '<p style="margin:0">For media enquiries please email <a href="mailto:' . esc_attr( $media_email ) . '">' . esc_html( $media_email ) . '</a>.</p>' ); ?>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'         => 'Media enquiries',
			'text'          => 'Our team is available for interviews, expert comment and background briefings on economic policy, human capital, entrepreneurship and responsible AI.',
			'primary_label' => 'Contact the media desk',
		)
	);

get_footer();
