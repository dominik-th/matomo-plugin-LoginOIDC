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
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Session\SessionInitializer;
use Piwik\Url;
use Piwik\View;

class Controller extends \Piwik\Plugin\Controller
{

  /**
   * @var Auth
   */
  protected $auth;

  /**
   * @var SessionInitializer
   */
  protected $sessionInitializer;

  /**
   * Constructor.
   *
   * @param AuthInterface $auth
   * @param SessionInitializer $sessionInitializer
   */
  public function __construct($auth = null, $sessionInitializer = null)
  {
    parent::__construct();

    if (empty($auth)) {
      $auth = StaticContainer::get('Piwik\Auth');
    }
    $this->auth = $auth;

    if (empty($sessionInitializer)) {
      $sessionInitializer = new SessionInitializer();
    }
    $this->sessionInitializer = $sessionInitializer;
  }

  public function userSettings()
  {
    return $this->renderTemplate('userSettings', array(
      'isLinked' => $this->isLinked('oidc')
    ));
  }

  public function unlink()
  {
    $sql = "DELETE FROM " . Common::prefixTable('loginoidc_provider') . " WHERE user=? AND provider=?";
    $bind = array(Piwik::getCurrentUserLogin(), 'oidc');
    Db::query($sql, $bind);
    $this->redirectToIndex('UsersManager', 'userSettings');
  }

  public function signin()
  {
    $settings = new \Piwik\Plugins\LoginOIDC\SystemSettings();
    if (!$this->isPluginSetup($settings)) {
      throw new Exception(Piwik::translate('LoginOIDC_LoginOIDC_ExceptionNotConfigured'));
    }
    $params = array(
      'module' => 'LoginOIDC',
      'action' => 'callback',
      'provider' => 'oidc'
    );
    $redirectUrl = Url::getCurrentUrlWithoutQueryString() . '?' . http_build_query($params);
    $_SESSION["loginoidc_state"] = $this->generateKey(32);
    $params = array(
      'client_id' => $settings->clientId->getValue(),
      'scope' => $settings->scope->getValue(),
      'redirect_uri'=> $redirectUrl,
      'state' => $_SESSION["loginoidc_state"]
    );
    $url = $settings->authorizeUrl->getValue();
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query($params);
    Url::redirectToUrl($url);
  }

  public function callback()
  {
    $settings = new \Piwik\Plugins\LoginOIDC\SystemSettings();
    if (!$this->isPluginSetup($settings)) {
      throw new Exception(Piwik::translate('LoginOIDC_ExceptionNotConfigured'));
    }

    if ($_SESSION["loginoidc_state"] !== Common::getRequestVar('state')) {
      throw new Exception(Piwik::translate('LoginOIDC_ExceptionStateMismatch'));
    } else {
      unset($_SESSION['loginoidc_state']);
    }

    if (Common::getRequestVar('provider') !== 'oidc') {
      throw new Exception(Piwik::translate('LoginOIDC_ExceptionUnknownProvider'));
    }

    $data = array(
      'client_id' => $settings->clientId->getValue(),
      'client_secret' => $settings->clientSecret->getValue(),
      'code' => Common::getRequestVar('code'),
      'redirect_uri' => $this->getRedirectUri(),
      'grant_type' => 'authorization_code',
      'state' => Common::getRequestVar('state')
    );
    $dataString = json_encode($data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($dataString),
      'Accept: application/json'
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $settings->tokenUrl->getValue());
    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response);

    if (empty($result) || empty($result->access_token)) {
      throw new Exception(Piwik::translate('LoginOIDC_ExceptionInvalidResponse'));
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer ' . $result->access_token,
      'Accept: application/json'
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_URL, $settings->userinfoUrl->getValue());
    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response);

    $providerUserId = $result->sub;

    if (empty($providerUserId)) {
      throw new Exception(Piwik::translate('LoginOIDC_ExceptionInvalidResponse'));
    }

    $user = $this->getUserByRemoteId('oidc', $providerUserId);

    if (empty($user)) {
      // user with the remote id is currently not in our database
      if (Piwik::isUserIsAnonymous()) {
        throw new Exception(Piwik::translate('LoginOIDC_ExceptionUserNotFound'));
      } else {
        // link current user with the remote user
        $this->linkAccount($providerUserId);
        $this->redirectToIndex('UsersManager', 'userSettings');
      }
    } else {
      // users identity has been successfully confirmed by the remote oidc server
      if (Piwik::isUserIsAnonymous()) {
        if ($settings->disableSuperuser->getValue() && Piwik::hasTheUserSuperUserAccess($user["login"])) {
          throw new Exception(Piwik::translate('LoginOIDC_ExceptionSuperUserOauthDisabled'));
        } else {
          $this->signinAndRedirect($user);
        }
      } else {
        Url::redirectToUrl('index.php');
      }
    }
  }

  private function linkAccount($providerUserId)
  {
    $sql = "INSERT INTO " . Common::prefixTable('loginoidc_provider') . " (`user`, `provider_user`, `provider`, `date_connected`) VALUES (?, ?, ?, ?)";
    $bind = array(Piwik::getCurrentUserLogin(), $providerUserId, 'oidc', date("Y-m-d H:i:s"));
    Db::query($sql, $bind);
  }

  private function isPluginSetup($settings)
  {
    return !empty($settings->authorizeUrl->getValue())
      && !empty($settings->tokenUrl->getValue())
      && !empty($settings->userinfoUrl->getValue())
      && !empty($settings->clientId->getValue())
      && !empty($settings->clientSecret->getValue());
  }

  private function signinAndRedirect($user)
  {
    $this->auth->setLogin($user["login"]);
    $this->auth->setTokenAuth($user["token_auth"]);
    $this->sessionInitializer->initSession($this->auth);
    Url::redirectToUrl('index.php');
  }

  private function generateKey(int $length = 64)
  {
    // thanks ccbsschucko at gmail dot com
    // http://docs.php.net/manual/pl/function.random-bytes.php#122766
    $length = ($length < 4) ? 4 : $length;
    return bin2hex(random_bytes(($length - ($length % 2)) / 2));
  }

  private function getRedirectUri()
  {
    $params = array(
      'module' => 'LoginOIDC',
      'action' => 'callback',
      'provider' => 'oidc'
    );
    return Url::getCurrentUrlWithoutQueryString() . '?' . http_build_query($params);
  }

  private function getUserByRemoteId($provider, $remoteId)
  {
    $sql = "SELECT user FROM " . Common::prefixTable('loginoidc_provider') . " WHERE provider=? AND provider_user=?";
    $result = Db::fetchRow($sql, array($provider, $remoteId));
    if (empty($result)) {
      return false;
    } else {
      $userModel = new Model();
      return $userModel->getUser($result["user"]);
    }
  }

  private function isLinked($provider)
  {
    $sql = "SELECT user FROM " . Common::prefixTable('loginoidc_provider') . " WHERE provider=? AND user=?";
    $result = Db::fetchRow($sql, array($provider, Piwik::getCurrentUserLogin()));
    if (empty($result)) {
      return false;
    } else {
      return true;
    }
  }

}
