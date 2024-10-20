=== AFI - The Easiest Integration Plugin  ===
Contributors: nasirahmed, freemius
Tags: Contact Form 7, WooCommerce, Google Sheets, Pipedrive, Zoho CRM
Requires at least: 3.0.1
Tested up to: 6.6.2
Stable tag: 1.91.4
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

* **[ARForms](https://wordpress.org/plugins/arforms-form-builder/)**

* **ARMember**

* **[Beaver Builder Form](https://www.wpbeaverbuilder.com/)**

* **[Bricks Builder Form](https://bricksbuilder.io/forms/)**

* **[BuddyBoss](https://www.buddyboss.com/)**

* **[Caldera Forms](https://advancedformintegration.com/docs/afi/sender-platforms/caldera-forms/)**

* **[Contact Form 7](https://advancedformintegration.com/docs/afi/sender-platforms/contact-form-7/)**

* **[ConvertPro Forms](https://www.convertpro.net/)**

* **[Divi Forms](https://www.elegantthemes.com/gallery/divi/)**

* **[Easy Digital Downloads](https://wordpress.org/plugins/easy-digital-downloads/)**

* **[Elementor Pro Form](https://advancedformintegration.com/docs/afi/sender-platforms/elementor-pro-form/)**

* **[Everest Forms](https://advancedformintegration.com/docs/afi/sender-platforms/everest-forms/)**

* **[Fluent Forms](https://advancedformintegration.com/docs/afi/sender-platforms/wp-fluent-forms/)**

* **[FormCraft](https://advancedformintegration.com/docs/afi/sender-platforms/formcraft/)**

* **[Formidable Forms](https://advancedformintegration.com/docs/afi/sender-platforms/formidable-forms/)**

* **[Forminator (Forms only)](https://advancedformintegration.com/docs/afi/sender-platforms/forminator/)**

* **GamiPress**

* **[GiveWP](https://wordpress.org/plugins/give/)**

* **[Gravity Forms](https://advancedformintegration.com/docs/afi/sender-platforms/gravity-forms/)**

* **[Happyforms](https://advancedformintegration.com/docs/afi/sender-platforms/happy-forms/)**

* **[JetFormBuilder](https://wordpress.org/plugins/jetformbuilder/)**

* **[Kadence Blocks Form](https://www.kadencewp.com/kadence-blocks/)**

* **[LearnDash](https://www.learndash.com/)**

* **[LifterLMS](https://wordpress.org/plugins/lifterlms/)**

* **[Live Forms](https://wordpress.org/plugins/liveforms/)**

* **[MailPoet Forms](https://wordpress.org/plugins/mailpoet/)**

* **[MasterStudy LMS](https://wordpress.org/plugins/masterstudy-lms-learning-management-system/)**

* **[Metform](https://wordpress.org/plugins/metform/)**

* **[Ninja Forms](https://advancedformintegration.com/docs/afi/sender-platforms/ninja-forms/)**

* **Paid Membership Pro**

* **[TutorLMS](https://wordpress.org/plugins/tutor/)**

* **[QuForm2](https://advancedformintegration.com/docs/afi/sender-platforms/quform/)**

* **[Smart Forms](https://advancedformintegration.com/docs/afi/sender-platforms/smart-forms/)**

* **[weForms](https://advancedformintegration.com/docs/afi/sender-platforms/weforms/)**

* **[WPForms](https://advancedformintegration.com/docs/afi/sender-platforms/wpforms/)**

*  **[WooCommerce](https://advancedformintegration.com/docs/afi/sender-platforms/woocommerce/)**

*  **[UTM Parameters](https://advancedformintegration.com/docs/afi/sender-platforms/utm-parameters/)**: You can also grab and send UTM variables. Just activate the feature from the plugin's settings page. Now use tags like {{utm_source}}, {{utm_medium}}, {{utm_term}}, {{utm_content}}, {{utm_campaign}}, {{gclid}}, etc.

<blockquote>
<p><strong>Premium Version Features.</strong></p>
<ul>
<li>All form fields</li>
<li>Inbound Webhooks</li>
</ul>
</blockquote>

### RECEIVER PLATFORMS (ACTION) ###

*  **[Acelle Mail](https://acellemail.com/)** - Creates contact and adds to a list. Requires a pro license to use custom fields and tags.

*  **[ActiveCampaign](https://advancedformintegration.com/docs/afi/receiver-platforms/activecampaign/)** - ActiveCampaign is a popular email marketing and automation platform. This plugin allows you to integrate it with any sender platform, so when a user submits the form with personal details, it will automatically create a contact in ActiveCampaign. The contact can be added to a list or automation. Also, deals and notes can be created for that contact. Requires a pro license to use custom fields.

*  **[Agile CRM](https://www.agilecrm.com/)** - This plugin allows creating contact, deal and note. Requires a pro license to use tags and custom fields.

*  **[Airtable](https://airtable.com/)** - Creates new row to selected table.

*  **[Asana](https://www.asana.com/)** - Allows to create a new task. Custom fields are support in the AFI Pro version.

*  **[Attio CRM](https://www.attio.com/)**

*  **[Autopilot](https://journeys.autopilotapp.com/)** - This plugin allows creating/updating contact and adding to a list. Requires a pro license to use custom fields.

*  **[AWeber](https://www.aweber.com/)** - Allows to create contact and subscribe to a list. A Pro license is required to use custom fields and tags.

*  **[beehiiv](https://www.beehiiv.com/)** - Create new subscriber to a selected publiction.

*  **[Benchmark Email](https://www.benchmarkemail.com/)** - Allows to create contact and subscribe to a list. A Pro license is required to use custom fields.

*  **[Campaign Monitor](https://www.campaignmonitor.com/)** - Allows to create contact and subscribe to a list. A Pro license is required to use custom fields.

*  **[Capsule CRM](https://capsulecrm.com/)** - Supports adding party, opportunity, case, and task. Requires the Pro version to add tags, and custom fields.

*  **[ClinchPad CRM](https://clinchpad.com/)** - Creates new Leads, including organization, contact, note, product, etc.

*  **[Close CRM](https://close.com/)** - Close is the inside sales CRM of choice for startups and SMBs. You can add a new lead and contact to Close CRM. The Pro version supports custom fields.

*  **[CompanyHub](https://www.companyhub.com/)** - Creates basic contact.

*  **[Constant Contact](https://www.constantcontact.com/)** - Allows you to create new contacts and subscribe to a list. A Pro license is required to use custom fields and tags.

*  **[ConvertKit](https://convertkit.com/)** - ConvetKit is another popular email marketing software. This plugin allows you to create a new contact and subscribe to a sequence or form. A Pro license is required to use custom fields and tags.

*  **[Copper CRM](https://www.copper.com/)** - This allows you to create a new company, person, and deal in Copper CRM. The Pro version supports custom fields and tags.

*  **[ClickUp](https://clickup.com/)** - Create tasks. Requires a Pro license to add tags and custom fields.

*  **[Curated](https://curated.co/)** - Add subscriber.

*  **[Demio](https://www.demio.com/)** - Register people to webinar.

*  **[DirectIQ](https://www.directiq.com/)** - Allows you to create contact and add to the mailing list.

*  **[Drip](https://www.drip.com/)** - Create Contact (Basic Fields), add to Campaign, Workflow. The Pro version supports custom fields.

*  **[EasySendy](https://www.easysendy.com/)** - This allows you to create contact and add them to the mailing list. Requires a Pro license to use custom fields.

*  **[Elastic Email](https://elasticemail.com/)** - Elastic Email is a marketing platform built on the most cost-effective delivery engine. You can create a contact and add it to a mailing list. A Pro license is required to use custom fields.

*  **[EmailOctopus](https://emailoctopus.com/)** - Allows you to add contact and subscribe to a list. A Pro license is required to use custom fields.

*  **[EngageBay](https://engagebay.com/)** - Create new contact and subscribe to a list. A Pro license is required to use custom fields.

*  **[EverWebinar](https://home.everwebinar.com/index)** - Add registrant to webinar.

*  **[Freshworks CRM (Freshsales)](https://www.freshworks.com/crm/sales/)** - Freshworks CRM is a full-fledged Sales CRM software for your business. This plugin allows you to create accounts, contact, and deals with custom fields.

*  **[GetResponse](https://www.getresponse.com/)** - GetResponse is a powerful, simplified tool to send emails, create pages, and automate your marketing. This plugin allows you to create a subscriber and add it to the mailing list The Pro version supports custom fields and tags.

*  **[Google Calendar](https://calendar.google.com)** - Allows you to create a new event on a selected Google Calendar with supplied data.

*  **[Google Sheets](https://seheets.google.com)** - When a sender form is submitted, or a WooCommerce order is created, this plugin allows you to create a new row on a selected sheet with supplied data. In the Pro version, it is allowed to create separate rows for WooCommerce Order Items. For example, if an order has 5 items so 5 separate rows will be created for each item.

*  **[Hubspot CRM](https://www.hubspot.com/)** - Allows you to create a new contact in Hubspot CRM with additional custom fields support. The AFI Pro supports creating companies, deals, tickets, tasks, etc.

*  **[Insightly](https://www.insightly.com/)** - Create New organisation, contact and opportunity with basic fields. The Pro plugin supports custom fields and tags.

*  **[Jumplead](https://jumplead.com/)** - Jumplead offers a full all-in-one inbound marketing automation platform. This plugin allows adding a contact to it.

*  **[Klaviyo](https://www.klaviyo.com/)** - Klaviyo is an email marketing platform created for online businesses — featuring powerful email and SMS marketing automation. Using Advanced Form Integration, you can add a contact, and subscribe to a list. Pro license is required to use custom properties.

*  **[lemlist](https://lemlist.com/)** - A cold email tool powering sales teams, agencies, and B2B businesses to personalize and automate outreach campaigns. This plugin allows creating contact and adds it to a campaign.

*  **[LionDesk](https://www.liondesk.com/)** - LionDesk offers sales and marketing automation for Real Estate Agents and Brokers. Creating a new contact is supported using our plugin. In the Pro version, you can add tags and custom fields.

*  **[Livestorm](https://livestorm.co/)** - Add people to event session.

*  **[MailBluster](https://mailbluster.com/)** - Creates new lead. Requires Pro license to use Custom fields, and tags.

*  **[Mailchimp](https://mailchimp.com/)** - Allows you to create contacts, subscribe to a list and group, and unsubscribe from the list. Requires Pro license to use Custom|Merge fields, and tags.

*  **[Maileon](https://maileon.com/)** - Adds new subscribers to a selected lists.

*  **[Mailercloud](https://www.mailercloud.com/)** - Adds new subscribers to a selected lists. Requires a Pro license to use custom fields.

*  **[MailerLite](https://www.mailerlite.com/)** - Allows you to add contact and subscribe to a group. Requires a Pro license to use custom fields.

*  **[MailerLite Classic](https://www.mailerlite.com/)** - Allows you to add contact and subscribe to a group. Requires a Pro license to use custom fields.

*  **[Mailify](https://www.mailify.com/)** - Mailify is a email marketing solution. This plugin allows you to create contacts and subscribe to lists. Requires a Pro license to use custom fields.

*  **[Mailjet](https://www.mailjet.com/)** - Allows you to create a contact and add it to a list. Requires a Pro license to use custom fields.

*  **[MailWizz](https://www.mailwizz.com/)** - Create contact and add to a list. The Pro plugin supports custom fields.

*  **[Mautic](https://www.mautic.org/)** - Allows you to create a contact. Requires a Pro license to use custom fields.

*  **[Moosend](https://moosend.com/)** - Allows you to create a contact and add it to a list. Requires a Pro license to use custom fields.

*  **[Omnisend](https://www.omnisend.com/)** - Create new contacts. Requires pro license to use custom fields and tags.

*  **[Onehash.ai](https://www.onehash.ai/)** - The plugin allows you to create new leads, contacts, and customers.

*  **[Ortto](https://ortto.com/)** - Allows creating contact. Requires a pro license to use tags and custom fields.

*  **[Pabbly Email Marketing](https://www.pabbly.com//)** - Allows you to create a subscriber and add it to a list. Requires a Pro license to use custom fields.

*  **[Pipedrive](https://www.pipedrive.com/)** - This plugin allows you to create organizations, people, deals, notes, activity with custom fields support. Requires a Pro license to add new lead.

*  **[Pushover](https://pushover.net/)** - Allows you to send push messages to Android/iOS/Desktop.

*  **[Robly](https://robly.com/)** - Add/update new subscriber. Requires a Pro license to use custom fields and tags.

*  **[Sales.Rocks](https://sales.rocks/)** - Allows you to add contact and subscribe to a list.

*  **[Salesflare](https://salesflare.com/)** - Allows you to create organization, contact, opportunity and task.

*  **[Selzy](https://selzy.com/)** - Create new contact and subscribe to a list. The Pro version supports custom fields and tags.

*  **[SendFox](https://sendfox.com/)** allows you to create contacts and subscribe to a list. The Pro version allows you to add custom fields.

*  **[SendPulse](https://sendpulse.com/)** - Allows you to create contact and subscribe to a list.

*  **[Brevo (Sendinblue)](https://www.brevo.com/)** - Brevo (formerly Sendinblue) is a complete all-in-one digital marketing toolbox. Our plugin allows you to create subscribers and add them to a list. A Pro license is required to use custom fields and other languages.

*  **[SendX](https://www.sendx.io/)** - Allows you to create new contact.

*  **[Sendy](https://sendy.co/)** - Allows creating contact and subscribe to a list. A Pro license is required to use custom fields.

*  **[Slack](https://slack.com/)** - Allows sending channel message.

*  **[Smartsheet](https://smartsheet.com/)** - Allows creating new row.

*  **[Trello](https://www.trello.com/)** - This plugin allows you to create a new card in Trello.

*  **[Twilio](https://www.twilio.com/)** - This plugin allows you to send customized SMS using Twilio.

*  **[Vertical Response](https://verticalresponse.com/)** - This plugin allows creating contacts in a certain list. Requires a pro license to use custom fields.

*  **[Wealthbox CRM](https://www.wealthbox.com/)** - This plugin allows creating contacts. Requires a pro license to use tags and custom fields.

*  **Webhook** - Allows you to send data to any webhook URL. In the Pro version, you can send fully customized headers and bodies (GET, POST, PUT, DELETE). You can literally send data to any API with an API token and Basic auth.

*  **[WebinarJam](https://home.webinarjam.com/index)** - Add registrant to webinar.

*  **[Woodpecker.co](https://woodpecker.co/)** - Allows creating subscriber. Requires Pro license to use custom fields.

*  **WordPress** - Create new post.

*  **[Zapier](https://zapier.com/)** - Sends data to Zapier webhook.

*  **[Zoho Campaigns](https://www.zoho.com/campaigns/)** - Allows creating subscribers and adding to a list. Requires Pro license to use custom fields.

*  **[Zoho Bigin](https://bigin.com/)** - Allows creating Contacts, Companies, Pipelines, Tasks, Notes, etc. Requires Pro license to use custom fields.

*  **[Zoho CRM](https://www.zoho.com/crm/)** - Allows creating Leads, Contacts, Accounts, Deals, Tasks, Meetings, Calls, Products, Campaigns, Vendors, Cases, and Solutions. Requires Pro license to use custom fields.

*  **[Zoho Desk](https://www.zoho.com/desk/)**

*  **[Zoho Sheet](https://www.zoho.com/sheet/)** - Creates a new row on selected worksheet.


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

= 1.89.0 [2024-06-25] =
* [Added] Attio CRM as a receiver.

= 1.88.0 [2024-06-11] =
* [Added] JetFormBuilder as a trigger.

= 1.87.0 [2024-06-03] =
* [Added] Bricks Builder Form as a trigger.

= 1.86.0 [2024-05-13] =
* [Added] EDD as a trigger.
* [Added] MailPoet Forms as a trigger.
* [Added] MasterStudy LMS as a trigger.

= 1.85.0 [2024-04-16] =
* [Added] ZohoDesk as an action.
* [Updated] Klaviyo new API
* [Updated] Constant Contact integration
* [Updated] ClickUp integration

= 1.84.0 [2024-04-03] =
* [Added] ConvertPro forms as a trigger.
* [Updated] WordPress 6.5 compatible.

= 1.83.0 [2024-04-02] =
* [Added] PaidMembershipPro as a trigger.

= 1.82.0 [2024-03-05] =
* [Added] GamiPress as trigger
* [Added] Kadence Blocks Form as trigger.
* [Added] Metform as trigger.
* [Added] ARMember as trigger.

= 1.81.0 [2024-02-29] =
* [Added] BuddyBoss as trigger.
* [Added] AffiliateWP as trigger.
* [Added] Beaver Builder Form as trigger.

= 1.80.0 [2024-02-22] =
* [Added] TutorLMS as trigger.

= 1.79.0 [2024-02-06] =
* [Added] ARForms and Divi Forms as trigger.

= 1.78.0 [2024-01-09] =
* [Added] LifterLMS as trigger.

= 1.77.0 [2023-12-05] =
* [Added] Acelle Mail as the receiver

= 1.76.0 [2023-11-06] =
* [Updated] Updated all trigger processing 

= 1.75.0 [2023-10-10] =
* [Added] beehiiv as a receiver platform

= 1.74.0 [2023-09-25] =
* [Added] Zoho Bigin as a receiver platform

= 1.73.0 [2023-09-06] =
* [Added] MailBluster as a receiver platform

= 1.72.0 [2023-08-30] =
* [Added] GiveWP as a sender platform

= 1.71.0 [2023-08-23] =
* [Added] Encharge as a receiver platform

= 1.70.0 [2023-08-16] =
* [Added] LearnDash as a sender platform