<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Platform Terms of Service & Terms of Use') }} - Kairo CORE</title>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-700: #334155;
            --gray-900: #0f172a;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            line-height: 1.7;
            margin: 0;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--gray-100);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        h1 {
            font-size: 2rem;
            color: var(--gray-900);
            margin: 0;
        }
        .meta {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        .btn-pdf {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-pdf:hover {
            background: var(--primary-dark);
        }
        h2 {
            font-size: 1.25rem;
            color: var(--gray-900);
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        p, ul, ol {
            margin-bottom: 1.25rem;
        }
        ul, ol {
            padding-left: 1.5rem;
        }
        li {
            margin-bottom: 0.5rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-link">&larr; {{ __('Back to Registration') }}</a>
        
        <div class="header">
            <div>
                <h1>{{ __('Terms of Service & Terms of Use') }}</h1>
                <div class="meta">{{ __('Last Updated: August 20, 2026') }}</div>
            </div>
            <a href="{{ route('platform.terms.pdf') }}" class="btn-pdf">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                {{ __('Download PDF') }}
            </a>
        </div>

        <p>{{ __('Please read these Terms of Service and Terms of Use ("Terms", "Agreement") carefully before accessing or using our software application, multi-tenant platform, websites, and associated services (collectively, the "Service" or "Platform").') }}</p>

        <p>{{ __('By creating an account, accessing, or using the Service, you ("User", "Tenant", or "you") agree to be bound by these Terms. If you are entering into this Agreement on behalf of a company or other legal entity, you represent that you have the authority to bind such entity to these Terms. If you do not agree, do not access or use the Service.') }}</p>

        <h2>{{ __('1. Account Access, Tenant Isolation & Acceptable Use') }}</h2>
        <p><strong>{{ __('Tenant Scoping & Security:') }}</strong> {{ __('Access to the Service is resolved and isolated at the application boundary via subdomains, headers, or token claims. You are strictly responsible for maintaining the confidentiality of your credentials and for all activities, data transfers, and transactions conducted under your tenant environment.') }}</p>
        <p><strong>{{ __('Prohibited Conduct:') }}</strong> {{ __('You explicitly agree not to:') }}</p>
        <ul>
            <li>{{ __('Attempt to break tenant isolation boundaries, perform unauthorized cross-tenant data access, or bypass tenant resolution middleware.') }}</li>
            <li>{{ __('Probe, scan, or test the vulnerability of the Platform, or breach any security or authentication measures.') }}</li>
            <li>{{ __('Overburden, flood, or execute denial-of-service (DoS) attacks, automated scraping, or rate-limit circumvention against any endpoint or API.') }}</li>
            <li>{{ __('Reverse engineer, decompile, disassemble, or derive source code from the Service.') }}</li>
        </ul>

        <h2>{{ __('2. Disclaimer of Warranties ("As-Is" & "As-Available")') }}</h2>
        <p><strong>{{ __('No Guarantees:') }}</strong> {{ __('THE SERVICE IS PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS, WITHOUT WARRANTIES OF ANY KIND, WHETHER EXPRESS, IMPLIED, STATUTORY, OR OTHERWISE.') }}</p>
        <p><strong>{{ __('Exclusion of Implied Warranties:') }}</strong> {{ __('TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, WE EXPRESSLY DISCLAIM ALL WARRANTIES, INCLUDING BUT NOT LIMITED TO IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, TITLE, NON-INFRINGEMENT, AND ANY WARRANTIES ARISING OUT OF COURSE OF DEALING OR USAGE OF TRADE.') }}</p>
        <p><strong>{{ __('Operational Disclaimers:') }}</strong> {{ __('WE DO NOT WARRANT THAT THE SERVICE WILL BE UNINTERRUPTED, TIMELY, SECURE, ERROR-FREE, VIRUS-FREE, OR THAT ANY DEFECTS OR APP CRASHES WILL BE CORRECTED. YOU ASSUME FULL RESPONSIBILITY AND RISK FOR YOUR USE OF THE PLATFORM.') }}</p>

        <h2>{{ __('3. Absolute Limitation of Liability & Loss of Data') }}</h2>
        <p><strong>{{ __('Data Loss Disclaimer:') }}</strong> {{ __('WE ARE NOT LIABLE FOR ANY LOSS OF DATA, DATA CORRUPTION, OR UNAUTHORIZED DATA ACCESS RESULTING FROM CYBERATTACKS, HACKING, THIRD-PARTY BREACHES, SYSTEM CRASHES, HARDWARE FAILURES, DATABASE CORRUPTION, OR ANY OTHER CAUSE. YOU ARE SOLELY RESPONSIBLE FOR MAINTAINING INDEPENDENT BACKUPS OF YOUR DATA.') }}</p>
        <p><strong>{{ __('Exclusion of Consequential Damages:') }}</strong> {{ __('TO THE MAXIMUM EXTENT PERMITTED BY LAW, IN NO EVENT SHALL WE, OUR AFFILIATES, OFFICERS, DIRECTORS, EMPLOYEES, AGENTS, OR SUPPLIERS BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, COVER, OR PUNITIVE DAMAGES (INCLUDING LOSS OF PROFITS, REVENUE, GOODWILL, DATA, OR USE) HOWEVER CAUSED, UNDER ANY THEORY OF LIABILITY, INCLUDING CONTRACT, TORT (INCLUDING NEGLIGENCE), OR STRICT LIABILITY.') }}</p>
        <p><strong>{{ __('Monetary Liability Cap:') }}</strong> {{ __('OUR TOTAL CUMULATIVE AND AGGREGATE LIABILITY ARISING OUT OF OR RELATING TO THIS AGREEMENT OR THE USE OF THE SERVICE SHALL NOT EXCEED THE LESSER OF:') }}</p>
        <ul>
            <li>{{ __('THE TOTAL FEES ACTUALLY PAID BY YOU TO US IN THE ONE (1) MONTH IMMEDIATELY PRECEDING THE EVENT GIVING RISE TO THE CLAIM, OR') }}</li>
            <li>{{ __('$100 USD.') }}</li>
        </ul>

        <h2>{{ __('4. User Indemnification Clause') }}</h2>
        <p><strong>{{ __('Coverage of Legal Claims:') }}</strong> {{ __('You agree to defend, indemnify, and hold harmless us, our operators, officers, directors, employees, contractors, and agents from and against any and all claims, liabilities, damages, losses, obligations, costs, or debt, including but not limited to all attorney’s fees, court costs, and legal expenses, arising from or related to:') }}</p>
        <ul>
            <li>{{ __('Your access to or use of the Service.') }}</li>
            <li>{{ __('Any violation by you or your end-users of these Terms or applicable laws.') }}</li>
            <li>{{ __('Any Customer Data or content uploaded, stored, or processed under your tenant account.') }}</li>
            <li>{{ __('Any claim that your use of the Service caused damage or violation of rights to a third party (including data privacy breaches, intellectual property infringement, or unauthorized data collection).') }}</li>
        </ul>

        <h2>{{ __('5. Data Privacy & Compliance Alignment') }}</h2>
        <p><strong>{{ __('Privacy Policy:') }}</strong> {{ __('Your use of the Service is also governed by our Privacy Policy, which details how we handle user data, telemetry, and system processing. By using the Service, you consent to our data collection and handling practices as set forth therein.') }}</p>
        <p><strong>{{ __('Tenant Responsibility for End-Users:') }}</strong> {{ __('You are solely responsible for providing legally compliant privacy notices to your end-users, obtaining necessary consents, and managing regulatory requests (including GDPR "Right to be Forgotten" or CCPA opt-out requests) applicable to data collected through your tenant instance.') }}</p>

        <h2>{{ __('6. Account Termination & Suspension at Our Discretion') }}</h2>
        <p><strong>{{ __('Right to Terminate:') }}</strong> {{ __('WE RESERVE THE RIGHT, IN OUR SOLE AND ABSOLUTE DISCRETION, TO SUSPEND, DISABLE, OR TERMINATE YOUR ACCOUNT, TENANT ACCESS, OR USE OF THE SERVICE AT ANY TIME, WITH OR WITHOUT CAUSE, WITH OR WITHOUT PRIOR NOTICE, AND WITHOUT LIABILITY TO YOU.') }}</p>
        <p><strong>{{ __('Effect of Termination:') }}</strong> {{ __('Upon termination, your right to use the Service will immediately cease. We are under no obligation to retain, store, or export your data following account termination or suspension, and we may permanently delete your data from our servers.') }}</p>

        <h2>{{ __('7. Intellectual Property & Platform Ownership') }}</h2>
        <p>{{ __('All rights, titles, and interests in and to the Platform (including codebase, system architecture, database schemas, APIs, branding, UI design, and documentation) remain our exclusive property. Nothing in these Terms grants you any license or ownership right to our code or intellectual property beyond the limited right to access the Service in accordance with these Terms.') }}</p>

        <h2>{{ __('8. Governing Law & Dispute Resolution') }}</h2>
        <p>{{ __('These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which the Service owner operates, without regard to its conflict of law provisions. Any legal action or proceeding arising out of or relating to these Terms shall be instituted exclusively in the courts of that jurisdiction, and you consent to personal jurisdiction therein.') }}</p>

        <h2>{{ __('9. Modifications to Terms') }}</h2>
        <p>{{ __('We reserve the right to modify or replace these Terms at any time. Continued use of the Service following any changes constitutes acceptance of the new Terms.') }}</p>

        <h2>{{ __('10. Contact Information') }}</h2>
        <p>{{ __('For legal, support, or security inquiry disclosures, please reach out to:') }}<br>
        <strong>{{ __('Support Email:') }}</strong> twaynehlatywayo09@gmail.com</p>
    </div>
</body>
</html>
