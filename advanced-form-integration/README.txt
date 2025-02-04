=== AFI - The Easiest Integration Plugin  ===
Contributors: nasirahmed, freemius
Tags: Contact Form 7, WooCommerce, Google Sheets, Pipedrive, Zoho CRM
Requires at least: 3.0.1
Tested up to: 6.7.1
Stable tag: 1.104.0
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

*  **[Academy LMS](https://wordpress.org/plugins/academy/)**

*  **[AffiliateWP](https://affiliatewp.com/)**

* **[Amelia Booking](https://wordpress.org/plugins/ameliabooking/)**

* **AnsPress**

* **[ARForms](https://wordpress.org/plugins/arforms-form-builder/)**

* **ARMember**

* **Asgaros Forum**

* **Avada Forms**

* **Awesome Support**

* **bbPress**

* **[Beaver Builder Form](https://www.wpbeaverbuilder.com/)**

* **[Bit Form](https://wordpress.org/plugins/bit-form/)**

* **[Bricks Builder Form](https://bricksbuilder.io/forms/)**

* **[BuddyBoss](https://www.buddyboss.com/)**

* **[Caldera Forms](https://advancedformintegration.com/docs/afi/sender-platforms/caldera-forms/)**

* **Charitable**

* **[Contact Form 7](https://advancedformintegration.com/docs/afi/sender-platforms/contact-form-7/)**

* **[ConvertPro Forms](https://www.convertpro.net/)**

* **DigiMember**

* **[Divi Forms](https://www.elegantthemes.com/gallery/divi/)**

* **Easy Affiliate**

* **[Easy Digital Downloads](https://wordpress.org/plugins/easy-digital-downloads/)**

* **[Elementor Pro Form](https://advancedformintegration.com/docs/afi/sender-platforms/elementor-pro-form/)**

* **Eventin**

* **[Everest Forms](https://advancedformintegration.com/docs/afi/sender-platforms/everest-forms/)**

* **Fluent Booking**

* **[Fluent Forms](https://advancedformintegration.com/docs/afi/sender-platforms/wp-fluent-forms/)**

* **[FormCraft](https://advancedformintegration.com/docs/afi/sender-platforms/formcraft/)**

* **[Formidable Forms](https://advancedformintegration.com/docs/afi/sender-platforms/formidable-forms/)**

* **[Forminator (Forms only)](https://advancedformintegration.com/docs/afi/sender-platforms/forminator/)**

* **GamiPress**

* **[GiveWP](https://wordpress.org/plugins/give/)**

* **[Gravity Forms](https://advancedformintegration.com/docs/afi/sender-platforms/gravity-forms/)**

* **Groundhogg**

* **[Happyforms](https://advancedformintegration.com/docs/afi/sender-platforms/happy-forms/)**

* **[JetFormBuilder](https://wordpress.org/plugins/jetformbuilder/)**

* **Jetpack CRM**

* **[Kadence Blocks Form](https://www.kadencewp.com/kadence-blocks/)**

* **[LearnDash](https://www.learndash.com/)**

* **LearnPress**

* **[LifterLMS](https://wordpress.org/plugins/lifterlms/)**

* **[Live Forms](https://wordpress.org/plugins/liveforms/)**

* **[MailPoet Forms](https://wordpress.org/plugins/mailpoet/)**

* **[MasterStudy LMS](https://wordpress.org/plugins/masterstudy-lms-learning-management-system/)**

* **MemberPress**

* **[Metform](https://wordpress.org/plugins/metform/)**

* **Newsletter**

* **[Ninja Forms](https://advancedformintegration.com/docs/afi/sender-platforms/ninja-forms/)**

* **Paid Membership Pro**

* **Quiz and Survey Master**

* **RafflePress**

* **Sensei LMS**

* **SliceWP**

* **SureCart**

* **SureMembers**

* **The Events Calendar**

* **[TutorLMS](https://wordpress.org/plugins/tutor/)**

* **[QuForm2](https://advancedformintegration.com/docs/afi/sender-platforms/quform/)**

* **[Smart Forms](https://advancedformintegration.com/docs/afi/sender-platforms/smart-forms/)**

* **[weForms](https://advancedformintegration.com/docs/afi/sender-platforms/weforms/)**

* **[WPForms](https://advancedformintegration.com/docs/afi/sender-platforms/wpforms/)**

*  **[WooCommerce](https://advancedformintegration.com/docs/afi/sender-platforms/woocommerce/)**

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

*  **[Acelle Mail](https://acellemail.com/)** - Creates contacts and adds them to lists. Pro license required for custom fields and tags.

*  **[ActiveCampaign](https://advancedformintegration.com/docs/afi/receiver-platforms/activecampaign/)** - Create contacts, add them to lists or automations, and manage deals and notes. Pro license required for custom fields.

*  **[Agile CRM](https://www.agilecrm.com/)** - Create contacts, deals, and notes. Pro license required for tags and custom fields.

*  **[Airtable](https://airtable.com/)** - Creates new row to selected table.

*  **[Asana](https://www.asana.com/)** - Allows to create a new task. Custom fields are support in the AFI Pro version.

*  **[Attio CRM](https://www.attio.com/)**

*  **[Autopilot](https://journeys.autopilotapp.com/)** - Create/update contacts and add them to lists. Pro license required for custom fields.

*  **[AWeber](https://www.aweber.com/)** - Create contacts and subscribe them to lists. Pro license required for custom fields and tags.

*  **[beehiiv](https://www.beehiiv.com/)** - Create new subscriber to a selected publiction.

*  **[Benchmark Email](https://www.benchmarkemail.com/)** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **[Brevo (Sendinblue)](https://www.brevo.com/)** - Create subscribers and add them to lists. Pro license required for custom fields and multilingual support.

*  **[Campaign Monitor](https://www.campaignmonitor.com/)** - Create contacts and subscribe to lists. Pro license required for custom fields.

*  **[Campaigner](https://www.campaigner.com/)** - Subscribe to list.

*  **[Capsule CRM](https://capsulecrm.com/)** - Add parties, opportunities, cases, and tasks. Pro version required for tags and custom fields.

*  **[CiviCRM](https://civicrm.org/) - Add contacts.

*  **[ClinchPad CRM](https://clinchpad.com/)** - Creates new Leads, including organization, contact, note, product, etc.

*  **[Close CRM](https://close.com/)** - Adds a new lead and contat. The Pro version supports custom fields.

*  **[CompanyHub](https://www.companyhub.com/)** - Creates basic contact.

*  **[Constant Contact](https://www.constantcontact.com/)** - Create new contacts and subscribe them to lists. Pro license required for custom fields and tags.

*  **[ConvertKit](https://convertkit.com/)** - Create contacts and subscribe them to sequences or forms. Pro license required for custom fields and tags.

*  **[Copper CRM](https://www.copper.com/)** - Create companies, persons, and deals. Pro version required for custom fields and tags.

*  **[CleverReach](https://cleverreach.com/)** - Subscribe to list.

*  **[ClickUp](https://clickup.com/)** - Create tasks. Requires a Pro license to add tags and custom fields.

*  **[Curated](https://curated.co/)** - Add subscriber.

*  **[Demio](https://www.demio.com/)** - Register people to webinar.

*  **[DirectIQ](https://www.directiq.com/)** - Create contacts and add them to mailing lists.

*  **[Drip](https://www.drip.com/)** - Subscribe new contacts to campaigns and workflows. Pro version required for custom fields.

*  **[Dropbox](https://www.dropbox.com/)** - Upload file.

*  **[EasySendy](https://www.easysendy.com/)** - Subscribe new contacts. Pro license required for custom fields.

*  **[Elastic Email](https://elasticemail.com/)** - Subscribe new contacts. Pro license required for custom fields.

*  **[EmailOctopus](https://emailoctopus.com/)** - Subscribe new contacts. Pro license required for custom fields.

*  **[EngageBay](https://engagebay.com/)** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **[EverWebinar](https://home.everwebinar.com/index)** - Add registrant to webinar.

*  **[Flodesk](https://flodesk.com/)** - Add subscriber.

*  **[Fluent Support](https://wordpress.org/plugins/fluent-support/)** - Create ticket.

*  **[Freshdesk](https://www.freshworks.com/freshdesk/)** - Create contact, ticket.

*  **[Freshworks CRM (Freshsales)](https://www.freshworks.com/crm/sales/)** - Create accounts, contacts, and deals with custom fields.

*  **[GetResponse](https://www.getresponse.com/)** - Create subscribers and add them to mailing lists. Pro version required for custom fields and tags.

*  **[Google Calendar](https://calendar.google.com)** - Create new events on a selected Google Calendar using provided data.

*  **[Google Drive](https://www.drive.google.com/)** - Upload file.

*  **[Google Sheets](https://seheets.google.com)** - Create a new row in a selected sheet with submitted form or WooCommerce order data. Pro version supports separate rows for each WooCommerce order item.

*  **[HighLevel](https://www.gohighlevel.com/)** - Create leads, contacts, opportunities.

*  **[Hubspot CRM](https://www.hubspot.com/)** - Create new contacts in HubSpot CRM with custom fields. Pro version supports companies, deals, tickets, tasks, and more.

*  **[Insightly](https://www.insightly.com/)** - Create organizations, contacts, and opportunities with basic fields. Pro version supports custom fields and tags.

*  **[Instantly](https://instantly.ai/)** - Add lead.

*  **[Jumplead](https://jumplead.com/)** - Add contacts.

*  **[Klaviyo](https://www.klaviyo.com/)** - Add contacts and subscribe them to lists. Pro license required for custom properties.

*  **[lemlist](https://lemlist.com/)** - Create contacts and add them to campaigns.

*  **[LionDesk](https://www.liondesk.com/)** - Create contacts. Pro version supports tags and custom fields.

*  **[Livestorm](https://livestorm.co/)** - Add people to event session.

*  **[Loops](https://loops.so/)** - Subscribe to list.

*  **[MailBluster](https://mailbluster.com/)** - Create new leads. Pro license required for custom fields and tags.

*  **[Mailchimp](https://mailchimp.com/)** - Create contacts, manage subscriptions to lists and groups, and unsubscribe from lists. Pro license required for custom/merge fields and tags.

*  **[Maileon](https://maileon.com/)** - Adds new subscriber.

*  **[Mailercloud](https://www.mailercloud.com/)** - Add new subscribers to selected lists. Pro license required for custom fields.

*  **[MailerLite](https://www.mailerlite.com/)** - Add contacts and subscribe them to groups. Pro license required for custom fields.

*  **[MailerLite Classic](https://www.mailerlite.com/)** - Add contacts and subscribe them to groups. Pro license needed for custom fields.

*  **[Mailify](https://www.mailify.com/)** - Create contacts and subscribe them to lists. Pro license required for custom fields.

*  **[Mailjet](https://www.mailjet.com/)** - Create contacts and add them to lists. Pro license required for custom fields.

*  **[Mail Mint](https://wordpress.org/plugins/mail-mint/)** - Subscribe to list.

* **[MailPoet](https://wordpress.org/plugins/mailpoet/)** - Add contact to list.

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

*  **[Rapidmail](https://rapidmail.com/)** - Subscribe to list.

*  **[Resend](https://resend.com/)** - Add contact.

*  **[Robly](https://robly.com/)** - Add or update subscribers. Pro license required for custom fields and tags.

*  **[Salesforce](https://www.salesforce.com/)** - Add lead, account, contact, opportunity, case.

*  **[Sales.Rocks](https://sales.rocks/)** - Add contacts and subscribe them to lists.

*  **[Salesflare](https://salesflare.com/)** - Create organizations, contacts, opportunities, and tasks.

*  **[Selzy](https://selzy.com/)** - Subscribe to lists. Pro version supports custom fields and tags.

*  **[Sender](https://sender.net/)** - Subscribe to group.

*  **[SendFox](https://sendfox.com/)** Subscribe to lists. Pro version supports custom fields.

*  **[SendPulse](https://sendpulse.com/)** - Subscribe to lists.

*  **[SendX](https://www.sendx.io/)** - Create new contact.

*  **[Sendy](https://sendy.co/)** - Subscribe them to lists. Pro license required for custom fields.

*  **[Slack](https://slack.com/)** - Send channel messages.

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

= 1.99.0 [2024-12-24] =
* [Added] Instantly added as action
* [Added] Mailrelay added as action
* [Added] MailUp added as action
* [Added] Mail Mint added as action
* [Fixed] LionDesk integration
* [Fixed] EDD integration

= 1.98.0 [2024-12-17] =
* [Added] Campaigner added as action

= 1.97.0 [2024-12-12] =
* [Added] Monday.com added as action

= 1.96.0 [2024-12-10] =
* [Added] Dropbox added as action
* [Added] Google Drive added as action
* [Added] Fluent Support added as action
* [Added] Freshdesk added as action
* [Added] HighLevel added as action
* [Updated] AgileCRM integration

= 1.95.0 [2024-11-27] =
* [Added] Snov.io as action

= 1.94.0 [2024-11-26] =
* [Added] CiviCRM as action
* [Updated] Klaviyo integration

= 1.93.0 [2024-11-18] =
* [Added] MailPoet as action
* [Updated] Elementor Form integration
* [Updated] WP Fluent Form integration
* [Fixed] Minor CSS issue

= 1.92.1 [2024-11-11] =
* [Updated] Klaviyo integration
* [Updated] Acelle Mail integration
* [Updated] Asana integration
* [Updated] Attio CRM integration
* [Updated] Flodesk integration
* [Updated] Maileon integration
* [Updated] Webhook integration

= 1.92.0 [2024-11-03] =
* [Added] WS Form as trigger
* [Added] Flodesk as action
* [Updated] Klaviyo integration
* [Updated] Acelle Mail integration

= 1.91.0 [2024-09-24] =
* [Added] Maileon integration

= 1.90.1 [2024-09-02] =
* [Updated] Elemntor form integration
* [Updated] Hubspot CRM integration
* [Fixed] Bricks builder footer form issue
* [Fixed] Mailercloud update issue

= 1.90.0 [2024-08-26] =
* [Added] AcademyLMS as receiver
* [Added] FluentCRM as receiver
* [Updated] Klaviyo track profile
* [Updated] ZohoCRM authorization
* [Fixed] WPForms field issue
* [Fixed] Minor Pipedrive bug
* [Fixed] Attio field loading on edit screen
* [Fixed] Nonce issue while duplicating integration
* [Fixed] Quform field issue
* [Fixed] ZohoCRM date field issue
* [Fixed] Elementor Form loading issue
* [Fixed] Klaviyo - more than 10 lists issue