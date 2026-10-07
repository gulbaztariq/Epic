<?php
/**
 * A single publication.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$publication = Epic_Item::from_post( get_queried_object() );
$related     = Epic_Data::related_publications( $publication, 4 );
epic_trail( array( 'Publications' => epic_page_url( 'publications' ), $publication->title => null ) );

get_header();
epic_hero(
	array(
		'title'    => $publication->title,
		'subtitle' => $publication->subtitle,
		'eyebrow'  => $publication->type,
	)
);
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div>
					<div class="meta-row">
						<?php if ( $publication->authors ) : ?><span><?php epic_the_icon( 'people' ); ?><?php echo esc_html( $publication->authors ); ?></span><?php endif; ?>
						<?php if ( $publication->published_at ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $publication->published_at->format( 'F Y' ) ); ?></span><?php endif; ?>
						<?php if ( $publication->theme ) : ?><span><?php epic_the_icon( 'layers' ); ?><?php echo esc_html( $publication->theme ); ?></span><?php endif; ?>
					</div>

					<?php if ( $publication->abstract ) : ?>
						<p class="lead" style="color:var(--navy)"><?php echo esc_html( $publication->abstract ); ?></p>
					<?php endif; ?>

					<div class="prose"><?php echo epic_content( $publication->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

					<?php if ( $publication->download_url ) : ?>
						<a class="btn btn-green btn-lg mt-4" href="<?php echo esc_url( $publication->download_url ); ?>" target="_blank" rel="noopener">
							<?php epic_the_icon( 'download' ); ?> Download publication
						</a>
					<?php endif; ?>

					<?php epic_share_row( $publication->title ); ?>
				</div>

				<aside class="sidebar">
					<div class="sidebar-box" style="padding:0;overflow:hidden">
						<div class="detail-picture is-portrait <?php echo epic_pic_adjusted( $publication->cover_image ) ? 'is-framed' : ''; ?>" style="margin:0;border-radius:0">
							<img src="<?php echo esc_url( epic_image( $publication->cover_image, 'portrait' ) ); ?>" alt="<?php echo esc_attr( $publication->title ); ?>"<?php echo epic_pic_style( $publication->cover_image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						</div>
						<div style="padding:20px">
							<span class="badge badge-blue"><?php echo esc_html( $publication->type ); ?></span>
							<?php if ( $publication->download_url ) : ?>
								<a class="btn btn-primary btn-block mt-3" href="<?php echo esc_url( $publication->download_url ); ?>" target="_blank" rel="noopener">Download</a>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( $related ) : ?>
						<div class="sidebar-box">
							<h4>Related publications</h4>
							<ul>
								<?php foreach ( $related as $item ) : ?>
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
