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
     * Curated school-relevant photo pool (Pexels, hotlink-friendly). Seeds map
     * deterministically onto this pool so the same block always gets the same
     * real photo — classrooms, campus, students, sports, arts, science — which
     * a school then replaces with its own pictures through the studio.
     */
    protected const SIMPLE_PHOTO_POOL = [
        // Campus & exterior
        8613089, 8613312, 8471835, 256541, 207691, 289740, 1205651, 267885,
        // Classrooms & teaching
        34211744, 5905554, 8926553, 8423394, 6238120, 8535214, 8363036, 8088495,
        // Students & learning
        14909652, 8617542, 8926634, 8423397, 5905709, 8422135, 3747505, 8363775,
        // Science, library, arts
        8872369, 3966555, 11025019, 32719717, 256455, 1181406, 2079246, 3184291,
        // Sports, outdoor & community
        8550496, 19982406, 33613739, 5212703, 5212345, 8197544, 34162709, 3894376,
    ];

    /**
     * Real school photo for a Simple CMS block. Deterministic: the same seed
     * always resolves to the same photo from the curated pool.
     */
    protected static function photo(string $seed, int $w = 1200, int $h = 800): string
    {
        $id = self::SIMPLE_PHOTO_POOL[abs(crc32($seed)) % count(self::SIMPLE_PHOTO_POOL)];

        return "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w={$w}&h={$h}&fit=crop";
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
            // Simple CMS set
            'nordic-light' => self::nordic(),
            'savanna-gold' => self::savanna(),
            'botanical-green' => self::botanical(),
            'mediterranean-blue' => self::mediterranean(),
            'urban-creative' => self::urban(),
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
       centuries-old establishment\'s prospectus.
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
                        'video_url' => '',
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
                        'subtitle' => 'Horizon Institute is a modern city day school where bright, ambitious young people learn to think clearly and build boldly.',
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
                        'text' => 'We do not just teach subjects. We give every pupil the tools to ask their own questions and the confidence to see them through — from a spark of curiosity in class to a finished piece of work they are proud to share.',
                        'split_by' => 'word',
                    ]),
                    self::blk('statistics', [
                        'variant' => 'cinematic-overlay',
                        'title' => 'Horizon in Numbers',
                        'subtitle' => 'A snapshot from our most recent school census and exam results.',
                    ]),
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Think deeply. Build well. Lead kindly.',
                        'variant' => 'smoke',
                        'trigger' => 'scroll',
                        'intensity' => 1.2,
                        'title_size' => 64,
                    ]),
                    self::blk('coverflow_carousel', [
                        'title' => 'Signature Spaces',
                        'subtitle' => 'A quick tour of the places where Horizon pupils spend their days.',
                        'slides' => [
                            ['image' => self::img('library'), 'title' => 'The Quiet Commons'],
                            ['image' => self::img('science-lab'), 'title' => 'The Innovation Lab'],
                            ['image' => self::img('assembly-hall'), 'title' => 'The Forum Hall'],
                            ['image' => self::img('sports-field'), 'title' => 'North Field'],
                            ['image' => self::img('arts-studio'), 'title' => 'Studio East'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'title' => 'Why Families Choose Horizon',
                        'subtitle' => 'Four things that set our school apart, felt every day.',
                        'features' => [
                            ['title' => 'Mastery-Led Learning', 'desc' => 'Small classes where progress follows understanding, not the calendar.', 'image' => self::img('classroom')],
                            ['title' => 'Practical Science', 'desc' => 'A well-equipped lab where pupils design and run their own experiments.', 'image' => self::img('science-lab')],
                            ['title' => 'Creative Media & Arts', 'desc' => 'A pupil-run newsroom, film unit and studio for music and drama.', 'image' => self::img('arts-studio')],
                            ['title' => 'Sport for Everyone', 'desc' => 'From casual games to competitive squads, every pupil finds their field.', 'image' => self::img('sports-field')],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Join the Next Year Group',
                        'description' => 'Applications for the coming academic year are now open. Come for a visit and see Horizon for yourself.',
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
                        'title' => 'A School Built Around Curiosity',
                        'description' => 'Horizon Institute is an independent co-educational day school for Years 1–13. We combine a strong academic core with hands-on science, creative arts and sport — so every pupil leaves with the habit of asking good questions and the skill to answer them.',
                        'cta_text' => 'Read the Story',
                        'cta_url' => '/about',
                        'image_url' => self::img('campus-quad'),
                        'layout' => 'full-bleed',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Our Mission & Ethos',
                        'description' => 'Founded by educators and engineers in 2011, Horizon was built on a simple conviction: that learning sticks when pupils build, make and test things for themselves.',
                        'mission' => 'To make ambitious, creative learning accessible to every pupil in our community.',
                        'vision' => 'To send out graduates who think clearly, work with care and shape the world around them for the better.',
                        'image_url' => self::img('science-lab'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'Our Teachers',
                        'subtitle' => 'Passionate, well-qualified educators — engineers, artists, scientists and musicians.',
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
                        'title' => 'How to Apply',
                        'subtitle' => 'A simple, welcoming process from enquiry to first day.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Apply Online', 'description' => 'Complete our short online application form with the essentials.'],
                            ['title' => 'Creative Task', 'description' => 'A short open-ended task — write, design or build something that shows who you are.'],
                            ['title' => 'Visit & Interview', 'description' => 'A relaxed visit day on campus where pupils meet their future teachers.'],
                            ['title' => 'Offer & Welcome', 'description' => 'Offers are made within a fortnight, and our team helps with every step of enrolment.'],
                        ],
                        'documents' => [
                            ['label' => 'Most recent school report'],
                            ['label' => 'One reference'],
                            ['label' => 'A note or portfolio of work'],
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
                        'description' => 'News, results and notices from around the school.',
                        'cta_text' => 'Contact',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                        'layout' => 'full-bleed',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest News',
                        'subtitle' => 'Results, achievements and updates from across the school.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Upcoming Events',
                        'subtitle' => 'Concerts, productions, open days and sports fixtures.',
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
                        'text' => 'Life beyond the classroom',
                        'variant' => 'smoke',
                        'trigger' => 'scroll',
                        'title_size' => 72,
                    ]),
                    self::blk('gallery', [
                        'variant' => 'immersive-grid',
                        'title' => 'The Campus in Pictures',
                        'columns' => 3,
                        'images' => [
                            ['image' => self::img('students-outdoor'), 'caption' => 'Project showcase day'],
                            ['image' => self::img('library'), 'caption' => 'The Quiet Commons at dusk'],
                            ['image' => self::img('science-lab'), 'caption' => 'The Innovation Lab in use'],
                            ['image' => self::img('arts-studio'), 'caption' => 'A Studio East rehearsal'],
                            ['image' => self::img('cafeteria'), 'caption' => 'Lunchtime in the Canteen'],
                            ['image' => self::img('sports-field'), 'caption' => 'After-school fixture'],
                        ],
                    ]),
                    self::blk('coverflow_carousel', [
                        'title' => 'Clubs & Activities',
                        'slides' => [
                            ['image' => self::img('science-lab'), 'title' => 'Robotics Club'],
                            ['image' => self::img('arts-studio'), 'title' => 'Film Unit'],
                            ['image' => self::img('library'), 'title' => 'Book Club'],
                            ['image' => self::img('sports-field'), 'title' => 'Futsal League'],
                            ['image' => self::img('classroom'), 'title' => 'Debate Society'],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'Inside Horizon',
                        'video_url' => '',
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
                        'text' => 'Big school spirit, warm school heart',
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
                        'video_url' => '',
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
                    self::blk('testimonials', [
                        'variant' => 'image-led',
                        'title' => 'Kind Words',
                        'testimonials' => [
                            ['quote' => 'Greenleaf raised our children better than we could have asked. The teachers know them, and they know us.', 'name' => 'The Ndlovu Family', 'role' => 'Parents of three'],
                            ['quote' => 'My shy boy became a confident one here. That is the whole review.', 'name' => 'Mr. T. Banda', 'role' => 'Parent, Year 5'],
                            ['quote' => 'It feels less like a school and more like the village it takes to raise a child.', 'name' => 'Ms. R. Zhou', 'role' => 'Grandparent'],
                        ],
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
                        'video_url' => '',
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
                        'video_url' => '',
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
                        'text' => 'Every child has a story worth cheering for',
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
                        'video_url' => '',
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
                        'video_url' => '',
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
                        'title' => 'Preparing the Next Generation of Builders',
                        'subtitle' => 'Northstar Academy is a science, technology and mathematics day school where young people learn to think rigorously and build with confidence.',
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
                        'text' => 'We do not just teach technology. We give pupils the tools, the standards and the time to build for themselves — a working robot one term, a finished app the next.',
                        'split_by' => 'character',
                    ]),
                    self::blk('statistics', [
                        'variant' => 'cinematic-overlay',
                        'title' => 'Northstar in Numbers',
                        'subtitle' => 'Rigour and results, measured honestly each year.',
                    ]),
                    self::blk('kinetic_reveal_heading', [
                        'text' => 'Build the future you want to live in',
                        'variant' => 'rise',
                        'trigger' => 'scroll',
                        'intensity' => 1.4,
                        'title_size' => 60,
                    ]),
                    self::blk('coverflow_carousel', [
                        'variant' => 'classic',
                        'title' => 'Signature Spaces',
                        'subtitle' => 'A quick look around our purpose-built campus.',
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
                        'title' => 'Why Northstar',
                        'subtitle' => 'Four things families come for, and stay for.',
                        'features' => [
                            ['title' => 'Computing & Coding', 'desc' => 'Programming from Year 7, with algorithms and problem-solving built into the timetable.', 'image' => self::img('science-lab')],
                            ['title' => 'Design & Making', 'desc' => 'CNC, electronics and 3D printing open to every pupil in our fabrication lab.', 'image' => self::img('classroom')],
                            ['title' => 'Research Mentors', 'desc' => 'Every senior pupil is guided by a working scientist or engineer.', 'image' => self::img('library')],
                            ['title' => 'Sport & Esports', 'desc' => 'Strong athletics and sports teams, alongside healthy, supervised gaming clubs.', 'image' => self::img('sports-field')],
                        ],
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'two-row',
                        'items' => [
                            ['label' => 'Northstar Academy'],
                            ['label' => 'Robotics — Regional Champions'],
                            ['label' => 'Established 2015'],
                            ['label' => 'Cambridge & ZIMSEC Accredited'],
                            ['label' => 'Admissions Now Open'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'variant' => 'full-bleed',
                        'title' => 'Join the Next Year Group',
                        'description' => 'Applications for the coming academic year are now open. Come and see the labs, meet the teachers and discover what your child could build here.',
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
                        'title' => 'A School Built for the Future',
                        'description' => 'Northstar Academy is an independent STEM day school for Years 1–13. Strong academic teaching sits alongside hands-on science, technology and making — so every pupil leaves equipped to think clearly and build well.',
                        'cta_text' => 'Meet Our Teachers',
                        'cta_url' => '#team',
                        'image_url' => self::img('science-lab'),
                    ]),
                    self::blk('team_directory', [
                        'variant' => 'grid-editorial',
                        'title' => 'Our Teachers',
                        'subtitle' => 'Scientists, engineers, artists and mathematicians who love teaching.',
                    ]),
                    self::blk('logo_cloud', [
                        'title' => 'Our Partners',
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
                        'title' => 'How to Apply',
                        'subtitle' => 'A clear, welcoming process from enquiry to first day.',
                        'show_form' => true,
                        'steps' => [
                            ['title' => 'Submit an Application', 'description' => 'Online form plus transcripts and a short letter explaining why Northstar.'],
                            ['title' => 'Aptitude Assessment', 'description' => 'A relaxed logic-and-mathematics session with our admissions team.'],
                            ['title' => 'Hands-On Challenge', 'description' => 'A one-hour practical task, guided by our senior pupils.'],
                            ['title' => 'Offer & Enrolment', 'description' => 'Offers within ten days, then a friendly induction week before term begins.'],
                        ],
                        'documents' => [
                            ['label' => 'Birth certificate'],
                            ['label' => 'Latest school report'],
                            ['label' => 'Letter of intent'],
                            ['label' => 'Medical record'],
                        ],
                        'fee_note' => 'Merit scholarships cover up to 80% of fees. Device bursaries are available for families in need.',
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'Do I need my own laptop?', 'a' => 'Yes from Year 9; device bursaries are available, and the academy provides loaner units.'],
                            ['q' => 'Is prior coding experience required?', 'a' => 'No. We begin with first principles in Year 7.'],
                            ['q' => 'Is boarding available?', 'a' => 'Yes — two residential houses with supervised late-evening study and lab access.'],
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
                        'description' => 'Results, project showcases, competitions and notices from around the academy.',
                        'cta_text' => 'Subscribe',
                        'cta_url' => '/contact',
                        'image_url' => self::img('news-cover'),
                    ]),
                    self::blk('dynamic_news', [
                        'variant' => 'featured-hero',
                        'title' => 'Latest News',
                        'subtitle' => 'Achievements, results and updates from across the school.',
                    ]),
                    self::blk('events_calendar', [
                        'variant' => 'timeline',
                        'title' => 'The Academy Calendar',
                        'subtitle' => 'Showcases, fixtures, open days and graduation ceremonies.',
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
                        'text' => 'Learn it. Make it. Share it.',
                        'variant' => 'rise',
                        'trigger' => 'scroll',
                        'title_size' => 58,
                    ]),
                    self::blk('gallery', [
                        'variant' => 'horizontal-scroll',
                        'title' => 'Life at Northstar',
                        'images' => [
                            ['image' => self::img('science-lab'), 'caption' => 'Final-year robotics project'],
                            ['image' => self::img('sports-field'), 'caption' => 'Athletics on the dome field'],
                            ['image' => self::img('library'), 'caption' => 'The Quiet Stack study hall'],
                            ['image' => self::img('arts-studio'), 'caption' => 'Media Lab East in use'],
                            ['image' => self::img('assembly-hall'), 'caption' => 'Project showcase day'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Clubs & Activities',
                        'subtitle' => 'Competition teams, creative clubs and community projects.',
                        'features' => [
                            ['title' => 'FIRST Robotics Team', 'desc' => 'Regional champions three years running.', 'image' => self::img('science-lab')],
                            ['title' => 'Cybersecurity Club', 'desc' => 'Learn online safety and how to protect systems responsibly.', 'image' => self::img('library')],
                            ['title' => 'Game Design Club', 'desc' => 'Design and test games over a fun term-long project.', 'image' => self::img('arts-studio')],
                        ],
                    ]),
                    self::blk('video_embed', [
                        'title' => 'A Day at Northstar',
                        'video_url' => '',
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
                        'video_url' => '',
                    ]),
                ],
            ],
        ];
    }

    /* ═══════════════════════════════════════════════════════════════
       SIMPLE CMS SET — five distinct non-dark directions
       ═══════════════════════════════════════════════════════════════ */

    /** NORDIC LIGHT — Scandinavian clean: white, pale wood, soft blue. */
    protected static function nordic(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Where Clarity Meets Curiosity',
                        'description' => 'Fjordview Academy offers a calm, focused learning environment where every student discovers their potential through inquiry, creativity and community.',
                        'cta_text' => 'Explore Admissions',
                        'cta_url' => '/admissions',
                        'secondary_cta_text' => 'Book a Tour',
                        'secondary_cta_url' => '/contact',
                        'image_url' => self::photo('nordic-campus'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'A Philosophy of Light',
                        'description' => 'Inspired by Scandinavian educational traditions, we believe that learning thrives in spaces of simplicity, trust and natural beauty.',
                        'mission' => 'To nurture independent thinkers who approach the world with empathy, curiosity and quiet confidence.',
                        'vision' => 'A community where every child feels seen, challenged and inspired to grow.',
                        'image_url' => self::photo('nordic-classroom'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'Fjordview in Numbers',
                        'subtitle' => 'A small school with an outsized impact.',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'What Makes Us Different',
                        'subtitle' => 'Programmes designed for depth, not breadth.',
                        'features' => [
                            ['title' => 'Inquiry-Based Learning', 'desc' => 'Students lead their own investigations with teacher mentorship.', 'image' => self::photo('nordic-inquiry')],
                            ['title' => 'Nature Classroom', 'desc' => 'Weekly outdoor education in our forest campus and fjord shoreline.', 'image' => self::photo('nordic-nature')],
                            ['title' => 'Design & Making', 'desc' => 'A fully equipped workshop where ideas become prototypes.', 'image' => self::photo('nordic-makerspace')],
                            ['title' => 'Global Exchange', 'desc' => 'Partner schools in Norway, Japan and Kenya for cultural immersion.', 'image' => self::photo('nordic-exchange')],
                        ],
                    ]),
                    self::blk('testimonials', [
                        'title' => 'Voices from Our Community',
                        'items' => [
                            ['quote' => 'The calm atmosphere helps my son focus like never before. He comes home excited about what he discovered.', 'name' => 'Anna Bergström', 'role' => 'Parent, Grade 7'],
                            ['quote' => 'I love that we learn outside as much as inside. The forest classroom is my favourite place.', 'name' => 'Lars Nyberg', 'role' => 'Student, Grade 5'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Ready to See Fjordview for Yourself?',
                        'description' => 'Join us for an open morning or schedule a personal tour.',
                        'cta_text' => 'Get in Touch',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Our Story',
                        'description' => 'Founded in 2010, Fjordview Academy was built on the belief that education should be as beautiful and intentional as the landscape that surrounds it.',
                        'image_url' => self::photo('nordic-history'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Mission & Values',
                        'description' => 'Every decision at Fjordview is guided by three principles: simplicity, sustainability and student agency.',
                        'mission' => 'To empower learners to think independently, act responsibly and live curiously.',
                        'vision' => 'To be the benchmark for student-centred education in East Africa.',
                        'image_url' => self::photo('nordic-values'),
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'A Word from Our Principal',
                        'principal_name' => 'Ingrid Solberg',
                        'principal_title' => 'Principal',
                        'description' => 'At Fjordview, we don\'t rush childhood. We give students the time, space and support to become thoughtful, capable young people.',
                        'image_url' => self::photo('nordic-principal'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'Our Leadership Team',
                        'subtitle' => 'Experienced educators who know every student by name.',
                        'members' => [
                            ['name' => 'Ingrid Solberg', 'role' => 'Principal', 'image' => self::photo('nordic-team1')],
                            ['name' => 'David Ochieng', 'role' => 'Deputy Principal', 'image' => self::photo('nordic-team2')],
                            ['name' => 'Maria Santos', 'role' => 'Head of Primary', 'image' => self::photo('nordic-team3')],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Join Our Community',
                        'description' => 'Applications for the next academic year are now open.',
                        'cta_text' => 'Apply Now',
                        'cta_url' => '/admissions',
                    ]),
                ],
            ],
            'admissions' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Begin Your Journey',
                        'description' => 'We welcome families who value deep learning, kindness and community. Here is how to join Fjordview.',
                        'cta_text' => 'Start Application',
                        'cta_url' => '/apply',
                        'image_url' => self::photo('nordic-admissions'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Three Simple Steps',
                        'subtitle' => 'Our admissions process is designed to be transparent and stress-free.',
                        'features' => [
                            ['title' => '1. Enquire', 'desc' => 'Fill in the enquiry form or visit us on an open morning.', 'image' => self::photo('nordic-step1')],
                            ['title' => '2. Visit & Assess', 'desc' => 'A friendly classroom visit and informal assessment day.', 'image' => self::photo('nordic-step2')],
                            ['title' => '3. Offer & Enrol', 'desc' => 'Receive your offer letter and complete enrolment online.', 'image' => self::photo('nordic-step3')],
                        ],
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'What ages do you accept?', 'a' => 'We welcome students from Age 3 (Pre-Nursery) through Grade 12.'],
                            ['q' => 'Do you offer boarding?', 'a' => 'Yes, boarding is available for Grades 6-12 in our purpose-built residence.'],
                            ['q' => 'What curriculum do you follow?', 'a' => 'We offer the Cambridge International curriculum alongside a rich local studies programme.'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Have Questions?',
                        'description' => 'Our admissions team is here to help you every step of the way.',
                        'cta_text' => 'Contact Admissions',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'What\'s Happening at Fjordview',
                        'description' => 'Stories, celebrations and upcoming events from our school community.',
                        'image_url' => self::photo('nordic-news'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest News',
                        'subtitle' => 'Updates from the classroom and beyond.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Upcoming Events',
                        'subtitle' => 'Mark your calendar.',
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Stay Connected',
                        'description' => 'Subscribe to our termly newsletter for stories from campus.',
                        'cta_text' => 'Contact Us',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'We\'d Love to Hear from You',
                        'description' => 'Whether you have a question about admissions or just want to say hello, our team is ready to help.',
                        'image_url' => self::photo('nordic-contact'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Us',
                        'address' => '12 Lakeside Drive, Fjordview, Harare',
                        'phone' => '+263 77 123 4567',
                        'email' => 'hello@fjordview.ac.zw',
                    ]),
                ],
            ],
            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Life Beyond the Classroom',
                        'description' => 'From forest expeditions to design challenges, student life at Fjordview is rich, active and full of discovery.',
                        'image_url' => self::photo('nordic-studentlife'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('gallery', [
                        'title' => 'A Glimpse of Our World',
                        'subtitle' => 'Moments from campus life.',
                        'columns' => 3,
                        'images' => [
                            ['url' => self::photo('nordic-gal1'), 'caption' => 'Morning circle in the forest'],
                            ['url' => self::photo('nordic-gal2'), 'caption' => 'Science fair preparations'],
                            ['url' => self::photo('nordic-gal3'), 'caption' => 'Inter-house sports day'],
                            ['url' => self::photo('nordic-gal4'), 'caption' => 'Art exhibition opening'],
                            ['url' => self::photo('nordic-gal5'), 'caption' => 'Community service day'],
                            ['url' => self::photo('nordic-gal6'), 'caption' => 'Graduation celebration'],
                        ],
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Clubs & Activities',
                        'subtitle' => 'Something for every interest.',
                        'features' => [
                            ['title' => 'Eco Warriors', 'desc' => 'Environmental club managing our campus garden and recycling programme.', 'image' => self::photo('nordic-eco')],
                            ['title' => 'Robotics Lab', 'desc' => 'Build, code and compete in regional robotics challenges.', 'image' => self::photo('nordic-robotics')],
                            ['title' => 'Drama Society', 'desc' => 'Two major productions per year plus improvisation evenings.', 'image' => self::photo('nordic-drama')],
                            ['title' => 'Outdoor Adventure', 'desc' => 'Hiking, kayaking and camping in the nearby mountains.', 'image' => self::photo('nordic-adventure')],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Experience Fjordview',
                        'description' => 'The best way to understand our school is to visit.',
                        'cta_text' => 'Book a Visit',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
        ];
    }

    /** SAVANNA GOLD — African contemporary: terracotta, gold, bold geometric. */
    protected static function savanna(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Rooted in Heritage, Ready for the World',
                        'description' => 'Baobab International School blends the warmth of African hospitality with world-class Cambridge education, preparing confident global citizens from a proud local foundation.',
                        'cta_text' => 'Discover Baobab',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Apply Now',
                        'secondary_cta_url' => '/admissions',
                        'image_url' => self::photo('savanna-campus'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('marquee_ticker', [
                        'variant' => 'single-row',
                        'items' => [
                            ['label' => 'Cambridge International School'],
                            ['label' => 'Founded 2005'],
                            ['label' => 'Ages 3-18'],
                            ['label' => 'Day & Boarding'],
                            ['label' => '32 Nationalities'],
                        ],
                    ]),
                    self::blk('about_section', [
                        'title' => 'The Baobab Philosophy',
                        'description' => 'Like the great baobab tree, our school is a place of shelter, nourishment and deep roots. We grow strong learners who stand tall in any soil.',
                        'mission' => 'To provide an education that honours African identity while opening doors to global opportunity.',
                        'vision' => 'To be the leading international school in Southern Africa, known for academic excellence and cultural pride.',
                        'image_url' => self::photo('savanna-philosophy'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'Baobab by the Numbers',
                        'subtitle' => 'Growing stronger every year.',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Our Pillars of Excellence',
                        'subtitle' => 'Four cornerstones of the Baobab experience.',
                        'features' => [
                            ['title' => 'Academic Rigour', 'desc' => 'Cambridge curriculum with a 98% pass rate at IGCSE and A-Level.', 'image' => self::photo('savanna-academic')],
                            ['title' => 'Cultural Pride', 'desc' => 'Shona, Ndebele, French and Mandarin language programmes.', 'image' => self::photo('savanna-culture')],
                            ['title' => 'Sporting Excellence', 'desc' => 'Championship-winning rugby, netball, athletics and swimming.', 'image' => self::photo('savanna-sport')],
                            ['title' => 'Community Service', 'desc' => 'Every student contributes 40 hours of service per year.', 'image' => self::photo('savanna-service')],
                        ],
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'A Word from the Head of School',
                        'principal_name' => 'Dr. Tendai Moyo',
                        'principal_title' => 'Head of School',
                        'description' => 'We are raising a generation that is proud of where they come from and prepared for where they are going. That is the Baobab promise.',
                        'image_url' => self::photo('savanna-principal'),
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Join the Baobab Family',
                        'description' => 'Applications for 2027 are now open. Limited places available.',
                        'cta_text' => 'Apply Now',
                        'cta_url' => '/admissions',
                    ]),
                ],
            ],
            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Twenty Years of Growing Minds',
                        'description' => 'From a single classroom in 2005 to a thriving campus of 800 students, Baobab International School has become a beacon of excellence in the region.',
                        'image_url' => self::photo('savanna-history'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Our Story',
                        'description' => 'Founded by a group of passionate educators who believed that African children deserved both world-class academics and cultural grounding.',
                        'mission' => 'To nurture proud, capable and compassionate young people.',
                        'vision' => 'A future where every African child can compete on the global stage.',
                        'image_url' => self::photo('savanna-story'),
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'From the Head of School',
                        'principal_name' => 'Dr. Tendai Moyo',
                        'principal_title' => 'Head of School',
                        'description' => 'Every child who walks through our gates carries the potential to change the world. Our job is to give them the tools and the confidence to do it.',
                        'image_url' => self::photo('savanna-head'),
                    ]),
                    self::blk('team_directory', [
                        'title' => 'Leadership Team',
                        'subtitle' => 'Dedicated educators leading with vision.',
                        'members' => [
                            ['name' => 'Dr. Tendai Moyo', 'role' => 'Head of School', 'image' => self::photo('savanna-t1')],
                            ['name' => 'Sarah Chen', 'role' => 'Deputy Head (Academic)', 'image' => self::photo('savanna-t2')],
                            ['name' => 'James Ochieng', 'role' => 'Head of Pastoral Care', 'image' => self::photo('savanna-t3')],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'See Our Campus',
                        'description' => 'We would love to show you around.',
                        'cta_text' => 'Book a Tour',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'admissions' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Your Baobab Journey Starts Here',
                        'description' => 'Join a community that celebrates heritage, excellence and ambition. Our admissions process is straightforward and welcoming.',
                        'cta_text' => 'Start Application',
                        'cta_url' => '/apply',
                        'image_url' => self::photo('savanna-admissions'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'How to Apply',
                        'subtitle' => 'Four steps to becoming a Baobab student.',
                        'features' => [
                            ['title' => '1. Enquire', 'desc' => 'Submit an online enquiry or visit us at an open day.', 'image' => self::photo('savanna-s1')],
                            ['title' => '2. Assessment', 'desc' => 'English, Maths and a friendly interview.', 'image' => self::photo('savanna-s2')],
                            ['title' => '3. Offer', 'desc' => 'Receive your offer letter within two weeks.', 'image' => self::photo('savanna-s3')],
                            ['title' => '4. Enrol', 'desc' => 'Complete registration and join the Baobab family.', 'image' => self::photo('savanna-s4')],
                        ],
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Admissions FAQ',
                        'faqs' => [
                            ['q' => 'What are the fees?', 'a' => 'Tuition ranges from $2,400 (Pre-School) to $4,800 (A-Level) per term. Boarding additional.'],
                            ['q' => 'Do you offer scholarships?', 'a' => 'Yes, we offer merit and needs-based scholarships covering up to 100% of tuition.'],
                            ['q' => 'What is the student-teacher ratio?', 'a' => 'We maintain a maximum 18:1 ratio across all year groups.'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Ready to Apply?',
                        'description' => 'Our admissions team will guide you through every step.',
                        'cta_text' => 'Contact Admissions',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'The Baobab Bulletin',
                        'description' => 'Celebrating achievements, sharing stories and keeping you informed.',
                        'image_url' => self::photo('savanna-news'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest News',
                        'subtitle' => 'From the Baobab campus.',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'What\'s On',
                        'subtitle' => 'Upcoming events and term dates.',
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Never Miss a Story',
                        'description' => 'Follow us on social media or subscribe to our newsletter.',
                        'cta_text' => 'Get in Touch',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Karibu! Welcome.',
                        'description' => 'We would love to hear from you. Reach out with any question about Baobab International School.',
                        'image_url' => self::photo('savanna-contact'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Us',
                        'address' => '45 Acacia Avenue, Harare',
                        'phone' => '+263 77 987 6543',
                        'email' => 'info@baobabacademy.co.zw',
                    ]),
                ],
            ],
            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Vibrant, Colourful, Unforgettable',
                        'description' => 'Life at Baobab is a celebration of talent, friendship and adventure. Every day brings new opportunities to shine.',
                        'image_url' => self::photo('savanna-life'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('gallery', [
                        'title' => 'Campus Moments',
                        'subtitle' => 'A snapshot of life at Baobab.',
                        'columns' => 3,
                        'images' => [
                            ['url' => self::photo('savanna-g1'), 'caption' => 'Annual arts festival'],
                            ['url' => self::photo('savanna-g2'), 'caption' => 'Inter-house cooking competition'],
                            ['url' => self::photo('savanna-g3'), 'caption' => 'Science and innovation fair'],
                            ['url' => self::photo('savanna-g4'), 'caption' => 'Cultural dress day'],
                            ['url' => self::photo('savanna-g5'), 'caption' => 'Community outreach programme'],
                            ['url' => self::photo('savanna-g6'), 'caption' => 'Prize-giving ceremony'],
                        ],
                    ]),
                    self::blk('coverflow_carousel', [
                        'title' => 'Clubs & Societies',
                        'subtitle' => 'Find your passion at Baobab.',
                        'slides' => [
                            ['title' => 'Debate Society', 'description' => 'National champions three years running.', 'image' => self::photo('savanna-c1')],
                            ['title' => 'Music & Choir', 'desc' => 'From mbira to marimba, our ensemble performs across the region.', 'image' => self::photo('savanna-c2')],
                            ['title' => 'Basketball Academy', 'desc' => 'Train with former national team coaches.', 'image' => self::photo('savanna-c3')],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Experience Baobab Life',
                        'description' => 'Visit us during an open day to feel the energy for yourself.',
                        'cta_text' => 'Book a Visit',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
        ];
    }

    /** BOTANICAL GREEN — nature-inspired: forest green, sage, cream, organic. */
    protected static function botanical(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Growing Minds, Rooted in Nature',
                        'description' => 'Greenwood Montessori School nurtures the whole child through hands-on learning, outdoor exploration and a deep respect for the natural world.',
                        'cta_text' => 'Explore Greenwood',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Enrol Now',
                        'secondary_cta_url' => '/admissions',
                        'image_url' => self::photo('botanical-campus'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'The Greenwood Way',
                        'description' => 'Our campus is a living classroom. From the vegetable garden to the nature trail, every corner invites curiosity and discovery.',
                        'mission' => 'To foster independent, compassionate learners who feel connected to the earth and their community.',
                        'vision' => 'A school where education and nature grow together.',
                        'image_url' => self::photo('botanical-about'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'Greenwood Grows',
                        'subtitle' => 'Nurturing growth since 2012.',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Learning in Full Bloom',
                        'subtitle' => 'Programmes that connect children with the natural world.',
                        'features' => [
                            ['title' => 'Montessori Method', 'desc' => 'Child-led learning with mixed-age classrooms and hands-on materials.', 'image' => self::photo('botanical-montessori')],
                            ['title' => 'Forest School', 'desc' => 'Weekly sessions in our indigenous forest classroom.', 'image' => self::photo('botanical-forest')],
                            ['title' => 'Garden to Table', 'desc' => 'Students grow, harvest and cook their own food.', 'image' => self::photo('botanical-garden')],
                            ['title' => 'Animal Care', 'desc' => 'Our school farm is home to rabbits, chickens and a small dairy herd.', 'image' => self::photo('botanical-animals')],
                        ],
                    ]),
                    self::blk('testimonials', [
                        'title' => 'What Families Say',
                        'items' => [
                            ['quote' => 'Greenwood gave my daughter a love of learning that no classroom could. She comes home full of stories about the garden and the forest.', 'name' => 'Grace Mutasa', 'role' => 'Parent'],
                            ['quote' => 'The teachers truly see each child. My son has blossomed since joining Greenwood.', 'name' => 'Peter Chikafu', 'role' => 'Parent'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Come and See Greenwood',
                        'description' => 'We host open mornings every first Saturday of the month.',
                        'cta_text' => 'Book a Visit',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Our Roots Run Deep',
                        'description' => 'Greenwood was founded by a group of parents and educators who believed that children learn best when they are free to explore.',
                        'image_url' => self::photo('botanical-history'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Philosophy & Approach',
                        'description' => 'We follow the Montessori philosophy, adapted for our African context, with a strong emphasis on outdoor learning and environmental stewardship.',
                        'mission' => 'To educate the whole child: mind, body and spirit.',
                        'vision' => 'To raise a generation of environmentally conscious, confident learners.',
                        'image_url' => self::photo('botanical-philosophy'),
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'From Our Director',
                        'principal_name' => 'Ruth Ncube',
                        'principal_title' => 'School Director',
                        'description' => 'Every child is a seed. Our job is to provide the soil, the sunlight and the water — and then watch them grow in their own unique way.',
                        'image_url' => self::photo('botanical-director'),
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Join Our Community',
                        'description' => 'Applications are open for all year groups.',
                        'cta_text' => 'Apply Now',
                        'cta_url' => '/admissions',
                    ]),
                ],
            ],
            'admissions' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Plant the Seed',
                        'description' => 'Ready to give your child the gift of nature-based education? Here is how to join Greenwood.',
                        'cta_text' => 'Start Application',
                        'cta_url' => '/apply',
                        'image_url' => self::photo('botanical-admissions'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Your Greenwood Journey',
                        'subtitle' => 'Simple steps to enrolment.',
                        'features' => [
                            ['title' => 'Visit', 'desc' => 'Come to an open morning or schedule a personal tour.', 'image' => self::photo('botanical-visit')],
                            ['title' => 'Apply', 'desc' => 'Complete the online application form.', 'image' => self::photo('botanical-apply')],
                            ['title' => 'Meet', 'desc' => 'A friendly meeting with your child and our teachers.', 'image' => self::photo('botanical-meet')],
                        ],
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Common Questions',
                        'faqs' => [
                            ['q' => 'What ages do you accept?', 'a' => 'Ages 3 to 11 (Pre-School through Grade 6).'],
                            ['q' => 'Is this a full Montessori school?', 'a' => 'Yes, we are an accredited Montessori school with trained guides in every classroom.'],
                            ['q' => 'Do you have a waiting list?', 'a' => 'Some year groups have waiting lists. We recommend applying early.'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Questions About Admissions?',
                        'description' => 'We are happy to help.',
                        'cta_text' => 'Contact Us',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Growing Together',
                        'description' => 'News, updates and celebrations from the Greenwood community.',
                        'image_url' => self::photo('botanical-news'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest News',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Upcoming Events',
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Stay in the Loop',
                        'description' => 'Follow our journey on social media.',
                        'cta_text' => 'Get in Touch',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Let\'s Talk',
                        'description' => 'We would love to hear from you. Whether you have a question or just want to say hello.',
                        'image_url' => self::photo('botanical-contact'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Our Location',
                        'address' => '78 Greenhill Road, Harare',
                        'phone' => '+263 77 456 7890',
                        'email' => 'hello@greenwoodmontessori.co.zw',
                    ]),
                ],
            ],
            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Every Day is an Adventure',
                        'description' => 'From mud kitchens to music circles, life at Greenwood is joyful, active and full of wonder.',
                        'image_url' => self::photo('botanical-life'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('gallery', [
                        'title' => 'Moments from Campus',
                        'columns' => 3,
                        'images' => [
                            ['url' => self::photo('botanical-g1'), 'caption' => 'Harvesting vegetables'],
                            ['url' => self::photo('botanical-g2'), 'caption' => 'Forest school session'],
                            ['url' => self::photo('botanical-g3'), 'caption' => 'Music and movement'],
                            ['url' => self::photo('botanical-g4'), 'caption' => 'Animal feeding time'],
                            ['url' => self::photo('botanical-g5'), 'caption' => 'Art in the garden'],
                            ['url' => self::photo('botanical-g6'), 'caption' => 'End-of-term celebration'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'See Greenwood in Action',
                        'description' => 'Nothing compares to visiting in person.',
                        'cta_text' => 'Book a Tour',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
        ];
    }

    /** MEDITERRANEAN BLUE — coastal: cobalt, white, terracotta, sun-drenched. */
    protected static function mediterranean(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Bright Futures Start Here',
                        'description' => 'Azure International School brings the warmth of Mediterranean hospitality to a world-class education, where every student shines.',
                        'cta_text' => 'Discover Azure',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Book a Tour',
                        'secondary_cta_url' => '/contact',
                        'image_url' => self::photo('mediterranean-campus'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'The Azure Spirit',
                        'description' => 'Like the Mediterranean coast, our school is a meeting place — of cultures, ideas and dreams. We celebrate diversity and academic ambition in equal measure.',
                        'mission' => 'To provide a joyful, rigorous education that prepares students for a connected world.',
                        'vision' => 'A school community as bright, warm and welcoming as the coast that inspires us.',
                        'image_url' => self::photo('mediterranean-spirit'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'Azure by the Numbers',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Why Families Choose Azure',
                        'features' => [
                            ['title' => 'Bilingual Programme', 'desc' => 'English and French from age 5, with Spanish option in secondary.', 'image' => self::photo('mediterranean-language')],
                            ['title' => 'STEM Innovation Hub', 'desc' => 'Robotics, coding and engineering in our purpose-built maker space.', 'image' => self::photo('mediterranean-stem')],
                            ['title' => 'Performing Arts', 'desc' => 'A 400-seat auditorium and programmes in music, drama and dance.', 'image' => self::photo('mediterranean-arts')],
                            ['title' => 'Mediterranean Kitchen', 'desc' => 'Our cooking programme teaches nutrition and cultural cuisine.', 'image' => self::photo('mediterranean-kitchen')],
                        ],
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'Welcome from Our Principal',
                        'principal_name' => 'Elena Papadopoulos',
                        'principal_title' => 'Principal',
                        'description' => 'At Azure, we believe that a bright education should be as joyful as it is rigorous. Every day, I see our students proving that those two things go together beautifully.',
                        'image_url' => self::photo('mediterranean-principal'),
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Join the Azure Family',
                        'description' => 'Come and see what makes our school special.',
                        'cta_text' => 'Apply Now',
                        'cta_url' => '/admissions',
                    ]),
                ],
            ],
            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Our Story',
                        'description' => 'Azure International School was founded in 2015 by a group of educators who wanted to create something different — a school that was both academically excellent and genuinely warm.',
                        'image_url' => self::photo('mediterranean-story'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Mission & Values',
                        'description' => 'We are guided by three values: kindness, curiosity and courage.',
                        'mission' => 'To educate the whole child — academically, socially and emotionally.',
                        'vision' => 'To be the most joyful and effective international school in the region.',
                        'image_url' => self::photo('mediterranean-values'),
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'A Personal Welcome',
                        'principal_name' => 'Elena Papadopoulos',
                        'principal_title' => 'Principal',
                        'description' => 'I chose to lead Azure because I believe in its vision: that school should be a place where children feel safe enough to take risks, and challenged enough to grow.',
                        'image_url' => self::photo('mediterranean-welcome'),
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Come Meet Us',
                        'description' => 'We would love to welcome you to campus.',
                        'cta_text' => 'Book a Visit',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'admissions' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Your Azure Journey',
                        'description' => 'Joining Azure is simple. We welcome applications from all backgrounds and are here to guide you.',
                        'cta_text' => 'Apply Now',
                        'cta_url' => '/apply',
                        'image_url' => self::photo('mediterranean-admissions'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Application Steps',
                        'features' => [
                            ['title' => 'Enquire', 'desc' => 'Fill in the enquiry form or visit us.', 'image' => self::photo('mediterranean-s1')],
                            ['title' => 'Assess', 'desc' => 'A friendly assessment day for your child.', 'image' => self::photo('mediterranean-s2')],
                            ['title' => 'Offer', 'desc' => 'Receive your offer and complete enrolment.', 'image' => self::photo('mediterranean-s3')],
                        ],
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'Frequently Asked Questions',
                        'faqs' => [
                            ['q' => 'What curriculum do you follow?', 'a' => 'Cambridge International with a strong bilingual programme.'],
                            ['q' => 'Do you offer transport?', 'a' => 'Yes, we have bus routes covering all major suburbs.'],
                            ['q' => 'What sports do you offer?', 'a' => 'Swimming, tennis, football, basketball, athletics and more.'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Have Questions?',
                        'description' => 'Our admissions team is happy to help with entry requirements, fees and visits.',
                        'cta_text' => 'Contact Admissions',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Azure News',
                        'description' => 'The latest stories and events from our bright community.',
                        'image_url' => self::photo('mediterranean-news'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest News',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'Events Calendar',
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Stay Connected',
                        'description' => 'Follow our stories and reach out — we would love to hear from you.',
                        'cta_text' => 'Contact Us',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Say Hello',
                        'description' => 'We would love to hear from you. Reach out anytime.',
                        'image_url' => self::photo('mediterranean-contact'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Us',
                        'address' => '12 Coast Road, Harare',
                        'phone' => '+263 77 111 2233',
                        'email' => 'hello@azureinternational.co.zw',
                    ]),
                ],
            ],
            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Life at Azure',
                        'description' => 'Bright, busy and full of energy — student life at Azure is an adventure.',
                        'image_url' => self::photo('mediterranean-life'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('gallery', [
                        'title' => 'Azure Moments',
                        'columns' => 3,
                        'images' => [
                            ['url' => self::photo('mediterranean-g1'), 'caption' => 'Annual arts showcase'],
                            ['url' => self::photo('mediterranean-g2'), 'caption' => 'Sports day'],
                            ['url' => self::photo('mediterranean-g3'), 'caption' => 'Science exhibition'],
                            ['url' => self::photo('mediterranean-g4'), 'caption' => 'Cultural festival'],
                            ['url' => self::photo('mediterranean-g5'), 'caption' => 'Graduation day'],
                            ['url' => self::photo('mediterranean-g6'), 'caption' => 'Community picnic'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Experience Azure',
                        'description' => 'Book a campus visit and see our classrooms, grounds and community for yourself.',
                        'cta_text' => 'Book a Visit',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
        ];
    }

    /** URBAN CREATIVE — modern arts: charcoal, coral, mustard, asymmetric. */
    protected static function urban(): array
    {
        return [
            'home' => [
                'title' => 'Home',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Think Different. Create Boldly.',
                        'description' => 'Metropolis Academy is where creative minds come to thrive. We combine academic excellence with a passion for design, technology and the arts.',
                        'cta_text' => 'Explore Metropolis',
                        'cta_url' => '/about',
                        'secondary_cta_text' => 'Apply Now',
                        'secondary_cta_url' => '/admissions',
                        'image_url' => self::photo('urban-campus'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'The Metropolis Approach',
                        'description' => 'We believe the best learning happens when students are trusted to create, experiment and lead. Our studios, labs and stages are always open.',
                        'mission' => 'To develop innovative thinkers and confident creators who shape the world around them.',
                        'vision' => 'A school where every student finds their creative voice.',
                        'image_url' => self::photo('urban-approach'),
                    ]),
                    self::blk('statistics', [
                        'variant' => 'minimal-editorial',
                        'title' => 'Metropolis in Focus',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Where Creativity Meets Rigour',
                        'features' => [
                            ['title' => 'Design Studio', 'desc' => 'Graphic design, architecture and product design with industry mentors.', 'image' => self::photo('urban-design')],
                            ['title' => 'Tech Lab', 'desc' => 'Coding, AI and digital fabrication in our innovation hub.', 'image' => self::photo('urban-tech')],
                            ['title' => 'Performance Wing', 'desc' => 'A professional-grade theatre, recording studio and dance hall.', 'image' => self::photo('urban-performance')],
                            ['title' => 'Entrepreneurship', 'desc' => 'Students launch real businesses through our venture programme.', 'image' => self::photo('urban-entrepreneur')],
                        ],
                    ]),
                    self::blk('testimonials', [
                        'title' => 'Student & Parent Voices',
                        'items' => [
                            ['quote' => 'Metropolis gave me the confidence to start my own design studio at 16. The teachers believed in my vision before I did.', 'name' => 'Zara Ndlovu', 'role' => 'Alumna, Class of 2024'],
                            ['quote' => 'This school sees the whole child — not just grades. My son has found his passion here.', 'name' => 'Michael Chen', 'role' => 'Parent'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Ready to Create?',
                        'description' => 'Join a community that celebrates bold ideas.',
                        'cta_text' => 'Apply Now',
                        'cta_url' => '/admissions',
                    ]),
                ],
            ],
            'about' => [
                'title' => 'About Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Built for Bold Thinkers',
                        'description' => 'Metropolis Academy was founded in 2018 by a collective of designers, educators and entrepreneurs who wanted a school that matched their ambition.',
                        'image_url' => self::photo('urban-history'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('about_section', [
                        'title' => 'Our Vision',
                        'description' => 'We are building a school that looks and feels like the future — flexible spaces, project-based learning and genuine industry connections.',
                        'mission' => 'To empower the next generation of African creatives and innovators.',
                        'vision' => 'To be the most creative school on the continent.',
                        'image_url' => self::photo('urban-vision'),
                    ]),
                    self::blk('principal_welcome', [
                        'title' => 'From the Founder',
                        'principal_name' => 'Kofi Mensah',
                        'principal_title' => 'Founder & Head of School',
                        'description' => 'I started Metropolis because I wished a school like this existed when I was young. Every student who walks through our doors deserves to be told: your ideas matter.',
                        'image_url' => self::photo('urban-founder'),
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Come See Our Space',
                        'description' => 'Our campus is unlike any other school.',
                        'cta_text' => 'Book a Tour',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'admissions' => [
                'title' => 'Admissions',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Your Creative Journey Starts Here',
                        'description' => 'We look for curious, driven students who are excited to create. Here is how to join Metropolis.',
                        'cta_text' => 'Start Application',
                        'cta_url' => '/apply',
                        'image_url' => self::photo('urban-admissions'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('features_grid', [
                        'variant' => 'cards',
                        'title' => 'Application Process',
                        'features' => [
                            ['title' => '1. Portfolio', 'desc' => 'Share your creative work (any medium).', 'image' => self::photo('urban-s1')],
                            ['title' => '2. Workshop Day', 'desc' => 'Join a hands-on workshop with our teachers.', 'image' => self::photo('urban-s2')],
                            ['title' => '3. Interview', 'desc' => 'A conversation about your goals and interests.', 'image' => self::photo('urban-s3')],
                        ],
                    ]),
                    self::blk('faq_accordion', [
                        'title' => 'FAQ',
                        'faqs' => [
                            ['q' => 'Do I need a portfolio to apply?', 'a' => 'Yes, but it can be anything — drawings, code, music, writing, photographs. We want to see how you think.'],
                            ['q' => 'What ages do you accept?', 'a' => 'Grades 7-12 (ages 12-18).'],
                            ['q' => 'Do you offer financial aid?', 'a' => 'Yes, we have a generous bursary programme.'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Ready to Apply?',
                        'description' => 'Applications are open. Our admissions team will guide you through every step.',
                        'cta_text' => 'Contact Admissions',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'news-events' => [
                'title' => 'News & Events',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'The Metropolis Edit',
                        'description' => 'Stories, showcases and events from the most creative school in the city.',
                        'image_url' => self::photo('urban-news'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('dynamic_news', [
                        'title' => 'Latest Stories',
                    ]),
                    self::blk('events_calendar', [
                        'title' => 'What\'s On',
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'Follow the Action',
                        'description' => 'See what our students are building, performing and competing in this term.',
                        'cta_text' => 'Get in Touch',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Let\'s Create Together',
                        'description' => 'Have a question? Want to visit? We are here.',
                        'image_url' => self::photo('urban-contact'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('contact_map', [
                        'title' => 'Find Us',
                        'address' => '30 Innovation Drive, Harare',
                        'phone' => '+263 77 555 6677',
                        'email' => 'hello@metropolisacademy.co.zw',
                    ]),
                ],
            ],
            'student-life' => [
                'title' => 'Student Life',
                'blocks' => [
                    self::blk('hero', [
                        'variant' => 'hero-editorial',
                        'title' => 'Always Something Happening',
                        'description' => 'Exhibitions, hackathons, performances, pop-ups — life at Metropolis never stands still.',
                        'image_url' => self::photo('urban-life'),
                        'layout' => 'image-right',
                    ]),
                    self::blk('gallery', [
                        'title' => 'The Metropolis Gallery',
                        'columns' => 3,
                        'images' => [
                            ['url' => self::photo('urban-g1'), 'caption' => 'Annual design exhibition'],
                            ['url' => self::photo('urban-g2'), 'caption' => 'Hackathon weekend'],
                            ['url' => self::photo('urban-g3'), 'caption' => 'Fashion show'],
                            ['url' => self::photo('urban-g4'), 'caption' => 'Film festival'],
                            ['url' => self::photo('urban-g5'), 'caption' => 'Startup pitch day'],
                            ['url' => self::photo('urban-g6'), 'caption' => 'Graduation showcase'],
                        ],
                    ]),
                    self::blk('cta_banner', [
                        'title' => 'See It for Yourself',
                        'description' => 'The best way to understand Metropolis is to walk the halls. Book a visit today.',
                        'cta_text' => 'Book a Visit',
                        'cta_url' => '/contact',
                    ]),
                ],
            ],
        ];
    }
}
