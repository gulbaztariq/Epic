<?php

namespace Database\Seeders;

use App\Models\FocusArea;
use App\Models\ListItem;
use App\Models\Stat;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->focusAreas();
        $this->stats();
        $this->lists();
    }

    protected function focusAreas(): void
    {
        $areas = [
            ['Economic Policy', 'Practical solutions for sustainable growth', 'chart', 'navy'],
            ['Innovation', 'From ideas to real-world impact', 'lightbulb', 'navy'],
            ['Entrepreneurship', 'Enabling enterprises for a brighter tomorrow', 'people', 'navy'],
            ['Human Capital', 'Skills, talent and productive people', 'users', 'green'],
            ['Governance', 'Stronger institutions for a fairer Pakistan', 'bank', 'navy'],
            ['Markets & Competitiveness', 'Open markets. Greater opportunities.', 'trending-up', 'navy'],
            ['Digital Economy', 'A more connected and inclusive future', 'monitor', 'navy'],
        ];

        foreach ($areas as $i => [$title, $description, $icon, $color]) {
            FocusArea::updateOrCreate(
                ['title' => $title],
                ['description' => $description, 'icon' => $icon, 'color' => $color, 'sort' => $i + 1, 'url' => '/what-we-do/themes']
            );
        }
    }

    protected function stats(): void
    {
        $stats = [
            ['GDP Growth (Real)', '2.4%', 'Pakistan | FY 2023', 'chart'],
            ['Youth Population', '64%', "of Pakistan's population is under 30", 'people'],
            ['Human Capital Index', '0.41', 'Pakistan | 2023', 'users'],
            ['Digital Adoption', '44%', 'Internet penetration in Pakistan', 'wifi'],
        ];

        foreach ($stats as $i => [$label, $value, $caption, $icon]) {
            Stat::updateOrCreate(
                ['label' => $label],
                ['value' => $value, 'caption' => $caption, 'icon' => $icon, 'sort' => $i + 1]
            );
        }
    }

    protected function lists(): void
    {
        $lists = [
            'hero_highlights' => [
                ['Evidence for Policy', null, 'document'],
                ['Ideas for People', null, 'lightbulb'],
                ['Inclusive Growth for Pakistan', null, 'chart'],
            ],

            'entrepreneurship_pillars' => [
                ['Better Policy Environment', 'Remove barriers and enable growth', 'rocket'],
                ['Access to Finance', 'Connect ideas with capital', 'partnership'],
                ['Innovation Ecosystems', 'Stronger networks and support', 'gear'],
                ['Scale for Impact', 'From startups to national impact', 'trending-up'],
                ['Human Capital for Enterprise', 'Skills, talent and entrepreneurial mindsets', 'users'],
            ],

            'principles' => [
                ['Evidence and Intellectual Rigor', 'We promote research, analysis, and policy recommendations grounded in credible evidence, sound methodologies, and independent inquiry.', 'search'],
                ['Human Capital First', 'We believe that educated, skilled, healthy, innovative, and empowered people are central to long-term economic and social progress.', 'users'],
                ['Freedom and Opportunity', 'We support an enabling environment in which individuals can exercise choice, pursue enterprise, innovate, and participate meaningfully in economic and civic life.', 'globe'],
                ['Value Creation', 'We encourage ideas, institutions, enterprises, and public policies that generate sustainable economic and social value.', 'trending-up'],
                ['Entrepreneurship and Innovation', 'We recognize entrepreneurs and innovators as important drivers of employment, productivity, competition, and economic transformation.', 'rocket'],
                ['Responsible Technology and AI', 'We promote the ethical, inclusive, transparent, and responsible use of Artificial Intelligence and emerging technologies.', 'cpu'],
                ['Integrity and Independence', 'We uphold professionalism, transparency, accountability, research integrity, and institutional independence.', 'shield'],
                ['Collaboration and Inclusion', 'We believe complex development challenges require cooperation among government, academia, civil society, businesses, communities, and development partners.', 'partnership'],
                ['Learning and Impact', 'We continuously assess our work, learn from evidence, and focus on measurable outcomes and practical impact.', 'target'],
                ['Accountability, Governance and Institutional Effectiveness', 'We believe transparent, accountable, responsive, and capable institutions are essential for public trust, effective policy implementation, responsible use of resources, and sustainable reform. EPIC promotes integrity, evidence-based governance, institutional performance, and mechanisms that strengthen accountability in public and organizational decision-making.', 'bank'],
            ],

            'strengths' => [
                ['Applied economic and policy research', null, 'chart'],
                ['Human capital and human resource development', null, 'users'],
                ['Entrepreneurship and enterprise development', null, 'rocket'],
                ['Public policy and governance analysis', null, 'bank'],
                ['Responsible AI and digital transformation', null, 'cpu'],
                ['Research design, surveys, and data analysis', null, 'pie'],
                ['Policy dialogue and stakeholder engagement', null, 'partnership'],
                ['Capacity-building and professional development', null, 'graduation'],
                ['Knowledge management and dissemination', null, 'book'],
                ['Grant-funded research and development projects', null, 'document'],
                ['Academic and institutional partnerships', null, 'network'],
                ['Monitoring, evaluation, learning, and impact assessment', null, 'target'],
            ],

            'board_areas' => [
                ['Institutional strategy', null, 'target'],
                ['Governance and compliance', null, 'shield'],
                ['Financial sustainability', null, 'chart'],
                ['Risk management', null, 'scale'],
                ['Organizational accountability', null, 'bank'],
                ['Partnership development', null, 'partnership'],
                ['Strategic growth', null, 'trending-up'],
                ['Research independence and integrity', null, 'book'],
            ],

            'advisory_areas' => [
                ['Research priorities', null, 'search'],
                ['Economic and public policy', null, 'chart'],
                ['Human capital development', null, 'users'],
                ['Entrepreneurship', null, 'rocket'],
                ['Governance and institutional reform', null, 'bank'],
                ['Artificial Intelligence and emerging technologies', null, 'cpu'],
                ['Academic collaboration', null, 'graduation'],
                ['International engagement', null, 'globe'],
                ['Knowledge dissemination', null, 'book'],
            ],

            'themes' => [
                ['Economic Policy and Prosperity', 'EPIC conducts research and policy analysis on economic growth, productivity, investment, markets, public finance, financial inclusion, employment, competitiveness, and economic opportunity.', 'chart'],
                ['Human Capital Development', 'We study and support policies and initiatives that improve education, skills, employability, leadership, productivity, and workforce capabilities.', 'users'],
                ['Human Resource Development', 'EPIC develops research, training, and institutional solutions related to workforce development, organizational performance, leadership, talent development, and the future of work.', 'briefcase'],
                ['Entrepreneurship and Enterprise Development', 'We promote entrepreneurship as a mechanism for innovation, employment creation, economic mobility, and value creation. Our work includes entrepreneurship education, SME development, startup ecosystems, financial literacy, and enterprise policy.', 'rocket'],
                ['Value Creation and Productivity', 'EPIC examines how individuals, businesses, institutions, and public systems can generate greater economic and social value through innovation, productivity, skills, technology, and institutional efficiency.', 'trending-up'],
                ['Governance and Public Policy', 'Our governance work focuses on evidence-based policymaking, institutional effectiveness, transparency, accountability, citizen engagement, regulatory quality, and improved public-sector performance.', 'bank'],
                ['Artificial Intelligence and Digital Transformation', 'EPIC explores how AI and digital technologies can support productivity, education, entrepreneurship, public services, research, and economic development while addressing issues of ethics, inclusion, accountability, privacy, and responsible innovation.', 'cpu'],
                ['Education, Skills and Employability', 'We examine the relationship between education systems, labour-market needs, skills development, employability, lifelong learning, and economic transformation.', 'graduation'],
                ['Youth and Future Generations', 'EPIC supports research and initiatives that expand economic, educational, entrepreneurial, and leadership opportunities for young people.', 'sparkle'],
                ['Sustainable and Inclusive Development', 'We promote development approaches that balance economic growth, social inclusion, environmental responsibility, resilience, and long-term prosperity.', 'leaf'],
            ],

            'project_types' => [
                ['Policy research studies', null, 'search'],
                ['Economic and labor-market assessments', null, 'chart'],
                ['Entrepreneurship programs', null, 'rocket'],
                ['Human capital initiatives', null, 'users'],
                ['Skills-development programs', null, 'graduation'],
                ['Digital transformation projects', null, 'monitor'],
                ['Responsible AI initiatives', null, 'cpu'],
                ['Policy dialogues and roundtables', null, 'partnership'],
                ['Research fellowships', null, 'book'],
                ['University partnerships', null, 'graduation'],
                ['Institutional capacity-building', null, 'bank'],
                ['Youth leadership initiatives', null, 'star'],
                ['Surveys and impact assessments', null, 'pie'],
                ['Knowledge dissemination campaigns', null, 'network'],
            ],

            'event_types' => [
                ['Policy dialogues', null, 'partnership'],
                ['Roundtable discussions', null, 'users'],
                ['Research conferences', null, 'bank'],
                ['Seminars', null, 'graduation'],
                ['Public lectures', null, 'mic'],
                ['Workshops', null, 'gear'],
                ['Executive forums', null, 'briefcase'],
                ['Entrepreneurship events', null, 'rocket'],
                ['Capacity-building sessions', null, 'layers'],
                ['Book launches', null, 'book'],
                ['Research dissemination events', null, 'document'],
                ['University dialogues', null, 'graduation'],
                ['Webinars', null, 'monitor'],
                ['Expert consultations', null, 'people'],
            ],

            'partner_types' => [
                ['Universities and research institutions', null, 'graduation'],
                ['Think tanks', null, 'bank'],
                ['Government institutions', null, 'scale'],
                ['Development partners', null, 'globe'],
                ['International organizations', null, 'network'],
                ['Civil society organizations', null, 'people'],
                ['Chambers and business associations', null, 'briefcase'],
                ['Private-sector companies', null, 'trending-up'],
                ['Entrepreneurship networks', null, 'rocket'],
                ['Technology organizations', null, 'cpu'],
                ['Professional associations', null, 'users'],
                ['Media and knowledge platforms', null, 'mic'],
            ],

            'mou_scope' => [
                ['Joint research', null, 'search'],
                ['Faculty and researcher collaboration', null, 'graduation'],
                ['Student engagement', null, 'users'],
                ['Internship opportunities', null, 'briefcase'],
                ['Joint events', null, 'calendar'],
                ['Capacity-building', null, 'layers'],
                ['Knowledge exchange', null, 'network'],
                ['Co-publication', null, 'book'],
                ['Research dissemination', null, 'document'],
                ['Collaborative grant applications', null, 'partnership'],
                ['Innovation and entrepreneurship initiatives', null, 'rocket'],
            ],

            'memberships' => [
                ['International think tank networks', null, 'globe'],
                ['Research networks', null, 'search'],
                ['Academic associations', null, 'graduation'],
                ['Policy forums', null, 'bank'],
                ['Entrepreneurship networks', null, 'rocket'],
                ['Professional associations', null, 'briefcase'],
                ['Civil society platforms', null, 'people'],
            ],

            'get_involved' => [
                ['Careers', 'Join the EPIC team as a researcher, analyst or programme professional.', 'briefcase'],
                ['Volunteer', 'Contribute your time and expertise to EPIC research and events.', 'heart'],
                ['Subscribe', 'Receive our research, policy briefs and event invitations.', 'mail'],
                ['Partner with us', 'Collaborate on research, dialogue and capacity-building initiatives.', 'partnership'],
                ['Research collaboration', 'Co-author studies, share data and join joint grant applications.', 'book'],
            ],

            'publication_types' => [
                ['Research reports', 'In-depth studies on economic policy, human capital and governance.', 'document'],
                ['Policy briefs', 'Concise, decision-ready analysis for policymakers and practitioners.', 'flag'],
                ['Working papers', 'Early-stage research shared for discussion and feedback.', 'edit'],
                ['Journal articles', 'Peer-reviewed research published in our HEC-recognized journal.', 'book'],
                ['E-Newsletter', 'Regular updates on EPIC research, events and opportunities.', 'mail'],
                ['Blogs and articles', 'Commentary and analysis from the EPIC team and network.', 'mic'],
            ],
        ];

        foreach ($lists as $group => $items) {
            foreach ($items as $i => [$title, $description, $icon]) {
                ListItem::updateOrCreate(
                    ['group' => $group, 'title' => $title],
                    ['description' => $description, 'icon' => $icon, 'sort' => $i + 1, 'is_active' => true]
                );
            }
        }
    }
}
