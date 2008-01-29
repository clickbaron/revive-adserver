<?php
/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                                              |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id$
*/

require_once MAX_PATH . '/lib/max/other/common.php';
require_once MAX_PATH . '/plugins/authentication/cas/CAS/CAS.php';
require_once MAX_PATH . '/plugins/authentication/cas/CAS/client.php';
require_once MAX_PATH . '/plugins/authentication/cas/OaCasClient.php';
require_once MAX_PATH . '/www/admin/lib-sessions.inc.php';

/**
 * Authentication CAS plugin which authenticates users against cas-server
 *
 * @package    OpenadsPlugin
 * @subpackage Authentication
 * @author     Radek Maciaszek <radek@openads.org>
 * @abstract
 */
class Plugins_Authentication_Cas_Cas // extends Plugins_Authentication
{
    function suppliedCredentials()
    {
        return isset($_GET['ticket']);
    }
    
    /**
     * Initialize CAS client. Only one copy of CAS client is allowed
     *
     */
    function initCasClient()
    {
        static $initialized;
        $aOpenSsoConfig = $GLOBALS['_MAX']['CONF']['oacSSO'];
        if (is_null($initialized)) {
            $this->clientInitialization(CAS_VERSION_2_0, $aOpenSsoConfig['host'],
               intval($aOpenSsoConfig['port']), $aOpenSsoConfig['casClientPath']);
            $initialized = true;
        }
    }
    
    function storePhpCasSession()
    {
        $sessionPhpCas = isset($_SESSION['phpCAS']) ? $_SESSION['phpCAS'] : null;
        global $session;
        $session['phpCAS'] = $sessionPhpCas;
        phpAds_SessionDataStore();
    }
    
    function restorePhpCasSession()
    {
        global $session;
        $_SESSION['phpCAS'] = isset($session['phpCAS']) ? $session['phpCAS'] : null;
    }
    
    function authenticateUser()
    {
        $this->restorePhpCasSession();
        
        $this->initCasClient();
        phpCAS::forceAuthentication();
        $username = phpCAS::getUser();
        
        $this->storePhpCasSession();
        
        if ($username) {
            $doUser = $this->getUser($username);
            if ($doUser) {
                return $doUser;
            }
            $this->displayRegistrationRequiredInfo($username);
        }
        return false;
    }
    
    /**
     * A static method to check a username and password
     *
     * @static
     *
     * @param string $username
     * @param string $md5Password
     * @return mixed A DataObjects_Users instance, or false if no matching user was found
     */
    function getUser($username)
    {
        $doUser = OA_Dal::factoryDO('users');
        $doUser->whereAdd('LOWER(username) = '.DBC::makeLiteral(strtolower($username)));
        $doUser->find();

        if ($doUser->fetch()) {
            return $doUser;
        }
        return false;
    }
    
    function logout()
	{
	    $this->restorePhpCasSession();
	    // unset phpCas session
	    if (isset($_SESSION['phpCAS'])) {
	       unset($_SESSION['phpCAS']);
	    }
	    phpAds_SessionDataDestroy();
	    // logout from CAS
        $dalAgency = OA_Dal::factoryDAL('agency');
        $this->initCasClient();
        phpCAS::logout($dalAgency->getLogoutUrl($GLOBALS['agencyid']));
        exit;
	}
    
    /**
     * A static method to display a login screen
     * 
     * TODOAUTH - put here cas login iframe, for now instead just redirect user?
     *
     * @static
     *
     * @param string $sMessage
     * @param string $sessionID
     * @param bool $inlineLogin
     */
    function displayRegistrationRequiredInfo($userName = '')
    {
        phpAds_PageHeader("1");
        echo "<br><br>";
        echo 'Welcome <b>'.phpCAS::getUser().'</b>';
        echo "<br /><br />You need a OAH account in order to use Openads Hosted. ";
        echo "If you want to create a Openads Hoster account please fill in <a href='http://www.openads.org/hosted'>this form</a>.<br /><br />";
        phpAds_PageFooter();
        exit();
    }
    
    /**
     * A static method to display a login screen
     * 
     * TODOAUTH - put here cas login iframe, for now instead just redirect user?
     *
     * @static
     *
     * @param string $sMessage
     * @param string $sessionID
     * @param bool $inlineLogin
     */
    function displayLogin($sMessage = '', $sessionID = 0, $inLineLogin = false)
    {
        $this->authenticateUser();
        exit;
    }
    
  /**
   * phpCAS client initializer (slightly modified version of method from phpCas::client),
   * returns OaCasClient object instead of CasClient
   * 
   * @note Only one of the phpCAS::client() and phpCAS::proxy functions should be
   * called, only once, and before all other methods (except phpCAS::getVersion()
   * and phpCAS::setDebug()).
   *
   * @param $server_version the version of the CAS server
   * @param $server_hostname the hostname of the CAS server
   * @param $server_port the port the CAS server is running on
   * @param $server_uri the URI the CAS server is responding on
   * @param $start_session Have phpCAS start PHP sessions (default true)
   *
   * @return a newly created OaCasClient object
   */
  function clientInitialization($server_version,
          $server_hostname,
          $server_port,
          $server_uri,
          $start_session = true)
  {
      global $PHPCAS_CLIENT, $PHPCAS_INIT_CALL;

      phpCAS::traceBegin();
      if ( is_object($PHPCAS_CLIENT) ) {
    phpCAS::error($PHPCAS_INIT_CALL['method'].'() has already been called '.
          '(at '.$PHPCAS_INIT_CALL['file'].':'.$PHPCAS_INIT_CALL['line'].')');
      }
      if ( gettype($server_version) != 'string' ) {
            phpCAS::error('type mismatched for parameter $server_version (should be `string\')');
      }
      if ( gettype($server_hostname) != 'string' ) {
          phpCAS::error('type mismatched for parameter $server_hostname (should be `string\')');
      }
      if ( gettype($server_port) != 'integer' ) {
          phpCAS::error('type mismatched for parameter $server_port (should be `integer\')');
      }
      if ( gettype($server_uri) != 'string' ) {
          phpCAS::error('type mismatched for parameter $server_uri (should be `string\')');
      }

      // store where the initialzer is called from
      $dbg = phpCAS::backtrace();
      $PHPCAS_INIT_CALL = array('done' => TRUE,
                'file' => $dbg[0]['file'],
                'line' => $dbg[0]['line'],
                'method' => __CLASS__.'::'.__FUNCTION__);

      // initialize the global object $PHPCAS_CLIENT
      $PHPCAS_CLIENT = new OaCasClient($server_version,FALSE/*proxy*/,$server_hostname,$server_port,$server_uri,$start_session);
      phpCAS::traceEnd();
    }    
}

?>