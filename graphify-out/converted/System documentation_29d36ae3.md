<!-- converted from System documentation.docx -->





Kairo CORE
School Management System
System Documentation




A complete user guide for school administrators,
teachers, bursars, clerks and students






Version 1.0

# How to Use This Manual
Welcome to KairoCORE System Documentation, a complete web-based school management system that runs directly in the browser and no software installation is required. Every institution receives a private, dedicated workspace with a custom web address, independent user access, and fully isolated data, ensuring absolute privacy and security across schools.
## Who this manual is for
This manual is written for the people who actually use the system day to day and it assumes no technical background. You do not need to know anything about web servers, databases or programming.
| Your role | What you may use the system for | Start reading at |
| --- | --- | --- |
| System administrator | Every operation in the system, from Registering the school, choosing a subscription, system settings, managing users, configurations etc | Chapter 1, then Chapters 2, 3 and 10 |
| School Administrator | Academic structure, timetables, examinations, progression, student management, School ID cards, Inventory & Procurement Inventory & Procurement | Chapters 4 and 6 |
| Bursar / finance officer | Fees, invoices, payments, expenses, Financial Statements, Financial Analytics | Chapter 7 |
| HR officer | Staff records, payroll, leave, attendance | Chapter 7 |
| Admissions officer | Online applications, review, enrollment | Chapter 5 |
| Class teacher | Timetable, attendance, marks, homework | Chapters 4 and 6 |
| Subject teacher | Timetable, homework, marks entry | Chapters 4 and 6 |
| Librarian | Catalogue, issuing and returning books | Chapter 8 |
| Welfare / boarding master | Hostel allocation, out-passes, welfare cases | Chapter 8 |
| Nurse | Clinic visits, medical records | Chapter 8 |
| ICT administrator | System settings, user accounts, backups | Chapter 10 |
| Student | The student portal — homework, results, fees, timetable | Chapter 10 |
| Parent / guardian | The public website and the online application form | Chapters 5 and 9 |
| Platform operator (the company selling the software) | Approving schools, billing, support | Chapter 10 |

## How this manual is organised
The manual follows the order in which you actually need to do the work.
- Chapters 1 to 3 explains what the system is, how a school signs up, and how people get user accounts. Read these first.
- Chapters 4 to 10 are organised module by module, in the same order the modules appear in the sidebar. Each chapter covers one module and describes every page in it.
- Chapter 11 is the appendix: a glossary, a page index, a notification reference, a command reference and a troubleshooting section.
## Reading the formatting
The manual uses a small number of visual signals. Once you know them you can scan any page quickly.
Bold text means one of two things: it is the name of a button, menu item, field or column exactly as it appears on screen, or it is an instruction you must follow.
Italic text is used for explanations, examples and things that are optional.
Text in monospace like this is a literal value — a field name, a file name, a URL, a status value or a command. Copy these exactly.
Coloured boxes carry a label that tells you why the box is there:
TIP: A shortcut or a piece of advice that will save you time.
NOTE: Background information that explains why something works the way it does.
IMPORTANT: Something you must not skip, because later steps depend on it.
WARNING: Something that will cause data loss, a wrong result, or a security problem if you get it wrong.
EXAMPLE: A complete worked example with real values you can copy and adapt.
CAUTION: A known limitation in the current version of the software. These are collected in Appendix 11.6.
Tables use a shaded header row. Where a field is marked Required, you cannot save the record without it.
## Conventions used in this manual
Paths. The staff workspace lives at /workspace  which is also the main system dashboard. So the Academic Years page is at /workspace/academic-years and the Student Directory is at /workspace/students.  The student portal is at /student/student-portal.
Navigation. The sidebar (which defaults to the left-hand side but can be switched to the right using the toggle button in the top-right toolbar) groups all application modules. Once you click into a module, a second bar appears at the top of the page with that module's pages and we call these module tabs. In this manual we write them like this:
Academics → Setup & Structure → Academic Years
meaning: click Setup & Structure under Academics in the sidebar, then Academic Years tab.
Role names. Where this manual names a role such as Bursar or Head Teacher, that is the job title. Where it names a role such as Accounts / Finance or Teaching Staff, that is a system role and every role has a set of permissions inside the software. Chapter 3 explains the difference and lists every system role.
Dates. The system stores dates as YYYY-MM-DD, for example 2026-01-15. Screens display them in a friendlier format, for example 15-Jan-2026.
Money. Amounts are shown in the currency you set under System Settings → System Preferences → Primary Ledger Currency. That setting defaults to USD and should be changed to your own currency before you invoice anything — see Section 10.4.
NOTE: This manual documents the system as it behaves in the current release. Where the software's behaviour is surprising, the manual says so plainly rather than pretending the behaviour is intentional. Every such case is also listed in Appendix 11.6 — Known issues and limitations.
## Conventions for the examples
Every example in this manual uses the same fictional school, so that the numbers line up as you read:
| Setting | Value used in examples |
| --- | --- |
| School name | Tinway Primary School |
| Web address | tinway (so the site is tinway.kairocore.co.zw) |
| Country | Zimbabwe |
| Currency | USD (the system default; see Section 10.4) |
| Academic year | 2026, running 13 January 2026 to 2 December 2026 |
| Terms | Term 1 (Jan–Apr), Term 2 (May–Aug), Term 3 (Sep–Dec) |
| Levels | ECD A, ECD B, Grade 1 … Grade 7 |
| A class inside a level | Grade 3A, Grade 3B or Grade 6 North, Grade 6 South |
| Administrator | Liam Moyo, moyol@tinwayprimary.kairocore.co.zw |
| A teacher | Tadiwa Mutamba mutambat@tinwayprimary.kairocore.co.zw |
| A student | Ronald Mufudzi, admission number 2601-0042 |

## Getting help inside the system
This manual is the reference for the system as it stands today.
NOTE:  in-application Help panel (accessed via the ? Help button in the top bar of each page) is available to explain each page's workflow. For additional troubleshooting, refer to the Problems and Solutions table at the end of each chapter, which covers known traps and limitations.
TIP: If a menu item is missing from your sidebar, you do not necessarily have a fault. Most pages in this system are reached through the module tab bar at the top of the page rather than the sidebar, and pages your role cannot use are hidden deliberately. Chapter 3 explains how to check what your role can see.
## Conventions for screenshots and placeholders
This manual contains no screenshots. Wherever you see a value written in monospace, it is the exact stored value. Wherever you see a value written in ordinary italic text, it is a suggestion so you pick whatever suits your school.







# Table of Contents
How to Use This Manual	2
Who this manual is for	2
How this manual is organised	2
Reading the formatting	3
Conventions used in this manual	3
Conventions for the examples	4
Getting help inside the system	4
Conventions for screenshots and placeholders	4
Table of Contents	5
Phase 1 — System Overview	20
1.1 What KairoCORE Is	20
1.2 The Three Interfaces	20
1.3 How Tenancy Works — Subdomains, Not URL Segments	21
1.4 The Life of a School — Status Values	22
How a school moves from pending to active	22
1.5 The Technology Underneath	23
1.6 Terminology You Will Meet	24
1.7 The Modules	25
1.8 Complete Feature Inventory	26
Getting started and access	26
Academics	27
Admissions	28
Students	29
Examinations and grading	29
Attendance	30
Finance	30
Human resources and payroll	31
Inventory and procurement	32
Library and knowledge	32
Boarding and welfare	32
Health and safety	33
Communication	33
Learning management	34
Website	34
Reports and intelligence	34
Administration and billing	35
Platform back office	36
1.9 Deployment and Operations	36
1.10 The Fifteen-Step Academic Setup Checklist	37
1.11 What To Do First — The Short Version	37
Phase 2 — Registering a School and Going Live	38
2.1 The Full Picture	38
2.2 Step 1 — Institution and Administrator	38
Two things that surprise people	39
Validation that runs as you type	39
2.3 Step 2 — Choosing Your Web Address	40
Live availability checking	40
The 54 reserved names	40
What happens if someone else takes it first	40
2.4 Step 3 — Choosing Your Modules	41
2.5 Terms and Submission	41
2.6 What the Platform Operator Does	42
Who gets notified	42
Approving the school	42
The other operator actions on a school	42
2.7 Activating the Administrator Account	43
What the activation page checks	43
The activation form	43
What happens on success	44
If the link expired	44
2.8 Demonstration Data	44
2.9 The Subscription — Created by Hand	44
The plan that ships with the system	45
The subscription lifecycle	45
Automated billing and dunning	45
Payment gateways	46
2.10 The Academic Region Preset	46
Zimbabwe — zimbabwe	46
South Africa — south_africa	46
United States — united_states	46
2.11 Your First Ten Minutes After Activation	46
2.12 Worked Example — Registering Greenfield Primary School	47
2.13 Problems and Solutions	48
Phase 3 — Registering Users and Managing Access	49
3.1 Accounts and Identity — The Key Concepts	49
3.2 Account Statuses — What They Mean	49
Which panel each status can reach	50
3.3 The Four Ways an Account Gets Created	50
3.4 Entry Point 1 — The HR Employee Wizard	51
Step 1 — Personal Information	51
Step 2 — Employment Details	51
Step 3 — Grade and Salary	52
Step 4 — Family and Medical	52
Step 5 — Documents	52
What happens when you save	52
Bulk activation	52
3.5 Entry Point 2 — Self-Registration on the Login Page	53
How the roster check works	53
Duplicate handling	53
What happens on submit	53
Approvers — who gets notified	54
3.6 Entry Point 3 — The User Accounts Directory	54
The form	54
Table columns	55
What happens when you save	55
3.7 Entry Point 4 — Enrolling From an Application	55
3.8 The Approval Workflow	55
Approve registration	55
Reject registration	56
Resend activation link	56
Delete	56
3.9 The Eleven System Roles	56
Self-service permissions every role gets	57
Your effective permissions	57
3.10 Departments — Adding Permissions by Organisation	57
Managing departments	58
3.11 Custom Roles	58
3.12 The Granular Permission Reference	59
3.13 Why a Page Is Missing — A Diagnostic Checklist	60
3.14 Worked Examples	60
Example A — Hiring a mathematics teacher	60
Example B — Adding a bursar who is not on the staff roster	61
Example C — A student claims their portal account	61
Example D — A stranger tries to register themselves	61
Phase 4 — The Academics Module	62
4.1 How This Module Is Organised	62
4.2 Academic Years — Start Here	62
The form	62
You may create as many years as you like — but only one can be active	63
How to change the active year	63
Terms — created inside the year form	63
The list	64
Rules that protect your data	64
Bulk creating years with a spreadsheet	64
4.3 Level — Defining Your Year Groups	65
The form	65
Creating classes — this is the only place you can	65
The list	66
Rules that protect your data	66
Bulk creating levels with a spreadsheet	66
4.4 Subjects	67
The form	67
The list	67
Protection	67
Bulk creating subjects	67
4.5 Classrooms	68
The form	68
The list	68
Bulk creating classrooms	68
4.6 Time Slots — The Periods of the School Day	69
4.7 Timetables and Teaching	69
Step 1 — Compile the period blocks	69
Step 2 — Choose the scope and the class	70
Step 3 — Place lessons	70
Conflict protection — the system will not let you create a clash	70
Helpful warnings on the page	71
View Timetable — the read-only twin	71
Teacher Assignments	71
The scope rule — this is the important part	72
4.8 Academic Operations Center	72
Marking a step complete by hand	72
4.9 Setup & Structure — The Complete Workflow	73
4.10 Progression — Promotion and Screening	74
Promotion — moving students up a level	74
Screening — placing students by sCORE	75
A page to avoid	76
4.11 Academic Reports (Report Cards)	77
4.12 The Complete First-Time Setup — Ordered Checklist	77
4.13 Academic Problems and Solutions	78
Phase 5 — Admissions and Students	79
5.1 How the Two Modules Fit Together	79
5.2 The Public Application Form	79
Step 1 — Student Profile	79
Step 2 — Parent or Guardian	80
Step 3 — Documents	80
What the parent sees at the end	80
Anti-spam and safety	80
What the system does on submission	81
Who gets notified	81
5.3 Reviewing Applications	81
The application record — four tabs	81
Filtering the list	82
5.4 Enrolling a Student	82
The confirmation form	82
What the action does, in order	83
Auto-assigning the class	84
5.5 Bulk Enrolling	84
5.6 Entering a Student Directly	84
5.7 The Student Record	85
Tab 1 — Personal Details	85
Tab 2 — Enrollment	86
Tab 3 — ID Card	86
The student list	86
Importing students from a spreadsheet	87
Editing a student	87
5.8 Guardians	87
5.9 Student Documents	88
5.10 The ID Card Designer	88
Template settings	88
Element visibility	89
Design controls	89
Position and size — per element	89
Live preview	89
Versions	89
Table actions	89
5.11 Printing ID Cards	89
Print scopes	90
Output formats	90
Print verification — an anti-fraud feature	90
Bulk printing from the student list	90
5.12 Student Medical Records	90
5.13 Worked Example — A Complete Admission	91
5.14 Admissions and Students — Problems and Solutions	92
Phase 6 — Examinations, Grading and the LMS	93
6.1 How Grading Works — The Full Chain	93
6.2 The Exams & Grading Module Structure	93
6.3 Assessment Types	94
6.4 Grading Scales	94
A complete grading scale	95
6.5 The Question Bank	95
6.6 Digital Assessments	96
Setting up an assessment	96
The student experience	96
Marking	97
6.7 Marks Entry, Manual Marking and Analytics	97
Marks Entry	97
Manual Marking	97
Performance Analytics	97
6.8 Academic Reports — Building the Report Card	98
Creating a report	98
The competency rating scale	98
The list	99
Generating reports in bulk	99
Publishing	99
Report templates	99
6.9 Workflows — Tracking an Assessment Through Its Life	99
Setting up a workflow	100
The seven workflow statuses	100
6.10 Publish to Student Portal	101
6.11 The LMS Module	101
Setting homework	101
The list	101
Grading submissions	102
The student experience	102
6.12 The Student Portal	103
6.13 Marks and Grades — Problems and Solutions	104
Phase 7 — Finance, HR, Payroll, Attendance and Leave	105
7.1 The Finance Module Structure	105
7.2 Fee Categories	105
7.3 Fee Structures	105
7.4 Invoicing — The Two Ways to Bill	106
A. Automatic bulk billing	106
B. A single manual invoice	106
The invoice	107
Recording a payment	107
The other invoice actions	107
Bank deposit submissions	107
7.5 Fee Collections	108
7.6 Fee Waivers	108
7.7 Revenue Streams and Bank Accounts	108
Revenue streams	108
Revenue and expense categories	109
Expense types	109
Bank accounts	109
7.8 Expenses and Purchasing	109
Recording an expense	109
Expenses versus purchases — how they differ	110
7.9 CORE Accounting	110
Financial statements	110
Document templates	111
7.10 The Executive Financial Dashboard	111
7.11 Student Financial History	111
7.12 Finance — Problems and Solutions	112
7.13 The HR & Payroll Module Structure	113
7.14 Salary Grades	113
Salary components	113
7.15 The Payroll Workflow	114
Pay periods	114
Pay runs	114
7.16 Staff Loans	115
7.17 Attendance	115
Staff attendance	115
Student attendance	116
7.18 Leave	116
Leave types	116
The request workflow	116
7.19 Disciplinary Cases	117
7.20 Worked Example — A Term's Billing and Payroll	118
7.21 Finance, HR and Attendance — Problems and Solutions	118
Phase 8 — Inventory & Procurement, Library, Boarding & Welfare, Health & Safety	119
8.1 Inventory & Procurement — The Whole Chain	119
8.2 Inventory Items	120
8.3 Issuing Stock	121
8.4 Stock Adjustments	121
8.5 The Procurement Workflow	121
Step 1 — The request	121
Step 2 — What approval actually does	122
Step 3 — The purchase order	122
Step 4 — Receiving the goods	123
Step 5 — Suppliers	123
8.6 Fixed Assets and Depreciation	123
The register	123
Posting depreciation	124
Asset maintenance	124
8.7 Inventory — Problems and Solutions	124
8.8 The Library Module	125
8.9 Books and Resources	125
How copies work	126
8.10 eResources	126
8.11 Issuing and Returning Books	126
The circulation desk	126
What the system does automatically	126
8.12 The Knowledge Hub — The School's Shared Repository	127
Visibility levels	127
Repositories Gallery	127
8.13 Library — Problems and Solutions	127
8.14 What Students See in the Library	128
8.15 Boarding & Welfare	128
8.16 Hostels, Floors and Rooms	128
Hostels	128
Rooms	129
8.17 Allocating a Learner to a Bed	129
Step 1 — Student Mapping	129
Step 2 — Room Mapping	129
Step 3 — Processing Rules	129
8.18 Out Passes	130
The approval chain — three steps	130
8.19 Hostel Attendance	130
8.20 Inspections	131
8.21 Boarding — Problems and Solutions	131
8.22 Health & Safety	132
8.23 Medical Records	132
8.24 Clinic Visits	132
8.25 Health — Problems and Solutions	133
8.24 Cross-Module Tips	133
Phase 9 — Communication Center, Website, Reports & Intelligence	134
9.1 The Communication Center Structure	134
9.2 Notices — Announcements	134
The four statuses	135
9.3 Events	135
9.4 Schedule — Events and Tasks Together	136
The event form on the Schedule page	136
Tasks	136
Reminders	137
9.5 Chat	137
9.6 Campus Resources	137
9.7 Polls & Surveys	138
9.8 Helpdesk Tickets	138
9.9 Kairo CORE Messages	139
9.10 Communication — Problems and Solutions	139
9.11 Getting a Message to Parents	140
9.12 The Website Module	140
9.13 Choosing a Template	140
9.14 Pages	141
9.15 The Visual Builder	141
The block palette — 23 block types	141
9.16 How the Public Site Works	141
9.17 Website — Problems and Solutions	142
9.18 Reports & Intelligence Structure	142
9.19 The Reporting Dashboard	142
9.20 The Report Generator	143
Output formats	144
9.21 The 29 Report Presets	144
Students (3)	144
Academics (2)	144
Attendance (2)	144
Finance (4)	144
Admissions (2)	145
Timetable (1)	145
Library (2)	145
Hostel (1)	145
Clinic (1)	145
Inventory (5)	145
HR (4)	145
Administration (1)	146
Communication (1)	146
9.22 Generated Reports	146
The Accuracy badge — the most useful column	146
9.23 Custom Report Templates	147
9.24 Analytics Explorer	147
Academics & Student Setup (7)	147
Welfare (5)	147
Financials (6)	148
Systems (3)	148
9.25 Who Can Use Reports	148
9.26 Reports — Problems and Solutions	148
Phase 10 — System Administration, Subscription & Billing, the Student Portal and the Platform Panel	149
10.1 The System Administration Structure	149
10.2 The Administration Dashboard	150
10.3 System Settings	150
Tab: Modules	150
Tab: Branding	151
Tab: Institution Profile	151
Tab: Footer & Branding	151
Tab: Banking & Payments	151
Tab: Authentication & Security	152
Tab: System Preferences	152
Tab: Legal & Terms	152
Tab: Email Configuration	152
Tab: Notifications	153
Tab: Feature Flags	153
10.4 Email Configuration in Detail	153
10.5 User Accounts, Roles and Departments	154
10.6 The Built-In Roles	154
10.7 The Audit Log	154
10.8 Data Export	155
10.9 Administration — Problems and Solutions	156
10.10 Subscription & Billing	156
10.11 The Billing Overview	156
What the screen shows	156
Paying	157
10.12 My Subscription	157
What the subscription states mean	157
10.13 Subscription — Problems and Solutions	158
10.14 The Student Portal	158
My Fees	159
My Profile	159
10.15 The Platform Panel — /platform	159
Pages	159
Resources	159
10.16 Approving a New School	160
School statuses and actions	160
10.17 How Tenancy Works	161
10.18 What Happens When a Subscription Expires	161
10.19 Platform — Problems and Solutions	161
10.20 Phase 10 Checklist	162
Phase 11 — Appendices	163
11.1 Glossary	163
11.2 Page Index	165
Admissions (/workspace/…)	166
Students	166
Academics	166
Exams & Grading	166
Finance	167
HR & Payroll	167
Inventory & Procurement	168
Library	168
Boarding & Welfare	169
Health & Safety	169
Communication Center	169
LMS	169
Website	170
Reports & Intelligence	170
System Administration	170
Subscription & Billing	170
Always available	171
Student portal (/student/…)	171
11.3 Notification Reference	171
11.4 Configuration Reference	172
11.5 Quick Troubleshooting Index	173
11.6 Known Issues and Limitations	174
Serious — read these before trusting the affected screen	174
Defects that block a screen	175
Missing features	176
Reporting problems	176
Data-quality and display problems	177
11.7 Glossary of Status Values	179
11.8 Who Can Do What — A Quick Reference	180
11.9 Glossary of Commands	181
11.10 Closing Note	181



# Phase 1 — System Overview
## 1.1 What Kairo CORE Is
Kairo CORE (Central Operations & Resource Evironment) is a multi-tenant school management system that also supports single-tenant deployments. In this architecture, each individual school functions as an independent tenant, ensuring that every tenant receives:
- its own private workspace on the internet, addressed by its own web address (a subdomain),
- its own users — staff and students,
- its own copy of every record — students, fees, timetables, everything,
- its own branded public website,
- its own subscription and billing history.
Data privacy and security are strictly enforced across all tenants. For example, a bursar at Tinway Primary School cannot view records belonging to Liam Academy. This isolation is not a configurable setting that can be accidentally turned off; instead, it is handled automatically at the model layer on every database query using a global scope. Whenever a query is executed, the system automatically appends the relevant school identifier (e.g., WHERE school_id = your_school), ensuring data boundaries are completely secure by design.
## 1.2 The Three Interfaces
Kairo CORE has three separate login interfaces. They are entirely different applications sharing one database and one design system.
| Interface | Address | Who signs in | Purpose |
| --- | --- | --- | --- |
| Staff Workspace | https://<yourschool>.kairocore.co.zw/workspace | Administrators, teachers, bursars, clerks, IT staff | The main working application. Everything in Chapters 4 to 10 happens here. |
| Platform Back Office | https://kairocore.co.zw/platform | The software company's own operators only | Approving new schools, subscriptions, invoices, support, backups. Not available to schools. |
| Student Portal | https://<yourschool>.kairocore.co.zw/student | Students only | A deliberately small, safe and isolated space for students where each student see his or her homework, results, timetable, fees, notices. Students are strictly blocked from the staff workspace. |
| Public Website | https://<yourschool>.kairocore.co.zw/ | Anyone, no login | The school's public website, built with the built-in website builder and content management system. Includes the online application form and contact form, about page and others. |



## 1.3 How Tenancy Works — Subdomains, Not URL Segments
Kairo CORE resolves the school from the hostname of the request, not from a URL parameter. This is the single most important architectural fact for understanding the system.
When you visit https://Tinway.kairocore.co.zw/workspace/academic-years, a middleware called ResolveTenant reads the hostname, strips the base domain, and is left with the string Tinway. It looks that up in the schools table, finds Tinway Primary School, and binds it into the application as the current tenant. From that moment on, every model query in the request is automatically scoped to that school.
The middleware runs before authentication and before any panel loads. Its full decision tree is:
| Situation | Result |
| --- | --- |
| tenancy.mode is single (a one-school installation) | The single school is always used, regardless of hostname. |
| Hostname is the bare base domain (kairocore.co.zw) | Allowed, but the student portal is blocked with a 404. Students must use their school's subdomain. |
| Hostname's subdomain matches no school | HTTP 404 — the site simply does not exist. |
| Subdomain matches a school whose status is suspended | HTTP 403 with a "school suspended" page. All users are redirected to the billing overview instead. |
| Subdomain matches a school whose status is pending | HTTP 403 with a "school awaiting approval" page. A school that has signed up but not yet been activated cannot serve any content. |
| Subdomain matches an active school | The request proceeds, and the school is bound as the current tenant. |

Two further jobs are done by the same middleware:
- Brand colours. It reads the school's branding.theme setting and applies it as the Filament primary colour, so the whole interface is automatically recoloured to match the school's logo and colours.
- Caching. The subdomain-to-school lookup is cached for 300 seconds to keep the site fast. The cache is flushed automatically whenever a school is saved or deleted.
EXAMPLE: If your school is called Tinway Primary School and you chose the web address Tinway during sign-up, your workspace is at https://Tinway.kairocore.co.zw/workspace. Your public website is at https://Tinway.kairocore.co.zw/, and online application goes to https://Tinway.kairocore.co.zw/apply-online.
## 1.4 The Life of a School — Status Values
A school moves through four statuses. The status controls whether the site works at all, and it is checked on every single request.
| Status | Meaning | What works |
| --- | --- | --- |
| pending | Signed up but not yet approved by the platform operator (Kairo CORE team). | Nothing. All requests are refused with HTTP 403 and pending for approval. The administrator's activation email has not been sent yet. |
| active | Approved, activated, and running. | Everything. |
| suspended | Approved but unpaid, or manually suspended by the operator. | Sign-in is blocked. Users are redirected to the subscription and billing page so they can pay. The billing, invoice and receipt pages remain reachable — everything else does not. |
| deleted | Soft-deleted. The subdomain is released and all users are soft-deleted with it. | Nothing. The record is retained for audit but the address is freed for re-registration. |

WARNING: Permanently deleting a school deletes all of its data, students, fees, timetables, users, everything. The confirmation dialog says so explicitly. Only the platform operator can do this, and only from the back office.
| School Registration Workflow |
| --- |

| STEP 1 | Submit Public Registration Who: Registrant School status: pending The user submits the online school registration form at https://kairocore.co.zw/register. |
| --- | --- |
▼
| STEP 2 | Initial System Provisioning Who: System School status: pending The system automatically provisions the school with a pending status, creates the pending administrator account, and sets up all required system roles. The platform operator is then notified. |
| --- | --- |
▼
| STEP 3 | Platform Validation & Approval Who: Platform operator School status: pending The Kairo CORE platform operator reviews and approves the school details. Upon approval, an activation email containing a secure link (valid for 48 hours) is automatically sent to the registered user. |
| --- | --- |
▼
| STEP 4 | Credentials Setup Who: School administrator School status: pending The administrator opens the activation link from the email and configures their preferred username and password. |
| --- | --- |
▼
| STEP 5 | System Activation & Preset Deployment Who: System School status: active The user account and school status are both updated to active, bringing the school live. The regional academic preset is applied, along with any requested demonstration data. |
| --- | --- |
Note: The school stays pending until the administrator completes Step 4 and activates the account. Until then, the school website refuses all traffic.

IMPORTANT: The status does not become active when the operator approves. It becomes active only when the administrator actually clicks the activation link and sets a password. Until then the school's website refuses all traffic. This is deliberate: it proves the administrator received the email and controls the real account.
## 1.5 The Technology Underneath
You never need this to use the system, but it is useful to know what you are dealing with, and it explains a few behaviours you might otherwise find surprising.
| Layer | What is used | Notes |
| --- | --- | --- |
| Language | PHP 8.3 | The whole application. |
| Framework | Laravel | The application framework: routing, database, validation, mail, queues. |
| User interface | Filament | The admin interface framework. Every screen described in this manual is a Filament Resource, Page or Livewire component. |
| Interactivity | Livewire + Alpine.js | Powers the registration wizard, the visual timetable builder, the visual website builder and the chat workspace without page reloads. |
| Styling | Tailwind CSS | Controls all appearance and the responsive layout. |
| Database | MySQL 8.0 | One shared database. Tenant isolation is enforced in the query layer, not by separate databases. |
| Web server | Nginx | Serves the pages and forwards PHP to the application. |
| Background work | Laravel queues + scheduler | Sends emails, loads demonstration data, runs nightly billing, chases overdue subscriptions. |
| PDF generation | dompdf / TCPDF | Invoice receipts, report cards, ID cards, statement of account. |
| Deployment | Docker (3 containers) | See Section 1.9. |

NOTE: Because all three Filament panels share one design system, a school that changes its brand colours in System Settings → Design & Branding sees those colours in the workspace, the student portal and the public website at the same time. The platform operator can also set a global default for schools that have not chosen their own.
## 1.6 Terminology You Will Meet
Kairo CORE is used by schools in several countries, so a few words change meaning depending on where you are. The system supports full customization.
| Word | Meaning in KairoCORE |
| --- | --- |
| Tenant | One school inside the multi-tenant installation. |
| Workspace | The staff application, at /workspace. |
| Panel | One of the three user interfaces (staff, platform, student). |
| Module | A major functional area, e.g. Academics, Finance. Shown as a sidebar group. |
| Module tab | A page inside a module, shown as a pill in the bar at the top of the page. |
| Hub | A category landing page that immediately forwards you to the last page you used in that category, or to its first page. |
| Capability | A permission key that decides whether a page is visible and usable, e.g. academics.courses. |
| Role | A named bundle of capabilities, e.g. Accounts / Finance. |
| Department | An organisational grouping (Bursary, Clinic, Library …) that can add extra capabilities on top of a role. |
| Academic year | A school year, e.g. 2026. Exactly one is active at a time. |
| Term | A subdivision of an academic year. Exactly one is active per year. |
| Level / Course | A year group, e.g. Grade 3. (Stored in a table called courses.) |
| Class / Section / Stream | A specific group inside a level, e.g. Grade 3B. (Stored in a table called sections.) There is no separate "Class" menu — classes are created inside their level. |
| Enrollment | The link that places one student in one level, in one class, in one academic year. |
| Workflow | A multi-step process the system guides you through, e.g. promotion. |
| Preset | A saved bundle of settings applied automatically — region presets, department permission presets, report presets. |

## 1.7 The Modules
KairoCORE contains 23 modules. Two of them — Administration and Subscription & Billing — are always switched on and cannot be disabled. The other 21 are chosen at sign-up and can be changed later in System Settings → Manage Modules.
The full list, in sidebar order:
| # | Module | Always on | What it is for |
| --- | --- | --- | --- |
| 1 | Schedule & Tasks | yes | Your personal calendar, tasks, reminders and overdue warnings. |
| 2 | Admissions | no | The public online application form, staff review, and conversion of an application into an enrolled student. |
| 3 | Students | no | The student directory, guardians, documents, and the ID card designer. |
| 4 | Academics | no | Academic years, terms, levels, subjects, classrooms, timetables, promotion and screening. |
| 5 | Exams & Grading | no | Assessment types, question bank, digital assessments, grading scales, marks entry, report cards. |
| 6 | Attendance | no | Daily attendance for students and staff. (Shown inside HR & Payroll → Attendance & Leave.) |
| 7 | Finance | no | Fee structures, invoices, payments, receipts, expenses, purchases, ledgers, financial statements. |
| 8 | HR & Payroll | no | Employee records, salary grades, payroll runs, leave requests, disciplinary cases. |
| 9 | Inventory & Procurement | no | Stock items, suppliers, purchase requests, purchase orders, goods received, fixed assets. |
| 10 | Library | no | Book catalogue, circulation, e-resources. |
| 11 | Knowledge | no | The knowledge hub: documents, galleries, downloads. |
| 12 | Boarding & Welfare | no | Hostels, rooms, beds, allocation, attendance, inspections, out-passes, welfare cases. |
| 13 | Clinic / Health & Safety | no | Clinic visits, prescriptions, medical alerts, student medical records. |
| 14 | Communication | no | Announcements, events, chat, polls, helpdesk tickets, campus resources. |
| 15 | LMS | no | Homework, worksheets, video links, submissions and grading. |
| 16 | Website | no | The visual website builder, page content manager, navigation menus, media library, SEO. |
| 17 | Reports | no | Report dashboard, analytics explorer, report generator, saved reports, schedules, audit. |
| 18 | Administration | yes | System settings, user management, custom roles, departments, audit log, data export. |
| 19 | Subscription & Billing | yes | Your school's own subscription, plan, invoices and receipts. |
| 20 | Digital Assessment | no | Online quizzes, question bank, student attempts and auto-marking. |
| 21 | Recovery | no | Fee recovery and debt collection tracking. |
| 22 | Screening | no | Placement screening rules and screening runs. (Shown inside Academics → Progression.) |
| 23 | Promotion | no | End-of-year promotion runs. (Shown inside Academics → Progression.) |

NOTE: Promotion, Screening, Attendance, Knowledge and Digital Assessment are separate modules in the code, but they do not each get their own sidebar group. They are attached to the module whose work they belong to: Promotion and Screening appear under Academics → Progression, Attendance under HR & Payroll → Attendance & Leave, Knowledge under Library, and Digital Assessment under Exams & Grading → Assessment Center. This is why switching off Promotion removes the promotion tabs but leaves the rest of Academics alone.
## 1.8 Complete Feature Inventory
This section is the one-page answer to "what does this system actually do?". Each feature gets a short, plain description. Later chapters explain the workflow in detail.
### Getting started and access
| Feature | What it does |
| --- | --- |
| Public school sign-up | A three-step public form at /register where a new school supplies its identity, its administrator's details, its web address and which modules it wants. |
| Subdomain reservation | Live availability checking of the chosen web address, with a blocklist of 54 reserved words that cannot be used. |
| Module selection at sign-up | The school picks which of the 21 optional modules to switch on. Administration and Subscription & Billing are locked on. |
| Platform approval | A platform operator reviews the pending school and issues the activation link. |
| Account activation | The administrator follows a 48-hour link, chooses a username and a strong password, and the school goes live with a 3-month trial. |
| Academic region preset | On activation, terminology and a starter list of levels and subjects are loaded to match the school's country. |
| Demonstration data | Optionally, a full set of realistic sample students, staff and finance records is loaded so a new school can explore safely. |
| School self-registration | Staff and students can create their own account from the workspace login page, verified against the school's own staff and student roster. |
| HR-provisioned staff accounts | Creating an employee automatically creates and emails their login. |
| Admissions-provisioned student accounts | Enrolling a student from an application automatically creates and emails their portal login. |
| Administrator-created accounts | An administrator can create any account directly from the user directory. |
| Account approval queue | Self-registered accounts can be held pending until an administrator approves them, optionally assigning a role, departments and extra permissions. |
| Roles and departments | 11 built-in roles, unlimited custom roles, and 15 department permission presets that add capabilities on top of a role. |
| Student portal | A separate, locked-down area for students: homework, results, timetable, fees, notices, polls, library and boarding information. |

### Academics
| Feature | What it does |
| --- | --- |
| Academic years | Create as many school years as you need. Exactly one is active. Each year holds its own terms. |
| Terms / semesters | Up to three terms per year, created inside the year form. Exactly one is the active term. |
| Active-term carry-forward | When the active term changes, unpaid student balances are automatically carried forward to the new term. |
| Levels / Forms / Grades | Define the year groups your school teaches. Each level has a code, an optional form teacher and a setup stage. |
| Classes / Streams / Sections | Create the classes inside a level — 3A, 3B, Red, Blue — each with a capacity, a pinned classroom and a class teacher. |
| Subjects | Define every subject with a code, a type (Theory / Practical / Both), a credit weight and an elective flag. |
| Classrooms | Physical rooms with capacity, building, and features such as projector, whiteboard, science lab equipment, WiFi and air conditioning. |
| Time slots | The periods of the school day — teaching periods, breaks and lunch. Normally generated automatically. |
| Visual timetable builder | A drag-and-drop weekly grid per class or per level, with automatic lesson placement and built-in clash detection. |
| Timetable conflict detection | Prevents a teacher being double-booked, a class being double-booked, and a classroom being double-booked. |
| Timetable viewer and printing | A read-only timetable with colour-coded subjects, printable per class or per level, with an active template you can switch. |
| Teacher assignments | Assign a subject specialist to a subject across a whole level or a single class, and assign class teachers. |
| Academic Operations Center | A 15-step setup checklist that detects what is configured and what is missing, with quick actions and suggested next steps. |
| Workflow override | Staff with the right permission can manually mark a setup step complete or skipped, and every change is audited. |
| Promotion runs | Preview, review and commit end-of-year promotion: who advances, who repeats, who needs screening. Fully reversible before commit. |
| Screening runs | Place students into target classes using configurable sCORE rules, with a clear reason recorded for every placement decision. |
| Academic year readiness | The system refuses to delete or archive configuration that is still in use, with a plain-language explanation of what to do first. |

### Admissions
| Feature | What it does |
| --- | --- |
| Public application form | A three-step form on the school's own website: student profile, parent/guardian details, and document upload. |
| Live application form options | Level and academic year dropdowns are generated from the school's real configuration, so parents only ever see valid choices. |
| Application reference numbers | Every application gets a tracking reference of the form APP-{year}-{6 characters} and the parent is shown a success screen with a copy button. |
| Anti-spam protection | Hidden honeypot fields and rate limiting on both the application and contact forms. |
| Transfer letter rule | Optionally require a transfer letter, but only for non-entry-level applications. |
| Application review tabs | Application → Review (documents, verification, interview) → Approval (status and decision notes) → Enrollment (year, level, class, admission number). |
| Enrol student | A single action that converts an application into a Student record and an Enrollment, copies the documents, creates the portal account and emails the parent. |
| Auto class balancing | If you leave the class blank when enrolling, the system picks the least-populated class for that level and year. |
| Bulk enrol | Convert many reviewed applications to students in one operation. |
| Admission settings | Configure notification emails, the contact address for admissions, and whether a transfer letter is required. |

### Students
| Feature | What it does |
| --- | --- |
| Student directory | Every student, searchable by any part of their name, with filters for status, gender, level and boarding status. |
| Automatic identifiers | A student number is generated on creation, and an admission number is generated in the form YYMM-XXXX-VV on a monthly cycle. |
| Student record tabs | Personal details (including photo, guardian, boarding and health) → Enrollment (year, level, class, roll number) → ID Card (status and expiry). |
| Guardians | Multiple guardians per student, linked through a join table, so both parents can be recorded. |
| Student documents | Eight document types per student, with file storage and download. |
| CSV import | Import students from a spreadsheet with a column-mapping wizard, and export rejected rows so you can fix and retry. |
| ID Card Designer | A visual designer with 11 themes, orientation, barcode format, per-element position/size/colour/font control, and a live preview that uses the same rendering pipeline as the printed card. |
| ID card versioning | Every design change is stored as a version, so you can see what a card looked like historically. |
| ID card printing | Print by selection, class, grade, new cards, expired cards, or "students with no card". Output as PDF, PNG or a ZIP of PNGs. |
| Print verification | Every print job writes a history row with a serial number and an HMAC verification code, so a lost card can be traced. |
| Card status tracking | Cards move through pending_issuance, active, lost, stolen and reissued, each change logged with a timestamp. |
| Photo approval | Photos submitted by users can be approved and locked, or rejected with a reason that notifies the student. |
| Fee waiver flag | A student-level flag that applies a waiver consistently across open invoices. |

### Examinations and grading
| Feature | What it does |
| --- | --- |
| Assessment types | Define the kinds of assessment your school uses — Test, Assignment, Practical, Exam — each with a weight and a maximum mark. |
| Grading scales | Define letter grades and percentage bands, with a Skip / Not Graded option. |
| Question bank | Store reusable questions by subject, difficulty and type, so assessments can be assembled from a bank. |
| Digital assessments | Publish quizzes to the student portal. Students attempt them online; answers are marked automatically. |
| Attempt limits and windows | Set how many attempts a student gets, when the assessment opens, when it closes, and whether it accepts late attempts. |
| Marks entry | Enter or bulk-import marks per class, per subject, per assessment type. |
| Manual marking | A screen for marking non-automated responses and pushing the result back to the mark set. |
| Performance analytics | Per-subject averages, maximums, minimums and distribution, filterable by class and assessment. |
| Academic reports (report cards) | Build a report card per student per term: subject marks, subject teacher remarks, class teacher remarks, headmaster remarks, and a competency block. |
| Competency ratings | Ten CORE competencies (respect, honesty, responsibility, discipline, patriotism, cooperation, leadership, critical thinking, creativity, environment) plus ten additional ones, each rated on a six-point scale. |
| Publishing to the portal | Approve and publish report cards so students can see them in their portal. |
| Report templates | Control which columns appear on a report card, such as the subject teacher remarks column. |

### Attendance
| Feature | What it does |
| --- | --- |
| Student attendance register | Daily register per class with Present, Absent, Late and Excused. |
| Staff attendance | Daily attendance for employees, including check-in and check-out times. |
| Attendance reporting | Attendance rate by class and a month-by-month trend, available as a saved report. |

### Finance
| Feature | What it does |
| --- | --- |
| Fee categories | Classify fees — Tuition, Boarding, Meals, Transport, Uniform — so reports can group them. |
| Fee structures | Set the amount for a fee category for a given level, term or class. |
| Automatic billing | Generate invoices for a whole level, class or individual student in one action, with duplicate-billing protection. |
| Manual invoicing | Raise a one-off invoice with arbitrary lines. |
| Invoice numbering | Monthly invoice number sequences per school. |
| Record payment | Take a payment against an invoice, wholly or partially, with cash, bank, mobile money or other methods. |
| Receipts | A numbered receipt is generated for every payment and can be printed. |
| Overpayment handling | Overpaid amounts become credit on the student's account and are applied to the next invoice automatically. |
| Mobile money / PayNow | Payments initiated in-app and confirmed by polling the gateway. |
| Bank deposit submissions | A parent uploads a deposit slip; staff approve or reject it, and both parties are emailed. |
| Fee waivers | Waive all or part of a fee for a student, applied consistently across invoices. |
| Revenue streams | Track income by stream (termly, uniform, excursions) for reconciliation. |
| Bank accounts | Register the school's bank accounts and choose the default account used on invoices and receipts. |
| Expenses | Record expenses by category and type, with attachment of receipts. |
| Purchases | Purchase orders and the link from a purchase to stock and to the expense ledger. |
| CORE accounting | Chart of accounts, journal entries, ledgers and trial balance. |
| Financial statements | Income statement, balance sheet, cash flow and a per-student statement of account. |
| Student financial history | A single per-student page showing every charge, payment, refund, waiver, carry-forward, invoice and reversal. |
| Executive dashboard | Collection rates, outstanding balances, revenue trends and defaulter lists at a glance. |
| Document templates | Customise the layout of invoices, receipts and statements. |
| Defaulter chasing | An automated job finds overdue invoices and notifies the responsible parties. |

### Human resources and payroll
| Feature | What it does |
| --- | --- |
| Employee wizard | A five-step employee creation wizard: personal information, employment details, grade and salary, family and medical, documents. |
| Automatic staff accounts | Saving an employee creates their login and sends the activation email. |
| Bulk activation | Send activation emails to many employees at once, skipping those already activated. |
| Department records | Departments with a named head, and members, and their own permission set. |
| Salary grades | Grade → minimum, mid and maximum salary, with components. |
| Payroll periods | Define a pay period with start and end dates, and the date it is paid. |
| Payroll runs | Select employees for a period; the system resolves fixed and percentage components from the grade and computes gross, deductions and net. |
| Payslips | Generated per employee per run, printable, with the full breakdown of earnings and deductions. |
| Payroll posting | A completed run posts the employer cost to the expense ledger automatically. |
| Leave requests | Staff request leave; the workflow validates eligibility including probation and gender-based restrictions, and supports delegation when the filer is not an HR user. |
| Leave types | Annual, sick, maternity, compassionate, study leave and any others you define, each with its own rules. |
| Disciplinary cases | Record a case against an employee, add notes, conclude or escalate it. |

### Inventory and procurement
| Feature | What it does |
| --- | --- |
| Inventory items | Items with a category, unit, reorder level, unit cost, current quantity and stock value. |
| Suppliers | Supplier records with contact details and the items they supply. |
| Procurement requests | A department raises a request for items; it is submitted and approved. |
| Purchase orders | Raise an order against an approved request and a supplier, with lines, quantities, unit prices and delivery dates. |
| Goods received | Receive a delivery against a purchase order, accepting or rejecting each line, which updates stock and batch records. |
| Inventory issuance | Issue stock to a department or a student. Availability is checked before the issue is allowed. |
| Stock adjustments | Record the system quantity against the physically counted quantity per item and post the difference. |
| Fixed assets | Register an item as a depreciable asset with a useful life, salvage value and method. |
| Depreciation | Straight-line and reducing-balance depreciation with validation on method, life and salvage value. |
| Asset maintenance | A maintenance log per asset with scheduled and completed work. |
| Employee assets | Assign assets to employees and record what they have been issued. |
| Purchase order repair | A maintenance command that rebuilds malformed purchase order lines. It is safe to run: it is a dry run unless you explicitly ask it to write. |

### Library and knowledge
| Feature | What it does |
| --- | --- |
| Catalogue | Books with title, author, category, format, edition, ISBN and copies. |
| Copies | Each physical copy is tracked individually, so you know which copy is out. |
| Circulation | Issue a book to a student or staff member, set a due date, renew, receive a return, and record a loss. |
| Overdue tracking | Overdue books are reported and can be chased. |
| E-resources | Links to online resources, borrowed under the same rules as physical books. |
| Library issues page | A simplified issuing desk screen for quick counter service. |
| Knowledge hub | Upload documents and media, organise them into galleries, and control access. |
| Versioning | Knowledge assets keep a version history. |

### Boarding and welfare
| Feature | What it does |
| --- | --- |
| Hostel structure | Hostels → floors → wings → rooms → beds, each fully named and capacity-tracked. |
| Room and bed allocation | Allocate a student to a bed, with occupancy reporting and waiting lists. |
| Hostel attendance | Take hostel attendance separately from classroom attendance. |
| Inspections | Record room inspections with findings and follow-up. |
| Out-passes | A student requests permission to leave; a one-time passcode is emailed to the parent for verification. Staff approve or reject. |
| Visitors and hostel staff | Record visitors to the hostel and the staff assigned to each hostel. |
| Welfare cases | Record and follow up welfare concerns about a student. |
| Boarding profile | Each boarder has a profile with contact and care details. |

### Health and safety
| Feature | What it does |
| --- | --- |
| Clinic visits | Record every visit with symptoms, diagnosis, treatment and discharge status. Parents are emailed automatically. |
| Prescriptions | Issue prescriptions against a visit. |
| Medical alerts | Flag critical allergies and conditions that staff must be aware of. |
| Student medical record | A long-term per-student record: blood group, allergies, chronic conditions and an immunisation history. |
| Medical alerts on the register | Alerts surface automatically so a teacher sees them before registering a sick student. |

### Communication
| Feature | What it does |
| --- | --- |
| Announcements | Post news to staff, students, or both. |
| Events calendar | School events on a month, week, day or agenda view, with conflict warnings. |
| Chat | Threaded conversations between staff, with attachments and a Livewire chat workspace. |
| Polls and surveys | Single-vote polls with live standings. |
| Helpdesk tickets | A ticketing system with replies, categories, priority and status. |
| Campus resources | Shareable resources such as forms, calendars and documents. |
| Tasks | Personal and assigned tasks with priority, recurrence and due dates. |
| My Day | A focused view of today: overdue items, important items, tasks assigned to you. |
| Automated reminders | Background jobs send task reminders, daily overdue notices and event reminders. |
| Email configuration | Per-school SMTP settings and email branding, with categories so each type of email can be switched on or off. |

### Learning management
| Feature | What it does |
| --- | --- |
| Homework | Set homework for a class and subject with instructions, an attached worksheet and a YouTube link. |
| Share to WhatsApp | Send a pre-formatted summary of a homework assignment straight to WhatsApp. |
| Submissions | Students upload their work from the portal. |
| Grading | Grade each submission as a percentage with written teacher feedback; students see the badge and the feedback. |
| Status tracking | Every assignment shows as Pending, Submitted or Overdue. |

### Website
| Feature | What it does |
| --- | --- |
| 10 ready-made site templates | Complete six-page website designs: heritage-editorial, cinematic-immersive, modern-vibrant, minimalist-academic, community-warm, coastal-fresh, playful-garden, emerald-heritage, neon-frontier, sunset-international. |
| Visual builder | A drag-and-drop page builder with more than 25 block types, an inspector panel, undo/redo history, and full/tablet/mobile preview. |
| Block library | Hero, principal welcome, about, academics grid, dynamic news, events calendar, admissions block, features grid, gallery, testimonials, team directory, FAQ accordion, contact map, video embed, CTA banner, logo cloud, stat globe, marquee ticker, and more. |
| Simple and advanced modes | Simple mode for content editing; advanced mode exposes the raw block structure. |
| Versioning and publishing | Draft, preview, publish. Publishing creates a version and a live-syncs the page. |
| Navigation menus | Build and order site menus. |
| Media library | Upload and reuse images and files across pages. |
| Reusable blocks and global components | Define a block once and reuse it everywhere; changing it updates all uses. |
| SEO | Per-page titles, descriptions, keywords, social images, sitemap and robots.txt. |
| Content manager | Edit page content without touching the design. |
| Live sync blocks | News, events and admissions blocks pull real data from the system, so the website updates itself. |

### Reports and intelligence
| Feature | What it does |
| --- | --- |
| Report dashboard | Headline figures across every module. |
| Analytics explorer | Build a query interactively across 49 datasets without writing anything. |
| Report generator | Pick a dataset, filter, choose columns, preview, then save or schedule. |
| 9 data providers | Students, Academics, Attendance, Finance, Admissions, Timetable, Welfare (library/hostel/clinic), Inventory, HR, LMS, System audit — each supplying its own datasets, joins and filters. |
| 28 built-in report presets | Ready-made reports including Student Register by Class Stream, Student Headcount by Status, Academic Performance Summary, Student Marks Ledger, Attendance by Class Stream, Attendance Monthly Trend, Fee Defaulters, Overdue Invoices, Monthly Revenue Collections, Payment Receipts Ledger, Admissions Pipeline, Timetable by Teacher, Book Circulation, Overdue Books, Hostel Occupancy, Clinic Visits Monthly, Inventory Stock Status, Low Stock Items, Fixed Assets Register, Asset Maintenance Log, Purchase Orders, HR Leave Requests, Payroll Summary by Department, Employee Register, Staff Disciplinary Cases, Helpdesk Tickets, System Audit Trail. |
| Charts | Presets can include bar, line, pie, doughnut and area charts. |
| Export | CSV, Excel and PDF. |
| Saved reports | Store a configured report for reuse. |
| Report schedules | Run a report automatically on a schedule and have it generated in the background. |
| Report audit | Record who ran which report, when, and with which filters. |
| Enterprise report templates | Reusable branded report layouts. |

### Administration and billing
| Feature | What it does |
| --- | --- |
| System settings | 19 settings categories including school profile, branding, modules, banking, invoicing, security, preferences, email, notifications, feature flags and terms. |
| Manage Modules | Switch any module or individual page on or off without editing code. |
| User management | The account directory: create, approve, reject, resend activation, assign role, departments and extra permissions. |
| Custom roles | Create unlimited roles with any combination of the granular permission set. |
| Departments | Create departments, name a head, add members, and give the department its own permissions. |
| Audit and compliance | Every create, update and delete is logged with the user, the change, the IP address, the browser and the outcome. |
| Data export | Export the school's data. |
| Email configuration | Per-school outgoing mail server and from-address, with per-category switches. |
| Tenant subscription | The school sees its own plan, price, period, renewal date and status. |
| Tenant billing page | Invoices, receipts, manual payment submission and bank details. |
| Grace period and suspension | Non-payment moves a subscription through grace period to suspension, with automatic dunning notices. |

### Platform back office
| Feature | What it does |
| --- | --- |
| Schools | Every registered school, with approve, resend activation, suspend, restore, impersonate and force-delete actions. |
| Tenant health | Automated health signals per school. |
| Plans | Define subscription plans with monthly, quarterly and yearly pricing, trial days and grace days, and a feature list. |
| Subscriptions | Create a subscription for any school: plan, billing period and status. |
| Invoices and transactions | Platform-side billing ledger, receipts and manual payment submissions. |
| Pending payments | Manual submissions awaiting verification. |
| Announcements | Platform-wide notices shown to every tenant. |
| Templates | Reusable platform content templates. |
| Platform messages | Support conversations between the operator and a school. |
| Platform audit log | Everything operators did. |
| Intelligence dashboard | Cross-school metrics. |
| Backup manager | Database backup and restore. |
| Maintenance mode | Take the whole platform offline for maintenance, with a custom message. |
| Platform settings | Global branding, module release stages, security policy, automation, notifications and email branding. |
| Tenant impersonation | Sign in as any school's administrator to reproduce a problem, using a short-lived signed token. Every impersonation is recorded. |

## 1.9 Deployment and Operations
For the person hosting the system, the whole application runs as three Docker containers described by one docker-compose.yml:
| Service | Image | Role |
| --- | --- | --- |
| app | Built from the local Dockerfile — php:8.3-fpm | Runs the PHP application. Installs pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip and intl, plus Composer. Exposes port 9000. |
| webserver | nginx:alpine | Serves public requests on port 8080 and forwards PHP to app using docker/nginx/conf.d/app.conf. |
| db | mysql:8.0 | The database, persisted in the named volume dbdata. |

All three join a bridge network called laravel_network. The app container runs as the host user so bind-mounted storage/ and bootstrap/cache/ stay writable.
A second file, Dockerfile.railway, plus docker/railway/ (nginx, opcache and supervisord configuration), supports deployment to the Railway platform.
EXAMPLE: A minimal production .env needs at least APP_KEY, APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD and MAIL_*. Because the compose file overrides DB_HOST to db, you do not need to set a database host in .env for Docker deployments.
## 1.10 The Fifteen-Step Academic Setup Checklist
Almost everything in this system depends on the academic structure being configured first. The Academic Operations Center (/workspace/academic-operations-center) shows a live checklist of the 15 steps below and turns each one green as it becomes satisfied. This is the single most useful page for a new school.
| # | Step | Satisfied when |
| --- | --- | --- |
| 1 | Academic Year | An academic year exists and is marked current active. |
| 2 | Terms | The active year has at least one term. |
| 3 | Levels | At least one level exists. |
| 4 | Forms | At least one level exists. |
| 5 | Classes / Streams | At least one class exists. |
| 6 | Subjects | At least one subject exists. |
| 7 | Classrooms | At least one classroom exists. |
| 8 | Teachers | A user with a teacher role exists, or a level/class/subject has a teacher assigned. |
| 9 | Time Slots | At least one non-break time slot exists. |
| 10 | Timetable | At least one timetable lesson exists. |
| 11 | Assessment Setup | Assessment types are configured. |
| 12 | Admissions Open | At least one application exists. |
| 13 | Student Enrolment | At least one enrollment exists. |
| 14 | Attendance Ready | Attendance is configured. |
| 15 | Report Cards Ready | An active report template exists. |

NOTE: Steps 3 and 4 are tested identically, so they always light up together. This is a harmless duplication in the current release.
TIP: The ordered first-time setup sequence — the exact order to click through — is in Section 4.12 of this manual. Bookmark that page; almost every new school needs it.
## 1.11 What To Do First — The Short Version
If you have just been given the system and want to be productive this week:
1.	Read Chapter 2 if you are the person who registered the school.
2.	Open Academics → Overview and work down the 15-step checklist. The exact click-path is in Section 4.12. Budget half a day for Levels, Subjects, Classrooms and the Timetable.
3.	Create your staff accounts — Chapter 3. Give each person the narrowest role that lets them do their job.
4.	Set your fee structure and issue invoices — Chapter 7.
5.	Publish your website — Chapter 9.
6.	Issue report cards — Chapter 6.
In this phase you learned: what KairoCORE is, that it serves many isolated schools from one installation, the three separate interfaces, how the school is determined from the subdomain, the four school statuses and how a school becomes active, the technology underneath, the regional terminology, all 23 modules, the complete feature inventory, the Docker deployment, and the 15-step setup checklist that everything else depends on.

# Phase 2 — Registering a School and Going Live
This chapter is the complete story of a new school, from the moment somebody finds your website to the moment the school is running with students in it. If you are the person responsible for registering a school, this is your chapter.
## 2.1 The Full Picture
A. THE PUBLIC PART                        B. THE PLATFORM SIDE
────────────────────                      ────────────────────
1. Open /register                         5. Operator is notified
2. Step 1 — Institution & Admin                 (in-app + email)
3. Step 2 — Web address                     6. Operator opens /platform → Schools
4. Step 3 — Choose modules                  7. "Approve & Send Activation"
5. Accept terms, Submit                    8. Activation email sent
↓                                      9. (Optional) Demo data loading
School created: status = pending                ↓
Admin user created: status = pending     C. THE ADMINISTRATOR
All system roles created                 10. Clicks the activation link
Redirect to success page                 11. Sets username + password
↓
User status = active
School status = ACTIVE
Region preset applied
↓
D. COMMERCIAL
12. Operator creates the subscription
13. First invoice issued

IMPORTANT: Steps A and C are separated by a human gate. The school cannot do anything at all — not even load its own home page — until the operator approves it and the administrator activates the account.
## 2.2 Step 1 — Institution and Administrator
Open https://yourdomain.co.zw/register. The form is rate-limited to a sensible number of attempts per person per minute, so a scripted attack cannot flood it.
The first of three steps asks who you are and who will run the system.
| Field | Label on screen | Required | Rules and notes |
| --- | --- | --- | --- |
| schoolName | Registered Institution Name | yes | 3 to 150 characters. Letters, numbers, spaces and . , - ' " only. Angle brackets and braces are rejected — this prevents someone injecting code through the school name. |
| country | Country | yes | Chosen from a searchable drop-down list. Validated against the configured country list. |
| physicalAddress | Physical Address | yes | 5 to 500 characters. |
| language | System Language | yes | One of english, spanish, french, portuguese, shona, swahili. Defaults to English. |
| institutionType | Type of Institution | yes | One of primary, secondary, tertiary, both, other. |
| otherInstitutionType | Specify Other Institution Type | only if type is other | Up to 255 characters. |
| phone | Primary Phone | yes | 7 to 20 characters. Must start with an optional + and may contain digits, spaces, brackets and hyphens. |
| adminName | Administrator Full Name | yes | 3 to 100 characters. Letters, spaces and . - ' only. |
| adminEmail | Administrator Email | yes | A real, resolvable email address. Must be unique across the entire installation — not just within your school. |
| institutionEmail | Institution Email | yes | Must be a valid address and must differ from the administrator email. Used for billing, legal documents and as the public contact address. |

### Two things that surprise people
WARNING: adminEmail is globally unique. Two different schools cannot register with the same administrator email address, even though they are completely separate organisations. If you are registering a second school, use a different administrator email address — for example admin2@Tinway.test rather than admin@Tinway.test.
NOTE: The form tells the administrator that "Username and password are set upon approval." This is accurate — see Section 2.6. You do not choose a password here.
### Validation that runs as you type
Every field on step 1 is validated as you leave it, not only when you press Next. If something is wrong, a red message appears immediately under the field. This means you will not discover ten problems at once at the end.
## 2.3 Step 2 — Choosing Your Web Address
Your web address is the address parents, students and staff will type. It becomes a subdomain of the main domain.
You type:   Tinway
Full address shown:  https://Tinway.yourdomain.co.zw

| Field | Label on screen | Required | Rules and notes |
| --- | --- | --- | --- |
| subdomain | Web Address | yes | 3 to 50 characters. Letters, digits and hyphens only. Must not be one of 54 reserved names. |
| hasDummyData | Pre-load Demonstration Data | no | Off by default. See Section 2.8. |

### Live availability checking
As you type, the form checks availability continuously (300 ms debounce) and shows one of two things:
- a green Available message, or
- an amber already taken message.
Behind the scenes the form lowercases your input, silently strips anything that is not a letter, digit or hyphen, and tells you what it removed.
EXAMPLE: If you type Green Field!, the form strips the space and the exclamation mark and shows "Removed invalid characters", leaving Tinway.
### The 54 reserved names
www, mail, admin, administrator, api, app, apps, blog, cdn, cpanel, dashboard, demo, dev, docs, email, ftp, help, host, hosting, imap, info, internal, intranet, login, m, mail, manager, mx, my, new, news, ns, ns1, ns2, office, panel, pop, pop3, portal, postmaster, preview, root, sales, secure, server, service, smtp, sql, ssh, ssl, staff, staging, support, sysadmin, system, test, testing, web, webmail, whm, www1, www2.
Choosing one of these would collide with the hosting infrastructure, so the form refuses it.
TIP: Pick something short, memorable and typeable by a parent on a phone. Tinway is far better than Tinwayprimaryschool2026. If a parent mistypes it, they will not find your site.
### What happens if someone else takes it first
Two schools can obviously pick the same address. The system handles this at two levels:
1.	Live check while you type, so you normally find out immediately.
2.	A second check on submit, inside the database transaction. If somebody registers the same address in the window between your check and your submit, you get a friendly validation error instead of a database crash.
There is also a cleanup step: if a soft-deleted school is still holding the address, it is permanently removed so the unique index is free for you.
## 2.4 Step 3 — Choosing Your Modules
The third step lets you switch on only the modules you actually intend to use.
- Select All and Deselect All buttons are provided.
- Administration and Subscription & Billing are shown as Always On with a disabled, ticked checkbox. You cannot turn them off; without them the system cannot be administered or billed.
- The other 21 modules are listed as cards, each with an icon, a name and a one-line description, and a checkbox.
- Every module starts selected.
TIP: Do not deselect modules you might need in three months. Switching a module off hides its pages and its tab bar, but it does not delete any data. You can switch it back on and everything is still there. But while it is off you cannot see or use it at all, which is confusing for staff.
The full module list and its purpose is in Section 1.7.
## 2.5 Terms and Submission
At the bottom of step 3:
- A terms and conditions checkbox must be ticked. The Submit button stays disabled until it is.
- The terms open in a new tab. They are also readable without signing up, at the platform terms route.
When you press Submit:
1.	The form re-validates every step.
2.	The subdomain is checked again for availability.
3.	One database transaction opens. Everything below either all succeeds or all rolls back — you can never end up with a school and no administrator:
- A School row is created with status = 'pending', carrying the name, country, address, language, locale, institution type, phone, institution email (lowercased) and subdomain.
- For every module in the system, a SystemSetting row is written under the modules group — '1' for the modules you selected, '0' for the ones you did not. The two locked modules are forced to '1'.
- The administrator User row is created with school_id set to the new school, name from your administrator name, email lowercased, password set to a random 64-character string nobody knows, requested_role = 'administrator' and account_status = 'pending'. No activation token is issued yet.
- Every system role is created for this school. The 11 built-in roles (School Administrator, Teaching Staff, Accounts / Finance, HR, Health, Procurement, Librarian, Houseparent, Supporting Staff, Student, System Administrator) are provisioned inside the same transaction.
4.	A background job is queued.
5.	You are redirected to a success page that confirms the school name.
NOTE: Two things deliberately do not happen at this point: no subscription is created, and no demonstration data is loaded. Both wait for approval, so a school that never gets approved never consumes resources. Section 2.9 explains the subscription, which must be created by hand.
## 2.6 What the Platform Operator Does
The queued job (ProcessSchoolRegistrationJob) does exactly one thing: it notifies the platform operators. It tries three times with a 10-second backoff and a 10-minute timeout.
### Who gets notified
Every user with no school attached — that is, every platform super administrator. Each receives:
- an in-app notification, always; and
- an email, provided the platform setting notifications → email_on_school_registration is on (it is on by default).
If the platform has a separate inbox address configured (notifications → super_admin_email, falling back to the platform's own email address), that address is emailed too, unless it is already a recipient.
The email lists the institution name, its workspace URL, country, address, phone, language, institution type, and the contact person's name and email. The in-app notification carries a deep link straight to the school's record in the back office.
### Approving the school
In the back office, open Schools, click the pending school, and use the Approve & Send Activation action (available on both the list row and the edit page header).
Approval performs these steps:
1.	Finds the school's user whose requested role is administrator and whose status is pending — or failing that, any pending user at that school.
2.	Issues a single-use activation token of 64 random characters and emails the activation link. Issuing a new token overwrites any previous one, so only the newest link works. The link is valid for 48 hours (configurable).
3.	Records who approved it (approved_by) and when (approved_at).
4.	Sets trial_ends_at to three months from now.
5.	Leaves the school status as pending. It only becomes active when the administrator actually activates.
6.	Re-runs role provisioning, creating any roles that are missing and refreshing any catalogue-owned role that has not been customised by the school.
7.	If demonstration data was requested and has not been loaded yet, queues the demonstration-data job.
IMPORTANT: If the school has no administrator account, approval still succeeds but no email is sent, and the operator sees a warning telling them so. Use Resend Activation Link once the account exists. That action is on the list row only.
### The other operator actions on a school
| Action | When it is offered | What it does |
| --- | --- | --- |
| Approve & Send Activation | status is pending | As above. |
| Resend Activation Link | status is pending and a pending user exists | Issues a fresh token and re-sends the email, invalidating the old link. |
| Suspend | any | Sets status to suspended. The site returns HTTP 403 and all users are redirected to the billing page. |
| Restore | status is suspended | Sets status back to active. |
| Impersonate | always | Sign in as that school's administrator to reproduce a problem. See Section 10.10. |
| Delete | always, with a typed confirmation | Permanently deletes the school and every table that has a school_id column. This cannot be undone. |

## 2.7 Activating the Administrator Account
The administrator receives an email whose only purpose is to get them to set a username and password. The link is of the form https://yourdomain.co.zw/activate-account?token=....
### What the activation page checks
| Situation | What the user sees |
| --- | --- |
| No token in the URL | "Your activation link is incomplete." with a form to request a new link. |
| Token matches no account | "This activation link is not valid." |
| Token has expired (or was never given an expiry) | "This activation link has expired." |
| Token is valid | The activation form. |

### The activation form
| Field | Label | Required | Rules |
| --- | --- | --- | --- |
| username | Username | yes | 3 to 50 characters. Must start with a letter or a digit. May then contain letters, digits, spaces, full stops, undersCOREs and hyphens. |
| password | Password | yes | Minimum 8 characters and must contain at least one uppercase letter, at least one digit, and at least one symbol. |
| password_confirmation | Confirm Password | yes | Must match exactly. |

The rules are enforced in this order so the user is told precisely what is wrong: the password pattern is ^(?=.*[A-Z])(?=.*\d)(?=.*\W_).{8,}$.
EXAMPLE: Tinway2026! passes. Tinway2026! fails — no uppercase. Tinway! fails — no digit. Tinway2026 fails — no symbol.
### What happens on success
1.	The username and hashed password are saved.
2.	The account status becomes active.
3.	The activation token and its expiry are cleared.
4.	activated_at is stamped.
5.	Because this user is not a student and has a school:
- the school status becomes active, and trial_ends_at is set to three months from now;
- the current tenant is bound;
- the academic preset for the school's region is applied — terminology and the starter list of levels and subjects (see Section 2.10);
- if demonstration data was requested and is still missing, the demonstration-data job is queued.
6.	The user is redirected to their school's own login page: https://Tinway.yourdomain.co.zw/workspace/login, with a success message.
IMPORTANT: The administrator must manually sign in after activating. Activation does not log them in.
### If the link expired
The activation page offers Request a new link. This is throttled to 5 requests per 10 minutes. The response is deliberately identical whether or not the account exists, whether or not it is suspended, and whether or not it has been approved — so the form cannot be used to discover which email addresses are registered.
The only rule that actually blocks a re-send is that the account must have been approved: either approved_at is set, or the school status is active.
## 2.8 Demonstration Data
During sign-up you can tick Pre-load Demonstration Data. Nothing happens immediately. If the operator approves the school, a background job populates it with a realistic set of sample records so you can explore every screen safely before committing real data.
Demonstration data is loaded once. It is skipped if the school has already been seeded, and it is also loaded at activation time if approval somehow missed it. SeedSchoolDemoDataJob runs in the background with a generous timeout.
WARNING: Demonstration data creates real Student and Employee records with real enrollment and invoice rows. They are marked so they can be told apart, but they will appear in reports, in the student directory and in fee balances. Load it on a school you are happy to clear out, and clear it out before you go live.
## 2.9 The Subscription — Created by Hand
IMPORTANT: Approving a school does not create a subscription. No subscription row, no invoice and no billing record are created during sign-up or approval. The subscription must be created deliberately by the platform operator. A school can therefore be approved, activated and running with a three-month trial and no subscription record at all. This is listed in Appendix 11.6.
The operator creates one in the back office under Subscription & Billing → Overview & Billing, with these fields:
| Field | Meaning |
| --- | --- |
| Institution | The school this subscription belongs to. |
| Plan | Which plan is being bought. |
| Billing Period | monthly, quarterly or yearly. |
| Status | trialing, active, grace_period, expired or suspended. |

### The plan that ships with the system
The installation creates exactly one plan by default:
| Field | Value |
| --- | --- |
| Name | Standard Kairo CORE License |
| Slug | standard-KairoCORE-license |
| Monthly price | 10.00 USD |
| Trial days | 14 |
| Grace days | 5 |
| Active | yes |

NOTE: The plan model and the plan editing screen expose additional columns — quarterly price, yearly price, a "popular" flag and a feature payload — but the shipped migration does not create those columns. Editing them will fail until the columns are added. Listed in Appendix 11.6.
### The subscription lifecycle
trialing ──payment received──► active
│                             │
│                        (renewal date passes, unpaid)
▼                             ▼
expired ◄──trial ends unpaid── grace_period ──grace days pass──► suspended
│
payment received
▼
active

A suspended tenant is redirected to its billing page on every request, except for the billing, invoice, receipt and Livewire-update routes — so the school can still pay, but cannot use anything else.
### Automated billing and dunning
Three background jobs handle the commercial side:
| Job | What it does |
| --- | --- |
| Auto-billing | Generates invoices on the subscription's billing period when auto-billing is enabled. |
| Dunning notifications | Sends escalating reminders as the renewal date passes: before due, on due, and during the grace period. |
| Auto-deactivation | Suspends a subscription that has been unpaid for longer than auto_deactivate_after_days. |

### Payment gateways
Four gateways are implemented: Stripe, PayPal, PayNow, and Manual (bank deposit with slip upload). The gateway resolver picks the active one from the subscription's stored configuration.
EXAMPLE: A school in Zimbabwe pays by PayNow. The operator creates the subscription with period monthly and status trialing. At the end of the trial an invoice is generated automatically. The bursar either pays in the app or uploads a deposit slip; the operator verifies it under Pending Payments, which marks the invoice paid and moves the subscription to active.
## 2.10 The Academic Region Preset
On activation, AcademicPresetService loads a starter configuration matched to the school's region. This is what stops a new school from staring at an empty system.
### Zimbabwe — zimbabwe
- Terminology: Level is called a Form; a class inside a form is a Class Stream; a subdivision of the year is a Term; a sitting of examinations is CALA / Exam.
- Levels created: ECD A, ECD B, then Form 1 through Upper Six.
- Subjects created: Mathematics, English, Heritage Studies, Shona, Ndebele, Combined Science, Agriculture, French, Business Studies, Accounts, and more.
### South Africa — south_africa
- Terminology: Grade, Class, Term.
- Levels created: Grade R through Grade 12.
### United States — united_states
- Terminology: Grade, Section, Semester.
- Levels created: Kindergarten through Grade 12.
CAUTION: The operator's school form stores the United States region as us, but the preset loader matches on united_states. A US school therefore receives the terminology change but not the automatic level list. Listed in Appendix 11.6.
## 2.11 Your First Ten Minutes After Activation
You are now logged in to an empty but correctly configured system. Do this, in this order:
1.	Open Academics → Overview (/workspace/academic-operations-center). You will see the 15-step checklist with almost everything red. This page is your guide for the next few days.
2.	Open System Settings → Profile & Branding and confirm your school name, short name, ministry registration number, phone, address and currency.
3.	Create your academic year and terms — Section 4.2.
4.	Work down the setup checklist — the full click-path is in Section 4.12.
## 2.12 Worked Example — Registering Tinway Primary School
EXAMPLE: Tinway Primary School is a government primary school in Zimbabwe. The proprietor registers it on 3 January 2026.
Step 1 — Institution and Administrator
| Field | Value entered |
| --- | --- |
| Registered Institution Name | Tinway Primary School |
| Country | Zimbabwe |
| Physical Address | 42 Samora Ndlovu Street, Harare |
| System Language | english |
| Type of Institution | primary |
| Primary Phone | +263 772 123 456 |
| Administrator Full Name | Tendai Moyo |
| Administrator Email | admin@Tinway.test |
| Institution Email | office@Tinway.test |

adminEmail and institutionEmail are different, so validation passes.
Step 2 — Web Address
The proprietor types Tinway. The form strips nothing and reports Available. The preview reads https://Tinway.yourdomain.co.zw. The proprietor ticks Pre-load Demonstration Data so the bursar can explore while training.
Step 3 — Modules
The proprietor ticks Select All and leaves everything on, then unticks Boarding & Welfare because the school is a day school. Administration and Subscription & Billing stay locked on.
The terms are ticked and Submit pressed.
Immediately afterwards
- The school exists with status = pending.
- The administrator exists with account_status = pending.
- 11 system roles exist.
- 19 modules are on, Boarding & Welfare is off.
The Tinway website still refuses every request with HTTP 403.
The same afternoon — platform operator
The operator receives an in-app notification and an email, opens /platform → Schools, finds Tinway, clicks Approve & Send Activation. The operator's screen confirms the activation email was sent, and shows a warning that the trial ends on 3 April 2026. The status is still pending.
The next morning — Tendai Moyo
Tendai opens the emailed link, is shown the activation form, and enters:
| Field | Value |
| --- | --- |
| Username | tmoyo |
| Password | Tinway2026! |
| Confirm Password | Tinway2026! |

He is redirected to https://Tinway.yourdomain.co.zw/workspace/login, where he signs in for the first time. The school status is now active, the trial end date is 3 April 2026, and the Zimbabwe preset has loaded Forms 1–6, ECD A/B and the standard subject list.
## 2.13 Problems and Solutions
| Symptom | Cause | What to do |
| --- | --- | --- |
| "That web address has already been taken" | Another school holds it, or a deleted school still holds the database record. | Choose another. A soft-deleted holder is cleared automatically; a live holder is not. |
| "The administrator email has already been taken" | adminEmail is unique across the whole installation, not just your school. | Use a different address for this school. |
| "Removed invalid characters" | You typed spaces, dots or symbols. | Use letters, digits and hyphens only. |
| The web address is refused immediately | You chose one of the 54 reserved names. | Choose something else. |
| The activation link says it is invalid | A newer link was issued, so this one was overwritten. | Use the most recent email. |
| The activation link says it has expired | Links last 48 hours. | Use Request a new link on the page. |
| "Request a new link" says nothing useful | The form deliberately gives the same answer for every case. | Check your junk folder, then ask the operator to use Resend Activation Link. |
| I activated but cannot sign in | The account may still be pending if you used a link for an account that was never approved. | Ask the operator to approve the school. |
| I signed in but every menu is empty | No modules are switched on. | System Settings → Manage Modules, and switch on what you need. |
| A page is missing from the sidebar | Most pages are reached from the module tab bar at the top, not the sidebar. Or your role cannot use it. | Click the module in the sidebar and use the tabs. See Chapter 3. |
| The school site returns "awaiting approval" | Status is pending. | Complete approval and activation. |
| The school site returns "suspended" | Status is suspended. | The operator must restore it, or the school must settle its subscription. |
| Nothing appears in Reports | No data exists yet, or the modules behind it are off. | Load data, or enable the module. |

In this phase you learned: the three-step public sign-up form and every field in it, how to choose and reserve a web address, the reserved-name blocklist, how to choose modules, what is and is not created at submission, how the platform operator approves a school, how account activation works and what it validates, how to recover an expired link, demonstration data, how subscriptions are created by hand and how they expire, the payment gateways, the regional academic presets, a complete worked example, and how to diagnose the common failures.

# Phase 3 — Registering Users and Managing Access
Every person who uses KairoCORE needs an account. This chapter explains the four different ways an account gets created, what each one does to the account immediately, and how you control what that person can see and do.
## 3.1 Accounts and Identity — The Key Concepts
Read these four definitions before anything else. Almost every confusion about permissions comes from mixing them up.
| Concept | What it is | Where you manage it |
| --- | --- | --- |
| Login account (users table) | The thing a person types a username and password into. It has an email address, a status, and a language. | Everywhere. |
| Person record (employees or students table) | The real-world person as the school knows them — their staff number, their salary grade, their class, their parent. | HR & Payroll, Students |
| Role | A named bundle of permissions. | System Administration → User Management, or Custom Roles |
| Department | An organisational group (Bursary, Library, Clinic) that can add permissions on top of whatever role the person holds. | System Administration → User Management |

IMPORTANT: A login account and a person record are linked, not identical. One person has exactly one login account and exactly one person record. The link is what lets the system know that the account that just paid a school fee is the same human as the student in Grade 3B.
EXAMPLE: Mrs Rudo Chikore is hired as a mathematics teacher. - Her person record holds her employee number, her department (Academics), her grade (Senior Teacher) and her salary. - Her login account holds her email, her username, her password hash and her status. - The link between them is what lets her mark homework for Grade 3B and be automatically the class teacher of any class assigned to her.
## 3.2 Account Statuses — What They Mean
Every login account has exactly one of four statuses. The status is checked on every request, so it matters.
| Status | Meaning | Can the person sign in? |
| --- | --- | --- |
| pending | Created but not yet approved. | Only after they activate. Activation sets the status to active — see the warning below. |
| active | Fully approved and usable. | Yes. |
| rejected | An administrator refused the registration. The reason is stored and shown to the person. | No. |
| suspended | Temporarily disabled. | No. |

Two further timestamps are kept on the account and drive the approval workflow:
| Field | Meaning |
| --- | --- |
| requested_role | The role the person asked for, or the role the system inferred. One of administrator, student, teaching_staff, non_teaching_staff. |
| approved_by / approved_at | Who approved the account, and when. A re-review is possible while approved_at is null. |
| rejected_reason | Why it was refused. Shown to the person. |
| activation_token / activation_token_expires_at | The set-your-password token. |
| activated_at | When the person first set a password. |

### Which panel each status can reach
| The person is… | Staff workspace | Student portal | Platform back office |
| --- | --- | --- | --- |
| An administrator of a school | Yes, if active | No | No |
| A student | No — always refused | Yes, if active | No |
| A platform operator (no school attached) | No | No | Yes |

WARNING — a real ordering problem. When a person self-registers from the login page, the system emails them an activation link immediately, before any administrator has approved them. Following that link sets the account to active. So a self-registered person can complete activation and sign in before you press Approve registration. Their permissions in the meantime come from the defaults for the role they requested, not from anything you chose. What this means in practice: treat the approval queue as the real control, and review it promptly. If you want a hard block until you approve, create the account yourself from the user directory with a temporary password instead of letting people self-register. Listed in Appendix 11.6.
## 3.3 The Four Ways an Account Gets Created
This is the most important table in the chapter.
| # | Entry point | Who does it | Creates the person record? | Sends activation email? | Goes to the approval queue? | Active immediately? |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | The HR Employee wizard | An HR officer adds a staff member | Yes | Yes, on first save | No | Yes |
| 2 | Self-registration on the login page | The person themselves | No — verified against the existing roster | Yes, immediately | Yes | No, but activation makes it active (see warning) |
| 3 | The User Accounts directory | An administrator | No | No, unless you resend | No | Yes |
| 4 | Enrolling from an application | Admissions, or the Enrollment action | Yes, a student | Yes | No | Yes |

IMPORTANT: Entry points 1, 3 and 4 create accounts that are immediately active and skip the approval queue entirely. Only entry point 2 — a person registering themselves — actually uses the queue. Listed in Appendix 11.6.

## 3.4 Entry Point 1 — The HR Employee Wizard
Best for: all staff, because it is the only method that also creates the person record with salary, grade and department.
Go to HR & Payroll → Staff Directory & HR → Staff Directory, click Create, and work through five steps.
### Step 1 — Personal Information
| Field | Required | Notes |
| --- | --- | --- |
| First Name | yes |  |
| Last Name | yes |  |
| National ID Number | no |  |
| Gender | yes |  |
| Date of Birth | yes |  |
| Marital Status | no |  |
| Phone Number | no |  |
| Email Address | no, but strongly recommended | Without one, no login account is created and no activation email is sent. |
| Physical Address | no |  |
| Emergency Contact Name | no |  |
| Emergency Contact Phone | no |  |

TIP: If you leave the email blank the employee is still created, but they will never be able to sign in. The wizard tells you so with a warning toast when you save.
### Step 2 — Employment Details
| Field | Required | Notes |
| --- | --- | --- |
| Department | yes | Chosen from your school's departments. The department's permission set will be added to this person's access. |
| Designation / Job Title | yes |  |
| Role | yes | The system role. Chosen from the staff roles: Teaching Staff or Non-Teaching Staff. |
| Employment Type | yes | Permanent, Contract, Part-time or Volunteer. |
| Contract End Date | only if Contract |  |
| Date Joined | yes |  |

The role field carries helpful guidance: it explains that students register through the student portal, not here.
NOTE: The system infers a role if you do not force one: if the designation contains the word "teach", the account is treated as teaching staff; otherwise non-teaching staff.
### Step 3 — Grade and Salary
| Field | Required | Notes |
| --- | --- | --- |
| Salary Grade | yes | From your salary grades. The grade carries the minimum, mid and maximum salary. |
| Basic Salary | yes |  |
| Allowances | no |  |

### Step 4 — Family and Medical
Next of kin and emergency medical details. Optional but recommended.
### Step 5 — Documents
| Upload | Constraint |
| --- | --- |
| Profile photo | Forced to exactly 300 × 300 pixels. |
| Contract | PDF |
| Academic qualifications | PDF |
| Professional certificates | PDF |

### What happens when you save
1.	The employee record is created.
2.	A login account is created automatically:
- if the email already matches an existing account at your school, that existing account is linked and restored if it had been deleted;
- otherwise a new account is created with a random 64-character password and status pending;
- the username and display name are set from the employee's full name.
3.	The employee record's user_id is back-linked to the account.
4.	If this is the first time the account exists, an activation email is sent. If the account already existed and already has a token or has been activated, nothing is sent — so re-saving an employee never sends a surprise email.
You see a "Portal account ready" success toast.
### Bulk activation
Select several employees in the list and choose Send Activation Email. The system provisions any missing accounts, skips anyone already activated, and re-issues a token for everyone else. This is the safe way to onboard a whole department at once.
## 3.5 Entry Point 2 — Self-Registration on the Login Page
Best for: existing staff and students who already have a person record and want to claim their own login.
Go to https://Tinway.yourdomain.co.zw/workspace/login. Below the sign-in form there is a self-registration form.
| Field | Required | Rules |
| --- | --- | --- |
| Full Name | only for administrator |  |
| Student Registration No. | for student | Must match the student's ID number or admission number. |
| Staff ID | for teaching_staff and non_teaching_staff | Must match the employee number on the staff roster. |
| Email Address | yes | Must be a valid address. |
| Phone | no |  |
| Role | yes | One of Administrator, Student, Teaching Staff, Non-Teaching Staff. |
| Agree to terms | yes | Must be ticked. |

### How the roster check works
This is the security feature that makes self-registration safe. You cannot invent yourself an account:
1.	The identifier you type is looked up in your school's own roster.
- For a student: student_id_number or admission_number.
- For staff: the employee number in employees.
2.	If nothing matches, registration is refused.
3.	If a record matches, the email address you typed must match the email address on that record exactly. If the record has no email on file you get a different, explicit message telling you so.
4.	Name and username are taken from the roster record, not from what you typed. For an administrator, the email address becomes the username.
So the system is verifying "the person who knows admission number 2601-0042 and whose email is on file is the same person". A stranger cannot guess this.
### Duplicate handling
Before creating anything, the system checks whether an account already exists:
- linked to that roster record — you are told "You already have an account";
- or matching that email at your school — same message;
- or matching that email but soft-deleted — the deleted account is restored and reset to pending rather than failing on the unique index.
### What happens on submit
1.	The account is created with password set to a random 64-character string, account_status = 'pending' and requested_role set to what you chose.
2.	The roster record's user_id is linked.
3.	An activation email is sent immediately — before approval.
4.	Approvers are notified.
### Approvers — who gets notified
The system finds the administrators at your school who hold the users.approve capability, filters them by the departments they belong to, and sends each one:
- an in-app notification, always; and
- an email, if notifications → email_on_user_registration is on.
In practice this means school administrators, because users.approve and users.reject live in the administration permission namespace, and only the full-access System Administrator role holds that namespace.
## 3.6 Entry Point 3 — The User Accounts Directory
Best for: creating a login for someone who has no person record at all — for example a consultant, a temporary examiner, or an accounts clerk who is not on the staff roster.
Go to System Administration → User Management → Overview (the user directory). It is hidden from the sidebar but linked from that hub.
The list shows a warning badge with the number of accounts awaiting approval, so you always know there is a queue to work through. It opens filtered to pending by default.
### The form
| Field | Notes |
| --- | --- |
| Name | Up to 255 characters. |
| Email Address |  |
| Phone Number |  |
| Assigned Role | A custom role at your school. |
| Temporary Password | Labelled Temporary Password when creating and Reset Password when editing. Uses the system's default password policy. Only applied if you type something. |
| Account Status | Shown but disabled and never saved from this form. |
| Requested Role | One of the staff roles. |
| Departments | Many-to-many. The department's permissions are added on top of the role. |
| Extra Permissions For This Account | Organised into tabs, one per permission area. These are additional to what the role already grants. |
| Rejection Reason | Shown but disabled. Set only by the reject action. |
| Conflict panel | Appears only if an account already exists with the same email. You choose Merge (keep the existing account and add to it) or Replace (permanently delete the existing one). |

Under the permission tabs the form shows a plain-language summary of what the role and departments already cover, so you only tick genuine additions.
### Table columns
Name, Email, Requested Role, Assigned Role, Status badge, Registered date, Approved date.
### What happens when you save
The account is created and is immediately active — the approval queue is not used and approvers are not notified. Use this method when you have already decided the person should have access.
## 3.7 Entry Point 4 — Enrolling From an Application
Best for: students. This is described fully in Chapter 5; the short version:
When you enrol a student — either from an application or by creating the student directly — the system:
1.	looks for an account matching the student's own email address (never the parent's email);
2.	restores a soft-deleted match, or creates a new pending student account with a random password;
3.	links it to the student record;
4.	sends the activation email so the student can set a username and password and reach the student portal.
The parent is emailed separately with the admission confirmation, and that email also contains a set-your-password link.
IMPORTANT: The activation link is single-use and issuing a new one invalidates the previous one. If the admission confirmation email issues a fresh token, it replaces the one from the provisioning step. Tell parents to use the most recent email.
## 3.8 The Approval Workflow
Use this when accounts arrive through self-registration. Open System Administration → User Management → Overview, which is pre-filtered to pending, and click the account.
At the top of the account you will find four actions.
### Approve registration
| Field | Notes |
| --- | --- |
| Role to assign | Pre-filled with the default role for the requested role, but you can choose anything. |
| Department | Only shown when the requested role is Non-Teaching Staff. |
| Extra Permissions | A searchable checklist of every granular permission in the system. Only tick genuine additions. |
| Inherited permissions | A read-only count of what the role and departments already grant. |

The button label changes to Update role & re-activate once the account has been approved before, so you can revise a decision.
On approval the system:
- assigns the role (creating the role first if it does not exist yet);
- syncs departments, restricted to your own school;
- stores only the approver's additions in the account's permissions field, so inherited permissions are never duplicated;
- sets account_status = active;
- clears any previous rejection reason;
- stamps approved_by and approved_at.
### Reject registration
Requires the users.reject capability and is blocked once the account is active. You must give a reason. The account becomes rejected, approved_at is cleared so it can be reviewed again, and the reason is shown to the person.
### Resend activation link
Visible while the account is pending. Issues a fresh token and re-sends the email, invalidating any earlier link. You get a success or danger toast confirming it.
### Delete
Requires administration.manage_users. Removes the account.
## 3.9 The Eleven System Roles
When a school registers, these eleven roles are created automatically. They are catalogue roles: the system owns them, and re-provisioning refreshes any that the school has not deliberately customised. A role the school has customised is marked CUSTOMISED and is then left alone forever.
| Role key | Name | What it grants |
| --- | --- | --- |
| administrator | System Administrator | Everything. Its permission list is the literal wildcard *. This role is never customisable — the system always resets it to full access. |
| school_administrator | School Administrator | The modules Communication, Inventory, Library, Admissions, Students, Academics and Website. Broad day-to-day administration without full system access. |
| teaching_staff | Teaching Staff | The Academics module, plus the Exams groups Assessment Center, Grading & Marks Management and Reports & Academic Publishing — but excluding exams.publish_to_student_portal. A teacher can prepare a report card but cannot publish it. |
| accounts_finance | Accounts / Finance | The Finance module. |
| hr | HR | The HR module. |
| health | Health | The Health module. |
| procurement | Procurement | The Inventory module. |
| librarian | Librarian | The Library module. |
| houseparent | Houseparent | The Boarding module. |
| supporting_staff | Supporting Staff | No module permissions. Self-service only. Also the fallback: any unrecognised role key is treated as this. |
| student | Student | Only student_portal.access. Excluded from the staff role lists. |

### Self-service permissions every role gets
On top of its module permissions, every role — including Supporting Staff — receives a common personal set:
- view, create and clear their own tasks;
- contact the platform;
- manage their own personal details;
- view their own day and schedule;
- request their own leave;
- see notices, use chat, and raise a helpdesk ticket.
### Your effective permissions
A person's actual permissions are the union of three sets. Permissions are only ever added, never subtracted:
effective permissions  =  role permissions
+  personal permissions  (ticked by an approver)
+  department permissions

Four rules govern how a permission check resolves:
| Rule | Effect |
| --- | --- |
| Wildcard | The literal key * grants everything. Only System Administrator has it. |
| Exact key | finance.invoices.view grants exactly that. |
| Module prefix | finance grants every finance.* key. |
| View implies view | module.view_module grants every view action in that module. |

NOTE: If you cannot find a permission key in the checklist, it does not exist. The system silently discards any permission that is not in its catalogue, so a typo cannot accidentally grant access to nothing.
## 3.10 Departments — Adding Permissions by Organisation
A department is an organisational group with its own permission set. Everyone in the department gets those permissions in addition to their role. This is how you say "everyone in the Bursary can also see the Finance module" without changing anybody's role.
The system ships with 15 department presets, created for each school:
| Department | Abbreviation | Focus |
| --- | --- | --- |
| Finance & Bursary | FIN | Invoicing, payments, ledgers, financial statements |
| Clinic & Health | CLN | Clinic visits, medical records, prescriptions, alerts |
| Inventory & Assets | INV | Stock, procurement, suppliers, fixed assets |
| Library & Media | LIB | Catalogue, circulation, e-resources |
| Boarding & Welfare | BRD | Hostels, allocation, out-passes, welfare |
| Academics & Curriculum | ACA | Levels, subjects, classrooms, academic years |
| Examinations & Assessment | EXA | Assessment types, grading scales, marks |
| ICT & Systems | ICT | System settings, user management, backups |
| Human Resources | HR | Employee records, payroll, leave |
| Communications & PR | COM | Announcements, events, media, website content |
| Admissions & Enrolment | ADM | Applications, review, enrolment |
| Transport & Estates | EST | Fixed assets, campus, facilities |
| Sports & Co-curricular | SPT | Activities and events |
| Student Welfare & Guidance | WEL | Welfare cases, boarding care |

### Managing departments
Go to System Administration → User Management, then Departments.
| Field | Notes |
| --- | --- |
| Name |  |
| Description |  |
| Head | Any employee. The department head. |
| Members | Any users, many-to-many. |
| Permissions | The department's own permission list. |

When a new department preset is added in a later software release, existing schools receive it automatically, and newly added permission keys are merged into departments that already exist — without removing anything a school had set.
## 3.11 Custom Roles
When none of the eleven built-in roles fits, create your own. Go to System Administration → User Management → Custom Roles.
A custom role has:
| Field | Notes |
| --- | --- |
| Name |  |
| Description |  |
| Permissions | Any combination from the full granular permission set, organised by area. |

Roles created from the catalogue are marked as system roles and are refreshed on re-provisioning unless the school customised them. Roles you create yourself are never touched by the system.
EXAMPLE: You want a Head of Department for mathematics. Create a custom role, tick the Academics module, the Exams marking and analytics permissions, and the Students view permission — but leave out Finance, HR and Administration. Assign it to the four maths teachers. They can now do everything a subject teacher does, plus the department-level reports, and nothing else.
## 3.12 The Granular Permission Reference
Permissions are namespaced by module. This is the complete list of areas and what each contains.
| Namespace | Actions |
| --- | --- |
| student_portal | Access the student portal |
| academics | View and manage levels, subjects, classrooms, academic years, streams |
| academic_ops | The Operations Center and the academic workflow |
| exams | Assessment types, question bank, marks entry, grading, publishing, analytics |
| attendance | Take and report attendance |
| finance | Fee structures, invoices, payments, expenses, ledgers, statements |
| boarding | Hostels, allocation, out-passes, welfare |
| clinic | Clinic visits, medical records, prescriptions |
| inventory | Items, suppliers, procurement, assets |
| library | Catalogue, circulation, e-resources |
| communication | Announcements, events, chat, polls, helpdesk |
| admissions | view_module, manage_applications, approve_applications, export, receive_notifications |
| lms | Homework and submissions |
| knowledge | Knowledge hub and galleries |
| website | Templates, pages, media, SEO |
| reports | Dashboards, the explorer, the generator, schedules |
| hr | Employees, payroll, leave, disciplinary |
| users | approve, reject |
| tasks | Personal task management |
| administration | view_module, manage_users, manage_settings, manage_security, manage_branding, manage_email_config, clear_caches, manage_personal_details |
| saas | The school's own subscription and billing |
| digital_assessment | Online assessments and attempts |

IMPORTANT: users.approve and users.reject sit in the administration namespace. Only the System Administrator role holds that namespace. In practice, only a school administrator can approve or reject registrations, even if you have created a custom role called "Head Teacher" with every other permission.
## 3.13 Why a Page Is Missing — A Diagnostic Checklist
Work down this list in order. It answers almost every "I can't see the menu" case.
1.	Is the module switched on? System Settings → Manage Modules. Every module has an on/off switch, and many also have per-page switches underneath. A disabled page is hidden from everyone including the administrator.
2.	Are you in the right place? Most pages are not in the sidebar at all. Click the module in the sidebar and use the module tab bar at the top of the page. Academic Years, Subjects, Classrooms and Level are all reached this way.
3.	Is your role right? Compare your role against the table in Section 3.9. Remember permissions are only added, so start from your role and see what is missing.
4.	Do your departments add it? Check User Management → Departments for the departments you belong to.
5.	Are you personally granted it? Check User Management → Overview, open your own account, and look at the permission tabs.
6.	Does your page require a specific capability? Some pages need a page-level capability such as academics.courses. Your role must contain it.
7.	Are you an administrator? If you are the System Administrator you bypass all module-visibility checks — so if a page is still missing for you, it is almost certainly switched off in Manage Modules.
TIP: When you hover over a page name in the module tab bar, the system shows a one-line description of what that page is for. If a tab is present but clicking it gives you a "not authorised" message, that is your role, not your navigation.
## 3.14 Worked Examples
### Example A — Hiring a mathematics teacher
1.	HR & Payroll → Staff Directory & HR → Staff Directory → Create.
2.	Step 1: First Name Rudo, Last Name Chikore, Gender Female, Date of Birth 1990-04-12, Email rudo@Tinway.test, Phone +263 772 555 001.
3.	Step 2: Department Academics & Curriculum, Designation Mathematics Teacher, Role Teaching Staff, Employment Type Permanent, Date Joined 2026-01-15.
4.	Step 3: Salary Grade Senior Teacher, Basic Salary entered.
5.	Step 4 & 5: Next of kin entered; qualification PDF uploaded.
6.	Save.
7.	The employee record is created, a login account is created with username Rudo Chikore, the account is linked to the employee, and an activation email is sent to rudo@Tinway.test.
8.	She clicks the link, sets username rchikore and a strong password, and can sign in.
9.	She can now see Academics and the Exams assessment and marking screens, but not Reports publishing to the student portal — her role deliberately excludes it.
10.	Assign her as the Form Teacher on Grade 3 and as the subject specialist for Mathematics on Grade 3. See Section 4.6.
### Example B — Adding a bursar who is not on the staff roster
A bookkeeper is hired but the school does not keep a staff record for her.
1.	System Administration → User Management → Overview → Create.
2.	Name Tendai Ncube, Email tncube@Tinway.test, Assigned Role Accounts / Finance, Temporary Password left blank so she sets her own.
3.	Save.
4.	The account is immediately active. No approval is needed and nobody is notified — you have already made the decision.
5.	Send her the Resend activation link action from her account page so she can set a password.
6.	She can now see the whole Finance module and nothing else.
TIP: If you want the approval step even for accounts you create, add her to the Finance & Bursary department instead and assign Supporting Staff as the role — then use the approval action on her record.
### Example C — A student claims their portal account
Kudzai's admission number is 2601-0042 and the email on his student record is kudzai@student.test.
1.	He opens https://Tinway.yourdomain.co.zw/workspace/login.
2.	He fills in Student Registration No. 2601-0042, Email kudzai@student.test, Role Student, and accepts the terms.
3.	The system finds his student record by admission number, confirms the email matches, and takes his username and name from the record.
4.	An account is created as pending and an activation email is sent. School administrators are notified.
5.	An administrator opens User Management → Overview, finds the pending account, clicks Approve registration, leaves the role as Student, and presses Approve.
6.	Kudzai follows the activation link, sets his username and password, signs in at /student, and sees his timetable, homework and fees.
### Example D — A stranger tries to register themselves
Someone who is not on the roster visits the login page and types Student Registration No. 2601-9999 with an email they control.
The lookup fails — no student has that admission number — so registration is refused. They cannot invent an account, because the identifier must match a real record and the email must match the email on that record.
In this phase you learned: the difference between a login account, a person record, a role and a department; the four account statuses and what each permits; the four ways an account gets created and how they differ; the five-step employee wizard; how self-registration is verified against the school roster; the user directory and its conflict handling; the approve, reject and resend actions; the eleven system roles and exactly what each grants; how effective permissions are computed; the fifteen department presets; custom roles; the complete granular permission reference; a seven-step diagnostic for a missing page; and four worked examples.

# Phase 4 — The Academics Module
The Academics module is the structural heart of KairoCORE. Almost nothing else in the system can be used until it is configured, because everything else — fees, timetables, marks, reports, promotion — refers back to an academic year, a level, a class and a subject defined here.
## 4.1 How This Module Is Organised
The Academics module is divided into four categories, shown as tabs under the module title at the top of every page:
| Category | Pages in it |
| --- | --- |
| Overview | Academic Operations Center |
| Setup & Structure | Level, Subjects, Classrooms, Academic Years, Time Slots |
| Timetables & Teaching | Timetables, View Timetable, Teacher Assignments, Time Slots |
| Progression | Promotion Runs, Screening Runs |

Clicking a category tab opens a short hub page that immediately forwards you to the last page you used in that category, or to its first page if you have never used it. The brief "Opening category…" flash is normal and harmless.
TIP: The order in which you must configure this module is not the order the tabs appear in. The correct order is in Section 4.12. Follow that and the whole thing takes an afternoon.

## 4.2 Academic Years — Start Here
Go to: Academics → Setup & Structure → Academic Years Address: /workspace/academic-years
An academic year is one school year — "2026". Every enrollment, invoice, timetable lesson and mark belongs to exactly one academic year. This is the first thing you must create.
### The form
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example 2026 or Year 2026. 3 characters minimum. |
| Start Date | yes | For example 2026-01-15. |
| End Date | yes | For example 2026-12-31. |
| Current Active | no | The single most important tick box in this module. See below. |
| Stage | no | Draft, In Progress, Active, Completed or Archived. A label, not an enforcement — the real control is Current Active. |
| Fully Configured On | no | A date/time stamp for your own records. |
| Terms | see below | Up to three terms, created inside this form. |

### You may create as many years as you like — but only one can be active
IMPORTANT: You can register many academic years. You can keep last year's, this year's and next year's all in the system at once, so historical data stays readable. But exactly one year may be marked *Current Active* at a time. The active year is the one the system uses by default everywhere: it is the year new enrollments go into, the year new timetables are built for, the year report cards default to, and the year the fees engine bills against.
When you tick Current Active on one year, the system silently unticks it on every other year for your school. You do not have to unset the old one first, and you will not get a warning that you have changed something else.
EXAMPLE: Tinway has 2025 (last year, still needed for old report cards) and 2026 (this year). Both exist in the list. Only 2026 has Current Active ticked. When 2027 is created and activated, 2026 loses the tick automatically, and 2025 and 2026 remain readable.
### How to change the active year
1.	Go to Academic Years.
2.	Find the year you want to make current. Use the search box if there are many.
3.	Click Edit.
4.	Tick Current Active.
5.	Save.
Check the Current Active column in the list — a tick appears against exactly one row. Do this check after saving; it is the fastest way to be certain.
WARNING: Changing the active year is a significant action. Fees may be generated against it, new enrollments land in it, and report cards default to it. Do it deliberately, at the start of a year, and tell the bursar.
### Terms — created inside the year form
Terms are not a separate page. They are rows in a repeating group at the bottom of the academic year form, with three blank rows supplied by default.
Each term row has:
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example Term 1, Semester 1. |
| Start Date | yes |  |
| End Date | yes | Must be on or after the start date. |
| Active Term | no | Marks this as the current term for the year. Only one per year. |

The same uniqueness rule applies: ticking Active Term on one term deactivates the others in that year.
IMPORTANT: Changing the active term carries unpaid student balances forward to the new term. When the active term changes, the system takes the previous term's outstanding balance and rolls it onto the new term so the student is not suddenly in the clear. Warn your bursar before you change the term, because it will move money on their statements.
### The list
| Column | Shows |
| --- | --- |
| Name | The year, with the date range underneath. |
| Start Date / End Date |  |
| Current Active | A tick against the one active year. |
| Stage | A coloured badge: Draft, In Progress, Active, Completed, Archived. |
| Terms | How many terms the year has. |

Filters: by Stage, and a yes/no filter on Current Active.
Row actions: Edit, Workflow (jumps to the Academic Operations Center so you can see what is still missing for this year), and Delete.
### Rules that protect your data
| Attempt | What happens | Why |
| --- | --- | --- |
| Delete the active year | Refused: "Cannot archive the active academic year. Activate another year first." | Everything would lose its default year. |
| Delete a year that has terms | Refused: "Cannot delete an academic year that has terms. Archive the year instead." | The terms would be orphaned. |
| Delete a year with data | You will meet further validation refusals. | Historical records must stay readable. |

CAUTION: The refusal messages tell you to "archive the year instead", but in the current release there is no Archive action wired to the row — only Edit, Workflow and Delete. To retire a year, change its Stage to Archived and untick Current Active. Listed in Appendix 11.6.
### Bulk creating years with a spreadsheet
The list page has a CSV import. The columns are:
| Column | Required |
| --- | --- |
| Year Name | yes |
| Start Date | yes |
| End Date | yes |
| Term 1 Start Date / Term 1 End Date | both needed to create Term 1 |
| Term 2 Start Date / Term 2 End Date | both needed to create Term 2 |
| Term 3 Start Date / Term 3 End Date | both needed to create Term 3 |
| Is Active | no |

Dates must be written as YYYY-MM-DD. Import matches on the year name within your school, so importing the same file twice updates rather than duplicates. A term is only created when both of its dates are supplied.

## 4.3 Level — Defining Your Year Groups
Go to: Academics → Setup & Structure → Level Address: /workspace/courses
A level is a year group: Form 1, Grade 5, Upper Six. In the database the table is called courses, which is why the system sometimes calls these "Forms".
NOTE — naming varies by country. The tab label and the field labels follow your region preset. In Zimbabwe a level is a Form and a class inside it is a Class Stream. In South Africa they are Grade and Class. In the United States, Grade and Section. Nothing else changes.
### The form
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example Form 1 or Grade 5. |
| Code | yes | Short unique code, for example F1 or G10. |
| Form Teacher | no | The teacher responsible for the whole level. Searchable, with avatars. |
| Setup Stage | no | Pending, In Progress or Complete. Defaults to Pending. Colour-coded badge in the list. Use it to track which levels are ready. |
| Completed On | no | Date/time stamp. |
| Classes (Streams) | see below | The classes inside this level. |

### Creating classes — this is the only place you can
IMPORTANT: There is no separate "Classes" page. Classes (streams, sections) are created inside their level, in a repeating group at the bottom of the level form. Each row creates one class.
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example A, B, Red, Blue. |
| Code | no | For example F1A, F1R. |
| Capacity | yes | Maximum number of students. Defaults to 40. Minimum 1. |
| Classroom | no | Pins this class to one physical room. Help text: "Lock this class to one room. No other class will use it during generation." |
| Class Teacher | no | The teacher responsible for the day-to-day running of this class. Help text confirms one teacher may lead more than one class. |

EXAMPLE: Create level Grade 3 with code G3, then add three class rows: 3A / G3A / capacity 40 / classroom Room 12 / class teacher Rudo Chikore; 3B / G3B / capacity 40 / classroom Room 13; and 3C / G3C / capacity 30 with no pinned room so the timetable generator can use any free room.
### The list
| Column | Shows |
| --- | --- |
| Form / Level | The name. |
| Code | A grey badge. |
| Teacher | The form teacher. |
| Streams | How many classes the level has. |
| Stage | A colour-coded badge. |

Filters: by Teacher, and a very useful "Unassigned Teachers Only" filter that shows you exactly which levels still have no form teacher.
Bulk actions:
| Action | What it does |
| --- | --- |
| Assign Teacher | Sets the same form teacher on every selected level at once. |
| Mark Complete | Sets the Setup Stage to Complete on every selected level. |
| Delete | Deletes the selected levels, after validating each one. |

### Rules that protect your data
A level with enrolled students cannot be deleted. The message is "Cannot delete a form with N enrolled students. Transfer students first." You will also be told how many timetable lessons reference the level.
CAUTION: Three fields exist on the level record that are not shown in the form: a sequence order, a "next level" pointer, and a "terminal level" flag. They drive automatic promotion and screening. Because you cannot set them in the UI, they must be populated by an administrator directly in the database. Without the terminal-level flag, a screening run finds no candidates at all. Listed in Appendix 11.6 and in Section 4.10.
### Bulk creating levels with a spreadsheet
| Column | Required |
| --- | --- |
| Course Name | yes |
| Course Code | yes |
| Class / Stream | no |
| Teacher Name | no |
| Status | no — pending, in_progress or complete; defaults to pending |

Note that the export produces one row per class when a level has classes, so an export-then-edit-then-import round trip preserves your class structure.

## 4.4 Subjects
Go to: Academics → Setup & Structure → Subjects Address: /workspace/subjects
### The form
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | The full subject name exactly as it should appear on report cards. |
| Code | yes | Short unique code, for example MATH. Help text: "Full subject name as it appears on reports." |
| Type | yes | Theory, Practical, or Both (Theory & Practical). This matters for grading — see Chapter 6. |
| Credit Weight | yes | A decimal, step 0.01. Defaults to 1.00. Used when averaging results. |
| Elective | no | Tick when students choose this subject rather than all taking it. |
| Setup Stage | no | Pending Setup, In Progress, Complete. |

### The list
| Column | Shows |
| --- | --- |
| Name |  |
| Code | A green badge. |
| Type | A coloured badge — Theory, Practical, or Both. |
| Credits | The credit weight. |
| Elective | A tick when it is elective. |
| Stage |  |

Filter: by Type.
### Protection
A subject used in any timetable lesson cannot be deleted: "Cannot delete a subject that is used in timetable entries. Remove from timetable first."
### Bulk creating subjects
| Column | Required |
| --- | --- |
| Subject Name | yes |
| Subject Code | yes |
| Type | yes |
| Credit Weight | no |
| Is Elective | no |
| Status | no |

TIP: Enter the subject name exactly as it must appear on the report card. This value is copied onto report cards and into the marks ledger, so changing it later does not rewrite historical reports.

## 4.5 Classrooms
Go to: Academics → Setup & Structure → Classrooms Address: /workspace/classrooms
### The form
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example Room 12 or Science Lab A. |
| Capacity | no | How many students it seats. |
| Building / Location | no | For example Main Block, East Wing. |
| Description | no | Up to 500 characters. |
| Room Features | no | Any combination of: Projector, Whiteboard, Computer Station, Science Lab Equipment, WiFi Access, Air Conditioning. |

### The list
| Column | Shows |
| --- | --- |
| Name | With the location underneath. |
| Building |  |
| Capacity |  |
| Usage | A live count of how many timetable lessons use this room. |

Filter: "Has Capacity > 0", so you can find the rooms that are actually usable.
Row actions: Edit, View (shown as Usage), and Delete (validated).
TIP: Classrooms are not decoration. When you build a timetable, the system checks that a room is not double-booked. Rooms you have created but not given a capacity still work; they simply cannot be checked for overcrowding. CAUTION: Classroom capacity is not enforced when a student is placed in a class. The automatic class balancer picks the least-populated class alphabetically and does not consider capacity. If you rely on auto-assignment during enrolment, check capacities yourself afterwards. Listed in Appendix 11.6.
### Bulk creating classrooms
| Column | Required |
| --- | --- |
| Classroom Name | yes |
| Capacity | no |
| Location | no |
| Description | no |
| Features | no |


## 4.6 Time Slots — The Periods of the School Day
Go to: Academics → Timetables & Teaching → Time Slots Address: /workspace/time-slots
A time slot is one period, break or lunch in the school day.
| Field | Notes |
| --- | --- |
| Name | Required. For example Period 1, Tea Break, Lunch. |
| Slot Type | teaching, break or lunch. |
| Start Time / End Time | The duration in minutes is derived from these. |
| Is Break | Kept automatically in step with the slot type. |
| Locked | Slots marked locked survive timetable regeneration. |
| Colour | Used in the timetable grid. |
| Period Order | The position of the slot in the day. |

Time slots belong to a timetable template.
TIP: You rarely type time slots by hand. The timetable builder generates them for you from your school hours — see Section 4.7. Come to this page only if you maintain periods manually.

## 4.7 Timetables and Teaching
Go to: Academics → Timetables & Teaching Addresses: /workspace/visual-timetable-builder, /workspace/timetable-viewer-page, /workspace/teacher-assignments
### Step 1 — Compile the period blocks
The Timetables page opens on Auto-Generate School Period Blocks. You describe your school day and the system turns it into time slots.
| Setting | Notes |
| --- | --- |
| Lessons start / Lessons end | The boundaries of the teaching day. |
| Period Length | How many minutes each lesson lasts. |
| Tea Break | Either fixed at a time or flexible after Period N. |
| Lunch | Either fixed at a time or flexible after Period N. |

Press Compile & Generate Time Slots. The system creates the slot rows and marks any now-stale slots for removal.
IMPORTANT: Until a template is active, the page shows "Active Schedule Template Not Selected" and the lesson buttons are disabled. This is the most common cause of "I cannot add a lesson".
Two other buttons here:
- Delete Selected Template — removes the whole template and its lessons.
- Auto-Generate All Lessons — see step 3.
Once a template is active, the page header summarises Lessons Duration · Period Length · Tea Break · Lunch.
### Step 2 — Choose the scope and the class
- Class View | Stream View toggle.
- Class View works on one specific class — Grade 3A.
- Stream View produces one merged schedule for every class in a level — described in the interface as "One merged schedule for every class stream in this form." Choose this if all your Grade 3 classes follow the same timetable.
- A search box finds the class by class name or level name. It returns the top five matches, and the picker then shows the class teacher's name so you can be sure you picked the right one.
### Step 3 — Place lessons
You need an active template, an academic year and a term before either method works.
A. Automatically — recommended
Press Auto-Generate All Lessons. Before you do, you can optionally set a Subject Pattern: a repeating group where you choose a level, a subject, and how many double and triple lessons per week that subject should have. Consecutive periods are packed as double or triple blocks, and the chosen numbers are written back onto the subject assignments so they persist.
You get a plain-language result, for example:
Successfully placed 214 lesson(s) automatically with zero clashes!
followed by counts of anything that could not be placed, or — if nothing could be placed at all — a bulleted list of the reasons. Read that list; it tells you exactly what is missing.
One option, replace unlocked, controls regeneration: locked lessons always survive, unlocked ones are rebuilt. Use locking to protect lessons you placed by hand.
B. Manually
- Click an empty cell → "+ Add Lesson".
- Click an existing lesson → the "Edit Scheduled Lesson Details" dialog with Subject, Teacher and Classroom, or the bin icon to remove it.
### Conflict protection — the system will not let you create a clash
Before saving a lesson the system checks, in order:
| Check | Message |
| --- | --- |
| Is this teacher already teaching another class in this period? | "Scheduling Conflict Blocked — Teacher is already scheduled to teach [X] in class [Y] at this period!" |
| Is this classroom already in use in this period? | "Classroom Double-Booking Blocked" |

The database enforces the same three rules independently with unique indexes, so even a direct database write cannot create a double-booking:
1.	one lesson per class per time slot,
2.	one lesson per teacher per time slot,
3.	one lesson per classroom per time slot.
### Helpful warnings on the page
| Warning | Meaning |
| --- | --- |
| "Gaps Detected: Please register at least one Form Level and Class Stream inside your portal before scheduling lessons." | You have no levels or no classes yet. Go to Level first. |
| "Time slots are not configured yet for this template." | Press Compile & Generate Time Slots first. |

### View Timetable — the read-only twin
The View Timetable page shows the same grid without editing:
- an Active Template selector and a Make Active button, which deactivates all other templates and confirms "Template set as the active school timetable."
- the same School Hours / Period Length / Tea Break / Lunch summary;
- the same Class View / Stream View toggle and class search;
- Print Stream and Print Class;
- colour-coded subjects: mathematics indigo; science, physics, chemistry and biology emerald; English, Shona and Ndebele sky; accounts, business and economics amber; everything else grey;
- teacher names rendered as initials, so a narrow cell stays readable.
TIP: Build timetables under one template, switch to the next with Make Active when the new year starts, and keep the old one. You then have last year's timetable for reference without losing the new one.
### Teacher Assignments
Address: /workspace/teacher-assignments
This screen assigns teachers to subjects. It writes to the subject-assignment record, which is a link between a teacher, a subject, a level and optionally a specific class.
Two entry points:
| Entry point | What it assigns |
| --- | --- |
| Assign Subject Specialist | One teacher to one subject, across a whole level or specific classes. |
| Assign Teacher to a Class | One teacher to every subject in a class. Use this for a form teacher who teaches everything. |

The multi-class assignment page lets you pick one teacher and several subjects at once, scoped either to a whole level or to explicitly chosen classes.
Each assignment records:
| Field | Notes |
| --- | --- |
| Role | main, assistant or substitute. |
| Periods Per Week | Defaults to 4. |
| Room Preference | Optional. |
| Double / Triple Periods Per Week | Feeds the timetable generator's packing. |

### The scope rule — this is the important part
| You set the class to… | The meaning |
| --- | --- |
| (blank) | This teacher teaches every class in that level for that subject. |
| A specific class | This teacher teaches only that class for that subject. |

Both can exist at the same time for the same subject, so you can have a school-wide specialist and a dedicated teacher for one stream.
EXAMPLE: Rudo Chikore teaches Mathematics. Leave the class blank on Grade 3 / Mathematics and she teaches 3A, 3B and 3C. Then add a second assignment for Grade 3 / Mathematics / 3C assigned to Tino Marange, and 3C alone now has its own maths teacher while Rudo covers 3A and 3B.

## 4.8 Academic Operations Center
Go to: Academics → Overview Address: /workspace/academic-operations-center
This is the most useful page in the module. It shows:
- Academic Readiness — a visual summary of how configured you are.
- Key figures relevant to academics.
- Validation issues — real problems, each with a route to fix it.
- Suggested actions — what to do next.
- Quick Actions — buttons to the most common jobs.
- Recent Activity.
- A live workflow timeline of all 15 setup steps.
Each step turns green automatically as its condition is satisfied. The 15 steps and their tests are in Section 1.10.
### Marking a step complete by hand
If you hold the academic_ops.manage_workflow permission, you can override any step and mark it complete or skipped. The override is stored and always wins over automatic detection.
Every change is recorded in the workflow history with the old and new state, the reason, the user, the browser and the IP address.
TIP: Use manual completion sparingly. It is genuinely useful when a step's automatic test does not match your situation — for example if you genuinely do not need classrooms because you are a small primary school sharing one room. It is not useful for skipping steps you have genuinely not done, because the rest of the module will not work.

## 4.9 Setup & Structure — The Complete Workflow
This is the summary you asked for. It is the correct order for the four pages in the Setup & Structure group.
1. ACADEMIC YEARS                        /workspace/academic-years
Create the year, set its dates,
fill in the term rows, tick
"Current Active" on the year and
"Active Term" on the current term.
│
▼
2. LEVEL                                  /workspace/courses
One row per year group. Give each a
unique code. In the same form, add
the CLASSES inside each level:
name, code, capacity, optional pinned
classroom, optional class teacher.
│
▼
3. SUBJECTS                               /workspace/subjects
Every subject taught, with a unique
code, a type (Theory / Practical /
Both), a credit weight, and the
elective flag where relevant.
│
▼
4. CLASSROOMS                             /workspace/classrooms
Physical rooms, with capacity,
building and features. Needed before
the timetable generator can place
lessons.
│
▼
5. TIME SLOTS  (automatic)                /workspace/time-slots
Normally generated by the timetable
builder — you do not type these.
│
▼
6. TEACHER ASSIGNMENTS                    /workspace/teacher-assignments
Form teacher per level, class teacher
per class, subject specialists per
subject.
│
▼
7. TIMETABLE                              /workspace/visual-timetable-builder
Compile period blocks → choose year
and term → Auto-Generate All Lessons
→ fix anything unplaced by hand.
│
▼
8. VIEW TIMETABLE                         /workspace/timetable-viewer-page
Make the template active, then print.

Why this order matters:
- Years first, because levels, timetables, enrollments and fees all need an active year to attach to.
- Levels before classes, because a class cannot exist without a level.
- Subjects before the timetable, because a lesson is a subject taught at a time by a teacher — no subjects, no lessons.
- Classrooms before the timetable, because the generator needs somewhere to put lessons. (If you skip classrooms the generator can still run without room assignment, but you lose double-booking protection.)
- Teacher assignments before the timetable, because the generator places lessons for teachers.
EXAMPLE — the full Setup & Structure workflow for Tinway. 1. Academic Years. Click the Academic Years tab. Click Create. Enter Name 2026, Start Date 2026-01-15, End Date 2026-12-31. Tick Current Active. In the terms repeating group fill in three rows: Term 1 2026-01-15 to 2026-04-30 with Active Term ticked; Term 2 2026-05-01 to 2026-08-31; Term 3 2026-09-01 to 2026-12-31. Save. The list now shows one row, 2026, with a tick in Current Active and 3 in Terms. 2. Level. Click the Level tab. Click Create. Name Grade 3, Code G3. Leave Form Teacher blank for now. In the classes repeating group add three rows: 3A / G3A / capacity 40; 3B / G3B / capacity 40; 3C / G3C / capacity 30. Save. Repeat for Grade 4 and Grade 5. Return later to set the Form Teacher once the teachers exist. 3. Subjects. Click the Subjects tab. Create Mathematics / MATH / Theory / 1.00; English / ENG / Both / 1.00; Combined Science / SCI / Both / 1.50; Shona / SHO / Theory / 1.00. Repeat for the rest of the syllabus. 4. Classrooms. Click the Classrooms tab. Create Room 12 capacity 40 location Main Block with Projector and Whiteboard; Room 13 capacity 40; Science Lab A capacity 24 with Science Lab Equipment and WiFi Access. Now return to Level, edit Grade 3, and set the Classroom on class 3A to Room 12 and on 3B to Room 13. Open Academics → Overview. Steps 1 to 7 are now green. Steps 8 to 15 are still red, and the page tells you what to do next.

## 4.10 Progression — Promotion and Screening
Go to: Academics → Progression Addresses: /workspace/promotion-runs, /workspace/screening-runs
Both promotion and screening follow the same three-stage model:
DRAFT ──preview──► IN PROGRESS ──commit──► COMMITTED ──► ARCHIVED
▲                                            │
└──────────────── undo ──────────────────────┘

Undo works only on a committed run. It deletes the target enrollments, reactivates the originals and returns the run to draft.
### Promotion — moving students up a level
Click "Preview New Promotion". You must supply:
- the source academic year (the year they are in now), and
- the target academic year (the year they are moving into).
The system then builds one item per active enrollment whose student has a real first and last name. If the source year has no active enrollments it falls back to the most recent year that does — which saves you when you run it slightly early.
Each item gets a decision:
| Decision | Meaning |
| --- | --- |
| promoted | Advances to the next level. |
| repeated | Stays at the same level. |
| needs_screening | Requires a placement decision — see screening below. |

The next level is resolved from the level's next-level pointer, falling back to positional order over the sequence order. Both of those fields are not editable in the UI (see the caution in Section 4.3).
Review the Items table. It shows Student (searchable by name or admission number), Admission #, and Decision. Change any decision you disagree with.
Press Commit. The system creates a new enrollment in the target year, level and class, carrying the roll number and term across from the old record. Students with no target level are marked graduated.
EXAMPLE: Tinway runs promotion in December 2026, previewing from 2026 to 2027. 412 items are generated. 380 are promoted, 20 are repeated, and 12 have no next level so they become graduated. The bursar notices before committing that 12 students would leave; the head teacher adds a next level pointer for Upper Six to Form 6, re-previews, and commits.
CAUTION: Committing a promotion run will silently create a class named after the target level if that level has no classes yet. Check your target level has classes before you commit. Listed in Appendix 11.6.
### Screening — placing students by sCORE
Click "Preview New Screening". You supply:
| Field | Required | Notes |
| --- | --- | --- |
| Source Year | yes | The year the students are in now. |
| Target Year | yes | The year you are placing them into. |
| Promotion Run | no | Screen only the needs_screening students from a specific run. |
| SCORE Basis | no | Overall average, or selected subject(s) only. |
| Year Mode | no | The current year, or specific year(s). |
| Subjects, Terms, Years | no | Multi-selects to narrow further. |

Who gets screened
- If you linked a promotion run, only that run's needs_screening students.
- Otherwise, every active enrollment in the source year whose level is flagged as a terminal level.
CAUTION: That second route depends on the terminal-level flag described in Section 4.3. If no level has it, a screening run finds no candidates and the table is empty. This is the single most common reason "screening does nothing". Listed in Appendix 11.6.
How the sCORE is calculated
1.	Each mark is converted to a percentage of its assessment type's maximum mark.
2.	Within a subject, those percentages are averaged, weighted by the assessment type's weight percentage.
3.	The overall sCORE is the arithmetic mean of the per-subject sCOREs.
How students are placed
Screening rules are tried highest threshold first. The first rule whose threshold the student's sCORE clears places them, using that rule's target level and target class. Every item records the reason, so you can always see why a student went where they did.
If no rule matches, the student is marked unplaced with the reason "No screening rule matched for available sCOREs".
Target class fallback, when a rule has no class set:
1.	use the rule's target class, if it has one;
2.	otherwise use the first class of the target level, ordered by its rank then its id;
3.	otherwise the system creates a class named after the level.
WARNING: Step 3 means a committed screening run can quietly create a class for you. Always create the target classes yourself before committing.
The Items table
| Column | Notes |
| --- | --- |
| Student | Searchable. |
| Admission # |  |
| Overall SCORE % | Sortable — click to sort ascending and find the lowest performers. |
| Decision | Green placed, red unplaced. |
| Target Level |  |
| Target Class |  |
| Reason | Up to 40 characters, straight from the matching rule. |

The run header summarises placed / unplaced / total and echoes the criteria you chose, so a printed run is self-documenting.
### A page to avoid
There is an older page called Promote Students which is hidden and not linked from any tab. In one of its modes it generates a random GPA between 50 and 95 rather than reading real marks. Do not use it. Use the Promotion Runs and Screening Runs screens. Listed in Appendix 11.6.

## 4.11 Academic Reports (Report Cards)
Address: /workspace/academic-reports
NOTE: This screen sits under the Exams & Grading navigation group, not Academics, but it draws its data from Academics. It is described in full in Section 6.8.

## 4.12 The Complete First-Time Setup — Ordered Checklist
This is the sequence to follow on a brand-new school. Each step depends on the one above it. Budget half a day for steps 1 to 9.
| # | Do this | Where | Verify by |
| --- | --- | --- | --- |
| 1 | Create the academic year, its dates and its terms. Tick Current Active and one Active Term. | Academics → Setup & Structure → Academic Years | Exactly one tick in the Current Active column. |
| 2 | Create every level, with a unique code. Add the classes inside each level with names, codes and capacities. | Academics → Setup & Structure → Level | The Streams column shows a class count on every level. |
| 3 | Create every subject with a code, type and credit weight. | Academics → Setup & Structure → Subjects | The list shows one row per subject with no blanks. |
| 4 | Create every classroom with a capacity and features. | Academics → Setup & Structure → Classrooms | The Usage column is 0 everywhere — nothing scheduled yet. |
| 5 | Assign a form teacher to each level. | Level → Edit → Form Teacher | The Unassigned Teachers Only filter returns nothing. |
| 6 | Assign a class teacher and a pinned classroom to each class. | Level → Edit → classes | Each class row is complete. |
| 7 | Assign subject specialists. | Academics → Timetables & Teaching → Teacher Assignments | Every subject in every level has a teacher. |
| 8 | Compile the period blocks and generate time slots. | Academics → Timetables & Teaching → Timetables | The header shows your Lessons Duration, Period Length, Tea Break and Lunch. |
| 9 | Auto-generate the timetable for the year and term. Fix anything unplaced. | Timetables | The result message reports zero clashes. |
| 10 | Make the template active. Print a test copy. | View Timetable → Make Active | The active-template selector shows your template. |
| 11 | Create assessment types and grading scales. | Exams & Grading → Grading & Marks Management | Assessment Center lists your types. |
| 12 | Enrol students (outside Academics). | Admissions → Online Admissions, then Enroll | Step 13 of the checklist turns green. |
| 13 | Take attendance. | HR & Payroll → Attendance & Leave | The register opens for a class. |
| 14 | Take applications and enrol new students for the year. | Admissions | Step 12 turns green. |
| 15 | Create report cards per student per term. | Exams & Grading → Reports & Academic Publishing | A report card exists for a test student. |
| 16 | At year end: preview, review and commit promotion. Run screening for anyone needing placement. | Academics → Progression | The run reads committed. |

IMPORTANT: Steps 11 to 15 depend on step 12. Do not try to grade students who are not enrolled. If the Operations Center shows steps 12 to 15 red and you believe they are done, check that enrollments exist for the active academic year.
## 4.13 Academic Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| The Setup & Structure tab immediately jumps to Level | The hub forwards to the first page in the category when it has no memory of your last visit. | Nothing is wrong. Use the tabs. |
| I cannot delete an academic year | It is the active year, or it has terms. | Activate a different year, or change the old year's Stage to Archived. |
| Everything disappeared after I activated a new year | The active year changed. | That is the intended behaviour. Old data is still there under the old year. |
| Student balances jumped when I changed the term | Carry-forward ran. | Expected. Tell the bursar in advance. |
| "Cannot delete a form with N enrolled students" | The level has enrollments. | Promote or transfer the students first. |
| "Cannot delete a subject that is used in timetable entries" | The subject appears in lessons. | Remove it from the timetable first. |
| "Active Schedule Template Not Selected" | No timetable template is active. | Open View Timetable and press Make Active. |
| "Scheduling Conflict Blocked" | The teacher already has a lesson then. | Choose another slot or teacher. |
| "Classroom Double-Booking Blocked" | The room is taken then. | Leave Classroom blank for that lesson, or pick another room. |
| Auto-generation placed nothing | No active template, no year, or no term chosen. | Check all three, then read the bulleted reason list the page gives you. |
| Gaps Detected warning | No levels or no classes exist. | Create them in Level first. |
| Screening found no candidates | No level is flagged as a terminal level. | An administrator must set the terminal-level flag. See Section 4.3. |
| Promotion put everyone in the same class | The target level had no classes, so one was created. | Create the classes first, undo the run, and re-commit. |
| I cannot set "next level" or "sequence order" | They are not exposed in the form. | They must be set directly in the database. See Section 4.3. |
| A class filled beyond its capacity | Capacity is not enforced by auto-assignment. | Check capacities after bulk enrolment. |
| Timetables disappeared | A new template was made active. | Switch back with Make Active on the old template. |

In this phase you learned: how the Academics module is organised into four categories; how to create academic years with many years but only one active, and why that matters; how terms are created inside the year form and how the active term carries balances forward; how to define levels and create classes inside them; how to define subjects with types and credit weights; how to define classrooms with features; how to compile period blocks and generate time slots; how the visual timetable builder works and the three clashes it prevents; how to assign teachers and the level-versus-class scope rule; the Academic Operations Center and its 15 steps; promotion and screening workflows including sCORE calculation and rule matching; a summary of report cards; a 16-step ordered first-time setup checklist; and how to diagnose the common academic problems.

# Phase 5 — Admissions and Students
This chapter covers the two modules that create and hold the people in your school: Admissions, which turns an enquiry into an enrolled student, and Students, which holds the student record.
## 5.1 How the Two Modules Fit Together
PUBLIC WEBSITE                ADMISSIONS                 STUDENTS
──────────────                ──────────                 ────────
Parent fills in the    ──►   Application        ──►      Student record
3-step application           (status: pending)           (status: active)
form                          │                           │
│                             │  Staff review             │  ID card
│                             │  documents, interview     │  documents
│                             ▼                           │  medical record
│                          APPROVED                      │  portal login
│                             │                           │
└─ Reference number ──────────┤  "Enroll Student"         │
shown to parent             └───────────────────────────┘
creates the Enrollment
too, and emails the parent

IMPORTANT: Three different records are involved and they are not the same thing. | Record | What it is | Created by | | --- | --- | --- | | Application | Somebody asked to join. Not yet a student. | The public form, or a staff member | | Student | A person the school has enrolled. | The Enroll action, or direct entry | | Enrollment | The placement: this student, in this level, in this class, in this year. | The Enroll action, or direct entry | A student can exist without an enrollment (bad — they are not in any class), and an enrollment without a student is impossible.

## 5.2 The Public Application Form
Address: https://Tinway.yourdomain.co.zw/apply-online
The form is published from the school's own website, so parents reach it from the website they already know. The level and academic-year drop-downs are generated from your real configuration, so a parent can never pick a class you do not teach.
The form has three steps and a hidden security field that only a bot would fill in.
### Step 1 — Student Profile
| Field | Required | Rules and notes |
| --- | --- | --- |
| First Name | yes | Up to 40 characters. |
| Last Name | yes | Up to 40 characters. |
| Gender | yes | Male, Female or Other. |
| Date of Birth | yes | Cannot be in the future. |
| Boarding Status | yes | Day Scholar or Boarder. Collected here so accommodation can be planned. |
| Medical Notes / Allergies | no | Up to 1000 characters. |
| Student Email | no | The student's own address, if they have one. This is the address the portal account is created against. |
| Physical Address | yes | 5 to 500 characters. |
| Year Applying For | yes | A drop-down of your academic years. |
| Term | no | A drop-down of terms. |
| Level / Course Applying For | yes | A drop-down of your levels. |

### Step 2 — Parent or Guardian
| Field | Required | Notes |
| --- | --- | --- |
| Full Name | yes | Up to 70 characters. |
| Primary Email | yes | Validated as the parent types. All correspondence goes here. |
| Primary Phone | yes | Validated as the parent types. |

### Step 3 — Documents
| Upload | Requirement |
| --- | --- |
| Transfer Letter | Required only if the level applied for is not an entry level and the school has switched on the transfer-letter requirement. Otherwise optional. |
| Supporting documents | Up to 10 files. Each needs a short title. PDF, JPG, JPEG or PNG. Maximum 10 MB per file. |

Typical supporting documents: a birth certificate, the previous school's report card, and proof of residence.
### What the parent sees at the end
A success screen showing the tracking reference with a copy button, so they can keep it. The reference looks like APP-2026-A7K2QP.
### Anti-spam and safety
- A hidden field, randomised per session, that a human never sees. If it is filled in, the submission is silently discarded and the parent is redirected back to the form with no error.
- The form is rate-limited to 20 submissions per 10 minutes.
- The level chosen is validated to belong to your school, so a hand-crafted request cannot attach an application to another school's class.
### What the system does on submission
Inside one database transaction:
1.	A reference number is generated as APP-{year}-{6 random characters}, retried until unique.
2.	The Application is created with status = 'pending' and the applied level derived from the chosen course.
3.	Documents are stored under admission-docs/{application-id}. A transfer letter is saved with its type recorded and flagged not yet verified.
4.	The transaction commits.
5.	Then staff are notified — never before the commit, so nobody is told about an application that failed to save.
### Who gets notified
Staff holding the admissions.receive_notifications permission receive an in-app notification with the application number, the applicant and parent details, and a link to the record. If the school has switched on admissions email and set a contact address, the school also receives an email.
CAUTION: Two fields are collected on the public form — Boarding Status and Medical Notes — but they are not currently saved onto the application record. Every student enrolled from the public form therefore defaults to Day Scholar with no medical notes. Enter these by hand on the application when you review it, and always take medical details verbally or on paper at interview. Listed in Appendix 11.6.
CAUTION: national_id is validated on the public form but there is no input for it, so it is never collected. It can only be entered on the internal application form by staff.
## 5.3 Reviewing Applications
Go to: Admissions → Online Admissions Address: /workspace/applications
### The application record — four tabs
Tab 1 — Application
The student's identity and the level they applied for, plus the parent or guardian's name, email and phone. Staff can also enter a National ID here, which the public form does not collect.
Tab 2 — Review
| Field | Notes |
| --- | --- |
| Documents | The uploaded documents, as a repeating list. |
| Documents Verified | Tick once you have checked them against originals. |
| Interview Date |  |
| Interview Notes |  |
| Outcome | Passed, Failed or Pending. |

Tab 3 — Approval
| Field | Notes |
| --- | --- |
| Status | pending → verified → confirmed → enrolled, or rejected, or waiting_list. |
| Decision Notes | Your reasoning. Shown to nobody by default; keep it useful for the record. |

TIP: Use the statuses consistently. pending = not yet looked at; verified = documents checked; confirmed = offer made; waiting_list = good candidate, no place this year; rejected = declined; enrolled = done. The status drives your admissions funnel report.
Tab 4 — Enrollment
Where you decide the placement. You can leave these blank here and let the Enroll action ask you, but filling them in makes bulk enrolment smoother.
| Field | Notes |
| --- | --- |
| Academic Year |  |
| Course / Level |  |
| Class / Section | Leave blank and the system assigns the least-populated class. |
| Admission Number | Optional. If blank, one is generated. |

### Filtering the list
| Filter | Use it for |
| --- | --- |
| Status | Work the queue: pending first. |
| Unverified documents | Find applications whose documents nobody has checked. |
| Applied this month | Monthly intake reporting. |
| Applied between | Any date range. |

## 5.4 Enrolling a Student
The Enroll Student action appears on every application that has not yet been enrolled. It is one action that does everything.
### The confirmation form
| Field | Required | Notes |
| --- | --- | --- |
| Academic Year | yes | Defaults to the active year. |
| Course / Level | yes |  |
| Section / Class | no | Leave blank to auto-assign. |
| Admission Number | yes | Pre-filled with a suggestion of the form {year}/{3 random digits}, for example 2026/417. Change it if your school has a numbering convention. |

### What the action does, in order
1. RESOLVE THE CLASS
If you chose a class, use it.
If you left it blank, ask the class balancer for the least-populated
class for that level, year and school.
If no class exists at all for that level, STOP with the message:
"No class exists for this level…"   ← create classes first
│
▼
2. UPDATE THE APPLICATION
status = enrolled, and the course is corrected if needed.
│
▼
3. CREATE THE PORTAL ACCOUNT
Look for an account matching the STUDENT'S OWN email.
Never the parent's email. Restore a deleted one, or create a new
pending student account with a random password.
│
▼
4. CREATE THE STUDENT RECORD
Admission number, national ID, names, gender, date of birth,
boarding status, medical notes, admission date = today,
status = active, photo, guardian name, guardian phone,
parent email, student email.
│
▼
5. CREATE THE ENROLLMENT
School + student + academic year + course + section.
│
▼
6. COPY THE DOCUMENTS
Every application document becomes a StudentDocument, filed under
the student's own folder, so the record is self-contained even if
the application is later removed.
│
▼
7. EMAIL THE PARENT
An admission confirmation containing the student's name, admission
number, school name, academic year, level — and a set-your-password
link for the student portal.
│
▼
8. NOTIFY STAFF
Everyone with admissions.receive_notifications is told a student
has been enrolled, with a link to the record.
│
▼
9. PROVISION THE PORTAL ACCOUNT
Link it to the student record and, if it is new, email the student
their own activation link.
│
▼
10. CONFIRM TO YOU
A toast telling you the portal account is ready and the activation
email was sent — or warning you that no email was on file, so the
student has no login.

IMPORTANT — the admission email's activation link supersedes the earlier one. Issuing a new activation token overwrites the previous one. If a parent received two emails, the later link is the only one that works. Tell them to use the most recent email.
WARNING: If the application has no student email on file, no portal account is created and no student activation email is sent. The parent confirmation still goes out. You will see a toast saying no email was on file. Add the student's email to the student record later and use Send Activation Email from the student list.
### Auto-assigning the class
If you leave the class blank, the balancer picks the least-populated class for that level and year. Two things to know:
- It picks alphabetically among equally-populated classes, so the choice is predictable but arbitrary.
- It does not consider the classroom or stream capacity. See the caution in
Section 4.5.
## 5.5 Bulk Enrolling
Select several reviewed applications and choose Bulk Enroll.
The same sequence runs for each application that is not yet enrolled:
- an admission number is generated automatically if you do not supply one;
- a class is chosen by the balancer, or one you supplied;
- documents are copied;
- the portal account is provisioned and the activation email sent;
- the parent confirmation email is sent;
- staff are notified.
You get a toast reporting how many students were enrolled.
CAUTION: The bulk path does not copy Boarding Status or Medical Notes onto the student record, even if they were typed onto the application. The single-student action does copy them. After a bulk enrolment, spot-check the boarding status of every student you expected to be a boarder and fix them. Listed in Appendix 11.6.
## 5.6 Entering a Student Directly
Sometimes there is no application — a transfer who walked in, or a staff child. Go to Students → Students → Student Directory → Create.
TIP: Prefer the Enroll action where you can. Direct creation does not copy documents from an application and does not run the same checks, so it is easier to create a student with no enrollment.
The form has three tabs. See Section 5.7.
On save the system:
1.	creates the student and their enrollment;
2.	applies any fee waiver flag across open invoices;
3.	sends the admission notification to the parent;
4.	creates and emails the portal account.
## 5.7 The Student Record
Go to: Students → Students Address: /workspace/students
### Tab 1 — Personal Details
| Field | Notes |
| --- | --- |
| First Name, Last Name |  |
| Student ID Number | Generated automatically on creation if you leave it blank. |
| Admission Number | Generated automatically on a monthly cycle in the form YYMM-XXXX-VV if you leave it blank. |
| Gender |  |
| Date of Birth |  |
| National ID |  |
| Email | The student's own email. Used for the portal account. |
| Phone |  |
| Physical Address |  |
| Photo |  |
| Guardian Name |  |
| Guardian Phone |  |
| Parent Email | Where fee and admission correspondence goes. |
| Emergency Contact |  |
| Boarding Status | Day Scholar or Boarder. |
| Medical Notes / Allergies |  |
| Admission Date |  |
| Status | active, inactive, graduated, transferred, suspended. |
| Fee Waiver | A flag applied consistently across open invoices. |

How the automatic numbers work
| Number | Pattern | Notes |
| --- | --- | --- |
| Student ID Number | R + 2-digit year + random digits + suffix | Generated once, on creation. Permanent. |
| Admission Number | YYMM + - + 4 digits + - + 2 digits | Regenerated on a monthly cycle, so each month starts a new sequence. |

EXAMPLE: A student admitted in January 2026 might get 2601-0042-VV. A student admitted in February gets a number starting 2602-.
TIP: If your school has its own admission-number convention, type it. The system only generates one when you leave the field empty, and once a number is set it stays.
### Tab 2 — Enrollment
| Field | Notes |
| --- | --- |
| Academic Year |  |
| Course / Level |  |
| Section / Class |  |
| Roll Number | The student's number within the class. |

An enrollment has its own status: active, promoted, repeated, transferred_out or graduated. Promotion runs change it for you.
### Tab 3 — ID Card
| Field | Notes |
| --- | --- |
| Card Status | pending_issuance, active, lost, stolen or reissued. |
| Card Expiry | Can be calculated automatically from the issue date. |

Every change to the card status is written to a status log with a timestamp, so you can answer "when was this card reported lost?".
### The student list
| Column | Notes |
| --- | --- |
| Photo | A circular thumbnail, with a neutral placeholder by gender. |
| Student ID |  |
| Name | Searchable on any part of the name, so chik finds Rudo Chikore. |
| Email |  |
| Gender | A coloured badge. |
| Form | The level. |
| Stream | The class. |
| Status | A badge. |
| Enrolled Date |  |

Filters: Status, Gender, Form (level), Boarding Status.
Row actions:
| Action | What it does |
| --- | --- |
| View / Edit |  |
| Print ID | Opens the card print screen scoped to this student. |
| Approve Photo | Locks the submitted photo so it can no longer be replaced by the student. |
| Remove / Replace Photo | Requires a reason. The student is notified that their photo was rejected. |

Bulk actions:
| Action | What it does |
| --- | --- |
| Send Activation Email | Skips students already activated; re-issues tokens for the rest. |
| Print Selected ID Cards |  |
| Download ID Cards as PNG / ZIP | Bulk image export. |
| Delete |  |

### Importing students from a spreadsheet
The list page has a CSV import wizard with a column-mapping step, so you can upload a file exported from another system and map its columns to the system's fields without editing the file first.
Rows that fail validation can be exported as a CSV so you can fix them and re-upload only those. This is far better than losing a whole import to one bad row.
### Editing a student
Saving changes to an existing student also:
- updates the enrollment;
- applies or removes a fee waiver across all open invoices when you change the waiver flag. Be aware this touches invoices you may already have issued.

## 5.8 Guardians
A student can have several guardians, linked through a join table, so both parents can be recorded with their own contact details.
| Field | Notes |
| --- | --- |
| Name |  |
| Relationship | Mother, Father, Grandparent, Aunt, Uncle, Sibling, Legal Guardian, Other. |
| Phone |  |
| Email |  |
| Is Primary | The one used for official correspondence when only one is needed. |
| Can Receive Academic Reports | Controls whether this guardian is sent report cards. |
| Can Receive Fee Statements | Controls whether this guardian is sent invoices. |

TIP: When both parents want the report cards, record both guardians and tick the academic-report box on each. The fee defaulters job then chases both of them.

## 5.9 Student Documents
Address: on the student record.
Eight document types are available:
| Type | Typical use |
| --- | --- |
| Birth Certificate |  |
| Previous School Report |  |
| Transfer Letter |  |
| Passport / ID |  |
| Medical Record |  |
| Guardian Consent |  |
| Photograph |  |
| Other | Anything else, with a custom title. |

Documents filed here are independent of the application documents. When you enrol from an application, application documents are copied here, so the student's record is complete even if the application is later archived.

## 5.10 The ID Card Designer
Go to: Students → ID Card Templates Address: /workspace/card-templates
A full visual designer for school identity cards.
### Template settings
| Field | Notes |
| --- | --- |
| Template Name |  |
| Target Cohort | Limit the template to a year group, level or class. |
| Theme | One of 11 built-in themes. Each ships with a complete set of font and colour defaults you can then customise. |
| Orientation | Portrait or landscape. |
| Barcode Format |  |
| Active | Only one template is active at a time — setting one active deactivates the others. |

### Element visibility
About 12 independent toggles control what appears on the card: photograph, name, admission number, level, class, guardian contact, school name, school logo, signature line, validity dates, barcode and QR.
### Design controls
| Group | What you control |
| --- | --- |
| Canvas | Card size, background, border. |
| Gradient | Background gradient direction and colours. |
| Watermark | A faint background image. |
| Typography | The font family, and per-element font, size, weight and colour. |
| Brand colours | The school's primary and accent colours. |

### Position and size — per element
Each visible element gets its own tab where you set its position, size, colour and font. So you can move the photo to the left, shrink the name, recolour the admission number, and change the signature line's thickness — independently.
### Live preview
The preview renders through the same rendering pipeline as the printed card. What you see is exactly what prints. This is deliberately the case so that there are no surprises at printing time.
### Versions
Every design change is stored as a version, so you can see what the card looked like in the past. Useful when a school wants to know why last year's cards looked different.
### Table actions
| Action | What it does |
| --- | --- |
| Preview |  |
| Duplicate | Copy a template as the basis for a variation. |
| Set Active | Make this the template used for printing. Deactivates the others. |


## 5.11 Printing ID Cards
Address: /workspace/card-templates printing, or the Print ID action on a student.
### Print scopes
| Scope | What it prints |
| --- | --- |
| Selected | Only the students you ticked. |
| By class | Every student in one class. |
| By grade | Every student in one level. |
| New cards | Students whose card status is pending_issuance. |
| Expired cards | Students whose card expiry date has passed. |
| No card | Students who have never had one printed. |

The last three are the ones you will actually use day to day.
### Output formats
| Format | Use |
| --- | --- |
| PDF | For the office printer. Multiple cards per page. |
| PNG | For a single card, or for a submission system. |
| ZIP of PNGs | For bulk upload to another system. |

### Print verification — an anti-fraud feature
Every print job writes a print history row containing:
- a serial number, and
- an HMAC verification code derived from it.
So a lost card can be traced to the job that produced it, and a card can be checked for authenticity. This is recorded whether the card is printed, exported or downloaded.
### Bulk printing from the student list
Select students and choose Print Selected ID Cards or Download ID Cards as PNG/ZIP. This bypasses the print screen and goes straight to the job.

## 5.12 Student Medical Records
Go to: Health & Safety → Health Records Address: /workspace/student-medical-records
A long-term record per student, separate from the clinic visit log.
| Field | Notes |
| --- | --- |
| Blood Group |  |
| Allergies |  |
| Chronic Conditions |  |
| Immunisation History | A repeating list — name, date given, dose, administered by. |
| Notes |  |

Medical alerts can be raised against a student for critical conditions. Alerts surface automatically so that a teacher registering a sick student, or a hostel staff member on duty, sees the alert before making a decision.

## 5.13 Worked Example — A Complete Admission
EXAMPLE: The Nyathi family applies online for Kudzai Nyathi to join Tinway as a Grade 5 day scholar.
The parent applies
The parent opens https://Tinway.yourdomain.co.zw/apply-online, which they found on the school's website. They complete all three steps and submit. They see the success screen with reference APP-2026-K3M9XW and copy it.
The admissions officer reviews
She opens Admissions → Online Admissions, filters to Status: pending, and clicks the record.
- Tab 1 confirms the details. She notices Boarding Status was collected as Day Scholar — correct — and types the National ID from the birth certificate, which the online form could not collect.
- Tab 2: she ticks Documents Verified after checking the birth certificate and the previous report card against originals. She sets Interview Date to the 20th and Outcome to Passed.
- Tab 3: she sets Status to confirmed and writes a decision note.
- Tab 4: she sets Academic Year 2026 and Course / Level Grade 5, and leaves the class blank.
She enrols
She presses Enroll Student. The confirmation dialog opens with Admission Number pre-filled as 2026/318. She accepts it and confirms.
The system:
1.	Asks the balancer for the least-populated Grade 5 class for 2026 and gets 5B (28 students, against 34 and 31 in the others).
2.	Sets the application to enrolled.
3.	Creates a portal account for kudzai@student.test with status pending.
4.	Creates the Student with admission number 2026/318, boarding status day_scholar, the national ID she typed, guardian Farai Nyathi, guardian phone, and parent email parent@nyathi.test.
5.	Creates the Enrollment: Kudzai, 2026, Grade 5, class 5B, roll number 29.
6.	Copies the birth certificate and report card into his student documents.
7.	Emails his parent the admission confirmation with a set-your-password link.
8.	Notifies the admissions team.
9.	Emails Kudzai his own portal activation link.
10.	Shows her a success toast confirming the portal account is ready.
Kudzai activates
His parent forwards him the latest email. He opens the link, sets username k.nyathi and a strong password, signs in at /student, and sees his Grade 5 timetable, his fees balance and his homework.
NOTE: The parent received two emails with different activation links — one from the admission confirmation and one for the student portal. Only the later one works. She should forward the newer email.
She spots a problem
She notices Kudzai's record shows no medical notes, even though the parent wrote "Allergies: peanuts" on the form. She opens his student record and types it into Medical Notes / Allergies by hand. She also raises a medical alert so any teacher registering him sees it.
## 5.14 Admissions and Students — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| The public form's level drop-down is empty | No levels exist. | Create levels in Academics → Level first. |
| The form says the year is not allowed | The chosen academic year is not selectable. | Check the year exists and the form's year list. |
| "No class exists for this level…" when enrolling | The level has no classes. | Create classes inside the level. See Section 4.3. |
| The enrolled student defaulted to Day Scholar despite Boarder on the form | boarding_status is not saved from public applications. | Fix it by hand on the student record. See Section 5.2. |
| Medical notes are empty after enrolling | Same cause. | Type them in by hand. |
| After a bulk enrolment, boarding status is wrong even though the application had it | The bulk path omits it. | Check every boarder and fix. See Section 5.5. |
| The parent never got the confirmation email | The admissions email category is off, or no contact address is set. | Admissions → Admission Settings. |
| The parent got two emails with different links | Two activation tokens were issued; the newer one wins. | Tell them to use the latest email. |
| The student cannot sign in to the portal | No student email was on file, so no account was created. | Add the email, then Send Activation Email from the student list. |
| CSV import rejected some rows | Those rows failed validation. | Export the rejected rows, fix them, re-upload only those. |
| The ID card preview does not match the printout | It should not happen — both use the same pipeline. | Report it; it is a genuine defect. |
| A student's card status changed and I cannot see why | It is in the card status log. | Open the student → ID Card tab. |
| Cannot delete a student | Referenced by enrollments, invoices or marks. | These records must be retained for audit. |
| The class chosen by auto-assign is wrong | The balancer picks the least-populated alphabetically and ignores capacity. | Choose the class explicitly at enrolment. |
| I cannot find the Applications menu | Either the Admissions module is off, or you are looking in the wrong place. | System Settings → Manage Modules, then Admissions in the sidebar. |

In this phase you learned: the difference between an application, a student and an enrollment; the public three-step application form with every field and its rules; how the parent gets a tracking reference; the anti-spam protection; how staff review an application across its four tabs; the statuses and what each means; the ten-step Enroll Student action including automatic class balancing and portal account creation; bulk enrolment and its one important difference; the three tabs of the student record and how admission numbers are generated; the student list, its filters, actions and spreadsheet import; guardians and why you want more than one; the eight student document types; the ID Card Designer with its themes, element controls and live preview; the six print scopes and the verification code behind them; student medical records and alerts; a complete worked admission; and how to diagnose the common problems.

# Phase 6 — Examinations, Grading and the LMS
This chapter covers two modules: Exams & Grading, which turns raw marks into grades, positions and report cards, and LMS, which handles homework and submissions.
## 6.1 How Grading Works — The Full Chain
Understand this chain before you touch any screen.
ASSESSMENT TYPE            "Mathematics Test"     max 50, weight 30%
│                     "Mathematics Exam"     max 100, weight 70%
▼
MARKS                       Kudzai sCOREd 42/50 on the Test
│                     and 71/100 on the Exam
▼
PERCENTAGES                 Test  = 42/50  = 84%
│                     Exam  = 71/100 = 71%
▼
WEIGHTED, PER SUBJECT       84% × 30  +  71% × 70   = 74.9%
│
▼
OVERALL SCORE               Mean of the per-subject sCOREs across
│                     all subjects on the report
▼
GRADING SCALE               74.9% falls in the "B" band
│                     (70 – 79)
▼
POSITION / RANK             Ranked within the class for the subject
│
▼
REPORT CARD                 Kudzai Nyathi — Mathematics — B — 74.9%
— position 7 of 29 — teacher remark
│
▼
PUBLISHED TO THE PORTAL     Kudzai sees it at /student → My Results

IMPORTANT: A percentage is always calculated against the assessment type's maximum mark, and assessment types are weighted. A single 40% exam does not mean a student has 40%. Configure the weights deliberately — see Section 6.3.
## 6.2 The Exams & Grading Module Structure
| Category | Pages |
| --- | --- |
| Assessment Center | Digital Assessments, Question Bank |
| Grading & Marks Management | Performance Analytics, Marks Entry, Grading Scales, Assessment Types |
| Reports & Academic Publishing | Report Cards, Report Templates, Workflows, Publish to Student Portal |


## 6.3 Assessment Types
Address: /workspace/assessment-types
An assessment type is a category of assessment with its own maximum mark and weight. You define these once and they then apply everywhere.
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example Test, Assignment, Practical, Exam, Project. |
| Code |  | Short code. |
| Maximum Mark | yes | The denominator for every mark recorded under this type. |
| Weight Percentage | yes | How much of the final subject sCORE this type contributes. All weights for a subject should total 100. |
| Assessment Category |  | How it is grouped in reports. |
| Setup Stage |  | Pending, In Progress, Complete. |

EXAMPLE — sensible weights for a Zimbabwean secondary school:
| Assessment Type | Maximum Mark | Weight |
| --- | --- | --- |
| Continuous Assessment (CA) | 50 | 30% |
| Mid-term Test | 50 | 20% |
| Practical | 40 | 10% |
| End-of-term Exam | 100 | 40% |
| Total |  | 100% |

With these weights, a student who sCOREs 42/50 on the CA and 71/100 on the exam has an 84% CA and a 71% exam, giving 84 × 0.30 + 71 × 0.40 = 53.6 from the two assessed components.
WARNING: The weighting is used as a decimal multiplier, so a type whose weight is 30 is treated as 30×, not 30%. Enter 30 for 30%, and confirm the total of all weights is 100 before you enter marks. There is no validation that the weights total 100, so an incorrect total silently distorts every result.
## 6.4 Grading Scales
Address: /workspace/grading-scales
A grading scale turns a percentage into a letter or a descriptor.
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example Zimbabwean Secondary, Primary School. |
| Grade / Band | yes | A, B, C, Distinction, Merit, and so on. |
| Minimum Percentage | yes | The lower bound of the band. |
| Maximum Percentage | yes | The upper bound. |
| Grade Points |  | For GPA calculations. |
| Remark |  | A short descriptor printed on the report card. |
| Is Passing |  | Whether this band counts as a pass. Used by promotion and screening. |
| Active |  | Switch a band off without deleting it. |

Bands are evaluated from the highest minimum downwards, so the first band whose minimum the sCORE clears wins.
### A complete grading scale
| Band | Min % | Max % | Points | Remark | Passing |
| --- | --- | --- | --- | --- | --- |
| A | 80 | 100 | 5 | Excellent | Yes |
| B | 70 | 79.99 | 4 | Very Good | Yes |
| C | 60 | 69.99 | 3 | Good | Yes |
| D | 50 | 59.99 | 2 | Satisfactory | Yes |
| E | 40 | 49.99 | 1 | Needs Improvement | Yes |
| F | 0 | 39.99 | 0 | Fail | No |

TIP: Always include a final band starting at 0. Without one, a student scoring below your lowest minimum gets no grade at all rather than the lowest grade.
## 6.5 The Question Bank
Address: /workspace/question-banks
Store reusable questions so you can assemble assessments without retyping them.
| Field | Notes |
| --- | --- |
| Question | The text. |
| Subject |  |
| Question Type | Multiple choice, true/false, short answer, essay, matching, numeric. |
| Difficulty | Easy, Medium, Hard. |
| Marks | How many marks this question carries. |
| Answer / Model Answer | For auto-marked types, the correct answer. For written types, the marking note. |
| Explanation | Shown to the student afterwards, if you choose to reveal it. |
| Active | Switch out of circulation without deleting. |

TIP: Tag questions by topic as well as subject — "quadratic equations", "parts of speech". When you come to build a test, you can pull a coherent set in seconds rather than picking at random.
## 6.6 Digital Assessments
Address: /workspace/digital-assessments
Publish a quiz to the student portal. Students attempt it online; answers are marked automatically where the question type allows.
### Setting up an assessment
| Field | Required | Notes |
| --- | --- | --- |
| Title | yes |  |
| Subject | yes |  |
| Class / Section | yes | Who may attempt it. |
| Assessment Type | yes | Links the result to a weighted component. |
| Academic Year and Term | yes |  |
| Questions | yes | Chosen from the question bank. |
| Total Marks |  | Summed from the questions. |
| Available From | no | Before this moment the student cannot start. |
| Available Until | no | After this moment the student cannot start. |
| Max Attempts | no | How many times a student may try. Leave blank for unlimited. |
| Accept Late Submissions | no |  |
| Shuffle Questions | no | Each student gets a different order. Discourages copying. |
| Show Results Immediately | no |  |
| Show Correct Answers | no | Reveal the answers after submission. |
| Pass Mark % | no |  |
| Published | no | Draft assessments are invisible to students. |

### The student experience
A student opens the assessment and the button reflects their state:
| Their state | Button |
| --- | --- |
| Not started, in the window | Start Assessment |
| Started, not submitted | Continue |
| Attempts exhausted | Completed |
| Any state, if results are released | View Result — showing their best attempt |

The student is blocked, with a clear reason, when: they are not enrolled in the class; they have used all their attempts; the window has not opened; the window has closed; or the assessment is not accepting submissions.
A student cannot see another student's attempt, ever.
### Marking
Multiple choice, true/false and numeric answers are marked automatically against the stored answer. Short answer, essay and matching are held for a teacher. Where Show Results Immediately is on, the student sees their sCORE and — if you allow it — the correct answers and explanations.

## 6.7 Marks Entry, Manual Marking and Analytics
### Marks Entry
Address: /workspace/assessment-marks
This is the fast screen for getting marks in.
1.	Choose the class, subject, assessment type, academic year and term.
2.	The class roster loads, one row per student, with a mark box each.
3.	Type or paste the marks. Students are sorted by admission number.
4.	Save. Values are validated against the assessment type's maximum mark, so a mark above the maximum is rejected rather than silently distorting a percentage.
TIP: Marks Entry also accepts a bulk import, so you can mark on paper during an examination and enter the whole class from a spreadsheet afterwards.
### Manual Marking
Address: /workspace/manual-marking
For the responses that cannot be marked automatically. It shows the student's written response and lets the teacher award marks and feedback, which is then pushed back into the same mark set. Everything then behaves like any other mark — percentages, weights, grades and report cards all update.
### Performance Analytics
Address: /workspace/exams-performance-analytics
| Filter | Shows |
| --- | --- |
| Class |  |
| Subject |  |
| Assessment type |  |
| Academic year / term |  |
| Range | Filter to students within a percentage band. |

For each subject you get the average, maximum and minimum, plus the distribution across bands. Use it to find a subject where the class is struggling, or a teacher whose class is consistently an outlier.
TIP: Sort the class list by a subject average rather than by name. The pattern across several subjects usually identifies a teaching or syllabus issue rather than a student issue.

## 6.8 Academic Reports — Building the Report Card
Address: /workspace/academic-reports
### Creating a report
Tab 1 — Class and Student Selection
| Field | Notes |
| --- | --- |
| Class | A live searchable drop-down. |
| Student | Reloaded from the chosen class's enrollments. |
| Term | Offers only the terms of the currently active academic year. Set the active year correctly before building report cards. |
| Status | Published, Approved or Draft. Defaults to Approved. |
| Teacher Comment | The class teacher's remarks. |
| Outstanding Achievements | One accomplishment per line. |
| Headmaster Comment |  |

Subject Teacher Remarks is a repeating group: pick a subject, write a remark, press Add Subject Remark. Repeat per subject. These appear on the printed report card only if your report template has the Show Subject Teacher Remarks Column option enabled.
Heritage-Based Curriculum (HBC) Competencies are two blocks:
- Top 10 CORE: Respect, Honesty, Responsibility, Discipline, Patriotism, Cooperation, Leadership, Critical Thinking, Creativity, Environment.
- Additional 10 (collapsed by default): Communication, Digital Literacy, Entrepreneurship, Cultural Appreciation, Community Service, Perseverance, Compassion, Time Management, Self-Confidence, Adaptability.
### The competency rating scale
| Rating | Points |
| --- | --- |
| Outstanding | 10.0 |
| Excellent | 8.5 |
| Very Good | 7.0 |
| Good | 5.5 |
| Satisfactory | 4.0 |
| Needs Improvement | 2.0 |
| Skip / Not Graded | — |

Every rating control offers the Skip / Not Graded placeholder so you are never forced to sCORE a competency you have no evidence for.
### The list
| Column | Notes |
| --- | --- |
| Student |  |
| Class |  |
| Year |  |
| Term |  |
| Status | Draft, Approved, Published. |
| Overall sCORE |  |
| Strengths |  |
| Needs improvement |  |
| Teacher |  |

### Generating reports in bulk
The resource resolves the enrollments for the scope you chose and creates or repairs reports — including re-pointing draft reports that were attached to the wrong class. So you can select a whole class and generate every report card in one action.
### Publishing
A report card is only visible to the student once its status is Published.
IMPORTANT: The Teaching Staff role deliberately excludes the permission exams.publish_to_student_portal. A teacher can build and approve a report card but cannot publish it. Publishing is reserved for administrators and the head of department. This is a deliberate control, not a fault — Section 3.9.
CAUTION: The Outstanding Achievements text is written into the competency JSON field, while the model also declares a separate top-level column of the same name. Anything reading the standalone column will come back empty. Use the competency field, which is where the form writes. Listed in Appendix 11.6.
### Report templates
A report template controls the layout of the printed report card. The important switch is Show Subject Teacher Remarks Column. A report template must be marked active for the Operations Center's step 15 to turn green.
## 6.9 Workflows — Tracking an Assessment Through Its Life
Address: /workspace/assessment-workflows
Where Marks Entry records marks, a workflow tracks the assessment itself from planning to publication. Use one for a formal test that has stages.
### Setting up a workflow
Tab 1 — Create Assessment
| Section | Field | Notes |
| --- | --- | --- |
| Assessment Details | Assessment / Test Name | Required. |
|  | Subject | Required. |
|  | Class Stream | Required. |
|  | Term | Required. |
| Grading & Weighting | Max Attainable Mark |  |
|  | Weight towards Final Term Grade (%) |  |
|  | Workflow Status | See below. |

Tab 2 — Define Scope
| Field | Notes |
| --- | --- |
| Restrict to Specific Grade / Form | Leave empty if the assessment applies to all levels. |

Table columns: ID, Assessment Name, Max Mark, Weight %, Class Stream, Term, and a status badge.
Filters: status and subject.
Actions:
| Action | What it does |
| --- | --- |
| Enter Marks | Opens Marks Entry, already filtered to this assessment. |
| Publish | Available only when the status is Under Review. Sets it to Published. |
| Edit |  |

Bulk actions: Open for Marking (moves selected workflows to open), Publish Results (moves them to published), and Delete.
### The seven workflow statuses
| Status | Meaning |
| --- | --- |
| draft | Being planned. |
| scheduled | Planned for a future date. |
| open | Open for marking. |
| marking | Marking in progress. |
| review | Under review. This is where Publish becomes available. |
| published | Results released. |
| locked | Closed. No further change. |

## 6.10 Publish to Student Portal
Address: /workspace/exams-portal-reports-publisher
One screen that releases both report cards and individual assessments to students, so you do not have to do it in two places.
| Field | Notes |
| --- | --- |
| Scope | Whole School, a Stream, a Class, a Subject, a Term, or Other (portal only). |
| Publish Report Cards to Portal | Bulk-releases report cards in that scope. |
| Unpublish Report Cards | Withdraws them again. |
| Assessments in the Portal | Every assessment in scope, with its Max Mark, Weight, and whether it is Included in Report Card. |

IMPORTANT: Students see continuous-assessment marks only for assessments whose status is Published. To release an "other" assessment that is not part of a report card, mark it here.
IMPORTANT: This is the screen the Teaching Staff role cannot reach. See Section 6.8.

## 6.11 The LMS Module
Go to: LMS → LMS Addresses: /workspace/homework, /workspace/lms-homework-lessons
### Setting homework
Address: /workspace/homework
| Field | Required | Notes |
| --- | --- | --- |
| Class Stream | yes | Which class. |
| Subject | yes |  |
| Assignment Title | yes |  |
| Submission Due Date | yes | Defaults to two days from today. |
| Instructions / Study Guide | no |  |
| Study Guide / Worksheet | no | One file, up to 5 MB. PDF, Word or an image. Stored privately. |
| YouTube Lesson Link | no | A URL, so students can watch the lesson. |

### The list
| Column | Notes |
| --- | --- |
| Title | Searchable and sortable. |
| Subject | Sortable. |
| Class | Shows the level and the class. Sortable. |
| Due Date | Sortable. |
| Submissions | A count badge with a tooltip. |

Row actions:
| Action | What it does |
| --- | --- |
| Edit |  |
| Share to WhatsApp | Builds a message containing the class, subject, assignment title and due date, and opens WhatsApp with it ready to send. Use this to reach parents who will not read the portal. |
| Delete |  |

### Grading submissions
Open the Submissions panel on any homework record.
| Column | Notes |
| --- | --- |
| Student Name | Searchable. |
| Submitted Date | With the time. |
| Grade | A percentage badge. Green at 50% and above, red below. Pending Grading until you grade it. |
|  | Actions: Grade to enter the mark and feedback; Download Work to open the uploaded file. |

The grading form is two fields:
| Field | Notes |
| --- | --- |
| Grade Obtained (%) | A number. |
| Teacher Feedback | Written feedback shown to the student. |

### The student experience
Students see their homework sorted by due date, soonest first, with a status badge:
| Badge | Meaning |
| --- | --- |
| Green Submitted | Work uploaded and graded or awaiting grading. |
| Red Overdue | Past the due date, nothing uploaded. |
| Amber Pending | Not yet past due, nothing uploaded. |

Clicking View opens a slide-over with the subject, class, due date, instructions, the attachment, the video link, the student's own submission and the teacher's feedback.
Submit Work opens a required file upload. Once a student has submitted, the button is gone — they cannot submit twice, so tell them to check before uploading.
IMPORTANT: How the system identifies which student is logged in is worth knowing if a student reports seeing the wrong work. It tries four things in order: a direct link to their user account; a username matching their student ID or admission number; a match on their first and last name; and finally a match on the parent's email address. The fallbacks exist so that a student can still get in when the link was never made properly.

## 6.12 The Student Portal
Address: https://Tinway.yourdomain.co.zw/student
Students have their own small, safe application. They cannot reach the staff workspace at all — this is enforced when they sign in, not merely hidden.
| Sidebar group | Page | What the student can do |
| --- | --- | --- |
| — | Dashboard | See upcoming homework (the next 5), their invoice count, unpaid balance, pending payments, ungraded submissions, available digital assessments, recent results and assessment statistics. One screen answering "what do I need to do today?" |
| Academics | My Results | Filter by term and subject. See published or approved report cards and published continuous-assessment marks. Drafts are invisible. |
| Academics | Performance Analytics | Their own per-subject average, maximum and minimum. Can be switched off for the whole school. |
| Academics | My Timetable | The timetable for the current academic year, plus upcoming tests and tasks. |
| Academics | Digital Assessments | Attempt published assessments. See Start / Continue / Completed according to their state, and View Result for their best attempt. |
| Academics | My Homework | View and submit homework. See Section 6.11. |
| Fees & Payments | My Fees | See invoices and their balance. Pay online with PayNow, or upload a bank deposit slip for staff approval. Pending transactions are polled automatically. |
| Library | Library | Browse the catalogue and the knowledge hub, with search, category and media-type filters. |
| Communication | Notices | Announcements aimed at students. |
| Communication | Polls & Surveys | Vote once in each poll and see live standings. |
| Communication | My Schedule | Their own events. They may edit their own events but nobody else's. |
| Boarding | My Residence | Their hostel, room and bed. |
| My Account | My Profile | Their own details. The photograph locks once approved, so it cannot be swapped afterwards. |
| Learning | Gamification Profile | Currently switched off in this release. |

IMPORTANT: A student is refused the staff workspace even if they somehow obtain a staff role. Conversely a staff member is refused the student portal unless they have a linked student record. The check happens at sign-in.
NOTE: Performance Analytics can be switched off for the entire school in System Settings. Gamification is currently disabled at the page level.

## 6.13 Marks and Grades — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| A student's percentage looks far too high | An assessment type's weight is much larger than intended, or weights do not total 100. | Recalculate the weights in Assessment Types. |
| A student has no grade at all | The sCORE is below the lowest band's minimum. | Add a final band starting at 0. |
| The Term drop-down on a report card is empty | The active academic year has no terms. | Add terms in Academic Years. See Section 4.2. |
| The Term drop-down is missing the right term | Report cards only offer terms of the active year. | Activate the correct year. |
| A teacher cannot publish a report card | The Teaching Staff role excludes exams.publish_to_student_portal. | An administrator publishes, or grant the permission to a custom role. |
| Marks above the maximum are rejected | Working as intended. | Check the assessment type's maximum mark. |
| A student cannot start an assessment | Not enrolled in the class, outside the window, or out of attempts. | The page states which of these applies. |
| A student cannot resubmit homework | Submissions are one per assignment. | Ask the teacher to delete or extend if needed. |
| Subject teacher remarks do not print | The report template has the column switched off. | Enable Show Subject Teacher Remarks Column in the report template. |
| "Outstanding Achievements" appears empty elsewhere | It is stored in the competency JSON field. | Read it from there, not from the standalone column. See Section 6.8. |
| Only one student can attempt an assessment | Max attempts may be 1. | Check Max Attempts. |
| Analytics show no data | No marks have been entered, or the filters exclude them. | Enter marks first, then widen the filters. |
| The whole Exams menu is missing | The Exams module is off, or your role has no exams permissions. | System Settings → Manage Modules, and check your role. |
| Gamification is missing in the student portal | It is disabled in this release. | Expected. |

In this phase you learned: the full chain from assessment type to published report card; how to configure assessment types with maximum marks and weights, and the danger of weights that do not total 100; how grading scales turn percentages into bands; the question bank and why tagging by topic pays off; how to publish a digital assessment and what the student sees; Marks Entry and bulk import; manual marking for written responses; performance analytics; how to build a report card with subject remarks and the twenty HBC competencies and their seven-point rating scale; why a teacher cannot publish; the LMS homework workflow including WhatsApp sharing and grading; every page of the student portal; and how to diagnose grading problems.

# Phase 7 — Finance, HR, Payroll, Attendance and Leave
This chapter covers the three modules that run a school's money and its people: Finance, HR & Payroll, and Attendance.
## 7.1 The Finance Module Structure
| Category | Pages |
| --- | --- |
| Dashboard & Analytics | Overview (Executive Financial Dashboard), Financial Statements |
| Student Billing & Revenue | Fee Categories, Fee Structures, Invoices, Fee Collections, Student Financial History, Payment Proofs, Fee Waivers |
| Expenses & Purchasing | Expenses |
| CORE Accounting & Setup | Revenue Streams, School Bank Accounts, Document Templates |

Additional accounting screens, reached from the CORE Accounting hub rather than the tabs: Accounts (/workspace/accounts) and Journal Entries (/workspace/journal-entries).

## 7.2 Fee Categories
Address: /workspace/fee-categories
Categorise your fees so reports group them meaningfully. This is a prerequisite for fee structures.
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example Tuition, Boarding, Meals, Transport, Uniform, Books, Excursion. |
| Code |  |  |
| Description |  |  |
| Recurring | no | Tick if this fee is charged every term rather than once. |
| Active |  | Switch off without deleting history. |

TIP: Create your categories before your fee structures. It is much harder to categorise hundreds of fee structure rows afterwards, and every billing and revenue report depends on the category.
## 7.3 Fee Structures
Address: /workspace/fee-structures
A fee structure says: this category, this amount, for this level / class / term.
| Field | Required | Notes |
| --- | --- | --- |
| Fee Category | yes |  |
| Course / Level | no | Leave blank to apply to all levels. |
| Section / Class | no | Narrower than a level. |
| Term | no | Leave blank to apply to every term. |
| Amount | yes | In your school currency. |
| Currency |  | Defaults to the school currency. |
| Frequency |  | Once, per term, per month, per year. |
| Effective From / To |  | Optional date range. |
| Active |  |  |

EXAMPLE: Tinway's structures for 2026: | Category | Level | Term | Amount | | --- | --- | --- | --- | | Tuition | Grade 5 | Term 1 | 120.00 | | Tuition | Grade 5 | Term 2 | 120.00 | | Tuition | Grade 5 | Term 3 | 120.00 | | Meals | Grade 5 | Term 1 | 45.00 | | Transport | Grade 5 | Term 1 | 30.00 | | Uniform | (all) | (once) | 60.00 |

## 7.4 Invoicing — The Two Ways to Bill
Address: /workspace/invoices
There are two routes, and they are not interchangeable.
### A. Automatic bulk billing
The Bulk Billing action generates invoices for a whole group at once.
1.	Choose the academic year and term.
2.	Choose the scope: a level, a class, or selected students.
3.	The system matches every fee structure in scope and creates one invoice line per matching structure, per student.
4.	Each invoice gets a monthly number from your school's invoice sequence.
IMPORTANT: The system guards against double-billing. Before creating an invoice it checks whether that student already has an invoice for the same category in the same term. Re-running bulk billing for the same scope is safe — it will not duplicate charges it can already see.
EXAMPLE: Tinway issues Term 1 invoices for all three Grade 5 classes. The system finds 87 students, and for each of them matches Tuition 120.00, Meals 45.00 and Transport 30.00 where applicable, producing invoices numbered from 2026/01/0001 upward. Kudzai's invoice shows Tuition 120.00, Meals 45.00, Transport 30.00 — total 195.00.
### B. A single manual invoice
For one-offs: a transfer fee, a damaged book, a late enrolment penalty. Create the invoice directly and add arbitrary lines.
### The invoice
| Field | Notes |
| --- | --- |
| Invoice Number | Generated from the monthly sequence. |
| Student |  |
| Academic Year / Term |  |
| Issue Date / Due Date | The due date drives defaulter reporting. |
| Lines | Each with a description, a fee category and an amount. |
| Status | unpaid, partial, paid or cancelled. |

### Recording a payment
The Record Payment action on an invoice:
1.	Enter the amount — it may be less than, equal to, or more than the balance.
2.	Choose the method: cash, bank transfer, mobile money, cheque, other.
3.	Choose the bank account if more than one is registered.
4.	Optionally record a reference and a date.
5.	Save.
A numbered receipt is generated and can be printed immediately.
IMPORTANT — overpayments. If the amount exceeds the balance, the surplus does not disappear. It is stored as credit on the student's account and applied automatically to their next invoice. The receipt still shows the full amount received.
### The other invoice actions
| Action | What it does |
| --- | --- |
| Print Invoice | The full invoice. |
| Print Receipt | The payment receipt. |
| Print Statement | The student's full statement of account. |
| PayNow | Initiates a mobile-money payment and polls for confirmation. |
| Adjust Fee | Adds or removes a line from an issued invoice. Recorded in the audit log. |

### Bank deposit submissions
Where you cannot take a card payment, a parent deposits into your bank account and uploads the slip.
Address: /workspace/fee-payment-submissions
The staff screen lists pending submissions. For each one you Approve or Reject.
- On approval, the payment is recorded against the student's invoice, a receipt is issued, and both the school and the parent are emailed.
- On rejection, the reason is recorded and both parties are emailed so the parent knows to try again.
TIP: Always reject with a reason. "Slip unreadable" tells the parent something actionable; a bare rejection does not.

## 7.5 Fee Collections
Address: /workspace/fee-collections
The screen that tells you who has paid and who has not, grouped so you can act.
| View | What it shows |
| --- | --- |
| By class or level | Every invoice in a group, with its balance and status. |
| Collection rate | Invoiced, collected and outstanding for the period. |
| Defaulter list | Students with an outstanding balance, with their guardians. |

From here you can open an invoice, record a payment, or export the list for follow-up.
## 7.6 Fee Waivers
Address: /workspace/fee-waivers
| Field | Required | Notes |
| --- | --- | --- |
| Student | yes |  |
| Fee Category | no | Leave blank to waive all categories. |
| Amount |  | A fixed amount, or leave blank with Percentage set to waive a proportion. |
| Percentage |  |  |
| Reason | yes | For example sibling discount, hardship, staff child, scholarship. |
| Effective From / To |  |  |
| Approved By |  |  |

Waivers are applied consistently. Setting the waiver flag on a student applies it across all their open invoices; clearing it removes it again. Both actions are recorded on the student's financial history so you can always see what happened and when.

## 7.7 Revenue Streams and Bank Accounts
### Revenue streams
Address: /workspace/revenue-streams
Track income by source so you can reconcile at term end.
| Field | Notes |
| --- | --- |
| Name | For example Term 1 Tuition, Uniform Sales, Gate Takings. |
| Code |  |
| Expected Amount | Your budget for the period. |
| Actual Amount | Filled in as money comes in. |
| Period |  |
| Status | On target, behind, ahead. |

### Revenue and expense categories
Addresses: /workspace/revenue-categories, /workspace/expense-categories
Simple groupings used by the category pickers and the statements.
### Expense types
Address: /workspace/expense-types
Classifies an expense for reporting — for example Operating, Capital, Welfare, Administrative.
### Bank accounts
Address: /workspace/school-bank-accounts
| Field | Notes |
| --- | --- |
| Bank Name |  |
| Account Name |  |
| Account Number |  |
| Branch |  |
| Currency |  |
| Is Default | Exactly one account should be the default. It is pre-selected on invoices and receipts. |
| Active |  |

IMPORTANT — a quirk worth knowing. Transactions that have no bank account recorded are treated as belonging to the school's default account, or to its only account if none is marked default. So the "All Accounts" view and the default-account view agree, but selecting a non-default account excludes those transactions. This is deliberate, not a bug, but it surprises people reconciling a single account.

## 7.8 Expenses and Purchasing
### Recording an expense
Address: /workspace/expenses
| Field | Required | Notes |
| --- | --- | --- |
| Date | yes |  |
| Category | yes |  |
| Type | yes |  |
| Payee | yes |  |
| Description | yes |  |
| Amount | yes |  |
| Payment Method |  |  |
| Bank Account |  | Which account the money left. |
| Receipt / Attachment |  | Upload the invoice or slip. |
| Recorded By |  | You. |

### Expenses versus purchases — how they differ
|  | Expense | Purchase |
| --- | --- | --- |
| What it is | Money already spent on something consumed | A request to buy something, tracked from request to delivery |
| Where it starts | You type it | A department raises a Procurement Request |
| Does it create stock? | No | Yes, when the goods are received |
| Approval needed? | No | Yes — the request must be submitted and approved |
| Connects to a supplier? | Optionally, by name | Yes, to a supplier record and a purchase order |

A purchase becomes an expense when the goods arrive and the bill is settled. See Section 8.5 for the full procurement workflow.

## 7.9 CORE Accounting
Addresses: /workspace/accounts, /workspace/journal-entries
| Screen | What it holds |
| --- | --- |
| Accounts | The chart of accounts — account code, name, type (asset, liability, equity, income, expense), and whether it is active. |
| Journal Entries | Manual accounting entries: date, reference, description, and debit/credit lines that must balance. |

IMPORTANT: A journal entry that does not balance — debits not equal to credits — is refused. This is the single most important rule in the accounting module.
### Financial statements
Address: /workspace/financial-statements
| Statement | What it shows |
| --- | --- |
| Income Statement | Income and expenditure for a period, with the surplus or deficit. |
| Balance Sheet | Assets, liabilities and equity at a date. |
| Cash Flow | Money in and out, by activity. |
| Student Statement of Account | One student's complete history. |

### Document templates
Address: /workspace/finance-document-templates
Customise the layout of your invoices, receipts and statements — the school letterhead, the footer, the columns shown and their order, and whether to include a signature line. Template changes affect new documents; already-issued documents keep the layout they were issued with.

## 7.10 The Executive Financial Dashboard
Address: /workspace/executive-financial-dashboard
The screen the head teacher and the board actually look at.
| Group | Figures |
| --- | --- |
| Collections | Invoiced this term, collected this term, outstanding, collection rate. |
| Revenue trend | Month by month, invoiced against collected. |
| Aging | Balances split into current, 30 days, 60 days, 90 days and older. |
| Defaulters | The students with the largest outstanding balances, and their guardians. |
| Expense breakdown | By category and by type, against budget. |
| Recent activity | The last invoices and payments. |

TIP: The aging buckets are the most actionable part of this screen. Money more than 90 days old is usually far harder to collect than money that is merely overdue, and it should be escalated to the guardians and, where appropriate, to the board.

## 7.11 Student Financial History
Address: /workspace/student-financial-history
One page per student showing their entire financial history in one place. This is the screen to use when a parent disputes a balance.
Every event is listed with its date and running balance:
| Event | What it shows |
| --- | --- |
| Charge | An invoice line added. |
| Payment | Money received, with the receipt number. |
| Refund | Money returned to the parent. |
| Waiver | A fee waived, with the reason. |
| Carry-forward | A balance moved from one term to the next. |
| Invoice | A new invoice raised. |
| Reversal | A payment or invoice reversed. |
| Waiver removal | A waiver taken back. |

Available actions on the page: charge a fee, record a payment, issue a refund, apply or remove a waiver, view invoices, reverse a payment, delete an invoice, and print a statement. Every one of them is written to the audit log with the user and the timestamp.
IMPORTANT: When a parent queries a balance, come to this page rather than adding up invoices by hand. The carry-forward and waiver events are the two things that are almost always missed in a manual reconciliation.

## 7.12 Finance — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| Bulk billing produced no invoices | No fee structures match the chosen scope and term. | Check Fee Structures for that level and term. |
| Bulk billing ran twice | It is safe — duplicates are blocked. | Nothing to do. |
| A student's balance is negative | An overpayment became account credit. | Expected. It applies to the next invoice. |
| A payment will not save | No bank account is marked as default. | School Bank Accounts → mark one as default. |
| A journal entry is refused | Debits do not equal credits. | Balance the entry. |
| The defaulter job notified the wrong person | Multiple guardians exist and both receive copies. | Tick the fee statements box on the guardians who should be chased. |
| A receipt number looks out of sequence | Receipts and invoices use separate sequences. | Expected. |
| A bank account view does not add up | Transactions with no account are treated as the default account. | See Section 7.7. |
| Changing a waiver flag changed old invoices | Waivers apply across all open invoices by design. | Reverse the adjustment on the financial history page if that was not intended. |
| The statement total differs from the sum of invoices | A carry-forward or waiver was applied. | Read the financial history. |
| Mobile money payments are not confirming | The gateway is not configured, or the transaction is still pending. | Check the pending transactions list and the gateway settings. |
| I cannot delete an invoice | It has payments against it. | Reverse the payments first, on the financial history page. |
| The Expenses menu is empty | No expense categories or types exist. | Create them first. |


## 7.13 The HR & Payroll Module Structure
| Category | Pages |
| --- | --- |
| Staff Directory & HR | Employees, Staff Assets, Disciplinary Cases |
| Payroll & Compensation | Payroll Periods, Salary Grades, Staff Loans |
| Attendance & Leave | Leave Requests, Staff Attendance |

Also under Staff Directory: Staff Assets (/workspace/employee-assets) records equipment issued to an employee — a laptop, a phone, a fuel allowance — with the same issue, return and adjustment pattern as stock. See Section 8.3.
The employee creation wizard is described in full in Section 3.4. This section covers everything after that.

## 7.14 Salary Grades
Address: /workspace/salary-grades
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example Trainee Teacher, Junior Teacher, Senior Teacher, Head of Department. |
| Code |  |  |
| Minimum Salary | yes |  |
| Midpoint Salary | yes |  |
| Maximum Salary | yes |  |
| Pay Frequency |  | Monthly, fortnightly, weekly. |
| Components |  | The allowances and deductions attached to this grade. See below. |

### Salary components
| Field | Notes |
| --- | --- |
| Name | For example Housing Allowance, Transport Allowance, PAYE, Pension, Union Dues. |
| Type | Fixed — a set amount. Percentage — a percentage of the basic salary. |
| Amount / Rate | The value for the chosen type. |
| Is Deduction | Tick for statutory and voluntary deductions. |
| Applies To | One grade, or all grades. |

TIP: Model PAYE, pension and any union dues as percentage deductions on the grade. Model housing and transport as fixed allowances on the grade. Then payroll needs no per-employee overrides when rates change.

## 7.15 The Payroll Workflow
Address: /workspace/payroll-periods, then the Payroll run screen
1. CREATE A PAY PERIOD        Name, start date, end date,
payment date, pay frequency
│
▼
2. CREATE A PAYROLL RUN       Select the period, then tick
the employees to include
│
▼
3. THE SYSTEM CALCULATES      For each employee:
· basic salary from the grade
· every FIXED component added
· every PERCENTAGE component
recalculated at the current rate
────────────────────────────
GROSS  −  DEDUCTIONS  =  NET
│
▼
4. REVIEW AND APPROVE         Every payslip is shown with the
full breakdown before you commit
│
▼
5. POST                       On approval the employer cost is
posted to the expense ledger
automatically
│
▼
6. PRINT PAYSLIPS             One per employee, with earnings
and deductions itemised

### Pay periods
| Field | Required | Notes |
| --- | --- | --- |
| Name | yes | For example January 2026. |
| Start Date / End Date | yes | The period being paid. |
| Payment Date | yes | When money actually leaves. |
| Status | Draft, Processing, Approved, Paid. |  |

### Pay runs
| Field | Notes |
| --- | --- |
| Pay Period | Required. |
| Employees | Tick who to include. You can include all and then exclude. |
| Status | draft, paid or void. |

Statuses:
| Status | Meaning |
| --- | --- |
| draft | Being prepared. Editable. |
| paid | Finalised and money sent. |
| void | Cancelled. |

IMPORTANT: Because percentage components are recalculated at the current rate when the run is created, running the same period twice at different times can produce different figures if a tax rate has changed in between. Void a run before re-creating it rather than editing a paid one.

## 7.16 Staff Loans
Address: /workspace/staff-loans
| Field | Required | Notes |
| --- | --- | --- |
| Employee | yes |  |
| Loan Type | yes | For example Salary Advance, School Fee Loan, Emergency Loan. |
| Principal Amount | yes |  |
| Interest Rate |  | Percentage. |
| Instalment Amount | yes |  |
| Start Date / End Date | yes |  |
| Status | Active, Settled, Defaulted, Written Off. |  |

Loans are recovered from payroll as deductions, and the outstanding balance tracks each recovery.

## 7.17 Attendance
### Staff attendance
Address: /workspace/staff-attendance
| Field | Notes |
| --- | --- |
| Employee |  |
| Date |  |
| Status | Present, Absent, Late, Excused, On Leave. |
| Check In / Check Out | Times, so you can see hours worked. |
| Notes |  |

You can take attendance for the whole staff at once on a given date, which is the normal way to do it.
### Student attendance
Address: the register, inside Attendance & Leave
The register is taken per class on a given date.
| Status | Meaning |
| --- | --- |
| Present | In school. |
| Absent | Not in school, no explanation. |
| Late | Present but after the start time. |
| Excused | Absent with a reason — illness, appointment, religious observance. |

IMPORTANT: Medical alerts surface on the register. If a student has an alert for a serious allergy or a chronic condition, you see it before you mark them absent or send them to the clinic.
Attendance rates feed the Attendance by Class Stream and Attendance Monthly Trend reports, and are available to all staff for their own classes.

## 7.18 Leave
Address: /workspace/leave-requests
### Leave types
| Field | Notes |
| --- | --- |
| Name | Annual, Sick, Maternity, Paternity, Compassionate, Study Leave, Unpaid. |
| Days Allowed Per Year | For annual leave. |
| Requires Approval |  |
| Gender Restriction | Some types may only be taken by one gender — for example maternity leave. |
| Probation Restriction | Tick to block leave during probation. |
| Carry Over | Whether unused days roll into next year. |
| Requires Document | For example a medical certificate for sick leave beyond two days. |

### The request workflow
1.	The employee submits a leave request: leave type, start date, end date, and a reason.
2.	The system validates eligibility:
- enough days remaining in their balance;
- compliance with any gender restriction;
- compliance with any probation restriction;
- no overlapping leave already approved.
3.	It is validated against their own employee record, even if somebody else files it. A non-HR user filing on a colleague's behalf is transparently redirected to the correct record.
4.	The requester selects an approver where delegation applies.
5.	Status moves pending → approved or rejected.
| Status | Meaning |
| --- | --- |
| pending | Awaiting a decision. |
| approved | Granted. Attendance is updated. |
| rejected | Refused. |
| cancelled | Withdrawn by the employee. |

TIP: Keep separate leave types for sick and annual even if you use the same paperwork. The split is what lets you report on staff absence patterns at board level, and it is what the eligibility rules are built on.

## 7.19 Disciplinary Cases
Address: /workspace/disciplinary-cases
| Field | Notes |
| --- | --- |
| Employee |  |
| Case Number | Generated. |
| Date Reported / Incident Date |  |
| Category | Misconduct, Poor Performance, Attendance, Conduct, Other. |
| Severity | Minor, Major, Critical. |
| Description | What happened. |
| Witnesses |  |
| Status | Open, Under Investigation, Concluded, Escalated. |
| Outcome | The decision and any sanction. |
| Concluded On |  |

Actions available: add notes, conclude, and escalate.
IMPORTANT: These records are auditable. Keep the description factual and dated, and record the outcome once a decision is made. They are included in the Staff Disciplinary Cases report.

## 7.20 Worked Example — A Term's Billing and Payroll
EXAMPLE: Tinway bills Term 1 and runs payroll for January.
Billing
The bursar confirms Fee Structures are in place for Grade 5, Term 1. He opens Student Billing & Revenue, chooses Bulk Billing, sets year 2026, term Term 1, scope Grade 5. 87 invoices are created, numbered 2026/01/0001 to 2026/01/0087.
A parent pays by bank deposit and uploads the slip. The bursar opens Fee Payment Submissions, finds the submission, checks the amount against the slip, and approves. The payment posts to the student's invoice, a numbered receipt is issued, and both the bursar's email and the parent's email confirm it.
Another parent overpays by 20.00. The bursar records it. The receipt shows the full amount, and 20.00 appears as credit on the statement, applied automatically to the next invoice issued.
At month end he opens Executive Financial Dashboard. Collection rate is 71%. The aging shows 12% of the outstanding balance more than 90 days old. He exports the defaulter list, and the scheduled defaulter notification goes to the primary guardian of each.
Payroll
The HR officer opens Pay Periods and creates January 2026, 1 to 31 January, payment date the 28th. He opens the payroll run screen, selects the period and ticks all 24 employees.
For Rudo Chikore, on grade Senior Teacher:
Basic salary                     1,200.00
Housing allowance   (fixed)        250.00
Transport allowance (fixed)        100.00
──────────
GROSS                             1,550.00

PAYE              (percentage)     212.50
Pension           (percentage)      93.00
Union dues        (fixed)           15.00
Staff loan        (fixed)           50.00
──────────
DEDUCTIONS                        370.50
──────────
NET PAY                           1,179.50

He reviews, approves, and the employer cost posts to the expense ledger. He then prints 24 payslips.

## 7.21 Finance, HR and Attendance — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| No invoices were generated | No fee structures match the scope and term. | Create the structures first. |
| A student's credit did not apply | The credit applies to the next invoice, not the current one. | Check the financial history. |
| A payment will not record | No default bank account. | Mark one in School Bank Accounts. |
| A journal entry is refused | Unbalanced. | Debits must equal credits. |
| The statement total differs from the invoices | Carry-forward or waiver events. | Read Student Financial History. |
| A single bank account view looks short | Transactions with no account count as the default account. | See Section 7.7. |
| Payroll figures differ between two runs of the same period | Percentage components use the current rate. | Void and re-create rather than editing. |
| A payslip cannot be edited | The run is approved or paid. | Void it, or record a correction. |
| An employee cannot request leave | Eligibility failed — no balance left, probation, or a gender restriction. | The refusal names the reason. |
| Someone else filed leave on an employee's behalf | Non-HR filers are redirected to the correct record. | Expected. |
| Attendance will not save | A medical alert or an existing record for that date. | Resolve the conflict. |
| The Staff Directory tab is missing | HR module off, or your role lacks it. | System Settings → Manage Modules; check your role. |
| An employee's department permissions are not applying | They are not a member of the department. | Add them in User Management → Departments. |
| A salary advance is not being deducted | The loan is not attached to the grade's components or the payroll run. | Check both. |

In this phase you learned: how to categorise fees and build fee structures; the difference between automatic bulk billing and manual invoicing and how duplicate billing is prevented; how payments, receipts and overpayment credit work; mobile money and bank deposit submissions and why rejections need a reason; how fee waivers apply consistently across open invoices; revenue streams and bank accounts and the default-account quirk; expenses versus purchases; the chart of accounts, balanced journal entries and the four financial statements; document templates; the executive dashboard and why the aging buckets matter; the student financial history and its eight event types; every finance troubleshooting case; the five-step employee wizard reference; salary grades and salary components; the six-step payroll workflow and why re-running a period can change the figures; staff loans; staff and student attendance and how medical alerts surface; leave types, eligibility validation and the request workflow; disciplinary cases; two complete worked examples; and every HR and attendance troubleshooting case.

# Phase 8 — Inventory & Procurement, Library, Boarding & Welfare, Health & Safety
Four operational modules: Inventory & Procurement, Library, Boarding & Welfare, and Health & Safety.
## 8.1 Inventory & Procurement — The Whole Chain
This is the module that answers "where did the money go, and what did we get for it?"
A DEPARTMENT NEEDS SOMETHING
│
▼
PROCUREMENT REQUEST           PR-2026-0001 · "20 exercise books, Grade 5"
│                     drafted by a teacher, marked High urgency
▼
APPROVED BY THE HEAD          pick or create the supplier
│
▼
PURCHASE ORDER                PO-2026-00001 · sent to the supplier
│                     expected delivery 7 days out
▼
GOODS RECEIVED                GRN · 18 accepted, 2 rejected, short by 2
│                     stock levels INCREASE by the accepted quantity
▼
ISSUANCE                      3 exercise books issued to a teacher
│                     stock levels DECREASE by 3
▼
STOCK ADJUSTMENT              breakage found during term
−1, with a reason, for the audit trail

IMPORTANT: Stock only changes when goods are received or when an issuance or adjustment is posted. Approving a request and approving a purchase order change paperwork, not stock levels.
## 8.2 Inventory Items
Address: /workspace/inventory-items
The catalogue of everything the school holds.
| Field | Notes |
| --- | --- |
| SKU | Generated automatically. |
| Barcode | For scanning. |
| Name |  |
| Category | Free or grouped. |
| Item Type | Consumable, Returnable, Fixed Asset. |
| Unit of Measure | Each, packet, box, litre. |
| Reorder Level | The point at which you should reorder. |
| Current Quantity | Maintained by receipts, issuances and adjustments. |
| Average Unit Cost | What you are paying, on average. |
| Saleable | Whether the item is sold to students. |
| Sale Price | Only meaningful when Saleable is on. |
| Received Date |  |
| Description |  |

Table columns: SKU, Name, Category, Type, Quantity, Reorder Level, Cost, and a status badge that highlights the item when quantity has fallen to the reorder level.
Actions: Edit, Delete, and CSV import and export.
TIP: Use Item Type honestly. Consumables (chalk, glue) should be issued and written off. Returnables (sports equipment, calculators) should come back. Fixed Assets should never appear as stock at all — they belong in the asset register.
## 8.3 Issuing Stock
Address: /workspace/inventory-issuances
| Field | Notes |
| --- | --- |
| Recipient | A student or a staff member — both are supported. |
| Item |  |
| Quantity |  |
| Location | Where the stock is drawn from. |
| Returnable | Tick if the item is expected back. |
| Expected Return Date | For returnable items. |
| Remarks |  |

CAUTION — a current defect. If no stock location has been set up, the system falls back to location 0, which does not exist, and the posting is rejected. Create at least one location before issuing anything. See Appendix 11.6.
## 8.4 Stock Adjustments
Address: /workspace/stock-adjustments
Use an adjustment whenever the physical count disagrees with the system — breakage, theft, a miscount, a donation in.
| Field | Notes |
| --- | --- |
| Item |  |
| Location |  |
| Adjustment Reason | Breakage, theft, miscount, correction, donation, other. |
| Quantity | The delta: positive to increase, negative to decrease. |
| Notes |  |

Every adjustment writes a movement record and updates the item's quantity, so the variance is explainable afterwards.

## 8.5 The Procurement Workflow
### Step 1 — The request
Address: /workspace/procurement-requests
| Section | Field | Notes |
| --- | --- | --- |
| Request Metadata | Request Number | Auto: PR-2026-0001. |
|  | Requester | Set to you and locked. |
|  | Urgency | Low, Medium, High, Emergency. |
|  | Department |  |
| Purpose | Purpose | Why it is needed. |
| Requisitioned Items | Repeater | Item name, link to the catalogue, Fixed Asset? flag, quantity, estimated unit cost, description. |
| Signatories | Requester, Procurement Officer, Date Signed | For the paper trail. |

Table columns: Request No., Requester, Item Count, Urgency, Status, Approved By, Approved At, Created.
Actions:
| Action | What it does |
| --- | --- |
| Approve | Opens a modal to pick or create the supplier, then approves. |
| PDF | Downloads the requisition as a PDF. |
| Edit |  |
| Delete | Blocked for requests whose purchase orders are already approved, completed or partly received. |

### Step 2 — What approval actually does
Approving a request is not a rubber stamp. In one action the system:
1.	Generates a purchase order numbered PO-2026-00001, in draft, with an expected delivery seven days out and a total of quantity × estimated cost for every line.
2.	Adds any missing item to the catalogue automatically, so a request for something never catalogued still becomes orderable.
3.	Creates a Fixed Asset for every line flagged as a fixed asset — numbered FA-2026-00001, with 10% salvage value and 5-year straight-line depreciation.
4.	Marks the request approved, with the approver and the timestamp.
### Step 3 — The purchase order
Address: /workspace/purchase-orders
Table columns: Order No., Supplier, Order Date, Expected Delivery, Status, Total, Approved By, Approved At.
Clicking the row opens the order, which is where Edit lives.
| Action | What it does |
| --- | --- |
| Approve | Confirms the order with the supplier and creates the matching finance expense. |
| Receive Goods | Pre-fills a goods-received note from the lines still outstanding. |
| Compare GRN | Side-by-side: what was ordered against what was received. |
| Print |  |

Statuses: draft → approved → partially_received → received / completed.
### Step 4 — Receiving the goods
Address: /workspace/goods-receiveds
| Field | Notes |
| --- | --- |
| Purchase Order |  |
| Supplier |  |
| Received Date |  |
| Delivery Note Number | The supplier's own paperwork reference. |
| Lines | Each with Quantity Accepted and Quantity Rejected. |
| Notes |  |

What happens when you post it:
- Lines accepted at zero or less are skipped entirely.
- The outstanding quantity is validated, so you cannot receive more than was ordered.
- quantity_received on the order increases.
- Tracked batches are created with a batch number and, where relevant, an expiry date.
- Stock movements are written, so item quantities go up.
- Capitalised lines also create fixed-asset records.
IMPORTANT: Once a goods-received note is posted, its form is locked. If you accepted the wrong quantity, raise a stock adjustment — do not try to edit the posted note.
### Step 5 — Suppliers
Address: /workspace/suppliers
| Section | Field |
| --- | --- |
| Vendor Profile | Name, Contact Person, VAT / Tax Registration |
| Communication Channels | Phone, Email, Address |

CSV import and export are available.

## 8.6 Fixed Assets and Depreciation
### The register
Address: /workspace/fixed-assets
| Field | Notes |
| --- | --- |
| Asset Name |  |
| Asset Number | Auto: FA-2026-00001. |
| Inventory Item | Linked, where applicable. |
| Location |  |
| Custodian | The person accountable for it. |
| Acquisition Date |  |
| Purchase Cost |  |
| Salvage Value | The residual value. 10% by default for auto-created assets. |
| Useful Life | Years. |
| Funding Source |  |
| Status |  |
| Description |  |

### Posting depreciation
The Post Depreciation Schedule action runs the depreciation engine. Two methods are supported:
| Method | How it works |
| --- | --- |
| straight_line | The same amount every year: (cost − salvage) ÷ useful life. |
| double_declining | Twice the book value in the first year, halving each year. |

IMPORTANT: Only these two methods can be posted. If a schedule names any other method it is recorded but refuses to post, rather than silently producing wrong numbers.
### Asset maintenance
Address: /workspace/asset-maintenances
Records servicing: what was serviced, when, by whom, at what cost.
NOTE: This list has no Edit button — an existing log can only be corrected through its web address. Journal the maintenance as a new record instead, which is better practice for an audit trail anyway.

## 8.7 Inventory — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| Issuing stock fails | No stock location exists; the system falls back to a location that does not exist. | Create a location. See Section 8.3. |
| The Return action on an issuance errors | A defect in the current build. | Issue a stock adjustment to bring stock back instead. |
| Approving a request created a purchase order I did not expect | That is the designed behaviour. | See Section 8.5. |
| I cannot delete an approved request | Its purchase orders are approved, completed or partly received. | Delete the purchase orders first, if appropriate. |
| Receiving goods did not increase stock | Nothing was accepted — quantities accepted were zero or negative. | Re-post with correct accepted quantities. |
| Depreciation will not post | The method is not straight_line or double_declining. | Use one of the two supported methods. |
| A maintenance record cannot be edited | There is no Edit action on the list. | Add a new record. See Section 8.6. |
| The reorder badge is on everything | Reorder levels are set too high, or equal to the quantity. | Review reorder levels. |
| Reorder alerts never arrive | The alert command exists but is not scheduled. | Review reorder levels manually. Listed in Appendix 11.6. |
| The supplier page and the inventory supplier differ | Two supplier screens exist; only the module one is reachable. | Use the one under Inventory & Procurement. |
| Stock levels disagree with the physical count | Unrecorded issues or losses. | Count, then post a stock adjustment with a reason. |
| I cannot import CSV | The import capability is not granted to your role. | Ask an administrator to grant the import permission. |


## 8.8 The Library Module
Go to: Library → Library Addresses: /workspace/library-books, /workspace/e-resources, /workspace/library-issues, /workspace/knowledge-assets, /workspace/knowledge-galleries
## 8.9 Books and Resources
Address: /workspace/library-books
| Section | Field | Notes |
| --- | --- | --- |
| Resource Specification | Title |  |
|  | Subtitle |  |
|  | Authors | Multiple, and you can create an author inline. |
|  | Category | With inline creation. |
|  | ISBN |  |
| Storage & Format Parameters | Classification | Physical or Digital. |
|  | Format Class | Book, Journal, Thesis, Reference, Periodical, with inline creation. |
|  | Add Physical Copies | How many copies. |
|  | File | Digital items only, up to 300 MB. |
|  | External URL | For a resource hosted elsewhere. |
|  | Publisher |  |
|  | Publication Year |  |
|  | Cover |  |

### How copies work
For a physical title the system creates one record per copy, each with a barcode in the form BC-{book_id}-0001, condition excellent and status available.
The copy table shows: barcode, QR code (required), condition and status. Use the QR code to issue and return with a scanner or phone.
Table columns: cover, title, authors, category, classification, format class, then four counts — Total, Available, Damaged, Lost. A digital resource always reports 1 of 1, since it cannot be damaged or lost.
Actions: View (a digital resource opens in a new tab), Edit.
CSV import and export are available.
## 8.10 eResources
Address: /workspace/e-resources
A gallery of non-physical resources — past papers, links, recordings.
Actions: View, Download, Open Link. Bulk Pack & Download Selected zips your selection, and Delete removes them.
## 8.11 Issuing and Returning Books
### The circulation desk
Address: /workspace/library-circulation, then Issue Book (/workspace/issue-book), and /workspace/library-issues
| Field | Notes |
| --- | --- |
| Select Resource Title | Physical titles only, each showing available / total. |
| Select Copy / Barcode | Only copies whose status is available. |
| Search Student |  |
| Search Staff Borrower |  |
| Borrowed Date | Defaults to today. |
| Return Due Date | Defaults to 14 days later. |

Table columns: Title, Barcode, Student (with class), Staff Borrower, Borrowed, Due, Status.
### What the system does automatically
| Event | Effect on the copy |
| --- | --- |
| A loan is created | The copy becomes issued. |
| A loan changes to returned | The copy becomes available. |
| A loan changes to lost | The copy becomes lost. |
| A loan is deleted | The copy becomes available. |

IMPORTANT — current limitations. In this build there is no return button, no renew button and no fine collection screen on the circulation list, and nothing automatically flags a loan as overdue. The fine logic (a daily rate of 0.50 by default) and the renewal logic exist in the code but are not wired to the interface. Record returns by editing the loan's status to returned; that immediately frees the copy.
NOTE: The circulation desk overwrites the Borrowed Date with today's date when the loan is created, so review the date if you are backdating.
## 8.12 The Knowledge Hub — The School's Shared Repository
Address: /workspace/knowledge-assets, with /workspace/knowledge-galleries
The Knowledge Hub is broader than the library: it holds schemes of work, policies, past papers, templates and media.
It has the same two-section shape as Books, plus:
| Field | Notes |
| --- | --- |
| Sub-Classification | The subtype. |
| Access Visibility | Who may reach it. |

### Visibility levels
| Level | Meaning |
| --- | --- |
| Archival Staff Only | Not on the student portal at all. |
| Local Library Terminals Only | Inside the library only. |
| Academic Teaching Staff Only | Teachers. |
| Student Portal Viewable | Students. |
| External Public Website Portal | Public website. |
| Everyone | Any signed-in user. |

The default is Local Library Terminals Only.
Copies are generated as REP-{asset_id}-0001.
### Repositories Gallery
Address: /workspace/knowledge-galleries
A cover-image grid of the non-physical repository assets, with View, Download and Open Link, plus a bulk Pack & Download Selected and Delete. You cannot add a gallery asset from this screen — add it in the repository itself.

## 8.13 Library — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| The Location of a physical copy is blank | The copy form does not offer a shelf field. | Record shelving in the description. Listed in Appendix 11.6. |
| A book cannot be issued | No copy is available. | Find the copy that is out and mark its loan returned. |
| A loan never becomes overdue | Nothing checks the due date. | Use the reports and chase manually. |
| I cannot see a Return button | Not implemented in this build. | Edit the loan's status to returned. See Section 8.11. |
| Editing a loan gives a 404 | The edit page is not registered. | Use the View screen. |
| The library dashboard widget is missing | It sits outside the panel's widget folder. | Use the Circulation Desk counts instead. Listed in Appendix 11.6. |
| A student can see a teacher-only repository item | The student Knowledge tab filters only private. | Keep genuinely staff-only items on Archival Staff Only. See Section 8.14. |
| The copy's file cannot be found | Legacy rows with no file path. | Re-attach the file. |
| Physical and digital copies report the same counts | Digital resources always count as one available copy. | Expected. |
| Publisher and year are missing on repository items | The fields exist in the data but are not on the form. | Set them through the Books screen if needed. |

## 8.14 What Students See in the Library
Address: /student/library — titled Library & Resources
| Control | Behaviour |
| --- | --- |
| Search | Title, author, subject, description and ISBN. |
| Category |  |
| Classification | Physical or Digital. |
| Tabs | Books & Resources and Knowledge Hub. |

A digital card offers Access Resource, which opens the external link or the stored file. A physical card shows available / total available and a Visit library prompt — students cannot borrow a book themselves; the desk issues it for them.
IMPORTANT — visibility limits. The Knowledge Hub tab on the student portal hides only items marked Archival Staff Only. Items marked Teaching Staff Only, Everyone or Public Website also appear. Where confidentiality matters, use Archival Staff Only. Listed in Appendix 11.6.

## 8.15 Boarding & Welfare
## 8.16 Hostels, Floors and Rooms
### Hostels
Address: /workspace/hostels
| Field | Notes |
| --- | --- |
| Name |  |
| Type | Boys, Girls, Mixed. |
| Capacity | The number of learners it should hold. |
| Status | Active, Under Maintenance, Deactivated. |
| Description |  |
| Floors & Wings | Repeater: floor number, floor name, wings. |

CSV import and export are available.
### Rooms
Address: /workspace/hostel-rooms
| Field | Notes |
| --- | --- |
| Hostel |  |
| Floor | Optional. |
| Wing | Optional. |
| Room Number |  |
| Name |  |
| Room Type | Dormitory, Single, Double, Triple, Quad, Isolation Room. |
| Capacity |  |

Isolation Room is worth creating where possible — it is where a sick or distressed learner waits for a parent or an ambulance.
IMPORTANT — a defect that blocks this screen in the current build. Opening Create or Edit on a room raises an error, because the floor and wing drop-downs query a building relationship the data model does not have. Room creation cannot be completed from the interface until this is fixed. Creating hostels and their floors still works. Listed in Appendix 11.6.
## 8.17 Allocating a Learner to a Bed
Address: /workspace/hostel-allocations
A three-step wizard.
### Step 1 — Student Mapping
Choose the student and the academic year. A learner already allocated is shown as [Already Allocated to {hostel} — Room {n}] so you cannot allocate them twice by accident.
### Step 2 — Room Mapping
Choose the room. Options show occupancy — Room 4 (Occupied: 3/4) — and a [FULL] marker when there is no space left. Only vacant beds are offered, and you can add a bed inline.
### Step 3 — Processing Rules
Allocated At, Expected Checkout At and Notes.
Table columns: Student, Hostel, Wing, Floor, Room, Bed, Status.
What saving does: the learner's previous active allocation is set to completed, the new one to active, and the bed status flips between occupied and vacant automatically.
## 8.18 Out Passes
Address: /workspace/hostel-out-passes
| Section | Field | Notes |
| --- | --- | --- |
|  | Student | Selecting them auto-fills the hostel from their active allocation. |
|  | Hostel |  |
| Student Details |  | A read-only summary: name, student ID, gender, boarding status, grade and class, hostel, room and bed. |
|  | Type | Emergency Out-Pass, Weekend Leave, Medical Check, Home Visit. |
|  | Expected Departure |  |
|  | Expected Return |  |
|  | Reason |  |

### The approval chain — three steps
1. REQUEST PARENTAL APPROVAL     status → pending_parent_otp
a 6-digit OTP is sent to the first
guardian by SMS and email

2. VERIFY OTP                    enter the 6 digits
status → pending_warden

3. CONFIRM WARDEN EXIT           the warden signs the learner out
status → approved

IMPORTANT: There is currently no check-in action in the interface, so a learner's return is not recorded from this screen. Also, the OTP is stored and compared as plain text with no expiry — treat it as a formality, not a security control. See Appendix 11.6.
## 8.19 Hostel Attendance
Address: /workspace/hostel-attendances
| Field | Notes |
| --- | --- |
| Hostel |  |
| Date |  |
| Type | Morning Roll Call, Evening Roll Call, Curfew Verification. |
| Roll Call | Create only. Press Load learners from selected hostel, then tick Present — ticked by default — and add remarks. |

The three moments exist because a boarding school has three separate safety checks, not one.
Individual learners are managed in the Student Attendance relation manager, where each has a status of present, absent, late or on leave, plus a Parents Notified column.
NOTE: Nothing currently writes the "parents notified" value, so treat it as a manual reminder to tick once you have actually contacted the parent.
## 8.20 Inspections
Address: /workspace/hostel-inspections
| Field | Notes |
| --- | --- |
| Room |  |
| Inspection Date |  |
| Cleanliness | SCORE. |
| Inventory Status | SCORE. |
| Orderliness | SCORE. |
| Passes Inspection | Pass or Fail. |
| Notes |  |
| Inspector | Recorded from you. |

Table columns: Room, Date, and a pass/fail icon.
Use a weekly inspection with a fail recorded against a room; the pattern of fails in one room is a useful early warning about that dormitory.
## 8.21 Boarding — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| Create Room raises an error | The floor and wing look-ups query a relationship that does not exist. | Not usable in this build. See Section 8.16 and Appendix 11.6. |
| Load learners on a roll call errors | The same missing relationship. | Not usable in this build. |
| Learners shows no results | A search box defect. | Clear the search and try again. See Appendix 11.6. |
| A learner appears in another school's list | A search defect that bypasses the tenant filter. | Do not use the list to select; verify the learner's school before allocating. Listed in Appendix 11.6. |
| A room shows [FULL] but a bed is free | Occupancy counts allocations, not beds. | Check the allocations list. |
| A learner can be allocated twice | The warning is informational; the system still allows it. | Look for the [Already Allocated…] marker before saving. |
| A learner has left but the room still looks occupied | No checkout action from the allocations list. | Close the allocation. |
| An out pass stays at approved | There is no check-in action. | Record the return manually. |
| The warden's floor name is blank | The display reads a field the model does not store. | Use the room number instead. See Appendix 11.6. |
| A student portal residence looks wrong | It shows the newest allocation whatever its status. | Check the allocation status on the staff screen. |
| "Parents Notified" never fills in | Nothing records the notification. | Tick it manually after you have contacted them. |


## 8.22 Health & Safety
## 8.23 Medical Records
Address: /workspace/student-medical-records
| Field | Notes |
| --- | --- |
| Student |  |
| Blood Group | A+, A−, B+, B−, AB+, AB−, O+, O−. |
| Allergies |  |
| Chronic Conditions |  |
| Immunization History | Repeater: vaccine name, date administered. |

Table columns: Student, Blood Group, Allergies (truncated at 50 characters).
IMPORTANT — read this before you mark a learner absent. A learner's allergies and chronic conditions are shown on the class attendance register. This is the single most valuable safety feature in the module. If you do not read it, the allergy will not help you.
## 8.24 Clinic Visits
Address: /workspace/clinic-visits
| Field | Notes |
| --- | --- |
| Student |  |
| Visit Time |  |
| Body Temperature |  |
| Blood Pressure |  |
| Symptoms | Required. |
| Diagnosis |  |
| Treatment Given |  |
| Status | Checked In, Under Active Treatment, Admitted to Clinic Ward, Referred to General Hospital, Discharged. |
| Referral Destination | For a referral. |
| Recorded By | Recorded from you. |
| Treatment Prescriptions | Repeater: link a stock product, medicine, dosage, frequency, quantity. |

Table columns: Student (first and last name), Visit Time, Status, Temperature.
Actions: Edit, and Discharge Student — available until the visit is discharged, and requiring a final diagnosis and treatment note.
IMPORTANT: The parent is notified by email when the visit is discharged. A visit logged but not yet discharged does not trigger a parent notice in this build. See Section 8.25.
## 8.25 Health — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| A parent was not told about a clinic visit | Notification fires on discharge, not on logging. | Discharge the visit with a final diagnosis to send the notice. Listed in Appendix 11.6. |
| Prescribed medicine was never dispensed | The dispensing routine is not wired up. | Issue the medicine from Inventory & Procurement and record it. |
| Regular medications cannot be recorded | The field exists in the data but is not on the form. | Note them under chronic conditions in the interim. |
| Two medical records exist for one learner | There is no duplicate guard. | Keep one and delete the other. |
| No alert appears for a critical reading | Critical-value alerting is not implemented. | Read vitals yourself at the clinic visit. |
| A learner leaves the clinic without a record | Only the clinic screen creates one. | Always log a visit. |
| The clinic list cannot be filtered | Filters are not implemented on the list. | Use your browser's find, or export and filter in a spreadsheet. |
| A teacher needs a learner's blood group | Medical records are restricted by permission. | Ask the nurse. |


## 8.24 Cross-Module Tips
| If you are doing this | Also look at |
| --- | --- |
| Issuing exercise books to a class | Academics → Setup & Structure → Classrooms for the class list. |
| Buying books for a course | Academics → Subjects so the purchase matches a real syllabus. |
| Issuing a calculator to a learner and taking it back | Inventory Returnable type, plus an expected return date. |
| Recording a learner's illness affecting their marks | Exams & Grading → Manual Marking or a concession agreed with the head. |
| Boarding a learner | Admissions → Applications, then an allocation here. |
| Fining a learner for a lost book | Record the book lost on the loan, then bill via Finance → Invoices. |
| Sanctioning a learner for a hostel offence | Boarding → Inspections, then your school's disciplinary process. |

In this phase you learned: the five-step procurement chain from request to adjustment and the fact that only receipts and postings change stock; inventory items, issuance and adjustments and the location defect that blocks issuance; procurement request fields and exactly what approval generates; purchase orders, their four statuses and the six actions; goods received notes, what posting them does, and why a posted note is locked; suppliers; fixed assets and the two depreciation methods that actually work; the library's two-section book form and the automatic BC-… copies with QR codes; eResources and the bulk pack download; the circulation desk, the fourteen-day default, the automatic copy-status changes and the missing return, renew and overdue features; the Knowledge Hub's six visibility levels and the student-portal leak; the hostel, floor and room structure and the defect that blocks room creation; the three-step bed allocation wizard and its automatic occupancy handling; the three-step out-pass OTP chain and its missing check-in; the three hostel roll-call moments and inspections; the medical record, the immunization repeater and why you must read allergies before marking absence; clinic visits, statuses and the discharge rule; and every troubleshooting case in all four modules.

# Phase 9 — Communication Center, Website, Reports & Intelligence
Three modules: Communication Center (how you talk to people), Website (how the public sees you), and Reports & Intelligence (how you find out what is happening).
## 9.1 The Communication Center Structure
| Category | Pages |
| --- | --- |
| Overview | Communication Center |
| Schedule & Tasks | Schedule |
| Community & Engagement | Announcements, Events, Chat, Resources, Polls & Surveys |
| Help & Inbox | Helpdesk, Kairo CORE Messages |

NOTE: The Overview, Community & Engagement and Help & Inbox pages are category landing pages. They open the module and immediately take you to the first tab — they carry no counts, alerts or shortcuts of their own.
## 9.2 Notices — Announcements
Address: /workspace/announcements
The main way to reach the school community.
| Section | Field | Notes |
| --- | --- | --- |
| Notice Details | Title | Required. |
|  | Content | Required. Rich text, so you can format it. |
| Publication & Priority | Status | Draft, Scheduled, Published, Expired. |
|  | Priority | Low, Normal, Important, Critical, Emergency. |
|  | Display Style | Standard Card, Alert Banner, Modal Popup, Scrolling Ticker. |
|  | Requires Acknowledgement | See the caution below. |
| Audience Targets | Visible to Roles | Multiple: Administrators, Teachers, Students, Parents, Finance Staff, Librarians. |
|  | Published | Date. |
|  | Expires | Date. Shown as "Never" when blank. |
| Attachments | Documents | Multiple files, 2 MB each. PDF, Word, Excel, JPEG, PNG. |

Table columns: Title, Status, Published, Expiration, and a history toggle.
Filters: Show History (Include Expired & Drafts) — a single toggle that reveals everything, including drafts.
Actions: Edit, Publish (with confirmation), Delete.
### The four statuses
| Status | Who sees it |
| --- | --- |
| draft | Nobody. Staff editing only. |
| scheduled | Nobody, until the published date arrives. |
| published | Everyone in the selected roles. |
| expired | Nobody. The list shows how long ago. |

IMPORTANT: Notices reach students only. There is no parent panel and no teacher-specific notice screen, so a notice aimed at Parents or Teachers is stored and listed but is not delivered to anybody through a portal. To reach parents reliably, use a WhatsApp message. See Section 9.11.
CAUTION: Requires Acknowledgement is recorded and can be reported on, but no acknowledgement is ever captured — there is no acknowledgement screen or record behind it. Do not promise parents a reply-tracking feature. Listed in Appendix 11.6.
CAUTION: An attachment on a notice restricted to Administrators can still be downloaded by anyone who has its web address, because the file is served straight from public storage. Do not attach anything genuinely confidential to a notice. Listed in Appendix 11.6.
## 9.3 Events
Address: /workspace/events
| Section | Field | Notes |
| --- | --- | --- |
| Event Scope | Title | Required. |
|  | Description |  |
|  | Category | Academic Event, Board/Staff Meeting, Sports Event, Examination Period, Public Holiday. |
|  | Location |  |
| Date Boundaries | Start Time | Required. |
|  | End Time | Required. |
|  | Colour | Used on the calendar. |
|  | Target Roles | Administrators, Teachers, Students, Parents. |

Table columns: Title (searchable), Category, Start Time, End Time, Location.
NOTE: Target Roles is honoured for students — they see only events that include students. Events created from the Schedule page ignore the audience entirely and are visible to everyone. See Section 9.4.
## 9.4 Schedule — Events and Tasks Together
Address: /workspace/schedule
A calendar and a task list in one place, with a date range navigation.
### The event form on the Schedule page
| Field | Notes |
| --- | --- |
| Title |  |
| Category | Academic, Meeting, Sports, Examination, Holiday. |
| Description |  |
| All Day | Tick to make it a full-day entry. |
| Start Date / Start Time |  |
| End Date / End Time |  |
| Location |  |
| Colour |  |
| Reminder (minutes before) | Defaults to 30. |
| Recurrence | Does not repeat, Daily, Weekly, Monthly. |

Recurring entries are expanded into their individual occurrences for the period being viewed, so a weekly staff meeting appears on every week.
IMPORTANT: Events created here do not keep their target roles — the audience is stored empty, so every student sees every event created on this page. Use Events when the audience matters. See Section 9.3.
### Tasks
A task has:
| Field | Notes |
| --- | --- |
| Title |  |
| Details |  |
| Due Date |  |
| Priority | Low, Medium, High. |
| Recurrence | Does not repeat, Daily, Weekly, Monthly. |
| Important | Flagged separately from priority. |
| Status | open, done. |

### Reminders
The scheduler runs one command, schedule:send-reminders, which sends reminder notifications for tasks and events that have a reminder time set. Nothing else is scheduled — see the note in Section 9.11.
## 9.5 Chat
Address: /workspace/chat-threads
| Field | Notes |
| --- | --- |
| Group Name |  |
| Type | Direct Message, General Group Chat, Department Internal Chat, Classroom Stream Chat, Parent-Teacher Private Chat. |
| Add Members |  |

Table columns: Discussion Name, Type, Active Members, Messages Sent, Last Activity.
Actions: View, Edit, Post Alert.
Opening a thread gives you a message pane with file attachment and a notifications toggle. The sidebar lists every thread in the school.
IMPORTANT: Membership is not enforced. Any staff member can open and post in any thread in their school — including a Parent-Teacher private chat. Do not open a confidential conversation in a thread. Use a private channel outside the system for anything sensitive. Listed in Appendix 11.6.
NOTE: A system bulletin is an ordinary message that begins with the words SYSTEM BULLETIN:. Anyone typing those words has their message displayed as a red alert. Bulletin styling is lost if the text is later edited.
## 9.6 Campus Resources
Address: /workspace/campus-resources
A shareable file library for staff — syllabi, policies, templates, tutorials.
| Section | Field | Notes |
| --- | --- | --- |
| Resource Properties | Title |  |
|  | Description |  |
|  | Category | Academic Curriculum, Finance & Invoicing, Human Resources Policies, Examinations Syllabus, Video Tutorials, Office Downloads. |
|  | Tags |  |
| Security & Files | Version | Defaults to 1.0. |
|  | Asset File | PDF, DOC, DOCX, JPEG or PNG, up to 10 MB. |
|  | Cover Image |  |
|  | Download Eligibility | Administrators, Teachers, Students, Parents. |

Table columns: Cover, Title, Category, Version, Downloads, Date Added.
Actions: Edit, Download.
IMPORTANT: Download Eligibility is stored but never checked when somebody downloads. Anyone who can open the page can download every file. Treat it as a label, not a control. Listed in Appendix 11.6.
## 9.7 Polls & Surveys
Address: /workspace/polls
| Section | Field | Notes |
| --- | --- | --- |
| Survey Definition | Question | Required. |
|  | Description |  |
|  | Type | Quick Multi-choice Poll, Open Feedback Survey, Formal Student/Staff Election. |
|  | Is Anonymous | Cosmetic — see the note below. |
| Audience Boundaries | Target Roles | Administrators, Teachers, Students, Parents. |
|  | Expires At | Required. |
| Choice Parameters | Options | At least two choices. |

Table columns: Question, Type, an anonymous indicator, Participation (the vote count) and Closing Date.
Actions: View, Edit, Vote.
The View page shows a Live Voting Results Summary — the current standing as a percentage and a count for each choice, updating as votes arrive. An untested poll reads "No votes have been recorded for this poll."
NOTE: Is Anonymous does not make a poll anonymous in the data. The voter is recorded against every vote. Use it to reassure people, not to promise secrecy. Listed in Appendix 11.6.
IMPORTANT — students only. As with notices and events, only the student audience is honoured. A poll aimed at staff or parents collects no votes through the system. Staff must vote on the staff screen.
## 9.8 Helpdesk Tickets
Address: /workspace/helpdesk
| Section | Field | Notes |
| --- | --- | --- |
| Ticket Scope | Submitter |  |
|  | Category | Six categories. |
|  | Subject |  |
|  | Description |  |
| Internal Assignment | Priority | Low, Medium, High. |
|  | Status | Open, Assigned, In Progress, Waiting for User, Resolved, Closed. |
|  | Assigned Agent |  |

Table columns: Ticket Number, Submitter, Category, Priority, Status, Assigned Agent, Date Opened.
Actions: Edit, Assign, Reply, Close.
Ticket numbers are generated as HD-YYYY-NNNNN.
IMPORTANT — a defect that blocks this screen. The list shows Edit and a Create button, but the create and edit pages are not registered, so both links lead nowhere. Tickets cannot currently be raised from this screen. Listed in Appendix 11.6.
NOTE: Replies recorded against a ticket — including replies marked Hidden from parents/students — are never displayed anywhere, because there is no reply view. There is no parent or student ticket surface at all.
## 9.9 Kairo CORE Messages
Address: /workspace/platform-inboxes
The school's inbox for messages from the platform operator — support replies and service notices. It is read-only from the school's side.
## 9.10 Communication — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| Parents never received my notice | Only the student audience is delivered. There is no parent panel. | Send a WhatsApp message. See Section 9.11. |
| A staff poll collected no votes | Only the student audience is delivered. | Staff vote on the staff screen. |
| A staff-only notice or event is visible to students | Events made on the Schedule page lose their audience. | Create the event under Events instead. |
| "Requires Acknowledgement" produced no replies | Nothing records acknowledgements. | Track responses yourself. See Section 9.2. |
| A restricted notice's PDF is downloadable by anyone | Attachments are served from public storage. | Do not attach confidential files. See Section 9.2. |
| A restricted campus resource downloaded anyway | Eligibility is not enforced. | See Section 9.6. |
| A confidential chat thread was read by other staff | Membership is not enforced. | Use a channel outside the system. See Section 9.5. |
| A message rendered as a red alert | The text began with SYSTEM BULLETIN:. | Edit the message text. |
| A helpdesk ticket cannot be created | The create and edit pages are not registered. | Recorded in Appendix 11.6. |
| A helpdesk reply is invisible | Replies are never displayed. | Communicate by email instead. |
| A ticket number collided | Numbers are generated by counting. Harmless; retry. | See Appendix 11.6. |
| No reminder arrived | The scheduler only covers tasks and events with a reminder time. | Set a reminder time. See Section 9.4. |
| An overdue loan never turned up in the helpdesk | Nothing routes overdue loans there. | Use the Overdue Books report. |

## 9.11 Getting a Message to Parents
Because there is no parent portal, use the channel that parents actually read:
| Channel | How |
| --- | --- |
| WhatsApp | Homework → Share to WhatsApp. Builds the message for you. |
| SMS | Out passes send OTP SMS to the first guardian. |
| Email | Payment approvals and rejections email the guardian automatically. |
| Student portal | Notices reach students, who can show them at home. |


## 9.12 The Website Module
Go to: Website → Website Addresses: /workspace/website-templates, /workspace/cms-websites, /workspace/cms-pages, /workspace/cms/content, /workspace/cms/builder/{id}
IMPORTANT: The Website module is available to the System Administrator and the School Administrator only. Teachers and other staff roles cannot open it, and librarians cannot either. See Section 3.6.
## 9.13 Choosing a Template
Address: /workspace/website-templates
Ten ready-made designs ship with the system. Each arrives pre-built with its own pages and sections.
| Template | Character |
| --- | --- |
| Heritage Editorial | Serif, traditional, suits a long-established school. |
| Cinematic Immersive | Full-bleed imagery, suited to a school with strong photography. |
| Modern Vibrant | Bright and rounded, suits a junior school. |
| Minimalist Academic | Restrained and typographic. |
| Community Warm | Warm palette, welcoming. |
| Coastal Fresh | Light and airy. |
| Playful Garden | Playful, for pre-primary. |
| Emerald Heritage | Deep green, traditional and formal. |
| Neon Frontier | Bold and contemporary. |
| Sunset International | Warm international feel. |

Each shows its name, subtitle, description, colour palette and fonts before you choose. Choosing a template activates it for the school's live site.
NOTE: Choosing a template seeds it as a shadow site for your school. This happens the first time you open the builder, so the page takes a moment longer the first time.
## 9.14 Pages
Address: /workspace/cms-pages
| Field | Notes |
| --- | --- |
| Title | Required. The slug is generated from it as you type. |
| Slug | Required, and unique within your site. |
| Published |  |
| Set as Homepage | Only one page at a time. |
| (Site) | Assigned automatically to your live website. |

Table columns: Title, Slug, Homepage, Published, Last Modified.
Actions: Edit and Open Visual Studio, which launches the builder for that page.
TIP: Page slugs are unique per website, not across the platform, so two schools can each have /home, /about and /admissions. This is deliberate.
## 9.15 The Visual Builder
Address: /workspace/cms/builder/{pageId}
The builder lays a page out as a stack of blocks, each editable in place.
### The block palette — 23 block types
Content blocks
Hero, Kinetic Reveal Heading, Scroll Highlight Text, Depth Text, Marquee Ticker, Principal Welcome, About Section, Academics Grid, Features Grid, Statistics, Team Directory, Testimonials, FAQ Accordion, Dynamic News, Events Calendar, Gallery, Orbit Gallery, Coverflow Carousel, Cylinder Carousel, Cinematic Scroll, Logo Cloud, CTA Banner, Video Embed, Contact Map, Admissions Block, Preview Block.
Groups within each block: content, layout, appearance, animation, responsive and advanced.
Field types the editor understands: text, textarea, rich text, URL, image, image list, list, select, colour, number, range, toggle and group.
TIP: Start from a template rather than from an empty page. The templates already show the sections a school website needs, in a proven order.
## 9.16 How the Public Site Works
Once you publish, the pages are served from your own subdomain.
| URL | What it serves |
| --- | --- |
| https://Tinway.yourdomain.co.zw/about | The page whose slug is about. |
| https://Tinway.yourdomain.co.zw/ | Your homepage. |
| https://Tinway.yourdomain.co.zw/sitemap.xml | A sitemap generated from your published pages. |
| https://Tinway.yourdomain.co.zw/robots.txt | A robots file for your school. |
| https://Tinway.yourdomain.co.zw/_cms-preview/{slug} | A private preview. Requires you to be signed in. |
| Apply Online | A working application form on the public site. |
| Contact | A working contact form. |

Panel addresses — /workspace, /student, /platform — are reserved and are never swallowed by the public site.
IMPORTANT: The site's statistics block reports "0+ Certified Faculty" on the public page, because the count looks for a role value the system does not use. This is a display defect only. Do not publish the statistics block until it is fixed. Listed in Appendix 11.6.
TIP: Every school site gets its own sitemap and robots file automatically. You do not need to generate or upload either.
## 9.17 Website — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| I cannot see the Website module | Only System and School Administrators have it. | See Section 3.6. |
| The public site shows "0+ Certified Faculty" | A counting defect. | Do not publish the statistics block. See Section 9.16. |
| The builder is slow the first time | The ten templates are being seeded. | Wait; it is faster afterwards. |
| My page does not appear publicly | It is not published, or the slug is not the one you expected. | Check Published and the slug. |
| Two pages have the same slug | Not possible — slugs are unique per site. | Rename one. |
| /workspace shows my public page | It cannot — those paths are reserved. | Use the panel path. |
| A block shows placeholder images | The placeholders are bundled illustrations, not photographs. | Upload your own images. |
| The preview link asks me to sign in | Previews are deliberately private. | Sign in, then preview. |


## 9.18 Reports & Intelligence Structure
| Category | Pages |
| --- | --- |
| Dashboard | Reporting Dashboard |
| Reports | Report Generator, Generated Reports, Analytics Explorer |

## 9.19 The Reporting Dashboard
Address: /workspace/reporting-dashboard
The three figures across the top:
| Figure | What it counts |
| --- | --- |
| Total Compiled Reports | Every report run stored in your archive. |
| Saved Custom Templates | Custom layouts you have configured for re-running. |
| Automated Distribution Tasks | Active recurring email or notification tasks. |

Below them: your pinned templates, your recent reports, your active schedules and your most-used templates.
## 9.20 The Report Generator
Address: /workspace/report-generator-page
Pick a preset card, then refine it.
| Section | Field | Notes |
| --- | --- | --- |
| Basics | Report Name | For example Q2 Outstanding Defaulters List. |
|  | Output Format | See below. |
| Data | Data Source | One or more datasets. At least one is required. |
|  | Columns | Choose the fields to show. |
|  | Joins | Bring in related data. |
|  | Filters | Source, Field, Operator, Value, and Logic (all/any). |
|  | Group Rows By |  |
|  | Totals | Aggregate, Source, Field, Label. |
|  | Sort Order | Source, Field, Direction. |
|  | Charts | Chart Type, Chart Title, Category Axis, Data Series, Series Label, Colour. |
|  | Presentation | Page Orientation (portrait or landscape). |
|  | Sharing Scope | Private (only me), Department, Whole school. |
|  | Accent Color |  |
|  | Header Text |  |
|  | Footer Text |  |
|  | Show Institution Logo | On by default. |
|  | Show Signature Block | On by default. |
| Schedule | Schedule This Report | Tick to automate it. |
|  | Schedule Name |  |
|  | Frequency | Daily, Weekly, Monthly, Quarterly, Yearly. |
|  | Distribution | Email recipients, In-app notification, or both. |
|  | Recipient Emails | Type each address and press Enter. |

### Output formats
| Format | What you get |
| --- | --- |
| PDF document | Formatted, signature block, ready to file. The default. |
| CSV spreadsheet | Raw rows for Excel or Sheets. |
| Excel workbook (.xls) | A native workbook to keep editing. |
| JSON data | For integrations and dashboards. |
| Print view | Opens the browser print dialogue. |
| Clipboard (HTML) | Rich text to paste into an email. |

TIP: Schedule the reports you need every month — defaulters, overdue invoices, low stock, attendance — and let the system email them. It replaces a Friday morning of manual report building.
## 9.21 The 29 Report Presets
These are the reports the generator offers, grouped by category.
### Students (3)
| Preset | What it reports |
| --- | --- |
| Student Register by Class Stream | Every learner, with admission number, name, gender, class, boarding status and status. |
| Student Headcount by Status | How many learners are active, inactive or on hold. |
| Student Medical Profiles | Blood groups, allergies and chronic conditions. Treat the output as confidential. |

### Academics (2)
| Preset | What it reports |
| --- | --- |
| Academic Performance Summary | Performance by subject and class. |
| Student Marks Ledger | Every mark recorded, by student, subject and assessment. |

### Attendance (2)
| Preset | What it reports |
| --- | --- |
| Attendance by Class Stream | Attendance rates per class. |
| Attendance Monthly Trend | Month-by-month attendance. |

### Finance (4)
| Preset | What it reports |
| --- | --- |
| Fee Defaulters (Cross-Module) | Students with an outstanding balance. |
| Overdue Invoices | Invoices past their due date. |
| Monthly Revenue Collections | Money collected month by month. |
| Payment Receipts Ledger | Every receipt issued. |

### Admissions (2)
| Preset | What it reports |
| --- | --- |
| Admissions Pipeline | Applications by stage. |
| Admission Applications (detail) | Every application, field by field. |

### Timetable (1)
| Preset | What it reports |
| --- | --- |
| Timetable by Teacher | Each teacher's teaching load. |

### Library (2)
| Preset | What it reports |
| --- | --- |
| Book Circulation | Loans issued. |
| Overdue Books | Loans past their due date. |

### Hostel (1)
| Preset | What it reports |
| --- | --- |
| Hostel Occupancy | Bed occupancy per hostel. |

### Clinic (1)
| Preset | What it reports |
| --- | --- |
| Clinic Visits Monthly | Clinic visits month by month. |

### Inventory (5)
| Preset | What it reports |
| --- | --- |
| Inventory Stock Status | Quantity on hand per item. |
| Low Stock Items | Items at or below their reorder level. |
| Fixed Assets Register | The asset register. |
| Asset Maintenance Log | Servicing history. |
| Purchase Orders | Orders placed with suppliers. |

### HR (4)
| Preset | What it reports |
| --- | --- |
| HR Leave Requests | Leave by employee and type. |
| Payroll Summary by Department | Payroll cost per department. |
| Employee Register | The staff list. |
| Staff Disciplinary Cases | Cases opened, by severity and status. Confidential. |

### Administration (1)
| Preset | What it reports |
| --- | --- |
| Helpdesk Tickets | Ticket volume and status. |

### Communication (1)
| Preset | What it reports |
| --- | --- |
| System Audit Trail | Audit entries across the system. |

## 9.22 Generated Reports
Address: /workspace/generated-reports
Every report you run is archived here.
Table columns:
| Column | Notes |
| --- | --- |
| Report | The name, with who generated it and when underneath. |
| Status | processing, completed, failed. |
| Format | The format badge. |
| Records | How many rows the report contains. |
| Accuracy | See below. |
| Execution | How long it took, in milliseconds. Toggle it on if you need it. |

### The Accuracy badge — the most useful column
| Badge | Meaning |
| --- | --- |
| Not verified | You have not re-checked this report against live data. |
| Verified | Re-checked, and it still matches live data, with the time of the check. |
| Source changed | The underlying data has changed since the report was compiled. Regenerate it. |

IMPORTANT: Before you take a report to the board, press Verify data accuracy on it. If the badge turns to Source changed, the figures are out of date and you must regenerate. This badge exists precisely to stop an old number being quoted as a current one.
Actions: Download, Verify data accuracy, Delete.
## 9.23 Custom Report Templates
Address: /workspace/enterprise-report-templates
Save a report layout for reuse.
| Column | Notes |
| --- | --- |
| Template Reference | The name. |
| Domain Module | Which module it draws from. |
| Alignment | Portrait or landscape. |
| Pinned | Keep it to the top. |
| Favorite | Mark it. |
| Created By |  |
| Date Saved |  |

Action: Compile PDF — render the saved layout immediately.
## 9.24 Analytics Explorer
Address: /workspace/analytics-explorer
Twenty-one analytics cards in four groups.
### Academics & Student Setup (7)
| Card | What it shows |
| --- | --- |
| Admissions Analytics | Applications, approved admissions, pending review. |
| Academic Analytics | Subjects, class streams and form levels. |
| Student Analytics | Total, male and female scholars. |
| Attendance Analytics | Student attendance records and staff check-ins. |
| Examination Analytics | Formal exam sessions. |
| Assessment Analytics | Homework and tests mapped, and total sCOREd marks. |
| LMS Analytics | Coursework and submissions. |

### Welfare (5)
| Card | What it shows |
| --- | --- |
| Boarding Analytics | Active hostels, rooms, allocated beds. |
| Health Analytics | Medical profiles, outpatient check-ins, ward admissions. |
| Library Analytics | Catalogue titles and physical copies. |
| Knowledge Analytics | Registered repository assets. |
| Communication Analytics | Published notices and helpdesk tickets. |

### Financials (6)
| Card | What it shows |
| --- | --- |
| Finance Analytics | Invoices issued and payments logged. |
| HR Analytics | Staff on board, disciplinary records, pending leave, contracts. |
| Payroll Analytics | Salary grades and staff loans. |
| Inventory Analytics | Master SKUs, consumable lines, returnable lines. |

### Systems (3)
| Card | What it shows |
| --- | --- |
| Subscription Analytics | Your subscription position. |
| Website Analytics | Site pages. |
| System Analytics | Audit trail entries. |

IMPORTANT — two things to know about these cards. First, Communication Analytics always reads zero for notices and helpdesk tickets, because it queries table names that do not exist; use the Notices and Helpdesk lists instead. Second, four cards — Website, Inventory Assets, Procurement and System — currently all display the same audit-trail chart rather than their own data. Treat the numbers on those four with suspicion and use the underlying screens. Listed in Appendix 11.6.
## 9.25 Who Can Use Reports
| Role | Reports & Intelligence |
| --- | --- |
| System Administrator | Full access |
| School Administrator | No access |
| Accounts / Finance | No access |
| Teaching Staff | No access |

IMPORTANT: Unlike almost every other module, no standard role grants Reports & Intelligence to the school — it is effectively System Administrator only. If your head or bursar needs reports, give them the access deliberately as described in Section 3.7.
NOTE: Members of the FIN and Clinic departments receive report access through their department preset, independently of their role.
NOTE: Reporting Workflows is reachable by its web address but is not linked from any menu, even though it appears in the permission editor. See Appendix 11.6.
## 9.26 Reports — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| I cannot see the Reports module | No standard role grants it. | Grant it as an administrator. See Section 9.25. |
| Reporting Workflows is not in the menu | It is deliberately unlinked. | Reach it by URL. See Section 9.25. |
| A report shows an old figure | The source data changed after it was compiled. | Check the Accuracy badge and regenerate. |
| A report failed | Status reads failed. | Check your filters and run it again. |
| Notification Analytics reads zero | Wrong table names. | Use the Notices list. See Section 9.24. |
| Four system cards show identical charts | They are not wired to their own data. | Use the underlying screens. See Section 9.24. |
| The generator refuses to run | No data source chosen. | Choose at least one Data Source. |
| A scheduled report never arrived | Check the schedule is active and the recipients are correct. | Review Automated Distribution Tasks on the dashboard. |
| A report is missing a column | It is not in the preset's selected fields. | Edit the report and add the column. |
| Excel refuses to open the file | The workbook was generated for the older format. | Try CSV instead. |
| Medical or disciplinary data was exposed | Those presets contain confidential records. | Restrict the sharing scope and delete the file after use. |

In this phase you learned: the four Communication Center groups; notices with their four statuses, five priorities and four display styles, and the two cautions about acknowledgements and attachment privacy; events and the audience defect in the Schedule page; tasks, their three recurrence options and the single reminder command; chat thread types and the membership defect; campus resources and the unenforced eligibility; polls with their live standing and the cosmetic anonymity; helpdesk tickets, their six statuses and the defect that blocks creation; platform messages; every communication troubleshooting case; four channels for reaching parents; the ten website templates and how to choose one; pages, slugs and per-site uniqueness; the visual builder's 23 block types and 6 setting groups and 13 field types; the public URLs including sitemap, robots, preview and the working application and contact forms; who can build a website; the reporting dashboard's three headline figures; the report generator's every field and six output formats; all 29 report presets by category; generated reports and the critical Accuracy badge; custom report templates; the 21 analytics cards in four groups and the two defects among them; who can actually use reports and why it surprises people; and every reports troubleshooting case.

# Phase 10 — System Administration, Subscription & Billing, the Student Portal and the Platform Panel
This chapter covers the parts of the system that govern the system itself: how a school is configured, how it pays for access, what a student sees, and what the platform operator who runs Kairo CORE sees.
## 10.1 The System Administration Structure
| Category | Pages |
| --- | --- |
| Overview | Administration Dashboard |
| System Settings | System Settings, Email Configuration |
| User Management | User Accounts, Roles, Departments |
| Audit & Compliance | Audit Log, Data Export |

## 10.2 The Administration Dashboard
Address: /workspace/administration-dashboard
Your entry point for the whole configuration surface. It links into System Settings, User Management and the Audit tools.
## 10.3 System Settings
Address: /workspace/system-settings-page
Settings are grouped into tabs. They apply to your school only, never to another school.
### Tab: Modules
This is the single most powerful screen in the system. Every module has a master switch and each page inside it has its own switch.
Enable All Modules and Disable All Modules apply to every switch on the tab at once.
| Module | Master switch | Page switches |
| --- | --- | --- |
| Admissions & Online CRM | Enable Admissions Module | Applications List, Admissions Kanban Pipeline, Admission Settings |
| Students Directory | Enable Students Module | Student Directory & Cards, Guardians & Contacts Hub, Student Promotion Workflow |
| Academics & Curriculum | Enable Academics Module | Courses / Forms Management, Classes / Streams, Academic Operations Center, Curriculum Dashboard |
| Exams & Grading | Enable Exams & Grading | Assessment Workspace, Marks Entry Ledger, Academic Report Cards, Exams Analytics & Trends |
| Attendance Tracking | Enable Attendance Module | Staff Attendance, Attendance Reports & Analytics |
| HR & Payroll | Enable HR & Payroll | Staff Directory & Contracts, Payroll & Salary Grades, Leave Requests & Staff Loans |
| Boarding & Welfare |  | Accommodation and welfare pages |
| Health & Safety (Clinic) |  | Medical records and clinic visits |
| Library Circulation |  | Catalogue, circulation, knowledge |
| Inventory & Procurement |  | Stock, procurement, fixed assets |
| Finance & Accounting |  | Dashboards, billing, expenses, accounting |
| Communication Center |  | Notices, events, chat, helpdesk |
| Visual Website & CMS |  | Website builder, news |
| LMS |  | Homework |
| Knowledge Hub |  | Repository |
| Reports & Intelligence |  | Reports and analytics |
| System Administration |  | Users, roles, settings |
| Subscription & Billing |  | Your plan |

IMPORTANT: Turning a module off removes it from the navigation and makes it inaccessible to every user, including you. Before you switch anything off, check whether anything depends on it — turning off Exams removes Marks Entry, which removes the ability to record marks at all.
### Tab: Branding
| Setting | What it does |
| --- | --- |
| Visual Theme Design | The colour scheme of the whole application. |
| System Typography Font | The interface font. A live preview is shown. |
| Logo Badge Upload | Your school logo. |
| Browser Tab Favicon | The small icon in the browser tab. |
| Logo Badge Scale Height | Logo size. |
| Logo Header Opacity | Logo transparency. |
| Background Wallpaper | The dashboard wallpaper. |
| Wallpaper Opacity |  |
| Wallpaper Scale |  |

### Tab: Institution Profile
These details appear on the branded sign-in page, on printed documents and on your public website.
| Setting |
| --- |
| School Name |
| Short Name |
| Ministry Registration Number |
| School Type — Primary, Secondary, Combined / Comprehensive, Tertiary College |
| Phone Number |
| Contact Email |
| Physical Address |
| Established Year — shown on the sign-in page |
| Principal / Head Name — shown on the sign-in page |
| Website URL — leave empty to use your automatically assigned address |

### Tab: Footer & Branding
| Setting | Notes |
| --- | --- |
| Powered-By Text | The clickable footer label. |
| Powered-By Link (URL) | Where it links to. Empty shows plain text. |

### Tab: Banking & Payments
| Setting | Notes |
| --- | --- |
| Bank Account Printed on Student Invoices | Choose which of your accounts prints by default. Empty prints all active accounts. Accounts themselves are managed under Finance → School Bank Accounts. |
| EcoCash Merchant Pin Code | For example *153*1*55326*AMOUNT#. Use {AMOUNT} to let parents pay any amount. |
| Billing Frequency | Termly (one invoice per term) or Monthly (the term fee is split across the calendar months of the term, producing one invoice per month, numbered like INV-2026-00001-M03). |

IMPORTANT — the currency trap. Primary Ledger Currency defaults to USD, not ZiG. A Zimbabwean school must change this to ZiG before invoicing, or every invoice will be denominated in dollars. See Section 10.4.
### Tab: Authentication & Security
| Setting | Options |
| --- | --- |
| Minimum Password Length | 8, 10 or 12 characters. Defaults to 8. |
| Auto-Session Timeout | 15 minutes, 30 minutes, 60 minutes or 2 hours. Defaults to 60. |
| Enforce Multi-Factor Authentication (MFA) | Off by default. |
| Restrict System Login Access by IP Range | Empty allows all IP addresses. |

TIP: The 60-minute session timeout applies to every member of staff. If your bursar is interrupted frequently, they will be signed out mid-entry. Ten or twelve characters is a cheap, immediate improvement in security.
### Tab: System Preferences
| Setting | Notes |
| --- | --- |
| School Operating & ERP Language | English, Shona, Shwahili, Français, Português, Español. Used for administration and all printed documents. |
| Public Website & Portal Language | Set separately, for the public site and the portal. |
| Primary Ledger Currency | See the trap above. |
| Student ID Prefix Pattern | Defaults to STU-{YEAR}-{SEQ}. |
| Invoice Number Pattern | Defaults to INV-{SEQ}. |

### Tab: Legal & Terms
| Setting | Notes |
| --- | --- |
| School Terms of Service Content | The terms registrants must review and agree to. A sensible default is provided and is designed to be replaced with your own. |

### Tab: Email Configuration
Configures how your school sends email, separately for each category — so admissions mail, billing mail and general notices can each come from the right address with the right reply-to.
IMPORTANT: The sender address must be your school's own address. It can never reuse the platform's sending account. Changes made here are the same configuration as the dedicated Email Configuration page — there is only one underlying configuration per category, so setting it in either place is equivalent.
Email branding, used in the header and footer of every outgoing email:
| Setting |
| --- |
| Email logo |
| Company / institution name |
| Address |
| Phone number |
| Contact email address |

### Tab: Notifications
| Setting | Notes |
| --- | --- |
| Email approvers when a new user registers | On by default. In-app notification is always delivered; this toggle governs only the additional email. Turning it off reduces noise when registrations are frequent. |

### Tab: Feature Flags
Individual features, on or off, hidden from navigation and inaccessible to everyone when off.
UI & Experience: Dark Mode, Onboarding Wizard, ID Card Designer
CORE Modules: Attendance Tracking, Exam Management, Fee Management, Payroll Module, Library Module, Hostel Module, Clinic Module, Inventory Module, LMS Module, Knowledge Base, Report Generation, Website Builder
Communication & Notifications: Communication Center, Email Notifications, SMS Notifications, WhatsApp Notifications
Portals & Access: Student Portal, Parent Portal, Staff Portal, Online Admissions, Two-Factor Authentication, Custom Role Builder
Data & Integrations: CSV Export, PDF Export, Data Import, Advanced Analytics, External API Access, Bulk Operations, Print Reports, Academic Calendar, Visual Timetable Builder
IMPORTANT: Two flags describe things that do not exist. The Parent Portal flag defaults to off because there is no parent portal in this build. Advanced Analytics and External API Access similarly have no working implementation. Do not enable a flag expecting a feature to appear. See Appendix 11.6.
TIP: Each flag inherits a platform default, and your override applies only to your school.
## 10.4 Email Configuration in Detail
Address: /workspace/email-configuration
The same configuration as the Settings tab, opened full-page so you can work through every category in one place. Use whichever screen you prefer.
## 10.5 User Accounts, Roles and Departments
Covered fully in Chapter 3. The three screens are at:
| Screen | Address |
| --- | --- |
| User Accounts | /workspace/user-accounts |
| Roles | /workspace/custom-roles |
| Departments | /workspace/departments |

## 10.6 The Built-In Roles
| Role | Modules | What it is for |
| --- | --- | --- |
| System Administrator | Everything | Runs the whole platform for this school: every module, every page, every operation, plus accounts, roles and settings. |
| School Administrator | Communication, Inventory, Library, Admissions, Students, Academics, Website | Runs the day-to-day school. |
| Teaching Staff | Academics (plus three Exams areas) | Teaches classes. Cannot publish report cards. |
| Accounts / Finance | Finance | Owns the money side. |
| HR | HR & Payroll | Owns the staff record. |
| Health | Health & Safety | Keeps health records and logs clinic visits. |
| Procurement | Inventory | Runs purchasing and stock. |
| Librarian | Library | Runs the library. |
| Houseparent | Boarding & Welfare | Looks after boarders. |
| Supporting Staff | None | General duties, own day and tasks only. |
| Student | None | Uses only the student portal. |

NOTE: Students are registered through the student portal, never as employees, so the Student role is deliberately absent from the employee registration form.
NOTE — which role has Website. The Website module belongs to the School Administrator, not the Librarian. It was moved deliberately, because whoever holds Templates & Design is maintaining the school's public face.
IMPORTANT: The Teaching Staff role is given the three Exams & Grading areas it needs — Assessment Center, Grading & Marks Management and Reports & Academic Publishing — and is specifically denied exams.publish_to_student_portal. This is intentional. See Section 6.8.
## 10.7 The Audit Log
Address: /workspace/system-audit-logs
Every administrative action is recorded.
Table columns: Timestamp, Administrator, Action Performed, Operational Module, IP Source, Outcome.
Opening an entry shows:
| Field |
| --- |
| Timestamp |
| Administrator Account — reads System Autonomic Engine for unattended jobs |
| Action Performed |
| SaaS Module Segment |
| Request IP Source |
| Outcome State — a green or red badge |
| Original Database State |
| Updated / Changes Saved |

That before-and-after pair is what makes the log useful: it shows not only that something changed, but exactly what the value was and what it became.
Filters: module, administrator, IP source, and a Logged From / Logged Until date range.
Actions: Export Filtered CSV, Export Print View.
TIP: Export the audit log to CSV at the end of each term and keep it. If a fee, a mark or a role assignment is ever disputed, this is the record that settles it.
## 10.8 Data Export
Address: /workspace/tenant-data-export-page
Choose a data module and download it as CSV.
| Module | What you get |
| --- | --- |
| Student Directory | Every learner record. |
| Staff Directory (HR) | Every employee record. |
| Billing Invoices Ledger | Every invoice. |
| Student Attendance Logs | Every attendance record. |
| Staff Daily Attendance Logs | Every staff attendance record. |
| Form / Class Grade Levels | Your courses. |
| Class Stream Allocations | Your classes. |
| Academic Curriculum Subjects | Your subjects. |
| Clinic Outpatient Visits | Every clinic visit. Confidential. |
| Library Books Registry | Your catalogue. |
| Inventory Stock Catalog | Your stock. |

Important details:
- Exports are always limited to your own school. There is no way to export another school's data from this screen.
- The file is streamed, so a large export — years of attendance logs — does not exhaust memory.
- The file name is export_{table}_{timestamp}.csv.
- Column headings are the database column names, tidied into words. You will need to tidy them yourself before sharing the file.
CAUTION: Exports include columns you may not expect. A student directory export and a clinic export both contain personal and health data. Treat an exported file as confidential and delete it when you no longer need it.
## 10.9 Administration — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| A module vanished from the menu | Its module switch is off. | Turn it back on. See Section 10.3. |
| I cannot record marks any more | Turning off Exams removed Marks Entry. | Turn Exams back on and re-enable Marks Entry Ledger. |
| Invoices show dollars | The currency default is USD. | Set Primary Ledger Currency to ZiG. See Section 10.4. |
| Every receipt number changed | You changed the invoice number pattern. | Restore the previous pattern. |
| I enabled a flag and nothing appeared | The flag describes an unimplemented feature. | See Section 10.3. |
| Emails are rejected as spoofed | The sender address is not your school's own. | Use your own domain. See Section 10.3. |
| Email Configuration and Settings disagree | They are the same configuration. | Set it in either place. |
| A bursar is signed out mid-entry | The session timeout is 60 minutes. | Lengthen the timeout, or see Section 10.3. |
| The parent portal flag is on but there is no parent portal | No parent portal exists in this build. | Use WhatsApp. See Section 9.11. |
| A CSV export column name is unreadable | Headings are database column names. | Tidy them in a spreadsheet. |
| An export is missing a school I can see | Deliberate — exports are tenant-scoped. | Expected. |


## 10.10 Subscription & Billing
Go to: Subscription & Billing Addresses: /workspace/saas-billing-overview, /workspace/saas-hub, /workspace/saa-s-my-subscriptions
NOTE: This module also appears as a tab of the Admissions module. It is the same screen, reachable from two places.
## 10.11 The Billing Overview
Address: /workspace/saas-billing-overview
### What the screen shows
| Group | What it holds |
| --- | --- |
| Access Status | Whether your workspace is licensed. |
| Billing Amount | What you owe for the current period. |
| Licensing Expiry Date | When access lapses. |
| Prepaid Credit Balance | Lump-sum advance payments, deducted automatically at renewal. |
| Active Licensing Invoices | Every licensing invoice, with Download Invoice PDF. |
| Official Payments Receipts | Every receipt, with Download Receipt PDF. |
| Bank Settlement Audits | Manual bank deposits you have submitted. |
| Platform Banking Details | Bank name, account name, account number, branch code. |

### Paying
| Method | How |
| --- | --- |
| Pay Online via Paynow | Local EcoCash wallets, OneMoney, Zimswitch debit cards, and USD Visa/Mastercard. Choose the currency, then proceed to secure checkout. |
| Manual Bank Settlement | Pay into the school account, then record: Account Name, Account Number, Branch Code, Bank Name, Amount Paid, Payment Date, Reference, and attach proof. |
| Prepaid Credit Balance | Advance payments, deducted automatically at renewal. |

Actions: Generate Licensing Invoice, Create New Invoice Statement.
IMPORTANT: The screen says "Instantly renew access" for an online payment, but in practice the payment is then verified before access extends. A payment that has not cleared does not renew anything. Watch the payment status.
## 10.12 My Subscription
Address: /workspace/saa-s-my-subscriptions
| Column | Notes |
| --- | --- |
| Plan Name |  |
| Billing Period | A badge. |
| Status |  |
| Renewal Expiration Date |  |

Action: Go to Billing Portal.
### What the subscription states mean
| State | Meaning |
| --- | --- |
| Trialling | Inside the plan's trial days. |
| Active | Paid and current. |
| Expired | Past the end date. There is also a grace period recorded separately. |
| Days remaining | Counted from the end date. |

## 10.13 Subscription — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| A newly registered school has no subscription | Subscriptions are not created automatically. | The platform operator creates one. See Section 10.16. |
| I paid but access has not extended | The transaction has not been verified. | Wait for verification, or ask the platform operator. |
| My plan shows as expired but I have paid | The payment cleared after the end date. | Renew from the billing overview. |
| A prepaid credit was not deducted | Credit is deducted on the renewal date, not immediately. | Check the renewal date. |
| I cannot find the billing screens | The Subscription & Billing module is switched off. | Turn it on. See Section 10.3. |
| An invoice PDF will not open | It has not been generated yet. | Use Generate Licensing Invoice. |


## 10.14 The Student Portal
Address: https://Tinway.yourdomain.co.zw/student
IMPORTANT: Students sign in here with their own credentials and can never reach the staff workspace. The check happens at sign-in, not merely by hiding links. Conversely, staff are refused the student portal unless they have a linked student record. See Section 6.10 for the page-by-page detail.
| Sidebar group | Page | Address |
| --- | --- | --- |
| — | Dashboard | /student |
| Academics | My Results | /student/my-results |
| Academics | Performance Analytics | /student/performance-analytics |
| Academics | My Timetable | /student/my-timetable |
| Academics | Digital Assessments | /student/digital-assessments |
| Academics | My Homework | /student/homeworks |
| Fees & Payments | My Fees | /student/my-fees |
| Library | Library | /student/library |
| Communication | Notices | /student/notices |
| Communication | Polls & Surveys | /student/polls-surveys |
| Communication | My Schedule | /student/my-schedule |
| Boarding | My Residence | /student/my-residence |
| My Account | My Profile | /student/my-profile |
| Learning | Gamification Profile | /student/gamification-profile-page |

Additional working pages: an assessment-taking page, an attempt-result page, a report-card PDF download, a fee checkout page, and the PayNow redirect and sandbox-callback handlers.
### My Fees
The fee screen offers My Fee Invoices and a payment form:
| Field |
| --- |
| Invoice No, Issued, Due Date, Amount, Balance, Status |
| A period filter: All Terms (Full Year) |
| Outstanding Balance |
| Pay Online — via PayNow |
| Bank Deposit — "Made a bank deposit? Submit the details and attach your proof of payment for the finance office to verify." with Account number you paid from, Date, Notes (Optional), and the deposit slip |
| Method, Payment Date, Receipt Number on submitted payments |

Statuses appear as PAID, APPROVED, Not Enrolled, and a Carried Forward Credit line.
### My Profile
The student sees their own details. The photograph locks once approved and cannot be changed afterwards. See Section 3.9.

## 10.15 The Platform Panel — /platform
The platform operator's panel. A completely separate application, at the platform domain, with its own login. No school staff can reach it.
Navigation groups: Overview, Tenants, Billing & Subscriptions, Intelligence, Operations, Communication, Platform Control.
### Pages
| Page | Address |
| --- | --- |
| Dashboard | /platform |
| Platform Intelligence Dashboard | /platform/platform-intelligence-dashboard |
| Platform Backup Manager | /platform/platform-backup-manager |
| Platform Maintenance | /platform/platform-maintenance-page |
| Platform Settings | /platform/platform-settings-page |

### Resources
| Resource | Address | Group |
| --- | --- | --- |
| Schools (tenants) | /platform/schools | Tenants |
| Tenant Health | /platform/tenant-healths | Tenants |
| SaaS Plans | /platform/saa-s-plans | Billing & Subscriptions |
| School Subscriptions | /platform/school-subscriptions | Billing & Subscriptions |
| SaaS Invoices | /platform/saa-s-invoices | Billing & Subscriptions |
| Pending Payments | /platform/pending-payments | Billing & Subscriptions |
| SaaS Transactions | /platform/saa-s-transactions | Billing & Subscriptions |
| Platform Announcements | /platform/platform-announcements | Communication |
| Platform Templates | /platform/platform-templates | Communication |
| Platform Messages | /platform/platform-messages | Communication |
| Platform Audit Log | /platform/platform-audit-logs | Platform Control |

## 10.16 Approving a New School
This is the operator-side counterpart to the registration wizard in Chapter 2.
A SCHOOL REGISTERS            a pending school appears in
/platform/schools
│
▼
THE OPERATOR REVIEWS IT        region and registered date at a glance
│
▼
APPROVE & SEND ACTIVATION      activates the school and emails the
pending administrator an activation link
│
▼
THE ADMINISTRATOR ACTIVATES    sets a password and enters the workspace
│
▼
CREATE A SUBSCRIPTION          the operator creates the plan and
billing period

### School statuses and actions
| Status | Actions available |
| --- | --- |
| Pending Approval | Approve & Send Activation, Resend Activation Link |
| Active | Suspend Access, Impersonate |
| Suspended | Restore Access |

Filters: status, and region (Zimbabwe, South Africa, United States).
What approval does: activates the school, then emails the activation link to its pending administrator. If the school was flagged to generate demo data, that generation starts in the background and the data appears once it is ready.
IMPORTANT: If approval cannot find a pending administrator, or the activation email cannot be delivered, the screen says so explicitly. Use Resend Activation Link once the record is ready. This is a normal path, not a failure.
IMPORTANT — impersonation. Impersonate generates a secure link that signs the operator in as that school's administrator. It is the right tool for supporting a school that cannot get in, and it must never be used to look at a school's data casually. Every impersonation is written to the audit log.
## 10.17 How Tenancy Works
Every request to a school address resolves which school it belongs to.
Tinway.yourdomain.co.zw/workspace/students
│
▼
ResolveTenant middleware        reads the hostname, finds the matching
subdomain, and binds "current_tenant"
│
▼
EVERY QUERY                    is filtered to that school automatically

- The staff panel is at /workspace on your school's subdomain.
- The student portal is at /student on the same subdomain.
- The platform panel is at /platform on the platform's own domain.
- During development, tenant URLs use the pattern https://{tenant}.lvh.me/workspace/....
IMPORTANT: Access control fails closed. A role that is not granted a module cannot open it, and the module tabs are filtered to what you are entitled to see. You do not need to worry about a teacher stumbling into the bursar's screens — and if a user does see something they should not, that is a fault worth reporting.
NOTE: A few screens deliberately bypass the tenant filter internally in order to build a report. Those are security defects and are listed in Appendix 11.6. They do not affect normal day-to-day use.
## 10.18 What Happens When a Subscription Expires
1.	The subscription status becomes Expired and the days-remaining count reaches zero.
2.	A grace end date is recorded, giving a period to settle up.
3.	Access to the workspace is affected at renewal, and the billing overview shows the outstanding licensing fee as the amount required to preserve access.
4.	The school can settle at any time and continue; it does not need a new registration.
IMPORTANT: Expired does not mean your data is deleted. Nothing in this flow removes a school's records.
## 10.19 Platform — Problems and Solutions
| Symptom | Cause | Fix |
| --- | --- | --- |
| A registered school has no subscription | Subscriptions are not created automatically. | The operator creates one under School Subscriptions. |
| An activation email never arrived | Delivery failed, or no pending administrator existed. | Use Resend Activation Link. |
| Demo data has not appeared after approval | It is still generating in the background. | Wait; it appears when ready. |
| A school is locked out | Its access was suspended. | Restore Access. |
| School Records is empty | No school has registered. | Expected. |
| The platform panel says I am not authorised | Only platform administrators may enter it. | Sign in with a platform account. |
| I need to see why a school's screen misbehaves | Use Impersonate. | It is audited. Use it responsibly. |


## 10.20 Phase 10 Checklist
Before you consider configuration finished, work through this list.
| # | Task | Where |
| --- | --- | --- |
| 1 | Set the currency to your own. | Settings → System Preferences |
| 2 | Turn off every module you do not use. | Settings → Modules |
| 3 | Set the minimum password length to 12. | Settings → Authentication & Security |
| 4 | Lengthen the session timeout to 2 hours. | Settings → Authentication & Security |
| 5 | Fill in the Institution Profile. | Settings → Institution Profile |
| 6 | Upload your logo and favicon. | Settings → Branding |
| 7 | Replace the Terms of Service with your own. | Settings → Legal & Terms |
| 8 | Configure email per category. | Settings → Email Configuration |
| 9 | Set your default invoice bank account. | Settings → Banking & Payments |
| 10 | Decide termly or monthly billing. | Settings → Banking & Payments |
| 11 | Create roles for the jobs you actually have. | User Management → Roles |
| 12 | Create departments and assign members. | User Management → Departments |
| 13 | Confirm who may publish report cards. | Section 10.6 |
| 14 | Confirm who can run reports. | Section 9.25 |
| 15 | Export the audit log and keep it. | Audit & Compliance → Audit Log |
| 16 | Confirm your subscription is active. | Subscription & Billing |

In this phase you learned: the System Administration structure and dashboard; every System Settings tab — Modules with its master and page switches, Branding, Institution Profile, Footer, Banking, Authentication and Security, Preferences, Legal, Email Configuration, Notifications and Feature Flags; the two feature flags that describe features which do not exist; the currency trap; the audit log's before-and-after value display; the eleven data exports and their confidentiality warnings; the eleven built-in roles with their modules and summaries, why the Website module belongs to the School Administrator, and why teachers cannot publish; the billing overview, its three payment routes and the verification step; subscription states including trial, grace and days remaining; the student portal's fifteen pages with their addresses and the fee payment form; the platform panel's five pages and eleven resources in seven navigation groups; the school approval workflow with its three statuses and four actions, and the audit implications of impersonation; how subdomain tenancy resolves a request and fails closed; what happens when a subscription expires and what does not happen; a sixteen-item configuration checklist; and every administration, billing, student-portal and platform troubleshooting case.

# Phase 11 — Appendices
Five appendices: a glossary, a page index, a notification reference, a configuration reference, and a full list of known issues and limitations.
## 11.1 Glossary
Terms in alphabetical order, with the chapter that explains them properly.
| Term | Meaning | Chapter |
| --- | --- | --- |
| Academic Year | The school year. Report cards only offer terms of the active year. | 4 |
| Admissions Kanban | The visual pipeline a new application moves along from submitted to enrolled. | 5 |
| Analytics Explorer | 21 read-only analytics cards in four groups. | 9 |
| Assessment Type | A category of assessment with a maximum mark and a weight. | 6 |
| Assignment (LMS) | Homework set for a class stream. | 6 |
| Attendance Register | The per-class daily register of present, absent, late and excused. | 7 |
| Audit Log | Every administrative action, with before-and-after values. | 10 |
| Band | A range of percentages in a grading scale, such as B from 70 to 79.99. | 6 |
| Bed | One sleeping space in a hostel room. | 8 |
| Billing Frequency | Whether invoices are raised per term or per calendar month. | 10 |
| Blended Weights | The combination of assessment-type weights that produces a subject sCORE. | 6 |
| Boarding Status | Whether a learner sleeps at school. | 8 |
| Capability | One permission, named like finance.invoices.view. | 3 |
| Carry Forward | A balance moved from one term to the next, or credit applied later. | 7 |
| Class Stream | A class inside a level — Grade 3B. | 4 |
| Class Teacher | The teacher responsible for a class. | 4 |
| Clinic Visit | One attendance of the school clinic by a learner. | 8 |
| Course / Level | Grade 1, Grade 2, and so on. | 4 |
| Dashboard | The first screen each panel opens on. | 1 |
| Department | A group of staff with preset permissions. | 3 |
| Digital Assessment | A quiz published to the student portal. | 6 |
| Discipline Workflow | The internal process for managing a learner's behaviour. | 5 |
| Discount / Waiver | An amount removed from what a learner owes. | 7 |
| Double Declining | A depreciation method that front-loads the charge. | 8 |
| End of Term Report | The report card. | 6 |
| Enrolment | A learner's place in a class for an academic year. | 5 |
| Escalate (disciplinary) | To refer a case upward. | 7 |
| Examination | A formal assessment run by the school. | 6 |
| Fee Category | A grouping such as Tuition or Boarding. | 7 |
| Fee Structure | A category, an amount, and the level/class/term it applies to. | 7 |
| Fixed Asset | Durable equipment recorded separately from stock. | 8 |
| Form Level | Another name for Course / Level. | 4 |
| Generated Report | An archived, already-run report. | 9 |
| Grading Scale | The mapping from a percentage to a grade. | 6 |
| Guardian | A parent or other contact for a learner. | 3 |
| Hostel | A boarding house. | 8 |
| Houseparent | The role that looks after boarders. | 10 |
| Impersonation | Signing in as a school's administrator to support them. | 10 |
| Invoice | A bill issued to a learner. | 7 |
| Knowledge Hub | The school's shared repository of schemes, policies and media. | 8 |
| Learning Outcome | What a learner is expected to achieve. | 6 |
| Library Circulation | Issuing and returning books. | 8 |
| Manual Marking | Grading written responses by hand. | 6 |
| Marks Entry | The fast screen for typing marks for a class. | 6 |
| Medical Alert | An allergy or chronic condition shown on the register. | 8 |
| Module | One area of the system — Finance, Academics and so on. | 1 |
| Module Tab | The second bar listing a module's pages. | 1 |
| Modules (settings) | The switches that turn areas of the system on and off. | 10 |
| Out Pass | Authorisation for a boarder to leave the premises. | 8 |
| Panel | One of the four separate applications: staff, student, platform, imports. | 1 |
| PayNow | Mobile-money payment. | 7 |
| Payroll Run | One calculation and payment of salaries for a pay period. | 7 |
| Percentage | A mark expressed against the assessment type's maximum. | 6 |
| Permission | The right to view or perform something. | 3 |
| Position | A learner's rank within a class for a subject. | 6 |
| Prescribed Medication | Medicine recorded on a clinic visit. | 8 |
| Principal's Welcome | A website block with the head's message. | 9 |
| Procurement Request | A departmental request to buy something. | 8 |
| Purchase Order | The order placed with a supplier. | 8 |
| Question Bank | Reusable questions. | 6 |
| Receipt | Proof of payment, numbered. | 7 |
| Report Card | The end-of-term summary published to a learner. | 6 |
| Report Template | The layout of the printed report card. | 6 |
| Role | A named set of permissions. | 3 |
| Roll Call | A boarding attendance check — morning, evening or curfew. | 8 |
| Scholarship / Bursary | Fee support awarded to a learner. | 7 |
| Section | A class stream. | 4 |
| Security Deposit | Money held at enrolment. | 5 |
| Shadow Site | The hidden website record behind a template. | 9 |
| Straight Line | Depreciation at a constant amount per year. | 8 |
| Student Portal | The learner's own application at /student. | 6 |
| Subject | A teaching subject such as Mathematics. | 4 |
| Subscription | The school's licence to use the system. | 10 |
| Tenant | One school within the installation. | 1 |
| Tenant Health | The operator's record of a school's usage. | 10 |
| Term | One of the periods of the academic year. | 4 |
| Timetable Slot | A period for one class. | 4 |
| User Account | A login belonging to a person. | 3 |
| Weight | How much an assessment type counts towards the final sCORE. | 6 |
| Workflow | A tracked sequence of steps, such as an assessment's life cycle. | 6 |
| Workspace | The staff application at /workspace. | 1 |


## 11.2 Page Index
Every page in the staff workspace, with its address. If you know what you want to do but not where it lives, this is the list to search.
### Admissions (/workspace/…)
| Page | Address |
| --- | --- |
| Applications | /workspace/applications |
| Admission Settings | /workspace/admission-settings |

### Students
| Page | Address |
| --- | --- |
| Students | /workspace/students |
| ID Card Templates | /workspace/card-templates |

### Academics
| Page | Address |
| --- | --- |
| Overview (Academic Operations Center) | /workspace/academic-operations-center |
| Setup & Structure (hub) | /workspace/academics-setup-structure |
| Level | /workspace/courses |
| Subjects | /workspace/subjects |
| Classrooms | /workspace/classrooms |
| Academic Years | /workspace/academic-years |
| Timetables & Teaching (hub) | /workspace/academics-timetables-teaching |
| Timetables (Visual Timetable Builder) | /workspace/visual-timetable-builder |
| View Timetable | /workspace/timetable-viewer-page |
| Teacher Assignments | /workspace/teacher-assignments |
| Timetable Lessons | /workspace/timetable-lessons |
| Time Slots | /workspace/time-slots |
| Progression (hub) | /workspace/academics-progression |
| Promotion Runs | /workspace/promotion-runs |
| Screening Runs | /workspace/screening-runs |
| Promote Students | /workspace/promote-students |

### Exams & Grading
| Page | Address |
| --- | --- |
| Assessment Center (hub) | /workspace/exams-assessment-center |
| Digital Assessments | /workspace/digital-assessments |
| Question Bank | /workspace/question-banks |
| Grading & Marks Management (hub) | /workspace/exams-grading-marks |
| Performance Analytics | /workspace/exams-performance-analytics |
| Marks Entry | /workspace/assessment-marks |
| Grading Scales | /workspace/grading-scales |
| Assessment Types | /workspace/assessment-types |
| Manual Marking | /workspace/manual-marking |
| Reports & Academic Publishing (hub) | /workspace/exams-reports-publishing |
| Report Cards | /workspace/academic-reports |
| Report Templates | /workspace/report-templates |
| Workflows | /workspace/assessment-workflows |
| Publish to Student Portal | /workspace/exams-portal-reports-publisher |

### Finance
| Page | Address |
| --- | --- |
| Overview (Executive Financial Dashboard) | /workspace/executive-financial-dashboard |
| Financial Statements | /workspace/financial-statements |
| Student Billing & Revenue (hub) | /workspace/finance-student-billing |
| Fee Categories | /workspace/fee-categories |
| Fee Structures | /workspace/fee-structures |
| Invoices | /workspace/invoices |
| Fee Collections | /workspace/fee-collections |
| Student Financial History | /workspace/student-financial-history |
| Payment Proofs | /workspace/fee-payment-submissions |
| Fee Waivers | /workspace/fee-waivers |
| Expenses & Purchasing (hub) | /workspace/finance-expenses-purchasing |
| Expenses | /workspace/expenses |
| Expense Categories | /workspace/expense-categories |
| Expense Types | /workspace/expense-types |
| CORE Accounting & Setup (hub) | /workspace/finance-CORE-accounting |
| Accounts | /workspace/accounts |
| Journal Entries | /workspace/journal-entries |
| Revenue Streams | /workspace/revenue-streams |
| Revenue Categories | /workspace/revenue-categories |
| School Bank Accounts | /workspace/school-bank-accounts |
| Document Templates | /workspace/finance-document-templates |

### HR & Payroll
| Page | Address |
| --- | --- |
| Staff Directory & HR (hub) | /workspace/hr-staff-directory |
| Employees | /workspace/employees |
| Staff Assets | /workspace/employee-assets |
| Disciplinary Cases | /workspace/disciplinary-cases |
| Payroll & Compensation (hub) | /workspace/hr-payroll-compensation |
| Payroll Periods | /workspace/payroll-periods |
| Salary Grades | /workspace/salary-grades |
| Staff Loans | /workspace/staff-loans |
| Attendance & Leave (hub) | /workspace/hr-attendance-leave |
| Leave Requests | /workspace/leave-requests |
| Staff Attendance | /workspace/staff-attendances |

### Inventory & Procurement
| Page | Address |
| --- | --- |
| Stock & Inventory (hub) | /workspace/inventory-stock |
| Inventory Items | /workspace/inventory-items |
| Issuance | /workspace/inventory-issuances |
| Stock Adjustments | /workspace/stock-adjustments |
| Procurement (hub) | /workspace/inventory-procurement |
| Procurement Requests | /workspace/procurement-requests |
| Purchase Orders | /workspace/purchase-orders |
| Goods Received | /workspace/goods-receiveds |
| Suppliers | /workspace/suppliers |
| Fixed Assets (hub) | /workspace/inventory-fixed-assets |
| Fixed Assets | /workspace/fixed-assets |
| Asset Maintenance | /workspace/asset-maintenances |

### Library
| Page | Address |
| --- | --- |
| Catalogue (hub) | /workspace/library-catalogue |
| Books | /workspace/library-books |
| eResources | /workspace/e-resources |
| Circulation (hub) | /workspace/library-circulation |
| Issue Book | /workspace/issue-book |
| Issues | /workspace/library-issues |
| Knowledge (hub) | /workspace/knowledge-knowledge |
| Assets | /workspace/knowledge-assets |
| Galleries | /workspace/knowledge-galleries |

### Boarding & Welfare
| Page | Address |
| --- | --- |
| Accommodation (hub) | /workspace/boarding-accommodation |
| Hostels | /workspace/hostels |
| Rooms | /workspace/hostel-rooms |
| Allocations | /workspace/hostel-allocations |
| Welfare & Care (hub) | /workspace/boarding-welfare |
| Out Passes | /workspace/hostel-out-passes |
| Attendance | /workspace/hostel-attendances |
| Inspections | /workspace/hostel-inspections |

### Health & Safety
| Page | Address |
| --- | --- |
| Health Records (hub) | /workspace/health-records |
| Medical Records | /workspace/student-medical-records |
| Clinic Visits | /workspace/clinic-visits |

### Communication Center
| Page | Address |
| --- | --- |
| Overview | /workspace/communication-center |
| Schedule & Tasks | /workspace/schedule |
| Community & Engagement (hub) | /workspace/communication-community |
| Announcements (Notices) | /workspace/announcements |
| Events | /workspace/event-calendars |
| Chat | /workspace/chat-threads |
| Resources | /workspace/campus-resources |
| Polls & Surveys | /workspace/polls |
| Help & Inbox (hub) | /workspace/communication-help-inbox |
| Helpdesk | /workspace/helpdesk |
| Kairo CORE Messages | /workspace/platform-inboxes |

### LMS
| Page | Address |
| --- | --- |
| Homework & Lessons (hub) | /workspace/lms-lms |
| Homework | /workspace/homework |

### Website
| Page | Address |
| --- | --- |
| Templates & Design (hub) | /workspace/website-templates |
| Websites | /workspace/cms-websites |
| Pages | /workspace/cms-pages |
| Content Manager | /workspace/cms/content |
| Visual Studio (builder) | /workspace/cms/builder/{pageId} |

### Reports & Intelligence
| Page | Address |
| --- | --- |
| Dashboard | /workspace/reporting-dashboard |
| Reports (hub) | /workspace/reports-reports |
| Report Generator | /workspace/report-generator-page |
| Generated Reports | /workspace/generated-reports |
| Analytics Explorer | /workspace/analytics-explorer |
| Report Templates | /workspace/enterprise-report-templates |
| Reporting Workflows (reachable by URL only) | /workspace/reporting-workflows |

### System Administration
| Page | Address |
| --- | --- |
| Overview | /workspace/administration-dashboard |
| System Settings (hub) | /workspace/admin-system-settings |
| System Settings | /workspace/system-settings-page |
| Email Configuration | /workspace/email-configuration |
| User Management (hub) | /workspace/admin-user-management |
| User Accounts | /workspace/user-accounts |
| Roles | /workspace/custom-roles |
| Departments | /workspace/departments |
| Audit & Compliance (hub) | /workspace/admin-audit |
| Audit Log | /workspace/system-audit-logs |
| Data Export | /workspace/tenant-data-export-page |

### Subscription & Billing
| Page | Address |
| --- | --- |
| Overview | /workspace/saas-billing-overview |
| Subscription & Billing (hub) | /workspace/saas-hub |
| My Subscription | /workspace/saa-s-my-subscriptions |

### Always available
| Page | Address |
| --- | --- |
| My Day | /workspace/my-day |
| Profile | /workspace/profile |
| Sign in / out | /workspace/login, /workspace/logout |

### Student portal (/student/…)
| Page | Address |
| --- | --- |
| Dashboard | /student |
| My Results | /student/my-results |
| Performance Analytics | /student/performance-analytics |
| My Timetable | /student/my-timetable |
| Digital Assessments | /student/digital-assessments |
| My Homework | /student/homeworks |
| My Fees | /student/my-fees |
| Library | /student/library |
| Notices | /student/notices |
| Polls & Surveys | /student/polls-surveys |
| My Schedule | /student/my-schedule |
| My Residence | /student/my-residence |
| My Profile | /student/my-profile |


## 11.3 Notification Reference
What the system sends, to whom, and when.
| Trigger | Who receives it | Channel |
| --- | --- | --- |
| A school is approved | The school's pending administrator | Email with an activation link |
| An activation email cannot be delivered | The platform operator sees a warning | On screen |
| A new user registers on your workspace | The authorised approvers | In-app always; email if the toggle is on |
| A user account is approved | The new user | Email |
| A user account is rejected | The applicant | Email |
| An employee's account is created | The employee | Email |
| A fee payment slip is approved | The school and the parent | Email |
| A fee payment slip is rejected | The school and the parent, with the reason | Email |
| The scheduled defaulter notice runs | The primary guardian of each defaulter, where fee statements are ticked | Email |
| A clinic visit is discharged | The learner's first guardian | Email |
| An out pass needs parental approval | The learner's first guardian | SMS and email, with the OTP |
| A payment receipt is issued | Nobody automatically — print or send it | On demand |
| A report card is published | The learner, in the portal | Portal |
| A new report schedule fires | The recipients you entered | Email, in-app notification, or both |
| A task or event reminder time arrives | The task or event owner | In-app |
| A homework submission is graded | The learner, in the portal | Portal |
| Every scheduled command | Nobody — this is the scheduler running | Server-side |

NOTE: Four of these are not delivered in this release even though the screen suggests they are: a notice requiring acknowledgement, a helpdesk reply, an SMS or WhatsApp notification (the flags exist, the gateways do not), and a clinic-visit parent notice on logging rather than on discharge. See Appendix 11.6.

## 11.4 Configuration Reference
The settings that change how the system behaves, in the order they should be changed.
| # | Setting | Where | Default | Change it when |
| --- | --- | --- | --- | --- |
| 1 | Primary Ledger Currency | Settings → System Preferences | USD | Always. Before any invoicing. |
| 2 | School Operating & ERP Language | Settings → System Preferences | English | You operate in another language. |
| 3 | Public Website & Portal Language | Settings → System Preferences | English | Your public site is in another language. |
| 4 | Student ID Prefix Pattern | Settings → System Preferences | STU-{YEAR}-{SEQ} | Your admission-number convention differs. |
| 5 | Invoice Number Pattern | Settings → System Preferences | INV-{SEQ} | Your finance office requires another format. |
| 6 | Billing Frequency | Settings → Banking & Payments | Termly | You invoice monthly. |
| 7 | Bank Account Printed on Invoices | Settings → Banking & Payments | All active | You want one account printed. |
| 8 | EcoCash Merchant Pin Code | Settings → Banking & Payments | Empty | You take mobile-money payments. |
| 9 | Minimum Password Length | Settings → Authentication & Security | 8 characters | Always. Set 12. |
| 10 | Auto-Session Timeout | Settings → Authentication & Security | 60 minutes | Staff are interrupted during entry. |
| 11 | Enforce MFA | Settings → Authentication & Security | Off | You have email or SMS configured for it. |
| 12 | Restrict Login by IP Range | Settings → Authentication & Security | Empty | The school has a fixed address. |
| 13 | Email on registration | Settings → Notifications | On | Registrations are frequent and noisy. |
| 14 | Established Year / Principal Name | Settings → Institution Profile | Empty | You want them on the sign-in page. |
| 15 | Website URL | Settings → Institution Profile | Empty | You have your own domain. |
| 16 | Terms of Service Content | Settings → Legal & Terms | A sensible default | Always. Replace it with your own. |
| 17 | Email, per category | Settings → Email Configuration | Platform sender | Always. Use your own domain. |
| 18 | Module switches | Settings → Modules | All on | Turn off what you do not use. |
| 19 | Feature flags | Settings → Feature Flags | Various | Only after confirming the feature exists. |
| 20 | Grading scales | Exams → Grading Scales | None | Before entering any marks. |
| 21 | Assessment types and weights | Exams → Assessment Types | None | Before entering any marks. Weights must total 100. |
| 22 | Report templates | Exams → Report Templates | None | Before printing a report card. Must be active. |
| 23 | Fee categories | Finance → Fee Categories | None | Before fee structures. |
| 24 | Fee structures | Finance → Fee Structures | None | Before bulk billing. |
| 25 | Default bank account | Finance → School Bank Accounts | None | Before recording a payment. |
| 26 | Salary grades and components | HR → Salary Grades | None | Before a payroll run. |
| 27 | Stock locations | Inventory | None | Before issuing stock. |
| 28 | Roles | Administration → Roles | Built-in roles | When a job needs a new permission set. |
| 29 | Departments | Administration → Departments | None | When department presets fit your structure. |
| 30 | Subscription | Subscription & Billing | None | To activate the workspace. |


## 11.5 Quick Troubleshooting Index
The fastest route to an answer. Find the symptom, jump to the chapter.
| Symptom | Go to |
| --- | --- |
| I cannot see a module in my sidebar | 10.3 |
| A page my colleague can see is missing for me | 3.7 |
| I cannot publish a report card | 6.8 |
| I cannot open Reports & Intelligence | 9.25 |
| I cannot open the Website module | 9.17 |
| Invoices are in the wrong currency | 10.4 |
| Bulk billing produced nothing | 7.12 |
| A student's balance will not add up | 7.11 |
| Payroll figures differ between runs | 7.21 |
| A journal entry will not save | 7.9 |
| Issuing stock fails | 8.7 |
| I cannot create a hostel room | 8.21 |
| A parent did not receive my notice | 9.10 |
| I cannot create a helpdesk ticket | 9.10 |
| A report shows an old figure | 9.26 |
| A report will not download | 9.26 |
| A new school has no subscription | 10.19 |
| Emails arrive from the platform address | 10.3 |
| My access was suspended | 10.16 |
| I cannot see my child's results | 6.12 |
| A student cannot start an assessment | 6.6 |


## 11.6 Known Issues and Limitations
Every item below was verified in the system's own source code for this release. They are recorded so that nobody wastes time searching for a cause that is not there. Where a workaround exists, it is given.
### Serious — read these before trusting the affected screen
| # | Issue | Effect | What to do | Reference |
| --- | --- | --- | --- | --- |
| 1 | Some migrations drop their own tables on every run. Eleven migration files call dropIfExists inside the up() method rather than only in down(). They cover communication, HR, inventory, reporting, knowledge and platform-backup tables. | Running migrations destroys data in those tables. | Never run migrations on a production database without a verified backup. | Migration files listed in the project history |
| 2 | The hostel allocation student search bypasses the tenant filter. | Students from other schools can be listed and selected. | Verify a learner's school before allocating a bed. Do not rely on the list. | 8.21 |
| 3 | The chat workspace does not check membership. Any staff member can read and post in any school thread, including parent-teacher threads. | Confidential conversations are not confidential. | Use a channel outside the system for anything sensitive. | 9.5 |
| 4 | Bulk export of student financial histories is not tenant-scoped. | In principle a school's billing histories could be exported by guessing identifiers. | Restrict access to the panel's export route until fixed. | 10.8 |
| 5 | A ?student= parameter on the financial history page is not tenant-checked for writes. | In principle a financial entry could be written against another school's learner. | Restrict the module; report to the vendor. | 7.11 |
| 6 | eResources and the Knowledge Gallery do not enforce module permissions. | Any signed-in staff member can reach them and delete records. | Ask an administrator to review who has panel access. | 8.13 |
| 7 | Announcement attachments are served from public storage. | An attachment on an admin-only notice is downloadable by anyone with the address. | Never attach confidential documents to a notice. | 9.2 |
| 8 | Repository file visibility is not enforced on download. | Files served by direct public URL bypass visibility rules. | Keep staff-only material on Archival Staff Only. | 8.14 |
| 9 | Out-pass OTPs are stored and compared as plain text, with no expiry. | The OTP is a formality, not a security control. | Do not rely on it for genuine security. | 8.18 |
| 10 | Public preview and file routes for digital resources require no authentication. | Some digital files are reachable without signing in. | Do not store sensitive material as a digital resource. | 8.13 |

### Defects that block a screen
| # | Issue | Effect | Reference |
| --- | --- | --- | --- |
| 11 | Creating or editing a hostel room raises an error. The floor and wing look-ups query a building relationship the data model does not have. | Rooms cannot be created through the interface. Hostels and floors still work. | 8.16 |
| 12 | "Load learners from selected hostel" fails for the same reason. | Hostel roll calls cannot be loaded. | 8.19 |
| 13 | Helpdesk create and edit pages are not registered, although the buttons are shown. | Tickets cannot be raised. The list is read-only. | 9.8 |
| 14 | Issuing stock requires a stock location, and no location is created for you. | The posting is rejected. | 8.3 |
| 15 | The Return action on an inventory issuance calls a method that does not exist. | Returns fail. Use a stock adjustment instead. | 8.7 |
| 16 | Editing a library loan returns a 404. | Use the View screen and change the status instead. | 8.13 |

### Missing features
| # | Issue | Effect | Reference |
| --- | --- | --- | --- |
| 17 | No parent portal exists. The Parent Portal feature flag is present but off by default. | Parents cannot see fees, results or attendance. Use WhatsApp, SMS and email. | 9.11 |
| 18 | No staff portal exists beyond the workspace itself. | Staff work inside the workspace, not in a separate app. | 10.14 |
| 19 | No SMS or WhatsApp gateways are configured, although the feature flags exist. | Turning the flags on sends nothing. | 10.3 |
| 20 | No library return, renew or fine-collection screen, and nothing marks a loan overdue. | Overdue books must be chased from the Overdue Books report. | 8.11 |
| 21 | No out-pass check-in action. | A boarder's return is not recorded from the screen. | 8.18 |
| 22 | No notice acknowledgement record. | Nothing captures that a notice was read. | 9.2 |
| 23 | Helpdesk replies are never displayed. | Replies, including internal ones, are written and never shown. | 9.8 |
| 24 | No parent or student helpdesk surface. | Only staff use the helpdesk. | 9.8 |
| 25 | Advanced Analytics and External API Access have no implementation. | The flags exist; the features do not. | 10.3 |
| 26 | Chat thread membership cannot be set beyond adding members, and is not enforced. | See item 3 above. | 9.5 |
| 27 | Subscriptions are not created when a school registers. | The operator must create one. | 10.19 |

### Reporting problems
| # | Issue | Effect | Reference |
| --- | --- | --- | --- |
| 28 | Analytics Explorer's Communication card reads two tables that do not exist. | Published Notices and Helpdesk Tickets always read zero. Use the actual lists. | 9.24 |
| 29 | Four analytics cards render an identical fallback chart — Website, Inventory Assets, Procurement and System. | Their figures are not their own. Use the underlying screens. | 9.24 |
| 30 | "By Term" on the financial history page prints the whole history to PDF. | The PDF does not match the screen. | 7.11 |
| 31 | The whole-school financial summary is built in full before paging. | Large schools are slow to filter. | 7.11 |
| 32 | The financial history page advertises permissions it does not use. The permission editor shows create, edit, delete and import keys, but the controls are gated by a different, older permission. | A role granted only the visible keys sees no edit controls. | 3.7 |
| 33 | Reporting Workflows is in the permission editor but not in any menu. | Reachable only by URL. | 9.25 |
| 34 | Reorder-level alerts are not scheduled. | You will not be warned automatically when stock runs low. Check the Low Stock Items report. | 8.7 |

### Data-quality and display problems
| # | Issue | Effect | Reference |
| --- | --- | --- | --- |
| 35 | The public website displays "0+ Certified Faculty". The count looks for a role value the system does not use. | Do not publish the statistics block until fixed. | 9.16 |
| 36 | A notice priority of high can never be produced, and the student view tests for it. | No notice will ever show the "high priority" badge. | 9.2 |
| 37 | Poll anonymity is cosmetic. The voter is recorded. | Do not promise secrecy. | 9.7 |
| 38 | "Outstanding Achievements" is written into the competency JSON field, while a separate empty column of the same name exists. | Anything reading the standalone column sees nothing. | 6.8 |
| 39 | A physical copy's Location always shows blank — the form has no shelf field. | Record shelving in the description. | 8.13 |
| 40 | The student portal's Knowledge tab hides only *Archival Staff Only*. | Items marked teacher-only or everyone are shown to students. | 8.14 |
| 41 | The student residence page shows the newest allocation whatever its status, and the floor name is always blank. | Check the allocation status on the staff screen. | 8.21 |
| 42 | The hostel's *Parents Notified* value is never written. | Tick it manually as a reminder. | 8.19 |
| 43 | The *Learners* search box does not show results. | Clear the search and try again. | 8.21 |
| 44 | The role vocabulary differs across four screens — role names, registration role values, announcement audiences and seeded data. | Always pick a value from the drop-down on the screen in front of you. | 3.6 |
| 45 | Medical records have no duplicate guard, and Regular Medications has no form field. | Keep one record per learner; note medications under chronic conditions. | 8.25 |
| 46 | Clinic vitals raise no critical-value alert, despite alert models existing. | Read vitals yourself. | 8.25 |
| 47 | Prescribed stock is never dispensed, so Quantity Dispensed stays at zero. | Issue medicine through Inventory and record it. | 8.25 |
| 48 | The circulation desk overwrites the Borrowed Date with today. | Review the date when backdating. | 8.11 |
| 49 | Notice display style is stored but ignored — every notice renders as the same card. | Do not rely on banner, popup or ticker behaviour. | 9.2 |
| 50 | Campus Resource download eligibility is never checked. | Anyone with page access downloads everything. | 9.6 |
| 51 | Chat shows only the first attachment on a message. | Send one file per message. | 9.5 |
| 52 | Chat uploads have no server-side validation. | Do not use chat for files you would refuse to receive. | 9.5 |
| 53 | Helpdesk ticket numbers are generated by counting and can collide. | A duplicate-key error is harmless; retry. | 9.8 |
| 54 | A SYSTEM BULLETIN: prefix turns any message into a red alert, and is lost if edited. | Avoid that phrase. | 9.5 |
| 55 | Events created on the Schedule page lose their audience and become visible to everyone. | Create events under Events instead. | 9.4 |
| 56 | Only the student audience is honoured for notices, events and polls. Staff and parent audiences are stored but undelivered. | Use WhatsApp or email for parents; staff vote on the staff screen. | 9.2 |
| 57 | Bulk repository packs are written into public storage with a predictable name. | Treat a pack as public once created. | 8.12 |
| 58 | Two suppliers screens exist and only one is reachable. | Use the one under Inventory & Procurement. | 8.5 |
| 59 | Some inventory reporting and automation code is present but never called, and refers to columns that do not exist. | Do not expect it to work if it appears in a future release without a rewrite. | 8.7 |
| 60 | Publisher, publication year and language exist on repository assets but are not on the form. | Set them elsewhere if you need them. | 8.13 |
| 61 | Depreciation supports only straight line and double declining. Other methods are recorded but refuse to post. | Use one of the two. | 8.6 |
| 62 | Asset maintenance records cannot be edited from the list. | Add a new record. | 8.6 |
| 63 | Payroll percentage components are recalculated at the current rate each time a run is created. | Void and re-create rather than editing. | 7.15 |
| 64 | Transactions with no bank account count as the default account. | A single non-default account view will not add up. | 7.7 |
| 65 | Receipts and invoices use separate sequences, so they are not comparable. | Expected. | 7.7 |


## 11.7 Glossary of Status Values
Every status the system uses, in one place.
| Value | Where | Meaning |
| --- | --- | --- |
| draft | Announcements, Poll workflows, Assessment workflows, Report cards, Pay periods, Pay runs, Purchase orders | Not yet in use |
| pending | Applications, Helpdesk tickets, Leave requests, Schools, School registration | Awaiting a decision |
| scheduled | Announcements | Publishes on a date |
| open | Helpdesk tickets, Assessment workflows, User tasks | Available for work |
| in_progress | Helpdesk tickets | Being worked on |
| waiting | Helpdesk tickets | Blocked on the requester |
| resolved | Helpdesk tickets, Disciplinary cases | Finished, not yet closed |
| closed | Helpdesk tickets, Disciplinary cases, Academic years | Finished |
| published | Announcements, Report cards, Assessment workflows, Pages | Live and visible |
| expired | Announcements, Schools, Subscriptions, Warranties | Past its end date |
| active | Schools, Hostels, Suppliers, Loans, Leave requests, Stock | In use |
| suspended | Schools | Access withdrawn |
| unpaid | Invoices | Nothing received |
| partial | Invoices | Some money received |
| paid | Invoices, Pay runs | Settled |
| cancelled | Invoices, Leave requests | Withdrawn |
| approved | Users, Procurement requests, Purchase orders, Report cards, Fee payment submissions, Leave requests, Assets | Accepted |
| rejected | Users, Procurement requests, Leave requests, Fee payment submissions | Declined |
| processing | Generated reports | Being compiled |
| failed | Generated reports | Did not compile |
| locked | Assessment workflows | Closed to change |
| void | Pay runs | Cancelled after creation |
| outstanding | Assets | Still depreciating |
| open / done | User tasks | Not started / finished |
| low / medium / high | Task and ticket priority | Urgency |
| checked_in | Clinic visits | At the clinic |
| discharged | Clinic visits | Sent home or recovered |
| Pending Parent OTP | Out passes | Awaiting the guardian's code |
| Pending Warden | Out passes | Guardian approved, awaiting the warden |
| Trialling / Active / Expired | Subscriptions | Licence state |


## 11.8 Who Can Do What — A Quick Reference
| Task | Who can do it |
| --- | --- |
| Register the school | Anyone, on the public site |
| Approve the school | Platform operator |
| Create a user account | System Administrator |
| Assign a role | System Administrator |
| Grant a permission | System Administrator |
| Create a department | System Administrator |
| Change system settings | System Administrator |
| Turn a module off | System Administrator |
| Publish a report card | System Administrator or School Administrator — not a teacher |
| Run a report | System Administrator; also FIN and Clinic department members |
| Build or change the website | System Administrator or School Administrator |
| Configure email | System Administrator |
| Issue an invoice | Accounts / Finance |
| Record a payment | Accounts / Finance |
| Run payroll | HR |
| Approve leave | HR or a School Administrator |
| Log a clinic visit | Health |
| Issue stock | Procurement or School Administrator |
| Approve a purchase order | Procurement or School Administrator |
| Issue a book | Librarian |
| Allocate a bed | Houseparent or School Administrator |
| Approve an out pass | Guardian, then the warden |
| Set homework and grade it | Teaching Staff |
| Export the audit log | System Administrator |
| Export school data | System Administrator |


## 11.9 Glossary of Commands
Commands run by an administrator on the server. In normal day-to-day use you will never need these.
| Command | What it does |
| --- | --- |
| schedule:send-reminders | The only scheduled command. Sends reminders for tasks and events that have a reminder time. |
| cms:seed-placeholders | Generates the bundled placeholder illustrations used by website templates. |

NOTE: The scheduled task must be registered with the server's scheduler for reminders to arrive. If reminders never appear, that registration is the first thing to check. See Section 9.4.

## 11.10 Closing Note
This manual documents the system as it is at present. It is deliberately honest about the gaps: a reader who knows that, say, hostel room creation is broken in this release will lose an hour of searching; a reader who is told everything works perfectly will lose a day.
Almost everything in this system works well. The Admissions, Academics, Exams, Finance and HR modules are complete and can be relied on day to day. Most of the gaps in Appendix 11.6 are in the specialist modules — Library, Boarding, Health — where a manual workaround exists for every one of them.
Two things are worth doing straight away on any new installation, and both are in the checklist in Section 10.20: set your currency and set your module switches. Get those right and almost every later surprise disappears.