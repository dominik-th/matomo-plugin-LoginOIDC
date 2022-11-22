<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginOIDC;

use Exception;
use Piwik\Piwik;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSetting;
use Piwik\Settings\Setting;
use Piwik\Validators\NotEmpty;
use Piwik\Validators\UrlLike;

class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{

    /**
     * The disable superuser setting.
     *
     * @var bool
     */
    public $disableSuperuser;

    /**
     * The disable password confirmation setting.
     *
     * @var bool
     */
    public $disablePasswordConfirmation;

    /**
     * Whether the login procedure has to be initiated from the Matomo login page
     *
     * @var bool
     */
    public $disableDirectLoginUrl;

    /**
     * Whether to hide standard password login form.
     *
     * @var bool
     */
    public $hidePasswordLogin;

    /**
     * Whether new Matomo accounts should be created for unknown users
     *
     * @var bool
     */
    public $allowSignup;

    /**
     * Bypass 2nd factor when login with OIDC
     *
     * @var bool
     */
    public $bypassTwoFa;

    /**
     * Enable auto linking of accounts
     *
     * @var bool
     */
    public $autoLinking;

    /**
     * The name of the oauth provider, which is also shown on the login screen.
     *
     * @var string
     */
    public $authenticationName;

    /**
     * The url where the external service authenticates the user.
     *
     * @var string
     */
    public $authorizeUrl;

    /**
     * The url where an access token can be retreived (json response expected).
     *
     * @var string
     */
    public $tokenUrl;

    /**
     * The url where the external service provides the users unique id (json response expected).
     *
     * @var string
     */
    public $userinfoUrl;

    /**
     * The url where the OIDC provider will invalidate the users session.
     *
     * @var string
     */
    public $endSessionUrl;

    /**
     * The name of the unique user id field in $userinfoUrl response.
     *
     * @var string
     */
    public $userinfoId;

    /**
     * The client id given by the provider.
     *
     * @var string
     */
    public $clientId;

    /**
     * The client secret given by the provider.
     *
     * @var string
     */
    public $clientSecret;

    /**
     * The oauth scopes.
     *
     * @var string
     */
    public $scope;

    /**
     * The optional redirect uri override.
     *
     * @var string
     */
    public $redirectUriOverride;

    /**
     * The domains which are allowed to create accounts.
     *
     * @var string
     */
    public $allowedSignupDomains;

    /**
     * Initialize the plugin settings.
     *
     * @return void
     */
    protected function init()
    {
        $this->disableSuperuser = $this->createDisableSuperuserSetting();
        $this->disablePasswordConfirmation = $this->createDisablePasswordConfirmationSetting();
        $this->disableDirectLoginUrl = $this->createDisableDirectLoginUrlSetting();
        $this->hidePasswordLogin = $this->createHidePasswordLoginSetting();
        $this->allowSignup = $this->createAllowSignupSetting();
        $this->bypassTwoFa = $this->createBypassTwoFaSetting();
        $this->autoLinking = $this->createAutoLinkingSetting();
        $this->authenticationName = $this->createAuthenticationNameSetting();
        $this->authorizeUrl = $this->createAuthorizeUrlSetting();
        $this->tokenUrl = $this->createTokenUrlSetting();
        $this->userinfoUrl = $this->createUserinfoUrlSetting();
        $this->endSessionUrl = $this->createEndSessionUrlSetting();
        $this->userinfoId = $this->createUserinfoIdSetting();
        $this->clientId = $this->createClientIdSetting();
        $this->clientSecret = $this->createClientSecretSetting();
        $this->scope = $this->createScopeSetting();
        $this->redirectUriOverride = $this->createRedirectUriOverrideSetting();
        $this->allowedSignupDomains = $this->createAllowedSignupDomainsSetting();
    }

    /**
     * Add disable superuser setting.
     *
     * @return SystemSetting
     */
    private function createDisableSuperuserSetting() : SystemSetting
    {
        return $this->makeSetting("disableSuperuser", $default = false, FieldConfig::TYPE_BOOL, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingDisableSuperuser");
            $field->description = Piwik::translate("LoginOIDC_SettingDisableSuperuserHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Add disable password confirmation setting.
     *
     * @return SystemSetting
     */
    private function createDisablePasswordConfirmationSetting() : SystemSetting
    {
        return $this->makeSetting("disablePasswordConfirmation", $default = false, FieldConfig::TYPE_BOOL, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingDisablePasswordConfirmation");
            $field->description = Piwik::translate("LoginOIDC_SettingDisablePasswordConfirmationHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Add disable direct login url setting.
     *
     * @return SystemSetting
     */
    private function createDisableDirectLoginUrlSetting() : SystemSetting
    {
        return $this->makeSetting("disableDirectLoginUrl", $default = true, FieldConfig::TYPE_BOOL, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingDisableDirectLoginUrl");
            $field->description = Piwik::translate("LoginOIDC_SettingDisableDirectLoginUrlHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }
    
    /**
     * Add hide password login setting.
     *
     * @return SystemSetting
     */
    private function createHidePasswordLoginSetting() : SystemSetting
    {
        return $this->makeSetting("hidePasswordLogin", $default = false, FieldConfig::TYPE_BOOL, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingHidePasswordLogin");
            $field->description = Piwik::translate("LoginOIDC_SettingHidePasswordLoginHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Add allowSignup setting.
     *
     * @return SystemSetting
     */
    private function createAllowSignupSetting() : SystemSetting
    {
        return $this->makeSetting("allowSignup", $default = false, FieldConfig::TYPE_BOOL, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingAllowSignup");
            $field->description = Piwik::translate("LoginOIDC_SettingAllowSignupHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Add bypassTwoFa setting.
     *
     * @return SystemSetting
     */
    private function createBypassTwoFaSetting() : SystemSetting
    {
        return $this->makeSetting("bypassTwoFa", $default = false, FieldConfig::TYPE_BOOL, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingBypassTwoFa");
            $field->description = Piwik::translate("LoginOIDC_SettingBypassTwoFaHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Add autoLinking setting.
     *
     * @return SystemSetting
     */
    private function createAutoLinkingSetting() : SystemSetting
    {
        return $this->makeSetting("autoLinking", $default = false, FieldConfig::TYPE_BOOL, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingAutoLinking");
            $field->description = Piwik::translate("LoginOIDC_SettingAutoLinkingHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    /**
     * Add authentication name setting.
     *
     * @return SystemSetting
     */
    private function createAuthenticationNameSetting() : SystemSetting
    {
        return $this->makeSetting("authenticationName", $default = "OAuth login", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingAuthenticationName");
            $field->description = Piwik::translate("LoginOIDC_SettingAuthenticationNameHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });
    }

    /**
     * Add authorization url setting.
     *
     * @return SystemSetting
     */
    private function createAuthorizeUrlSetting() : SystemSetting
    {
        return $this->makeSetting("authorizeUrl", $default = "https://github.com/login/oauth/authorize", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingAuthorizeUrl");
            $field->description = Piwik::translate("LoginOIDC_SettingAuthorizeUrlHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_URL;
            $field->validators[] = new UrlLike();
        });
    }

    /**
     * Add token url setting.
     *
     * @return SystemSetting
     */
    private function createTokenUrlSetting() : SystemSetting
    {
        return $this->makeSetting("tokenUrl", $default = "https://github.com/login/oauth/access_token", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingTokenUrl");
            $field->description = Piwik::translate("LoginOIDC_SettingTokenUrlHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_URL;
            $field->validators[] = new UrlLike();
        });
    }

    /**
     * Add userinfo url setting.
     *
     * @return SystemSetting
     */
    private function createUserinfoUrlSetting() : SystemSetting
    {
        return $this->makeSetting("userinfoUrl", $default = "https://api.github.com/user", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingUserinfoUrl");
            $field->description = Piwik::translate("LoginOIDC_SettingUserinfoUrlHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_URL;
            $field->validators[] = new UrlLike();
        });
    }

    /**
     * Add end session url setting.
     *
     * @return SystemSetting
     */
    private function createEndSessionUrlSetting() : SystemSetting
    {
        return $this->makeSetting("endSessionUrl", $default = "", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingEndSessionUrl");
            $field->description = Piwik::translate("LoginOIDC_SettingEndSessionUrlHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_URL;
        });
    }

    /**
     * Add userinfo id setting.
     *
     * @return SystemSetting
     */
    private function createUserinfoIdSetting() : SystemSetting
    {
        return $this->makeSetting("userinfoId", $default = "id", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingUserinfoId");
            $field->description = Piwik::translate("LoginOIDC_SettingUserinfoIdHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validators[] = new NotEmpty();
        });
    }

    /**
     * Add client id setting.
     *
     * @return SystemSetting
     */
    private function createClientIdSetting() : SystemSetting
    {
        return $this->makeSetting("clientId", $default = "", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingClientId");
            $field->description = Piwik::translate("LoginOIDC_SettingClientIdHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });
    }

    /**
     * Add client secret setting.
     *
     * @return SystemSetting
     */
    private function createClientSecretSetting() : SystemSetting
    {
        return $this->makeSetting("clientSecret", $default = "", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingClientSecret");
            $field->description = Piwik::translate("LoginOIDC_SettingClientSecretHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
        });
    }

    /**
     * Add scope setting.
     *
     * @return SystemSetting
     */
    private function createScopeSetting() : SystemSetting
    {
        return $this->makeSetting("scope", $default = "", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingScope");
            $field->description = Piwik::translate("LoginOIDC_SettingScopeHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });
    }

    /**
     * Add redirect uri override setting.
     *
     * @return SystemSetting
     */
    private function createRedirectUriOverrideSetting() : SystemSetting
    {
        return $this->makeSetting("redirectUriOverride", $default = "", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingRedirectUriOverride");
            $field->description = Piwik::translate("LoginOIDC_SettingRedirectUriOverrideHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_URL;
        });
    }

    /**
     * Add allowed signup domains setting.
     *
     * @return SystemSetting
     */
    private function createAllowedSignupDomainsSetting() : SystemSetting
    {
        return $this->makeSetting("allowedSignupDomains", $default = "", FieldConfig::TYPE_STRING, function(FieldConfig $field) {
            $field->title = Piwik::translate("LoginOIDC_SettingAllowedSignupDomains");
            $field->description = Piwik::translate("LoginOIDC_SettingAllowedSignupDomainsHelp");
            $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
            $field->validate = function ($value, $setting) {
                if (empty($value)) {
                    return;
                }
                $domainPattern = "/^(((?!-))(xn--|_{1,1})?[a-z0-9-]{0,61}[a-z0-9]{1,1}\.)*(xn--)?([a-z0-9][a-z0-9\-]{0,60}|[a-z0-9-]{1,30}\.[a-z]{2,})$/";
                $domains = explode("\n", $value);
                foreach($domains as $domain) {
                    $isValidDomain = preg_match($domainPattern, $domain);
                    if (!$isValidDomain) {
                        throw new Exception(Piwik::translate("LoginOIDC_ExceptionAllowedSignupDomainsValidationFailed"));
                    }
                }
            };
        });
    }
}
