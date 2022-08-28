## Changelog

### 4.1.2
* Fix disabling OIDC for superusers not having any effect (#68).

### 4.1.1
* Hotfix warning about session variable not being set when signing in with username/password.

### 4.1.0
* Add option to skip password confirmation requests when user has signed in via LoginOIDC (requires Matomo >4.12.0) (#72).
* Add option to automatically link existing users when IdP user id matches Matomos user id (#44).
* Fix logout redirect (#64).
* Improve db table creation (#31).

### 4.0.0
* Prepare plugin for Matomo 4.
* Linking accounts has been moved to the users security settings.

### 3.0.1
* Hotfix saving plugin system settings with empty domain whitelist (#34).

### 3.0.0
* Align version number with Matomo major release version.
* Support embedding login button on third-party sites.
* Restrict account creation to specified domains.
* Support [OIDC Logout URLs](https://openid.net/specs/openid-connect-session-1_0-17.html#RPLogout).
* Support Matomos regular password verification (currently requires modification of plugins/Login/templates/confirmPassword.twig)

### 0.1.5
* Add option to bypass second factor when sign in with OIDC.

### 0.1.4
* Add option to automatically create unknown users.

### 0.1.3
* Add an option to override the redirect URI.

### 0.1.2
* Fix oauth flow for [Keycloak](https://github.com/keycloak/keycloak).
* Improve FAQ.

### 0.1.1
* Lowered the required Matomo version for this plugin.

### 0.1.0
* Initial version.
