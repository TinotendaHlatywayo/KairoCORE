<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('Kairo CORE Platform Terms of Service and Terms of Use') }}">
    <title>{{ __('Terms of Service & Terms of Use') }} - Kairo CORE</title>
    <style>
        :root{--primary:#f26b3a;--primary-dark:#d95427;--primary-soft:#fff1eb;--navy:#172b4d;--navy-dark:#0e1d35;--blue:#2b456c;--ink:#0f172a;--text:#475569;--muted:#64748b;--line:#dfe5ec;--surface:#fff;--alt:#f6f8fb;--danger:#991b1b;--danger-bg:#fef2f2;--radius:20px}
        *{box-sizing:border-box}html{scroll-behavior:smooth}
        body{margin:0;padding:32px 16px 64px;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--text);background:radial-gradient(circle at top left,rgba(242,107,58,.09),transparent 34rem),var(--alt);line-height:1.75}
        .page{width:min(1120px,100%);margin:0 auto}
        .topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px}.back-link{color:var(--primary);text-decoration:none;font-weight:700}.back-link:hover{text-decoration:underline}.actions{display:flex;gap:10px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:44px;padding:10px 16px;border-radius:12px;text-decoration:none;font-weight:750;font-size:.9rem;border:1px solid var(--line);background:var(--surface);color:var(--ink);transition:.2s ease}.btn:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(15,23,42,.08)}.btn-primary{color:#fff;background:var(--primary);border-color:var(--primary)}.btn-primary:hover{background:var(--primary-dark)}
        
        /* Updated Hero Styles */
        .hero {
            position: relative;
            overflow: hidden;
            background-color: #172B4D;
            color: #fff;
            border-radius: 28px;
            padding: 42px 46px;
            box-shadow: 0 25px 60px rgba(23, 43, 77, .22);
            margin-bottom: 22px;
            border-top: 5px solid #F26B3A;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            right: -120px;
            top: -160px;
            background: rgba(242, 107, 58, .12);
        }

        .brand-lockup {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 20px;
            border-radius: 18px;
            background: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, .85);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .18);
            margin-bottom: 24px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .brand-logo {
            display: block;
            width: min(320px, 65vw);
            max-width: 320px;
            height: auto;
        }

        .eyebrow {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            background: #F26B3A;
            border: 1px solid #F26B3A;
            color: #FFFFFF;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .hero-accent {
            position: relative;
            z-index: 1;
            width: 72px;
            height: 4px;
            border-radius: 999px;
            background: #F26B3A;
            margin: 18px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .hero h1 {
            position: relative;
            z-index: 1;
            margin: 16px 0 10px;
            color: #FFFFFF;
            font-size: clamp(2rem, 5vw, 3.25rem);
            line-height: 1.08;
            letter-spacing: -.04em;
        }

        .hero p {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0;
            color: rgba(255, 255, 255, .88);
            font-size: 1.05rem;
            line-height: 1.75;
        }

        .hero-meta {
            position: relative;
            z-index: 1;
            margin-top: 25px;
        }

        .meta-pill {
            display: inline-block;
            padding: 7px 12px;
            margin: 0 7px 7px 0;
            border-radius: 999px;
            background: rgba(255, 255, 255, .10);
            border: 1px solid rgba(255, 255, 255, .20);
            color: rgba(255, 255, 255, .94);
            font-size: .82rem;
            white-space: nowrap;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .layout{display:grid;grid-template-columns:250px minmax(0,1fr);gap:22px;align-items:start}.toc{position:sticky;top:20px;background:rgba(255,255,255,.9);border:1px solid var(--line);border-radius:var(--radius);padding:18px;box-shadow:0 12px 35px rgba(15,23,42,.06)}.toc-title{color:var(--ink);font-size:.78rem;font-weight:850;text-transform:uppercase;letter-spacing:.08em;margin:0 0 10px}.toc a{display:block;color:var(--muted);text-decoration:none;font-size:.86rem;line-height:1.35;padding:7px 8px;border-radius:8px}.toc a:hover{color:var(--primary);background:var(--primary-soft)}
        .document{min-width:0;background:var(--surface);border:1px solid var(--line);border-radius:28px;box-shadow:0 18px 55px rgba(15,23,42,.07);overflow:hidden}.document-inner{padding:clamp(24px,5vw,54px)}
        .notice{padding:18px 20px;border-radius:15px;margin:0 0 30px;border:1px solid #fed7c7;background:var(--primary-soft);color:#7c2d12}.notice strong{color:var(--navy)}.danger{padding:18px 20px;border-radius:15px;margin:22px 0;border:1px solid #fecaca;background:var(--danger-bg);color:var(--danger)}
        h2{scroll-margin-top:24px;color:var(--ink);font-size:clamp(1.25rem,2vw,1.55rem);line-height:1.25;letter-spacing:-.02em;margin:48px 0 14px;padding-top:4px}h2:first-of-type{margin-top:34px}p,ul,ol{margin:0 0 16px}ul,ol{padding-left:1.35rem}li{margin:7px 0}strong{color:var(--ink)}
        .section-number{display:inline-flex;align-items:center;justify-content:center;width:31px;height:31px;margin-right:8px;border-radius:9px;background:var(--primary-soft);color:var(--primary);font-size:.8rem;vertical-align:2px}.divider{height:1px;background:var(--line);margin:38px 0}.signature{margin-top:38px;padding:22px;border:1px solid var(--line);border-radius:16px;background:var(--alt)}.footer{padding:24px 0 0;text-align:center;color:var(--muted);font-size:.82rem}
        .pdf-mode{width:100%}.pdf-mode .document{border:0;box-shadow:none;border-radius:0}.pdf-mode .document-inner{padding:0}
        @media(max-width:900px){.layout{grid-template-columns:1fr}.toc{display:none}.hero{padding:32px 24px}}
        
        /* Updated Print Styles */
        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .hero {
                background-color: #172B4D !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .brand-lockup {
                background: #fff !important;
                box-shadow: none !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .eyebrow,
            .hero-accent {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .meta-pill {
                background: rgba(255, 255, 255, .09) !important;
                border-color: rgba(255, 255, 255, .16) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .topbar,
            .toc,
            .actions,
            .footer {
                display: none !important;
            }

            .hero {
                box-shadow: none;
                border-radius: 0;
            }

            .document {
                border: 0;
                box-shadow: none;
            }

            .document-inner {
                padding: 24px;
            }

            h2 {
                break-after: avoid;
            }
        }
    </style>
</head>
<body>
@php
    $isPdf = request()->routeIs('platform.terms.pdf');
    $logoPath = public_path('images/kairo-core-logo.png');
    $logoData = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : asset('images/kairo-core-logo.png');
@endphp
<div class="page {{ $isPdf ? 'pdf-mode' : '' }}">

    @if(!$isPdf)
    <div class="topbar">
        <a href="javascript:history.back()" class="back-link">&larr; {{ __('Back to Registration') }}</a>
        <div class="actions">
            <a href="{{ route('platform.terms.pdf') }}" class="btn btn-primary">
                <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                {{ __('Download PDF') }}
            </a>
        </div>
    </div>
    @endif

    <header class="hero">

        <div class="brand-lockup">
            <img
                class="brand-logo"
                src="{{ $logoData }}"
                alt="Kairo CORE — Central Operations and Resources Environment"
            >
        </div>

        <span class="eyebrow">
            {{ __('Kairo CORE Legal') }}
        </span>

        <div class="hero-accent" aria-hidden="true"></div>

        <h1>
            {{ __('Terms of Service & Terms of Use') }}
        </h1>

        <p>
            {{ __('Kairo CORE (Central Operations and Resources Environment) is a comprehensive software platform designed to support schools and other authorised organisations in managing operational, academic, administrative and related activities. These Terms govern access to and use of the Kairo CORE software platform, tenant environments, websites, APIs, integrations and related services.') }}
        </p>

        <div class="hero-meta">
            <span class="meta-pill">
                {{ __('Last Updated: 7 September 2026') }}
            </span>

            <span class="meta-pill">
                {{ __('Effective upon acceptance') }}
            </span>

            <span class="meta-pill">
                {{ __('Version 2.0') }}
            </span>
        </div>

    </header>

    <div class="layout">
        @if(!$isPdf)
        <aside class="toc" aria-label="{{ __('Table of contents') }}">
            <p class="toc-title">{{ __('On this page') }}</p>
            <a href="#acceptance">1. {{ __('Acceptance') }}</a><a href="#definitions">2. {{ __('Definitions') }}</a><a href="#accounts">3. {{ __('Accounts & Tenant Security') }}</a><a href="#subscription">4. {{ __('Subscription & Fees') }}</a><a href="#acceptable-use">5. {{ __('Acceptable Use') }}</a><a href="#customer-content">6. {{ __('Customer Data & Content') }}</a><a href="#privacy">7. {{ __('Privacy & Data Protection') }}</a><a href="#security">8. {{ __('Security') }}</a><a href="#third-party">9. {{ __('Third-Party Services') }}</a><a href="#availability">10. {{ __('Availability & Backups') }}</a><a href="#ip">11. {{ __('Intellectual Property') }}</a><a href="#education">12. {{ __('Educational Disclaimer') }}</a><a href="#warranties">13. {{ __('Warranties & Disclaimers') }}</a><a href="#liability">14. {{ __('Limitation of Liability') }}</a><a href="#indemnity">15. {{ __('Indemnification') }}</a><a href="#suspension">16. {{ __('Suspension & Termination') }}</a><a href="#data-after">17. {{ __('Data After Termination') }}</a><a href="#confidentiality">18. {{ __('Confidentiality') }}</a><a href="#changes">19. {{ __('Changes') }}</a><a href="#law">20. {{ __('Governing Law & Disputes') }}</a><a href="#general">21. {{ __('General Provisions') }}</a><a href="#contact">22. {{ __('Contact') }}</a>
        </aside>
        @endif

        <main class="document">
            <div class="document-inner">
                <div class="notice"><strong>{{ __('Important:') }}</strong> {{ __('By registering for, accessing, or using Kairo CORE, you confirm that you have read, understood and agreed to these Terms. If you are accepting these Terms on behalf of a school, company, institution or other legal entity, you represent that you have authority to bind that entity.') }}</div>
                <p>{{ __('Please read these Terms of Service and Terms of Use ("Terms", "Agreement") carefully before accessing or using Kairo CORE ("Kairo CORE", "Platform", "Service", "we", "us", or "our"). The Service may include software, multi-tenant infrastructure, school management functionality, websites, APIs, mobile or web interfaces, communications features, storage, reporting, integrations and other related services.') }}</p>
                <p>{{ __('If you do not agree to these Terms, you must not register for, access, or use the Service.') }}</p>

                <section id="acceptance"><h2><span class="section-number">1</span>{{ __('Acceptance and Contract Formation') }}</h2><p>{{ __('These Terms form a legally binding agreement between you and the legal entity that owns and operates Kairo CORE ("Company"). Your acceptance occurs when you create an account, click an acceptance checkbox, execute an order or subscription, access the Platform, or otherwise use the Service.') }}</p><p>{{ __('If you are using Kairo CORE on behalf of an organisation, the organisation is the customer ("Tenant" or "Customer") and you confirm that you are authorised to accept these Terms on its behalf. You are responsible for ensuring that all authorised users within your organisation comply with this Agreement.') }}</p></section>

                <section id="definitions"><h2><span class="section-number">2</span>{{ __('Definitions') }}</h2><ul><li><strong>{{ __('Customer/Tenant:') }}</strong> {{ __('the school, institution, business or other entity subscribing to or using Kairo CORE.') }}</li><li><strong>{{ __('Authorised User:') }}</strong> {{ __('an individual permitted by the Customer to access the Customer’s tenant environment.') }}</li><li><strong>{{ __('Customer Data:') }}</strong> {{ __('data, records, files, documents, images, student information, employee information and other content submitted to the Platform by or on behalf of the Customer.') }}</li><li><strong>{{ __('Platform Data:') }}</strong> {{ __('technical, operational, security, diagnostic and usage information generated through operation of the Service, subject to applicable law and the Privacy Policy.') }}</li><li><strong>{{ __('Subscription:') }}</strong> {{ __('the paid or trial plan under which the Customer receives access to the Service.') }}</li></ul></section>

                <section id="accounts"><h2><span class="section-number">3</span>{{ __('Accounts, Authorised Users and Tenant Security') }}</h2><p>{{ __('Each Customer is responsible for the accuracy of registration information, maintaining secure credentials, controlling user permissions and ensuring that accounts are used only by authorised persons.') }}</p><ul><li>{{ __('Do not share administrator credentials or permit unauthorised persons to access a tenant environment.') }}</li><li>{{ __('Immediately notify us of suspected credential compromise, unauthorised access or material security incidents affecting the Platform.') }}</li><li>{{ __('You are responsible for actions taken through your accounts, including actions performed by employees, contractors, administrators and other Authorised Users.') }}</li><li>{{ __('Configure roles and permissions appropriately and do not intentionally circumvent tenant isolation or access controls.') }}</li><li>{{ __('The Company may require reasonable verification before changing an organisation’s administrator, domain, billing details or security settings.') }}</li></ul></section>

                <section id="subscription"><h2><span class="section-number">4</span>{{ __('Subscriptions, Trials, Fees and Payment') }}</h2><p>{{ __('Access to paid features is subject to the applicable subscription, pricing, order form or plan displayed at the time of purchase. Prices, features, limits and billing intervals may vary by plan and may be changed prospectively by the Company.') }}</p><ul><li>{{ __('Trial access, promotional periods and free plans may be subject to eligibility, feature, usage and duration limits.') }}</li><li>{{ __('Unless otherwise stated in writing, subscriptions renew according to the selected billing interval until cancelled or terminated.') }}</li><li>{{ __('You authorise the applicable payment provider to process legitimate charges associated with your subscription.') }}</li><li>{{ __('Failed, reversed, disputed or overdue payments may result in restricted features, suspension or termination after reasonable notice where required by law.') }}</li><li>{{ __('Taxes, duties, bank charges, payment-provider charges or other transaction costs may be payable by the Customer where applicable.') }}</li><li>{{ __('Refunds are subject to the applicable plan, order terms and mandatory legal rights. Nothing in these Terms excludes rights that cannot lawfully be excluded.') }}</li></ul></section>

                <section id="acceptable-use"><h2><span class="section-number">5</span>{{ __('Acceptable Use and Prohibited Activities') }}</h2><p>{{ __('You must use Kairo CORE lawfully, responsibly and only for legitimate educational, administrative, organisational or business purposes. You must not:') }}</p><ul><li>{{ __('attempt to breach, disable, bypass or defeat authentication, authorisation, rate limits, tenant isolation or other security controls;') }}</li><li>{{ __('probe, scan, penetration-test or conduct vulnerability assessments against the Platform without prior written authorisation;') }}</li><li>{{ __('perform denial-of-service attacks, excessive automated requests, scraping, credential stuffing or other activity that could impair the Platform;') }}</li><li>{{ __('reverse engineer, decompile, disassemble or attempt to obtain source code except to the limited extent expressly permitted by applicable law;') }}</li><li>{{ __('upload malicious code, ransomware, spyware, malware or content intended to compromise systems;') }}</li><li>{{ __('use the Service to infringe intellectual-property, privacy, confidentiality or other rights of third parties;') }}</li><li>{{ __('use the Platform for unlawful surveillance, harassment, fraud, impersonation or other unlawful conduct;') }}</li><li>{{ __('attempt unauthorised access to another Customer’s environment or data;') }}</li></ul><div class="danger"><strong>{{ __('Security violations:') }}</strong> {{ __('Unauthorised attempts to access another tenant, bypass platform controls or compromise the security of Kairo CORE may result in immediate suspension or termination and may be reported where required or permitted by law.') }}</div></section>

                <section id="customer-content"><h2><span class="section-number">6</span>{{ __('Customer Data, Content and Responsibility') }}</h2><p>{{ __('The Customer retains ownership of Customer Data, subject to the rights necessary for the Company to operate the Service. The Customer grants the Company a limited, non-exclusive, worldwide, royalty-free licence to host, store, transmit, reproduce, process and otherwise use Customer Data solely as reasonably necessary to provide, secure, maintain, improve and support the Service, comply with law, prevent abuse, and perform contractual obligations.') }}</p><p>{{ __('The Customer is responsible for the legality, accuracy, quality and integrity of Customer Data; having all necessary rights, permissions, notices and lawful bases to collect and process data submitted to Kairo CORE; ensuring content does not unlawfully infringe third-party rights; configuring retention, access, roles and permissions appropriately; and responding to data-subject requests where the Customer is the relevant controller.') }}</p></section>

                <section id="privacy"><h2><span class="section-number">7</span>{{ __('Privacy, Data Protection and Regulatory Compliance') }}</h2><p>{{ __('Use of personal information is also governed by the Kairo CORE Privacy Policy and applicable data-protection law. Where the Customer determines the purposes and means of processing personal information and Kairo CORE processes information on the Customer’s behalf, the parties will operate on the basis of the applicable controller/processor relationship to the extent recognised by law.') }}</p><p>{{ __('Customers remain responsible for determining lawful bases for processing, providing required privacy notices, obtaining required consents, handling data-subject requests and complying with applicable retention, security and transfer requirements.') }}</p><p>{{ __('Where applicable, the parties may enter into additional data-processing terms, data-processing agreements or other contractual safeguards required by law.') }}</p></section>

                <section id="security"><h2><span class="section-number">8</span>{{ __('Security and Security Incidents') }}</h2><p>{{ __('The Company implements reasonable technical and organisational measures designed to protect the Service. However, no internet-connected system, software, hosting environment or transmission method can be guaranteed to be completely secure.') }}</p><p>{{ __('Security also depends on Customer-side controls, including strong passwords, multi-factor authentication where available, least-privilege permissions, secure devices, secure networks and appropriate staff practices.') }}</p><p>{{ __('Where required by applicable law, the Company will provide notices and cooperate with competent authorities and Customers regarding qualifying personal-data or security incidents.') }}</p></section>

                <section id="third-party"><h2><span class="section-number">9</span>{{ __('Third-Party Services and Integrations') }}</h2><p>{{ __('The Service may integrate with or depend on third-party services including payment processors, email providers, hosting providers, domain services, communications platforms, analytics providers and other technology providers.') }}</p><p>{{ __('Third-party services are controlled by their respective providers. The Company does not guarantee their availability, security, functionality, accuracy or continued compatibility and is not responsible for failures caused solely by third-party providers, except to the extent liability cannot lawfully be excluded.') }}</p></section>

                <section id="availability"><h2><span class="section-number">10</span>{{ __('Availability, Maintenance, Backups and Service Changes') }}</h2><p>{{ __('The Company aims to provide a reliable service but does not promise uninterrupted or error-free availability unless a separate written service-level agreement expressly provides otherwise.') }}</p><ul><li>{{ __('The Platform may be unavailable because of maintenance, upgrades, security events, infrastructure failures, telecommunications failures or events beyond reasonable control.') }}</li><li>{{ __('Features may be added, modified, replaced or discontinued as the Platform evolves.') }}</li><li>{{ __('Unless a separate written agreement states otherwise, Customer-managed backups remain the Customer’s responsibility.') }}</li><li>{{ __('Where backup or recovery functionality is provided, it is a resilience feature and not a guarantee that every record can always be restored.') }}</li></ul></section>

                <section id="ip"><h2><span class="section-number">11</span>{{ __('Intellectual Property and Platform Ownership') }}</h2><p>{{ __('The Platform and associated intellectual property, including software, source code, object code, architecture, workflows, APIs, databases and schemas, interfaces, designs, documentation, trademarks, logos, branding, templates, reports, visual elements and proprietary know-how, are owned by or licensed to the Company and protected by applicable intellectual-property laws.') }}</p><p>{{ __('Except for the limited right to access and use the Service during an active subscription in accordance with these Terms, no ownership interest is transferred to you.') }}</p><p>{{ __('You may not copy, reproduce, resell, sublicense, distribute, modify, create derivative works from, commercially exploit or publicly represent the Platform as your own except where expressly authorised in writing or permitted by mandatory law.') }}</p></section>

                <section id="education"><h2><span class="section-number">12</span>{{ __('Educational and Administrative Disclaimer') }}</h2><p>{{ __('Kairo CORE is a software and administrative platform. It is not a substitute for professional judgment, legal obligations, safeguarding duties, academic policies, financial controls or regulatory responsibilities of a school, institution, administrator, teacher, accountant, healthcare professional or other authorised professional.') }}</p><p>{{ __('Reports, analytics, automated calculations, notifications, recommendations, grades, attendance records and other outputs must be reviewed by appropriately authorised personnel before being relied upon where accuracy or legal consequences matter.') }}</p></section>

                <section id="warranties"><h2><span class="section-number">13</span>{{ __('Warranties and Disclaimers') }}</h2><p><strong>{{ __('TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, THE SERVICE IS PROVIDED “AS IS” AND “AS AVAILABLE”.') }}</strong></p><p>{{ __('Except where expressly stated in a separate written agreement, the Company disclaims warranties not required by law, including implied warranties of merchantability, fitness for a particular purpose, title and non-infringement.') }}</p><p>{{ __('The Company does not warrant that the Service will always be uninterrupted, completely secure, error-free, compatible with every device or third-party system, or that every defect will be corrected immediately.') }}</p></section>

                <section id="liability"><h2><span class="section-number">14</span>{{ __('Limitation of Liability') }}</h2><p>{{ __('To the maximum extent permitted by applicable law, the Company, its affiliates, officers, directors, employees, contractors, agents, licensors and service providers will not be liable for indirect, incidental, special, exemplary, punitive or consequential losses, including loss of profits, revenue, business opportunity, goodwill, anticipated savings or use, arising from or relating to the Service or these Terms.') }}</p><p>{{ __('To the maximum extent permitted by applicable law, the Company’s aggregate liability for direct claims arising out of or relating to the Service or these Terms will not exceed the greater of (a) the fees actually paid by the Customer to the Company for the affected Service during the three (3) months immediately preceding the event giving rise to the claim, or (b) USD 100.') }}</p><p>{{ __('These limitations apply regardless of the legal theory on which a claim is based, including contract, delict/tort, negligence, strict liability or otherwise, subject to liabilities that cannot legally be limited or excluded.') }}</p></section>

                <section id="indemnity"><h2><span class="section-number">15</span>{{ __('Customer Indemnification') }}</h2><p>{{ __('To the maximum extent permitted by law, the Customer agrees to defend, indemnify and hold harmless the Company and its affiliates, officers, directors, employees, contractors and agents from third-party claims, damages, liabilities, costs and reasonable legal expenses arising from or relating to Customer Data or content submitted by or on behalf of the Customer; breach of these Terms; violation of applicable law or third-party rights; unauthorised or unlawful use through Customer-controlled accounts; or failure to obtain required permissions, notices or lawful authority to process personal information.') }}</p></section>

                <section id="suspension"><h2><span class="section-number">16</span>{{ __('Suspension and Termination') }}</h2><p>{{ __('The Company may suspend or restrict access where reasonably necessary to protect the Platform, other customers, data, infrastructure or the Company; to investigate suspected abuse, fraud or security incidents; for non-payment; or where continued access may violate law or these Terms.') }}</p><p>{{ __('The Company may terminate an account or subscription for material breach, unlawful use, persistent non-payment, security abuse, fraud, or other grounds permitted by the applicable agreement or law. Where appropriate and legally required, we will provide notice and a reasonable opportunity to cure the breach.') }}</p><p>{{ __('The Customer may terminate its subscription in accordance with the applicable cancellation procedure. Termination does not extinguish accrued payment obligations or provisions intended to survive termination.') }}</p></section>

                <section id="data-after"><h2><span class="section-number">17</span>{{ __('Data Export, Retention and Deletion After Termination') }}</h2><p>{{ __('Subject to the applicable subscription plan and law, the Customer may request an export of Customer Data during the applicable post-termination period. Export formats, availability, fees and deadlines may depend on the Service plan and technical feasibility.') }}</p><p>{{ __('After the applicable retention or export period, the Company may delete Customer Data from active systems, subject to legal retention obligations, security backups, disaster-recovery cycles and legitimate compliance requirements. Data in backups may persist until the normal backup-retention cycle expires.') }}</p><p>{{ __('The Customer remains responsible for maintaining independent copies required for legal, educational, accounting, archival or operational purposes.') }}</p></section>

                <section id="confidentiality"><h2><span class="section-number">18</span>{{ __('Confidentiality') }}</h2><p>{{ __('Each party may receive non-public information belonging to the other party and will use reasonable measures to protect such confidential information and use it only for purposes connected with the relationship between the parties.') }}</p><p>{{ __('Confidentiality obligations do not apply to information that is publicly available without breach, was lawfully known before disclosure, is independently developed, is lawfully received from a third party without confidentiality restrictions, or must be disclosed by law or lawful authority.') }}</p></section>

                <section id="changes"><h2><span class="section-number">19</span>{{ __('Changes to the Service and These Terms') }}</h2><p>{{ __('The Company may update the Platform, pricing, policies or these Terms from time to time. Material changes will be communicated through reasonable means where required. Revised Terms become effective on the stated effective date.') }}</p><p>{{ __('Continued use after the effective date constitutes acceptance to the extent permitted by law. If you do not agree to a material change, your remedy is to stop using the affected Service and, where applicable, cancel your subscription in accordance with the applicable cancellation terms.') }}</p></section>

                <section id="law"><h2><span class="section-number">20</span>{{ __('Governing Law and Dispute Resolution') }}</h2><p>{{ __('Unless a separate written agreement provides otherwise, these Terms are governed by the laws of Zimbabwe, without giving effect to conflict-of-law principles.') }}</p><p>{{ __('The parties will first attempt in good faith to resolve material disputes through written notice and direct discussion. Where appropriate, the parties may agree to mediation or arbitration under applicable Zimbabwean law. If a dispute cannot be resolved through the agreed alternative process, the competent courts of Zimbabwe will have jurisdiction, subject to mandatory rights that cannot lawfully be excluded.') }}</p><p>{{ __('Nothing in this section prevents either party from seeking urgent interim or protective relief from a competent court where reasonably necessary to protect confidential information, intellectual property, security, personal information or other legal rights.') }}</p></section>

                <section id="general"><h2><span class="section-number">21</span>{{ __('General Provisions') }}</h2><ul><li><strong>{{ __('Entire Agreement:') }}</strong> {{ __('These Terms, the Privacy Policy, applicable subscription/order terms and expressly incorporated policies form the agreement governing the Service.') }}</li><li><strong>{{ __('Severability:') }}</strong> {{ __('If any provision is unenforceable, it will be modified to the minimum extent necessary or severed, while the remaining provisions continue in effect.') }}</li><li><strong>{{ __('No Waiver:') }}</strong> {{ __('Failure to enforce a provision is not a waiver of the right to enforce it later.') }}</li><li><strong>{{ __('Assignment:') }}</strong> {{ __('You may not assign or transfer this Agreement without the Company’s prior written consent except where mandatory law permits otherwise. The Company may assign it in connection with a merger, acquisition, restructuring or transfer of substantially all relevant business assets.') }}</li><li><strong>{{ __('Independent Parties:') }}</strong> {{ __('These Terms do not create a partnership, joint venture, employment relationship, franchise or agency relationship.') }}</li><li><strong>{{ __('Force Majeure:') }}</strong> {{ __('The Company will not be responsible for delay or failure caused by events beyond reasonable control, including natural disasters, war, civil unrest, government action, telecommunications or power failures, widespread cyber incidents, infrastructure outages or failures of third-party providers.') }}</li><li><strong>{{ __('Survival:') }}</strong> {{ __('Intellectual property, confidentiality, payment obligations, indemnification, limitations of liability, dispute resolution and provisions that by their nature should survive will survive termination.') }}</li><li><strong>{{ __('Electronic Records:') }}</strong> {{ __('Electronic acceptance, notices, records and communications may be used to evidence agreement and communications to the extent permitted by applicable law.') }}</li></ul></section>

                <section id="contact"><h2><span class="section-number">22</span>{{ __('Contact and Legal Notices') }}</h2><p>{{ __('For legal, privacy, security, billing or support enquiries, contact the Company using the official contact details published within Kairo CORE or the applicable subscription documentation.') }}</p><p><strong>{{ __('Current support/legal contact:') }}</strong> twaynehlatywayo09@gmail.com</p><p>{{ __('For formal legal notices, the Company may require notices to be sent through a designated legal or administrative channel specified in the applicable agreement.') }}</p><div class="signature"><strong>{{ __('Acceptance') }}</strong><p style="margin:8px 0 0;">{{ __('By clicking “I Agree”, creating an account, signing an applicable order, or using Kairo CORE, you acknowledge that you have read and accepted these Terms.') }}</p></div></section>

                <div class="divider"></div>
                <p class="meta"><strong>{{ __('Document version:') }}</strong> 2.0 &nbsp;•&nbsp; <strong>{{ __('Effective date:') }}</strong> 7 September 2026</p>
            </div>
        </main>
    </div>

    @if(!$isPdf)
    <div class="footer">&copy; {{ date('Y') }} Kairo CORE. {{ __('All rights reserved.') }}</div>
    @endif
</div>
</body>
</html>
