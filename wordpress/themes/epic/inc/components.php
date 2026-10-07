<?php
/**
 * The reusable pieces of the design, ported one for one from the Laravel Blade
 * components and partials (page-hero, check-list, cta-band, subscribe-band,
 * sections, the cards, pagination, flash messages). The markup is unchanged, so
 * the stylesheet applies exactly as before.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ----------------------------------------------------------------- hero --- */

/**
 * Remember the breadcrumb trail. Called before get_header(), because search
 * engines read the trail from the page head, which is printed before the hero.
 *
 * @param array<string,string|null> $trail label => address (null for the current page)
 */
function epic_trail( array $trail ): void {
	$GLOBALS['epic_breadcrumbs'] = $trail;
}

/**
 * The page header band.
 *
 * @param array{page?:Epic_Item,title?:string,subtitle?:string,eyebrow?:string,image?:Epic_Pic,breadcrumbs?:array} $a
 */
function epic_hero( array $a = array() ): void {
	$page   = $a['page'] ?? null;
	$crumbs = $a['breadcrumbs'] ?? ( $GLOBALS['epic_breadcrumbs'] ?? array() );

	$title    = ! empty( $a['title'] ) ? $a['title'] : ( $page ? ( $page->hero_title ?: $page->title ) : '' );
	$subtitle = ! empty( $a['subtitle'] ) ? $a['subtitle'] : ( $page ? $page->hero_subtitle : '' );
	$eyebrow  = ! empty( $a['eyebrow'] ) ? $a['eyebrow'] : ( $page ? $page->eyebrow : '' );
	$image    = ! empty( $a['image'] ) ? $a['image'] : ( $page ? $page->hero_image : null );
	?>
	<section class="page-hero <?php echo $image ? 'has-image' : ''; ?>">
		<?php if ( $image ) : ?>
			<img class="page-hero-media" src="<?php echo esc_url( $image->url ); ?>" alt="" fetchpriority="high"<?php echo $image->style(); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php endif; ?>
		<div class="container">
			<?php if ( $crumbs ) : ?>
				<ul class="breadcrumbs">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
					<?php foreach ( $crumbs as $label => $url ) : ?>
						<li><?php echo $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' : ' <span>' . esc_html( $label ) . '</span> '; // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $eyebrow ) : ?>
				<p class="eyebrow eyebrow-light"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>

			<h1><?php echo esc_html( (string) $title ); ?></h1>

			<?php if ( $subtitle ) : ?>
				<p><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/* ------------------------------------------------------------ small parts --- */

/**
 * A grid of ticked items.
 *
 * @param array<int,Epic_Item|string> $items
 */
function epic_check_list( array $items, int $columns = 2, string $icon = 'check' ): void {
	if ( ! $items ) {
		return;
	}
	?>
	<ul class="check-list is-<?php echo (int) $columns; ?>col">
		<?php foreach ( $items as $item ) : ?>
			<li>
				<?php epic_the_icon( is_object( $item ) && $item->icon ? $item->icon : $icon ); ?>
				<div>
					<strong><?php echo esc_html( is_object( $item ) ? $item->title : $item ); ?></strong>
					<?php if ( is_object( $item ) && $item->description ) : ?>
						<span><?php echo esc_html( $item->description ); ?></span>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/** A friendly "nothing here yet" box. $html is trusted markup. */
function epic_empty_state( string $title = 'Coming soon', string $icon = 'sparkle', string $html = '' ): void {
	?>
	<div class="empty-state">
		<?php epic_the_icon( $icon ); ?>
		<h3><?php echo esc_html( $title ); ?></h3>
		<div><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted markup from the templates. ?></div>
	</div>
	<?php
}

/**
 * The dark call-to-action band. A "Call to action band" section on the page
 * overrides the defaults, so editors can change this text.
 *
 * @param array{title?:string,text?:string,primary_label?:string,primary_url?:string,secondary_label?:string,secondary_url?:string} $a
 */
function epic_cta_band( Epic_Item $page, array $a = array() ): void {
	$a += array(
		'title'           => 'Work with EPIC',
		'text'            => 'Partner with us on research, policy dialogue, capacity building and innovation initiatives.',
		'primary_label'   => 'Contact us',
		'primary_url'     => null,
		'secondary_label' => null,
		'secondary_url'   => null,
	);

	$block = $page->section( 'cta' );

	$title         = $block->title ?: $a['title'];
	$text          = $block->body ? trim( strip_tags( (string) $block->body ) ) : $a['text'];
	$primary_label = $block->link_text ?: $a['primary_label'];
	$primary_url   = $block->link_url ?: ( $a['primary_url'] ?: epic_page_url( 'contact' ) );
	?>
	<section class="section">
		<div class="container">
			<div class="cta-band">
				<div>
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $text ); ?></p>
				</div>
				<div class="cta-actions">
					<a class="btn btn-light" href="<?php echo esc_url( epic_resolved_url( $primary_url ) ); ?>"><?php echo esc_html( $primary_label ); ?> <?php epic_the_icon( 'arrow-right' ); ?></a>
					<?php if ( $a['secondary_label'] ) : ?>
						<a class="btn btn-ghost-light" href="<?php echo esc_url( (string) $a['secondary_url'] ); ?>"><?php echo esc_html( $a['secondary_label'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/** The "Stay informed" email sign-up band. */
function epic_subscribe_band(): void {
	?>
	<section class="section section-sm">
		<div class="container">
			<div class="cta-band">
				<div>
					<h2><?php echo esc_html( epic_setting( 'subscribe_title', 'Stay informed' ) ); ?></h2>
					<p><?php echo esc_html( epic_setting( 'subscribe_text', 'Get EPIC research, policy briefs, events and opportunities delivered to your inbox.' ) ); ?></p>
				</div>
				<form class="subscribe-inline" action="<?php echo esc_url( epic_page_url( 'subscribe' ) ); ?>" method="post" style="flex:1 1 320px;max-width:520px">
					<?php epic_form_fields( 'subscribe' ); ?>
					<input type="hidden" name="source" value="footer-band">
					<input type="email" name="email" placeholder="Your email address" required aria-label="Email address">
					<button class="btn btn-green" type="submit">Subscribe</button>
				</form>
			</div>
		</div>
	</section>
	<?php
}

/** The hidden fields every public form carries: which form it is, and the spam trap. */
function epic_form_fields( string $form ): void {
	echo '<input type="hidden" name="epic_form" value="' . esc_attr( $form ) . '">';
	echo '<input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">';
}

/** Success or error message left by the last form submission. */
function epic_flash(): void {
	$flash = Epic_Forms::flash();

	if ( empty( $flash['messages'] ) ) {
		return;
	}

	if ( 'success' === ( $flash['type'] ?? '' ) ) {
		echo '<div class="alert alert-success">';
		epic_the_icon( 'check' );
		echo '<div>' . esc_html( $flash['messages'][0] ) . '</div></div>';

		return;
	}

	echo '<div class="alert alert-error">';
	epic_the_icon( 'close' );
	echo '<div><strong>Please check the form:</strong><ul style="margin:.4rem 0 0;padding-left:1.1rem">';
	foreach ( $flash['messages'] as $message ) {
		echo '<li>' . esc_html( $message ) . '</li>';
	}
	echo '</ul></div></div>';
}

/** The value a form field held before an error, for refilling it. */
function epic_old( string $key ): string {
	return Epic_Forms::old( $key );
}

/** Previous / numbered / next links. */
function epic_pagination( Epic_Pager $pager ): void {
	if ( ! $pager->has_pages() ) {
		return;
	}

	$pages = array();
	if ( $pager->last <= 7 ) {
		$pages = range( 1, $pager->last );
	} else {
		$pages = array_unique( array_merge( array( 1, 2 ), range( max( 1, $pager->current - 2 ), min( $pager->last, $pager->current + 2 ) ), array( $pager->last - 1, $pager->last ) ) );
		sort( $pages );
	}
	?>
	<nav aria-label="Pagination">
		<ul class="pagination">
			<?php if ( $pager->current <= 1 ) : ?>
				<li class="disabled" aria-disabled="true"><span><?php epic_the_icon( 'arrow-left' ); ?></span></li>
			<?php else : ?>
				<li><a href="<?php echo esc_url( $pager->url( $pager->current - 1 ) ); ?>" rel="prev" aria-label="Previous page"><?php epic_the_icon( 'arrow-left' ); ?></a></li>
			<?php endif; ?>

			<?php
			$previous = 0;
			foreach ( $pages as $number ) {
				if ( $previous && $number - $previous > 1 ) {
					echo '<li class="disabled"><span>...</span></li>';
				}
				if ( $number === $pager->current ) {
					echo '<li class="active" aria-current="page"><span>' . (int) $number . '</span></li>';
				} else {
					echo '<li><a href="' . esc_url( $pager->url( $number ) ) . '">' . (int) $number . '</a></li>';
				}
				$previous = $number;
			}
			?>

			<?php if ( $pager->current < $pager->last ) : ?>
				<li><a href="<?php echo esc_url( $pager->url( $pager->current + 1 ) ); ?>" rel="next" aria-label="Next page"><?php epic_the_icon( 'arrow-right' ); ?></a></li>
			<?php else : ?>
				<li class="disabled" aria-disabled="true"><span><?php epic_the_icon( 'arrow-right' ); ?></span></li>
			<?php endif; ?>
		</ul>
	</nav>
	<?php
}

/** LinkedIn, X and email share links. */
function epic_share_row( string $title ): void {
	$url = get_permalink();
	?>
	<div class="share-row">
		<span>Share</span>
		<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode( $url ); ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><?php epic_the_icon( 'linkedin' ); ?></a>
		<a href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode( $url ); ?>&amp;text=<?php echo rawurlencode( $title ); ?>" target="_blank" rel="noopener" aria-label="Share on X"><?php epic_the_icon( 'x-social' ); ?></a>
		<a href="mailto:?subject=<?php echo rawurlencode( $title ); ?>&amp;body=<?php echo rawurlencode( $url ); ?>" aria-label="Share by email"><?php epic_the_icon( 'mail' ); ?></a>
	</div>
	<?php
}

/* ----------------------------------------------------------------- cards --- */

function epic_card_event( Epic_Item $event ): void {
	?>
	<article class="card event-card">
		<a class="event-thumb" href="<?php echo esc_url( $event->url ); ?>">
			<img src="<?php echo esc_url( epic_image( $event->image, 'card' ) ); ?>" alt="<?php echo esc_attr( $event->title ); ?>" loading="lazy"<?php echo epic_pic_style( $event->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		</a>
		<div class="event-main">
			<div class="event-date">
				<strong><?php echo esc_html( $event->day ); ?></strong>
				<span><?php echo esc_html( $event->month_year ); ?></span>
			</div>
			<div>
				<h3><a href="<?php echo esc_url( $event->url ); ?>"><?php echo esc_html( $event->title ); ?></a></h3>
				<?php if ( $event->excerpt ) : ?>
					<p><?php echo esc_html( epic_summarise( $event->excerpt, 110 ) ); ?></p>
				<?php endif; ?>
				<div class="card-meta">
					<?php if ( $event->city || $event->location ) : ?>
						<span><?php epic_the_icon( 'location' ); ?><?php echo esc_html( $event->city ?: $event->location ); ?></span>
					<?php endif; ?>
					<span><?php epic_the_icon( 'clock' ); ?><?php echo esc_html( $event->mode ); ?></span>
				</div>
			</div>
		</div>
	</article>
	<?php
}

function epic_card_post( Epic_Item $post ): void {
	?>
	<article class="card">
		<a class="card-media is-wide" href="<?php echo esc_url( $post->url ); ?>">
			<img src="<?php echo esc_url( epic_image( $post->image, 'card' ) ); ?>" alt="<?php echo esc_attr( $post->title ); ?>" loading="lazy"<?php echo epic_pic_style( $post->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<span class="badge badge-navy"><?php echo esc_html( $post->category_label ); ?></span>
		</a>
		<div class="card-body">
			<h3><a href="<?php echo esc_url( $post->url ); ?>"><?php echo esc_html( $post->title ); ?></a></h3>
			<p><?php echo esc_html( epic_summarise( $post->summary, 130 ) ); ?></p>
			<div class="card-meta">
				<?php if ( $post->published_at ) : ?>
					<span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $post->published_at->format( 'd M Y' ) ); ?></span>
				<?php endif; ?>
				<?php if ( $post->author ) : ?>
					<span><?php epic_the_icon( 'people' ); ?><?php echo esc_html( $post->author ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

function epic_card_project( Epic_Item $project ): void {
	$status = Epic_Schema::PROJECT_STATUSES[ $project->status ] ?? $project->status;
	?>
	<article class="card">
		<a class="card-media is-wide" href="<?php echo esc_url( $project->url ); ?>">
			<img src="<?php echo esc_url( epic_image( $project->image, 'card' ) ); ?>" alt="<?php echo esc_attr( $project->title ); ?>" loading="lazy"<?php echo epic_pic_style( $project->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<span class="badge <?php echo 'completed' === $project->status ? 'badge-outline' : 'badge-green'; ?>"><?php echo esc_html( $status ); ?></span>
		</a>
		<div class="card-body">
			<h3><a href="<?php echo esc_url( $project->url ); ?>"><?php echo esc_html( $project->title ); ?></a></h3>
			<p><?php echo esc_html( epic_summarise( $project->excerpt ?: $project->body, 140 ) ); ?></p>
			<div class="card-meta">
				<?php if ( $project->category ) : ?>
					<span><?php epic_the_icon( 'layers' ); ?><?php echo esc_html( $project->category ); ?></span>
				<?php endif; ?>
				<?php if ( $project->started_at ) : ?>
					<span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $project->started_at->format( 'Y' ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

function epic_card_publication( Epic_Item $publication ): void {
	?>
	<article class="card pub-card">
		<a class="card-media" href="<?php echo esc_url( $publication->url ); ?>">
			<img src="<?php echo esc_url( epic_image( $publication->cover_image, 'portrait' ) ); ?>" alt="<?php echo esc_attr( $publication->title ); ?>" loading="lazy"<?php echo epic_pic_style( $publication->cover_image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		</a>
		<div class="card-body">
			<h3><a href="<?php echo esc_url( $publication->url ); ?>"><?php echo esc_html( $publication->title ); ?></a></h3>
			<?php if ( $publication->subtitle ) : ?>
				<p class="pub-sub"><?php echo esc_html( $publication->subtitle ); ?></p>
			<?php endif; ?>
			<div class="card-meta">
				<span class="badge badge-blue"><?php echo esc_html( $publication->type ); ?></span>
				<?php if ( $publication->published_at ) : ?>
					<span><?php epic_the_icon( 'calendar' ); ?><?php echo esc_html( $publication->published_at->format( 'M Y' ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

/* -------------------------------------------------- page content sections --- */

/** The flexible content blocks an editor attaches to a page, in order. */
function epic_sections( Epic_Item $page ): void {
	$i = 0;

	foreach ( $page->sections as $block ) {
		++$i;
		$even  = 0 === $i % 2;
		$odd   = ! $even;
		$soft  = $even ? 'section-soft' : '';
		$items = $block->list_group ? Epic_Data::list_items( (string) $block->list_group ) : array();

		switch ( $block->type ) {
			case 'text':
				?>
				<section class="section <?php echo esc_attr( $soft ); ?>">
					<div class="container container-narrow">
						<?php if ( $block->title ) : ?><h2 class="section-title"><?php echo esc_html( $block->title ); ?></h2><?php endif; ?>
						<?php if ( $block->subheading ) : ?><p class="lead"><?php echo esc_html( $block->subheading ); ?></p><?php endif; ?>
						<div class="prose"><?php echo epic_content( $block->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						<?php if ( $block->link_text ) : ?>
							<a class="btn btn-primary mt-3" href="<?php echo esc_url( epic_resolved_url( $block->link_url ?: '#' ) ); ?>"><?php echo esc_html( $block->link_text ); ?> <?php epic_the_icon( 'arrow-right' ); ?></a>
						<?php endif; ?>
					</div>
				</section>
				<?php
				break;

			case 'list':
				?>
				<section class="section <?php echo esc_attr( $soft ); ?>">
					<div class="container">
						<?php if ( $block->title ) : ?><h2 class="section-title"><?php echo esc_html( $block->title ); ?></h2><?php endif; ?>
						<?php if ( $block->subheading ) : ?><p class="lead" style="max-width:70ch"><?php echo esc_html( $block->subheading ); ?></p><?php endif; ?>
						<div class="mt-4"><?php epic_check_list( $items, 2 ); ?></div>
					</div>
				</section>
				<?php
				break;

			case 'cards':
				?>
				<section class="section <?php echo esc_attr( $soft ); ?>">
					<div class="container">
						<?php if ( $block->title ) : ?><h2 class="section-title"><?php echo esc_html( $block->title ); ?></h2><?php endif; ?>
						<?php if ( $block->subheading ) : ?><p class="lead" style="max-width:70ch"><?php echo esc_html( $block->subheading ); ?></p><?php endif; ?>
						<div class="grid grid-3 mt-4">
							<?php foreach ( $items as $item ) : ?>
								<div class="card" style="padding:24px">
									<div class="principle-icon" style="margin-bottom:14px"><?php epic_the_icon( $item->icon ?: 'sparkle' ); ?></div>
									<h3 style="font-size:1.12rem"><?php echo esc_html( $item->title ); ?></h3>
									<p style="font-size:.92rem;margin:0"><?php echo esc_html( $item->description ); ?></p>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>
				<?php
				break;

			case 'image_text':
				?>
				<section class="section <?php echo esc_attr( $soft ); ?>">
					<div class="container">
						<div class="split <?php echo $odd ? 'is-reverse' : ''; ?>">
							<div class="split-media">
								<img src="<?php echo esc_url( epic_image( $block->image, 'card' ) ); ?>" alt="<?php echo esc_attr( $block->title ); ?>" loading="lazy"<?php echo epic_pic_style( $block->image ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							</div>
							<div>
								<?php if ( $block->title ) : ?><h2 class="section-title"><?php echo esc_html( $block->title ); ?></h2><?php endif; ?>
								<?php if ( $block->subheading ) : ?><p class="lead"><?php echo esc_html( $block->subheading ); ?></p><?php endif; ?>
								<div class="prose"><?php echo epic_content( $block->body ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
								<?php if ( $block->link_text ) : ?>
									<a class="btn btn-primary mt-3" href="<?php echo esc_url( epic_resolved_url( $block->link_url ?: '#' ) ); ?>"><?php echo esc_html( $block->link_text ); ?> <?php epic_the_icon( 'arrow-right' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</section>
				<?php
				break;

			case 'quote':
				?>
				<section class="section section-sm">
					<div class="container container-narrow text-center">
						<?php epic_the_icon( 'quote', 'icon', null ); ?>
						<blockquote style="border:0;font-size:1.5rem;text-align:center;padding:0;margin:14px 0 0"><?php echo esc_html( trim( wp_strip_all_tags( (string) $block->body ) ) ?: $block->title ); ?></blockquote>
						<?php if ( $block->subheading ) : ?><p class="text-muted mt-3"><?php echo esc_html( $block->subheading ); ?></p><?php endif; ?>
					</div>
				</section>
				<?php
				break;

			case 'accordion':
				?>
				<section class="section <?php echo esc_attr( $soft ); ?>">
					<div class="container container-narrow">
						<?php if ( $block->title ) : ?><h2 class="section-title"><?php echo esc_html( $block->title ); ?></h2><?php endif; ?>
						<div class="accordion mt-4" data-single>
							<?php foreach ( array_values( $items ) as $n => $item ) : ?>
								<div class="accordion-item <?php echo 0 === $n ? 'is-open' : ''; ?>">
									<button class="accordion-trigger" type="button" aria-expanded="<?php echo 0 === $n ? 'true' : 'false'; ?>">
										<?php echo esc_html( $item->title ); ?> <?php epic_the_icon( 'chevron-down' ); ?>
									</button>
									<div class="accordion-panel"><?php echo epic_rich( $item->description ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</section>
				<?php
				break;
		}
		// 'cta' blocks are rendered by the page's call-to-action band.
	}
}
