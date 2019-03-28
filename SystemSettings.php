<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginOIDC;

use Piwik\Piwik;
use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\Validators\UrlLike;

/**
 * Defines settings for LoginOIDC plugin.
 */
class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{

  /** @var Setting **/
  public $authenticationName;

  /** @var Setting **/
  public $authorizeUrl;

  /** @var Setting **/
  public $tokenUrl;

  /** @var Setting **/
  public $userinfoUrl;

  /** @var Setting **/
  public $clientId;

  /** @var Setting **/
  public $clientSecret;

  /** @var Setting **/
  public $scope;

  protected function init()
  {
    // System setting --> allows selection of a single value
    $this->authenticationName = $this->createAuthenticationNameSetting();
    $this->authorizeUrl = $this->createAuthorizeUrlSetting();
    $this->tokenUrl = $this->createTokenUrlSetting();
    $this->userinfoUrl = $this->createUserinfoUrlSetting();
    $this->clientId = $this->createClientIdSetting();
    $this->clientSecret = $this->createClientSecretSetting();
    $this->scope = $this->createScopeSetting();
  }

  private function createAuthenticationNameSetting()
  {
    return $this->makeSetting('authenticationName', $default = '', FieldConfig::TYPE_STRING, function(FieldConfig $field) {
      $field->title = Piwik::translate('LoginOIDC_SettingAuthenticationName');
      $field->description = Piwik::translate('LoginOIDC_SettingAuthenticationNameHelp');
      $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
    });
  }

  private function createAuthorizeUrlSetting()
  {
    return $this->makeSetting('authorizeUrl', $default = '', FieldConfig::TYPE_STRING, function(FieldConfig $field) {
      $field->title = Piwik::translate('LoginOIDC_SettingAuthorizeUrl');
      $field->description = Piwik::translate('LoginOIDC_SettingAuthorizeUrlHelp');
      $field->uiControl = FieldConfig::UI_CONTROL_URL;
      $field->validators[] = new UrlLike();
    });
  }

  private function createTokenUrlSetting()
  {
    return $this->makeSetting('tokenUrl', $default = '', FieldConfig::TYPE_STRING, function(FieldConfig $field) {
      $field->title = Piwik::translate('LoginOIDC_SettingTokenUrl');
      $field->description = Piwik::translate('LoginOIDC_SettingTokenUrlHelp');
      $field->uiControl = FieldConfig::UI_CONTROL_URL;
      $field->validators[] = new UrlLike();
    });
  }

  private function createUserinfoUrlSetting()
  {
    return $this->makeSetting('userinfoUrl', $default = '', FieldConfig::TYPE_STRING, function(FieldConfig $field) {
      $field->title = Piwik::translate('LoginOIDC_SettingUserinfoUrl');
      $field->description = Piwik::translate('LoginOIDC_SettingUserinfoUrlHelp');
      $field->uiControl = FieldConfig::UI_CONTROL_URL;
      $field->validators[] = new UrlLike();
    });
  }

  private function createClientIdSetting()
  {
    return $this->makeSetting('clientId', $default = '', FieldConfig::TYPE_STRING, function(FieldConfig $field) {
      $field->title = Piwik::translate('LoginOIDC_SettingClientId');
      $field->description = Piwik::translate('LoginOIDC_SettingClientIdHelp');
      $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
    });
  }

  private function createClientSecretSetting()
  {
    return $this->makeSetting('clientSecret', $default = '', FieldConfig::TYPE_STRING, function(FieldConfig $field) {
      $field->title = Piwik::translate('LoginOIDC_SettingClientSecret');
      $field->description = Piwik::translate('LoginOIDC_SettingClientSecretHelp');
      $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
    });
  }

  private function createScopeSetting()
  {
    return $this->makeSetting('scope', $default = '', FieldConfig::TYPE_STRING, function(FieldConfig $field) {
      $field->title = Piwik::translate('LoginOIDC_SettingScope');
      $field->description = Piwik::translate('LoginOIDC_SettingScopeHelp');
      $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
    });
  }

}
