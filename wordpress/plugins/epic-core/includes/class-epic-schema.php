<?php
/**
 * The single description of the site's content: every content type and its fields,
 * every built-in page, and every site setting.
 *
 * The edit screens, the data layer, the importer and the admin lists are all
 * generated from this file, which mirrors the models and dashboard resources of
 * the Laravel site this plugin replaces.
 *
 * Field `store` says where a value lives:
 *   meta        a post meta row named epic_{key}            (the default)
 *   content     the post body (the main editor)
 *   excerpt     post_excerpt
 *   menu_order  menu_order
 *   parent      post_parent (a page picker)
 * Published / hidden is always the post status: Draft hides an item.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Schema {

	const LIST_GROUPS = array(
		'principles'               => 'Who We Are — EPIC Principles',
		'strengths'                => 'Who We Are — Our Strengths',
		'board_areas'              => 'Who We Are — Board of Directors areas',
		'advisory_areas'           => 'Who We Are — Advisory Council guidance',
		'themes'                   => 'What We Do — Themes of EPIC work',
		'project_types'            => 'What We Do — Types of projects',
		'event_types'              => 'Events — Types of events',
		'partner_types'            => 'Partnerships — Who we work with',
		'mou_scope'                => 'Partnerships — MoU partnership areas',
		'memberships'              => 'Partnerships — Membership networks',
		'entrepreneurship_pillars' => 'Home — Entrepreneurship pillars',
		'get_involved'             => 'Get Involved — Ways to engage',
		'publication_types'        => 'Publications — Our collection types',
	);

	const SECTION_TYPES = array(
		'text'             => 'Text block',
		'list'             => 'Checklist from a content list',
		'cards'            => 'Icon cards from a content list',
		'image_text'       => 'Image + text',
		'quote'            => 'Quote',
		'accordion'        => 'Accordion (FAQ style)',
		'cta'              => 'Call to action band',
		'focus'            => 'Home: focus areas heading',
		'publications'     => 'Home: publications heading',
		'events'           => 'Home: events heading',
		'entrepreneurship' => 'Home: entrepreneurship band',
		'stats'            => 'Home: data & insights heading',
		'vision'           => 'Vision block',
		'mission'          => 'Mission block',
	);

	const PUBLICATION_TYPES = array(
		'Research Report'  => 'Research Report',
		'Policy Brief'     => 'Policy Brief',
		'Research Paper'   => 'Research Paper',
		'Working Paper'    => 'Working Paper',
		'Book'             => 'Book',
		'Journal Article'  => 'Journal Article',
		'Journal Issue'    => 'Journal Issue',
		'E-Newsletter'     => 'E-Newsletter',
		'Discussion Paper' => 'Discussion Paper',
	);

	const COLLECTIONS = array(
		'collection' => 'Our Collection',
		'journal'    => 'Journal (HEC-recognized)',
		'newsletter' => 'E-Newsletter',
	);

	const POST_CATEGORIES = array(
		'blog'          => 'Blog',
		'article'       => 'Article',
		'press_release' => 'Press Release',
	);

	const EVENT_MODES = array(
		'In-Person'    => 'In-Person',
		'Hybrid Event' => 'Hybrid Event',
		'Online'       => 'Online',
	);

	const PROJECT_STATUSES = array(
		'ongoing'   => 'Ongoing',
		'completed' => 'Completed',
		'upcoming'  => 'Upcoming',
	);

	const CHAPTER_STATUSES = array(
		'active'  => 'Active',
		'forming' => 'Forming',
	);

	const PARTNER_TYPES = array(
		'partnership' => 'Partnership',
		'mou'         => 'MoU',
		'membership'  => 'Membership',
	);

	const CAREER_TYPES = array(
		'Full Time'   => 'Full Time',
		'Part Time'   => 'Part Time',
		'Internship'  => 'Internship',
		'Consultancy' => 'Consultancy',
		'Fellowship'  => 'Fellowship',
		'Volunteer'   => 'Volunteer',
	);

	const SUBSCRIBER_STATUSES = array(
		'subscribed'   => 'Subscribed',
		'unsubscribed' => 'Unsubscribed',
	);

	/** @var array<string, array>|null */
	private static $types = null;

	/* ------------------------------------------------------------ helpers --- */

	/** A field definition. */
	private static function f( string $key, string $label, string $type = 'text', array $extra = array() ): array {
		return array(
			'key'   => $key,
			'label' => $label,
			'type'  => $type,
		) + $extra;
	}

	/** A heading that divides the fields into groups. */
	private static function s( string $label ): array {
		return array(
			'type'  => 'section',
			'label' => $label,
		);
	}

	/**
	 * The three people groups take their names from the titles of their pages, so
	 * renaming a page renames the group as well (as on the Laravel site).
	 *
	 * @return array<string,string>
	 */
	public static function team_categories(): array {
		$defaults = array(
			'team'     => 'EPIC Team',
			'board'    => 'Board of Directors',
			'advisory' => 'Advisory Council',
		);
		$keys     = array(
			'team'     => 'epic-team',
			'board'    => 'board',
			'advisory' => 'advisory-council',
		);

		foreach ( $keys as $category => $page_key ) {
			$title = Epic_Data::page_title_by_key( $page_key );
			if ( '' !== $title ) {
				$defaults[ $category ] = $title;
			}
		}

		return $defaults;
	}

	/* -------------------------------------------------------------- types --- */

	/**
	 * @return array<string, array>
	 */
	public static function types(): array {
		if ( null !== self::$types ) {
			return self::$types;
		}

		$order_field = self::f( 'sort', 'Display order', 'number', array(
			'store'   => 'menu_order',
			'col'     => 4,
			'default' => 0,
		) );

		self::$types = array(

			'epic_section'     => array(
				'table'       => 'page_sections',
				'label'       => 'Page sections',
				'singular'    => 'Section',
				'description' => 'Extra content blocks that appear on a page, in the order you set. Home page blocks use the reserved types focus, publications, events, entrepreneurship and stats to set those section headings and links.',
				'icon'        => 'dashicons-layout',
				'public'      => false,
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Heading',
				'body_label'  => 'Body',
				'orderby'     => array(
					'post_parent' => 'ASC',
					'menu_order'  => 'ASC',
				),
				'fields'      => array(
					self::s( 'Placement' ),
					self::f( 'page', 'Page', 'page', array(
						'store' => 'parent',
						'col'   => 6,
					) ),
					self::f( 'type', 'Block type', 'select', array(
						'options' => self::SECTION_TYPES,
						'default' => 'text',
						'col'     => 6,
						'filter'  => true,
					) ),
					$order_field,
					self::s( 'Content' ),
					self::f( 'subheading', 'Sub heading / quote', 'textarea', array(
						'col'  => 6,
						'rows' => 3,
					) ),
					self::f( 'image', 'Image', 'image', array( 'col' => 6 ) ),
					self::f( 'list_group', 'Content list to display', 'select', array(
						'options'     => self::LIST_GROUPS,
						'placeholder' => 'None',
						'col'         => 6,
						'hint'        => 'Used by the checklist, cards and accordion block types.',
					) ),
					self::s( 'Link' ),
					self::f( 'link_text', 'Link label', 'text', array( 'col' => 6 ) ),
					self::f( 'link_url', 'Link URL', 'url', array( 'col' => 6 ) ),
				),
				'columns'     => array(
					array(
						'field' => 'page',
						'label' => 'Page',
						'kind'  => 'page',
					),
					array(
						'field' => 'type',
						'label' => 'Type',
						'kind'  => 'badge',
						'map'   => self::SECTION_TYPES,
					),
					array(
						'field' => 'menu_order',
						'label' => 'Order',
						'kind'  => 'order',
					),
				),
			),

			'epic_item'        => array(
				'table'       => 'list_items',
				'label'       => 'Content lists',
				'singular'    => 'List item',
				'description' => 'Reusable lists: principles, strengths, themes, project types, event types, partnership areas and more. The list an item belongs to decides where it appears on the website.',
				'icon'        => 'dashicons-editor-ul',
				'public'      => false,
				'supports'    => array( 'title' ),
				'title_label' => 'Title',
				'orderby'     => array(
					'epic_group' => 'ASC',
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
				'fields'      => array(
					self::f( 'group', 'List', 'select', array(
						'options' => self::LIST_GROUPS,
						'col'     => 6,
						'filter'  => true,
						'default' => 'principles',
					) ),
					self::f( 'icon', 'Icon', 'icon', array( 'col' => 6 ) ),
					self::f( 'description', 'Description', 'textarea', array(
						'col'  => 12,
						'rows' => 4,
					) ),
					self::f( 'image', 'Image', 'image', array( 'col' => 6 ) ),
					self::f( 'url', 'Link', 'url', array( 'col' => 6 ) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'group',
						'label' => 'List',
						'kind'  => 'badge',
						'map'   => self::LIST_GROUPS,
					),
					array(
						'field' => 'icon',
						'label' => 'Icon',
						'kind'  => 'icon',
					),
					array(
						'field' => 'menu_order',
						'label' => 'Order',
						'kind'  => 'order',
					),
				),
			),

			'epic_focus'       => array(
				'table'       => 'focus_areas',
				'label'       => 'Focus areas',
				'singular'    => 'Focus area',
				'description' => 'The icon strip shown near the top of the home page.',
				'icon'        => 'dashicons-star-filled',
				'public'      => false,
				'supports'    => array( 'title' ),
				'title_label' => 'Title',
				'orderby'     => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
				'fields'      => array(
					self::f( 'icon', 'Icon', 'icon', array(
						'col'     => 6,
						'default' => 'chart',
					) ),
					self::f( 'color', 'Icon colour', 'select', array(
						'options' => array(
							'navy'  => 'Blue',
							'green' => 'Green',
						),
						'default' => 'navy',
						'col'     => 6,
					) ),
					self::f( 'description', 'Short description', 'textarea', array(
						'col'  => 12,
						'rows' => 2,
					) ),
					self::f( 'url', 'Link', 'url', array(
						'col'         => 6,
						'placeholder' => '/what-we-do/themes',
					) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'icon',
						'label' => 'Icon',
						'kind'  => 'icon',
					),
					array(
						'field' => 'menu_order',
						'label' => 'Order',
						'kind'  => 'order',
					),
				),
			),

			'epic_stat'        => array(
				'table'       => 'stats',
				'label'       => 'Data & insights',
				'singular'    => 'Statistic',
				'description' => 'The headline figures shown in the Data & Insights strip on the home page. The title is the small label above the figure.',
				'icon'        => 'dashicons-chart-bar',
				'public'      => false,
				'supports'    => array( 'title' ),
				'title_label' => 'Label (e.g. GDP GROWTH (REAL))',
				'orderby'     => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
				'fields'      => array(
					self::f( 'value', 'Value', 'text', array(
						'col'         => 4,
						'placeholder' => '2.4%',
					) ),
					self::f( 'caption', 'Caption', 'text', array(
						'col'         => 5,
						'placeholder' => 'Pakistan | FY 2023',
					) ),
					self::f( 'icon', 'Icon', 'icon', array(
						'col'     => 3,
						'default' => 'chart',
					) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'value',
						'label' => 'Value',
						'kind'  => 'text',
					),
					array(
						'field' => 'menu_order',
						'label' => 'Order',
						'kind'  => 'order',
					),
				),
			),

			'epic_member'      => array(
				'table'       => 'team_members',
				'label'       => 'Team & councils',
				'singular'    => 'Member',
				'description' => 'EPIC team, Board of Directors and Advisory Council profiles.',
				'icon'        => 'dashicons-groups',
				'public'      => false,
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Full name',
				'body_label'  => 'Full biography',
				'orderby'     => array(
					'epic_category' => 'ASC',
					'menu_order'    => 'ASC',
					'ID'            => 'ASC',
				),
				'fields'      => array(
					self::f( 'designation', 'Designation', 'text', array( 'col' => 6 ) ),
					self::f( 'category', 'Group', 'select', array(
						'options' => 'team_categories',
						'default' => 'team',
						'col'     => 6,
						'filter'  => true,
					) ),
					self::f( 'country', 'Country', 'text', array( 'col' => 6 ) ),
					self::f( 'photo', 'Photograph', 'image', array(
						'col'    => 6,
						'aspect' => '1 / 1',
						'hint'   => 'Square images look best.',
					) ),
					self::f( 'short_bio', 'Short bio', 'textarea', array(
						'col'  => 12,
						'rows' => 4,
					) ),
					self::f( 'email', 'Email', 'email', array( 'col' => 4 ) ),
					self::f( 'linkedin', 'LinkedIn URL', 'url', array( 'col' => 4 ) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'photo',
						'label' => 'Photo',
						'kind'  => 'image',
					),
					array(
						'field' => 'designation',
						'label' => 'Designation',
						'kind'  => 'text',
					),
					array(
						'field' => 'category',
						'label' => 'Group',
						'kind'  => 'badge',
						'map'   => 'team_categories',
					),
					array(
						'field' => 'menu_order',
						'label' => 'Order',
						'kind'  => 'order',
					),
				),
			),

			'epic_project'     => array(
				'table'       => 'projects',
				'label'       => 'Projects',
				'singular'    => 'Project',
				'description' => 'Research, policy, capacity-building and development projects.',
				'icon'        => 'dashicons-portfolio',
				'public'      => true,
				'rewrite'     => 'what-we-do/projects',
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Title',
				'body_label'  => 'Full description',
				'orderby'     => array(
					'menu_order' => 'ASC',
					'ID'         => 'DESC',
				),
				'fields'      => array(
					self::s( 'Details' ),
					self::f( 'category', 'Theme', 'text', array(
						'col'         => 6,
						'placeholder' => 'Human Capital Development',
					) ),
					self::f( 'status', 'Status', 'select', array(
						'options' => self::PROJECT_STATUSES,
						'default' => 'ongoing',
						'col'     => 6,
						'filter'  => true,
					) ),
					self::f( 'started_at', 'Start date', 'date', array( 'col' => 6 ) ),
					self::f( 'ended_at', 'End date', 'date', array( 'col' => 6 ) ),
					self::f( 'partners', 'Partners', 'text', array( 'col' => 12 ) ),
					self::s( 'Content' ),
					self::f( 'summary', 'Short summary', 'textarea', array(
						'store' => 'excerpt',
						'col'   => 12,
						'rows'  => 3,
					) ),
					self::f( 'image', 'Project image', 'image', array( 'col' => 6 ) ),
					self::s( 'Visibility' ),
					self::f( 'is_featured', 'Featured', 'checkbox', array( 'col' => 4 ) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'image',
						'label' => 'Image',
						'kind'  => 'image',
					),
					array(
						'field' => 'status',
						'label' => 'Status',
						'kind'  => 'badge',
						'map'   => self::PROJECT_STATUSES,
					),
				),
			),

			'epic_chapter'     => array(
				'table'       => 'chapters',
				'label'       => 'International chapters',
				'singular'    => 'Chapter',
				'description' => 'EPIC chapters and country presence. The title is the country.',
				'icon'        => 'dashicons-admin-site-alt3',
				'public'      => false,
				'supports'    => array( 'title' ),
				'title_label' => 'Country',
				'orderby'     => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'fields'      => array(
					self::f( 'city', 'City', 'text', array( 'col' => 6 ) ),
					self::f( 'status', 'Status', 'select', array(
						'options' => self::CHAPTER_STATUSES,
						'default' => 'active',
						'col'     => 6,
					) ),
					self::f( 'description', 'Description', 'textarea', array(
						'col'  => 12,
						'rows' => 3,
					) ),
					self::f( 'image', 'Flag or image', 'image', array(
						'col'    => 6,
						'adjust' => false,
					) ),
					self::f( 'contact_name', 'Contact name', 'text', array( 'col' => 3 ) ),
					self::f( 'contact_email', 'Contact email', 'email', array( 'col' => 3 ) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'city',
						'label' => 'City',
						'kind'  => 'text',
					),
					array(
						'field' => 'status',
						'label' => 'Status',
						'kind'  => 'badge',
						'map'   => self::CHAPTER_STATUSES,
					),
				),
			),

			'epic_partner'     => array(
				'table'       => 'partners',
				'label'       => 'Partners & MoUs',
				'singular'    => 'Partner',
				'description' => 'Partner organisations, signed MoUs and network memberships. The title is the organisation name.',
				'icon'        => 'dashicons-networking',
				'public'      => false,
				'supports'    => array( 'title' ),
				'title_label' => 'Organisation name',
				'orderby'     => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'fields'      => array(
					self::f( 'type', 'Listed under', 'select', array(
						'options' => self::PARTNER_TYPES,
						'default' => 'partnership',
						'col'     => 6,
						'filter'  => true,
					) ),
					self::f( 'category', 'Category', 'text', array(
						'col'         => 6,
						'placeholder' => 'University · Think tank · Government',
					) ),
					self::f( 'country', 'Country', 'text', array( 'col' => 6 ) ),
					self::f( 'website', 'Website', 'url', array( 'col' => 6 ) ),
					self::f( 'description', 'Description', 'textarea', array(
						'col'  => 12,
						'rows' => 3,
					) ),
					self::f( 'logo', 'Logo', 'image', array(
						'col'    => 6,
						'adjust' => false,
					) ),
					self::f( 'signed_on', 'MoU signed on', 'date', array( 'col' => 3 ) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'logo',
						'label' => 'Logo',
						'kind'  => 'image',
					),
					array(
						'field' => 'type',
						'label' => 'Type',
						'kind'  => 'badge',
						'map'   => self::PARTNER_TYPES,
					),
					array(
						'field' => 'category',
						'label' => 'Category',
						'kind'  => 'text',
					),
				),
			),

			'epic_career'      => array(
				'table'       => 'careers',
				'label'       => 'Careers',
				'singular'    => 'Vacancy',
				'description' => 'Jobs, internships, fellowships and consultancy opportunities. Closed vacancies stay online, marked as closed, until you delete them.',
				'icon'        => 'dashicons-businessperson',
				'public'      => true,
				'rewrite'     => 'get-involved/careers',
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Job title',
				'body_label'  => 'Role description',
				'orderby'     => array(
					'menu_order' => 'ASC',
					'ID'         => 'DESC',
				),
				'fields'      => array(
					self::f( 'type', 'Type', 'select', array(
						'options' => self::CAREER_TYPES,
						'default' => 'Full Time',
						'col'     => 4,
						'filter'  => true,
					) ),
					self::f( 'location', 'Location', 'text', array(
						'col'         => 4,
						'placeholder' => 'Islamabad / Remote',
					) ),
					self::f( 'deadline', 'Application deadline', 'date', array( 'col' => 4 ) ),
					self::f( 'summary', 'Short summary', 'textarea', array(
						'store' => 'excerpt',
						'col'   => 12,
						'rows'  => 3,
					) ),
					self::f( 'requirements', 'Requirements', 'richtext', array( 'col' => 12 ) ),
					self::s( 'How to apply' ),
					self::f( 'apply_url', 'Application link', 'url', array( 'col' => 4 ) ),
					self::f( 'apply_email', 'Application email', 'email', array( 'col' => 4 ) ),
					self::f( 'is_open', 'Open for applications', 'checkbox', array(
						'col'     => 2,
						'default' => true,
					) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'type',
						'label' => 'Type',
						'kind'  => 'badge',
						'map'   => self::CAREER_TYPES,
					),
					array(
						'field' => 'location',
						'label' => 'Location',
						'kind'  => 'text',
					),
					array(
						'field' => 'deadline',
						'label' => 'Deadline',
						'kind'  => 'date',
					),
					array(
						'field' => 'is_open',
						'label' => 'Open',
						'kind'  => 'bool',
					),
				),
			),

			'epic_publication' => array(
				'table'       => 'publications',
				'label'       => 'Publications',
				'singular'    => 'Publication',
				'description' => 'Research reports, policy briefs, working papers, journal issues and newsletters.',
				'icon'        => 'dashicons-book-alt',
				'public'      => true,
				'rewrite'     => 'publications',
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Title',
				'body_label'  => 'Full text',
				'orderby'     => array(
					'epic_published_at' => 'DESC',
					'ID'                => 'DESC',
				),
				'fields'      => array(
					self::s( 'Details' ),
					self::f( 'subtitle', 'Subtitle', 'text', array( 'col' => 12 ) ),
					self::f( 'type', 'Type', 'select', array(
						'options' => self::PUBLICATION_TYPES,
						'default' => 'Research Report',
						'col'     => 4,
						'filter'  => true,
					) ),
					self::f( 'collection', 'Collection', 'select', array(
						'options' => self::COLLECTIONS,
						'default' => 'collection',
						'col'     => 4,
						'filter'  => true,
					) ),
					self::f( 'published_at', 'Publication date', 'date', array( 'col' => 4 ) ),
					self::f( 'authors', 'Author(s)', 'text', array( 'col' => 6 ) ),
					self::f( 'theme', 'Theme', 'text', array(
						'col'         => 3,
						'placeholder' => 'Human Capital',
					) ),
					self::f( 'issue', 'Volume / issue', 'text', array(
						'col'         => 3,
						'placeholder' => 'Vol 1, Issue 2',
					) ),
					self::s( 'Content' ),
					self::f( 'abstract', 'Abstract / summary', 'textarea', array(
						'store' => 'excerpt',
						'col'   => 12,
						'rows'  => 4,
					) ),
					self::s( 'Files' ),
					self::f( 'cover_image', 'Cover image', 'image', array(
						'col'    => 6,
						'aspect' => '3 / 4',
						'hint'   => 'Portrait works best (3:4).',
					) ),
					self::f( 'file_path', 'PDF file', 'file', array( 'col' => 6 ) ),
					self::f( 'external_url', 'External link', 'url', array(
						'col'  => 12,
						'hint' => 'Used when no PDF is uploaded.',
					) ),
					self::s( 'Visibility' ),
					self::f( 'is_featured', 'Feature on the home page', 'checkbox', array( 'col' => 6 ) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'cover_image',
						'label' => 'Cover',
						'kind'  => 'image',
					),
					array(
						'field' => 'type',
						'label' => 'Type',
						'kind'  => 'badge',
						'map'   => self::PUBLICATION_TYPES,
					),
					array(
						'field' => 'collection',
						'label' => 'Collection',
						'kind'  => 'badge',
						'map'   => self::COLLECTIONS,
					),
					array(
						'field' => 'published_at',
						'label' => 'Published',
						'kind'  => 'date',
					),
				),
			),

			'epic_post'        => array(
				'table'       => 'posts',
				'label'       => 'Blogs & press',
				'singular'    => 'Post',
				'description' => 'Blogs, articles and press releases. Press releases appear under Media → Press Releases; blogs and articles under Publications → Blogs & Articles.',
				'icon'        => 'dashicons-edit-page',
				'public'      => true,
				'rewrite'     => false, // Two address bases, see Epic_Types::post_rules().
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Title',
				'body_label'  => 'Body',
				'orderby'     => array(
					'epic_published_at' => 'DESC',
					'ID'                => 'DESC',
				),
				'fields'      => array(
					self::s( 'Details' ),
					self::f( 'category', 'Category', 'select', array(
						'options' => self::POST_CATEGORIES,
						'default' => 'blog',
						'col'     => 4,
						'filter'  => true,
					) ),
					self::f( 'author', 'Author', 'text', array( 'col' => 4 ) ),
					self::f( 'published_at', 'Publication date', 'date', array( 'col' => 4 ) ),
					self::f( 'tags', 'Tags', 'text', array(
						'col'         => 12,
						'placeholder' => 'Human capital, Skills, Youth',
					) ),
					self::s( 'Content' ),
					self::f( 'excerpt', 'Short summary', 'textarea', array(
						'store' => 'excerpt',
						'col'   => 12,
						'rows'  => 3,
					) ),
					self::f( 'image', 'Featured image', 'image', array( 'col' => 6 ) ),
					self::s( 'Visibility' ),
					self::f( 'is_featured', 'Featured', 'checkbox', array( 'col' => 6 ) ),
				),
				'columns'     => array(
					array(
						'field' => 'image',
						'label' => 'Image',
						'kind'  => 'image',
					),
					array(
						'field' => 'category',
						'label' => 'Category',
						'kind'  => 'badge',
						'map'   => self::POST_CATEGORIES,
					),
					array(
						'field' => 'published_at',
						'label' => 'Published',
						'kind'  => 'date',
					),
				),
			),

			'epic_event'       => array(
				'table'       => 'events',
				'label'       => 'Events',
				'singular'    => 'Event',
				'description' => 'Policy dialogues, roundtables, seminars, webinars and launches.',
				'icon'        => 'dashicons-calendar-alt',
				'public'      => true,
				'rewrite'     => 'events',
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Title',
				'body_label'  => 'Full description',
				'orderby'     => array(
					'epic_starts_at' => 'DESC',
					'ID'             => 'DESC',
				),
				'fields'      => array(
					self::s( 'Details' ),
					self::f( 'event_type', 'Event type', 'text', array(
						'col'         => 6,
						'placeholder' => 'Policy Dialogue',
					) ),
					self::f( 'mode', 'Format', 'select', array(
						'options' => self::EVENT_MODES,
						'default' => 'In-Person',
						'col'     => 6,
						'filter'  => true,
					) ),
					self::f( 'starts_at', 'Starts at', 'datetime', array( 'col' => 6 ) ),
					self::f( 'ends_at', 'Ends at', 'datetime', array( 'col' => 6 ) ),
					self::f( 'city', 'City', 'text', array(
						'col'         => 6,
						'placeholder' => 'Islamabad',
					) ),
					self::f( 'location', 'Venue', 'text', array( 'col' => 6 ) ),
					self::s( 'Content' ),
					self::f( 'excerpt', 'Short summary', 'textarea', array(
						'store' => 'excerpt',
						'col'   => 12,
						'rows'  => 3,
					) ),
					self::f( 'image', 'Event image', 'image', array( 'col' => 6 ) ),
					self::f( 'registration_url', 'Registration link', 'url', array( 'col' => 6 ) ),
					self::s( 'Visibility' ),
					self::f( 'is_featured', 'Featured', 'checkbox', array( 'col' => 6 ) ),
				),
				'columns'     => array(
					array(
						'field' => 'image',
						'label' => 'Image',
						'kind'  => 'image',
					),
					array(
						'field' => 'starts_at',
						'label' => 'Starts',
						'kind'  => 'datetime',
					),
					array(
						'field' => 'city',
						'label' => 'City',
						'kind'  => 'text',
					),
					array(
						'field' => 'mode',
						'label' => 'Format',
						'kind'  => 'badge',
						'map'   => self::EVENT_MODES,
					),
				),
			),

			'epic_podcast'     => array(
				'table'       => 'podcasts',
				'label'       => 'Podcast episodes',
				'singular'    => 'Episode',
				'description' => 'EPIC podcast episodes, with an audio file or an embed from your podcast host.',
				'icon'        => 'dashicons-microphone',
				'public'      => true,
				'rewrite'     => 'media/podcast',
				'supports'    => array( 'title', 'editor' ),
				'title_label' => 'Title',
				'body_label'  => 'Description',
				'orderby'     => array(
					'epic_published_at' => 'DESC',
					'ID'                => 'DESC',
				),
				'fields'      => array(
					self::f( 'episode_number', 'Episode number', 'text', array( 'col' => 3 ) ),
					self::f( 'guest', 'Guest(s)', 'text', array( 'col' => 5 ) ),
					self::f( 'duration', 'Duration', 'text', array(
						'col'         => 2,
						'placeholder' => '32 min',
					) ),
					self::f( 'published_at', 'Published on', 'date', array( 'col' => 2 ) ),
					self::s( 'Media' ),
					self::f( 'cover_image', 'Cover image', 'image', array( 'col' => 6 ) ),
					self::f( 'audio_url', 'Audio file', 'file', array( 'col' => 6 ) ),
					self::f( 'embed_url', 'Embed URL', 'url', array(
						'col'  => 12,
						'hint' => 'Spotify, Anchor, YouTube or SoundCloud embed link. Used instead of the audio file when set.',
					) ),
				),
				'columns'     => array(
					array(
						'field' => 'cover_image',
						'label' => 'Cover',
						'kind'  => 'image',
					),
					array(
						'field' => 'episode_number',
						'label' => 'Episode',
						'kind'  => 'text',
					),
					array(
						'field' => 'published_at',
						'label' => 'Published',
						'kind'  => 'date',
					),
				),
			),

			'epic_video'       => array(
				'table'       => 'videos',
				'label'       => 'Videos',
				'singular'    => 'Video',
				'description' => 'YouTube videos shown on the Media → YouTube page.',
				'icon'        => 'dashicons-video-alt3',
				'public'      => false,
				'supports'    => array( 'title' ),
				'title_label' => 'Title',
				'orderby'     => array(
					'menu_order' => 'ASC',
					'epic_published_at' => 'DESC',
				),
				'fields'      => array(
					self::f( 'youtube_url', 'YouTube link', 'url', array(
						'col'         => 8,
						'placeholder' => 'https://www.youtube.com/watch?v=…',
						'hint'        => 'Any YouTube link works: watch, share or embed.',
					) ),
					self::f( 'published_at', 'Published on', 'date', array( 'col' => 4 ) ),
					self::f( 'description', 'Description', 'textarea', array(
						'col'  => 12,
						'rows' => 3,
					) ),
					self::f( 'thumbnail', 'Custom thumbnail', 'image', array(
						'col'  => 6,
						'hint' => 'Optional — the YouTube thumbnail is used automatically.',
					) ),
					self::f( 'is_featured', 'Show as the main video', 'checkbox', array( 'col' => 4 ) ),
					$order_field,
				),
				'columns'     => array(
					array(
						'field' => 'youtube_url',
						'label' => 'Link',
						'kind'  => 'text',
					),
					array(
						'field' => 'is_featured',
						'label' => 'Main',
						'kind'  => 'bool',
					),
				),
			),

			'epic_album'       => array(
				'table'       => 'gallery_albums',
				'label'       => 'Photo gallery',
				'singular'    => 'Album',
				'description' => 'Photo albums. Add the photos in the Photos box on each album.',
				'icon'        => 'dashicons-format-gallery',
				'public'      => true,
				'rewrite'     => 'media/gallery',
				'supports'    => array( 'title' ),
				'title_label' => 'Album title',
				'orderby'     => array(
					'menu_order'      => 'ASC',
					'epic_event_date' => 'DESC',
				),
				'fields'      => array(
					self::f( 'description', 'Description', 'textarea', array(
						'store' => 'excerpt',
						'col'   => 12,
						'rows'  => 3,
					) ),
					self::f( 'cover_image', 'Cover image', 'image', array(
						'col'  => 6,
						'hint' => 'Optional — the first photo is used otherwise.',
					) ),
					self::f( 'location', 'Location', 'text', array( 'col' => 3 ) ),
					self::f( 'event_date', 'Date', 'date', array( 'col' => 3 ) ),
					$order_field,
					self::s( 'Photos' ),
					self::f( 'images', 'Photos', 'gallery', array( 'col' => 12 ) ),
				),
				'columns'     => array(
					array(
						'field' => 'cover_image',
						'label' => 'Cover',
						'kind'  => 'image',
					),
					array(
						'field' => 'event_date',
						'label' => 'Date',
						'kind'  => 'date',
					),
					array(
						'field' => 'images',
						'label' => 'Photos',
						'kind'  => 'count',
					),
				),
			),

			'epic_message'     => array(
				'table'       => 'contact_messages',
				'label'       => 'Messages',
				'singular'    => 'Message',
				'description' => 'Messages sent through the Contact form.',
				'icon'        => 'dashicons-email-alt',
				'public'      => false,
				'form'        => true,
				'supports'    => array( 'title' ),
				'title_label' => 'Sender',
				'orderby'     => array( 'date' => 'DESC' ),
				'fields'      => array(
					self::f( 'name', 'Full name', 'text', array( 'col' => 6 ) ),
					self::f( 'email', 'Email', 'email', array( 'col' => 6 ) ),
					self::f( 'phone', 'Phone', 'text', array( 'col' => 6 ) ),
					self::f( 'organisation', 'Organisation', 'text', array( 'col' => 6 ) ),
					self::f( 'subject', 'Subject', 'text', array( 'col' => 12 ) ),
					self::f( 'message', 'Message', 'textarea', array(
						'col'  => 12,
						'rows' => 8,
					) ),
					self::f( 'is_read', 'Marked as read', 'checkbox', array( 'col' => 6 ) ),
				),
				'columns'     => array(
					array(
						'field' => 'email',
						'label' => 'Email',
						'kind'  => 'text',
					),
					array(
						'field' => 'subject',
						'label' => 'Subject',
						'kind'  => 'text',
					),
					array(
						'field' => 'is_read',
						'label' => 'Read',
						'kind'  => 'bool',
					),
				),
			),

			'epic_volunteer'   => array(
				'table'       => 'volunteer_applications',
				'label'       => 'Volunteers',
				'singular'    => 'Application',
				'description' => 'Applications sent through the Volunteer form.',
				'icon'        => 'dashicons-heart',
				'public'      => false,
				'form'        => true,
				'supports'    => array( 'title' ),
				'title_label' => 'Applicant',
				'orderby'     => array( 'date' => 'DESC' ),
				'fields'      => array(
					self::f( 'name', 'Full name', 'text', array( 'col' => 6 ) ),
					self::f( 'email', 'Email', 'email', array( 'col' => 6 ) ),
					self::f( 'phone', 'Phone', 'text', array( 'col' => 4 ) ),
					self::f( 'city', 'City', 'text', array( 'col' => 4 ) ),
					self::f( 'country', 'Country', 'text', array( 'col' => 4 ) ),
					self::f( 'interest', 'Area of interest', 'text', array( 'col' => 6 ) ),
					self::f( 'availability', 'Availability', 'text', array( 'col' => 6 ) ),
					self::f( 'message', 'Message', 'textarea', array(
						'col'  => 12,
						'rows' => 6,
					) ),
					self::f( 'cv_path', 'CV', 'file', array( 'col' => 6 ) ),
					self::f( 'is_read', 'Marked as read', 'checkbox', array( 'col' => 6 ) ),
				),
				'columns'     => array(
					array(
						'field' => 'email',
						'label' => 'Email',
						'kind'  => 'text',
					),
					array(
						'field' => 'interest',
						'label' => 'Interest',
						'kind'  => 'text',
					),
					array(
						'field' => 'is_read',
						'label' => 'Read',
						'kind'  => 'bool',
					),
				),
			),

			'epic_subscriber'  => array(
				'table'       => 'subscribers',
				'label'       => 'Subscribers',
				'singular'    => 'Subscriber',
				'description' => 'People who subscribed to EPIC updates. Use the Export CSV button above the list to download them.',
				'icon'        => 'dashicons-megaphone',
				'public'      => false,
				'form'        => true,
				'supports'    => array( 'title' ),
				'title_label' => 'Email address',
				'orderby'     => array( 'date' => 'DESC' ),
				'fields'      => array(
					self::f( 'name', 'Name', 'text', array( 'col' => 6 ) ),
					self::f( 'organisation', 'Organisation', 'text', array( 'col' => 6 ) ),
					self::f( 'status', 'Status', 'select', array(
						'options' => self::SUBSCRIBER_STATUSES,
						'default' => 'subscribed',
						'col'     => 6,
						'filter'  => true,
					) ),
					self::f( 'source', 'Signed up from', 'text', array( 'col' => 6 ) ),
				),
				'columns'     => array(
					array(
						'field' => 'name',
						'label' => 'Name',
						'kind'  => 'text',
					),
					array(
						'field' => 'status',
						'label' => 'Status',
						'kind'  => 'badge',
						'map'   => self::SUBSCRIBER_STATUSES,
					),
					array(
						'field' => 'source',
						'label' => 'Source',
						'kind'  => 'text',
					),
				),
			),
		);

		return self::$types;
	}

	/** Look a content type up by the Laravel table it came from. */
	public static function type_for_table( string $table ): ?string {
		foreach ( self::types() as $name => $type ) {
			if ( $type['table'] === $table ) {
				return $name;
			}
		}

		return null;
	}

	/**
	 * Resolve an `options` value, which is either an array or the name of a method
	 * on this class (for lists that depend on the database).
	 *
	 * @param array|string $options
	 * @return array<string,string>
	 */
	public static function options( $options ): array {
		if ( is_string( $options ) && method_exists( __CLASS__, $options ) ) {
			return self::$options();
		}

		return is_array( $options ) ? $options : array();
	}

	/* ------------------------------------------------------- page fields --- */

	/**
	 * The fields every page carries in addition to its title and main content.
	 *
	 * @return array<int, array>
	 */
	public static function page_fields(): array {
		return array(
			self::s( 'Hero' ),
			self::f( 'eyebrow', 'Eyebrow / kicker', 'text', array(
				'col'  => 6,
				'hint' => 'Small line above the heading.',
			) ),
			self::f( 'hero_title', 'Hero heading', 'textarea', array(
				'col'  => 6,
				'rows' => 3,
				'hint' => 'On the home page each line becomes its own line, and the last line is highlighted in green. Leave empty to use the page title.',
			) ),
			self::f( 'hero_subtitle', 'Hero text', 'textarea', array(
				'col'  => 12,
				'rows' => 3,
			) ),
			self::f( 'hero_image', 'Hero image', 'image', array(
				'col'    => 6,
				'aspect' => '16 / 7',
				'auto'   => 'cover',
				'hint'   => 'Recommended 1600×1000px or larger. Page headers fill the width by default; use Fit & crop to change that.',
			) ),
			self::f( 'quote', 'Highlight quote', 'textarea', array(
				'col'  => 6,
				'rows' => 3,
			) ),
			self::f( 'quote_author', 'Quote attribution', 'text', array( 'col' => 6 ) ),
			self::s( 'Introduction' ),
			self::f( 'intro', 'Introduction', 'textarea', array(
				'col'  => 12,
				'rows' => 4,
			) ),
			self::s( 'Call to action' ),
			self::f( 'cta_text', 'Button label', 'text', array( 'col' => 6 ) ),
			self::f( 'cta_url', 'Button link', 'url', array(
				'col'         => 6,
				'placeholder' => '/publications',
			) ),
		);
	}

	/**
	 * The pages the website itself depends on. `path` is the web address, which is
	 * the same one the Laravel site used, so existing links and search rankings
	 * carry over. Pages are found by `key`, never by slug, so editors can retitle
	 * them freely.
	 *
	 * @return array<string, array{path:string,title:string,layout:string}>
	 */
	public static function builtin_pages(): array {
		return array(
			'home'                   => array( 'path' => '', 'title' => 'Home', 'layout' => 'home' ),
			'about-us'               => array( 'path' => 'who-we-are', 'title' => 'About Us', 'layout' => 'about' ),
			'vision-mission'         => array( 'path' => 'who-we-are/vision-mission', 'title' => 'Vision & Mission', 'layout' => 'vision' ),
			'epic-principles'        => array( 'path' => 'who-we-are/epic-principles', 'title' => 'EPIC Principles', 'layout' => 'principles' ),
			'our-strengths'          => array( 'path' => 'who-we-are/our-strengths', 'title' => 'Our Strengths', 'layout' => 'strengths' ),
			'epic-team'              => array( 'path' => 'who-we-are/epic-team', 'title' => 'EPIC Team', 'layout' => 'people' ),
			'board'                  => array( 'path' => 'who-we-are/board-of-directors', 'title' => 'Board of Directors', 'layout' => 'people' ),
			'advisory-council'       => array( 'path' => 'who-we-are/advisory-council', 'title' => 'Advisory Council', 'layout' => 'people' ),
			'themes'                 => array( 'path' => 'what-we-do/themes', 'title' => 'Themes of EPIC Work', 'layout' => 'themes' ),
			'projects'               => array( 'path' => 'what-we-do/projects', 'title' => 'Projects', 'layout' => 'projects' ),
			'international-chapters' => array( 'path' => 'what-we-do/international-chapters', 'title' => 'International Chapters', 'layout' => 'chapters' ),
			'events'                 => array( 'path' => 'events', 'title' => 'Events', 'layout' => 'events' ),
			'partnerships'           => array( 'path' => 'partnerships', 'title' => 'Partnerships', 'layout' => 'partnerships' ),
			'mous'                   => array( 'path' => 'partnerships/mous', 'title' => 'MoUs', 'layout' => 'mous' ),
			'memberships'            => array( 'path' => 'partnerships/memberships', 'title' => 'Memberships', 'layout' => 'memberships' ),
			'publications'           => array( 'path' => 'publications', 'title' => 'Our Collection', 'layout' => 'publications' ),
			'journal'                => array( 'path' => 'publications/journal', 'title' => 'Journal (HEC-recognized)', 'layout' => 'journal' ),
			'newsletter'             => array( 'path' => 'publications/e-newsletter', 'title' => 'E-Newsletter', 'layout' => 'newsletter' ),
			'blogs'                  => array( 'path' => 'blogs-and-articles', 'title' => 'Blogs & Articles', 'layout' => 'blogs' ),
			'careers'                => array( 'path' => 'get-involved/careers', 'title' => 'Careers', 'layout' => 'careers' ),
			'volunteer'              => array( 'path' => 'get-involved/volunteer', 'title' => 'Volunteer', 'layout' => 'volunteer' ),
			'subscribe'              => array( 'path' => 'get-involved/subscribe', 'title' => 'Subscribe', 'layout' => 'subscribe' ),
			'contact'                => array( 'path' => 'contact', 'title' => 'Contact', 'layout' => 'contact' ),
			'press-releases'         => array( 'path' => 'media/press-releases', 'title' => 'Press Releases', 'layout' => 'press' ),
			'podcast'                => array( 'path' => 'media/podcast', 'title' => 'Podcast', 'layout' => 'podcast' ),
			'youtube'                => array( 'path' => 'media/youtube', 'title' => 'YouTube', 'layout' => 'videos' ),
			'gallery'                => array( 'path' => 'media/gallery', 'title' => 'Gallery', 'layout' => 'gallery' ),
			'search'                 => array( 'path' => 'search', 'title' => 'Search', 'layout' => 'search' ),
		);
	}

	/**
	 * Pages that exist only so that a web address like /what-we-do has somewhere to
	 * go: they send the visitor on to a real page.
	 *
	 * @return array<string, array{title:string,to:string}>
	 */
	public static function container_pages(): array {
		return array(
			'what-we-do'   => array(
				'title' => 'What We Do',
				'to'    => 'what-we-do/themes',
			),
			'get-involved' => array(
				'title' => 'Get Involved',
				'to'    => 'get-involved/careers',
			),
			'media'        => array(
				'title' => 'Media',
				'to'    => 'media/press-releases',
			),
		);
	}

	/**
	 * Old slugs a built-in page may be stored under on a Laravel install that
	 * predates page keys (the board page was renamed more than once).
	 *
	 * @return array<string, string[]>
	 */
	public static function legacy_slugs(): array {
		return array(
			'board' => array( 'board-of-governance', 'board-of-directors', 'board' ),
		);
	}

	/* ----------------------------------------------------------- settings --- */

	const YES_NO = array(
		'1' => 'Yes',
		'0' => 'No',
	);

	/**
	 * Every editable site setting, grouped into the settings screen's tabs. Mirrors
	 * the Laravel dashboard; the visitor-analytics options that depended on
	 * Laravel's scheduler and geolocation lookups are replaced by the simpler
	 * counter described under Analytics.
	 *
	 * @return array<string, array>
	 */
	public static function settings(): array {
		return array(
			'general'      => array(
				'label'  => 'General',
				'fields' => array(
					'site_name'        => array( 'label' => 'Site name', 'type' => 'text', 'col' => 6, 'default' => 'EPIC — Economic Policy and Innovation Centre' ),
					'site_name_full'   => array( 'label' => 'Full organisation name', 'type' => 'text', 'col' => 6, 'hint' => 'Used in the footer copyright line.' ),
					'site_description' => array( 'label' => 'Site description', 'type' => 'textarea', 'col' => 12, 'hint' => 'Shown to search engines when a page has no description of its own.' ),
					'logo'             => array( 'label' => 'Logo (dark version)', 'type' => 'image', 'col' => 4, 'hint' => 'Used in the header. Leave empty to use the built-in EPIC logo.' ),
					'logo_light'       => array( 'label' => 'Logo (light version)', 'type' => 'image', 'col' => 4, 'hint' => 'Used on dark backgrounds such as the footer.' ),
					'favicon'          => array( 'label' => 'Favicon', 'type' => 'image', 'col' => 4, 'hint' => 'Square PNG, 512×512px recommended.' ),
					'header_cta_label' => array( 'label' => 'Header button label', 'type' => 'text', 'col' => 6, 'placeholder' => 'Support Our Work' ),
					'header_cta_url'   => array( 'label' => 'Header button link', 'type' => 'url', 'col' => 6, 'placeholder' => '/contact' ),
				),
			),
			'appearance'   => array(
				'label'  => 'Appearance',
				'fields' => array(
					'menu_background'     => array(
						'label'   => 'Menu bar background',
						'type'    => 'color',
						'col'     => 6,
						'default' => '#e8f1fa',
						'hint'    => 'The colour behind the logo and main menu. Keep it light so the dark menu text stays readable.',
					),
					'page_header_overlay' => array(
						'label'   => 'Page header picture — darkening',
						'type'    => 'range',
						'col'     => 6,
						'min'     => 0,
						'max'     => 95,
						'step'    => 5,
						'unit'    => '%',
						'default' => '50',
						'hint'    => 'How much of a navy wash sits over the picture at the top of each page. Lower shows more of the picture; the heading stays readable on the darker left side.',
					),
				),
			),
			'home'         => array(
				'label'  => 'Home page',
				'fields' => array(
					'home_cta2_label'        => array( 'label' => 'Second hero button label', 'type' => 'text', 'col' => 6 ),
					'home_cta2_url'          => array( 'label' => 'Second hero button link', 'type' => 'url', 'col' => 6 ),
					'subscribe_title'        => array( 'label' => 'Subscribe band heading', 'type' => 'text', 'col' => 6 ),
					'subscribe_text'         => array( 'label' => 'Subscribe band text', 'type' => 'textarea', 'col' => 6 ),
					'subscribe_privacy_note' => array( 'label' => 'Subscribe privacy note', 'type' => 'textarea', 'col' => 12 ),
				),
			),
			'contact'      => array(
				'label'  => 'Contact',
				'fields' => array(
					'contact_address'    => array( 'label' => 'Address', 'type' => 'text', 'col' => 6 ),
					'contact_email'      => array( 'label' => 'Public email', 'type' => 'text', 'col' => 6 ),
					'contact_phone'      => array( 'label' => 'Phone', 'type' => 'text', 'col' => 6 ),
					'office_hours'       => array( 'label' => 'Office hours', 'type' => 'text', 'col' => 6 ),
					'notification_email' => array( 'label' => 'Send form notifications to', 'type' => 'text', 'col' => 6, 'hint' => 'Contact and volunteer forms are emailed here. Submissions are always saved under EPIC → Messages and Volunteers as well.' ),
					'careers_email'      => array( 'label' => 'Careers email', 'type' => 'text', 'col' => 3 ),
					'media_email'        => array( 'label' => 'Media email', 'type' => 'text', 'col' => 3 ),
					'map_embed'          => array( 'label' => 'Map embed code', 'type' => 'code', 'col' => 12, 'hint' => 'Paste the <iframe> embed code from Google Maps.' ),
				),
			),
			'social'       => array(
				'label'  => 'Social media',
				'fields' => array(
					'social_linkedin'  => array( 'label' => 'LinkedIn URL', 'type' => 'url', 'col' => 6 ),
					'social_x'         => array( 'label' => 'X (Twitter) URL', 'type' => 'url', 'col' => 6 ),
					'social_youtube'   => array( 'label' => 'YouTube URL', 'type' => 'url', 'col' => 6 ),
					'social_facebook'  => array( 'label' => 'Facebook URL', 'type' => 'url', 'col' => 6 ),
					'social_instagram' => array( 'label' => 'Instagram URL', 'type' => 'url', 'col' => 6 ),
				),
			),
			'footer'       => array(
				'label'  => 'Footer',
				'fields' => array(
					'footer_about'   => array( 'label' => 'Footer intro text', 'type' => 'textarea', 'col' => 6 ),
					'footer_tagline' => array( 'label' => 'Footer tagline', 'type' => 'textarea', 'col' => 6, 'hint' => 'Each line is shown on its own row, e.g. Ideas / People / Prosperity.' ),
					'footer_rights'  => array( 'label' => 'Rights statement', 'type' => 'text', 'col' => 12 ),
				),
			),
			'analytics'    => array(
				'label'  => 'Visitor counter',
				'fields' => array(
					'analytics_enabled'        => array( 'label' => 'Count visitors', 'type' => 'select', 'col' => 4, 'options' => self::YES_NO, 'default' => '1', 'hint' => 'Records a row for each page view of the public website (people only, never crawlers).' ),
					'analytics_respect_dnt'    => array( 'label' => 'Honour "Do Not Track"', 'type' => 'select', 'col' => 4, 'options' => self::YES_NO, 'default' => '1' ),
					'analytics_retention_days' => array( 'label' => 'Keep visit records for (days)', 'type' => 'text', 'col' => 4, 'default' => '0', 'hint' => 'Older records are deleted daily. Use 0 to keep them indefinitely.' ),
					'show_visitor_counter'     => array( 'label' => 'Show the counter on the website', 'type' => 'select', 'col' => 4, 'options' => self::YES_NO, 'default' => '1', 'hint' => 'Appears in the footer.' ),
					'visitor_counter_metric'   => array( 'label' => 'Counter shows', 'type' => 'select', 'col' => 4, 'options' => array( 'visitors' => 'Visitors', 'views' => 'Page views' ), 'default' => 'visitors', 'hint' => 'Visitors counts people; page views counts pages opened.' ),
					'visitor_counter_label'    => array( 'label' => 'Counter label', 'type' => 'text', 'col' => 4, 'placeholder' => 'Website visitors' ),
				),
			),
			'integrations' => array(
				'label'  => 'Integrations',
				'fields' => array(
					'head_code' => array( 'label' => 'Code before </head>', 'type' => 'code', 'col' => 12, 'hint' => 'Analytics or verification tags. Paste the full script tag.' ),
					'body_code' => array( 'label' => 'Code before </body>', 'type' => 'code', 'col' => 12, 'hint' => 'Chat widgets or tracking pixels.' ),
				),
			),
		);
	}

	/** Every setting key with its definition, flattened. */
	public static function setting_fields(): array {
		$all = array();
		foreach ( self::settings() as $group ) {
			$all += $group['fields'];
		}

		return $all;
	}
}
