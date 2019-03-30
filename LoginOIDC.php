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

  public function registerEvents()
  {
    return array(
      'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
      'Template.userSettings.afterTokenAuth' => 'renderLoginOIDCUserSettings',
      'Template.loginNav' => 'renderLoginOIDCMod'
    );
  }

  public function getStylesheetFiles(&$files)
  {
    $files[] = "plugins/LoginOIDC/stylesheets/loginMod.css";
  }

  public function install()
  {
    try {
      // right now there is just one provider but we already add a column to support multiple providers later on
      $sql = "CREATE TABLE " . Common::prefixTable('loginoidc_provider') . " (
                user VARCHAR( 100 ) NOT NULL,
                provider_user VARCHAR( 255 ) NOT NULL,
                provider VARCHAR( 255 ) NOT NULL,
                date_connected TIMESTAMP NOT NULL,
                PRIMARY KEY ( provider_user, provider ),
                FOREIGN KEY ( user ) REFERENCES " . Common::prefixTable('user') . "( login ) ON DELETE CASCADE,
                CONSTRAINT user_provider UNIQUE ( user, provider )
              ) DEFAULT CHARSET=utf8";
      Db::exec($sql);
    } catch(Exception $e) {
      // ignore error if table already exists (1050 code is for 'table already exists')
      if (!Db::get()->isErrNo($e, '1050')) {
        throw $e;
      }
    }
  }

  public function uninstall()
  {
    Db::dropTables(Common::prefixTable('loginoidc_provider'));
  }

  public function renderLoginOIDCUserSettings(&$out)
  {
    $content = FrontController::getInstance()->dispatch('LoginOIDC', 'userSettings');
    if (!empty($content)) {
      $out .= $content;
    }
  }

  public function renderLoginOIDCMod(&$out, $payload)
  {
    if ($payload === 'bottom') {
      $content = FrontController::getInstance()->dispatch('LoginOIDC', 'loginMod');
      if (!empty($content)) {
        $out .= $content;
      }
    }
  }

}
