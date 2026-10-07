<?php
/**
 * Who We Are → EPIC Team, Board of Directors and Advisory Council (one layout, three groups).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$epic_page = Epic_Data::current_page();
$key  = (string) $epic_page->key;

$groups = array(
	'epic-team'        => array( 'team', null, '' ),
	'board'            => array( 'board', 'board_areas', 'The Board supports the organisation in areas including' ),
	'advisory-council' => array( 'advisory', 'advisory_areas', 'The Advisory Council provides non-executive technical and strategic guidance on' ),
);
list( $category, $area_group, $areas_heading ) = $groups[ $key ] ?? $groups['epic-team'];

$members = Epic_Data::team( $category );
$areas   = $area_group ? Epic_Data::list_items( $area_group ) : array();

epic_trail( array( 'Who We Are' => epic_page_url( 'about-us' ), $epic_page->title => null ) );

get_header();
epic_hero( array( 'page' => $epic_page ) );
?>

	<section class="section">
		<div class="container">
			<?php if ( $epic_page->intro ) : ?>
				<p class="lead" style="max-width:76ch"><?php echo esc_html( $epic_page->intro ); ?></p>
			<?php endif; ?>

			<?php if ( $members ) : ?>
				<div class="team-grid mt-4">
					<?php foreach ( $members as $member ) : ?>
						<article class="member-card">
							<div class="member-photo">
								<?php if ( $member->photo ) : ?>
									<img src="<?php echo esc_url( $member->photo->url ); ?>" alt="<?php echo esc_attr( $member->title ); ?>" loading="lazy"<?php echo epic_pic_style( $member->photo ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
								<?php else : ?>
									<div class="member-initials"><?php echo esc_html( $member->initials ); ?></div>
								<?php endif; ?>
							</div>
							<div class="member-body">
								<h3><?php echo esc_html( $member->title ); ?></h3>
								<?php if ( $member->designation ) : ?><p class="member-role"><?php echo esc_html( $member->designation ); ?></p><?php endif; ?>
								<?php if ( $member->short_bio ) : ?><p class="member-bio"><?php echo esc_html( epic_summarise( $member->short_bio, 110 ) ); ?></p><?php endif; ?>
								<?php if ( $member->email || $member->linkedin ) : ?>
									<div class="member-links">
										<?php if ( $member->email ) : ?><a href="mailto:<?php echo esc_attr( $member->email ); ?>" aria-label="Email <?php echo esc_attr( $member->title ); ?>"><?php epic_the_icon( 'mail' ); ?></a><?php endif; ?>
										<?php if ( $member->linkedin ) : ?><a href="<?php echo esc_url( $member->linkedin ); ?>" target="_blank" rel="noopener" aria-label="LinkedIn profile"><?php epic_the_icon( 'linkedin' ); ?></a><?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php epic_empty_state( 'To be confirmed', 'users', '<p style="margin:0">Details will be announced shortly. For enquiries please <a href="' . esc_url( epic_page_url( 'contact' ) ) . '">contact EPIC</a>.</p>' ); ?>
			<?php endif; ?>

			<?php if ( $areas ) : ?>
				<div class="mt-4" style="margin-top:52px">
					<h2 class="section-title" style="font-size:1.45rem"><?php echo esc_html( $areas_heading ?: 'Areas of oversight' ); ?></h2>
					<?php epic_check_list( $areas, 3 ); ?>
				</div>
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
			'title'           => 'Join the EPIC network',
			'text'            => 'We welcome researchers, practitioners and institutions who share our commitment to evidence and impact.',
			'primary_label'   => 'Get involved',
			'primary_url'     => epic_page_url( 'volunteer' ),
			'secondary_label' => 'See open roles',
			'secondary_url'   => epic_page_url( 'careers' ),
		)
	);

get_footer();
