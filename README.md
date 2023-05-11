# Inpsyde Disable Comments

A WordPress plugin that completely ditches comments as a WordPress feature.


## Features

This is a simple no-configuration plugin. Install, activate and forget.

It does not distinguish between type of comments or post types, it makes look like comments are not
a WordPress feature.

Among other things:

- Forces comments-related configuration to be disabled
- Prevents comments to be added
- Prevents comment queries to run
- Removes from dashboard any reference to comments
- Makes sure all post have comments disabled
- Removes comment-related editor blocks and "Discussion" editor sidebar panel
- Removes comment-related REST API endpoints


## A note about themes

A theme might hardcode comments-related output even if comments are disabled.

That might be the case for FSE themes, but for those it should be possible to use the site editor to 
remove the undesired parts of templates.
"Traditional" themes should not output anything comments-related if comments are closed (and that
is ensured by this plugin).
If you see any comments-related output in theme, please reach out to the theme developer or use a 
child theme to replace the offending templates/template parts.


## Support and contributions

Bug reports and contributions are welcome, but please don't ask adding features or configurations.
For less "radical" approaches to the topic there are several other options available.


## Requirements

- PHP 8.0+
- WP 6.0+

The plugin has no production dependencies.
When installed for development via Composer, the package requires:

* [inpsyde/php-coding-standards](https://github.com/inpsyde/php-coding-standards/blob/master/LICENSE)
* [inpsyde/wp-stubs](https://github.com/inpsyde/wp-stubs/blob/main/LICENSE)
* [vimeo/psalm](https://github.com/vimeo/psalm/blob/master/LICENSE)



## Installation

Best served via Composer:

```bash
$ composer require inpsyde/disable-comments
```


## Crafted by Inpsyde

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.


## License

Copyright (c) 2023, Inpsyde GmbH

This software is released under the ["GNU General Public License v2.0 or later"](LICENSE) license.
