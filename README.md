# WP Multisite Global Footer

Simple WordPress multisite plugin that adds a network-wide link to the main site.

## Features

- Network-admin settings page (`Network Admin -> Settings -> Global Footer Link`)
- Footer link shown on every site in the network
  - Display as button or text link
  - Custom text
  - Custom footer background color
  - Custom footer text color
  - Custom button color
  - Fallback output-buffer injection so footer still appears on themes missing `wp_footer()`
- Optional compact header menu link
  - Position: next to WP icon / before Login/Register when present
  - Works for logged-out and logged-in users
  - Supports standard menus and hardcoded header link structures (DOM fallback)
  - Custom text
  - Optional house icon
  - Custom background and text color
  - Opens in same/new tab

## Installation

1. Copy this plugin file to your plugins directory.
2. Network activate **WP Multisite Global Footer**.
3. Go to **Network Admin -> Settings -> Global Footer Link**.
4. Save your preferred global settings.

