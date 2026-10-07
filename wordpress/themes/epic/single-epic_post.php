<?php
/**
 * A blog, article or press release.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$article  = Epic_Item::from_post( get_queried_object() );
$is_press = 'press_release' === $article->category;
$related  = Epic_Data::related_posts( $article, $is_press ? array( 'press_release' ) : array( 'blog', 'article' ), 3 );

epic_trail(
	$is_press
		? array( 'Media' => null, 'Press Releases' => epic_page_url( 'press-releases' ), $article->title => null )
		: array( 'Blogs & Articles' => epic_page_url( 'blogs' ), $article->title => null )
);

get_header();
epic_hero(
	array(
		'title'   => $article->title,
		'eyebrow' => $article->category_label,
	)
);
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div>
					<div class="detail-picture <?php echo epic_pic_adjusted( $article->image ) ? 'is-framed' : ''; ?>">
						<img src="<?php echo esc_url( epic_image( $article->image, 'wide' ) ); ?>" alt="<?php echo esc_attr( $article->title ); ?>"<?php echo epic_pic_style( $article->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					</div>

					<div class="meta-row">
						<?php if ( $article->published_at ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $article->published_at->format( 'd F Y' ) ); ?></span><?php endif; ?>
						<?php if ( $article->author ) : ?><span><?php epic_the_icon( 'people' ); ?><?php echo esc_html( $article->author ); ?></span><?php endif; ?>
						<?php if ( $article->tags ) : ?><span><?php epic_the_icon( 'layers' ); ?><?php echo esc_html( $article->tags ); ?></span><?php endif; ?>
					</div>

					<?php if ( $article->excerpt ) : ?>
						<p class="lead" style="color:var(--navy)"><?php echo esc_html( $article->excerpt ); ?></p>
					<?php endif; ?>

					<div class="prose"><?php echo epic_content( $article->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

					<?php epic_share_row( $article->title ); ?>
				</div>

				<aside class="sidebar">
					<?php if ( $related ) : ?>
						<div class="sidebar-box">
							<h4><?php echo $is_press ? 'More press releases' : 'Related reading'; ?></h4>
							<ul>
								<?php foreach ( $related as $item ) : ?>
									<li><a href="<?php echo esc_url( $item->url ); ?>"><?php echo esc_html( $item->title ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<div class="sidebar-box">
						<h4>Stay informed</h4>
						<p style="font-size:.9rem">Get EPIC research, events and insights in your inbox.</p>
						<form action="<?php echo esc_url( epic_page_url( 'subscribe' ) ); ?>" method="post">
							<?php epic_form_fields( 'subscribe' ); ?>
							<input type="hidden" name="source" value="article-sidebar">
							<input class="form-control" type="email" name="email" placeholder="Your email" required>
							<button class="btn btn-primary btn-block mt-3" type="submit">Subscribe</button>
						</form>
					</div>
				</aside>
			</div>
		</div>
	</section>

<?php
get_footer();
