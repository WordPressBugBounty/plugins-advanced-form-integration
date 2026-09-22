=== Advanced Form Integration — Connect Forms to 300+ Apps ===
Contributors: afisupport, nasirahmed, freemius
Tags: form integration, crm, webhooks, automation, contact form 7
Requires at least: 3.0.1
Tested up to: 7.1
Stable tag: 2.9.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send WordPress form and order data to 300+ CRMs, email tools, and sheets — no code, no per-task fees, data stays on your site.

== DESCRIPTION ==

## Overview

**Stop paying per-task fees to move your own data.**

Advanced Form Integration (AFI) connects your WordPress forms and site events to 300+ external platforms (CRMs, email marketing, Google Sheets, helpdesks, webhooks) with zero code. Set it up once and form submissions, WooCommerce orders, bookings, and more flow into the tools you already use, in real time.

AFI runs entirely on your own site, so integrations fire directly from WordPress to each platform's API. There are no task limits, no metering, and no per-action billing. Whether you process 100 submissions a month or 100,000, the plugin works the same, and your form data and credentials never leave your server.

AFI requires no coding knowledge and can be configured in minutes. It is built for marketers, sales teams, agencies, and business owners who want to streamline workflows and improve data accuracy.

[Website](https://advancedformintegration.com/) | [Documentation](https://advancedformintegration.com/docs/afi/) | [Tutorial Videos](https://www.youtube.com/channel/UCyl43pLFvAi6JOMV-eMJUbA)

## Why Choose AFI

* **No per-task fees.** AFI has no task meter, so your submissions are unlimited on every plan, free or paid.
* **Your data never leaves your server.** Integrations fire directly from your site to each platform's API. No third-party service sits in the middle of your leads, orders, or customer details. Better for privacy and GDPR.
* **Built for WordPress.** Native triggers for 70+ form plugins plus WooCommerce, LMS, memberships, and bookings. No brittle webhook glue to maintain.
* **Fix and resend, not lost data.** Every API call is logged. If one fails, view the exact request and response, edit the data, and resend in one click. No need to ask anyone to re-submit the form.
* **Conditional logic on every integration.** Route submissions to different destinations based on their content, so you never push junk into your CRM.

## Common Use Cases

* **E-commerce.** Send WooCommerce orders to Google Sheets, create CRM leads for high-value purchases, and keep your fulfilment tools in sync.
* **Lead generation.** Add Contact Form 7, WPForms, or Gravity Forms submissions to HubSpot, Mailchimp, or ActiveCampaign automatically.
* **Agencies.** Connect many clients' forms to different CRM and email accounts, and manage every integration from one dashboard.
* **Membership and LMS sites.** Sync new MemberPress signups and course enrolments to your email platform or CRM.
* **Events and bookings.** Register attendees from your booking or events plugin into Zoom Webinar, a calendar, or a spreadsheet.
* **Customer support.** Create Freshdesk or Zendesk tickets from form submissions and route urgent ones to Slack.

## Key Features

### Universal Form Support
Connect virtually any WordPress form plugin, including Contact Form 7, WPForms, Elementor Pro Forms, Gravity Forms, Ninja Forms, Fluent Forms, and 70+ others. Triggers also extend beyond forms to WooCommerce orders, LMS enrolments, memberships, bookings, forum posts, donations, and more.

### Smart Field Mapping
Map form fields to destination fields with support for combining multiple fields and mixing static text with dynamic tags. For example, set a destination field to "New lead from {{_site_title}}" or combine {{first_name}} and {{last_name}} into one value.

### Conditional Logic
Control when data is sent using visual conditional rules. Send only when specific criteria are met, and route different submissions to different destinations.

### System and UTM Tags
Access dynamic system information using tags like {{_date}}, {{_user_ip}}, and {{_site_title}}, and capture campaign data with {{utm_source}}, {{utm_medium}}, {{utm_campaign}}, {{gclid}}, and more.

### Activity Log and Resend
Every outbound API call is recorded with its full request and response. When something fails, view exactly what went wrong, edit the data inline, and resend with one click.

### Multi-Account Support
Connect multiple accounts for the same platform from one site, for example several Mailchimp, Salesforce, or Zoho accounts, each labelled and tracked separately. Ideal for agencies.

### Built for Performance
Integrations run asynchronously in the background via Action Scheduler, so form submissions stay instant. Database tables are indexed for fast log queries even on busy sites, and credentials are not autoloaded on front-end pageviews.

### Multisite Support
Full compatibility with WordPress multisite installations.

## Supported Trigger Platforms (Senders)

AFI can start an integration from any of these forms, plugins, and events.

**Form builders:** Contact Form 7, WPForms, Gravity Forms, Elementor Pro Form, Fluent Forms, Ninja Forms, Formidable Forms, Forminator, WS Form, ARForms, Avada Forms, Beaver Builder Form, Bit Form, Breakdance Builder Form, Bricks Builder Form, Caldera Forms, ConvertPro Forms, Cool FormKit, Crowdsignal Forms, Divi Forms, eForm, Everest Forms, FormCraft 3, FormCraft Basic, Happyforms, JetFormBuilder, Kadence Blocks Form, Live Forms, Metform, Quform, Smart Forms, User Registration, weForms, WP User Frontend, SureForms, Jetpack Forms, Kali Forms, MW WP Form, NEX-Forms, QuillForms, RegistrationMagic, RomeTheme Form, Simple Basic Contact Form, SiteOrigin Contact Form, Snow Monkey Forms, Spectra Pro Forms, VS Contact Form, HTML Forms, HT Contact Form, Hash Form, Gutena Forms, Gutenverse Form, Form Maker by 10Web, WPZOOM Forms, Depicter, Otter Blocks Form, FormLayer, Nextend Social Login and Register, ProfilePress

**E-commerce:** WooCommerce, Easy Digital Downloads, SureCart, FluentCart, CartFlows, WP Simple Pay, WP Pizza, Advanced Coupons, Customer Reviews for WooCommerce, Dokan, WooCommerce Analytics, WooCommerce Subscriptions, Cart Abandonment Recovery for WooCommerce, YITH WooCommerce Wishlist, WCBoost Wishlist, WPC Smart Wishlist for WooCommerce, RestroPress, FunnelKit Funnel Builder

**Memberships:** MemberPress, Paid Membership Pro, ARMember, SureMembers, Ultimate Member, WP-Members, DigiMember, Restrict Content Pro, WooCommerce Memberships, Simple Membership, WP User Manager

**LMS and courses:** LearnDash, LifterLMS, TutorLMS, LearnPress, Sensei LMS, MasterStudy LMS, Academy LMS, Thrive Apprentice, WPLMS

**Events and bookings:** The Events Calendar, Events Manager, Event Tickets, Eventin, Event Espresso, FooEvents, Amelia Booking, Bookly, LatePoint, Easy Appointments, WP Booking Calendar, Fluent Booking, Appointment Hour Booking, Simply Schedule Appointments, Timetable and Event Schedule, Webba Booking Lite, WP Travel Engine, WooCommerce Bookings, Tickera

**Community and forums:** BuddyBoss, BuddyPress, bbPress, PeepSo, wpForo, Asgaros Forum, AnsPress, Fluent Community, wpDiscuz

**Affiliates:** AffiliateWP, Easy Affiliate, SliceWP, Fluent Affiliate

**Donations:** GiveWP, Charitable, Fundraising for WooCommerce

**Gamification and engagement:** GamiPress, myCred, WP ULike, WP Post Ratings, RafflePress, Site Reviews, kk Star Ratings, WP Recipe Maker, WP-Polls, Strong Testimonials

**Email capture and newsletters:** MailPoet Forms, Newsletter, Thrive Leads, Hustle, Email Subscribers & Newsletters

**Quizzes and surveys:** Quiz and Survey Master, Thrive Quiz Builder, UserFeedback

**Support and CRM:** Awesome Support, Jetpack CRM, Groundhogg, FluentCRM, Fluent Support, CiviCRM, Tawk.to, FunnelKit Automations

**Security:** All-In-One Security (AIOS), Defender Security, Kadence Security (formerly Solid Security / iThemes Security), Two Factor, Temporary Login Without Password

**Other WordPress plugins:** Business Directory Plugin, Fluent Boards, FluentAuth, FluentSMTP, My Sticky Elements, Ninja Tables, WP Job Manager, Download Manager, Download Monitor, PrettyLinks, Advanced Custom Fields, UpdraftPlus, WordPress core events

You can also capture UTM parameters from any trigger by enabling the feature in settings.

## Supported Action Platforms (Receivers)

AFI can send your data to any of these 300+ destinations.

**CRM and sales:** Salesforce, HubSpot, Zoho CRM, Zoho Bigin, Pipedrive, Copper CRM, Insightly, Close CRM, Capsule CRM, Agile CRM, Apptivo, Attio, CiviCRM, ClinchPad CRM, CompanyHub, Dynamics 365 CRM, Dynamics 365 Sales, Dynamics 365 Marketing, Flowlu, FollowUpBoss, Freshworks CRM (Freshsales), HighLevel, Less Annoying CRM, LionDesk, Nimble, Nutshell CRM, Onehash.ai, Salesflare, Salesmate, SuiteDash, Vtiger CRM, Wealthbox CRM, Zendesk Sell, Apollo.io, Fluent CRM, Jobber, Keap, LocaliQ, Mailshake, Ontraport, Outreach, Salesloft, Scoro CRM, SharpSpring, Success.ai, SuperOffice CRM, Teamleader Focus, noCRM.io

**Real estate:** Lofty (formerly Chime), Real Geeks, Wise Agent, Sierra Interactive, CINC, IXACT Contact, DealMachine, Podio, Dotloop, SkySlope, Brokermint

**Mortgage:** Total Expert, Jungo, Shape Software, Encompass (ICE Mortgage), Velocify, Insellerate, Big Purple Dot

**Legal:** Clio, PracticePanther, Filevine

**Healthcare and wellness:** Practice Better, DrChrono, Mindbody

**Home services:** ServiceTitan, Housecall Pro, JobNimbus, AccuLynx

**Nonprofit and church management:** Bloomerang, DonorPerfect, NeonCRM, Givebutter, Breeze ChMS, Donorbox

**Insurance:** EZLynx

**Email marketing and automation:** Mailchimp, ActiveCampaign, Brevo, Constant Contact, AWeber, GetResponse, Klaviyo, Kit, MailerLite, MailerLite Classic, Omnisend, Drip, Encharge, EngageBay, Mautic, Ortto, Customer.io, Braze, Attentive, Autopilot, Benchmark Email, BombBomb, Acelle Mail, Acumbamail, Audienceful, beehiiv, Cakemail, Campaigner, Campaign Monitor, Campayn, CleverReach, Copernica, Curated, DirectIQ, Doppler, EasySendy, Elastic Email, Emailchef, Emailit, EmailOctopus, Enormail, Flodesk, iContact, Instantly, Keila, Laposta, lemlist, Loops, MailBluster, Mailcoach, Maileon, Mailercloud, Mailify, Mailjet, Mail Mint, Mailmodo, MailPoet, Mailrelay, Mailster, MailUp, MailWizz, Moosend, Newsletter, Pabbly Email Marketing, Rapidmail, Resend, Robly, Saleshandy, Sales.Rocks, Sarbacane, Selzy, Sender, SendFox, Sendlane, SendPulse, SendX, Sendy, Smartlead.ai, SmartrMail, Snov.io, System.io, Vertical Response, Woodpecker.co, Zoho Campaigns, Zoho Marketing Automation, Iterable, MailerSend, Maropost, SendGrid, Vision6, Adobe Campaign / Journey Optimizer, Kartra, Marketo, Pardot (Account Engagement), Courrielleur, Emma, Listmonk, Mailgun Transactional, MailingBoss, Mumara, Sendinblue (legacy Brevo)

**Spreadsheets and databases:** Google Sheets, Airtable, Smartsheet, Zoho Sheet, Zoho Creator, Knack, Quickbase, Ragic, Microsoft Dataverse, Kintone, Notion, Softr

**Project management and productivity:** Asana, ClickUp, Trello, Monday.com, Microsoft To Do, Fluent Boards, Google Tasks, Field Nation, ServiceM8, Teamwork, Todoist, Wrike

**Calendars and scheduling:** Google Calendar, Cal.com, Calendly, Acuity Scheduling, AddCal, Appointment Hour Booking, Fluent Booking

**Helpdesk and customer service:** Freshdesk, Zendesk, Zoho Desk, Zoho FSM, Fluent Support, Intercom, Dynamics 365 Customer Service, Dynamics 365 Field Service, Help Scout, Gist, LiveChat, Tawk.to, Tidio, Crisp, Front, Re:amaze

**Team communication and SMS:** Slack, Microsoft Teams, Twilio, Pushover, EZ Texting, JustCall, SlickText, WhatsApp Business Platform, Aircall, OpenPhone

**Webinars and events:** Zoom Webinar, WebinarJam, EverWebinar, Demio, Livestorm, Airmeet, BigMarker, Eventbrite, eWebinar, GoToWebinar, WebinarGeek, Zoho Meeting, Adobe Connect, ON24

**E-signature and documents:** DocuSign, PandaDoc

**Ad conversion tracking and analytics:** Google Analytics 4, Meta Conversions API

**Customer data platforms:** Twilio Segment

**File storage:** Google Drive, Dropbox

**E-commerce:** WooCommerce, Shopify

**Courses and memberships:** Teachable, Thinkific, Memberful

**Finance and operations:** Zoho Books, Zoho Inventory, Zoho Invoice, Zoho Billing (Subscriptions), Zoho People, Fortnox, FreeAgent, FreshBooks, e-conomic, Lexoffice, Moneybird, MYOB, QuickBooks Online, sevDesk, Stripe, Visma eAccounting, Xero

**HR and workforce management:** Deputy, Employment Hero, Personio, Recruitee, Workable, Zoho Recruit, Greenhouse

**WordPress plugins:** WordPress (create post), Gravity Forms, WPForms, bbPress, BuddyBoss, BuddyPress, Charitable, GiveWP, AffiliateWP, Fluent Affiliate, Fluent Community, GamiPress, Events Manager, Academy LMS, LatePoint, myCred, Ninja Tables, The Events Calendar, Groundhogg, Jetpack CRM, SupportCandy, WP Job Manager, Paid Memberships Pro, LearnDash, Tutor LMS, Easy Digital Downloads, Advanced Custom Fields, Flamingo, Awesome Support, SliceWP, PropertyHive, WP ERP, Sensei LMS, SureMembers, WP Booking Calendar, WP Event Manager, Eventin, LifterLMS, Fluent Cart, Site Reviews, MasterStudy LMS, WP CRM System, Ultimate Member

**Developer and webhooks:** Webhook (any method, custom headers and body), Zapier (send data to a Zapier webhook)

Cannot find your platform? Email support@advancedformintegration.com to request it. Many integrations were added by user request.

## Free vs Pro

AFI is fully functional for free. Every platform above is available in the free version with core field support, unlimited integrations, conditional logic, the activity log with resend, and multi-account support.

A Pro license adds:

* **All form fields**, not just the core set.
* **Custom fields and tags** for CRMs and email platforms (for example Salesforce, HubSpot, Mailchimp, Zoho, and many more).
* **Inbound webhooks**, so external systems can trigger actions inside your WordPress site.
* **Platform-specific advanced actions**, for example HubSpot companies, deals, tickets, and tasks; Salesforce record types; and separate Google Sheets rows for each WooCommerce line item.
* **Priority email support** and frequent feature updates.

See [pricing and the full feature comparison](https://advancedformintegration.com/pricing/).

== Installation ==

### Automatic install from the WordPress dashboard
1. Log in to your admin panel.
2. Navigate to Plugins, then Add New.
3. Search for Advanced Form Integration.
4. Click Install Now, then Activate.
5. Open Advanced Form Integration, then Add New Integration to begin.

### Manual install
1. Download the plugin ZIP file from WordPress.org.
2. In your admin panel, go to Plugins, then Add New, then Upload Plugin.
3. Choose the file and click Install Now, then Activate.

== Frequently Asked Questions ==

= Do I need coding skills? =
No. Everything is point and click with visual field mapping. If you can build a WordPress form, you can set up AFI.

= Will this slow down my site? =
No. Integrations run asynchronously in the background using Action Scheduler. Forms submit instantly and the data sync happens behind the scenes.

= Does the free version have limits? =
The free version supports unlimited integrations and all 300+ platforms with core fields. Custom fields, tags, inbound webhooks, and some platform-specific advanced actions require a Pro license.

= What happens if an integration fails? =
Open the Activity Log to see the exact error. Common fixes are re-authorizing an expired token, adjusting a field mapping, or clicking Resend to retry. Most issues are resolved in a couple of minutes.

= Can I connect multiple accounts for the same platform? =
Yes. You can connect several accounts for one platform, each labelled separately. This is ideal for agencies managing multiple clients.

= How is my data handled? =
Integrations make direct API calls from your site to each platform using OAuth 2.0 or API keys. Credentials are stored on your own server and your submission data does not pass through any AFI servers.

= Connection error, how can I re-authorize Google Sheets? =
If authorization stops working, go to https://myaccount.google.com/permissions, remove the app permission, then authorize again from the plugin settings.

= Getting a "The requested URL was not found on this server" error while authorizing Google Sheets? =
Check your permalink settings. Go to Settings, then Permalinks, select Post name, then Save.

= Do I need to map all fields when creating an integration? =
No, but any required fields must be mapped.

= How can I get support? =
Email support@advancedformintegration.com and the team will be happy to help.

== Screenshots ==

1. The integration editor: Contact Form 7 to Mailchimp with field mapping.
2. 300+ destinations, searchable from a single picker.
3. Activity log with one-click Resend on failed submissions.
4. Conditional logic: send only when your rules match.
5. Conditional logic: all condition types.
6. Manage every integration from one dashboard, with status at a glance.

== Changelog ==

= 2.9.0 [2026-09-22] =
**New Trigger Platforms**

* [New] **WordPress** - Start an integration from core site events: new user registration, profile updated, user logs in, post published, new comment, and new media uploaded.
* [New] **FluentCRM** - Contact created, contact updated, tags added, lists added, and status changed.
* [New] **WP User Manager** - User approved or rejected, and group membership joins, leaves, approvals, and rejections.
* [New] **CiviCRM** - Contact created, contact updated, contribution created, and membership created.
* [New] **WPLMS** - Course, quiz, assignment, and unit completion, each carrying course details, user name and email, and marks where the event provides them.
* [New] **UpdraftPlus** - Backup succeeded, backup finished with errors, and backup failed.
* [New] **Webba Booking** - Booking created, booking paid, and booking cancelled.
* [New] **Fluent Support** - Ticket opened by an agent, and ticket opened by a client.
* [New] **Tawk.to** - Pre-chat and offline form submissions.
* [New] **FunnelKit Funnel Builder** - Funnel step viewed, and funnel step converted.
* [New] **FunnelKit Automations** - Contact added to a list, and tag added to a contact.
* [New] **Tickera** - New ticket order.
* [New] **RestroPress** - Order completed.
* [New] **Advanced Custom Fields** - Post with ACF fields updated.

**Fixed**

* [Fixed] **wpForo** - wpForo was listed as a trigger source but showed no triggers when selected, because the function that supplies them was named incorrectly and was never called. Its topic and reply triggers now work.
* [Fixed] **Thrive Quiz Builder** - Listed the same way and equally unusable, from a misspelled internal key that meant its trigger file was never loaded. Quiz completion now works.

**Improved**

* [Improved] **wpForo** - Topic and reply triggers now also send created and modified timestamps, moderation status, the privacy flag, slug, tags, attachment flag, view and reply counts, SEO meta, the direct reply URL, and parent and thread IDs for nested replies. Added a "User registers" trigger that fires only on registrations made through wpForo, not on every WordPress user created anywhere on the site.
* [Improved] **Hustle** - First Name and Last Name are now available as mappable fields.

= 2.8.6 [2026-09-13] =
**Fixed**

* [Fixed] **Emma** - The Group dropdown saved each group's position in the list instead of its Emma group id, so every submission failed with either "group(s) do not exist" or "The field [group_ids] is required". Emma identifies a group by `member_group_id`. **Existing Emma integrations need their Group selected again once after updating**, because the previously stored value cannot be mapped to a real group id automatically.
* [Fixed] **Monday.com** - An apostrophe in any mapped field broke the generated GraphQL mutation and Monday.com rejected the whole request with a 400 PARSING_ERROR. Line breaks and other control characters in a field value were affected the same way. Values are now escaped as GraphQL and JSON strings require.
* [Fixed] **Zoho Sheet** - Row values and the worksheet name were placed in the request URL without encoding, so an "&" or "#" in a submitted field silently truncated or corrupted the row, a "+" arrived as a space, and worksheet names containing spaces could fail.
* [Fixed] **VerticalResponse, Robly, EngageBay** - The existing-contact lookup sent the email address unencoded, so plus-addressed addresses such as name+tag@example.com never matched and a duplicate contact was created instead of the existing one being updated.
* [Fixed] **Salesforce** - Convert Lead could be rejected when a mapped value contained an HTML entity such as "&nbsp;". Values in the SOAP request are now escaped as XML requires.

**Improved**

* [Improved] Action settings now enforce their required fields. An integration can no longer be saved without choosing the list, board, group, sheet or account it sends to, which previously produced an integration that saved cleanly but then failed on every submission. Genuinely optional selections, such as record owner or assigned user, remain optional.
* [Added] **Monday.com** - New `adfoin_monday_create_item_mutation` filter for overriding the generated Create Item mutation.

= 2.8.5 [2026-08-31] =
**Fixed**

* [Fixed] **Emma** - Fixed subscriber email placement in signup payload to comply with Emma API requirements, and improved group ID handling.

= 2.8.4 [2026-08-25] =
**Fixed**

* [Fixed] **Zendesk Support** - Widened the OAuth scope requested when connecting an account so account-configuration endpoints (e.g. ticket field definitions) authorize correctly instead of being silently denied.
* [Fixed] **Zendesk Support** - The "Update & Authorize" action on an already-connected account now always re-runs authorization instead of silently keeping the existing grant, so accounts can be re-authorized after a scope change without re-entering credentials. Existing accounts should reconnect once to pick up the wider scope.

= 2.8.3 [2026-08-21] =
**Compatibility**

* [Improved] Confirmed compatibility with WordPress 7.1.

= 2.8.2 [2026-08-11] =
**Improved**

* [Improved] **Pipedrive** - Migrated organization, person, deal, and activity create/update requests to Pipedrive API v2 ahead of API v1's deprecation. Custom fields of every type (enum, set, monetary, address, date/daterange, time/timerange, and more) are now sent with the exact structure v2 requires, company-domain routing is handled automatically, and Activity due date/time values are normalized to the format v2 expects. Existing accounts require no changes — just update the plugin.

= 2.8.1 [2026-08-08] =
**Improved**

* [Improved] **Zendesk Support** - Added OAuth 2.0 as the connection method for new accounts, per Zendesk's current guidance.

= 2.8.0 [2026-07-30] =
**New Platforms**

* [New] **Telegram** - Send a message to a chat/channel via a bot, with chat list fetching.
* [New] **KIRIM.EMAIL** - Add or remove subscribers on a list using per-account credentials.

**New**

* [New] Added "Manage Accounts" shortcut links next to account/credential selectors on action forms, linking directly to each integration's Accounts tab.

**Fixed**

* [Fixed] Corrected the lemlist setup instructions to point to Settings > Integrations > API & Webhooks.

= 2.7.0 [2026-07-23] =
**Security**

* [Security] Fixed a missing-authorization vulnerability (CWE-862) that let any logged-in user overwrite a connected platform's OAuth tokens; update recommended for all users.

**New Platforms**

* [New] **Groundhogg**, **Jetpack CRM**, **SupportCandy**, **WP Job Manager**, **BuddyPress**, **Paid Memberships Pro**, **LearnDash**, **Tutor LMS**, **Easy Digital Downloads**, **Advanced Custom Fields**, **Flamingo**, **Awesome Support**, **SliceWP**, **PropertyHive**, **WP ERP**, **Sensei LMS**, **SureMembers**, **WP Booking Calendar**, **WP Event Manager**, **Eventin**, **LifterLMS**, **Fluent Cart**, **Site Reviews**, **MasterStudy LMS**, **WP CRM System**, **Ultimate Member** - new local WordPress-integration action platforms (create/update users, contacts, tickets, enrollments, and records directly against the installed plugin, no external API key needed). Where a platform already existed as a trigger, the action uses a distinct slug so both can be enabled independently.

= 2.6.0 [2026-07-16] =
**New Platforms**

* [New] **DocuSign** - Send an envelope from a template for e-signature, with multi-account OAuth2 and Pro-tier prefilled tab values.
* [New] **Google Analytics 4** - Send server-side conversion events via the Measurement Protocol, with Pro-tier custom params and hashed user data for enhanced matching.
* [New] **Meta Conversions API** - Send server-side Lead/Contact/CompleteRegistration events to Facebook/Instagram Ads, with automatic PII hashing and Pro-tier event dedup + custom data.
* [New] **Greenhouse** - Submit job applications to a specific opening via the public Job Board API, with Pro-tier custom application questions.
* [New] **Marketo** - Create or update leads via OAuth2 client credentials, with Pro-tier custom fields and campaign triggering.
* [New] **PandaDoc** - Create and send a document from a template, with Pro-tier merge-field tokens.
* [New] **Pardot (Account Engagement)** - Create or update Prospects via Salesforce OAuth2, with Pro-tier custom fields.
* [New] **Twilio Segment** - Track events into a Segment workspace via the HTTP Tracking API, with Pro-tier custom properties.
* [New] **WhatsApp Business Platform** - Send approved template messages via the Cloud API, with Pro-tier header media and extra parameters.
* [New] **Kartra** - Add leads to a list, with per-account credentials and Pro-tier custom fields.
* [New] **noCRM.io** - Create leads, with Pro-tier client-folder creation and extra description fields.
* [New] **Adobe Campaign / Journey Optimizer** - Create profiles via OAuth2 Server-to-Server, with Pro-tier profile upsert and workflow triggering.
* [New] **Adobe Connect** - Create or update users and enrol them into groups via the XML Web Services API.
* [New] **ON24** - Register attendees for a webcast/event.
* [New] **Dotloop**, **SkySlope**, **Brokermint** - Real estate transaction management, with full OAuth2/PKCE popup flows.
* [New] **Total Expert**, **Jungo**, **Shape Software** - Mortgage/real estate CRM lead creation.
* [New] **Encompass (ICE Mortgage)**, **Velocify**, **Insellerate**, **Big Purple Dot** - Mortgage LOS and marketing platforms.
* [New] **Clio**, **PracticePanther**, **DrChrono**, **Filevine**, **ServiceTitan**, **Mindbody** - Practice/case management platforms, several with new OAuth2 support.
* [New] **Housecall Pro**, **JobNimbus**, **AccuLynx** - Home-services CRM and job management.
* [New] **Bloomerang**, **DonorPerfect**, **NeonCRM**, **Givebutter** - Nonprofit donor management.
* [New] **Breeze ChMS**, **EZLynx** - Church management and insurance agency management.
* [New] **Wise Agent**, **Sierra Interactive**, **CINC**, **IXACT Contact**, **DealMachine**, **Podio** - Real estate CRM and investor tooling.

**Fixed**

* [Fixed] Corrected the API contract (endpoints, auth, or request/field shapes) for over a dozen previously mis-implemented integrations, including Dotloop, SkySlope, Brokermint, Jungo, Total Expert, Encompass, Velocify, Insellerate, Big Purple Dot, Podio, Adobe Campaign, noCRM.io, Kartra, Filevine, and ServiceTitan.
* [Fixed] Several Pro-tier action components were missing their JS file entirely, silently breaking the field-mapping UI for Pro users on those platforms — now present for every enabled Pro integration.

= 2.5.2 [2026-07-14] =
**New Platforms**

* [New] **Lofty (formerly Chime)** - Create real estate leads, with automatic Home/Work location tagging and lead-type mapping.
* [New] **Real Geeks** - Create real estate leads via the Incoming Leads API, with HTTP Basic Auth.
* [New] **Practice Better** - Create clients (intake), with OAuth2 client credentials and automatic access-token refresh.

= 2.5.1 [2026-07-12] =
**Bug Fixes**

* [Fixed] **Planning Center** - Email, Phone Number, and Address creation were failing Planning Center's API validation because the required `location` attribute was never sent. Contact info now saves correctly on new and updated People records.

**Improved**

* [Improved] **Planning Center** - Clearer setup instructions with a clickable link to the Personal Access Tokens page.

= 2.5.0 [2026-07-12] =
**New Platforms**

* [New] **Zoho Inventory** - Search-or-create customers and items, then create sales orders, invoices, and packages, with multi-organization support and WooCommerce order line-item mapping.
* [New] **Zoho Invoice** - Search-or-create customers and items, then create invoices, estimates, recurring invoices, and customer payments, with multi-organization support.
* [New] **Zoho Billing (Subscriptions)** - Search-or-create customers, then create subscriptions, hosted checkout pages, or one-off invoices, with a live plan picker.
* [New] **Zoho FSM** - Search-or-create customers, then create service appointments and work orders.
* [New] **Zoho Creator** - Add records to any Creator form via a flexible field-mapping textarea, with a per-line-item mode for WooCommerce orders.
* [New] **Zoho Recruit** - Create candidates, with multi-organization support.

= 2.4.0 [2026-07-09] =
**New Platforms**

* [New] **EZ Texting** - Create SMS contacts, with OAuth2 client-credential token exchange.
* [New] **Field Nation** - Create work orders with type of work, location, schedule, and pay.
* [New] **GoToWebinar** - Register attendees for live webinars, with multi-account OAuth2.
* [New] **JustCall** - Create Sales Dialer contacts.
* [New] **Kintone** - Create records in a Kintone app.
* [New] **LatePoint** - Manage customers, bookings, and activity notes directly through the plugin's own models.
* [New] **Lexoffice** - Create contacts in the German/Austrian accounting platform.
* [New] **LiveChat** - Create customers via the LiveChat Agent API.
* [New] **LocaliQ** - Submit leads to a LocaliQ intake URL.
* [New] **MailerSend** - Send transactional emails.
* [New] **Mailshake** - Add recipients to an outreach campaign.
* [New] **Maropost** - Create or update contacts.
* [New] **Moneybird** - Create contacts in the Dutch accounting platform, with OAuth2.
* [New] **MYOB** - Create customers in AccountRight Live, with OAuth2.
* [New] **myCred** - Award, deduct, set, or log points directly through the plugin's own functions.
* [New] **Ninja Tables** - Create, update, or delete table rows.
* [New] **Notion** - Create pages in a Notion database.
* [New] **Ontraport** - Create or update contacts.
* [New] **Outreach** - Create prospects, with OAuth2.
* [New] **Personio** - Create recruiting applicants.
* [New] **QuickBooks Online** - Create customers and invoices, with OAuth2.
* [New] **Recruitee** - Create candidates.
* [New] **Salesloft** - Create or update people.
* [New] **Scoro CRM** - Create or update contacts.
* [New] **SendGrid** - Add or update marketing contacts, with a live list picker.
* [New] **ServiceM8** - Create clients and jobs.
* [New] **sevDesk** - Create contacts, with optional email, phone, and address attached across sevDesk's Contact, CommunicationWay, and ContactAddress endpoints.
* [New] **SharpSpring** - Create or update leads via the JSON-RPC API.
* [New] **Shopify** - Create customers, with full address and marketing-consent support.
* [New] **SlickText** - Create SMS contacts with opt-in status control.
* [New] **Softr** - Create records in a Softr Database table, with a dynamic field mapper.
* [New] **Stripe** - Create customers.
* [New] **Success.ai** - Add leads to a campaign.
* [New] **SuperOffice CRM** - Create company contacts, with a flexible field-path builder for nested data.
* [New] **Tawk.to** - Add or update chat contacts.
* [New] **Teamleader Focus** - Create contacts and companies, with OAuth2.
* [New] **Teamwork** - Create tasks in Teamwork.com projects.
* [New] **The Events Calendar** - Create, update, or delete events.
* [New] **Tidio** - Create chat contacts.
* [New] **Todoist** - Create tasks, with live project/section pickers and label management.
* [New] **Vision6** - Add contacts to a list.
* [New] **Visma eAccounting** - Create customers, with OAuth2.
* [New] **WebinarGeek** - Register attendees for live or on-demand webinars.
* [New] **WooCommerce** - Create customers, orders, subscriptions, and bookings directly through WooCommerce's own APIs.
* [New] **Workable** - Create job candidates.
* [New] **Wrike** - Create tasks in a selected folder.
* [New] **Xero** - Create/update contacts and invoices, with multi-tenant organisation selection.
* [New] **Zoho Meeting** - Register contacts to a Zoho Webinar.

**New Features**

* [New] **WordPress** - Added an "Update User Role" task to change an existing user's role (looked up by ID, username, or email) from a form submission.

**Bug Fixes**

* [Fixed] **Pabbly Email Marketing** - Migrated to Pabbly's v2 Subscribers/Lists API; the upsert flow now calls `/v2/subscribers` and `/v2/lists/add-subscriber` instead of the deprecated v1 endpoints.

= 2.3.0 [2026-07-07] =
**New Platforms**

* [New] **Deputy** - Create employees via multi-account OAuth2, with automatic per-install URL and company discovery.
* [New] **e-conomic** - Create customers in the Danish accounting platform, with configurable VAT zone, payment terms, customer group, and currency defaults.
* [New] **Employment Hero** - Create employees via OAuth2, with automatic organisation discovery.
* [New] **Eventbrite** - Create free-ticket attendees via the Orders API.
* [New] **eWebinar** - Register attendees for on-demand or scheduled webinars, with custom fields support.
* [New] **Fortnox** - Create customers in the Swedish accounting platform via OAuth2.
* [New] **FreeAgent** - Create contacts, with sandbox/production environment switching.
* [New] **FreshBooks** - Create clients, with automatic account discovery.
* [New] **Gist** - Create or update contacts.
* [New] **Google Tasks** - Create tasks in a selected task list, with multi-account OAuth2.
* [New] **Help Scout** - Create support conversations or customer records via the Mailbox API.
* [New] **Iterable** - Subscribe contacts to a list, with US/EU data-center support.
* [New] **Jobber** - Create clients and jobs via the GraphQL API.
* [New] **Keap** - Create or update contacts with a comprehensive field set covering names, emails, phones, addresses, and social accounts.

= 2.2.1 [2026-06-18] =
**Security**

* [Security] **Freshworks CRM** - Added a capability check to the Freshsales account, contact, and deal field AJAX handlers so stored CRM credentials can only be used by users who manage the plugin.

= 2.2.0 [2026-06-17] =
**New Platforms**

* [New] **Discord** - Send a message to a server channel via a Discord bot, with account, server, and channel pickers.
* [New] **Dotdigital** - Create or update contacts with opt-in type and first/last name mapping.

= 2.1.2 [2026-06-10] =
**Bug Fixes**

* [Fixed] **WooCommerce** - Integrations on large orders silently failed to run with no log entry. Orders whose data exceeded Action Scheduler's queued-argument size limit were dropped without dispatching; such submissions now fall back to synchronous processing so they are always delivered.

= 2.1.1 [2026-06-10] =
**Critical Security Fix**

* [Security] Fixed unauthenticated privilege escalation vulnerability in WooCommerce Create Customer and FluentAffiliate Create Affiliate actions
* [Security] Added server-side role validation to prevent privileged roles (administrator, editor, author, shop_manager) from being assigned via form submissions
* [Security] Added security logging for blocked role assignment attempts
* [Added] New filters: `adfoin_woocommerce_blocked_roles` and `adfoin_fluentaffiliate_blocked_roles` for customizing blocked roles list

**This is a critical security update. All users should update immediately.**

= 2.1.0 [2026-06-08] =
Enhanced platform reliability, dedupe strategies.

* [New] **Cal.com** - Added event-type support with booking payload builder and per-event fields.
* [New] **Calendly** - Re-enabled with support for advanced workflows.
* [New] **Knack** - Added platform with typed field formatting and field-type caching.
* [Improved] **Google Sheets** - Enhanced reliability with better sheet/worksheet handling, locks, and dedupe options.
* [Improved] **WooCommerce** - Added order status trigger, custom field persistence, and improved dispatch/error handling.
* [Improved] **Freshsales** - Normalized subdomain.
* [Improved] **Salesforce** - Added record types, pagination support.
* [Improved] **HubSpot** - Added date hints, pagination, retry logic, upsert capability, and dedupe guards.
* [Improved] **Pipedrive** - Implemented company-domain v2 routing, dedupe by email/phone, domain cache, and switched to PUT for v1 updates.
* [Improved] **Mailchimp** - Added 429 retry logic, email validation.
* [Improved] **Constant Contact** - Added multi-list support, 409 upsert fallback.
* [Improved] **AWeber** - Serialized refresh token handling, keepalive logic, pagination handling.
* [Improved] **Attio** - Added dedupe guard, date normalization.
* [Improved] **Bigin** - Surface errors in UI, improved token handling and defaults.
* [Improved] **CapsuleCRM & MailerLite** - Enhanced pagination, retries, dedupe, and error reporting.

= 2.0.0 [2026-05-23] =
Major modernization release. Every existing integration keeps working unchanged (there are no breaking changes), but under the hood almost every subsystem has been reviewed, hardened, and tuned.

* [Major] Rebuilt the admin UI on Vue 3 (replacing Vue 2.7, which reached end-of-life in 2023). Snappier integration editor, smaller admin JS payload, future-proof.
* [Security] Hardened SQL across 14 trigger field-discovery handlers and the form, field, and task admin AJAX endpoints: parameterised queries plus explicit `manage_options` checks throughout.
* [Security] Added explicit capability and nonce checks to 125 platform credential-list AJAX endpoints across 83 platform files.
* [Security] Tightened the resend-log handler, the review-dismiss handler, and the integration save path.
* [Security] Same-origin guard on the post-save redirect helper, plus a fix for an object-injection vector in the Gravity Forms list-field handler.
* [Improved] Database indexing on `adfoin_integration` and `adfoin_log`: PRIMARY KEY plus covering indexes on the columns the plugin actually filters by. An idempotent migration runs once on the first wp-admin pageload after upgrade. Busy sites' log page goes from seconds to milliseconds.
* [Improved] Bulk activate, deactivate, and delete on the integrations list now executes a single SQL statement instead of one round-trip per row.
* [Improved] Log page status counts collapse four `COUNT(*)` scans into one query; the integration filter dropdown uses an index-only `DISTINCT` instead of a full-table `LEFT JOIN`.
* [Improved] Log table integration titles are bulk-preloaded once per page instead of N per-row queries.
* [Improved] Credentials option is no longer autoloaded: front-end pageviews skip loading the credential blob into memory.
* [Improved] WooCommerce trigger resolves item-meta keys once per submission instead of once per line item.
* [Improved] OAuth token-exchange now uses a 30-second HTTP timeout matching the refresh-token call (previously could hang on a slow provider).
* [Improved] OAuth `redirect_uri` no longer double-encoded.
* [Improved] Single-source credentials: `ADFOIN_OAuth_Manager` now routes through the canonical credential store, with a one-time additive migration that merges any legacy per-platform records (no accounts lost).
* [Improved] Bundled Action Scheduler updated to 3.9.3.
* [Improved] Numerous WPCS and Plugin Check compliance cleanups (`wp_unslash`, `wp_json_encode`, escaping, deprecated `current_time('timestamp')`, `@set_time_limit`).
* [Fixed] Log page CodeMirror no longer throws "unrecognized expression: ##" on newer jQuery versions.
* [Fixed] Editing a deleted integration id (`?action=edit&id=999999`) renders a clear "not found" notice instead of a PHP warning.
* [Fixed] The single-row Duplicate handler no longer shows a misleading "duplicated" success notice when the underlying INSERT fails.

== Upgrade Notice ==

= 2.9.0 =
Adds 15 new trigger sources, including WordPress core events (registrations, posts, comments, media uploads), FluentCRM, CiviCRM, WPLMS, UpdraftPlus, and FunnelKit. Also fixes wpForo and Thrive Quiz Builder, which were listed as trigger sources but loaded no triggers when selected. No action needed after updating; existing integrations are unaffected.

= 2.8.6 =
Emma users: after updating, open your Emma integration, select your Group again, and save. The old group value was stored incorrectly and cannot be migrated automatically. Also fixes Monday.com submissions containing apostrophes and Zoho Sheet rows containing "&" or "#".

= 2.8.5 =
Fixes Emma integration subscriber signup payload structure.

= 2.8.4 =
Zendesk Support OAuth accounts should reconnect (Edit > Update & Authorize) after updating — the requested scope was widened so account-configuration endpoints work correctly.

= 2.8.2 =
Pipedrive integration now uses API v2 (v1 is being retired by Pipedrive) — update recommended for all Pipedrive users.

= 2.8.1 =
Zendesk Support now offers OAuth 2.0 for new accounts; existing API-token accounts keep working unattended.

= 2.8.0 =
Adds Telegram and KIRIM.EMAIL integrations, and Manage Accounts shortcut links on action forms.

= 2.7.0 =
Security fix: any logged-in user could previously overwrite a connected platform's OAuth tokens via an unauthenticated-by-role auth_redirect request on 29 OAuth-based platforms (MailUp, Bigin, CleverReach, Constant Contact, Google Calendar/Drive/Sheets/Tasks, GoToWebinar, LionDesk, Salesforce, Outreach, Vertical Response, Moneybird, BombBomb, and 15 Zoho platforms). Update is recommended for all users. Also adds 26 new local WordPress-integration action platforms (Groundhogg, Jetpack CRM, LearnDash, Tutor LMS, Ultimate Member, and more).

= 2.6.0 =
Adds 30+ new platform integrations (DocuSign, GA4, Meta Conversions API, Greenhouse, Marketo, PandaDoc, Pardot, Twilio Segment, WhatsApp, Kartra, noCRM.io, Adobe Campaign/Connect, Dotloop, SkySlope, Clio, and more) and fixes the API contract on over a dozen previously mis-implemented ones. Credential storage changed for Dotloop, Jungo, SkySlope, Total Expert, and Kartra — reconnect those accounts after updating.

= 2.5.2 =
Adds Lofty, Real Geeks, and Practice Better integrations.

= 2.5.1 =
Fixes Planning Center contact info (email/phone/address) failing to save due to a missing required API field. Recommended for all Planning Center users.

= 2.5.0 =
Adds 6 new Zoho platform integrations: Zoho Inventory, Zoho Invoice, Zoho Billing (Subscriptions), Zoho FSM, Zoho Creator, and Zoho Recruit. No breaking changes.

= 2.4.0 =
Adds 48 new platform integrations (including WooCommerce, Xero, QuickBooks Online, Shopify, Stripe, and Todoist) plus a WordPress "Update User Role" task. Also migrates the Pabbly Email Marketing integration to Pabbly's v2 API — update if you use Pabbly, since v1 endpoints are being retired. No breaking changes for other platforms.

= 2.3.0 =
Adds 14 new platform integrations: Deputy, e-conomic, Employment Hero, Eventbrite, eWebinar, Fortnox, FreeAgent, FreshBooks, Gist, Google Tasks, Help Scout, Iterable, Jobber, and Keap. No breaking changes.

= 2.2.1 =
Security fix: adds a capability check to the Freshworks CRM (Freshsales) field AJAX handlers. Recommended for all users.

= 2.2.0 =
Adds Discord and Dotdigital integrations. No breaking changes.

= 2.1.1 =
Critical security update. All users should update immediately. Adds server-side role validation to block privilege escalation via form submissions.