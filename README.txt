INSTALLATION INSTRUCTIONS:

- Always backup your database before installing a module!
Log into your ExpressionEngine Control Panel, visit the following page:
Admin > Utilities > Database Backup, and make that backup!

- Upload the "reeorder" directory and everything inside it to your server and place it in the /system/modules/ directory. You should end up with it located at /system/modules/reeorder/.

- Upload the file named "lang.reeorder.php" to your /system/language/english/ directory on your server. You should end up with it located at /system/language/english/lang.reeorder.php

- Go to the Modules tab. You will see an entry listed for "REEOrder". On the right side of the table, click the Install link corresponding to the REEOrder Module. This will install the necessary database tables and settings for the module.

- The REEOrder entry is now a clickable link, click it to start using the REEOrder module.

Optionally:
- Upload the file named "ext.reeorder.php" to your /system/extensions/ directory on your server. You should end up with it located at /system/extensions/ext.reeorder.php


ABOUT THIS MODULE:

The REEOrder module allows you to easily re-order your entries.

The module uses EE’s ability to order Weblog Entries by a Custom Field, it does not add any queries or other additional processing to your templates.

Installation and usage:

Step 1:
Create a new Custom Field in your Field Group, this field will hold the sorting value. You aren’t actually going to use this field. The REEOrder Module will do all the heavy lifting. (The actual sorting.)
- The preferred "Field Type" would be "Text Input"
- Name it anything you like, and feel free to set any other options

Step 2:
Install the "REEOrder" Module

Step 3:
In the module's Preferences page, select the Custom Field you just created.

Step 4:
Select a weblog and re-order your entries

Step 5:
Create or modify a template and change the "orderby" parameter in the weblog tag to the name of your new Custom Field

Example of the weblog tag:

{exp:weblog:entries weblog="default_site" orderby="your_new_custom_field" disable="categories|member_data|pagination|trackbacks"}

<h3>{title}</h3>
{body}

{/exp:weblog:entries}

If you change Sort Order to "Ascending" in the module's Preferences, make sure you also add sort"asc" to your weblog tag.

Step 6: (optional)
If you like you can install the additional "REEOrder" Extension, this will remove the Custom Field from the Entry Form. (so your client won't accidentally mess with it).