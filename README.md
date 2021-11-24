## WHMCS

WHMCS Module for the [Pterodactyl Panel](https://github.com/pterodactyl/panel/).

## Configuration support

Please use the [Pterodactyl Discord](https://discord.gg/pterodactyl) for configuration related support instead of GitHub issues.

## NOTE

This module requires the panel to be on version 1.0.0 and above, if you need one for the 0.7.x versions, check the [0.7 branch.](https://github.com/pterodactyl/whmcs/tree/0.7)

## Installation

[Video Tutorial](https://www.youtube.com/watch?v=wURpRD9vfj4) (uses 0.7 version of the panel but nothing changed functionality wise)

1. Download/Git clone this repository.
2. Move the ``pterodactyl/`` folder into ``<path to whmcs>/modules/servers/``.
3. Create API Credentials with these permissions: ![Image](https://i.imgur.com/oeoTyBO.png)
4. In WHMCS 8+ navigate to System Settings → Servers. In WHMCS 7 or below navigate to Setup → Products/Services → Servers
5. Create new server, fill the name with anything you want, hostname as the url to the panel either as an IP or domain. For example: ``123.123.123.123`` or ``my.pterodactyl.panel``
6. Change Server Type to Pterodactyl, leave username empty, fill the password field with your generated API Key.
7. Tick the "Secure" option if your panel is using SSL.
8. Confirm that everything works by clicking the Test Connection button -> Save Changes.
9. Go back to the Servers screen and press Create New Group, name it anything you want and choose the created server and press the Add button, Save Changes.
10. Navigate to Setup > Products/Services > Products/Services
11. Create your desired product (and product group if you haven't already) with the type of Other and product name of anything -> Continue.
12. Click the Module Settings tab, choose for Module Name Pterodactyl and for the Server Group the group you created in step 8.
13. Fill all non-optional fields, and you are good to go!

## Credits

[Dane](https://github.com/DaneEveritt) and [everyone else](https://github.com/Pterodactyl/Panel/graphs/contributors) involved in development of the Pterodactyl Panel.
[death-droid](https://github.com/death-droid) for the original WHMCS module.
[Crident](https://crident.com) for providing me a dev environment to test the module on and the installation video.

# FAQ

## Migrating from death-droid's module

Migrating is simple, delete death-droid's module and then upload this one instead of it.
Then do the steps 3-6 in Installation instructions above and resetup all products.

## Overwriting values through configurable options

Overwriting values can be done through either Configurable Options or Custom Fields.

Their name should be exactly what you want to overwrite.
dedicated_ip => Will overwrite dedicated_ip if its ticked or not.
Valid options: ``server_name, memory, swap, io, cpu, disk, nest_id, egg_id, pack_id, location_id, dedicated_ip, port_range, image, startup, databases, allocations, backups, oom_disabled, username``

This also works for any name of environment variable:
Player Slots => Will overwrite the environment variable named "Player Slots" to its value.

Useful trick: You can use the | seperator to change the display name of the variable like this:
dedicated_ip|Dedicated IP => Will be displayed as "Dedicated IP" but will work correctly.

[Sample configuration for configurable memory](https://owo.whats-th.is/85JwhVX.png)

## Couldn't find any nodes satisfying the request

This can be caused from any of the following: Wrong location, not enough disk space/CPU/RAM, or no allocations matching the provided criteria.

## The username/password field is empty, how does the user get credentials?

The customer gets an email from the panel to setup their account (incl. password) if they didn't have an account before. Otherwise they should be able to use their existing credentials.

## The customer didn't receive any emails from the panel

Double check that you've configured the panel's mail settings correctly, the Test button works in the admin area's mail settings, and that you've restarted pteroq afterwards confirming that everything works.

## My game requires multiple ports allocated

Currently, this isn't possible with this module but is planned.

## The server gets assigned to the first/admin user of the panel instead of the user who ordered the service

Please update your module (by redownloading it).

## The feature_limits.backups field must be present

Please update your module (by redownloading it).

## How to enable module debug log

1. In WHMCS 7 or below navigate to Utilities > Logs > Module Log. For WHMCS 8.x navigate to System Logs > Module Log in the left sidebar.
2. Click the Enable Debug Logging button.
3. Do the action that failed again and you will have required logs to debug the issue. All 404 errors can be ignored.
4. Remember to Disable Debug Logging if you are using this in production, as it's not recommended to have it enabled.
