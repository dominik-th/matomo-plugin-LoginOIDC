<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginOIDC;

use Piwik\Db;
use Piwik\Common;
use \Exception;

class LoginOIDC extends \Piwik\Plugin
{

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
                FOREIGN KEY ( user ) REFERENCES " . Common::prefixTable('user') . "( login ) ON DELETE CASCADE
              ) DEFAULT CHARSET=utf8 ";
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

}
