=== AFI - The Easiest Integration Plugin  ===
Contributors: nasirahmed, freemius
Tags: Contact Form 7, WooCommerce, Google Sheets, Pipedrive, Zoho CRM
Requires at least: 3.0.1
Tested up to: 6.8.2
Stable tag: 1.120.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Effortlessly sync your WordPress plugin data with your favorite platforms.

== DESCRIPTION ==

AFI is a simple tool that links your website forms to various other platforms. It can connect with email marketing, CRM, spreadsheets, task management, and different software. AFI ensures the information goes to these other programs when someone fills out a form. AFI isn't just for forms; it can also connect with other plugins like WooCommerce, LearnDash, GiveWP, etc.

* **Easy to use**: The plugin was created with not-tech people in mind. Setting up new integrations is a breeze and can be accomplished within minutes. No coding skill is required, almost no learning curve.

* **Flexible**: Integrations between sender and receiver platforms can be created. You can create as many connections as you want—single sender to multiple receivers, multiple senders to a single receiver, various senders to multiple receivers. Remember that all PHP server has a maximum execution time allowed.

* **Conditional Logic**: You can create single or multiple conditional logic to filter the data flow. Submitted data will only be sent if the conditions match. For example, when you want to send contact data only if the user has agreed and filed the checkbox "I agree" (Contact Form 7 acceptance field) or if the city is only New York or the subject contacts the word "Lead," etc. You can set up the conditions as you like.

* **Special Tags**: We have introduced several special tags that can be passed to receiver platforms. These are helpful when you want more system information, like IP address, user agent, etc. Example: `{{_date}},` `{{_time}}`, `{{_weekday}}`, `{{_user_ip}},` `{{_user_agent}},` `{{_site_title}},` `{{_site_description}},` `{{_site_url}},` `{{_site_admin_email}},` `{{_post_id}},` `{{_post_name}},` `{{_post_title}},` `{{_post_url}},` `{{_user_id}},` `{{_user_first_name}},` `{{_user_last_name}},` `{{_user_last_name}},` `{{_user_email}}.`

* **Job Queue**: Leverage the proven reliability of [Action Scheduler](https://actionscheduler.org) for seamless background processing of extensive task queues within WordPress. Activate this functionality in AFI settings to improve the submission process and ensure a smooth user experience.

* **Multisite**: Multisite supported.

* **Log**: A powerful log feature with an edit and resend function. If something goes wrong on a submission, the admin can go to the log, edit/correct the data, and resend it.

[youtube https://youtu.be/iU0YmEks84Q]

[**[Website](https://advancedformintegration.com/)**]   [**[Documentation](https://advancedformintegration.com/docs/afi/)**]   [**[Tutorial Videos](https://www.youtube.com/channel/UCyl43pLFvAi6JOMV-eMJUbA)**]

### SENDER PLATFORMS (TRIGGER) ###

The following plugins work as a sender platform.

*  **Academy LMS**

*  **AffiliateWP**

* **Amelia Booking**

* **AnsPress**

* **ARForms**

* **ARMember**

* **Asgaros Forum**

* **Avada Forms**

* **Awesome Support**

* **bbPress**

* **Beaver Builder Form**

* **Bit Form**

* **Bricks Builder Form**

* **BuddyBoss**

* **Caldera Forms**

* **Charitable**

* **Contact Form 7**

* **ConvertPro Forms**

* **DigiMember**

* **Divi Forms**

* **Easy Affiliate**

* **Easy Digital Downloads**

* **eForm**

* **Elementor Pro Form**

* **Eventin**

* **Events Manager**

*  **Event Tickets**

* **Everest Forms**

* **Fluent Booking**

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

*  **[WS Form](https://advancedformintegration.com/docs/afi/sender-platforms/ws-form/)**

*  **[UTM Parameters](https://advancedformintegration.com/docs/afi/sender-platforms/utm-parameters/)**: You can also grab and send UTM variables. Just activate the feature from the plugin's settings page. Now use tags like {{utm_source}}, {{utm_medium}}, {{utm_term}}, {{utm_content}}, {{utm_campaign}}, {{gclid}}, etc.

<blockquote>
<p><strong>Premium Version Features.</strong></p>
<ul>
<li>All form fields</li>
<li>Inbound Webhooks</li>
</ul>
</blockquote>

### RECEIVER PLATFORMS (ACTION) ###

* **Academy LMS**

*  **Acelle Mail** - Creates contacts and adds them to lists. Pro license required for custom fields and tags.

*  **ActiveCampaign** - Create contacts, add them to lists or automations, and manage deals and notes. Pro license required for custom fields.

* **Acumbamail**

*  **[Agile CRM](https://www.agilecrm.com/)** - Create contacts, deals, and notes. Pro license required for tags and custom fields.

*  **Airtable** - Creates new row to selected table.

*  **Apollo.io**

*  **Apptivo**

*  **Asana** - Allows to create a new task. Custom fields are support in the AFI Pro version.

*  **Attio CRM**

* **Audienceful**

*  **[Autopilot](https://journeys.autopilotapp.com/)** - Create/update contacts and add them to lists. Pro license required for custom fields.

*  **AWeber** - Create contacts and subscribe them to lists. Pro license required for custom fields and tags.

*  **beehiiv** - Create new subscriber to a selected publiction.

*  **Benchmark Email** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **BombBomb**

*  **Brevo** - Create subscribers and add them to lists. Pro license required for custom fields and multilingual support.

*  **Cakemail - Courrielleur**

*  **Campaigner** - Subscribe to list.

*  **Campaign Monitor** - Create contacts and subscribe to lists. Pro license required for custom fields.

*  **Campayn**

*  **Capsule CRM** - Add parties, opportunities, cases, and tasks. Pro version required for tags and custom fields.

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

*  **Demio** - Register people to webinar.

*  **DirectIQ** - Create contacts and add them to mailing lists.

*  **Doppler**

*  **Drip** - Subscribe new contacts to campaigns and workflows. Pro version required for custom fields.

*  **Dropbox** - Upload file.

*  **EasySendy** - Subscribe new contacts. Pro license required for custom fields.

*  **Elastic Email** - Subscribe new contacts. Pro license required for custom fields.

* **Emailchef**

* **Emailit**

*  **EmailOctopus** - Subscribe new contacts. Pro license required for custom fields.

*  **EngageBay** - Create contacts and subscribe them to lists. Pro license required for custom fields.

* **Enormail**

*  **EverWebinar** - Add registrant to webinar.

*  **[Flodesk](https://flodesk.com/)** - Add subscriber.

*  **[Fluent Support](https://wordpress.org/plugins/fluent-support/)** - Create ticket.

*  **FollowUpBoss** - Add contacts.

*  **[Freshdesk](https://www.freshworks.com/freshdesk/)** - Create contact, ticket.

*  **[Freshworks CRM (Freshsales)](https://www.freshworks.com/crm/sales/)** - Create accounts, contacts, and deals with custom fields.

*  **[GetResponse](https://www.getresponse.com/)** - Create subscribers and add them to mailing lists. Pro version required for custom fields and tags.

*  **[Google Calendar](https://calendar.google.com)** - Create new events on a selected Google Calendar using provided data.

*  **[Google Drive](https://www.drive.google.com/)** - Upload file.

*  **[Google Sheets](https://seheets.google.com)** - Create a new row in a selected sheet with submitted form or WooCommerce order data. Pro version supports separate rows for each WooCommerce order item.

*  **[HighLevel](https://www.gohighlevel.com/)** - Create leads, contacts, opportunities.

*  **[Hubspot CRM](https://www.hubspot.com/)** - Create new contacts in HubSpot CRM with custom fields. Pro version supports companies, deals, tickets, tasks, and more.

* **iContact**

*  **[Insightly](https://www.insightly.com/)** - Create organizations, contacts, and opportunities with basic fields. Pro version supports custom fields and tags.

*  **[Instantly](https://instantly.ai/)** - Add lead.

*  **Intercom** - Add contacts.

*  **[Jumplead](https://jumplead.com/)** - Add contacts.

*  **Keila**

*  **[Kit](https://kit.com/)** - Create contacts and subscribe them to sequences or forms. Pro license required for custom fields and tags.

*  **[Klaviyo](https://www.klaviyo.com/)** - Add contacts and subscribe them to lists. Pro license required for custom properties.

*  **Laposta**

*  **[lemlist](https://lemlist.com/)** - Create contacts and add them to campaigns.

* **Less Annoying CRM**

*  **[LionDesk](https://www.liondesk.com/)** - Create contacts. Pro version supports tags and custom fields.

*  **[Livestorm](https://livestorm.co/)** - Add people to event session.

*  **[Loops](https://loops.so/)** - Subscribe to list.

*  **[MailBluster](https://mailbluster.com/)** - Create new leads. Pro license required for custom fields and tags.

*  **[Mailchimp](https://mailchimp.com/)** - Create contacts, manage subscriptions to lists and groups, and unsubscribe from lists. Pro license required for custom/merge fields and tags.

*  **Mailcoach**

*  **[Maileon](https://maileon.com/)** - Adds new subscriber.

*  **[Mailercloud](https://www.mailercloud.com/)** - Add new subscribers to selected lists. Pro license required for custom fields.

*  **[MailerLite](https://www.mailerlite.com/)** - Add contacts and subscribe them to groups. Pro license required for custom fields.

*  **[MailerLite Classic](https://www.mailerlite.com/)** - Add contacts and subscribe them to groups. Pro license needed for custom fields.

*  **[Mailify](https://www.mailify.com/)** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **[Mailjet](https://www.mailjet.com/)** - Create contacts and add them to lists. Pro license required for custom fields.

*  **[Mail Mint](https://wordpress.org/plugins/mail-mint/)** - Subscribe to list.

*  **Mailmodo**

*  **[MailPoet](https://wordpress.org/plugins/mailpoet/)** - Add contact to list.

*  **[Mailrelay](https://mailrelay.com/)** - Subscribe to group.

*  **[Mailster](https://wordpress.org/plugins/mailster/)** - Subscribe to list.

*  **[MailUp](https://mailup.com/)** - Subscribe to list.

*  **[MailWizz](https://www.mailwizz.com/)** - Create contacts and add them to lists. Pro plugin supports custom fields.

*  **[Mautic](https://www.mautic.org/)** - Create contacts. Pro license required for custom fields.

*  **[Moosend](https://moosend.com/)** - Create contacts and add them to lists. Pro license required for custom fields.

*  **[Monday.com](https://moonday.com/)** - Create item to board.

*  **[Newsletter](https://wordpress.org/plugins/newsletter/)** - Subscribe to list.

*  **[Nutshell CRM](https://www.nutshell.com/)** - Add account, contact.

*  **[Omnisend](https://www.omnisend.com/)** - Create contacts. Pro license required for custom fields and tags.

*  **[Onehash.ai](https://www.onehash.ai/)** - Create new leads, contacts, and customers with this plugin.

*  **[Ortto](https://ortto.com/)** - Create contacts. Pro license required for tags and custom fields.

*  **[Pabbly Email Marketing](https://www.pabbly.com//)** - Create subscribers and add them to lists. Pro license required for custom fields.

*  **[Pipedrive](https://www.pipedrive.com/)** - Create organizations, people, deals, notes, and activities with custom fields. Pro license required to add new leads.

*  **[Pushover](https://pushover.net/)** - Send push messages to Android, iOS, and Desktop devices.

*  **Quickbase**

*  **Ragic**

*  **[Rapidmail](https://rapidmail.com/)** - Subscribe to list.

*  **[Resend](https://resend.com/)** - Add contact.

*  **[Robly](https://robly.com/)** - Add or update subscribers. Pro license required for custom fields and tags.

*  **[Salesforce](https://www.salesforce.com/)** - Add lead, account, contact, opportunity, case.

*  **Saleshandy**

*  **[Sales.Rocks](https://sales.rocks/)** - Add contacts and subscribe them to lists.

*  **[Salesflare](https://salesflare.com/)** - Create organizations, contacts, opportunities, and tasks.

*  **Sarbacane**

*  **[Selzy](https://selzy.com/)** - Subscribe to lists. Pro version supports custom fields and tags.

*  **[Sender](https://sender.net/)** - Subscribe to group.

*  **[SendFox](https://sendfox.com/)** Subscribe to lists. Pro version supports custom fields.

*  **[SendPulse](https://sendpulse.com/)** - Subscribe to lists.

*  **[SendX](https://www.sendx.io/)** - Create new contact.

*  **[Sendy](https://sendy.co/)** - Subscribe them to lists. Pro license required for custom fields.

*  **[Slack](https://slack.com/)** - Send channel messages.

* **Smartlead.ai**

*  **SmartrMail**

*  **[Smartsheet](https://smartsheet.com/)** - Create new rows.

*  **[Snov.io](https://snov.io/)** - Subscribe to list. Pro license required for custom fields.

*  **[System.io](https://systeme.io/)** - Subscribe to list.

*  **[Trello](https://www.trello.com/)** - create cards in Trello.

*  **[Twilio](https://www.twilio.com/)** - Send customized SMS.

*  **[Vertical Response](https://verticalresponse.com/)** - Create contacts in specific lists. Pro license required for custom fields.

*  **[Wealthbox CRM](https://www.wealthbox.com/)** - Create contacts. Pro license required for tags and custom fields.

*  **Webhook** - Send data to any webhook URL. Pro version supports custom headers, bodies, and methods (GET, POST, PUT, DELETE) for API integration with token or Basic auth.

*  **[WebinarJam](https://home.webinarjam.com/index)** - Add registrant to webinar.

*  **[Woodpecker.co](https://woodpecker.co/)** - Create subscribers. Pro license required for custom fields.

*  **WordPress** - Create new post.

*  **[Zapier](https://zapier.com/)** - Sends data to Zapier webhook.

* **Zendesk Sell**

*  **[Zoho Campaigns](https://www.zoho.com/campaigns/)** - Create subscribers and add them to lists. Pro license required for custom fields.

*  **[Zoho Bigin](https://bigin.com/)** - Create contacts, companies, pipelines, tasks, notes, and more. Pro license required for custom fields.

*  **[Zoho CRM](https://www.zoho.com/crm/)** - Create leads, contacts, accounts, deals, tasks, meetings, calls, products, campaigns, vendors, cases, and solutions. Pro license required for custom fields.

*  **[Zoho Desk](https://www.zoho.com/desk/)**

*  **[Zoho Sheet](https://www.zoho.com/sheet/)** - Add rows.


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

1. All integrations list
2. Settings page
3. New integration page
4. Conditional logic

== Changelog ==

= 1.120.0 [2025-09-09] =
* [Added] Intercom as action
* [Added] FollowUpBoss as action

= 1.119.0 [2025-09-01] =
* [Added] SuiteDash as action
* [Fixed] Minor bugs

= 1.118.0 [2025-08-18] =
* [Added] Emailit as action

= 1.117.0 [2025-08-13] =
* [Added] Customer.io as action

= 1.116.0 [2025-07-28] =
* [Added] Doppler as action

= 1.115.0 [2025-07-19] =
* [Added] Audienceful as action

= 1.114.0 [2025-05-31] =
* [Added] Apptivo as action
* [Added] Cakemail as action
* [Added] Campayn as action
* [Added] SmartrMail as action

= 1.113.0 [2025-05-22] =
* [Added] Emailchef as action
* [Added] Less Annoying CRM as action

= 1.112.0 [2025-05-06] =
* [Added] Apollo.io as action
* [Added] BombBomb as action
* [Added] Copernica as action
* [Added] Keila as action
* [Added] Mailcoach as action
* [Added] Mailmodo as action
* [Added] Sarbacane as action

= 1.111.0 [2025-04-28] =
* [Added] Acumbamail as action
* [Added] iContact as action
* [Added] Enormail as action
* [Added] Laposta as action
* [Added] Quickbase as action
* [Added] Saleshandy as action
* [Added] Smartlead.ai as action
* [Updated] Asana integration
* [Updated] Systeme.io integration

= 1.110.0 [2025-04-15] =
* [Added] Ragic as action
* [Added] Zendesk Sell as action

= 1.109.0 [2025-03-26] =
* [Added] WP-Members plugin as trigger
* [Added] WP Pizza plugin as trigger
* [Updated] Lemlist Integration
* [Updated] Monday.com Integration
* [Updated] Attio CRM Integration
* [Updated] Omnisend Integration

= 1.108.0 [2025-03-10] =
* [Added] User Registration plugin as trigger
* [Fixed] CapsuleCRM php notices

= 1.107.1 [2025-03-06] =
* [Fixed] Omnisend integration improved

= 1.107.1 [2025-03-03] =
* [Fixed] lemlist update issue

= 1.107.0 [2025-02-27] =
* [Added] Event Tickets as trigger
* [Added] FooEvents as trigger
* [Added] LatePoint as trigger
* [Added] myCred as trigger
* [Added] PeepSo as trigger

= 1.106.0 [2025-02-25] =
* [Added] eForm as trigger
* [Added] Events Manager as trigger

= 1.105.0 [2025-02-07] =
* [Added] Thrive Apprentice as trigger
* [Added] Thrive Leads as trigger
* [Added] Thrive Quiz Builder as trigger
* [Added] Ultimate Member as trigger
* [Added] WP Booking Calendar as trigger
* [Added] wpForo as trigger
* [Added] WP Post Ratings as trigger
* [Added] WP Simple Pay as trigger
* [Added] WP ULike as trigger

= 1.104.0 [2025-02-04] =
* [Added] MemberPress as trigger
* [Added] Newsletter as trigger
* [Added] Quiz and Survey Master as trigger
* [Added] RafflePress as trigger
* [Added] Sensei LMS as trigger
* [Added] SliceWP as trigger
* [Added] SureCart as trigger
* [Added] SurMembers as trigger
* [Added] The Events Calendar as trigger

= 1.103.0 [2025-02-01] =
* [Added] Fluent Booking as trigger
* [Added] Groundhogg as trigger
* [Added] Jetpack CRM as trigger
* [Added] LearnPress as trigger
* [Fixed] Monday.com boards loading issue

= 1.102.0 [2025-01-31] =
* [Added] bbPress as trigger
* [Added] Charitable as trigger
* [Added] DigiMember as trigger
* [Added] Easy Affiliate as trigger
* [Added] Eventin as trigger

= 1.101.0 [2025-01-30] =
* [Added] AnsPress as trigger
* [Added] Asgaros Forum as trigger
* [Added] Avada Forms as trigger
* [Added] Awesome Support as trigger

= 1.100.0 [2025-01-03] =
* [Added] CleverReach added as action
* [Added] Loops added as action
* [Added] Mailster added as action
* [Added] Newsletter added as action
* [Added] Nutshell CRM added as action
* [Added] Rapidmail added as action
* [Added] Resend added as action
* [Added] Salesforce added as action
* [Added] Sender added as action
* [Added] System.io added as action
* [Updated] Kit (formerly ConvertKit) V4 API
* [Fixed] Flodesk integration
* [Fixed] Sendpulse integration