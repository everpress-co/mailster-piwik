# Mailster Piwik

Contributors: everpress, mailster, xaverb  
Tags: mailster, matomo, piwik, analytics, newsletter  
Requires at least: 6.0  
Tested up to: 6.5  
Stable tag: 1.0.0  
License: GPLv2 or later

Track Mailster newsletter link clicks in Matomo (Piwik) with pk_campaign and pk_kwd campaign parameters.

## Description

**Mailster Piwik** connects the [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics) to **Matomo** (formerly **Piwik**) so you can attribute newsletter clicks to campaigns in your analytics reports.

When a subscriber clicks a **same-site** link in a Mailster campaign, the add-on appends Matomo-compatible campaign query arguments (`pk_campaign` and `pk_kwd`) to the click redirect URL. Matomo still accepts these legacy `pk_*` names for campaign reporting (alongside newer `mtm_*` parameters).

> This plugin requires [Mailster](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics) and a Matomo or Piwik property (Site ID and tracker domain). It is not affiliated with Matomo or the Piwik PRO brand. For click attribution to appear in reports, your destination pages still need Matomo/Piwik tracking installed on the website.

### What it does

* Appends `pk_campaign` and `pk_kwd` to **same-site** Mailster click redirects (exact host match with your WordPress `site_url` host)
* Lets you set default campaign name and keyword patterns, including merge tags for campaign, subscriber, and link data
* Optionally overrides those values **per campaign** via a **Piwik Analytics** metabox when **Campaign based value** is enabled
* Stores Site ID, tracker domain, and optional `setDomains` for the embedded tracker snippet
* Outputs the Piwik JavaScript tracker (`piwik.js` / `piwik.php`) in the Mailster **campaign web version** footer when Site ID and domain are set

### What it does not do

* It does **not** tag external (off-site) links — only redirects whose host matches your site host
* It does **not** switch to Matomo’s newer `mtm_*` parameter names (it always writes `pk_campaign` / `pk_kwd`)
* It does **not** add an email open-tracking pixel; Mailster and Matomo handle opens separately if you configure them
* The embedded script is **not** a full-site Matomo install — it only loads on the Mailster campaign web version page

### Merge tags you can use

Use these placeholders in the campaign name and keyword fields:

`%%CAMP_ID%%`, `%%CAMP_TITLE%%`, `%%CAMP_TYPE%%`, `%%CAMP_LINK%%`, `%%SUBSCRIBER_ID%%`, `%%SUBSCRIBER_EMAIL%%`, `%%SUBSCRIBER_HASH%%`, `%%LINK%%`

* `%%CAMP_TYPE%%` resolves to `autoresponder` or `regular` based on the campaign status
* Defaults on the settings screen are `%%CAMP_TITLE%%` for the campaign name and `%%LINK%%` for the keyword until you change them

Avoid putting personally identifiable merge tags (such as `%%SUBSCRIBER_EMAIL%%`) into public URLs unless you intend that data to appear in query strings and analytics.

## Installation

1. Install and activate the [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics).
2. Upload the entire `mailster-piwik` folder to `/wp-content/plugins/`, or install **Mailster Piwik** from **Plugins → Add New**.
3. Activate **Mailster Piwik**.
4. Open **Newsletter → Settings → Piwik** and enter your analytics **Site ID** and **domain** (the host that serves the tracker; this add-on loads `piwik.js` and posts to `piwik.php` on that host).
5. Adjust the default campaign name and keyword patterns if needed. Optionally enable **Campaign based value** to edit them on each campaign screen.
6. Ensure Matomo/Piwik tracking is present on the pages users land on after clicking newsletter links.
7. Send a campaign (or click through a tracked link) and confirm same-site destinations include `pk_campaign` / `pk_kwd`, then check campaign reports in Matomo or Piwik.

## Frequently Asked Questions

### What does the plugin require?

An active [Mailster](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics) install and a Matomo or Piwik property (Site ID plus the domain where the tracker is hosted). Your site pages need Matomo/Piwik tracking so campaign parameters on landing URLs are recorded.

### Does this work with Matomo?

Yes. Campaign attribution uses `pk_campaign` and `pk_kwd`, which Matomo still accepts for backwards compatibility. The settings UI and plugin name use **Piwik** (the former product name). This add-on does not output Matomo’s newer `mtm_*` parameter names.

### Where do I configure it in Mailster?

Go to **Newsletter → Settings** and open the **Piwik** tab. That matches Mailster’s Newsletter menu and Settings submenu.

### Are external (off-site) links tagged?

No. Only click redirects whose host exactly matches your WordPress site URL host receive the campaign parameters. Links to other domains (and hosts that differ, such as a bare domain vs `www`) are left unchanged. Unlike some other Mailster analytics add-ons, there is no option to tag external domains.

### Can I set different tracking values per campaign?

Yes. Enable **Campaign based value** on the Piwik settings tab. A **Piwik Analytics** metabox appears on the campaign edit screen so you can override the campaign name and keyword for that newsletter. Fields are read-only for finished/active campaigns (and autoresponder stats views).

### What is the optional JavaScript tracker for?

When Site ID and domain are set, the add-on prints a classic Piwik `_paq` snippet (including optional `setDomains`) on the Mailster **campaign web version** page via Mailster’s `mailster_wpfooter` hook. It loads `piwik.js` and uses `piwik.php` as the tracker endpoint on the domain you entered. It does not replace installing Matomo on your main website.

### Does this track newsletter opens?

No. This add-on only adds campaign parameters on click redirects. Open tracking is separate (Mailster’s own open tracking, and/or a Matomo email tracking pixel if you add one yourself).

### Where is subscriber or click data sent?

Campaign parameters are added to links on **your** site. If you configure Site ID and domain, the campaign web version page can send analytics hits to the Piwik/Matomo hostname you enter. Use only a tracker endpoint you control or trust. Merge-tag values you put in `pk_campaign` / `pk_kwd` may appear in URLs and thus in analytics logs.

## Screenshots

### 1. Option Interface

Settings for Site ID, domain, defaults, and campaign-based overrides.

![Option Interface](https://ps.w.org/mailster-piwik/assets/screenshot-1.png)

## Changelog

### Unreleased

### 1.0.0

- initial release
