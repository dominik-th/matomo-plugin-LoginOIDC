<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginOIDC;

use Exception;
use Piwik\Common;
use Piwik\Config;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\FrontController;
use Piwik\Plugins\LoginOIDC\SystemSettings;
use Piwik\Plugins\LoginOIDC\Url;
use Piwik\Session;

class LoginOIDC extends \Piwik\Plugin
{
    /**
     * Subscribe to Matomo events and assign handlers.
     * https://developer.matomo.org/api-reference/Piwik/Plugin#registerevents
     *
     * @return array
     */
    public function registerEvents() : array
    {
        return array(
            "Session.beforeSessionStart" => "beforeSessionStart",
            "AssetManager.getStylesheetFiles" => "getStylesheetFiles",
            "Template.userSecurity.afterPassword" => "renderLoginOIDCUserSettings",
            "Template.loginNav" => "renderLoginOIDCMod",
            "Template.confirmPasswordContent" => "renderConfirmPasswordMod",
            "Login.logout" => "logoutMod"
        );
    }

    /**
     * Create RememberMe cookie.
     * @see \Piwik\Plugins\Login::beforeSessionStart
     *
     * @return void
     */
    public function beforeSessionStart() : void
    {
        if (!$this->shouldHandleRememberMe()) {
            return;
        }
        Session::rememberMe(Config::getInstance()->General["login_cookie_expire"]);
    }

    /**
     * Decide if RememberMe cookie should be handled by the plugin.
     * @see \Piwik\Plugins\Login::shouldHandleRememberMe
     *
     * @return bool
     */
    private function shouldHandleRememberMe() : bool
    {
        $module = Common::getRequestVar("module", false);
        $action = Common::getRequestVar("action", false);
        return ($module == "LoginOIDC") && ($action == "callback");
    }

    /**
     * Append additional stylesheets.
     *
     * @param  array  $files
     * @return void
     */
    public function getStylesheetFiles(array &$files)
    {
        $files[] = "plugins/LoginOIDC/stylesheets/loginMod.css";
    }

    /**
     * Register the new tables, so Matomo knows about them.
     *
     * @param array $allTablesInstalled
     */
    public function getTablesInstalled(&$allTablesInstalled)
    {
        $allTablesInstalled[] = Common::prefixTable('loginoidc_provider');
    }

    /**
     * Append custom user settings layout.
     *
     * @param  string  $out
     * @return void
     */
    public function renderLoginOIDCUserSettings(string &$out)
    {
        $content = FrontController::getInstance()->dispatch("LoginOIDC", "userSettings");
        if (!empty($content)) {
            $out .= $content;
        }
    }

    /**
     * Append login oauth button layout.
     *
     * @param  string       $out
     * @param  string|null  $payload
     * @return void
     */
    public function renderLoginOIDCMod(string &$out, string $payload = null)
    {
        if (!empty($payload) && $payload === "bottom") {
            $content = FrontController::getInstance()->dispatch("LoginOIDC", "loginMod");
            if (!empty($content)) {
                $out .= $content;
            }
        }
    }

    /**
     * Append login oauth button layout.
     *
     * @param  string       $out
     * @param  string|null  $payload
     * @return void
     */
    public function renderConfirmPasswordMod(string &$out, string $payload = null)
    {
        if (!empty($payload) && $payload === "bottom") {
            $content = FrontController::getInstance()->dispatch("LoginOIDC", "confirmPasswordMod");
            if (!empty($content)) {
                $out .= $content;
            }
        }
    }

    /**
     * Temporarily override logout url to the oidc provider end user session endpoint.
     *
     * @return void
     */
    public function logoutMod()
    {
        $settings = new SystemSettings();
        $endSessionUrl = $settings->endSessionUrl->getValue();
        if (!empty($endSessionUrl) && $_SESSION["loginoidc_auth"]) {
            $endSessionUrl = new Url($endSessionUrl);
            if (isset($_SESSION["loginoidc_idtoken"])) {
                $endSessionUrl->setQueryParameter("id_token_hint", $_SESSION["loginoidc_idtoken"]);
            }
            $originalLogoutUrl = Config::getInstance()->General['login_logout_url'];
            if ($originalLogoutUrl) {
                $endSessionUrl->setQueryParameter("post_logout_redirect_uri", $originalLogoutUrl);
            }
            Config::getInstance()->General['login_logout_url'] = $endSessionUrl->buildString();
        }
    }

    /**
     * Extend database.
     *
     * @return void
     */
    public function install()
    {
        // right now there is just one provider but we already add a column to support multiple providers later on
        DbHelper::createTable("loginoidc_provider", "
            `user` VARCHAR( 100 ) NOT NULL,
            `provider_user` VARCHAR( 255 ) NOT NULL,
            `provider` VARCHAR( 255 ) NOT NULL,
            `date_connected` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY ( `provider_user`, `provider` ),
            UNIQUE KEY `user_provider` ( `user`, `provider` ),
            FOREIGN KEY ( `user` ) REFERENCES " . Common::prefixTable("user") . " ( `login` ) ON DELETE CASCADE");
    }

    /**
     * Undo database changes from install.
     *
     * @return void
     */
    public function uninstall()
    {
        Db::dropTables(Common::prefixTable("loginoidc_provider"));
    }
}
