=== Advanced Form Integration — Connect Forms to 200+ Apps ===
Contributors: afisupport, nasirahmed, freemius
Tags: form integration, crm, webhooks, automation, contact form 7
Requires at least: 3.0.1
Tested up to: 6.9
Stable tag: 2.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress forms to 200+ apps with no code and no per-task fees. Send leads, orders, and signups to your CRM, email, or sheets. Your data stays on your site.

== DESCRIPTION ==

## Overview

**Stop paying per-task fees to move your own data.**

Advanced Form Integration (AFI) connects your WordPress forms and site events to 200+ external platforms (CRMs, email marketing, Google Sheets, helpdesks, webhooks) with zero code. Set it up once and form submissions, WooCommerce orders, bookings, and more flow into the tools you already use, in real time.

AFI runs entirely on your own site, so integrations fire directly from WordPress to each platform's API. There are no task limits, no metering, and no per-action billing. Whether you process 100 submissions a month or 100,000, the plugin works the same, and your form data and credentials never leave your server.

AFI requires no coding knowledge and can be configured in minutes. It is built for marketers, sales teams, agencies, and business owners who want to streamline workflows and improve data accuracy.

[Website](https://advancedformintegration.com/) | [Documentation](https://advancedformintegration.com/docs/afi/) | [Tutorial Videos](https://www.youtube.com/channel/UCyl43pLFvAi6JOMV-eMJUbA)

## Why Choose AFI

* **No per-task fees.** AFI has no task meter, so your submissions are unlimited on every plan, free or paid.
* **Your data never leaves your server.** Integrations fire directly from your site to each platform's API. No third-party service sits in the middle of your leads, orders, or customer details. Better for privacy and GDPR.
* **Built for WordPress.** Native triggers for 70+ form plugins plus WooCommerce, LMS, memberships, and bookings. No brittle webhook glue to maintain.
* **Fix and resend, not lost data.** Every API call is logged. If one fails, view the exact request and response, edit the data, and resend in one click. No need to ask anyone to re-submit the form.
* **Conditional logic on every integration.** Route submissions to different destinations based on their content, so you never push junk into your CRM.

## Recently Shipped (2.1.x)

Active, ongoing development. Recent releases added Cal.com and Knack support, hardened Google Sheets and WooCommerce reliability with dedupe and retry logic, and delivered a critical security patch (all users should run 2.1.1 or later). The 2.0 release rebuilt the admin UI on Vue 3 with a site-wide security and performance pass: faster log pages, single-source credential storage, and hardened AJAX endpoints throughout. See the Changelog for the full list.

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

**Form builders:** Contact Form 7, WPForms, Gravity Forms, Elementor Pro Form, Fluent Forms, Ninja Forms, Formidable Forms, Forminator, WS Form, ARForms, Avada Forms, Beaver Builder Form, Bit Form, Breakdance Builder Form, Bricks Builder Form, Caldera Forms, ConvertPro Forms, Cool FormKit, Crowdsignal Forms, Divi Forms, eForm, Everest Forms, FormCraft, Happyforms, JetFormBuilder, Kadence Blocks Form, Live Forms, Metform, QuForm2, Smart Forms, User Registration, weForms

**E-commerce:** WooCommerce, Easy Digital Downloads, SureCart, FluentCart, CartFlows, WP Simple Pay, WP Pizza, Advanced Coupons

**Memberships:** MemberPress, Paid Membership Pro, ARMember, SureMembers, Ultimate Member, WP-Members, DigiMember

**LMS and courses:** LearnDash, LifterLMS, TutorLMS, LearnPress, Sensei LMS, MasterStudy LMS, Academy LMS, Thrive Apprentice

**Events and bookings:** The Events Calendar, Events Manager, Event Tickets, Eventin, Event Espresso, FooEvents, Amelia Booking, Bookly, LatePoint, Easy Appointments, WP Booking Calendar, Fluent Booking, Appointment Hour Booking

**Community and forums:** BuddyBoss, BuddyPress, bbPress, PeepSo, wpForo, Asgaros Forum, AnsPress

**Affiliates:** AffiliateWP, Easy Affiliate, SliceWP, Fluent Affiliate

**Donations:** GiveWP, Charitable

**Gamification and engagement:** GamiPress, myCred, WP ULike, WP Post Ratings, RafflePress

**Email capture and newsletters:** MailPoet Forms, Newsletter, Thrive Leads

**Quizzes and surveys:** Quiz and Survey Master, Thrive Quiz Builder

**Support and CRM:** Awesome Support, Jetpack CRM, Groundhogg

You can also capture UTM parameters from any trigger by enabling the feature in settings.

## Supported Action Platforms (Receivers)

AFI can send your data to any of these 200+ destinations.

**CRM and sales:** Salesforce, HubSpot, Zoho CRM, Zoho Bigin, Pipedrive, Copper CRM, Insightly, Close CRM, Capsule CRM, Agile CRM, Apptivo, Attio, CiviCRM, ClinchPad CRM, CompanyHub, Dynamics 365 CRM, Dynamics 365 Sales, Dynamics 365 Marketing, Flowlu, FollowUpBoss, Freshworks CRM (Freshsales), HighLevel, Less Annoying CRM, LionDesk, Nimble, Nutshell CRM, Onehash.ai, Salesflare, Salesmate, SuiteDash, Vtiger CRM, Wealthbox CRM, Zendesk Sell, Apollo.io, Fluent CRM

**Email marketing and automation:** Mailchimp, ActiveCampaign, Brevo, Constant Contact, AWeber, GetResponse, Klaviyo, Kit, MailerLite, MailerLite Classic, Omnisend, Drip, Encharge, EngageBay, Mautic, Ortto, Customer.io, Braze, Attentive, Autopilot, Benchmark Email, BombBomb, Acelle Mail, Acumbamail, Audienceful, beehiiv, Cakemail, Campaigner, Campaign Monitor, Campayn, CleverReach, Copernica, Curated, DirectIQ, Doppler, EasySendy, Elastic Email, Emailchef, Emailit, EmailOctopus, Enormail, Flodesk, iContact, Instantly, Keila, Laposta, lemlist, Loops, MailBluster, Mailcoach, Maileon, Mailercloud, Mailify, Mailjet, Mail Mint, Mailmodo, MailPoet, Mailrelay, Mailster, MailUp, MailWizz, Moosend, Newsletter, Pabbly Email Marketing, Rapidmail, Resend, Robly, Saleshandy, Sales.Rocks, Sarbacane, Selzy, Sender, SendFox, Sendlane, SendPulse, SendX, Sendy, Smartlead.ai, SmartrMail, Snov.io, System.io, Vertical Response, Woodpecker.co, Zoho Campaigns, Zoho Marketing Automation

**Spreadsheets and databases:** Google Sheets, Airtable, Smartsheet, Zoho Sheet, Knack, Quickbase, Ragic, Microsoft Dataverse

**Project management and productivity:** Asana, ClickUp, Trello, Monday.com, Microsoft To Do, Fluent Boards

**Calendars and scheduling:** Google Calendar, Cal.com, Calendly, Acuity Scheduling, AddCal, Appointment Hour Booking, Fluent Booking

**Helpdesk and customer service:** Freshdesk, Zendesk, Zoho Desk, Fluent Support, Intercom, Dynamics 365 Customer Service, Dynamics 365 Field Service

**Team communication and SMS:** Slack, Microsoft Teams, Twilio, Pushover

**Webinars and events:** Zoom Webinar, WebinarJam, EverWebinar, Demio, Livestorm, Airmeet, BigMarker

**File storage:** Google Drive, Dropbox

**Finance and operations:** Zoho Books, Zoho People

**WordPress plugins:** WordPress (create post), Gravity Forms, WPForms, bbPress, BuddyBoss, Charitable, GiveWP, AffiliateWP, Fluent Affiliate, Fluent Community, GamiPress, Events Manager, Academy LMS

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
The free version supports unlimited integrations and all 200+ platforms with core fields. Custom fields, tags, inbound webhooks, and some platform-specific advanced actions require a Pro license.

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
2. 200+ destinations, searchable from a single picker.
3. Activity log with one-click Resend on failed submissions.
4. Conditional logic: send only when your rules match.
5. Conditional logic: all condition types.
6. Manage every integration from one dashboard, with status at a glance.

== Changelog ==

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

* [New] **Cal.com** - Added event-type support with booking payload builder, per-event fields, and Cal.com Pro actions.
* [New] **Calendly** - Re-enabled with Pro actions support for advanced workflows.
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

= 2.2.0 =
Adds Discord and Dotdigital integrations. No breaking changes.

= 2.1.1 =
Critical security update. All users should update immediately. Adds server-side role validation to block privilege escalation via form submissions.