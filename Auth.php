<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\LoginOIDC;

use Piwik\AuthResult;
use Piwik\Plugins\UsersManager\Model;

class Auth extends \Piwik\Plugins\Login\Auth
{
    /**
     * Forces a successful login.
     *
     * @var bool
     */
    protected $forceLogin;

    /**
     * @var Model
     */
    private $userModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new Model();
    }

    /**
     * Authenticates user.
     *
     * @return AuthResult
     */
    public function authenticate()
    {
        if ($this->forceLogin && !empty($this->login)) {
            $user = $this->userModel->getUser($this->login);
            return $this->authenticationSuccess($user);
        }
        return parent::authenticate();
    }

    /**
     * Returns positive AuthResult for a specific user.
     * See: {@link \Piwik\Plugins\Login\Auth::authenticationSuccess()} method.
     *
     * @return AuthResult
     */
    private function authenticationSuccess(array $user)
    {
        if (empty($this->token_auth)) {
            $this->token_auth = $this->userModel->generateRandomTokenAuth();
            // we generated one randomly which will then be stored in the session and used across the session
        }

        $isSuperUser = (int) $user['superuser_access'];
        $code = $isSuperUser ? AuthResult::SUCCESS_SUPERUSER_AUTH_CODE : AuthResult::SUCCESS;

        return new AuthResult($code, $user['login'], $this->token_auth);
    }

    /**
     * Returns if forceful login is enabled.
     *
     * @return bool
     */
    public function getForceLogin()
    {
        return $this->forceLogin;
    }

    /**
     * Sets the forceful login.
     *
     * @param bool $forceLogin true if authentication should succeed.
     */
    public function setForceLogin(bool $forceLogin)
    {
        $this->forceLogin = $forceLogin;
    }
}
