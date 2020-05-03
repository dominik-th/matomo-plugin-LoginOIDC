## FAQ

**What is the callback url?**

http(s)://<YOUR_MATOMO_URL>/index.php?module=LoginOIDC&action=callback&provider=oidc

**Which providers can I use?**

I tested the plugin with Auth0, Github and Keycloak, which work fine.
If your provider does not seem to work, leave an issue on Github.

**How can I unlink all users?**

The easiest way is to fully uninstall the plugin and reinstall afterwards.
Otherwise you can delete data from `matomo_loginoidc_provider` in your sql database.

If you change the OAuth provider and there could be user id collisions, you should make sure to unlink all users beforehand.

**Can I setup more than one provider?**

Currently that is **not** possible.
But you can use services like Auth0, which support multiple providers.

**I get a `Can't create table` error when installing the plugin**

Most likely you are using a very old Piwik installation, which still uses MyISAM tables.
Learn here on how to update the database engine:
https://matomo.org/faq/troubleshooting/faq_25610/

**What are the settings for ...?**

- Github:

  - Authorize URL: `https://github.com/login/oauth/authorize`
  - Token URL: `https://github.com/login/oauth/access_token`
  - Userinfo URL: `https://api.github.com/user`
  - Userinfo ID: `id`
  - OAuth Scopes: `<EMPTY>`

- Auth0:

  - Authorize URL: `https://<USERNAME>.eu.auth0.com/authorize`
  - Token URL: `https://<USERNAME>.eu.auth0.com/oauth/token`
  - Userinfo URL: `https://<USERNAME>.eu.auth0.com/userinfo`
  - Userinfo ID: `sub`
  - OAuth Scopes: `openid`

- Keycloak:

  - Authorize URL: `http(s)://<YOUR_KEYCLOAK_INSTALLATION>/auth/realms/<REALM>/protocol/openid-connect/auth`
  - Token URL: `http(s)://<YOUR_KEYCLOAK_INSTALLATION>/auth/realms/<REALM>/protocol/openid-connect/token`
  - Userinfo URL: `http(s)://<YOUR_KEYCLOAK_INSTALLATION>/auth/realms/<REALM>/protocol/openid-connect/userinfo`
  - Userinfo ID: `sub`
  - OAuth Scopes: `openid`

- Gitlab (self-hosted Community Edition 12.6.2)

  - Authorize URL: `http(s)://<YOUR_GIT_DOMAIN>/oauth/authorize`
  - Token URL: `http(s)://<YOUR_GIT_DOMAIN>/oauth/token`
  - Userinfo URL: `http(s)://<YOUR_GIT_DOMAIN>/oauth/userinfo`
  - Userinfo ID: `sub`
  - OAuth Scopes: `openid email`

- Microsoft Azure AD
  - Authorize URL: `https://login.microsoftonline.com/{tenant_id}/oauth2/authorize`
  - Token URL: `https://login.microsoftonline.com/{tenant_id}/oauth2/token`
  - Userinfo URL: `https://login.microsoftonline.com/{tenant_id}/openid/userinfo`
  - Userinfo ID: `sub`
  - OAuth Scopes: `openid`
  - Redirect URI Override\*: `http(s)://<YOUR_MATOMO_INSTALLATION>/oidc/callback`

\*because Microsoft Azure AD does not allow query parameters in the redirect URI we also have to edit our nginx configuration to work around this limitation:

```nginx
server {
    # ...
    rewrite ^/oidc/callback /index.php?module=LoginOIDC&action=callback&provider=oidc redirect;
    # ...
}
```
