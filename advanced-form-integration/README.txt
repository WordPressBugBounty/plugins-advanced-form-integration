=== Advanced Form Integration — Connect Forms to 200+ Apps ===
Contributors: afisupport, nasirahmed, freemius
Tags: form integration, crm, webhooks, automation, contact form 7
Requires at least: 3.0.1
Tested up to: 6.9
Stable tag: 2.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect any WordPress form or event to 200+ apps, no code. Send leads, orders, and signups to your CRM, email, or sheets in minutes.

== DESCRIPTION ==

## Overview

AFI connects your WordPress forms and site events to 200+ external platforms — CRMs, email marketing, sheets, helpdesks, webhooks — with zero code. Set it up once and form submissions, WooCommerce orders, bookings, and more flow into the tools you already use, in real time.

Perfect for small and medium businesses, AFI requires no coding knowledge and can be configured in minutes — an essential tool for marketers, sales teams, and business owners who want to streamline workflows and improve data accuracy.

## What's New in 2.0

A major modernization release. Every existing integration keeps working unchanged, but under the hood:

* Rebuilt admin UI on **Vue 3**, replacing the end-of-life Vue 2.7.
* Substantial security hardening across admin AJAX endpoints, credential handling, and SQL.
* Database-indexing pass that makes the log page measurably faster on busy sites.
* Single-source credential storage, with a one-time migration that merges any legacy per-platform records (no accounts lost).

See the Changelog at the bottom of this page for the full list.

## Key Benefits

* **200+ destinations out of the box** — CRMs, email tools, sheets, webhooks, helpdesks. No app-store grind.

* **Works with 70+ form plugins** — Contact Form 7, Elementor Pro, Gravity Forms, WPForms, Fluent Forms, Ninja Forms, and many more.

* **Conditional logic on every integration** — send to different destinations based on submission content.

* **Activity log with one-click resend** — every API call is recorded; if something fails, fix the data and re-send without re-submitting the form.

* **Multi-account support** — connect multiple Mailchimp / Salesforce / Zoho accounts from the same site.

## Core Features

### Universal Form Support

Connect virtually any WordPress form plugin — Contact Form 7, WPForms, Elementor Pro Forms, Gravity Forms, Ninja Forms, Fluent Forms, and 70+ other popular form builders. Triggers also extend beyond forms: WooCommerce orders, LMS enrolments, memberships, bookings, forum posts, and more.

### Smart Data Mapping

Map form fields to destination platform fields with support for static text combination and dynamic field population.

### Conditional Logic

Control when data is sent using conditional rules. Send data only when specific criteria are met.

### System Integration Tags

Access dynamic system information including timestamps, IP addresses, user details, and site information using special tags like `{{_date}}`, `{{_user_ip}}`, and `{{_site_title}}`.

### Activity Log & Resend

Every outbound API call is recorded. When something fails, view the exact request and response, edit the data inline if needed, and resend with one click — no need to ask the user to re-submit the form.

### Webhook Support

Send to any HTTP endpoint with custom headers, payload, and method — outbound webhooks work without a dedicated platform integration. Pro adds inbound webhooks, so external systems can fire integrations into your WordPress site.

### Multisite Support

Full compatibility with WordPress multisite installations for enterprise deployments.

[**[Website](https://advancedformintegration.com/)**]   [**[Documentation](https://advancedformintegration.com/docs/afi/)**]   [**[Tutorial Videos](https://www.youtube.com/channel/UCyl43pLFvAi6JOMV-eMJUbA)**]

### SENDER PLATFORMS (TRIGGER) ###

The following plugins work as a sender platform.

* **Academy LMS**

* **Advanced Coupons**

* **AffiliateWP**

* **Amelia Booking**

* **AnsPress**

* **Appointment Hour Booking**

* **ARForms**

* **ARMember**

* **Asgaros Forum**

* **Avada Forms**

* **Awesome Support**

* **bbPress**

* **Beaver Builder Form**

* **Bit Form**

* **Bookly**

* **Breakdance Builder Form**

* **Bricks Builder Form**

* **BuddyBoss**

* **BuddyPress**

* **Caldera Forms**

* **CartFlows**

* **Charitable**

* **Contact Form 7**

* **ConvertPro Forms**

* **Cool FormKit**

* **Crowdsignal Forms**

* **DigiMember**

* **Divi Forms**

* **Easy Affiliate**

* **Easy Appointments**

* **Easy Digital Downloads**

* **eForm**

* **Elementor Pro Form**

* **Event Espresso Decaf**

* **Eventin**

* **Events Manager**

* **Event Tickets**

* **Everest Forms**

* **Fluent Affiliate**

* **Fluent Booking**

* **FluentCart**

* **Fluent Forms**

*  **FooEvents**

* **FormCraft**

* **Formidable Forms**

* **Forminator (Forms only)**

* **GamiPress**

* **GiveWP**

* **Gravity Forms**

* **Groundhogg**

* **Happyforms**

* **JetFormBuilder**

* **Jetpack CRM**

* **Kadence Blocks Form**

*  **LatePoint**

* **LearnDash**

* **LearnPress**

* **LifterLMS**

* **Live Forms**

* **MailPoet Forms**

* **MasterStudy LMS**

* **MemberPress**

* **Metform**

*  **myCred**

* **Newsletter**

* **Ninja Forms**

* **Paid Membership Pro**

*  **PeepSo**

* **QuForm2**

* **Quiz and Survey Master**

* **RafflePress**

* **Sensei LMS**

* **SliceWP**

* **Smart Forms**

* **SureCart**

* **SureMembers**

* **The Events Calendar**

*  **Thrive Apprentice**

*  **Thrive Leads**

*  **Thrive Quiz Builder**

* **TutorLMS**

*  **Ultimate Member**

* **User Registration**

* **weForms**

*  **WP Booking Calendar**

* **WPForms**

*  **wpForo**

*  **WP-Members**

*  **WP Pizza**

*  **WP Post Ratings**

*  **WP Simple Pay**

*  **WP ULike**

*  **WooCommerce**

*  **WS Form**

*  **UTM Parameters** You can also grab and send UTM variables. Just activate the feature from the plugin's settings page. Now use tags like {{utm_source}}, {{utm_medium}}, {{utm_term}}, {{utm_content}}, {{utm_campaign}}, {{gclid}}, etc.

<blockquote>
<p><strong>Premium Version Features.</strong></p>
<ul>
<li>All form fields</li>
<li>Inbound Webhooks</li>
</ul>
</blockquote>

### RECEIVER PLATFORMS (ACTION) ###

*  **Academy LMS**

*  **Acelle Mail** - Creates contacts and adds them to lists. Pro license required for custom fields and tags.

*  **ActiveCampaign** - Create contacts, add them to lists or automations, and manage deals and notes. Pro license required for custom fields.

*  **Acuity Scheduling**

*  **Acumbamail**

*  **AddCal** - Create new bookings.

*  **AffiliateWP**

*  **Agile CRM** - Create contacts, deals, and notes. Pro license required for tags and custom fields.

*  **Airmeet**

*  **Airtable** - Creates new row to selected table.

*  **Appointment Hour Booking**

*  **Apollo.io**

*  **Apptivo**

*  **Asana** - Allows to create a new task. Custom fields are support in the AFI Pro version.

*  **Attentive**

*  **Attio CRM**

*  **Audienceful**

*  **Autopilot** - Create/update contacts and add them to lists. Pro license required for custom fields.

*  **AWeber** - Create contacts and subscribe them to lists. Pro license required for custom fields and tags.

*  **bbPress** - Create new topic in selected forum.

*  **BigMarker**

*  **beehiiv** - Create new subscriber to a selected publiction.

*  **Benchmark Email** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **BombBomb**

*  **Braze** - Track user attributes and events.

*  **Brevo** - Create subscribers and add them to lists. Pro license required for custom fields and multilingual support.

*  **BuddyBoss** - Create activity, send invite, register user.

*  **Cakemail - Courrielleur**

*  **Cal.com** - Create bookings with event type support. Pro license required for custom fields.

*  **Calendly** - Create invitees for events. Pro license required for custom fields and tags.

*  **Campaigner** - Subscribe to list.

*  **Campaign Monitor** - Create contacts and subscribe to lists. Pro license required for custom fields.

*  **Campayn**

*  **Capsule CRM** - Add parties, opportunities, cases, and tasks. Pro version required for tags and custom fields.

*  **Charitable** - Create new donor and donation.

*  **CiviCRM** - Add contacts.

*  **CleverReach** - Subscribe to list.

*  **ClickUp** - Create tasks. Requires a Pro license to add tags and custom fields.

*  **ClinchPad CRM** - Creates new Leads, including organization, contact, note, product, etc.

*  **Close CRM** - Adds a new lead and contat. The Pro version supports custom fields.

*  **CompanyHub** - Creates basic contact.

*  **Constant Contact** - Create new contacts and subscribe them to lists. Pro license required for custom fields and tags.

*  **Copernica**

*  **Copper CRM** - Create companies, persons, and deals. Pro version required for custom fields and tags.

*  **Curated** - Add subscriber.

*  **Customer.io** - Add people.

*  **Dataverse (Generic)** - Create a record in any Microsoft Dataverse table.

*  **Demio** - Register people to webinar.

*  **DirectIQ** - Create contacts and add them to mailing lists.

*  **Doppler**

*  **Drip** - Subscribe new contacts to campaigns and workflows. Pro version required for custom fields.

*  **Dropbox** - Upload file.

*  **Dynamics 365 CRM** - Create or update contacts and leads, and create accounts.

*  **Dynamics 365 Customer Service** - Create cases and add notes to existing cases.

*  **Dynamics 365 Field Service** - Create work orders and service requests.

*  **Dynamics 365 Marketing** - Create or update marketing contacts.

*  **Dynamics 365 Sales** - Create opportunities, quotes, and sales orders.

*  **EasySendy** - Subscribe new contacts. Pro license required for custom fields.

*  **Elastic Email** - Subscribe new contacts. Pro license required for custom fields.

*  **Emailchef**

*  **Emailit**

*  **EmailOctopus** - Subscribe new contacts. Pro license required for custom fields.

*  **Encharge** - Create or update people, add tags. Pro license required for custom fields.

*  **EngageBay** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **Enormail**

*  **Events Manager** - Register attendee to event.

*  **EverWebinar** - Add registrant to webinar.

*  **Flodesk** - Add subscriber.

*  **Flowlu** - Create contacts, opportunities, and tasks.

*  **Fluent Affiliate** - Create new affiliate.

*  **Fluent Boards** - Create boards, stages, and tasks.

*  **Fluent Booking** - Create new booking.

*  **Fluent Community** - Create posts and replies.

*  **Fluent CRM**

*  **Fluent Support** - Create ticket.

*  **FollowUpBoss** - Add contacts.

*  **Freshdesk** - Create contact, ticket.

*  **Freshworks CRM (Freshsales)** - Create accounts, contacts, and deals with custom fields.

*  **GamiPress** - Award achievements, points, and ranks.

*  **GetResponse** - Create subscribers and add them to mailing lists. Pro version required for custom fields and tags.

*  **GiveWP** - Create new donor and donation.

*  **Google Calendar** - Create new events on a selected Google Calendar using provided data.

*  **Google Drive** - Upload file.

*  **Google Sheets** - Create a new row in a selected sheet with submitted form or WooCommerce order data. Pro version supports separate rows for each WooCommerce order item.

*  **Gravity Forms** - Create new entry, update an existing entry, or add a note to a Gravity Forms entry.

*  **HighLevel** - Create contacts and opportunities.

*  **Hubspot CRM** - Create new contacts in HubSpot CRM with custom fields. Pro version supports companies, deals, tickets, tasks, and more.

*  **iContact**

*  **Insightly** - Create organizations, contacts, and opportunities with basic fields. Pro version supports custom fields and tags.

*  **Instantly** - Add lead.

*  **Intercom** - Add contacts.

*  **Keila**

*  **Kit** - Create contacts and subscribe them to sequences or forms. Pro license required for custom fields and tags.

*  **Klaviyo** - Add contacts and subscribe them to lists. Pro license required for custom properties.

*  **Knack** - Create records with field type formatting and caching support.

*  **Laposta**

*  **lemlist** - Create contacts and add them to campaigns.

*  **Less Annoying CRM**

*  **LionDesk** - Create contacts. Pro version supports tags and custom fields.

*  **Livestorm** - Add people to event session.

*  **Loops** - Subscribe to list.

*  **MailBluster** - Create new leads. Pro license required for custom fields and tags.

*  **Mailchimp** - Create contacts, manage subscriptions to lists and groups, and unsubscribe from lists. Pro license required for custom/merge fields and tags.

*  **Mailcoach**

*  **Maileon** - Adds new subscriber.

*  **Mailercloud** - Add new subscribers to selected lists. Pro license required for custom fields.

*  **MailerLite** - Add contacts and subscribe them to groups. Pro license required for custom fields.

*  **MailerLite Classic** - Add contacts and subscribe them to groups. Pro license needed for custom fields.

*  **Mailify** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **Mailjet** - Create contacts and add them to lists. Pro license required for custom fields.

*  **Mail Mint** - Subscribe to list.

*  **Mailmodo**

*  **MailPoet** - Add contact to list.

*  **Mailrelay** - Subscribe to group.

*  **Mailster** - Subscribe to list.

*  **MailUp** - Subscribe to list.

*  **MailWizz** - Create contacts and add them to lists. Pro plugin supports custom fields.

*  **Mautic** - Create contacts. Pro license required for custom fields.

*  **Microsoft Teams** - Post a message to a channel.

*  **Microsoft To Do** - Create tasks.

*  **Moosend** - Create contacts and add them to lists. Pro license required for custom fields.

*  **Monday.com** - Create item to board.

*  **Newsletter** - Subscribe to list.

*  **Nimble**

*  **Nutshell CRM** - Add account, contact.

*  **Omnisend** - Create contacts. Pro license required for custom fields and tags.

*  **Onehash.ai** - Create new leads, contacts, and customers with this plugin.

*  **Ortto** - Create contacts. Pro license required for tags and custom fields.

*  **Pabbly Email Marketing** - Create subscribers and add them to lists. Pro license required for custom fields.

*  **Pipedrive** - Create organizations, people, deals, notes, and activities with custom fields. Pro license required to add new leads.

*  **Pushover** - Send push messages to Android, iOS, and Desktop devices.

*  **Quickbase**

*  **Ragic**

*  **Rapidmail** - Subscribe to list.

*  **Resend** - Add contact.

*  **Robly** - Add or update subscribers. Pro license required for custom fields and tags.

*  **Salesforce** - Add lead, account, contact, opportunity, case.

*  **Saleshandy**

*  **Sales.Rocks** - Add contacts and subscribe them to lists.

*  **Salesflare** - Create organizations, contacts, opportunities, and tasks.

*  **Salesmate** - Create new contacts, companies, and deals.

*  **Sarbacane**

*  **Selzy** - Subscribe to lists. Pro version supports custom fields and tags.

*  **Sender** - Subscribe to group.

*  **SendFox** Subscribe to lists. Pro version supports custom fields.

*  **Sendlane** - Add subscribers to lists.

*  **SendPulse** - Subscribe to lists.

*  **SendX** - Create new contact.

*  **Sendy** - Subscribe them to lists. Pro license required for custom fields.

*  **Slack** - Send channel messages.

*  **Smartlead.ai**

*  **SmartrMail**

*  **Smartsheet** - Create new rows.

*  **Snov.io** - Subscribe to list. Pro license required for custom fields.

*  **SuiteDash**

*  **System.io** - Subscribe to list.

*  **Trello** - create cards in Trello.

*  **Twilio** - Send customized SMS.

*  **Vertical Response** - Create contacts in specific lists. Pro license required for custom fields.

*  **Vtiger CRM** - Create leads, contacts, organizations, and opportunities.

*  **Wealthbox CRM** - Create contacts. Pro license required for tags and custom fields.

*  **Webhook** - Send data to any webhook URL. Pro version supports custom headers, bodies, and methods (GET, POST, PUT, DELETE) for API integration with token or Basic auth.

*  **WebinarJam** - Add registrant to webinar.

*  **Woodpecker.co** - Create subscribers. Pro license required for custom fields.

*  **WordPress** - Create new post.

*  **WPForms** - Create new entry, update an existing entry, or add a note to a WPForms entry.

*  **Zapier** - Sends data to Zapier webhook.

*  **Zendesk**

*  **Zendesk Sell**

*  **Zoho Bigin** - Create contacts, companies, pipelines, tasks, notes, and more. Pro license required for custom fields.

*  **Zoho Books**

*  **Zoho Campaigns** - Create subscribers and add them to lists. Pro license required for custom fields.

*  **Zoho CRM** - Create leads, contacts, accounts, deals, tasks, meetings, calls, products, campaigns, vendors, cases, and solutions. Pro license required for custom fields.

*  **Zoho Desk** 

*  **Zoho Marketing Automation**

*  **Zoho People**

*  **Zoho Sheet** - Add rows.

*  **Zoom Webinar** - Add registrant to webinar.


== Installation ==
###Automatic Install From WordPress Dashboard

1. log in to your admin panel
2. Navigate to Plugins -> Add New
3. Search **Advanced Form Integration**
4. Click install and then active.

###Manual Install

1. Download the plugin by clicking on the **Download** button above. A ZIP file will be downloaded.
2. Login to your site’s admin panel and navigate to Plugins -> Add New -> Upload.
3. Click choose file, select the plugin file and click install

== Frequently Asked Questions ==

= Connection error, how can I re-authorize Google Sheets? =

If authorization is broken/not working for some reason, try re-authorizing. Please go to https://myaccount.google.com/permissions, remove app permission then authorize again from plugin settings.

= Getting "The requested URL was not found on this server" error while authorizing Google Sheets =

Please check the permalink settings in WordPress. Go to Settings > Permalinks > select Post name then Save.

= Do I need to map all fields while creating integration? =

No, but required fields must be mapped.

= Can I add additional text while field mapping?

Sure, you can. It is possible to mix static text and form field placeholder tags. Placeholder tags will be replaced with original data after form submission.

= How can I get support? =

For any query, feel free to send an email to support@advancedformintegration.com.

== Screenshots ==

1. The integration editor — Contact Form 7 to Mailchimp with field mapping
2. 200+ destinations, searchable from a single picker
3. Activity log with one-click Resend on failed submissions
4. Conditional logic — send only when your rules match
5. Conditional logic — all types
6. Manage every integration from one dashboard, with status at a glance

== Changelog ==

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

= 2.0.0 [2026-05-23]
Major modernization release. Every existing integration keeps working unchanged — there are no breaking changes — but under the hood almost every subsystem has been reviewed, hardened, and tuned.

* [Major] Rebuilt the admin UI on **Vue 3** (replacing Vue 2.7, which reached end-of-life in 2023). Snappier integration editor, smaller admin JS payload, future-proof.
* [Security] Hardened SQL across 14 trigger field-discovery handlers and the form / field / task admin AJAX endpoints — parameterised queries plus explicit `manage_options` checks throughout.
* [Security] Added explicit capability + nonce checks to 125 platform credential-list AJAX endpoints across 83 platform files.
* [Security] Tightened the resend-log handler, the review-dismiss handler, and the integration save path.
* [Security] Same-origin guard on the post-save redirect helper, plus a fix for an object-injection vector in the Gravity Forms list-field handler.
* [Improved] **Database indexing** on `adfoin_integration` and `adfoin_log` — PRIMARY KEY plus covering indexes on the columns the plugin actually filters by. Idempotent migration runs once on the first wp-admin pageload after upgrade. Busy sites' log page goes from seconds to milliseconds.
* [Improved] Bulk activate / deactivate / delete on the integrations list now executes a single SQL statement instead of one round-trip per row.
* [Improved] Log page status counts collapse four `COUNT(*)` scans into one query; the integration filter dropdown uses an index-only `DISTINCT` instead of a full-table `LEFT JOIN`.
* [Improved] Log table integration titles are bulk-preloaded once per page instead of N per-row queries.
* [Improved] Credentials option is no longer autoloaded — front-end pageviews skip loading the credential blob into memory.
* [Improved] WooCommerce trigger resolves item-meta keys once per submission instead of once per line item.
* [Improved] OAuth token-exchange now uses a 30-second HTTP timeout matching the refresh-token call (previously could hang on a slow provider).
* [Improved] OAuth `redirect_uri` no longer double-encoded.
* [Improved] **Single-source credentials** — `ADFOIN_OAuth_Manager` now routes through the canonical credential store, with a one-time additive migration that merges any legacy per-platform records (no accounts lost).
* [Improved] Bundled Action Scheduler updated to 3.9.3.
* [Improved] Numerous WPCS / Plugin Check compliance cleanups (`wp_unslash`, `wp_json_encode`, escaping, deprecated `current_time('timestamp')`, `@set_time_limit`).
* [Fixed] Log page CodeMirror no longer throws "unrecognized expression: ##" on newer jQuery versions.
* [Fixed] Editing a deleted integration id (`?action=edit&id=999999`) renders a clear "not found" notice instead of a PHP warning.
* [Fixed] The single-row Duplicate handler no longer shows a misleading "duplicated" success notice when the underlying INSERT fails.