Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
1 daripada 20
REFERENCE:
DOCUMENT
SOFTWARE REQUIREMENTS
SPECIFICATION (SRS)
UNISZA MyLegS
AGENCY NAME :
PARENT AGENCY
NAME :
DOCUMENT NAME :
DOCUMENT VERSION : 1
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
2 daripada 20
1. Introduction
1.1 Purpose
This Software Requirements Specification (SRS) defines the functional and non-
functional requirements for the UNISZA MyLegS platform (branded as LexSZA). It
serves as the primary reference document for the development team, client
stakeholders, and project managers throughout the software development lifecycle.
1.2 Scope
MyLegS is a legal studies support platform for UNISZA students and faculty,
consisting of:
• A mobile application (iOS & Android) providing learning content, AI chatbot
assistance, case summaries, and a question bank.
• A web-based admin panel for managing users, subscriptions, content, and
business settings.
The platform operates on a freemium model: free users get limited access, while
paid subscribers unlock full features.
1.3 Document Checking and Validation
Document Checking
Checked By Position Signature Date
Validation Document
Validated By Position Signature Date
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Muka Surat:
3 daripada 20
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
1.4 Document Control
Version
Number Date Summary of Amendements Issuer
1.3 Definitions, Acronyms & Abbreviations
Term Meaning
UNISZA Universiti Sultan Zainal Abidin
FUHA Faculty of Human Sciences and Arts (content owner)
AISB [Development/Technical Team — to be confirmed]
SRS Software Requirements Specification
TBD To Be Determined
KB Knowledge Base
VPS Virtual Private Server
MYR / RM Malaysian Ringgit
Admin Panel Web-based management interface
Free User Registered user with no active paid subscription
Paid User Registered user with an active subscription
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
4 daripada 20
1.4 List of Actors
Actor Role
Free User Access to 3 Notes topics and free reference links
Paid User (Subscriber) Full access including Chatbot, Case Summary, and
Question Bank
System Administrator Manages users, subscriptions, content, pages, and
system settings
Content Manager Manages notes and related learning content
Business Manager Manages Subscription plans and pricing
1.5 References
• UNISZA MyLegS Requirement Workshop Slides — 20 – 21 Jan 2026
(Datascience)
• Apple App Store & Google Play Store developer guidelines
1.6 Document Overview
• Section 2 describes the overall product context and user types.
• Section 3 details all system features and functional requirements.
• Section 4 covers external interfaces.
• Section 5 lists non-functional requirements.
• Section 6 captures all open/TBD items requiring client decision.
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
5 daripada 20
2. Overall Description
2.1 Product Perspective
LexSZA is a standalone platform. The mobile app is the primary user-facing
product; the web admin panel is used exclusively by administrators. The system
integrates with:
• Billplz — payment gateway for subscription processing
• External URLs — for free resources (statutes, case law, Google Scholar, etc.)
• AI/LLM service — to power the chatbot (knowledge base sourced from
uploaded PDFs)
2.2 Product Functions Summary
Platform Module Access
Mobile Notes Free (3 topics) + Full (paid)
Mobile Chatbot Paid only
Mobile Case Summary Paid only
Mobile Question Bank Paid only
Mobile Free Links Directory Free
Web (Admin) User Management Admin only
Web (Admin) Subscription / Pricing Admin only
Web (Admin) Page & Directory Management Admin only
Web (Admin) Chatbot Knowledge Base Admin only
Web (Admin) Business Settings Admin only
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
6 daripada 20
2.3 User Classes and Characteristics
Role Description Access Level
Guest Unauthenticated visitor App download &
registration only
Free User Registered, no active subscription Notes (3 topics), all free
links
Paid User Registered with active subscription All modules
Admin Platform manager via web panel Full system access
Free User Bypass: Admin can manually toggle any user to full access without payment (module limiter
override). This is intended for test accounts or special cases (e.g., faculty staff).
2.4 Operating Environment
• Mobile App: iOS and Android
• Web Admin Panel: Modern browsers (Chrome, Firefox, Edge); responsive
design recommended
• Hosting: Movahost VPS (provisioned by AISB)
• Domain: lexsza.my (pending confirmation)
• SSL/HTTPS: Configured and verified (AISB)
2.5 Design & Implementation Constraints
• Payment must be processed via Billplz (web only — no in-app payment)
• Primary brand color: Blue (exact hex code TBD)
• App branding name: LexSZA; logo design TBD
• File uploads for notes limited to: PDF, JPEG, PNG
• Chatbot knowledge base is sourced from the same PDFs in the Notes
module (11 topics)
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
7 daripada 20
2.6 Assumptions and Dependencies
• The client (FUHA/UNISZA) will supply all content (notes, PDFs, topic names)
before launch.
• Billplz account ownership and API credentials must be confirmed by the client.
• Apple Developer and Google Play accounts will be created fresh by AISB.
• The chatbot name is pending client decision.
• Game/Quiz URLs (Wayground) will be supplied by the client.
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
8 daripada 20
3. System Features
3.1 User Management (Web Admin)
3.1.1 User Registration (Mobile App)
• Users register via email (social login not required).
• Registration form fields:
• Full Name (required)
• Profile Photo (optional)
• Gender / Sex (required)
• Email Address (required, unique)
• Phone Number (required)
• Institution / Organization (required)
• Work or Study status (required — select one)
• Country (required)
• Password (required, min. 8 characters recommended)
3.1.2 User Status Management (Admin Panel)
• Admin can perform the following actions on any user account:
• Activate — enable access
• Suspend — temporarily disable access without deletion
• Delete — permanently remove account and data
• User profile details are visible to admin only; other users cannot view
each other's profiles.
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
9 daripada 20
3.1.3 User Roles
Role Notes Access Paid Modules Admin Panel
Free User 3 topics No No
Paid User All topics Yes No
Admin All topics Yes Full
3.1.4 Free User Bypass
• Admin can toggle a "bypass" flag on any user account, granting full paid-
module access without a subscription payment.
• This bypass is tracked separately from subscription status.
3.1.5 Admin User List View (Suggested)
• Admin panel displays a searchable, filterable table of all users.
• Filterable by: role, status (active/suspended), subscription status,
registration date.
3.2 Notes — Content Management (Web Admin + Mobile)
3.2.1 Topic / Category Management (Admin)
• Admin can create, edit, and delete topic folders (categories).
• Categories include: notes/topics, question bank, statutes, case law, game,
quiz (per workshop).
• Each topic has: Title, Description, and a Category tag.
3.2.2 File Upload (Admin)
• Admin uploads content files per topic.
• Supported formats: PDF, JPEG, PNG
• Each file record includes:
• Title (required)
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
10 daripada 20
• Description (required)
• Category / Topic (required)
• File attachment (required)
3.2.3 App View — User Access (Mobile)
• Notes are view-only in the app (no download/print).
• Free users: Access limited to 3 topics only.
• Paid users: Access to all topics based on their subscription package.
• Locked content should display a subscription prompt/upgrade nudge to
free users.
3.3 Subscription & Pricing (Web Admin + Mobile)
3.3.1 Subscription Plans
Plan Duration Price
Free Ongoing RM0 — limited access
Medium 4 months RM39
Annual 12 months RM79
3.3.2 Module Access per Tier
• Admin can toggle which modules are accessible per subscription tier.
• This enables future changes to tier benefits without code changes (tiering
concept).
3.3.3 Subscription Expiry Behavior
• Before expiry: System sends a standard reminder notification (exact timing
TBD).
• On expiry: User account automatically reverts to free-tier access.
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
11 daripada 20
• User presets/settings (bookmarks, preferences) are retained after expiry
— user does not lose personalization data.
3.3.4 Payment Gateway
• Payment is processed via Billplz.
• Payment is initiated and completed via the web only (not in-app).
• After successful payment, subscription is activated and reflected in the
mobile app.
• Billplz account ownership: TBD — client to confirm.
3.4 Chatbot — Knowledge Base (Web Admin + Mobile)
3.4.1 Knowledge Base Setup (Admin)
• Admin uploads PDFs as the AI knowledge source.
• Knowledge base is sourced from the Notes module (same 11 topic PDFs).
• Admin can define the chatbot's tone and behavior via an instruction/system
prompt box (e.g., "You are a legal assistant for UNISZA law students.
Answer formally.").
3.4.2 Reference / Citation
• Chatbot responses should cite the source document/topic when
answering.
• Citation is linked back to the relevant note/PDF where possible.
3.4.3 Admin Testing
• Admin can test the chatbot (send test queries) via the admin panel before
making it live for users.
3.4.4 Mobile App — Chatbot (Paid Only)
• Chatbot is accessible only to paid users.
• Free users see a locked state with an upgrade prompt.
• Chatbot name: TBD (client to confirm).
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
12 daripada 20
3.5 Page & Directory Management (Web Admin + Mobile)
3.5.1 Static Pages / Link Management (Admin)
• Admin can add, edit, and delete URL entries that appear as icon links in
the mobile app.
• Each entry includes:
• Page/Link Title (required)
• URL (required)
• Icon image (upload or select from preset icons)
3.5.2 Free Links Directory
The following links are pre-configured as free (accessible to all users):
Link Platform
Statutes External
Case Law External
UNISZA Learning Platform External
Judiciary Virtual Tour External
Google Scholar External
Quiz / Game (Wayground) External
Micro Credential EduflexS External
3.5.3 Link Behavior
• All links open in the device's external browser (not in-app webview).
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
13 daripada 20
3.6 Case Summary (Mobile — Paid Only)
• Paid users can browse a list of legal case summaries.
• Each case summary includes: Case Title, Citation, Summary Text, and
optionally a linked PDF.
• Admin manages case summaries via the admin panel (add/edit/delete).
• Free users see a locked state with upgrade prompt.
3.7 Question Bank (Mobile — Paid Only)
• Paid users can access practice questions organized by topic.
• Questions may be multiple choice or short answer.
• Admin manages questions via the admin panel.
• Free users see a locked state with upgrade prompt.
3.8 Game / Quiz (Mobile — Free Link)
• Game/Quiz is accessed via an external link (Wayground platform).
• Link opens in the external browser.
• The specific Wayground URL is TBD — client to provide.
3.9 Business Settings (Web Admin)
3.9.1 Company Information
• Admin can update:
• Company/Organization Name
• Address
3.9.2 Branding
• Admin can upload the application logo.
• Primary brand color: Blue.
• App name: LexSZA.
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Muka Surat:
14 daripada 20
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
4. External Interface Requirements
4.1 User Interfaces
Interface Description
Mobile App (iOS/Android) Primary interface for Free and Paid users
Web Admin Panel Browser-based management interface for Admin
Registration / Login Screen Email-based, accessible via mobile app
Subscription / Payment Page Web-based, redirects to Billplz
4.2 Hardware Interfaces
• Mobile: Standard iOS/Android smartphone hardware.
• Server: Movahost VPS (specifications TBD)
4.3 Software / Third-Party Interfaces
System Purpose Status
Billplz Payment gateway for
subscriptions
TBD — account owner
pending
AI/LLM service Power chatbot responses from
KB TBD
Apple App Store iOS app distribution New account
Google Play Store Android app distribution New account
Wayground External quiz/game platform URL TBD
4.4 Communication Interfaces
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
15 daripada 20
• All API communication over HTTPS (SSL verified)
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
16 daripada 20
5. Non-Functional Requirements
5.1 Performance
• App screens should load within 3 seconds on a standard mobile data
connection.
• PDF viewer should display the first page within 5 seconds of opening.
• Admin panel should handle at least [X] concurrent admin sessions (capacity
TBD).
5.2 Security
• All data transmitted over HTTPS.
• Passwords stored as hashed values (e.g., bcrypt) — never in plaintext.
• User profile data visible only to admin — not to other users.
• Session tokens expire after inactivity (timeout duration TBD).
• Payment data is handled entirely by Billplz — the platform should not store raw
card details.
5.3 Availability
• Target uptime: 99% or better (SLA to be agreed with hosting provider).
• Scheduled maintenance should be communicated to users in advance.
5.4 Usability
• Mobile app should support both Bahasa Malaysia and English (confirm with
client)
5.5 Scalability
• System should support growth to at least [X] registered users without
infrastructure changes
5.6 Maintainability
• Content (notes, PDFs, links) must be fully manageable by admin without
developer involvement.
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
17 daripada 20
• Subscription tier configurations (price, duration, module access) must be
configurable from the admin panel.
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
18 daripada 20
6. Open Issues & TBD Items
The following items require a decision or input from the client before development
can be finalized:
# Item Current Status Action Required
1 Chatbot name TBD Client to confirm
2 Billplz account ownership TBD Client to set up / provide
API credentials
3 AI/LLM provider for
chatbot Not specified Technical team to propose;
client to approve
4 Game/Quiz URL
(Wayground) TBD Client to provide the URL
5 Company name & address TBD Client to provide
6 LexSZA logo design TBD Client to provide final logo
file
7 Domain name
confirmation Proposed: lexsza.my Client to confirm and
register
8 Exact hex code for brand
blue TBD Client to confirm or provide
brand guide
9 Subscription duration tiers 4 months vs 6
months discrepancy
Client to confirm:
RM39/4mo or RM39/6mo?
10 Push notification support Not discussed Confirm if reminder
notifications are in scope
11 Case Summary module
details Not detailed Client to provide content
structure
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
19 daripada 20
# Item Current Status Action Required
12 Question Bank format Not detailed Client to confirm: MCQ,
short answer, etc.?
13 App language support Not discussed Confirm: BM only, English
only, or bilingual?
14 Expected number of users Not stated Needed for infrastructure
sizing
15 Billplz payment
confirmation webhook Not discussed Confirm automated
subscription activation flow
16 Session timeout duration Not discussed Client/security team to
decide
17 App store
review/distribution New accounts (AISB) Confirm timeline for account
creation
Hak Cipta Terpelihara
Tajuk Dokumen: Software Requirements Specification (SRS)
Tahap Keselamatan:
Terhad
No. Dokumen: Versi:
1
Muka Surat:
20 daripada 20
8. LAMPIRAN
Appendix A — Module Access Summary
Module Guest Free User Paid User Admin
Notes (3 topics) No Yes Yes Yes
Notes (all topics) No No Yes Yes
Chatbot No No Yes Yes
Case Summary No No Yes Yes
Question Bank No No Yes Yes
Free Links (Statutes, Case Law, etc.) No Yes Yes Yes
Subscribe / Payment (web) No Yes Yes Yes
Admin Panel No No No Yes
Appendix B — Subscription Plans Summary
Plan Duration Price
(MYR)
Notes
Module Chatbot Case
Summary
Question
Bank
Free N/A RM0 3 topics
only No No No
Medium 4 months RM39 All Yes Yes Yes
Annual 12 months RM79 All Yes Yes Yes
Hak Cipta Terpelihara