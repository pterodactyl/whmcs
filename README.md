## Pterodactyl-WHMCS
WHMCS Module for the [Pterodactyl Panel](https://github.com/Pterodactyl/Panel/)

### NOTE!
This module requires the panel to be on version 0.7.3 and above, if you need one for other versions check [death-droid's module](https://github.com/death-droid/Pterodactyl-WHMCS) out.

### Installation
[Video Tutorial](https://www.youtube.com/watch?v=wURpRD9vfj4)  

1. Download/Git clone this repository.  
2. Move the ``pterodactyl/`` folder into ``<path to whmcs>/modules/servers/``.
3. Create API Credentials with these permissions: ![Image](https://my-cat.is-going-to.space/fa1eee.png)
4. In WHMCS navigate to Setup > Products/Services > Servers
5. Create new server, fill the name with anything you want, hostname as the url to the panel either as an IP or domain. For example: http://123.123.123.123 or http://my.pterodactyl.panel/
6. Change Server Type to Pterodactyl, fill the password field with your generated API Key.
7. Confirm everything works by clicking the Test Connection button -> Save Changes.
8. Go back to the Servers screen and press Create New Group, name it anything you want and choose the created server and press the Add button, Save Changes.
9. Navigate to Setup > Products/Services > Products/Services
10. Create your desired product (and product group if you haven't already) with the type of Other and product name of anything -> Continue.
11. Click the Module Settings tab, choose for Module Name Pterodactyl and for the Server Group the group you created in step 8.
12. Fill all non-optional fields, and you are good to go!

### How to enable module debug log
1. In WHMCS navigate to Utilities > Logs > Module Log
2. Click the Enable Debug Logging button.
3. Do the action that failed again and you will have required logs to debug the issue. All 404 errors can be ignored.
4. Remember to Disable Debug Logging if you are using this in production, as it's not recommended to have it enabled.

### Credits
[Dane](https://github.com/DaneEveritt) and [everyone else](https://github.com/Pterodactyl/Panel/graphs/contributors) involved in development of the Pterodactyl Panel.  
[death-droid](https://github.com/death-droid) for the original WHMCS module.  
[Crident](https://crident.com) for providing me a dev environment to test the module on and the installation video.  