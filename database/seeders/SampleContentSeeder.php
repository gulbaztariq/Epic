<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Event;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Starter content so the website looks complete on day one.
 * Every record here can be edited or deleted from the dashboard.
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->chapters();
        $this->publications();
        $this->events();
        $this->projects();
        $this->posts();
    }

    protected function chapters(): void
    {
        $chapters = [
            ['United Kingdom', 'London', 'Research collaboration and policy engagement with UK universities, think tanks and the Pakistani diaspora.'],
            ['Indonesia', 'Jakarta', 'Knowledge exchange on entrepreneurship, skills and inclusive growth across South-East Asia.'],
            ['Nepal', 'Kathmandu', 'Joint work on human capital, governance and regional development.'],
            ['Sri Lanka', 'Colombo', 'Collaboration on economic policy, productivity and institutional reform.'],
            ['Malaysia', 'Kuala Lumpur', 'Partnerships on digital transformation, higher education and enterprise development.'],
            ['China', 'Beijing', 'Engagement on innovation, technology and economic cooperation.'],
            ['Bangladesh', 'Dhaka', 'Shared research on labour markets, skills and inclusive growth.'],
        ];

        foreach ($chapters as $i => [$country, $city, $description]) {
            Chapter::firstOrCreate(
                ['country' => $country],
                ['city' => $city, 'description' => $description, 'status' => 'forming', 'sort' => $i + 1, 'is_active' => true]
            );
        }
    }

    protected function publications(): void
    {
        $publications = [
            [
                'title' => "Pakistan's Growth Opportunity",
                'subtitle' => 'A Roadmap for a More Competitive Economy',
                'type' => 'Research Report',
                'abstract' => 'A roadmap for raising productivity, competitiveness and investment, setting out the reforms needed to lift sustained economic growth in Pakistan.',
                'months' => 1,
            ],
            [
                'title' => 'Green Growth for a Resilient Pakistan',
                'subtitle' => 'Policy Options for a Cleaner, Stronger Economy',
                'type' => 'Policy Brief',
                'abstract' => 'Policy options that connect climate resilience with economic opportunity, from clean energy and efficiency to green jobs and finance.',
                'months' => 2,
            ],
            [
                'title' => "Pakistan's Digital Economy",
                'subtitle' => 'Unlocking Jobs, Innovation and Inclusive Growth',
                'type' => 'Research Paper',
                'abstract' => 'How digital infrastructure, skills, regulation and responsible technology adoption can widen opportunity and create quality jobs.',
                'months' => 3,
            ],
            [
                'title' => 'Investing in Human Capital for a Brighter Pakistan',
                'subtitle' => 'Policy Options for a Skilled, Productive and Inclusive Economy',
                'type' => 'Policy Brief',
                'abstract' => 'Education, skills and employability reforms that raise productivity and expand economic participation.',
                'months' => 4,
            ],
            [
                'title' => 'Entrepreneurship for a New Pakistan',
                'subtitle' => 'Policies for Innovation, Jobs and Inclusive Growth',
                'type' => 'Working Paper',
                'abstract' => 'An agenda for entrepreneurship policy: the enabling environment, access to finance, innovation ecosystems and enterprise skills.',
                'months' => 5,
            ],
        ];

        foreach ($publications as $i => $item) {
            Publication::firstOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'title' => $item['title'],
                    'subtitle' => $item['subtitle'],
                    'type' => $item['type'],
                    'collection' => 'collection',
                    'abstract' => $item['abstract'],
                    'body' => '<p>'.$item['abstract'].'</p><p>This is starter content. Replace it with the full publication summary, upload the PDF and set the authors from the dashboard.</p>',
                    'published_at' => Carbon::now()->subMonths($item['months'])->startOfMonth(),
                    'is_featured' => true,
                    'is_published' => true,
                    'sort' => $i + 1,
                ]
            );
        }
    }

    protected function events(): void
    {
        $events = [
            [
                'title' => "Pakistan's Economic Reform Agenda",
                'excerpt' => 'A high-level policy dialogue with policymakers, industry and academia.',
                'city' => 'Islamabad',
                'mode' => 'Hybrid Event',
                'type' => 'Policy Dialogue',
                'in' => 1,
            ],
            [
                'title' => 'Human Capital for a Competitive Pakistan',
                'excerpt' => 'Investing in skills, youth and productive employment.',
                'city' => 'Lahore',
                'mode' => 'In-Person',
                'type' => 'Roundtable',
                'in' => 2,
            ],
            [
                'title' => 'Digital Economy: Opportunities and Challenges',
                'excerpt' => 'A multi-sectoral dialogue on regulation, skills and inclusive growth.',
                'city' => 'Karachi',
                'mode' => 'Hybrid Event',
                'type' => 'Policy Dialogue',
                'in' => 3,
            ],
        ];

        foreach ($events as $item) {
            Event::firstOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'title' => $item['title'],
                    'event_type' => $item['type'],
                    'excerpt' => $item['excerpt'],
                    'description' => '<p>'.$item['excerpt'].'</p><p>This is starter content. Add the full agenda, speakers and registration link from the dashboard.</p>',
                    'starts_at' => Carbon::now()->addMonths($item['in'])->setTime(10, 0),
                    'ends_at' => Carbon::now()->addMonths($item['in'])->setTime(16, 0),
                    'city' => $item['city'],
                    'location' => $item['city'].', Pakistan',
                    'mode' => $item['mode'],
                    'is_featured' => true,
                    'is_published' => true,
                ]
            );
        }
    }

    protected function projects(): void
    {
        $projects = [
            ['Skills for the Future Workforce', 'Human Capital Development', 'An applied research and capacity-building programme on skills, employability and the future of work.'],
            ['Responsible AI for Public Services', 'Artificial Intelligence and Digital Transformation', 'Exploring how public institutions can adopt AI responsibly, with attention to ethics, inclusion and accountability.'],
            ['Enterprise Growth and Job Creation', 'Entrepreneurship and Enterprise Development', 'Research and programme support for SME growth, startup ecosystems and quality job creation.'],
        ];

        foreach ($projects as $i => [$title, $category, $summary]) {
            Project::firstOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'category' => $category,
                    'summary' => $summary,
                    'description' => '<p>'.$summary.'</p><p>This is starter content. Add the full project description, partners and timeline from the dashboard.</p>',
                    'status' => 'ongoing',
                    'started_at' => Carbon::now()->subMonths(6 + $i)->startOfMonth(),
                    'is_published' => true,
                    'sort' => $i + 1,
                ]
            );
        }
    }

    protected function posts(): void
    {
        $posts = [
            [
                'title' => 'Why human capital is the foundation of economic growth',
                'category' => 'blog',
                'excerpt' => 'Educated, skilled, healthy and empowered people are central to long-term economic and social progress.',
                'months' => 1,
            ],
            [
                'title' => 'Responsible AI: an opportunity Pakistan cannot afford to miss',
                'category' => 'article',
                'excerpt' => 'Artificial intelligence can raise productivity across education, enterprise and public services — if it is adopted responsibly.',
                'months' => 2,
            ],
            [
                'title' => 'EPIC launches its research and policy programme',
                'category' => 'press_release',
                'excerpt' => 'The Economic Policy and Innovation Centre sets out its agenda for evidence-based policy, human capital and responsible innovation.',
                'months' => 3,
            ],
        ];

        foreach ($posts as $item) {
            Post::firstOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'title' => $item['title'],
                    'category' => $item['category'],
                    'excerpt' => $item['excerpt'],
                    'body' => '<p>'.$item['excerpt'].'</p><p>This is starter content. Replace it with the full text from the dashboard.</p>',
                    'published_at' => Carbon::now()->subMonths($item['months']),
                    'is_published' => true,
                ]
            );
        }
    }
}
