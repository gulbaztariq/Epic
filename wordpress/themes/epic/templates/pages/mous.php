<?php
/**
 * Partnerships & MoUs → MoUs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page     = Epic_Data::current_page();
$partners = Epic_Data::partners( 'mou' );
$scope    = Epic_Data::list_items( 'mou_scope' );
epic_trail( array( 'Partnerships & MoUs' => epic_page_url( 'partnerships' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $partners ) : ?>
				<div class="grid grid-3 mt-4">
					<?php foreach ( $partners as $partner ) : ?>
						<article class="card" style="padding:24px">
							<?php if ( $partner->logo ) : ?>
								<img src="<?php echo esc_url( $partner->logo->url ); ?>" alt="<?php echo esc_attr( $partner->title ); ?>" style="max-height:50px;width:auto;margin-bottom:14px">
							<?php else : ?>
								<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( 'document' ); ?></div>
							<?php endif; ?>
							<h3 style="font-size:1.08rem"><?php echo esc_html( $partner->title ); ?></h3>
							<?php if ( $partner->country ) : ?><p class="text-muted" style="font-size:.85rem;margin:0 0 6px"><?php echo esc_html( $partner->country ); ?></p><?php endif; ?>
							<?php if ( $partner->description ) : ?><p style="font-size:.9rem"><?php echo esc_html( $partner->description ); ?></p><?php endif; ?>
							<?php if ( $partner->signed_on ) : ?>
								<span class="badge badge-blue" style="align-self:flex-start">Signed <?php echo esc_html( $partner->signed_on->format( 'M Y' ) ); ?></span>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php epic_empty_state( 'MoUs will be listed here', 'document', '<p style="margin:0">EPIC enters into strategic Memoranda of Understanding with institutions that share our interests in research, education, policy, entrepreneurship, innovation and human capital development.</p>' ); ?>
			<?php endif; ?>

			<?php if ( $scope ) : ?>
				<div style="margin-top:54px">
					<h2 class="section-title" style="font-size:1.45rem">MoU partnerships include</h2>
					<?php epic_check_list( $scope, 3 ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'         => 'Sign an MoU with EPIC',
			'text'          => 'Joint research, faculty collaboration, internships, co-publication, collaborative grant applications and more.',
			'primary_label' => 'Discuss an MoU',
		)
	);

get_footer();
