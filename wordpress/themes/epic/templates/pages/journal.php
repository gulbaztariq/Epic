<?php
/**
 * Publications → Journal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page   = Epic_Data::current_page();
$issues = Epic_Data::publications( 'journal', null, 9 );
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
				<div class="grid mt-4" style="gap:20px">
					<?php foreach ( $issues->items as $issue ) : ?>
						<article class="list-card">
							<div class="list-media">
								<img src="<?php echo esc_url( epic_image( $issue->cover_image, 'portrait' ) ); ?>" alt="<?php echo esc_attr( $issue->title ); ?>" loading="lazy"<?php echo epic_pic_style( $issue->cover_image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							</div>
							<div class="list-body">
								<div class="card-meta" style="margin:0">
									<span class="badge badge-blue"><?php echo esc_html( $issue->type ); ?></span>
									<?php if ( $issue->issue ) : ?><span><?php epic_the_icon( 'book' ); ?><?php echo esc_html( $issue->issue ); ?></span><?php endif; ?>
									<?php if ( $issue->published_at ) : ?><span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $issue->published_at->format( 'M Y' ) ); ?></span><?php endif; ?>
								</div>
								<h3><a href="<?php echo esc_url( $issue->url ); ?>"><?php echo esc_html( $issue->title ); ?></a></h3>
								<?php if ( $issue->authors ) : ?><p class="text-muted" style="font-size:.88rem;margin:0"><?php echo esc_html( $issue->authors ); ?></p><?php endif; ?>
								<p style="font-size:.94rem"><?php echo esc_html( epic_summarise( $issue->excerpt ?: $issue->body, 220 ) ); ?></p>
								<div class="list-actions">
									<a class="btn btn-outline btn-sm" href="<?php echo esc_url( $issue->url ); ?>">Read more</a>
									<?php if ( $issue->download_url ) : ?>
										<a class="btn btn-green btn-sm" href="<?php echo esc_url( $issue->download_url ); ?>" target="_blank" rel="noopener"><?php epic_the_icon( 'download' ); ?> Download</a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<?php epic_pagination( $issues ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Journal issues coming soon', 'book', "<p style=\"margin:0\">EPIC's HEC-recognized journal will publish peer-reviewed research on economic policy, human capital, governance, entrepreneurship and responsible innovation.</p>" ); ?>
			<?php endif; ?>

			<?php if ( trim( (string) $epic_page->body ) ) : ?>
				<div class="prose mt-4" style="margin-top:48px"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'         => 'Submit to the EPIC journal',
			'text'          => 'We welcome original research from academics, practitioners and policy professionals.',
			'primary_label' => 'Submission enquiries',
		)
	);

get_footer();
