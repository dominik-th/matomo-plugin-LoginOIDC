## FAQ

__What is the callback url?__

http(s)://<YOUR_MATOMO_URL>/index.php?module=LoginOIDC&action=callback&provider=oidc

__Which providers can I use?__

I tested the plugin with Auth0, Github and Keycloak, which work fine.
If your provider does not seem to work, leave an issue on Github.

__How can I unlink all users?__

The easiest way is to fully uninstall the plugin and reinstall afterwards.
Otherwise you can delete data from `matomo_loginoidc_provider` in your sql database.

If you change the OAuth provider and there could be user id collisions, you should make sure to unlink all users beforehand.

__Can I setup more than one provider?__

Currently that is **not** possible.
But you can use services like Auth0, which support multiple providers.

__I get a `Can't create table` error when installing the plugin__

Most likely you are using a very old Piwik installation, which still uses MyISAM tables.
Learn here on how to update the database engine:
https://matomo.org/faq/troubleshooting/faq_25610/

__What are the settings for ...?__

* Github:
  * Authorize URL: `https://github.com/login/oauth/authorize`
  * Token URL: `https://github.com/login/oauth/access_token`
  * Userinfo URL: `https://api.github.com/user`
  * Userinfo ID: `id`
  * OAuth Scopes: `<EMPTY>`

* Auth0:
  * Authorize URL: `https://<USERNAME>.eu.auth0.com/authorize`
  * Token URL: `https://<USERNAME>.eu.auth0.com/oauth/token`
  * Userinfo URL: `https://<USERNAME>.eu.auth0.com/userinfo`
  * Userinfo ID: `sub`
  * OAuth Scopes: `openid`

* Keycloak:
  * Authorize URL: `http(s)://<YOUR_KEYCLOAK_INSTALLATION>/auth/realms/<REALM>/protocol/openid-connect/auth`
  * Token URL: `http(s)://<YOUR_KEYCLOAK_INSTALLATION>/auth/realms/<REALM>/protocol/openid-connect/token`
  * Userinfo URL: `http(s)://<YOUR_KEYCLOAK_INSTALLATION>/auth/realms/<REALM>/protocol/openid-connect/userinfo`
  * Userinfo ID: `sub`
  * OAuth Scopes: `openid`

* Microsoft Azure AD
  * Authorize URL: `https://login.microsoftonline.com/{tenant_id}/oauth2/authorize`
  * Token URL: `https://login.microsoftonline.com/{tenant_id}/oauth2/token`
  * Userinfo URL: `https://login.microsoftonline.com/{tenant_id}/openid/userinfo`
  * Userinfo ID: `sub`
  * OAuth Scopes: `openid`
  * Redirect URI Override*: `http(s)://<YOUR_MATOMO_INSTALLATION>/oidc/callback`


\*because Microsoft Azure AD does not allow query parameters in the redirect URI we also have to edit our nginx configuration to work around this limitation:

```nginx
server {
    # ...
    rewrite ^/oidc/callback /index.php?module=LoginOIDC&action=callback&provider=oidc redirect;
    # ...
}
```