<?php
/**
 * What We Do → Projects.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page          = Epic_Data::current_page();
$projects      = Epic_Data::paginate( 'epic_project', array(), 9 );
$project_types = Epic_Data::list_items( 'project_types' );
epic_trail( array( 'What We Do' => epic_page_url( 'themes' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $projects->items ) : ?>
				<div class="grid grid-3 mt-4">
					<?php foreach ( $projects->items as $project ) { epic_card_project( $project ); } ?>
				</div>
				<?php epic_pagination( $projects ); ?>
			<?php else : ?>
				<?php epic_empty_state( 'Projects will be published here', 'briefcase', '<p style="margin:0">EPIC designs and implements research, policy, capacity-building and development projects independently and with partner organisations.</p>' ); ?>
			<?php endif; ?>

			<?php if ( $project_types ) : ?>
				<div style="margin-top:54px">
					<h2 class="section-title" style="font-size:1.45rem">Our projects may include</h2>
					<?php epic_check_list( $project_types, 3 ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php epic_sections( $epic_page ); ?>

	<?php
	epic_cta_band(
		$epic_page,
		array(
			'title'         => 'Commission a project with EPIC',
			'text'          => 'From policy research and labour-market assessments to entrepreneurship programmes and impact evaluations.',
			'primary_label' => 'Start a conversation',
		)
	);

get_footer();
