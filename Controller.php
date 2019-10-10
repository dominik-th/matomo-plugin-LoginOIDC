<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginOIDC;

use Exception;
use Piwik\Access;
use Piwik\Auth;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Session\SessionInitializer;
use Piwik\Url;
use Piwik\View;

class Controller extends \Piwik\Plugin\Controller
{
    /**
     * Name of the none used in forms by this plugin.
     *
     * @var string
     */
    const OIDC_NONCE = "LoginOIDC.nonce";

    /**
     * Auth implementation to login users.
     * https://developer.matomo.org/api-reference/Piwik/Auth
     *
     * @var Auth
     */
    protected $auth;

    /**
     * Initializes authenticated sessions.
     *
     * @var SessionInitializer
     */
    protected $sessionInitializer;

    /**
     * Constructor.
     *
     * @param Auth                $auth
     * @param SessionInitializer  $sessionInitializer
     */
    public function __construct(Auth $auth = null, SessionInitializer $sessionInitializer = null)
    {
        parent::__construct();

        if (empty($auth)) {
            $auth = StaticContainer::get("Piwik\Auth");
        }
        $this->auth = $auth;

        if (empty($sessionInitializer)) {
            $sessionInitializer = new SessionInitializer();
        }
        $this->sessionInitializer = $sessionInitializer;
    }

    /**
     * Render the custom user settings layout.
     *
     * @return string
     */
    public function userSettings() : string
    {
        $providerUser = $this->getProviderUser("oidc");
        return $this->renderTemplate("userSettings", array(
            "isLinked" => !empty($providerUser),
            "remoteUserId" => $providerUser["provider_user"],
            "nonce" => Nonce::getNonce(self::OIDC_NONCE)
        ));
    }

    /**
     * Render the oauth login button.
     *
     * @return string
     */
    public function loginMod() : string
    {
        $settings = new \Piwik\Plugins\LoginOIDC\SystemSettings();
        return $this->renderTemplate("loginMod", array(
            "caption" => $settings->authenticationName->getValue(),
            "nonce" => Nonce::getNonce(self::OIDC_NONCE)
        ));
    }

    /**
     * Remove link between the currently signed user and the remote user.
     *
     * @return void
     */
    public function unlink()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new Exception(Piwik::translate("LoginOIDC_MethodNotAllowed"));
        }
        // csrf protection
        Nonce::checkNonce(self::OIDC_NONCE, $_POST["form_nonce"]);

        $sql = "DELETE FROM " . Common::prefixTable("loginoidc_provider") . " WHERE user=? AND provider=?";
        $bind = array(Piwik::getCurrentUserLogin(), "oidc");
        Db::query($sql, $bind);
        $this->redirectToIndex("UsersManager", "userSettings");
    }

    /**
     * Redirect to the authorize url of the remote oauth service.
     *
     * @return void
     */
    public function signin()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new Exception(Piwik::translate("LoginOIDC_MethodNotAllowed"));
        }
        // csrf protection
        Nonce::checkNonce(self::OIDC_NONCE, $_POST["form_nonce"]);

        $settings = new \Piwik\Plugins\LoginOIDC\SystemSettings();
        if (!$this->isPluginSetup($settings)) {
            throw new Exception(Piwik::translate("LoginOIDC_ExceptionNotConfigured"));
        }

        $_SESSION["loginoidc_state"] = $this->generateKey(32);
        $params = array(
            "client_id" => $settings->clientId->getValue(),
            "scope" => $settings->scope->getValue(),
            "redirect_uri"=> $this->getRedirectUri(),
            "state" => $_SESSION["loginoidc_state"],
            "response_type" => "code"
        );
        $url = $settings->authorizeUrl->getValue();
        $url .= (parse_url($url, PHP_URL_QUERY) ? "&" : "?") . http_build_query($params);
        Url::redirectToUrl($url);
    }

    /**
     * Handle callback from oauth service.
     * Verify callback code, exchange for authorization token and fetch userinfo.
     *
     * @return void
     */
    public function callback()
    {
        $settings = new \Piwik\Plugins\LoginOIDC\SystemSettings();
        if (!$this->isPluginSetup($settings)) {
            throw new Exception(Piwik::translate("LoginOIDC_ExceptionNotConfigured"));
        }

        if ($_SESSION["loginoidc_state"] !== Common::getRequestVar("state")) {
            throw new Exception(Piwik::translate("LoginOIDC_ExceptionStateMismatch"));
        } else {
            unset($_SESSION["loginoidc_state"]);
        }

        if (Common::getRequestVar("provider") !== "oidc") {
            throw new Exception(Piwik::translate("LoginOIDC_ExceptionUnknownProvider"));
        }

        // payload for token request
        $data = array(
            "client_id" => $settings->clientId->getValue(),
            "client_secret" => $settings->clientSecret->getValue(),
            "code" => Common::getRequestVar("code"),
            "redirect_uri" => $this->getRedirectUri(),
            "grant_type" => "authorization_code",
            "state" => Common::getRequestVar("state")
        );
        $dataString = http_build_query($data);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($dataString),
            "Accept: application/json",
            "User-Agent: LoginOIDC-Matomo-Plugin"
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $settings->tokenUrl->getValue());
        // request authorization token
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);

        if (empty($result) || empty($result->access_token)) {
            throw new Exception(Piwik::translate("LoginOIDC_ExceptionInvalidResponse"));
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $result->access_token,
            "Accept: application/json",
            "User-Agent: LoginOIDC-Matomo-Plugin"
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $settings->userinfoUrl->getValue());
        // request remote userinfo and remote user id
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);

        $userinfoId = $settings->userinfoId->getValue();
        $providerUserId = $result->$userinfoId;

        if (empty($providerUserId)) {
            throw new Exception(Piwik::translate("LoginOIDC_ExceptionInvalidResponse"));
        }

        $user = $this->getUserByRemoteId("oidc", $providerUserId);

        if (empty($user)) {
            // user with the remote id is currently not in our database
            if (Piwik::isUserIsAnonymous()) {
                if ($settings->allowSignup->getValue()) {
                    if (empty($result->email)) {
                        throw new Exception(Piwik::translate("LoginOIDC_ExceptionUserNotFoundAndNoEmail"));
                    }

                    $matomoUserLogin = $result->email;
                    // Set an invalid pre-hashed password, to block the user from logging in by password
                    Access::getInstance()->doAsSuperUser(function () use ($matomoUserLogin, $result) {
                        UsersManagerApi::getInstance()->addUser($matomoUserLogin,
                                                                "(disallow password login)",
                                                                $result->email,
                                                                /* $alias = */ false,
                                                                /* $_isPasswordHashed = */ true);
                    });
                    $userModel = new Model();
                    $user = $userModel->getUser($matomoUserLogin);
                    $this->linkAccount($providerUserId, $matomoUserLogin);
                    $this->signinAndRedirect($user);
                } else {
                    throw new Exception(Piwik::translate("LoginOIDC_ExceptionUserNotFoundAndSignupDisabled"));
                }
            } else {
                // link current user with the remote user
                $this->linkAccount($providerUserId);
                $this->redirectToIndex("UsersManager", "userSettings");
            }
        } else {
            // users identity has been successfully confirmed by the remote oidc server
            if (Piwik::isUserIsAnonymous()) {
                if ($settings->disableSuperuser->getValue() && Piwik::hasTheUserSuperUserAccess($user["login"])) {
                    throw new Exception(Piwik::translate("LoginOIDC_ExceptionSuperUserOauthDisabled"));
                } else {
                    $this->signinAndRedirect($user);
                }
            } else {
                Url::redirectToUrl("index.php");
            }
        }
    }

    /**
     * Create a link between the remote user and the currently signed in user.
     *
     * @param  string  $providerUserId
     * @param  string  $matomoUserLogin Override the local user if non-null
     * @return void
     */
    private function linkAccount(string $providerUserId, string $matomoUserLogin = null)
    {
        if ($matomoUserLogin === null) {
            $matomoUserLogin = Piwik::getCurrentUserLogin();
        }
        $sql = "INSERT INTO " . Common::prefixTable("loginoidc_provider") . " (user, provider_user, provider, date_connected) VALUES (?, ?, ?, ?)";
        $bind = array($matomoUserLogin, $providerUserId, "oidc", date("Y-m-d H:i:s"));
        Db::query($sql, $bind);
    }

    /**
     * Determine if all the required settings have been setup.
     *
     * @param  SystemSettings  $settings
     * @return bool
     */
    private function isPluginSetup($settings) : bool
    {
        return !empty($settings->authorizeUrl->getValue())
            && !empty($settings->tokenUrl->getValue())
            && !empty($settings->userinfoUrl->getValue())
            && !empty($settings->clientId->getValue())
            && !empty($settings->clientSecret->getValue());
    }

    /**
     * Sign in the given user and redirect to the front page.
     *
     * @param  array  $user
     * @return void
     */
    private function signinAndRedirect(array $user)
    {
        $this->auth->setLogin($user["login"]);
        $this->auth->setTokenAuth($user["token_auth"]);
        $this->sessionInitializer->initSession($this->auth);
        Url::redirectToUrl("index.php");
    }

    /**
     * Generate cryptographically secure random string.
     *
     * @param  int    $length
     * @return string
     */
    private function generateKey(int $length = 64) : string
    {
        // thanks ccbsschucko at gmail dot com
        // http://docs.php.net/manual/pl/function.random-bytes.php#122766
        $length = ($length < 4) ? 4 : $length;
        return bin2hex(random_bytes(($length - ($length % 2)) / 2));
    }

    /**
     * Generate the redirect url on which the oauth service has to redirect.
     *
     * @return string
     */
    private function getRedirectUri() : string
    {
        $settings = new \Piwik\Plugins\LoginOIDC\SystemSettings();

        if (!empty($settings->redirectUriOverride->getValue())) {
            return $settings->redirectUriOverride->getValue();
        } else {
            $params = array(
                "module" => "LoginOIDC",
                "action" => "callback",
                "provider" => "oidc"
            );
            return Url::getCurrentUrlWithoutQueryString() . "?" . http_build_query($params);
        }
    }

    /**
     * Fetch user from database given the provider and remote user id.
     *
     * @param  string  $provider
     * @param  string  $remoteId
     * @return array
     */
    private function getUserByRemoteId($provider, $remoteId)
    {
        $sql = "SELECT user FROM " . Common::prefixTable("loginoidc_provider") . " WHERE provider=? AND provider_user=?";
        $result = Db::fetchRow($sql, array($provider, $remoteId));
        if (empty($result)) {
            return $result;
        } else {
            $userModel = new Model();
            return $userModel->getUser($result["user"]);
        }
    }

    /**
     * Fetch provider information for the currently signed in user.
     *
     * @param  string  $provider
     * @return array
     */
    private function getProviderUser($provider)
    {
        $sql = "SELECT user, provider_user, provider FROM " . Common::prefixTable("loginoidc_provider") . " WHERE provider=? AND user=?";
        return Db::fetchRow($sql, array($provider, Piwik::getCurrentUserLogin()));
    }

}
