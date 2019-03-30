## FAQ

__What is the callback url?__

http(s)://YOUR_MATOMO_URL/index.php?module=LoginOIDC&action=callback&provider=oidc

__Which providers can I use?__

I tested the plugin with Auth0 and Github, which both work fine.
If your provider does not seem to work, leave an issue on Github.

__How can I unlink all users?__

The easiest way is to fully uninstall the plugin and reinstall afterwards.
Otherwise you can delete data from `matomo_loginoidc_provider` in your sql database.

If you change the OAuth provider and there could be user id collisions, you should make sure to unlink all users beforehand.

__Can I setup more than 1 provider?__

Currently that is **not** possible.
But you can use services like Auth0, which support multiple providers.
