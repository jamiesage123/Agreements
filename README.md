# Agreements

This is a plugin for MyBB which allows site admins to add agreements which users can then agree to. Agreements can be attached to a forum category to prevent users from viewing a particular category/forum before they view your agreement.

## Installation

1. Merging all the folders and files in the "UPLOAD" directory to your root directory of your MyBB installation. This process *should not* override any existing files.
2. After installing this plugin, head over to your MyBB "Admin Control Panel" and navigate to "Configuration" -> "Plugins". On this page, you should see the "Agreement" under the "Inactive Plugins" list. Click "Install & Activate".
3. Now, head back to the "Configuration" page and scroll to the bottom of the page to find "Agreements" under "Configuration" on the left side menu. On this page you can add, edit and view your agreements.

## Usage

Agreements is very simple to use.

Firstly, you have to create an agreement. You can do this by heading into your AdminCP, then click into "Configuration" and find "Agreements" under the "Configuration" menu on the left-hand side.

The page you land on will display all of your agreements. To create a new one, click on "New Agreement".

On the "New Agreement" page, you can enter a name, the content of your agreement and the forums you wish to enforce the agreement on.

Please note that the forums selector is optional and does not include children. Therefore, if you select a parent forum and not any of its children, only the parent forum will have the agreement enforced.

After pressing "Create", your agreement will be created and activated.

Under the "Options" button, you have several different actions you can perform:

**View Agreement**
This will take you to the permanent agreement page which you can share with your users if you do not wish to enforce the agreement on any particular forum.

**View Agreed Users**
This will show you a list of all the users who have agreed to this agreement.

**Clear Agreed Users**
This will permanently delete all user agreement records, enforcing anyone who has already agreed to the agreement to view it again.

**Edit Agreement**
Edit the agreement.

**Delete Agreement**
Permanently delete the agreement and its associated user records.

## Credits

- MyBB - [https://mybb.com](https://mybb.com)
- Jamie Sage - [https://www.jamiesage.co.uk](https://www.jamiesage.co.uk)

## License

[MIT License](https://github.com/jamiesage123/Agreements/blob/master/LICENSE)
