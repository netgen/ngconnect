# Netgen Connect extension
Netgen Connect is a simple eZ Publish extension able to provide user sign in
by using social network authentication and authorization. (Facebook Connect
for example).

At this time, Twitter, Facebook and Tumblr are supported.

After installing the extension and including its template in your pagelayout,
users are presented with buttons which take them to social network of their choice
so they can authenticate themselves and authorize you to access their information.
Upon taking an action there (allowing or denying authorization), users are taken
back to your eZ Publish installation and automatically logged in to your site
in case of successful authorization.

You can configure this extension by changing the values in `ngconnect.ini`, or, better
yet (and recommended way), by overriding `ngconnect.ini` and configure desired settings
in your override. Full instructions for configuring the extension are included in
`ngconnect.ini` settings file.

Before using the extension, you need to create Facebook, Twitter or Tumblr apps and have
their consumer keys and secrets ready.

This extension is published under GNU GPLv2 license and as such can be freely used
and modified.

## License, installation instructions and changelog
[Documentation](doc/Netgen Connect doc.pdf)

[Installation instructions](doc/INSTALL.md)

[License](LICENSE)

[Configuration instructions](settings/ngconnect.ini)

[Changelog](doc/CHANGELOG.md)

## Project page

You can find the project page on [projects.ez.no](http://projects.ez.no/ngconnect)

## PHP libraries used in this extension

[OAuth PHP library](http://code.google.com/p/oauth/)

[TwitterOAuth PHP library](http://github.com/abraham/twitteroauth)
