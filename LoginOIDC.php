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
use Piwik\Db;
use Piwik\FrontController;

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
            "AssetManager.getStylesheetFiles" => "getStylesheetFiles",
            "Template.userSettings.afterTokenAuth" => "renderLoginOIDCUserSettings",
            "Template.loginNav" => "renderLoginOIDCMod"
        );
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
     * Extend database.
     *
     * @return void
     */
    public function install()
    {
        try {
            // right now there is just one provider but we already add a column to support multiple providers later on
            $sql = "CREATE TABLE " . Common::prefixTable("loginoidc_provider") . " (
                user VARCHAR( 100 ) NOT NULL,
                provider_user VARCHAR( 255 ) NOT NULL,
                provider VARCHAR( 255 ) NOT NULL,
                date_connected TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY ( provider_user, provider ),
                UNIQUE KEY user_provider ( user, provider ),
                FOREIGN KEY ( user ) REFERENCES " . Common::prefixTable("user") . " ( login ) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
            Db::exec($sql);
        } catch(Exception $e) {
            // ignore error if table already exists (1050 code is for 'table already exists')
            if (!Db::get()->isErrNo($e, "1050")) {
                throw $e;
            }
        }
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
