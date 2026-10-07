<?php
/**
 * A single project.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$project = Epic_Item::from_post( get_queried_object() );
$related = Epic_Data::other_projects( $project, 3 );
$status  = Epic_Schema::PROJECT_STATUSES[ $project->status ] ?? $project->status;
epic_trail( array( 'What We Do' => epic_page_url( 'themes' ), 'Projects' => epic_page_url( 'projects' ), $project->title => null ) );

get_header();
epic_hero(
	array(
		'title'    => $project->title,
		'subtitle' => $project->summary,
		'eyebrow'  => $project->category,
	)
);
?>

	<section class="section">
		<div class="container">
			<div class="content-layout">
				<div>
					<div class="detail-picture <?php echo epic_pic_adjusted( $project->image ) ? 'is-framed' : ''; ?>">
						<img src="<?php echo esc_url( epic_image( $project->image, 'wide' ) ); ?>" alt="<?php echo esc_attr( $project->title ); ?>"<?php echo epic_pic_style( $project->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					</div>

					<div class="prose"><?php echo epic_content( $project->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</div>

				<aside class="sidebar">
					<div class="sidebar-box">
						<h4>Project details</h4>
						<ul class="contact-lines" style="gap:14px">
							<li><?php epic_the_icon( 'flag' ); ?><div><strong>Status</strong><span><?php echo esc_html( $status ); ?></span></div></li>
							<?php if ( $project->category ) : ?>
								<li><?php epic_the_icon( 'layers' ); ?><div><strong>Theme</strong><span><?php echo esc_html( $project->category ); ?></span></div></li>
							<?php endif; ?>
							<?php if ( $project->started_at ) : ?>
								<li><?php epic_the_icon( 'calendar' ); ?><div><strong>Timeline</strong><span><?php echo esc_html( $project->started_at->format( 'M Y' ) ); ?><?php echo $project->ended_at ? ' &ndash; ' . esc_html( $project->ended_at->format( 'M Y' ) ) : ''; ?></span></div></li>
							<?php endif; ?>
							<?php if ( $project->partners ) : ?>
								<li><?php epic_the_icon( 'partnership' ); ?><div><strong>Partners</strong><span><?php echo esc_html( $project->partners ); ?></span></div></li>
							<?php endif; ?>
						</ul>
						<a class="btn btn-primary btn-block mt-3" href="<?php echo esc_url( epic_page_url( 'contact' ) ); ?>">Enquire about this project</a>
					</div>

					<?php if ( $related ) : ?>
						<div class="sidebar-box">
							<h4>Other projects</h4>
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
