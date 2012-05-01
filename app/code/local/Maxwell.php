<?php

/**
 * Nexcess.net Magento Daemon
 *
 * <pre>
 * +----------------------------------------------------------------------+
 * | Nexcess.net Magento Daemon                                           |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 2006-2011 Nexcess.net L.L.C., All Rights Reserved.     |
 * +----------------------------------------------------------------------+
 * | Redistribution and use in source form, with or without modification  |
 * | is NOT permitted without consent from the copyright holder.          |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND |
 * | ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,    |
 * | THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A          |
 * | PARTICULAR PURPOSE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,    |
 * | EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,  |
 * | PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR   |
 * | PROFITS; OF BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY  |
 * | OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT         |
 * | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE    |
 * | USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH     |
 * | DAMAGE.                                                              |
 * +----------------------------------------------------------------------+
 * </pre>
 */

class Maxwell {
  /**
   * Path to the main Magento app file. Will need to be changed if client
   * is doing something weird with their setup.
   * 
   * @var string
   */
  const APP_FILE                = 'app/Mage.php';
  
  /**
   * Path to the compiler config file. Shouldn't need to be changed.
   * 
   * @var string
   */
  const COMPILER_FILE           = 'includes/config.php';
  
  /**
   * Path to the flag file used for signalling maintenance mode. Shouldn't
   * need to be changed.
   * 
   * @var string
   */
  const MAINTENANCE_FLAG_FILE   = 'maintenance.flag';
  
  /**
   * Minimum required PHP version for Magento. Technically this file itself
   * requires PHP 5.3+ but whatever.
   * 
   * @var string
   */
  const MIN_PHP_VERSION         = '5.2.0';

  /**
   * Default response code.
   * 
   * @var int
   */
  const DEFAULT_CODE            = 200;
  
  /**
   * Default reponse content
   * 
   * @var string
   */
  const DEFAULT_CONTENT         = '';
  
  /**
   * Response code to be sent to client.
   *
   * @var int
   */
  static protected $_code     = null;
  
  /**
   * Array of headers to be sent in response, format is: array( 'header1',
   *   'value1', 'header2', 'value2' );
   * 
   * Format is not a simple KV pair to allow for multiple headers with the same
   * key and is required by AiP
   *
   * @var array
   */
  static protected $_headers  = null;
  
  /**
   * Content to be sent in response
   *
   * @var string
   */
  static protected $_content  = null;
  
  /**
   * Flag for whether the AiP + Maxwell process is being used or not. Is tripped
   * by the Maxwell constructor
   *
   * @var bool
   */
  static protected $_active   = false;
  
  /**
   * Flag for locking the response to disallow further changes, useful for
   * redirects.
   * 
   * @var bool
   */
  static protected $_locked   = false;
  
  /**
   * The context of the current request.
   * 
   * @var array
   */
  static protected $_context  = null;
  
  /**
   * Flag for enabling Varien's profiling stuff.
   *
   * @var bool
   */
  protected $_profiling       = false;
  
  /**
   * Flag for displaying PHP errors. Should be off in production env.
   *
   * @var bool
   */
  protected $_showErrors      = true;

  /**
   * Set the response code.
   *
   * @param int $code 
   */
  static public function setCode( $code ) {
    if( !self::$_locked ) {
      self::$_code = (int)$code;
    }
  }
  
  /**
   * Add a header to the response.
   * 
   * @param string $name
   * @param string $value
   */
  static public function setHeader( $name, $value ) {
    if( !self::$_locked ) {
      self::$_headers[] = $name;
      self::$_headers[] = $value;
    }
  }
  
  /**
   * Add multiple headers to the response. Format is Zend like:
   * array( array( 'name' => 'header1', 'value' => 'value1' ) );
   *
   * @param array $headers 
   */
  static public function setHeaders( array $headers ) {
    if( !self::$_locked ) {
      foreach( $headers as $header ) {
        self::setHeader( $header['name'], $header['value'] );
      }
    }
  }
  
  /**
   * Set response content.
   *
   * @param string $content 
   */
  static public function setContent( $content ) {
    if( !self::$_locked ) {
      self::$_content = (string)$content;
    }
  }
  
  /**
   * Lock the response, prevent any further changes.
   */
  static public function lockResponse() {
    self::$_locked = true;
  }
  
  /**
   * Convenience method for redirecting and setting the response lock.
   *
   * @param string $location
   * @param int $code 
   */
  static public function redirect( $location, $code = 301 ) {
    if( !self::$_locked ) {
      self::_resetResponse();
      self::setCode( $code );
      self::setHeader( 'Location', $location );
      self::lockResponse();
    }
  }
  
  /**
   * Get the context of the current request.
   *
   * @return array
   */
  static public function getContext() {
    return self::$_context;
  }
  
  /**
   * Get the response in the format that AiP expects:
   * array( $code, $headers, $content );
   *
   * @return array
   */
  static public function getResponse() {
    return array( self::$_code, self::$_headers, self::$_content );
  }
  
  /**
   * Check if we're running in AiP+Maxwell mode.
   *
   * @return bool 
   */
  static public function isActive() {
    return self::$_active;
  }
  
  /**
   * Check if the response has been locked.
   * 
   * @return bool
   */
  static public function isResponseLocked() {
    return self::$_locked;
  }
  
  /**
   * Reset the response vars back to defaults.
   */
  static protected function _resetResponse() {
    self::$_locked = false;
    self::$_code = self::DEFAULT_CODE;
    self::$_headers = array();
    self::$_content = self::DEFAULT_CONTENT;
  }

  public function __construct() {
    self::$_active = true;
    error_reporting( E_ALL | E_STRICT );
    if( file_exists( self::COMPILER_FILE ) ) {
      include_once self::COMPILER_FILE;
    }
    umask(0);
  }
  
  /**
   * Entry point for AiP
   *
   * @param array $context
   * @return array
   */
  public function __invoke( $context ) {
    $this->_applyContext( $context );
    $this->_run();
    $response = self::getResponse();
    file_put_contents( 'out.txt', print_r(
      array( headers_list(), $_SERVER, $_COOKIE, $context ), true ) );
    printf( "URI: %s | Code: %d", $_SERVER['REQUEST_URI'], $response[0] );
    return $response;
  }
  
  /**
   * Setup the environment the way Magento would normally expect it to be and
   * reset the request to get rid of old data.
   *
   * @param array $context 
   */
  protected function _applyContext( $context ) {
    self::_resetResponse();
    self::$_context = $context;
    foreach( array( '_GET', '_POST', '_FILES', '_SERVER', '_COOKIE' ) as $v ) {
      if( isset( $context[$v] ) ) {
        if( is_array( $context[$v] ) ) {
          $$v = $context[$v];
        } else {
          $$v = $context[$v]->__toArray();
        }
      } else {
        unset( $$v );
      }
    }
    if( isset( $context['env'] ) ) {
      $_ENV = $context['env'];
      $_SERVER = $context['env'];
    } else {
      unset( $_ENV );
    }
  }
  
  /**
   * Run the stuff that would normally be found in index.php, basic error
   * checking stuff. If this passes we move on to _runApp()
   */
  protected function _run() {
    if( $this->_showErrors ) {
      ini_set( 'display_errors', 1 );
    }
    if( version_compare( phpversion(), self::MIN_PHP_VERSION, '<' ) ) {
      self::setContent( 'Unsupported PHP version: ' . phpversion() );
    } else {
      if( !file_exists( self::APP_FILE ) ) {
        if( is_dir( 'downloader' ) ) {
          self::redirect( 'downloader', 301 );
        } else {
          self::setContent( 'App file not found: ' . self::APP_FILE );
        }
      } elseif( file_exists( self::MAINTENANCE_FLAG_FILE ) ) {
        self::setCode( 503 );
        ob_start();
        include dirname( __FILE__ ) . '/errors/503.php';
        self::setContent( ob_get_clean() );
        if( isset( $processor ) ) {
          unset( $processor );
        }
      } else {
        $this->_runApp();
      }
    }
  }
  
  /**
   * _run() passed, we can run Magento for real now.
   */
  protected function _runApp() {
    require_once self::APP_FILE;
    if( $this->_profiling ) {
      Varien_Profiler::enable();
    }
    if( isset( $_SERVER['MAGE_IS_DEVELOPER_MODE'] ) ) {
      Mage::setIsDeveloperMode( true );
    }
    Mage::run( $this->_getRunCode(), $this->_getRunType() );
    if( $this->_profiling ) {
      Varien_Profiler::disable();
    }
    Mage::reset();
  }
  
  /**
   * Get the store code to run Magento on. Should be edited to add logic if
   * client is doing something unusual (multi-site setup).
   *
   * @return string
   */
  protected function _getRunCode() {
    return '';
  }
  
  /**
   * Get the store type to run Magento on. Should be edited to add logic if
   * client is doing something unusual (multi-site setup).
   *
   * @return string
   */
  protected function _getRunType() {
    return 'store';
  }
}
