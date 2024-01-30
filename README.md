# Inpsyde Disable Comments

A WordPress plugin that completely disables comments as a WordPress feature.

[![PHP Quality Assurance](https://github.com/inpsyde/disable-comments/actions/workflows/php-qa.yml/badge.svg)](https://github.com/inpsyde/disable-comments/actions/workflows/php-qa.yml)

---

## Table Of Contents

* [Features](#features)
* [A note about themes](#a-note-about-themes)
* [Requirements](#requirements)
* [Installation](#installation)
* [Crafted by Inpsyde](#crafted-by-inpsyde)
* [Credits](#credits)
* [License](#license)
* [Contributing](#contributing)

## Features

This package is a simple no-configuration plugin. Install, activate, and forget.

It does not distinguish between types of comments or post types; it makes it look like comments are not a WordPress
feature.

Among other things:

- Forces comments-related configuration to be disabled
- Prevents comments from being added
- Prevents comment queries from running
- Removes any reference to comments from the dashboard
- Makes sure all posts have comments disabled
- Removes comment-related editor blocks and the "Discussion" editor sidebar panel
- Removes comment-related REST API endpoints

---
> [!WARNING]  
> The plugin uses the [`allowed_block_types_all`](https://developer.wordpress.org/reference/hooks/allowed_block_types_all/) filter to disable comment-related blocks.
> When that filter runs, **Javascript-only registered blocks are** not recognized and **all removed by activating this plugin**.
---

## A note about themes

A theme might hardcode comments-related output even if comments are disabled.

That might be the case for FSE themes, but for those, it should be possible to use the site editor to remove the
undesired parts of templates.
"Traditional" themes should not output anything comments-related if comments are closed (and this plugin ensures that).
If you see any comments-related output in the theme, please contact the developer or use a child theme to replace the
offending templates/template parts.

## Requirements

- PHP 8.0+
- WP 6.0+

The plugin has no production dependencies. When installed for development via Composer, the package requires:

* [inpsyde/php-coding-standards](https://github.com/inpsyde/php-coding-standards/blob/master/LICENSE)
* [inpsyde/wp-stubs](https://github.com/inpsyde/wp-stubs/blob/main/LICENSE)
* [vimeo/psalm](https://github.com/vimeo/psalm/blob/master/LICENSE)

## Installation

The best way to install this package is with Composer:

```bash
$ composer require inpsyde/disable-comments
```

## Crafted by Inpsyde

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.

## Credits

Originally born as a fork of https://github.com/bueltge/remove-comments-absolutely.

## License

Copyright (c) 2023, Inpsyde GmbH

This software is released under the ["GNU General Public License v2.0 or later"](LICENSE) license.

## Contributing

Bug reports and contributions are welcome, but please don't ask to add features or configurations. For less "radical"
approaches to the topic, several other options are available.
