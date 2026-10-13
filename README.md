# Mailster Piwik

Contributors: everpress, mailster, xaverb  
Tags: mailster, piwik, matomo, analytics, newsletter  
Requires at least: 6.0  
Tested up to: 6.5  
Stable tag: 1.0.0  
License: GPLv2 or later

Track Mailster newsletter link clicks in Piwik (Matomo) with campaign name and keyword parameters.

## Description

**Mailster Piwik** connects the [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics) to **Piwik** / **Matomo** analytics so you can attribute same-site newsletter clicks to campaigns in your analytics reports.

When a subscriber clicks a link in a Mailster campaign that points to your own site, the add-on appends Piwik campaign tracking parameters (`pk_campaign` and `pk_kwd`) to the redirect URL. Matomo still recognizes these legacy `pk_*` parameters for campaign reporting (alongside newer `mtm_*` names).

> This plugin requires [Mailster](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics) and a Piwik or Matomo site (Site ID and tracker domain). It is not affiliated with Matomo or the Piwik PRO brand.

### What it does

* Adds `pk_campaign` and `pk_kwd` query arguments to **same-site** Mailster click redirects
* Lets you set default campaign name and keyword patterns (with merge tags for campaign, subscriber, and link data)
* Optionally override those values **per newsletter campaign** from a campaign metabox
* Configures Site ID, tracker domain, and optional `setDomains`
* Can output the Piwik JavaScript tracker in the Mailster campaign preview footer when Site ID and domain are set

### Merge tags you can use

Use these placeholders in the campaign name and keyword fields:

`%%CAMP_ID%%`, `%%CAMP_TITLE%%`, `%%CAMP_TYPE%%`, `%%CAMP_LINK%%`, `%%SUBSCRIBER_ID%%`, `%%SUBSCRIBER_EMAIL%%`, `%%SUBSCRIBER_HASH%%`, `%%LINK%%`

Defaults in the settings screen use `%%CAMP_TITLE%%` for the campaign name and `%%LINK%%` for the keyword until you change them.

## Installation

1. Install and activate the [Mailster Newsletter Plugin for WordPress](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics).
2. Upload the entire `mailster-piwik` folder to `/wp-content/plugins/`, or install **Mailster Piwik** from Plugins → Add New.
3. Activate **Mailster Piwik**.
4. Open **Newsletter → Settings → Piwik** and enter your analytics **Site ID** and **domain** (the host that serves `piwik.js` / your Matomo tracker).
5. Adjust the default campaign name and keyword patterns if needed. Optionally enable **campaign based values** to edit them on each campaign screen.
6. Send or preview a campaign and confirm click URLs on your site include `pk_campaign` / `pk_kwd`, then check campaign reports in Piwik or Matomo.

## Frequently Asked Questions

### What does the plugin require?

You need an active [Mailster](https://mailster.co/?utm_campaign=wporg&utm_source=wordpress.org&utm_medium=readme&utm_term=Piwik+Analytics) install and a Piwik or Matomo property (Site ID plus the domain where the tracker is hosted).

### Does this work with Matomo?

Yes for campaign attribution via `pk_campaign` and `pk_kwd`, which Matomo still accepts. The settings UI and plugin name refer to Piwik (the former product name). This add-on does not switch automatically to Matomo’s newer `mtm_*` parameter names.

### Are external (off-site) links tagged?

No. Only click redirects whose host matches your WordPress site URL receive the campaign parameters. Links to other domains are left unchanged.

### Can I set different tracking values per campaign?

Yes. Enable **campaign based value** in the Piwik settings tab. A **Piwik Analytics** metabox appears on the campaign edit screen so you can override the campaign name and keyword for that newsletter.

### Where is subscriber or click data sent?

Campaign parameters are added to links on **your** site. The JavaScript tracker (when configured) sends analytics hits to the Piwik/Matomo server hostname you enter in settings. Use only a tracker endpoint you control or trust.

## Screenshots

### 1. Option Interface

Settings for Site ID, domain, defaults, and campaign-based overrides.

![Option Interface](https://ps.w.org/mailster-piwik/assets/screenshot-1.png)

## Changelog

### Unreleased

### 1.0.0

- initial release
