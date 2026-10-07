<?php
/**
 * What We Do → International Chapters.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page     = Epic_Data::current_page();
$chapters = Epic_Data::chapters();
epic_trail( array( 'What We Do' => epic_page_url( 'themes' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $chapters ) : ?>
				<div class="chapter-grid mt-4">
					<?php foreach ( $chapters as $chapter ) : ?>
						<article class="chapter-card">
							<div class="chapter-top">
								<?php if ( $chapter->image ) : ?>
									<img src="<?php echo esc_url( $chapter->image->url ); ?>" alt="<?php echo esc_attr( $chapter->title ); ?>" style="width:38px;height:26px;object-fit:cover;border-radius:3px">
								<?php else : ?>
									<?php epic_the_icon( 'globe' ); ?>
								<?php endif; ?>
								<h3><?php echo esc_html( $chapter->title ); ?></h3>
							</div>
							<?php if ( $chapter->city ) : ?><p><strong style="color:var(--navy)"><?php echo esc_html( $chapter->city ); ?></strong></p><?php endif; ?>
							<?php if ( $chapter->description ) : ?><p><?php echo esc_html( $chapter->description ); ?></p><?php endif; ?>
							<span class="badge <?php echo 'active' === $chapter->status ? 'badge-green' : 'badge-outline'; ?>" style="align-self:flex-start;margin-top:auto">
								<?php echo esc_html( Epic_Schema::CHAPTER_STATUSES[ $chapter->status ] ?? $chapter->status ); ?>
							</span>
							<?php if ( $chapter->contact_email ) : ?>
								<a class="section-link" href="mailto:<?php echo esc_attr( $chapter->contact_email ); ?>"><?php epic_the_icon( 'mail' ); ?> <?php echo esc_html( $chapter->contact_email ); ?></a>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php epic_empty_state( 'Chapters coming soon', 'globe' ); ?>
			<?php endif; ?>

			<?php if ( trim( (string) $epic_page->body ) ) : ?>
				<div class="prose mt-4"><?php echo epic_content( $epic_page->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'         => 'Start an EPIC chapter',
			'text'          => 'We welcome expressions of interest from institutions and professionals who would like to host an EPIC international chapter.',
			'primary_label' => 'Express interest',
		)
	);

get_footer();
