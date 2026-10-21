<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('cover_image')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('seo_description', 300)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        $posts = [
            [
                'title' => 'Why schools are moving to digital management',
                'slug' => 'why-schools-are-moving-to-digital-management',
                'excerpt' => 'Paper registers, spreadsheets and WhatsApp groups were never designed to run a school. Here is what changes when your institution moves onto a single, secure platform.',
                'content' => <<<'HTML'
<p>For decades, school administration has meant cabinets of paper registers, fee books reconciled by hand, and report cards typed late into the night. It works — until it does not. A single misplaced register, a duplicated admission, or a fee receipt that never reached the ledger can take weeks to untangle.</p>
<h2>One record, one truth</h2>
<p>When admissions, attendance, fees and assessments share one database, every department sees the same student record. The admissions office enrols a learner once; finance invoices them automatically; the class teacher marks attendance against the same ID. There is no re-keying and no guessing which spreadsheet is current.</p>
<p>That is exactly what the <a href="/features">Kairo CORE feature set</a> is built around — a single connected workflow from application to graduation.</p>
<h2>What actually gets faster</h2>
<ul>
<li><strong>Admissions season</strong> — families apply online, upload documents once, and track their application status without visiting the school office.</li>
<li><strong>Fee collection</strong> — invoices, receipts and balances update the moment a payment is recorded, so nobody chases money that has already been paid.</li>
<li><strong>Report cards</strong> — marks entered once flow straight into printed, verifiable report cards aligned to ZIMSEC and Cambridge criteria.</li>
<li><strong>Parent communication</strong> — announcements reach the right class or the whole school without side chats that leave people out.</li>
</ul>
<h2>Security is part of the package</h2>
<p>Student data deserves more protection than a shared spreadsheet password. Role-based permissions, audit logs and encrypted connections mean staff only see what their role requires. Read more in our <a href="/security">security and data protection overview</a>.</p>
<p>Ready to see it in your own school? <a href="/register">Register your school</a> today, or explore the <a href="/features">full feature list</a> first.</p>
HTML,
                'cover_image' => '/images/placeholders/news-cover.svg',
                'published_at' => '2026-10-05 08:00:00',
                'seo_description' => 'Why schools across Zimbabwe and Southern Africa are replacing paper registers and spreadsheets with a single digital school management platform.',
            ],
            [
                'title' => 'School fees made simple: invoicing, Paynow and payment tracking',
                'slug' => 'school-fees-made-simple',
                'excerpt' => 'Chasing fees is stressful for bursars and families alike. Modern invoicing with digital payment options changes the conversation completely.',
                'content' => <<<'HTML'
<p>Fee collection is the heartbeat of any school — and the most common source of friction. Bursars juggle cash, bank deposits and mobile payments; parents queue to confirm what they owe; term-end reconciliation eats entire days.</p>
<h2>Automatic, accurate invoicing</h2>
<p>With Kairo CORE, every admitted student generates the right invoice for the right term automatically, using the fee structure you configured once. Balances carry forward correctly, discounts and sibling rates apply consistently, and receipts are issued the moment money is recorded.</p>
<p>See the <a href="/school-fees">fees and finance module</a> for the full breakdown of invoicing, expenses and financial reporting.</p>
<h2>Give families options</h2>
<p>Parents expect to pay digitally. Supporting Paynow, bank transfers and card payments alongside cash means fewer queues at the bursar's office and fewer "I already paid" disputes — every payment is matched to a student and a receipt number automatically.</p>
<h2>Know your position at any moment</h2>
<ul>
<li>Outstanding balances by class, grade or family</li>
<li>Daily, weekly and term collection summaries</li>
<li>Arrears lists you can act on before they grow</li>
<li>Export-ready reports for auditors and boards</li>
</ul>
<p>Fee stress drops sharply when everyone can see the same numbers. Explore <a href="/features">all features</a> or <a href="/register">register your school</a> to get started.</p>
HTML,
                'cover_image' => '/images/placeholders/campus-exterior.svg',
                'published_at' => '2026-10-08 08:00:00',
                'seo_description' => 'How digital invoicing, Paynow integration and automatic payment tracking simplify school fee collection for bursars and parents.',
            ],
            [
                'title' => 'From admission to report card: a term with Kairo CORE',
                'slug' => 'from-admission-to-report-card',
                'excerpt' => 'Follow one school term end to end — online admissions, daily attendance, assessments and report cards — and see where the time goes back to teachers.',
                'content' => <<<'HTML'
<p>A school term is a chain of workflows that must connect cleanly: admissions feed class lists, class lists feed attendance, attendance feeds reports, and assessments feed report cards. When any link is handled on paper, the work multiplies downstream.</p>
<h2>Week 1 — Admissions close</h2>
<p>Applications arrive through the school's own <a href="/admissions">online admissions portal</a> with documents attached. Staff review, accept or query each one in a single queue, and admission creates the student record — no re-typing forms into a register.</p>
<h2>Weeks 2–10 — The daily rhythm</h2>
<p>Teachers take attendance in seconds from their classroom device. Announcements reach exactly the right group. Assessments are captured against rubrics as they happen, not reconstructed at end of term. Each of these steps quietly assembles the data report cards will need.</p>
<h2>Week 11 — Report cards without the panic</h2>
<p>Because marks and attendance were captured all term, report cards are a review-and-print exercise rather than a data-entry marathon. Results follow <a href="/academics">ZIMSEC and Cambridge aligned criteria</a>, and each report card can be verified online by anyone holding its code.</p>
<h2>The real win</h2>
<ul>
<li>Teachers reclaim hours previously lost to duplicated paperwork</li>
<li>Parents get timely, consistent information</li>
<li>Leadership sees live dashboards instead of month-old summaries</li>
</ul>
<p>Walk through the <a href="/features">complete feature list</a>, or <a href="/register">register your school</a> and run your next term on Kairo CORE.</p>
HTML,
                'cover_image' => '/images/placeholders/classroom.svg',
                'published_at' => '2026-10-12 08:00:00',
                'seo_description' => 'A week-by-week look at how online admissions, attendance, assessments and reporting connect into one smooth school term.',
            ],
            [
                'title' => 'Five security habits every school should adopt',
                'slug' => 'five-security-habits-every-school-should-adopt',
                'excerpt' => 'From shared logins to lost paper records, student data leaks quietly. Five practical habits that protect learners, staff and families.',
                'content' => <<<'HTML'
<p>Schools hold some of the most sensitive records a person will ever have: identity documents, medical notes, grades and family contact details. Protecting them is not only an IT concern — it is a duty of care.</p>
<h2>1. One account per person</h2>
<p>Shared loggers make accountability impossible. Every staff member should have their own login with permissions scoped to their role — a class teacher does not need the same access as a bursar.</p>
<h2>2. Least privilege, reviewed often</h2>
<p>Permissions should grow and shrink with real responsibilities. Termly reviews catch accounts that still hold access from a role someone left years ago.</p>
<h2>3. Prefer systems that log</h2>
<p>A spreadsheet cannot tell you who changed a mark or deleted a receipt. Any platform handling student records should keep an audit trail of who did what and when.</p>
<h2>4. Verify documents instead of trusting them</h2>
<p>Report cards and certificates that carry a public verification code remove the incentive to forge them — and the awkwardness of manual verification. See <a href="/security">our security overview</a> for how verification works.</p>
<h2>5. Backups you have actually tested</h2>
<p>A backup you have never restored is a hope, not a plan. Test recovery regularly, and prefer providers who encrypt data in transit and at rest.</p>
<p>Kairo CORE ships with role-based permissions, audit logging and encrypted connections as standard. Read the <a href="/security">full security practices</a>, or <a href="/register">register your school</a> to get started securely.</p>
HTML,
                'cover_image' => '/images/placeholders/library.svg',
                'published_at' => '2026-10-16 08:00:00',
                'seo_description' => 'Five practical school data security habits: individual accounts, least privilege, audit logs, document verification and tested backups.',
            ],
        ];

        foreach ($posts as $post) {
            DB::table('platform_blog_posts')->insertOrIgnore(array_merge($post, [
                'status' => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_blog_posts');
    }
};
