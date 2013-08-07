<?php
/**
 * aDropboxGallery
 *
 * @author Alvaro Véliz
 */

require_once "Dropbox/autoload.php";

use \Dropbox as dbx;

class aDropboxGallery
{
  private $appKey;  
  private $appKeySecret; 
  private $appAuthCode;     
  private $authURL;
  private $accessToken;

  public function __construct()
  {
    $this->appKey        = get_option('adg_app_key');
    $this->appKeySecret  = get_option('adg_app_key_secret');
    $this->appAuthCode   = get_option('adg_app_auth_code');
    $this->accessToken   = get_option('adg_app_access_token');

    if ($this->appKey && $this->appKeySecret) {
      $jsonKeys = array(
        'key' => $this->appKey,
        'secret' => $this->appKeySecret,
      );
      
      if ( is_null($this->accessToken) ) {
        $appInfo = dbx\AppInfo::loadFromJson($jsonKeys);
        $webAuth = new dbx\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");
        $this->authURL = $webAuth->start();  

        if ($this->appAuthCode) {
          try {
            list($accessToken, $dropboxUserId) = $webAuth->finish($this->appAuthCode);
            if ( ! $this->accessToken ) {
              update_option('adg_app_access_token', $accessToken);
              $this->accessToken = $accessToken;
            }  
          } catch (Exception $e) {
            $this->appAuthCode = NULL;
            $this->accessToken = NULL;
            update_option('adg_app_auth_code', $this->appAuthCode);
            update_option('adg_app_access_token', $this->accessToken);
          }
          
        }
      }
      else {
        $this->authURL = NULL;
      }
    }

    
  }

  public function getAdminOptions()
  {
    add_menu_page( 'aDropboxGallery', 'aDropboxGallery', 'administrator', 'a_dropbox_gallery', array($this, 'getAdminSettings'));
  }

  public function getAdminSettings()
  {
    $html = '</pre>
    <div class="wrap">
      <form action="options.php" method="post" name="options">
        <h2>Please configure the plugin with this 3 steps</h2>' . wp_nonce_field('update-options') . '
        <h3>1. First of all, you have to create your Dropbox App <a href="https://www.dropbox.com/developers/apps/create">here</a></h3>
        <h3>2. Second, configure your application. Be sure to <strong>recreate your access token</strong> first.</h3>
        <table class="form-table" width="100%" cellpadding="10">
          <tbody>
            <tr>
              <td>
                <label>App Key</label>
              </td>
              <td>
                <input type="text" name="adg_app_key" value="'.$this->appKey.'" placeholder="" size="30">
              </td> 
            </tr>
            <tr>
              <td>
                <label>App Key Secret</label>
              </td>
              <td>
                <input type="text" name="adg_app_key_secret" value="'.$this->appKeySecret.'" placeholder="" size="30">
              </td> 
            </tr>
            <tr>
              <td></td>
              <td>
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="page_options" value="adg_app_key,adg_app_key_secret,adg_app_auth_code" />
                <input type="submit" name="Submit" value="Save Settings" />
              </td>
            </tr>
          </tbody>
        </table>
        ';

        if ($this->accessToken) {
          $html .= '
          <h3> 3. Good! Now, you can use the shortcode like this:</h3>
          <p>Shortcode Example: <em>[adropboxgallery path="/photos/event"]</em></p>
          ';
        } 
        else {
          $html .= '<h3>3. Third, you have to go to this <a href="'.$this->authURL .'">link</a> and authorize this plugin and copy the authorization code here:</h3>
          <table class="form-table" width="100%" cellpadding="10">
          <tbody>
            <tr>
              <td>
                <label>Authorization Code</label>
              </td>
              <td>
                <input type="text" name="adg_app_auth_code" value="'.$this->appAuthCode.'" placeholder="" size="60">
              </td> 
            </tr>
            <tr>
              <td></td>
              <td>
                <input type="submit" name="Submit" value="Save Settings" />
              </td>
            </tr>
          </tbody>
          </table>
          ';
        }

    $html .='
      </form>
    </div>
    <pre>
    ';

    echo $html;
  }

  public function getShortCode($attributes)
  {
    $validMimeTypes = array('image/jpeg', 'image/png');
    $files = $this->getFiles($attributes['path']);

    echo '<div class="adg_gallery"><ul>';
    foreach ($files['contents'] as $file) 
    {
      if (in_array($file['mime_type'], $validMimeTypes)) {
        $link = $this->getLink($file['path']);
        echo '<li><img src="'.$link[0].'" width="300"></li>';    
      }
    }
    echo '</ul></div>';
  }

  private function getAccountInfo()
  {
    $dbxClient = new dbx\Client($this->accessToken, "PHP-Example/1.0");
    return $dbxClient->getAccountInfo();
  }

  private function getFiles($path = '/')
  {
    $dbxClient = new dbx\Client($this->accessToken, "PHP-Example/1.0");
    return $dbxClient->getMetadataWithChildren($path);
  }

  private function getThumbnail($path, $format, $size)
  {
    $dbxClient = new dbx\Client($this->accessToken, "PHP-Example/1.0");
    return $dbxClient->getThumbnail($path, $format, $size); 
  }

  private function getLink($path)
  {
    $dbxClient = new dbx\Client($this->accessToken, "PHP-Example/1.0");
    return $dbxClient->createTemporaryDirectLink($path); 
  }
}