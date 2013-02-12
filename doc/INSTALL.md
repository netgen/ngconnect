# Netgen Connect extension installation instructions

## Installation

### Unpack/unzip

Unpack the downloaded package into the `extension` directory of your eZ Publish installation.

### Activate extension

Activate the extension by using the admin interface ( Setup -> Extensions ) or by
prepending `ngconnect` to `ActiveExtensions[]` in `settings/override/site.ini.append.php`:

    [ExtensionSettings]
    ActiveExtensions[]=ngconnect

### Create SQL table in your eZ Publish database

Extension requires an additional table to be added to your database. Use the following command from your eZ Publish
root folder, replacing `user`, `password`, `host` and `database` with correct values and removing double quotes

    mysql -u "user" -p"password" -h"host" "database" < extension/ngconnect/update/database/mysql/1.1/ngconnect-dbupdate-1.0-to-1.1.sql

### Regenerate autoload array

Run the following from your eZ Publish root folder

    php bin/php/ezpgenerateautoloads.php --extension

Or go to Setup -> Extensions and click the "Regenerate autoload arrays" button

### Configure the extension

Copy `ngconnect.ini` to your extension and configure the extension. Detailed instructions are within the ini file.

### Modify your templates

Include `extension/ngconnect/design/standard/templates/ngconnect/ngconnect.tpl` template in place where you want your login buttons to be displayed.

    {include uri="design:ngconnect/ngconnect.tpl"}

### Clear caches

Clear all caches (from admin 'Setup' tab or from command line).
