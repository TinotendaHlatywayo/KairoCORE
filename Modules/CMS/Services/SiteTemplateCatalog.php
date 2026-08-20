<?php

namespace Modules\CMS\Services;

/**
 * SiteTemplateCatalog
 *
 * The ten complete, agency-grade system templates — each a full 6-page
 * website with its own page compositions, component variants and copy voice.
 *
 * A "template" is nothing more than a named, ordered list of blocks per page.
 * Every block references a ComponentRegistry component + variant; content is
 * seed copy that site owners replace through the studio. Users can borrow an
 * entire page (per-page template override) or individual blocks (block
 * importer) from any of these ten catalogs when designing their own site.
 */
class SiteTemplateCatalog
{
    /**
     * Relative asset path for a placeholder image key.
     */
    protected static function img(string $key): string
    {
        $paths = ComponentRegistry::PLACEHOLDER_IMAGES;

        return isset($paths[$key]) ? asset($paths[$key]) : $paths['campus-exterior'];
    }

    /**
     * Build a block skeleton with unique id.
     */
    protected static function blk(string $type, array $data = []): array
    {
        return array_merge([
            'id' => uniqid('blk_'),
            'type' => $type,
            'styles' => ['padding_top' => 'py-20', 'padding_bottom' => 'py-20'],
        ], $data);
    }

    /**
     * The full catalog: template key -> page slug -> page seed.
     *
     * @return array<string, array<string, array{title: string, blocks: array}>>
     */
    public static function catalog(): array
    {
        return [
            'heritage-editorial' => self::heritage(),
            'cinematic-immersive' => self::cinematic(),
            'modern-vibrant' => self::vibrant(),
            'minimalist-academic' => self::minimal(),
            'community-warm' => self::warm(),
            'coastal-fresh' => self::coastal(),
            'playful-garden' => self::garden(),
            'emerald-heritage' => self::emerald(),
            'neon-frontier' => self::neon(),
            'sunset-international' => self::sunset(),
        ];
    }

    /**
     * Pages for one template key (empty when unknown).
     *
     * @return array<string, array{title: string, blocks: array}>
     */
    public static function pages(string $templateKey): array
    {
        return self::catalog()[$templateKey] ?? [];
    }

    /**
     * A single page seed for a template key.
     *
     * @return array{title: string, blocks: array}|null
     */
    public static function page(string $templateKey, string $slug): ?array
    {
        $pages = self::pages($templateKey);

        return $pages[$slug] ?? null;
    }

    /* ────────────────────────────────────────────────────────────────
       1. HERITAGE EDITORIAL — navy / cream / gold, serif editorial
       Masthead-worthy, symmetric, generous, formal. Feels like a
       centuries-old establishment's prospectus.
       ──────────────────────────────────────────────────────────────── */

    protected static function heritage(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'A Legacy of Learning, Since 1962',
                        'description' => 'For more than six decades, <strong>St. Aldric College</strong> has shaped young minds through scholarship, character and service. We invite you to become part of a story still being written.',
                        'cta_text' => 'Discover Our Heritage',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Book a Visit',
                        'secondary_cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'single-row',
                        'items' => [
                            ['label' => 'Founded 1962'],
                            ['label' => 'Cambridge & ZIMSEC Accredited'],
                            ['label' => 'A Culture of Excellence'],
                            ['label' => 'Boarding & Day Scholar'],
                            ['label' => 'Faith, Integrity, Diligence'],
                        ],
                    ]),
                    self::blk('about_section', [
                        'title' => 'Our Founding Principles',
                        'description' => 'Founded on the conviction that true education tempers the mind with virtue, St. Aldric College remains faithful to three enduring ideals.',
                        'mission' => 'To nurture principled, high-achieving leaders through transformative, values-led education.',
                        'vision' => 'To set the benchmark for modern education and moral formation across the region.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'The College in Figures',
                        'subtitle' => 'Numbers gathered from our record books, updated each term.',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Halls of Distinction',
                        'subtitle' => 'The houses and faculties that give the College its character.',
                        'features' => [
                            ['title' => 'The School of Letters', 'desc' => 'Classics, literature and languages taught with rigour and love.', 'image' => self::img('library')],
                            ['title' => 'The School of Science', 'desc' => 'Six laboratories for physics, chemistry and biology.', 'image' => self::img('science-lab')],
                            ['title' => 'The School of Arts', 'desc' => 'Music, drama and fine art under one roof.', 'image' => self::img('arts-studio')],
                            ['title' => 'The Athletic Grounds', 'desc' => 'Forty acres of fields, courts and a covered pavilion.', 'image' => self::img('sports-field')],
                        ],
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'A Word from the Headmaster',
                        'principal_name' => 'Dr. Edward Mhlanga',
                        'principal_title' => 'Headmaster',
                        'description' => 'We ask a great deal of our pupils, because the world asks a great deal of them. In return we offer scholarship that is demanding, character that is formed, and a community that never lets a child walk alone.',
                        'image_url' => self::img('staff-silhouette'),
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'large-quote',
                        'title' => 'What Families Say',
                        'testimonials' => [
                            ['quote' => 'Three generations of our family have passed through these gates. The standards have never slipped.', 'name' => 'Mrs. Catherine Ncube', 'role' => 'Parent, Class of 1988'],
                            ['quote' => 'St. Aldric gave my son discipline, curiosity and friends for life. The best decision we ever made.', 'name' => 'Mr. Tafadzwa Gumbo', 'role' => 'Parent, Lower Sixth'],
                            ['quote' => 'The teachers know each child by name. That is vanishingly rare, and precious.', 'name' => 'Dr. Amara Okafor', 'role' => 'Alumna, Class of 2005'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Begin Your Association with the College',
                        'description' => 'Admissions open for the Michaelmas term. Arrangements to visit may be made through the Registrar\'s office.',
                        'cta_text' => 'Apply for Admission',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('assembly-hall'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'About the College',
                        'description' => 'From our 1962 founding beneath a single fig tree to the present day, St. Aldric College has pursued a single vocation: the formation of the whole person.',
                        'cta_text' => 'Meet the Staff',
                        'cta_url' => '#staff',
                        'image_url' => self::img('campus-quad'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('about_section', [
                        'title' => 'History & Ethos',
                        'description' => 'The College was established by the Sisters of St. Aldric with twelve boarders and a library of eighty books. Today we are a co-educational college of more than a thousand pupils, faithful to the same rule of life.',
                        'mission' => 'To form leaders of principle through scholarship, service and spiritual grounding.',
                        'vision' => 'A College whose graduates are known for competence, integrity and compassion.',
                        'image_url' => self::img('campus-exterior'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'The Faculty',
                        'subtitle' => 'Our masters and mistresses are the heart of the College.',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Affiliations & Accreditations',
                        'logos' => [
                            ['name' => 'Cambridge International', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'ZIMSEC', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'ISA', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Round Square', 'logo_url' => self::img('logo-placeholder')],
                        ],
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Admissions',
                        'description' => 'Admission is by assessment and interview. Places are limited and are awarded without regard to means.',
                        'cta_text' => 'Begin an Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('assembly-hall'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('admissions_block', [
                        'title' => 'The Admission Procedure',
                        'subtitle' => 'A simple, transparent process in four stages.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Submit an Application', 'description' => 'Complete the online application with the required documents.'],
                            ['title' => 'Assessment Day', 'description' => 'Pupils sit a short assessment in English, mathematics and reasoning.'],
                            ['title' => 'Interview & References', 'description' => 'A conversation with the Headmaster and a reference from the current school.'],
                            ['title' => 'Offer & Enrolment', 'description' => 'Offers are made within three weeks; acceptance completes the enrolment.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Latest school report'],
                            ['label' => 'Two passport photographs'],
                            ['label' => 'Immunisation record'],
                        ],
                        'fee_note' => 'Scholarships and bursaries are awarded on merit and need. Enquire with the Registrar\'s office for the current schedule of fees.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'At what ages do you admit pupils?', 'a' => 'We admit from Year 1 through Lower Sixth. Entry at Year 7 and Year 9 is most common.'],
                            ['q' => 'Do you offer boarding?', 'a' => 'Yes. Three boarding houses — two for boys and one for girls — accommodate pupils from Year 6 upward.'],
                            ['q' => 'Is financial aid available?', 'a' => 'Yes. A bursary fund, supported by our alumni, assists families of demonstrated need.'],
                            ['q' => 'When does the academic year begin?', 'a' => 'The Michaelmas term begins in early January, with the Lent term in May and the Trinity term in September.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'News & Events',
                        'description' => 'The latest dispatches from the College and the dates that shape the school calendar.',
                        'cta_text' => 'Subscribe',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'From the Headmaster\'s Desk',
                        'subtitle' => 'Announcements and letters to families.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'The College Calendar',
                        'subtitle' => 'Term dates, fixtures and ceremonial occasions.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Contact the College',
                        'description' => 'The Registrar\'s office is at your service. Write, call, or come and walk the grounds yourself.',
                        'cta_text' => 'Send an Enquiry',
                        'cta_url' => '#contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Us',
                        'description' => 'We are a short drive from the city centre, off the Old Salisbury Road.',
                        'address' => 'St. Aldric College, Old Salisbury Road, Harare, Zimbabwe',
                        'phone' => '+263 24 270 1234',
                        'email' => 'registrar@staldric.edu.zw',
                        'hours' => 'Monday–Friday 08:00–16:00, Saturday 09:00–12:00',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Life Beyond the Classroom',
                        'description' => 'Houses, clubs, music, sport and service — the everyday texture of a St. Aldric education.',
                        'cta_text' => 'Explore the Galleries',
                        'cta_url' => '#gallery',
                        'image_url' => self::img('students-outdoor'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('gallery', [
                        'variant' => 'featured-image',
                        'title' => 'Scenes from the College',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Break on the Great Lawn'],
                            ['image' => self::img('library'), 'caption' => 'The Old Library'],
                            ['image' => self::img('sports-field'), 'caption' => 'First XV, Autumn Fixture'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Studio Practice'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Refectory at Noon'],
                            ['image' => self::img('assembly-hall'), 'caption' => 'Founders\' Day Assembly'],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at St. Aldric',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                    self::blk('features_grid', [
                        'title' => 'Houses of the College',
                        'subtitle' => 'Every pupil belongs to a house, and every house to a tradition.',
                        'features' => [
                            ['title' => 'Ellis House', 'desc' => 'The oldest house, known for music and debate.', 'image' => self::img('assembly-hall')],
                            ['title' => 'Nduna House', 'desc' => 'Home of the cross-country and the cadet corps.', 'image' => self::img('sports-field')],
                            ['title' => 'Okapi House', 'desc' => 'The arts house, with its own theatre company.', 'image' => self::img('arts-studio')],
                        ],
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       2. CINEMATIC IMMERSIVE — near-black / cyan / rose
       Dark, motion-forward, signature showpiece. Aurora hero, kinetic
       headlines, glass panels, scene-numbered chapters.
       ──────────────────────────────────────────────────────────────── */

    protected static function cinematic(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('cinematic_scroll', [
                        'variant' => 'aurora-immersive',
                        'title' => 'Where Learning Comes Alive',
                        'subtitle' => 'A living campus of ideas — where every day is a story worth telling.',
                        'primary_cta_text' => 'Explore the Campus',
                        'primary_cta_url' => '/about',
                        'secondary_cta_text' => 'Apply Now',
                        'secondary_cta_url' => '/apply-online',
                        'hue_shift' => true,
                        'blob_count' => 4,
                        'intensity' => 0.6,
                        'speed' => 1,
                    ]),
                    self::blk('scroll_highlight_text', [
                        'text' => 'We do not merely teach subjects. We engineer environments where curiosity compounds — where a question in physics becomes a prototype on Monday and a public experiment by Friday.',
                        'split_by' => 'word',
                    ]),
                    self::blk('statistics', [
                        'variant' => 'cinematic-overlay',
                        'title' => 'The Numbers Behind the Signal',
                        'subtitle' => 'Live data from the campus network.',
                    ]),
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Design the future you want to live in',
                        'variant' => 'smoke',
                        'trigger' => 'scroll',
                        'intensity' => 1.2,
                        'title_size' => 64,
                    ]),
                    self::blk('coverflow_carousel', [
                        'title' => 'Signature Spaces',
                        'subtitle' => 'A moving tour of the campus.',
                        'slides' => [
                            ['image' => self::img('library'), 'title' => 'The Quiet Commons'],
                            ['image' => self::img('science-lab'), 'title' => 'Innovation Lab 03'],
                            ['image' => self::img('assembly-hall'), 'title' => 'The Forum'],
                            ['image' => self::img('sports-field'), 'title' => 'North Field'],
                            ['image' => self::img('arts-studio'), 'title' => 'Studio East'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'title' => 'The Learning Stack',
                        'subtitle' => 'Four systems working in concert.',
                        'features' => [
                            ['title' => 'Adaptive Curriculum', 'desc' => 'Pacing matched to mastery, not the calendar.', 'image' => self::img('classroom')],
                            ['title' => 'Project Fabrication Lab', 'desc' => 'CNC, electronics and 3D printing open to every pupil.', 'image' => self::img('science-lab')],
                            ['title' => 'Broadcast Studio', 'desc' => 'Pupil-run newsroom and podcasting booths.', 'image' => self::img('arts-studio')],
                            ['title' => 'Sports Performance', 'desc' => 'Biomechanics, recovery and elite coaching.', 'image' => self::img('sports-field')],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Join the Next Cohort',
                        'description' => 'Applications for the new academic year close at midnight. Build something that outlives the semester.',
                        'cta_text' => 'Start Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-exterior'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'An Institution Built Like a Studio',
                        'description' => 'We are an independent day school that treats education as product design — iterated, tested and shipped to a thousand users a year.',
                        'cta_text' => 'Read the Story',
                        'cta_url' => '/about',
                        'image_url' => self::img('campus-quad'),
                        'layout' => 'full-bleed',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Mission & Operating System',
                        'description' => 'Founded by engineers and educators in 2011, the Institute runs on a simple loop: teach, build, reflect, release.',
                        'mission' => 'To make ambitious, creative learning accessible to every pupil in our community.',
                        'vision' => 'A school whose graduates build the systems the world runs on.',
                        'image_url' => self::img('science-lab'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'The Crew',
                        'subtitle' => 'Educators, engineers and designers.',
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'Admissions',
                        'description' => 'Admission is by portfolio, aptitude screen and a creative challenge. We look for curiosity over prior attainment.',
                        'cta_text' => 'Begin Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('classroom'),
                        'layout' => 'full-bleed',
                    ]),
                    self::blk('admissions_block', [
                        'title' => 'The Application Pipeline',
                        'subtitle' => 'From first click to first day.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Apply Online', 'description' => 'A ten-minute form with the essentials.'],
                            ['title' => 'Creative Challenge', 'description' => 'A short open-ended task — build, write or design something.'],
                            ['title' => 'Builder Day', 'description' => 'Half a day on campus in mixed-age teams.'],
                            ['title' => 'Offer & Onboarding', 'description' => 'Offers within a fortnight; onboarding is self-serve and digital.'],
                        ],
                        'documents' => [
                            ['label' => 'Most recent report'],
                            ['label' => 'One reference'],
                            ['label' => 'Portfolio of any kind'],
                        ],
                        'fee_note' => 'Merit scholarships cover up to 80% of tuition. Financial aid is assessed confidentially.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'What ages do you admit?', 'a' => 'Years 1 through 13, with the majority entering at Year 7 and Year 9.'],
                            ['q' => 'Do you have boarding?', 'a' => 'Not yet — but our city centre campus is a short transit ride from every district.'],
                            ['q' => 'How tech-heavy is the school day?', 'a' => 'Every pupil has a device, but we are screen-free for the first block of each day. Balance is the point.'],
                            ['q' => 'Is there a uniform?', 'a' => 'Yes — a minimalist uniform designed with pupil input and produced locally.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'News & Events',
                        'description' => 'Ship notes from the campus.',
                        'cta_text' => 'Contact',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'full-bleed',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest Dispatches',
                        'subtitle' => 'Announcements, builds and launches.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Upcoming Events',
                        'subtitle' => 'Shows, demos and open days.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'Get in Touch',
                        'description' => 'Questions, partnerships and press — the lines are open.',
                        'cta_text' => 'Contact',
                        'cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'full-bleed',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find the Campus',
                        'description' => 'We are two blocks from the university quarter.',
                        'address' => 'Horizon Institute, 12 Innovation Drive, Harare, Zimbabwe',
                        'phone' => '+263 77 000 4567',
                        'email' => 'hello@horizoninstitute.ac.zw',
                        'hours' => 'Monday–Friday 07:30–17:30',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Life is the curriculum',
                        'variant' => 'smoke',
                        'trigger' => 'scroll',
                        'title_size' => 72,
                    ]),
                    self::blk('gallery', [
                        'variant' => 'immersive-grid',
                        'title' => 'The Campus in Frames',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Monday demo day'],
                            ['image' => self::img('library'), 'caption' => 'The Quiet Commons at dusk'],
                            ['image' => self::img('science-lab'), 'caption' => 'Lab 03, after hours'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Studio East session'],
                            ['image' => self::img('cafeteria'), 'caption' => 'The Canteen Stack'],
                            ['image' => self::img('sports-field'), 'caption' => 'Night training'],
                        ],
                    ]),
                    self::blk('coverflow_carousel', [
                        'title' => 'Clubs & Builds',
                        'slides' => [
                            ['image' => self::img('science-lab'), 'title' => 'Robotics Guild'],
                            ['image' => self::img('arts-studio'), 'title' => 'Film Unit'],
                            ['image' => self::img('library'), 'title' => 'Book Sprint'],
                            ['image' => self::img('sports-field'), 'title' => 'Futsal League'],
                            ['image' => self::img('classroom'), 'title' => 'Debate Circuit'],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'Inside the Institute',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       3. MODERN VIBRANT — violet / cyan / amber, bold and youthful
       Color-blocked sections, coverflow showcase, marquee ticker,
       big rounded cards, energetic.
       ──────────────────────────────────────────────────────────────── */

    protected static function vibrant(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-modern',
                        'title' => 'Big Dreams. Bold Learning.',
                        'description' => 'Sunrise Academy is a joyful, high-energy school for pupils who ask "why" until the answer makes them smile.',
                        'cta_text' => 'Start an Application',
                        'cta_url' => '/apply-online',
                        'secondary_cta_text' => 'Tour the Campus',
                        'secondary_cta_url' => '/student-life',
                        'image_url' => self::img('students-outdoor'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'two-row',
                        'items' => [
                            ['label' => 'Top IGCSE Results 2025'],
                            ['label' => '40+ Clubs & Teams'],
                            ['label' => 'Solar-Powered Campus'],
                            ['label' => 'Duke of Edinburgh Centre'],
                            ['label' => 'Award-Winning Robotics'],
                        ],
                    ]),
                    self::blk('statistics', [
                        'variant' => 'large-number',
                        'title' => 'Sunrise in Numbers',
                        'subtitle' => 'A quick snapshot of our community.',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'What Makes Us Tick',
                        'subtitle' => 'Four big ideas behind every school day.',
                        'features' => [
                            ['title' => 'Learning by Doing', 'desc' => 'Projects, experiments and field trips every single week.', 'image' => self::img('science-lab')],
                            ['title' => 'Creative Everywhere', 'desc' => 'Art, music, dance and drama woven into the timetable.', 'image' => self::img('arts-studio')],
                            ['title' => 'Sport for All', 'desc' => 'Sixteen sports, from swimming to chess boxing.', 'image' => self::img('sports-field')],
                            ['title' => 'Green Campus', 'desc' => 'A garden school with its own farm and solar array.', 'image' => self::img('campus-quad')],
                        ],
                    ]),
                    self::blk('coverflow_carousel', [
                        'title' => 'Campus Highlights',
                        'subtitle' => 'Swipe through the spaces pupils love most.',
                        'slides' => [
                            ['image' => self::img('library'), 'title' => 'The Reading Nook'],
                            ['image' => self::img('science-lab'), 'title' => 'Maker Space'],
                            ['image' => self::img('sports-field'), 'title' => 'Astro Turf'],
                            ['image' => self::img('cafeteria'), 'title' => 'Lunchtime'],
                            ['image' => self::img('assembly-hall'), 'title' => 'Assembly'],
                        ],
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'carousel',
                        'title' => 'Happy Families',
                        'subtitle' => 'Hear it from the community.',
                        'testimonials' => [
                            ['quote' => 'My daughter bounds out of the car every morning. That says everything.', 'name' => 'Mrs. Precious Dube', 'role' => 'Parent, Year 4'],
                            ['quote' => 'The robotics club changed my son\'s whole attitude to school.', 'name' => 'Mr. Simba Chirwa', 'role' => 'Parent, Year 9'],
                            ['quote' => 'Sunrise feels like a big family that also happens to be excellent.', 'name' => 'Ms. Layla Kahn', 'role' => 'Teacher'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Come See the Sunshine',
                        'description' => 'Open morning every second Saturday. Bring the whole family.',
                        'cta_text' => 'RSVP to an Open Morning',
                        'cta_url' => '/contact',
                        'image_url' => self::img('students-outdoor'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-modern',
                        'title' => 'About Sunrise Academy',
                        'description' => 'We opened in 2009 with 40 pupils and one borrowed minibus. Today we are 900 strong — and we kept the minibus.',
                        'cta_text' => 'Meet the Team',
                        'cta_url' => '/about',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Our Mission & Vision',
                        'description' => 'Sunrise Academy exists to make school the best part of a child\'s day — without ever compromising on standards.',
                        'mission' => 'To ignite curiosity and kindness in every child who walks through our gates.',
                        'vision' => 'A generation of confident, creative, compassionate problem-solvers.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'Meet the Team',
                        'subtitle' => 'The people who make the magic happen.',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Our Partners',
                        'logos' => [
                            ['name' => 'Cambridge International', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'ZIMSEC', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'FIRST Robotics', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Eco-Schools', 'logo_url' => self::img('logo-placeholder')],
                        ],
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-modern',
                        'title' => 'Join the Sunrise Family',
                        'description' => 'Admissions are rolling for most year groups. Spaces in Year 7 go fast.',
                        'cta_text' => 'Apply Now',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('classroom'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('admissions_block', [
                        'title' => 'How to Apply',
                        'subtitle' => 'Four easy steps to your first day.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Fill in the Form', 'description' => 'Online, five minutes, no fee to apply.'],
                            ['title' => 'Meet the School', 'description' => 'A relaxed visit and a short chat with the year head.'],
                            ['title' => 'We Say Yes', 'description' => 'Most families hear back within a week.'],
                            ['title' => 'Enrol & Celebrate', 'description' => 'Complete the paperwork and join the welcome week.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Latest report card'],
                            ['label' => 'Emergency contact details'],
                        ],
                        'fee_note' => 'Sibling discounts and need-based bursaries are available. Talk to our friendly admissions team.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Admissions FAQ',
                        'faqs' => [
                            ['q' => 'Is there a uniform?', 'a' => 'Yes — a comfortable, colourful uniform that pupils actually like wearing.'],
                            ['q' => 'Do you provide transport?', 'a' => 'We run six school buses covering most of the city.'],
                            ['q' => 'Can we visit before applying?', 'a' => 'Please do! Open mornings run every second Saturday of the month.'],
                            ['q' => 'What about after-school care?', 'a' => 'Clubs run until 17:00, and supervised care is available until 18:00.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-modern',
                        'title' => 'What\'s Happening',
                        'description' => 'All the news that\'s fit to print — and a lot that isn\'t in the papers.',
                        'cta_text' => 'Contact Us',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest News',
                        'subtitle' => 'Fresh from the newsroom.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Upcoming Events',
                        'subtitle' => 'Save the date.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-modern',
                        'title' => 'Say Hello',
                        'description' => 'We love hearing from families, future pupils and old friends of the school.',
                        'cta_text' => 'Get in Touch',
                        'cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Us',
                        'description' => 'Easy to reach and easy to park.',
                        'address' => 'Sunrise Academy, 34 Blossom Road, Bulawayo, Zimbabwe',
                        'phone' => '+263 29 220 0987',
                        'email' => 'hello@sunriseacademy.co.zw',
                        'hours' => 'Monday–Friday 07:00–18:00, Saturday 08:00–13:00',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Every day is an adventure',
                        'variant' => 'smoke',
                        'trigger' => 'load',
                        'title_size' => 64,
                    ]),
                    self::blk('gallery', [
                        'variant' => 'masonry',
                        'title' => 'Life at Sunrise',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Break time'],
                            ['image' => self::img('library'), 'caption' => 'Reading club'],
                            ['image' => self::img('sports-field'), 'caption' => 'Match day'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Art block'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Lunch'],
                            ['image' => self::img('science-lab'), 'caption' => 'Science week'],
                        ],
                    ]),
                    self::blk('orbit_gallery', [
                        'title' => 'Clubs Around the Clock',
                        'subtitle' => 'Our clubs orbit the school day.',
                        'center_label' => 'Sunrise',
                        'images' => [
                            ['image' => self::img('arts-studio'), 'label' => 'Art'],
                            ['image' => self::img('science-lab'), 'label' => 'Robotics'],
                            ['image' => self::img('library'), 'label' => 'Books'],
                            ['image' => self::img('sports-field'), 'label' => 'Sport'],
                            ['image' => self::img('cafeteria'), 'label' => 'Cookery'],
                            ['image' => self::img('assembly-hall'), 'label' => 'Drama'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Ready for the Adventure?',
                        'description' => 'Applications open now for the new term.',
                        'cta_text' => 'Apply Today',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('students-outdoor'),
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       4. MINIMALIST ACADEMIC — ink / amber, restrained prestige
       Generous whitespace, hairlines, type-driven, one accent colour.
       Feels like a high-end university or research centre.
       ──────────────────────────────────────────────────────────────── */

    protected static function minimal(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-minimal',
                        'title' => 'Study, think, question.',
                        'description' => 'The Graystone Institute is a liberal arts college for secondary pupils who want more from school than a transcript.',
                        'cta_text' => 'Apply',
                        'cta_url' => '/apply-online',
                        'secondary_cta_text' => 'Read the Prospectus',
                        'secondary_cta_url' => '/about',
                        'image_url' => self::img('campus-quad'),
                        'layout' => 'centered',
                    ]),
                    self::blk('scroll_highlight_text', [
                        'text' => 'We believe the purpose of school is not to transmit facts but to teach pupils how to think — slowly, carefully, and in conversation with people who disagree with them.',
                        'split_by' => 'character',
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'The Institute, Measured',
                        'subtitle' => 'The few numbers we publish.',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'icon-list',
                        'title' => 'Four Commitments',
                        'subtitle' => 'What we will and will not do.',
                        'features' => [
                            ['title' => 'Small Seminars', 'desc' => 'Never more than sixteen pupils in a class.'],
                            ['title' => 'Primary Sources', 'desc' => 'Original texts, not summaries of summaries.'],
                            ['title' => 'Slow Assessment', 'desc' => 'Fewer exams; more essays, orals and portfolios.'],
                            ['title' => 'Silence & Solitude', 'desc' => 'Protected time to think, every single day.'],
                        ],
                    ]),
                    self::blk('gallery', [
                        'variant' => 'masonry',
                        'title' => 'The Campus',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('library'), 'caption' => 'The Reading Room'],
                            ['image' => self::img('campus-quad'), 'caption' => 'The Quad'],
                            ['image' => self::img('classroom'), 'caption' => 'Seminar 4'],
                            ['image' => self::img('assembly-hall'), 'caption' => 'The Hall'],
                            ['image' => self::img('students-library'), 'caption' => 'Study Carrels'],
                            ['image' => self::img('science-lab'), 'caption' => 'Laboratory'],
                        ],
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'large-quote',
                        'title' => 'In Their Words',
                        'testimonials' => [
                            ['quote' => 'Graystone taught me how to read a book the way a carpenter reads wood.', 'name' => 'Rudo M.', 'role' => 'Alumna, Oxford 2024'],
                            ['quote' => 'My son came home quoting Plato. I didn\'t know whether to be thrilled or terrified.', 'name' => 'Mr. K. Masuku', 'role' => 'Parent'],
                            ['quote' => 'It is the only school I know where the pupils complain that the holidays are too long.', 'name' => 'Prof. S. Grant', 'role' => 'Visitor'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Apply for the Michaelmas Term',
                        'description' => 'Applications close in September. Places are few by design.',
                        'cta_text' => 'Apply',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-quad'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-minimal',
                        'title' => 'About the Institute',
                        'description' => 'A small college with a singular idea: that adolescents deserve the same intellectual respect we give to undergraduates.',
                        'cta_text' => 'Meet the Faculty',
                        'cta_url' => '/about',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'centered',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Origin & Purpose',
                        'description' => 'The Institute was founded in 1998 by a consortium of university tutors who had grown tired of remediating habits their pupils had been taught at school.',
                        'mission' => 'To cultivate independent, careful thinkers through direct engagement with the best that has been thought and said.',
                        'vision' => 'A generation of graduates who read deeply, argue fairly and live deliberately.',
                        'image_url' => self::img('library'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'Faculty',
                        'subtitle' => 'Tutors, fellows and librarians.',
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-minimal',
                        'title' => 'Admissions',
                        'description' => 'Admission rests on a written application, two essays and an interview. No entrance fee; no ranking tricks.',
                        'cta_text' => 'Apply',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('library'),
                        'layout' => 'centered',
                    ]),
                    self::blk('admissions_block', [
                        'title' => 'Procedure',
                        'subtitle' => 'Deliberate, as befits the Institute.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Written Application', 'description' => 'A short form and a longer letter, written by the pupil themselves.'],
                            ['title' => 'Two Essays', 'description' => 'One on a set text, one on a question the pupil chooses.'],
                            ['title' => 'Interview', 'description' => 'A conversation with two tutors — no trick questions, only real ones.'],
                            ['title' => 'Offer & Settle In', 'description' => 'Offers are made by post. New pupils begin with a quiet induction week.'],
                        ],
                        'documents' => [
                            ['label' => 'School reports (last two years)'],
                            ['label' => 'Pupil\'s letter of application'],
                            ['label' => 'A reference from a subject teacher'],
                        ],
                        'fee_note' => 'Fees are modest by design and fully waived for pupils on the Open Scholarship. We do not market to families we cannot serve.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'Is this a boarding school?', 'a' => 'We are day-only, with study evenings on Tuesday and Thursday.'],
                            ['q' => 'Which curriculum do you follow?', 'a' => 'A Cambridge-aligned core, taught seminar-style, with an extended honours programme.'],
                            ['q' => 'What is the class size?', 'a' => 'Sixteen pupils maximum; most seminars run between ten and fourteen.'],
                            ['q' => 'Do pupils take public exams?', 'a' => 'Yes — IGCSE and A-Level, with the option to sit them early or late.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-minimal',
                        'title' => 'Notices & Occasions',
                        'description' => 'The Institute publishes sparingly, and only what matters.',
                        'cta_text' => 'Contact',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'centered',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Notices',
                        'subtitle' => 'Letters to the community.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Occasions',
                        'subtitle' => 'Lectures, colloquia and the occasional feast day.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-minimal',
                        'title' => 'Correspondence',
                        'description' => 'The Registrar answers letters within three working days.',
                        'cta_text' => 'Write to Us',
                        'cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'centered',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'The Institute',
                        'description' => 'On the quiet side of the university quarter.',
                        'address' => 'Graystone Institute, 5 College Close, Harare, Zimbabwe',
                        'phone' => '+263 24 233 8901',
                        'email' => 'registrar@graystone.edu.zw',
                        'hours' => 'Monday–Friday 09:00–16:00',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'A day of quiet intensity',
                        'variant' => 'rise',
                        'trigger' => 'scroll',
                        'title_size' => 60,
                    ]),
                    self::blk('scroll_highlight_text', [
                        'text' => 'Morning seminar, a library hour, afternoon laboratory or studio, and an evening lecture or recital. The week is spare on purpose — emptiness is where thinking happens.',
                        'split_by' => 'word',
                    ]),
                    self::blk('gallery', [
                        'variant' => 'masonry',
                        'title' => 'Quiet Places',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('library'), 'caption' => 'Reading Room'],
                            ['image' => self::img('students-library'), 'caption' => 'Carrels'],
                            ['image' => self::img('campus-quad'), 'caption' => 'Quad, morning'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Studio'],
                            ['image' => self::img('assembly-hall'), 'caption' => 'The Hall'],
                            ['image' => self::img('classroom'), 'caption' => 'Seminar 4'],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Year at Graystone',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       5. COMMUNITY WARM — rust / olive / cream, family-friendly
       Rounded, photo-heavy, earth tones. Feels like a beloved
       neighbourhood school.
       ──────────────────────────────────────────────────────────────── */

    protected static function warm(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Welcome Home to Greenleaf Academy',
                        'description' => 'A warm, family-centred school where every child is known by name and every family belongs.',
                        'cta_text' => 'Visit Our School',
                        'cta_url' => '/contact',
                        'secondary_cta_text' => 'Apply for Admission',
                        'secondary_cta_url' => '/apply-online',
                        'image_url' => self::img('students-outdoor'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('statistics', [
                        'variant' => 'large-number',
                        'title' => 'Our Family in Numbers',
                        'subtitle' => 'A community that keeps growing.',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'What Families Love',
                        'subtitle' => 'The little things that make Greenleaf feel like home.',
                        'features' => [
                            ['title' => 'A Teacher for Every Child', 'desc' => 'Tutorial groups of ten with the same tutor for three years.', 'image' => self::img('classroom')],
                            ['title' => 'Farm & Garden', 'desc' => 'Children grow what they eat, and eat what they grow.', 'image' => self::img('campus-quad')],
                            ['title' => 'Parent Community', 'desc' => 'Coffee mornings, working bees and a real parent-teacher association.', 'image' => self::img('cafeteria')],
                            ['title' => 'Siblings Welcome', 'desc' => 'Family discounts and priority places for brothers and sisters.', 'image' => self::img('students-outdoor')],
                        ],
                    ]),
                    self::blk('orbit_gallery', [
                        'title' => 'Our Little Universe',
                        'subtitle' => 'The pillars of a Greenleaf education.',
                        'center_label' => 'Greenleaf',
                        'images' => [
                            ['image' => self::img('classroom'), 'label' => 'Care'],
                            ['image' => self::img('campus-quad'), 'label' => 'Nature'],
                            ['image' => self::img('arts-studio'), 'label' => 'Creativity'],
                            ['image' => self::img('sports-field'), 'label' => 'Play'],
                            ['image' => self::img('library'), 'label' => 'Wonder'],
                            ['image' => self::img('assembly-hall'), 'label' => 'Gathering'],
                        ],
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'From Our Principal',
                        'principal_name' => 'Mrs. Sarah Moyo',
                        'principal_title' => 'Principal & Founder',
                        'description' => 'I started Greenleaf with eleven children in my own garden. Today we are a school of six hundred, and the garden has grown with us. What has not changed is the rule we live by: every child, known; every family, welcome.',
                        'image_url' => self::img('staff-silhouette'),
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'image-led',
                        'title' => 'Kind Words',
                        'testimonials' => [
                            ['quote' => 'Greenleaf raised our children better than we could have asked. The teachers know them, and they know us.', 'name' => 'The Ndlovu Family', 'role' => 'Parents of three'],
                            ['quote' => 'My shy boy became a confident one here. That is the whole review.', 'name' => 'Mr. T. Banda', 'role' => 'Parent, Year 5'],
                            ['quote' => 'It feels less like a school and more like the village it takes to raise a child.', 'name' => 'Ms. R. Zhou', 'role' => 'Grandparent'],
                        ],
                    ]),
                    self::blk('gallery', [
                        'variant' => 'masonry',
                        'title' => 'Moments from Our Days',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Playground, golden hour'],
                            ['image' => self::img('library'), 'caption' => 'Story time'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Harvest lunch'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Clay morning'],
                            ['image' => self::img('sports-field'), 'caption' => 'Sports day'],
                            ['image' => self::img('campus-quad'), 'caption' => 'The garden'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Your Family Is Welcome Here',
                        'description' => 'Come for a tour, stay for tea, and meet the teachers your child will love.',
                        'cta_text' => 'Book a Tour',
                        'cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'About Greenleaf',
                        'description' => 'Founded in 1998 in a family garden, Greenleaf Academy has grown into a warm, thriving community school of six hundred children.',
                        'cta_text' => 'Meet the Staff',
                        'cta_url' => '/about',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Our Story',
                        'description' => 'Greenleaf began when eleven neighbourhood children needed a school that felt like home. The answer was a garden, a chalkboard and a promise: no child gets lost in the crowd.',
                        'mission' => 'To give every child a safe, joyful and challenging place to grow into themselves.',
                        'vision' => 'A community school so good that every child in our region wants to attend it.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'Our Staff',
                        'subtitle' => 'The family behind the family.',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Community Partners',
                        'logos' => [
                            ['name' => 'Eco-Schools', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'ZIMSEC', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Child Protection Unit', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Reading Is Fundamental', 'logo_url' => self::img('logo-placeholder')],
                        ],
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Enrol at Greenleaf',
                        'description' => 'We enrol throughout the year when places allow, and we will always find room for a family in need.',
                        'cta_text' => 'Start Enrolment',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('classroom'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('admissions_block', [
                        'title' => 'Enrolment Steps',
                        'subtitle' => 'Simple, friendly and transparent.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Come for a Visit', 'description' => 'See the school, meet the teachers, drink the tea.'],
                            ['title' => 'Complete the Form', 'description' => 'A short enrolment form, online or on paper.'],
                            ['title' => 'Settle-In Meeting', 'description' => 'We plan each child\'s first weeks together with you.'],
                            ['title' => 'First Day', 'description' => 'A gentle start, with a buddy to show the way.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Health record / immunisations'],
                            ['label' => 'Previous school report (if any)'],
                        ],
                        'fee_note' => 'Fees are kept as low as we can manage, with a hardship fund and sibling discounts. No child is turned away for fees alone.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Questions Families Ask',
                        'faqs' => [
                            ['q' => 'At what age can my child start?', 'a' => 'From three years old in our pre-primary, through to Year 7.'],
                            ['q' => 'Do you offer after-school care?', 'a' => 'Yes — after-school care and clubs run until 17:30 every day.'],
                            ['q' => 'What about children with special needs?', 'a' => 'We are an inclusive school with a dedicated learning support team. Talk to us about your child\'s needs.'],
                            ['q' => 'Is there a uniform?', 'a' => 'A comfortable, affordable uniform, with a second-hand shop on site.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'News & Events',
                        'description' => 'Everything that is happening at Greenleaf, in one friendly place.',
                        'cta_text' => 'Get in Touch',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'News from the Family',
                        'subtitle' => 'Letters, photos and announcements.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Upcoming Events',
                        'subtitle' => 'Concerts, fairs and sports days — everyone is invited.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Come and Say Hello',
                        'description' => 'The kettle is always on. Visit us any school day.',
                        'cta_text' => 'Send a Message',
                        'cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Greenleaf',
                        'description' => 'Tucked into a leafy suburb, five minutes from the main road.',
                        'address' => 'Greenleaf Academy, 7 Cypress Lane, Harare, Zimbabwe',
                        'phone' => '+263 24 276 5432',
                        'email' => 'hello@greenleafacademy.ac.zw',
                        'hours' => 'Monday–Friday 07:00–17:30',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Childhood, protected',
                        'variant' => 'smoke',
                        'trigger' => 'scroll',
                        'title_size' => 60,
                    ]),
                    self::blk('gallery', [
                        'variant' => 'masonry',
                        'title' => 'A Week of Wonder',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Climbing the big tree'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Painting afternoon'],
                            ['image' => self::img('library'), 'caption' => 'Reading nook'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Garden lunch'],
                            ['image' => self::img('sports-field'), 'caption' => 'Wet day games'],
                            ['image' => self::img('campus-quad'), 'caption' => 'The veggie patch'],
                        ],
                    ]),
                    self::blk('orbit_gallery', [
                        'title' => 'Clubs & Activities',
                        'subtitle' => 'Something for every child.',
                        'center_label' => 'Greenleaf',
                        'images' => [
                            ['image' => self::img('arts-studio'), 'label' => 'Art'],
                            ['image' => self::img('sports-field'), 'label' => 'Games'],
                            ['image' => self::img('library'), 'label' => 'Reading'],
                            ['image' => self::img('campus-quad'), 'label' => 'Gardening'],
                            ['image' => self::img('cafeteria'), 'label' => 'Cookery'],
                            ['image' => self::img('assembly-hall'), 'label' => 'Choir'],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at Greenleaf',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       6. COASTAL FRESH — sky / seafoam / amber
       Airy coastal palette, soft gradients, a round photo carousel and
       rounded friendly cards. Feels like a bright open-air campus.
       ──────────────────────────────────────────────────────────────── */

    protected static function coastal(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Learning by the Water\'s Edge',
                        'description' => 'At <strong>Seabreeze Academy</strong> every lesson is framed by light and open air. Small classes, big horizons, and a community that feels like a harbour.',
                        'cta_text' => 'Explore Seabreeze',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Take a Tour',
                        'secondary_cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'single-row',
                        'items' => [
                            ['label' => 'Ocean Ridge Campus'],
                            ['label' => 'Cambridge & ZIMSEC Accredited'],
                            ['label' => 'Sailing, Rowing & Water Polo'],
                            ['label' => 'Years 1–13, Day & Boarding'],
                            ['label' => 'Admissions Open for Spring'],
                        ],
                    ]),
                    self::blk('about_section', [
                        'variant' => 'split',
                        'title' => 'Where Curiosity Sets Sail',
                        'description' => 'Founded on the belief that children flourish near the water and the sky, Seabreeze pairs a rigorous academic core with an unrivalled programme of outdoor discovery.',
                        'mission' => 'To grow resilient, curious young people who think clearly, act kindly and look outward.',
                        'vision' => 'To be the school families choose when they want depth, warmth and room to breathe.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'Seabreeze in Numbers',
                        'subtitle' => 'A snapshot from the headmaster\'s termly report.',
                    ]),
                    self::blk('orbit_gallery', [
                        'variant' => 'circle',
                        'title' => 'A Round Tour of Campus',
                        'subtitle' => 'Our signature circular carousel — spin it yourself.',
                        'center_label' => 'Seabreeze',
                        'images' => [
                            ['image' => self::img('library'), 'label' => 'The Tidewater Library'],
                            ['image' => self::img('science-lab'), 'label' => 'Marine Science Lab'],
                            ['image' => self::img('sports-field'), 'label' => 'The Long Lawn'],
                            ['image' => self::img('cafeteria'), 'label' => 'The Boathouse Canteen'],
                            ['image' => self::img('arts-studio'), 'label' => 'Dune Art Studio'],
                            ['image' => self::img('assembly-hall'), 'label' => 'The Pier Auditorium'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'What Makes Seabreeze Different',
                        'subtitle' => 'Four quiet advantages, felt daily.',
                        'features' => [
                            ['title' => 'Marine & Field Studies', 'desc' => 'The harbour is our laboratory — from tide pools to water-quality projects.', 'image' => self::img('campus-exterior')],
                            ['title' => 'Sailing & Rowing', 'desc' => 'Every pupil learns to crew, steer and race on our sheltered sound.', 'image' => self::img('sports-field')],
                            ['title' => 'Small Form Groups', 'desc' => 'A 14:1 ratio means every child is known by name.', 'image' => self::img('classroom')],
                            ['title' => 'Wellbeing First', 'desc' => 'A calm, predictable day, plenty of light and green time.', 'image' => self::img('campus-quad')],
                        ],
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'carousel',
                        'title' => 'From Our Harbour Families',
                        'testimonials' => [
                            ['quote' => 'Our daughter asks to stay later. That has never happened before.', 'name' => 'Mrs. Leila Moyo', 'role' => 'Parent, Year 4'],
                            ['quote' => 'The sailing programme changed my son — confidence, discipline, joy.', 'name' => 'Mr. Daniel Kachinga', 'role' => 'Parent, Year 9'],
                            ['quote' => 'A school that respects childhood as much as achievement.', 'name' => 'Ms. Rudo Nyathi', 'role' => 'Alumna, Class of 2019'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'variant' => 'centered-solid',
                        'title' => 'Drop Anchor With Us',
                        'description' => 'Spring admissions are open for Years 1, 4, 7 and 9. Come for a tour of the water\'s edge.',
                        'cta_text' => 'Apply to Seabreeze',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-exterior'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Our Story',
                        'description' => 'Seabreeze Academy has been a constant on the ridge since 1978 — a school built slowly, with purpose and patience.',
                        'cta_text' => 'Meet Our People',
                        'cta_url' => '#faculty',
                        'image_url' => self::img('assembly-hall'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('about_section', [
                        'variant' => 'timeline',
                        'title' => 'Milestones in the Tide',
                        'description' => 'From a single weatherboard classroom to a campus of two hundred pupils.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'The Crew',
                        'subtitle' => 'Educators, sailors and people who know your child\'s name.',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Affiliations & Accreditations',
                        'logos' => [
                            ['name' => 'Cambridge International', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'ZIMSEC', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'International Sailing Schools Assoc.', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Round Square', 'logo_url' => self::img('logo-placeholder')],
                        ],
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Admissions',
                        'description' => 'Boarding and day places for Years 1–13. Entry is by assessment, interview and a very gentle swim test.',
                        'cta_text' => 'Begin an Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('admissions_block', [
                        'title' => 'How Admission Works',
                        'subtitle' => 'Four easy stages, no paperwork overwhelm.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Submit an Application', 'description' => 'Complete the short online form and attach the requested documents.'],
                            ['title' => 'Welcome Visit', 'description' => 'A relaxed tour of campus followed by a conversation with a member of staff.'],
                            ['title' => 'Assessment Morning', 'description' => 'Pupils take part in reading, numeracy and a water-confidence session.'],
                            ['title' => 'Offer & Enrolment', 'description' => 'Offers arrive within two weeks. A simple enrolment day closes the loop.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Latest school report'],
                            ['label' => 'Two passport photographs'],
                            ['label' => 'Water-safety consent form'],
                        ],
                        'fee_note' => 'Sibling discounts and a small number of marine-scholarships are available. Enquire with the Registrar.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Questions Families Ask',
                        'faqs' => [
                            ['q' => 'Do you take beginners at sailing?', 'a' => 'Yes — most pupils arrive having never sailed. We teach from the very first capsize.'],
                            ['q' => 'Is boarding available?', 'a' => 'From Year 6, pupils may board in our two harbourside houses.'],
                            ['q' => 'What is the pupil–teacher ratio?', 'a' => 'Fourteen to one across the school, and eleven to one in the Junior School.'],
                            ['q' => 'When does the year begin?', 'a' => 'The academic year starts in January, with the spring term in May and the summer term in September.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'News & Events',
                        'description' => 'Regattas, results, campfires and notices — the rhythm of life at Seabreeze.',
                        'cta_text' => 'Subscribe',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'From the Headmaster\'s Log',
                        'subtitle' => 'Letters home and announcements.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'The Seabreeze Calendar',
                        'subtitle' => 'Fixtures, regattas and open days.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Contact Seabreeze',
                        'description' => 'The front office faces the water. Write, call, or simply arrive at high tide.',
                        'cta_text' => 'Send an Enquiry',
                        'cta_url' => '#contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Us',
                        'description' => 'Twenty minutes from the city centre, off the Coast Road.',
                        'address' => 'Seabreeze Academy, Coast Road, Harare, Zimbabwe',
                        'phone' => '+263 24 279 5560',
                        'email' => 'office@seabreeze.ac.zw',
                        'hours' => 'Monday–Friday 07:30–16:30',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Life at Seabreeze',
                        'description' => 'Sail, row, paint, build, act — and still make the honour roll.',
                        'cta_text' => 'See the Gallery',
                        'cta_url' => '#gallery',
                        'image_url' => self::img('students-outdoor'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('gallery', [
                        'variant' => 'horizontal-scroll',
                        'title' => 'Scenes from the Shore',
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Low tide games'],
                            ['image' => self::img('sports-field'), 'caption' => 'Regatta day'],
                            ['image' => self::img('library'), 'caption' => 'The Tidewater Library'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Fish pie Friday'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Dune studio practice'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Clubs & Committees',
                        'subtitle' => 'Every pupil leads something by Year 8.',
                        'features' => [
                            ['title' => 'The Regatta Committee', 'desc' => 'Pupils run the annual open-water regatta.', 'image' => self::img('sports-field')],
                            ['title' => 'The Tidewater Press', 'desc' => 'A pupil-written journal of news and fiction.', 'image' => self::img('library')],
                            ['title' => 'Conservation Patrol', 'desc' => 'Coastal clean-ups and a blue-flag campaign.', 'image' => self::img('campus-quad')],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at Seabreeze',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       7. PLAYFUL GARDEN — coral / teal / sun yellow
       Cheerful junior-school palette, bubbly rounded shapes and bright
       gradient blobs. Every page feels like playtime.
       ──────────────────────────────────────────────────────────────── */

    protected static function garden(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Where Little Seeds Grow Big',
                        'description' => 'Welcome to <strong>Sunnyhill Primary</strong> — a colourful, kind place where children from 3 to 12 learn through play, wonder and a lot of laughter.',
                        'cta_text' => 'Visit Sunnyhill',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Book a Playdate',
                        'secondary_cta_url' => '/contact',
                        'image_url' => self::img('campus-quad'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'two-row',
                        'items' => [
                            ['label' => 'Every Child Is Known'],
                            ['label' => 'Learn Through Play'],
                            ['label' => 'Garden To Table'],
                            ['label' => 'Music, Art & Dance'],
                            ['label' => 'From Age 3 To 12'],
                            ['label' => 'Big Hearts, Big Dreams'],
                        ],
                    ]),
                    self::blk('gallery', [
                        'variant' => 'immersive-grid',
                        'title' => 'A Colourful Week',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Free play on the lawn'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Finger-paint Friday'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Harvest lunch'],
                            ['image' => self::img('library'), 'caption' => 'Story circle'],
                            ['image' => self::img('campus-quad'), 'caption' => 'The veggie patch'],
                            ['image' => self::img('sports-field'), 'caption' => 'Obstacle day'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'How We Grow Happy Kids',
                        'subtitle' => 'The Sunnyhill way, in four petals.',
                        'features' => [
                            ['title' => 'Play-Based Learning', 'desc' => 'We teach through play because that is how young brains learn best.', 'image' => self::img('students-outdoor')],
                            ['title' => 'Garden To Table', 'desc' => 'Every class plants, harvests and cooks from our school garden.', 'image' => self::img('campus-quad')],
                            ['title' => 'Music & Movement', 'desc' => 'Daily singing, percussion and plenty of wiggling.', 'image' => self::img('arts-studio')],
                            ['title' => 'Emotional Literacy', 'desc' => 'Named feelings, calm corners and a buddy bench.', 'image' => self::img('library')],
                        ],
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'A Note From Mrs. Bloom',
                        'principal_name' => 'Mrs. Farai Bloom',
                        'principal_title' => 'Head of School',
                        'description' => 'We are in the business of wonderful childhoods. If you can trust us with your child\'s mornings, we will fill them with light, colour and the joy of figuring things out together.',
                        'image_url' => self::img('staff-silhouette'),
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'image-led',
                        'title' => 'What Parents Say',
                        'testimonials' => [
                            ['quote' => 'My son runs into school. Every single morning.', 'name' => 'Mrs. Tina Manyika', 'role' => 'Parent, Year 2'],
                            ['quote' => 'The garden programme is genius. He now eats broccoli.', 'name' => 'Mr. Sipho Dube', 'role' => 'Parent, Year 1'],
                            ['quote' => 'Teachers who hug, challenge and notice everything.', 'name' => 'Ms. Abigail Chiro', 'role' => 'Parent, Reception'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'variant' => 'centered-solid',
                        'title' => 'Come Play With Us',
                        'description' => 'Enrolments are open for our 3-year-old group through to Year 6.',
                        'cta_text' => 'Enrol at Sunnyhill',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-quad'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Our Sunnyhill Story',
                        'description' => 'Since 1989 we have grown small people into confident, kind, curious ones — one garden bed at a time.',
                        'cta_text' => 'Meet Our Team',
                        'cta_url' => '#team',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('about_section', [
                        'variant' => 'split',
                        'title' => 'A School Built on Wonder',
                        'description' => 'Warm colours, soft corners and a dozen gardens — this is a school designed for childhood.',
                        'mission' => 'To protect childhood while igniting curiosity.',
                        'vision' => 'A primary school where every child is known, loved and stretched.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('team_directory', [
                        'variant' => 'circle-photos',
                        'title' => 'Meet Our Growers',
                        'subtitle' => 'Warm, qualified and full of sparkle.',
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Admissions',
                        'description' => 'Enrolling is as friendly as the school. A short form, a play-visit, and a warm welcome.',
                        'cta_text' => 'Start an Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-quad'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('admissions_block', [
                        'variant' => 'checklist',
                        'title' => 'A Simple, Happy Enrolment',
                        'subtitle' => 'Three small steps.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Send the Form', 'description' => 'Five minutes online with your child\'s details.'],
                            ['title' => 'Come for a Playdate', 'description' => 'A relaxed morning to meet the teachers and friends.'],
                            ['title' => 'Welcome to Sunnyhill', 'description' => 'A welcome letter, a school bag and a happy first day.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Immunisation record'],
                            ['label' => 'One happy child'],
                        ],
                        'fee_note' => 'Fees include lunch, garden programme and all core activities. Sibling discounts apply.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Questions From Grown-Ups',
                        'faqs' => [
                            ['q' => 'From what age can my child start?', 'a' => 'Our toddler group welcomes children from age 3, with Reception from age 5.'],
                            ['q' => 'Is there a uniform?', 'a' => 'A simple polo and shorts — no expensive extras.'],
                            ['q' => 'Do you offer after-care?', 'a' => 'Yes, until 17:30 every school day, with a snack and a quiet corner.'],
                            ['q' => 'Do you support children with allergies?', 'a' => 'Absolutely. We are a nut-free school with a trained first-aider at every lunch.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'News & Events',
                        'description' => 'Concert dates, garden harvests and news you will actually smile about.',
                        'cta_text' => 'Subscribe',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'From the Head\'s Desk',
                        'subtitle' => 'Newsletters and happy announcements.',
                    ]),
                    self::blk('events_calendar', [
                        'variant' => 'mini-grid',
                        'title' => 'Dates For the Diary',
                        'subtitle' => 'Everything from sports day to the winter concert.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Contact Sunnyhill',
                        'description' => 'Pop in any morning — the kettle is always on.',
                        'cta_text' => 'Send an Enquiry',
                        'cta_url' => '#contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('contact_map', [
                        'variant' => 'centered-form',
                        'title' => 'Find Us',
                        'description' => 'A leafy street, a rainbow gate and a very noisy playground.',
                        'address' => 'Sunnyhill Primary, 12 Marigold Avenue, Harare, Zimbabwe',
                        'phone' => '+263 24 274 7788',
                        'email' => 'hello@sunnyhill.ac.zw',
                        'hours' => 'Monday–Friday 07:00–17:30',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Every day is an adventure',
                        'variant' => 'rise',
                        'trigger' => 'scroll',
                        'title_size' => 56,
                    ]),
                    self::blk('gallery', [
                        'variant' => 'immersive-grid',
                        'title' => 'A Week of Wonder',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Water-day fun'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Collage corner'],
                            ['image' => self::img('library'), 'caption' => 'Reading buddies'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Harvest stew'],
                            ['image' => self::img('sports-field'), 'caption' => 'Balloon games'],
                            ['image' => self::img('campus-quad'), 'caption' => 'Planting week'],
                        ],
                    ]),
                    self::blk('orbit_gallery', [
                        'variant' => 'circle',
                        'title' => 'Clubs & Activities',
                        'subtitle' => 'Something for every spark.',
                        'center_label' => 'Sunnyhill',
                        'images' => [
                            ['image' => self::img('arts-studio'), 'label' => 'Art'],
                            ['image' => self::img('cafeteria'), 'label' => 'Cookery'],
                            ['image' => self::img('campus-quad'), 'label' => 'Gardening'],
                            ['image' => self::img('sports-field'), 'label' => 'Games'],
                            ['image' => self::img('library'), 'label' => 'Storytime'],
                            ['image' => self::img('assembly-hall'), 'label' => 'Choir'],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at Sunnyhill',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       8. EMERALD HERITAGE — forest / antique gold / ivory
       Prestige set in deep green and gold: serif display type, editorial
       coverflow, refined cards. A second, quieter heritage voice.
       ──────────────────────────────────────────────────────────────── */

    protected static function emerald(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'A Standard Set in Woodland',
                        'description' => 'For over forty years <strong>Briarwood Academy</strong> has offered a classical, character-rich education in one of the greenest corners of the city.',
                        'cta_text' => 'Discover Briarwood',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Arrange a Visit',
                        'secondary_cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'single-row',
                        'items' => [
                            ['label' => 'Founded 1983'],
                            ['label' => 'Cambridge & ZIMSEC Accredited'],
                            ['label' => 'A Classical, Character-Rich Education'],
                            ['label' => 'Woodland Campus, Ten Acres'],
                            ['label' => 'Scholarships Available'],
                        ],
                    ]),
                    self::blk('about_section', [
                        'variant' => 'editorial',
                        'title' => 'Our Founding Creed',
                        'description' => 'Briarwood was founded on a simple conviction: that a demanding mind and a disciplined character are the truest inheritance a school can bestow.',
                        'mission' => 'To cultivate intellect, integrity and quiet courage.',
                        'vision' => 'To be remembered by our pupils as the place they became themselves.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'Briarwood in Figures',
                        'subtitle' => 'From the school\'s annual account of its year.',
                    ]),
                    self::blk('coverflow_carousel', [
                        'variant' => 'editorial',
                        'title' => 'The Estate',
                        'subtitle' => 'A slow walk through the woodland campus.',
                        'slides' => [
                            ['image' => self::img('library'), 'title' => 'The Green Library'],
                            ['image' => self::img('science-lab'), 'title' => 'The Conservatory'],
                            ['image' => self::img('assembly-hall'), 'title' => 'The Great Hall'],
                            ['image' => self::img('sports-field'), 'title' => 'The Long Meadow'],
                            ['image' => self::img('campus-quad'), 'title' => 'The Cedar Walk'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'split-feature',
                        'title' => 'The Briarwood Difference',
                        'subtitle' => 'Four disciplines that define the school.',
                        'features' => [
                            ['title' => 'Classical Curriculum', 'desc' => 'Logic, rhetoric, literature and languages taught with rigour.', 'image' => self::img('library')],
                            ['title' => 'Equestrian & Outdoors', 'desc' => 'Riding, forestry skills and the Duke of Edinburgh scheme.', 'image' => self::img('sports-field')],
                            ['title' => 'Chapel & Service', 'desc' => 'Weekly assembly, pastoral care and community service.', 'image' => self::img('assembly-hall')],
                            ['title' => 'Conservatory Sciences', 'desc' => 'Greenhouse biology and environmental field work.', 'image' => self::img('science-lab')],
                        ],
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'From the Headmaster',
                        'principal_name' => 'Dr. James Whitmore',
                        'principal_title' => 'Headmaster',
                        'description' => 'We ask much of our pupils, for the world will ask much of them. In return we offer standards, warmth and the unhurried confidence of a school that knows itself.',
                        'image_url' => self::img('staff-silhouette'),
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'large-quote',
                        'title' => 'In Their Own Words',
                        'testimonials' => [
                            ['quote' => 'The standards are exacting, yet our daughter has never been happier.', 'name' => 'Mrs. Eleanor Grant', 'role' => 'Parent, Lower Sixth'],
                            ['quote' => 'Briarwood taught me to write, to argue and to serve.', 'name' => 'Mr. Peter Masviba', 'role' => 'Alumnus, Class of 2001'],
                            ['quote' => 'A school of character, in every sense of the word.', 'name' => 'Dr. Hannah Kigali', 'role' => 'Parent, Year 8'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'variant' => 'centered-solid',
                        'title' => 'Join the Briarwood Standard',
                        'description' => 'Admissions open for the new academic year. Scholarships are awarded on merit and need.',
                        'cta_text' => 'Apply to Briarwood',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('assembly-hall'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Our History',
                        'description' => 'From a rented classroom in 1983 to a ten-acre woodland estate — the Briarwood story is one of patience and principle.',
                        'cta_text' => 'Meet the Faculty',
                        'cta_url' => '#faculty',
                        'image_url' => self::img('assembly-hall'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('about_section', [
                        'variant' => 'timeline',
                        'title' => 'Milestones in the Grove',
                        'description' => 'The years that shaped the school.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('team_directory', [
                        'variant' => 'grid-editorial',
                        'title' => 'The Faculty',
                        'subtitle' => 'Masters, mistresses and tutors of the estate.',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Affiliations & Accreditations',
                        'logos' => [
                            ['name' => 'Cambridge International', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'ZIMSEC', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Duke of Edinburgh', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Round Square', 'logo_url' => self::img('logo-placeholder')],
                        ],
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Admissions',
                        'description' => 'Admission is by examination and interview. Places are few and awarded without regard to means.',
                        'cta_text' => 'Begin an Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('assembly-hall'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('admissions_block', [
                        'title' => 'The Admission Procedure',
                        'subtitle' => 'A considered, transparent process in four stages.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Submit an Application', 'description' => 'Complete the online form with the requested documents.'],
                            ['title' => 'Entrance Examination', 'description' => 'A written examination in English, mathematics and general reasoning.'],
                            ['title' => 'Interview & References', 'description' => 'A conversation with the Headmaster and a reference from the current school.'],
                            ['title' => 'Offer & Enrolment', 'description' => 'Offers are made within three weeks; acceptance completes enrolment.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Latest school report'],
                            ['label' => 'Two passport photographs'],
                            ['label' => 'Medical record'],
                        ],
                        'fee_note' => 'Scholarships and bursaries are awarded on merit and need. Enquire with the Bursar for the schedule of fees.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'At what ages do you admit pupils?', 'a' => 'From Year 1 through Lower Sixth, with entry at Year 7 and Year 9 most common.'],
                            ['q' => 'Do you offer boarding?', 'a' => 'Yes. Two woodland boarding houses accommodate pupils from Year 6 upward.'],
                            ['q' => 'Is financial aid available?', 'a' => 'Yes. A bursary fund supported by alumni assists families of demonstrated need.'],
                            ['q' => 'When does the academic year begin?', 'a' => 'In January, with the Trinity term in September.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'News & Events',
                        'description' => 'Letters from the Headmaster and the dates that shape the school year.',
                        'cta_text' => 'Subscribe',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('dynamic_news', [
                        'variant' => 'featured-hero',
                        'title' => 'From the Headmaster\'s Desk',
                        'subtitle' => 'Announcements and letters to families.',
                    ]),
                    self::blk('events_calendar', [
                        'variant' => 'agenda-list',
                        'title' => 'The School Calendar',
                        'subtitle' => 'Term dates, examinations and ceremonial occasions.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Contact Briarwood',
                        'description' => 'The Bursar\'s office is at your service. Write, call, or walk the grounds yourself.',
                        'cta_text' => 'Send an Enquiry',
                        'cta_url' => '#contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('contact_map', [
                        'variant' => 'split-form',
                        'title' => 'Find Us',
                        'description' => 'Ten acres of woodland, a short drive from the city centre.',
                        'address' => 'Briarwood Academy, Cedar Lane, Harare, Zimbabwe',
                        'phone' => '+263 24 271 9021',
                        'email' => 'bursar@briarwood.ac.zw',
                        'hours' => 'Monday–Friday 08:00–16:00',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Life at Briarwood',
                        'description' => 'Houses, rides, rehearsals and service — the texture of a Briarwood education.',
                        'cta_text' => 'Explore the Gallery',
                        'cta_url' => '#gallery',
                        'image_url' => self::img('students-outdoor'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('gallery', [
                        'variant' => 'featured-image',
                        'title' => 'Scenes from the Estate',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Games on the Long Meadow'],
                            ['image' => self::img('library'), 'caption' => 'The Green Library'],
                            ['image' => self::img('sports-field'), 'caption' => 'The Riding Yard'],
                            ['image' => self::img('arts-studio'), 'caption' => 'The Art Barn'],
                            ['image' => self::img('cafeteria'), 'caption' => 'The Refectory'],
                            ['image' => self::img('assembly-hall'), 'caption' => 'Founders\' Day'],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at Briarwood',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       9. NEON FRONTIER — indigo / electric cyan / lime on near-black
       A dark, glassy STEM showcase: aurora hero, kinetic headlines, mono
       chapter labels and a coverflow of signature spaces.
       ──────────────────────────────────────────────────────────────── */

    protected static function neon(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('cinematic_scroll', [
                        'variant' => 'aurora-vibrant',
                        'title' => 'Engineering the Next Decade',
                        'subtitle' => 'Northstar Academy is where robotics, research and rigour collide under one glowing roof.',
                        'primary_cta_text' => 'Explore the Labs',
                        'primary_cta_url' => '/about',
                        'secondary_cta_text' => 'Apply Now',
                        'secondary_cta_url' => '/apply-online',
                        'hue_shift' => true,
                        'blob_count' => 6,
                        'intensity' => 0.8,
                        'speed' => 1.4,
                    ]),
                    self::blk('scroll_highlight_text', [
                        'text' => 'We do not just teach technology. We hand pupils the tools and the standards, then step back and let them build — a rover in term one, a working app by term three.',
                        'split_by' => 'character',
                    ]),
                    self::blk('statistics', [
                        'variant' => 'cinematic-overlay',
                        'title' => 'The Signal Behind the Lights',
                        'subtitle' => 'Live metrics from the Northstar network.',
                    ]),
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Build the future you want to log in to',
                        'variant' => 'rise',
                        'trigger' => 'scroll',
                        'intensity' => 1.4,
                        'title_size' => 60,
                    ]),
                    self::blk('coverflow_carousel', [
                        'variant' => 'classic',
                        'title' => 'Signature Spaces',
                        'subtitle' => 'A moving tour of the frontier.',
                        'slides' => [
                            ['image' => self::img('science-lab'), 'title' => 'Robotics Bay 04'],
                            ['image' => self::img('library'), 'title' => 'The Quiet Stack'],
                            ['image' => self::img('assembly-hall'), 'title' => 'The Auditorium'],
                            ['image' => self::img('sports-field'), 'title' => 'The Athletics Dome'],
                            ['image' => self::img('arts-studio'), 'title' => 'Media Lab East'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'The Learning Stack',
                        'subtitle' => 'Four systems working in concert.',
                        'features' => [
                            ['title' => 'Computational Core', 'desc' => 'Programming from Year 7, algorithms by Year 10.', 'image' => self::img('science-lab')],
                            ['title' => 'Design & Fabrication', 'desc' => 'CNC, electronics and 3D printing open to every pupil.', 'image' => self::img('classroom')],
                            ['title' => 'Research Mentors', 'desc' => 'Every senior partners with an industry mentor.', 'image' => self::img('library')],
                            ['title' => 'Esports & Athletics', 'desc' => 'Competitive programmes in both arenas.', 'image' => self::img('sports-field')],
                        ],
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'two-row',
                        'items' => [
                            ['label' => 'NORTHSTAR_ACADEMY'],
                            ['label' => 'ROBOTICS.FIRST'],
                            ['label' => 'EST.2015'],
                            ['label' => 'STEM_ACCREDITED'],
                            ['label' => 'ADMISSIONS_OPEN'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'variant' => 'full-bleed',
                        'title' => 'Join the Next Cohort',
                        'description' => 'Applications for the new academic year close at midnight. Build something that outlives the semester.',
                        'cta_text' => 'Start Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-exterior'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'An Institution Built Like a Studio',
                        'description' => 'Northstar is an independent STEM academy where education is treated as product design — iterated, tested and shipped to a thousand users a year.',
                        'cta_text' => 'Meet the Crew',
                        'cta_url' => '#team',
                        'image_url' => self::img('science-lab'),
                    ]),
                    self::blk('team_directory', [
                        'variant' => 'grid-editorial',
                        'title' => 'The Crew',
                        'subtitle' => 'Engineers, educators and designers.',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Backed By',
                        'logos' => [
                            ['name' => 'Cambridge International', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'FIRST Robotics', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Ministry of Education', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'ZIMSEC', 'logo_url' => self::img('logo-placeholder')],
                        ],
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'Admissions',
                        'description' => 'Entry is by aptitude assessment and an interview with the Head of Studies.',
                        'cta_text' => 'Begin an Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('science-lab'),
                    ]),
                    self::blk('admissions_block', [
                        'variant' => 'panels',
                        'title' => 'The Admission Track',
                        'subtitle' => 'Four commits, then you\'re in the build.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Submit an Application', 'description' => 'Online form plus transcripts and a short letter of intent.'],
                            ['title' => 'Aptitude Assessment', 'description' => 'A logic-and-mathematics assessment in a relaxed session.'],
                            ['title' => 'Build Challenge', 'description' => 'A one-hour hands-on challenge with our senior pupils.'],
                            ['title' => 'Offer & Onboarding', 'description' => 'Offers within ten days, then a sprint-week onboarding.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Latest school report'],
                            ['label' => 'Letter of intent'],
                            ['label' => 'Medical record'],
                        ],
                        'fee_note' => 'Merit scholarships cover up to 80% of fees. Device bursaries available for families in need.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'Do I need my own laptop?', 'a' => 'Yes from Year 9; device bursaries are available, and the academy provides loaner units.'],
                            ['q' => 'Is prior coding experience required?', 'a' => 'No. We start from first principles in Year 7.'],
                            ['q' => 'Is boarding available?', 'a' => 'Yes — two residential houses with late-evening lab access.'],
                            ['q' => 'What are your pass rates?', 'a' => '98% of our 2025 cohort entered their first-choice tertiary programme.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'News & Events',
                        'description' => 'Releases, hackathons, results and the academy\'s live changelog.',
                        'cta_text' => 'Subscribe',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                    ]),
                    self::blk('dynamic_news', [
                        'variant' => 'featured-hero',
                        'title' => 'From the Command Deck',
                        'subtitle' => 'Announcements and release notes.',
                    ]),
                    self::blk('events_calendar', [
                        'variant' => 'timeline',
                        'title' => 'The Academy Calendar',
                        'subtitle' => 'Hackathons, fixtures and graduation days.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-cinematic',
                        'title' => 'Contact Northstar',
                        'description' => 'The admissions desk answers within one business day.',
                        'cta_text' => 'Send an Enquiry',
                        'cta_url' => '#contact',
                        'image_url' => self::img('campus-exterior'),
                    ]),
                    self::blk('contact_map', [
                        'variant' => 'map-led',
                        'title' => 'Find Us',
                        'description' => 'On the innovation corridor, ten minutes from the interchange.',
                        'address' => 'Northstar Academy, 7 Circuit Drive, Harare, Zimbabwe',
                        'phone' => '+263 24 285 9900',
                        'email' => 'admissions@northstar.ac.zw',
                        'hours' => 'Monday–Friday 08:00–17:00',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Ship it. Break it. Rebuild it.',
                        'variant' => 'rise',
                        'trigger' => 'scroll',
                        'title_size' => 58,
                    ]),
                    self::blk('gallery', [
                        'variant' => 'horizontal-scroll',
                        'title' => 'The Frontier in Frames',
                        'images' => [
                            ['image' => self::img('science-lab'), 'caption' => 'Final-year robotics'],
                            ['image' => self::img('sports-field'), 'caption' => 'Athletics dome'],
                            ['image' => self::img('library'), 'caption' => 'The Quiet Stack'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Media Lab East'],
                            ['image' => self::img('assembly-hall'), 'caption' => 'Demo Day'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Clubs & Guilds',
                        'subtitle' => 'Competition teams and creative guilds.',
                        'features' => [
                            ['title' => 'FIRST Robotics Team', 'desc' => 'Regional champions three years running.', 'image' => self::img('science-lab')],
                            ['title' => 'Cyber Guild', 'desc' => 'Capture-the-flag and responsible disclosure.', 'image' => self::img('library')],
                            ['title' => 'Game Jam Guild', 'desc' => 'A 48-hour jam every term.', 'image' => self::img('arts-studio')],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at Northstar',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }

    /* ────────────────────────────────────────────────────────────────
       10. SUNSET INTERNATIONAL — teal / terracotta / sand
       Warm international-school palette with accreditation logo cloud,
       a global orbit gallery and terracotta sunsets.
       ──────────────────────────────────────────────────────────────── */

    protected static function sunset(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'One Campus, Every Horizon',
                        'description' => '<strong>Meridian International</strong> welcomes families from more than thirty nations to a warm, rigorous education that travels anywhere in the world.',
                        'cta_text' => 'Discover Meridian',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Book a Campus Tour',
                        'secondary_cta_url' => '/contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Accreditations & Global Memberships',
                        'logos' => [
                            ['name' => 'Cambridge International', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'International Baccalaureate', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'CIS', 'logo_url' => self::img('logo-placeholder')],
                            ['name' => 'Round Square', 'logo_url' => self::img('logo-placeholder')],
                        ],
                    ]),
                    self::blk('statistics', [
                        'variant' => 'large-number',
                        'title' => 'Meridian by the Numbers',
                        'subtitle' => 'Thirty-one nations, one community.',
                    ]),
                    self::blk('orbit_gallery', [
                        'variant' => 'ellipse',
                        'title' => 'A World on One Campus',
                        'subtitle' => 'Our global community, in orbit.',
                        'center_label' => 'Meridian',
                        'images' => [
                            ['image' => self::img('campus-quad'), 'label' => 'Campus'],
                            ['image' => self::img('library'), 'label' => 'Library'],
                            ['image' => self::img('cafeteria'), 'label' => 'Global Kitchen'],
                            ['image' => self::img('sports-field'), 'label' => 'Fields'],
                            ['image' => self::img('arts-studio'), 'label' => 'The Arts'],
                            ['image' => self::img('assembly-hall'), 'label' => 'Assembly'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'The Meridian Difference',
                        'subtitle' => 'Four reasons families choose us from abroad.',
                        'features' => [
                            ['title' => 'International Curriculum', 'desc' => 'Cambridge and IB programmes taught in English.', 'image' => self::img('classroom')],
                            ['title' => 'Language Pathways', 'desc' => 'English as a second language plus six world languages.', 'image' => self::img('library')],
                            ['title' => 'Global Exchanges', 'desc' => 'Sister-school exchanges on four continents.', 'image' => self::img('campus-exterior')],
                            ['title' => 'Settling-In Support', 'desc' => 'A dedicated families liaison for every new arrival.', 'image' => self::img('campus-quad')],
                        ],
                    ]),
                    self::blk('testimonials', [
                        'variant' => 'image-led',
                        'title' => 'Voices From Around the World',
                        'testimonials' => [
                            ['quote' => 'We relocated mid-year and Meridian made it feel seamless.', 'name' => 'Mrs. Yuki Tanaka', 'role' => 'Parent, Year 5'],
                            ['quote' => 'A globally-minded school with a genuinely warm heart.', 'name' => 'Mr. Emeka Okafor', 'role' => 'Parent, Year 9'],
                            ['quote' => 'The language support was transformative for our son.', 'name' => 'Ms. Layla Haddad', 'role' => 'Parent, Year 2'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'variant' => 'split-panel',
                        'title' => 'Your Child, Ready for Anywhere',
                        'description' => 'Applications open year-round for international and local families.',
                        'cta_text' => 'Apply to Meridian',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-exterior'),
                    ]),
                ],
            ],

            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Our Story',
                        'description' => 'Founded in 1995 to serve an expatriate community, Meridian has grown into the region\'s most international school.',
                        'cta_text' => 'Meet Our Faculty',
                        'cta_url' => '#faculty',
                        'image_url' => self::img('assembly-hall'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('about_section', [
                        'variant' => 'split',
                        'title' => 'Warmth, Rigour, Welcome',
                        'description' => 'We blend the warmth of a family school with the rigour of an international one.',
                        'mission' => 'To prepare every learner for a world without borders.',
                        'vision' => 'A campus where every culture is a classroom.',
                        'image_url' => self::img('campus-quad'),
                    ]),
                    self::blk('team_directory', [
                        'variant' => 'circle-photos',
                        'title' => 'Meet the Faculty',
                        'subtitle' => 'Educators from a dozen countries.',
                    ]),
                ],
            ],

            'apply-online' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Admissions',
                        'description' => 'We accept applications throughout the year and welcome mid-year moves with a settling-in plan.',
                        'cta_text' => 'Start an Application',
                        'cta_url' => '/apply-online',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('admissions_block', [
                        'variant' => 'checklist',
                        'title' => 'A Welcome-First Process',
                        'subtitle' => 'Four gentle stages for local and international families.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Submit an Application', 'description' => 'Online form with school reports and any language assessments.'],
                            ['title' => 'Welcome Conversation', 'description' => 'A chat with the admissions team, in your preferred language where possible.'],
                            ['title' => 'Campus & Class Visit', 'description' => 'A guided tour and a morning in the classroom.'],
                            ['title' => 'Offer & Settling-In', 'description' => 'Offer within a week, then a personal settling-in plan.'],
                        ],
                        'documents' => [
                            ['label' => 'Passport / birth certificate'],
                            ['label' => 'Latest school reports'],
                            ['label' => 'Immunisation record'],
                            ['label' => 'Any prior language assessments'],
                        ],
                        'fee_note' => 'Annual fees are payable in two terms. Sibling and early-bird discounts apply.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Questions Families Ask',
                        'faqs' => [
                            ['q' => 'Do you accept students mid-year?', 'a' => 'Yes — we welcome moves throughout the year with a tailored settling-in plan.'],
                            ['q' => 'Is English support available?', 'a' => 'Our English-as-a-Additional-Language programme supports pupils at every level.'],
                            ['q' => 'Do you follow an international calendar?', 'a' => 'We run a balanced calendar serving both local and international families.'],
                            ['q' => 'Is boarding available?', 'a' => 'From Year 7, with a dedicated international boarding house.'],
                        ],
                    ]),
                ],
            ],

            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'News & Events',
                        'description' => 'International days, results, exchanges and community notices.',
                        'cta_text' => 'Subscribe',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'From the Director\'s Desk',
                        'subtitle' => 'Newsletters for our international community.',
                    ]),
                    self::blk('events_calendar', [
                        'variant' => 'mini-grid',
                        'title' => 'The Meridian Calendar',
                        'subtitle' => 'Culture days, exchanges and open mornings.',
                    ]),
                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'Contact Meridian',
                        'description' => 'The admissions desk speaks your language — probably.',
                        'cta_text' => 'Send an Enquiry',
                        'cta_url' => '#contact',
                        'image_url' => self::img('campus-exterior'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('contact_map', [
                        'variant' => 'map-led',
                        'title' => 'Find Us',
                        'description' => 'On the international corridor, fifteen minutes from the airport.',
                        'address' => 'Meridian International School, 22 Horizon Road, Harare, Zimbabwe',
                        'phone' => '+263 24 266 4100',
                        'email' => 'admissions@meridianint.edu.zw',
                        'hours' => 'Monday–Friday 08:00–16:30',
                    ]),
                ],
            ],

            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-premium',
                        'title' => 'A Campus of Cultures',
                        'description' => 'Culture days, global kitchens and friends from thirty nations.',
                        'cta_text' => 'See the Gallery',
                        'cta_url' => '#gallery',
                        'image_url' => self::img('students-outdoor'),
                        'layout' => 'image-left',
                    ]),
                    self::blk('gallery', [
                        'variant' => 'masonry',
                        'title' => 'Culture in Colour',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Culture day parade'],
                            ['image' => self::img('cafeteria'), 'caption' => 'The global kitchen'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Festival art'],
                            ['image' => self::img('sports-field'), 'caption' => 'World football cup'],
                            ['image' => self::img('library'), 'caption' => 'Language corner'],
                            ['image' => self::img('assembly-hall'), 'caption' => 'Assembly of nations'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Clubs & Committees',
                        'subtitle' => 'Global in scope, personal in spirit.',
                        'features' => [
                            ['title' => 'Model United Nations', 'desc' => 'Our flagship debating team competes regionally.', 'image' => self::img('assembly-hall')],
                            ['title' => 'The Global Kitchen', 'desc' => 'A pupil-run festival of world cuisines.', 'image' => self::img('cafeteria')],
                            ['title' => 'Eco-Internationals', 'desc' => 'Sustainability projects linking sister schools.', 'image' => self::img('campus-quad')],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at Meridian',
                        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                    ]),
                ],
            ],
        ];
    }
}
