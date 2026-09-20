<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $sort => $page) {
            $sections = $page['sections'] ?? [];
            unset($page['sections']);

            $slug = $page['slug'];
            $record = Page::firstOrNew(['slug' => $slug]);

            // Only seed copy the first time so admin edits are never overwritten.
            if (! $record->exists) {
                $record->fill($page);
                $record->sort = $sort + 1;
                $record->is_published = true;
                $record->save();
            }

            foreach ($sections as $i => $section) {
                PageSection::updateOrCreate(
                    ['page_id' => $record->id, 'type' => $section['type']],
                    array_merge(['sort' => $i + 1, 'is_active' => true], $section)
                );
            }
        }
    }

    protected function pages(): array
    {
        return [
            [
                'slug' => 'home',
                'title' => 'Home',
                'eyebrow' => 'Ideas for a stronger Pakistan',
                'hero_title' => "Evidence.\nInnovation.\nOpportunity.",
                'hero_subtitle' => 'An independent policy, research and knowledge institution advancing economic prosperity, human capital, entrepreneurship, good governance and responsible innovation in Pakistan and globally.',
                'quote' => 'Better policies. Brighter possibilities for a more prosperous Pakistan.',
                'cta_text' => 'Explore Our Research',
                'cta_url' => '/publications',
                'meta_title' => 'EPIC — Economic Policy and Innovation Centre',
                'meta_description' => 'EPIC is an independent policy, research and knowledge institution advancing evidence-based solutions for economic prosperity, human capital, governance, entrepreneurship and responsible innovation.',
                'sections' => [
                    ['type' => 'focus', 'heading' => 'Our Focus Areas', 'link_text' => 'A more innovative, competitive and inclusive Pakistan', 'link_url' => '/what-we-do/themes'],
                    ['type' => 'publications', 'heading' => 'Featured Publications', 'link_text' => 'View All Publications', 'link_url' => '/publications'],
                    ['type' => 'events', 'heading' => 'Upcoming Events & Dialogues', 'link_text' => 'View All Events', 'link_url' => '/events'],
                    [
                        'type' => 'entrepreneurship',
                        'heading' => "Entrepreneurship\nfor a Brighter Pakistan",
                        'body' => 'We support evidence-based policies, partnerships and programmes that enable entrepreneurs, scale innovation, develop human capital and create quality jobs across Pakistan.',
                        'subheading' => "Entrepreneurs don't just build businesses. They build a brighter Pakistan.",
                        'link_text' => 'Our Entrepreneurship Agenda',
                        'link_url' => '/what-we-do/themes',
                    ],
                    ['type' => 'stats', 'heading' => 'Data & Insights', 'link_text' => 'Explore More Insights', 'link_url' => '/publications', 'body' => 'Expanding connectivity can unlock new opportunities.'],
                ],
            ],

            [
                'slug' => 'about-us',
                'title' => 'About Us',
                'eyebrow' => 'Who We Are',
                'hero_title' => 'An independent institution for evidence-based progress',
                'hero_subtitle' => 'EPIC connects credible evidence with informed public debate, institutional learning and practical policy action.',
                'intro' => 'Economic Policy and Innovation Centre (EPIC) is an independent policy, research, and knowledge institution dedicated to advancing evidence-based solutions for economic prosperity, human capital development, accountability and good governance, entrepreneurship, responsible innovation, and effective public policy.',
                'body' => '<p>We convene researchers, academics, practitioners, entrepreneurs, policymakers, and development professionals to generate knowledge that strengthens institutions, expands human capabilities, nurtures productive enterprises, and fosters sustainable and inclusive growth. Our work bridges research and practice through policy studies, applied research, capacity development, dialogue platforms, knowledge dissemination, and collaborative initiatives that address emerging social and economic challenges in Pakistan and globally.</p><p>EPIC places particular emphasis on human capital, value creation, entrepreneurship, digital transformation, and responsible Artificial Intelligence (AI) as critical drivers of productivity, competitiveness, and equitable development.</p><p>Through partnerships with universities, think tanks, civil society organizations, businesses, development partners, and public institutions worldwide, EPIC strengthens the connection between credible evidence, informed public debate, institutional learning, and practical policy action. We are committed to embedding diversity, equity, and inclusion into every dimension of our work, ensuring that the benefits of innovation and development are shared across communities, cultures, and generations.</p>',
                'quote' => 'Evidence for policy. Ideas for people. Inclusive growth for Pakistan.',
            ],

            [
                'slug' => 'vision-mission',
                'title' => 'Vision & Mission',
                'eyebrow' => 'Who We Are',
                'hero_title' => 'What we are working towards',
                'hero_subtitle' => 'Our vision describes the future we want to help create; our mission describes how we work towards it every day.',
                'sections' => [
                    [
                        'type' => 'vision',
                        'heading' => 'Vision',
                        'body' => 'A prosperous, innovative, and inclusive global society where evidence-based policy, empowered human capital, transparent and accountable institutions, entrepreneurship, and responsible technology create opportunities for individuals, institutions, and communities to thrive.',
                    ],
                    [
                        'type' => 'mission',
                        'heading' => 'Mission',
                        'body' => "EPIC's mission is to generate credible research, strengthen human capabilities, promote entrepreneurship and value creation, and facilitate evidence-based policy dialogue that contributes to economic freedom, institutional effectiveness, responsible innovation, and sustainable development.",
                    ],
                ],
            ],

            [
                'slug' => 'epic-principles',
                'title' => 'EPIC Principles',
                'eyebrow' => 'Who We Are',
                'hero_title' => 'The principles that guide our work',
                'hero_subtitle' => 'Ten commitments that shape how EPIC researches, partners and engages.',
                'intro' => "EPIC's work is guided by the following principles.",
            ],

            [
                'slug' => 'our-strengths',
                'title' => 'Our Strengths',
                'eyebrow' => 'Who We Are',
                'hero_title' => 'One multidisciplinary platform',
                'hero_subtitle' => 'Research, policy engagement, academic expertise, entrepreneurship, institutional development and implementation experience.',
                'intro' => "EPIC's strength lies in combining research, policy engagement, academic expertise, entrepreneurship, institutional development, and implementation experience within one multidisciplinary platform.",
            ],

            [
                'slug' => 'epic-team',
                'title' => 'EPIC Team',
                'eyebrow' => 'Who We Are',
                'hero_title' => 'The people behind EPIC',
                'hero_subtitle' => 'Researchers, practitioners and programme professionals working across economic policy, human capital, entrepreneurship, governance and responsible innovation.',
                'intro' => 'Team profiles are being finalised and will be published here shortly.',
            ],

            [
                'slug' => 'board-of-governance',
                'title' => 'Board of Governance',
                'eyebrow' => 'Who We Are',
                'hero_title' => 'Strategic oversight and accountability',
                'hero_subtitle' => "The Board of Governance provides strategic oversight, institutional guidance, and accountability for EPIC's long-term direction.",
                'intro' => "The Board of Governance provides strategic oversight, institutional guidance, and accountability for EPIC's long-term direction. Member profiles will be announced shortly.",
            ],

            [
                'slug' => 'advisory-council',
                'title' => 'Advisory Council',
                'eyebrow' => 'Who We Are',
                'hero_title' => 'Expertise from across disciplines',
                'hero_subtitle' => "EPIC's Advisory Council brings together distinguished academics, policy experts, economists, entrepreneurs, business leaders, development practitioners, technology specialists, and former public-sector professionals.",
                'intro' => "EPIC's Advisory Council brings together distinguished academics, policy experts, economists, entrepreneurs, business leaders, development practitioners, technology specialists, and former public-sector professionals. Council members will be announced shortly.",
            ],

            [
                'slug' => 'themes',
                'title' => 'Themes of EPIC Work',
                'eyebrow' => 'What We Do',
                'hero_title' => 'Ten interconnected thematic areas',
                'hero_subtitle' => 'Where our research, dialogue and programmes are focused.',
                'intro' => 'EPIC works across interconnected thematic areas that influence economic prosperity, institutional performance, and human development.',
            ],

            [
                'slug' => 'projects',
                'title' => 'Projects',
                'eyebrow' => 'What We Do',
                'hero_title' => 'Research and delivery in practice',
                'hero_subtitle' => 'Projects we design and implement independently and with partner organisations.',
                'intro' => 'EPIC designs and implements research, policy, capacity-building, and development projects independently and in collaboration with partner organizations.',
            ],

            [
                'slug' => 'international-chapters',
                'title' => 'International Chapters',
                'eyebrow' => 'What We Do',
                'hero_title' => 'A growing global network',
                'hero_subtitle' => 'EPIC chapters connect researchers, practitioners and institutions across regions.',
                'intro' => 'Our international chapters extend EPIC research, dialogue and collaboration beyond Pakistan, linking partners across Asia, Europe and beyond.',
            ],

            [
                'slug' => 'events',
                'title' => 'Events',
                'eyebrow' => 'Convening',
                'hero_title' => 'Events and dialogues',
                'hero_subtitle' => 'Connecting research with policy dialogue, institutional learning and public engagement.',
                'intro' => 'EPIC organizes events that connect research with policy dialogue, institutional learning, and public engagement.',
            ],

            [
                'slug' => 'partnerships',
                'title' => 'Partnerships',
                'eyebrow' => 'Partnerships & MoUs',
                'hero_title' => 'Impact built through collaboration',
                'hero_subtitle' => 'We work with universities, think tanks, government institutions, development partners and the private sector.',
                'intro' => 'EPIC believes sustainable impact is built through collaboration.',
            ],

            [
                'slug' => 'mous',
                'title' => 'Memoranda of Understanding',
                'eyebrow' => 'Partnerships & MoUs',
                'hero_title' => 'Strategic MoUs',
                'hero_subtitle' => 'Formal agreements with institutions that share our interests in research, education, policy, entrepreneurship, innovation and human capital development.',
                'intro' => 'EPIC enters into strategic Memoranda of Understanding with institutions that share common interests in research, education, policy, entrepreneurship, innovation, and human capital development.',
            ],

            [
                'slug' => 'memberships',
                'title' => 'Memberships',
                'eyebrow' => 'Partnerships & MoUs',
                'hero_title' => 'Networks we belong to',
                'hero_subtitle' => 'National and international networks that strengthen institutional learning, knowledge exchange and policy collaboration.',
                'intro' => 'EPIC actively participates in national and international networks that strengthen institutional learning, knowledge exchange, policy collaboration, and professional engagement.',
            ],

            [
                'slug' => 'publications',
                'title' => 'Our Collection',
                'eyebrow' => 'Publications',
                'hero_title' => 'Research, briefs and analysis',
                'hero_subtitle' => 'Reports, policy briefs, working papers and research papers from the EPIC team and our network.',
                'intro' => 'Browse the EPIC collection of research reports, policy briefs, working papers and research papers.',
            ],

            [
                'slug' => 'journal',
                'title' => 'Journal (HEC-recognized)',
                'eyebrow' => 'Publications',
                'hero_title' => 'The EPIC journal',
                'hero_subtitle' => 'Peer-reviewed research on economic policy, human capital, governance, entrepreneurship and responsible innovation.',
                'intro' => 'Our HEC-recognized journal publishes peer-reviewed scholarship that informs policy and practice.',
            ],

            [
                'slug' => 'newsletter',
                'title' => 'E-Newsletter',
                'eyebrow' => 'Publications',
                'hero_title' => 'The EPIC e-newsletter',
                'hero_subtitle' => 'Research highlights, upcoming events, opportunities and policy commentary, delivered to your inbox.',
                'intro' => 'Each issue brings together new research, events, opportunities and commentary from across the EPIC network.',
            ],

            [
                'slug' => 'blogs',
                'title' => 'Blogs & Articles',
                'eyebrow' => 'Publications',
                'hero_title' => 'Commentary and analysis',
                'hero_subtitle' => 'Short-form writing from the EPIC team, fellows and partners.',
                'intro' => 'Perspectives on the ideas shaping economic policy, human capital, entrepreneurship and responsible innovation.',
            ],

            [
                'slug' => 'careers',
                'title' => 'Careers',
                'eyebrow' => 'Get Involved',
                'hero_title' => 'Build your career at EPIC',
                'hero_subtitle' => 'Join a multidisciplinary team working at the intersection of research, policy and practice.',
                'intro' => 'We are always interested in hearing from researchers, analysts, programme professionals and practitioners who share our commitment to evidence and impact.',
            ],

            [
                'slug' => 'volunteer',
                'title' => 'Volunteer',
                'eyebrow' => 'Get Involved',
                'hero_title' => 'Volunteer with EPIC',
                'hero_subtitle' => 'Contribute your time, skills and perspective to research, events and outreach.',
                'intro' => 'Volunteers support EPIC research, events, communications and community engagement. Tell us how you would like to contribute and we will be in touch.',
            ],

            [
                'slug' => 'subscribe',
                'title' => 'Subscribe',
                'eyebrow' => 'Get Involved',
                'hero_title' => 'Stay informed',
                'hero_subtitle' => 'Research, policy briefs, events and opportunities from EPIC.',
                'intro' => 'Join our mailing list to receive EPIC publications, event invitations and opportunities.',
            ],

            [
                'slug' => 'contact',
                'title' => 'Contact',
                'eyebrow' => 'Get in touch',
                'hero_title' => 'Contact EPIC',
                'hero_subtitle' => 'For research collaboration, partnerships, media enquiries or general questions.',
            ],

            [
                'slug' => 'press-releases',
                'title' => 'Press Releases',
                'eyebrow' => 'Media',
                'hero_title' => 'News from EPIC',
                'hero_subtitle' => 'Official statements, launches and announcements.',
                'intro' => 'Official announcements and statements from the Economic Policy and Innovation Centre.',
            ],

            [
                'slug' => 'podcast',
                'title' => 'Pod Cast',
                'eyebrow' => 'Media',
                'hero_title' => 'The EPIC podcast',
                'hero_subtitle' => 'Conversations with researchers, entrepreneurs and policymakers.',
                'intro' => 'Listen to conversations on the ideas shaping economic policy, human capital, entrepreneurship and responsible innovation.',
            ],

            [
                'slug' => 'youtube',
                'title' => 'YouTube',
                'eyebrow' => 'Media',
                'hero_title' => 'Watch EPIC',
                'hero_subtitle' => 'Recordings of dialogues, seminars, launches and expert conversations.',
                'intro' => 'Recordings from EPIC policy dialogues, seminars, launches and expert conversations.',
            ],

            [
                'slug' => 'gallery',
                'title' => 'Gallery',
                'eyebrow' => 'Media',
                'hero_title' => 'EPIC in pictures',
                'hero_subtitle' => 'Photographs from dialogues, workshops, launches and field work.',
                'intro' => 'A visual record of EPIC events, partnerships and programmes.',
            ],

            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'eyebrow' => 'Legal',
                'hero_title' => 'Privacy Policy',
                'hero_subtitle' => 'How EPIC collects, uses and protects your information.',
                'body' => '<h2>Information we collect</h2><p>We collect the information you choose to give us through our contact, subscription and volunteer forms — such as your name, email address, phone number and organisation. We also collect standard technical information, such as your browser type and the pages you visit, to help us improve the website.</p><h2>How we use your information</h2><p>We use your information to respond to your enquiry, to send you the EPIC newsletter and event invitations when you have asked for them, and to understand how our website is used. We do not sell your information.</p><h2>Sharing</h2><p>We share personal information only with service providers who help us operate the website and our mailing list, and where we are required to do so by law.</p><h2>Your choices</h2><p>You can unsubscribe from our mailing list at any time using the link in our emails, or by writing to us. You may also ask us to correct or delete the information we hold about you.</p><h2>Contact</h2><p>Questions about this policy can be sent to our team using the details on the contact page.</p>',
            ],

            [
                'slug' => 'terms-of-use',
                'title' => 'Terms of Use',
                'eyebrow' => 'Legal',
                'hero_title' => 'Terms of Use',
                'hero_subtitle' => 'The terms on which this website is made available.',
                'body' => "<h2>Use of this website</h2><p>This website is provided by the Economic Policy and Innovation Centre (EPIC) for information purposes. By using the site you agree to use it lawfully and not in a way that damages the site or restricts anyone else's use of it.</p><h2>Intellectual property</h2><p>Unless stated otherwise, the content of this website — including publications, text, graphics and logos — belongs to EPIC or its partners. You may quote and share our research with appropriate attribution. Reproducing substantial parts of our work for commercial purposes requires written permission.</p><h2>Research and opinions</h2><p>Views expressed in EPIC publications, blogs and events are those of the authors and do not necessarily reflect the position of EPIC, its Board, its Advisory Council or its partners.</p><h2>External links</h2><p>Our website links to other organisations' sites. We are not responsible for their content or their privacy practices.</p><h2>Changes</h2><p>We may update these terms from time to time. The current version always appears on this page.</p>",
            ],
        ];
    }
}
