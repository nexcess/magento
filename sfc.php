<?php

/**
 * Compiler to collect all the classes *autoloaded* by Magento into
 * a single file associated with the request URL.
 *
 * To use, just include this file
 * before the app/Mage.php file in index.php
 */
class SingleFileCompiler {
    /**
     * Singleton instance holder
     *
     * @var SingleFileCompiler
     */
    static protected $_instance = null;

    /**
     * Autoloaded class name stack
     *
     * @var array
     */
    protected $_classStack = null;
    /**
     * Absolute path to magento root directory
     *
     * @var string
     */
    protected $_baseDir = null;
    /**
     * Absolute path to store cache files in
     *
     * @var string
     */
    protected $_cacheDir = null;
    /**
     * Debug output flag
     *
     * @var bool
     */
    protected $_debug = null;
    /**
     * Include paths Magento files will expect
     *
     * @var array
     */
    protected $_includePaths = array(
        'app/code/local',
        'app/code/community',
        'app/code/core',
        'lib'
    );
    /**
     * Compiler activated flag
     *
     * @var  bool
     */
    protected $_enabled = false;
    /**
     * Using cache file flag
     *
     * @var  bool
     */
    protected $_cached = null;

    /**
     * Get the current absolute URL path, without parameters
     *
     * @return string
     */
    static public function getActiveUrl() {
        return isset( $_SERVER['SCRIPT_URL'] ) ?
            $_SERVER['SCRIPT_URL'] :
            parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
    }

    /**
     * Clean a URL so it is suitable for use in a filename
     *
     * @param  string $url URL to clean
     * @return string
     */
    static public function cleanUrl( $url ) {
        return str_replace( '/', '--slash--', $url );
    }

    /**
     * Get the SFC instance or create if it doesn't exist
     *
     * @param  string  $cacheDir directory to store cache files in, absolute or
     *                           relative to magento root
     * @param  boolean $debug    enable debug output
     * @return SingleFileCompiler
     */
    static public function get( $cacheDir = 'var/cache/sfc', $debug = false ) {
        if( is_null( self::$_instance ) ) {
            self::$_instance = new SingleFileCompiler( $cacheDir, $debug );
        }
        return self::$_instance;
    }

    protected function __construct( $cacheDir = 'var/cache/sfc', $debug = false ) {
        $this->_classStack = array();
        $this->_baseDir = dirname( __FILE__ );
        if( $cacheDir[0] == '/' ) {
            $this->_cacheDir = '/' . trim( $cacheDir, '/' );
        } else {
            $this->_cacheDir = $this->_baseDir . '/' . trim( $cacheDir, '/' );
        }
        $this->_debug = $debug;
        foreach( $this->_includePaths as &$includePath ) {
            $includePath = $this->_baseDir . '/' . $includePath;
        }
    }

    /**
     * Enable the compiler
     *
     * If the cache file exists, we just load it; otherwise we stick our
     * autoload function at the front of the line to catch all autoloaded classes
     * and register our shutdown function for cache file generation.
     *
     * @return bool cache state
     */
    public function enable() {
        if( !$this->isEnabled() ) {
            if( $contentFilename = realpath( $this->getCacheFilename() ) ) {
                //temporarily set include path to what magento will have to account
                //for manually included files
                $origIncludePath = get_include_path();
                set_include_path( implode( ':', $this->_includePaths ) );
                include_once $contentFilename;
                set_include_path( $origIncludePath );
                $this->_cached = true;
            } else {
                if( version_compare( phpversion(), '5.3.0', '>=' ) ) {
                    spl_autoload_register( array( $this, 'SFC_autoload' ),
                        true, true );
                } else {
                   $autoloadStack = spl_autoload_functions();
                   foreach( $autoloadStack as $autoloadFunction ) {
                       spl_autoload_unregister( $autoloadFunction );
                   }
                   spl_autoload_register( array( $this, 'SFC_autoload' ) );
                   foreach( $autoloadStack as $autoloadFunction ) {
                       spl_autoload_register( $autoloadFunction );
                   }
                }
                register_shutdown_function( array( $this, 'SFC_shutdown' ) );
                $this->_cached = false;
            }
            $this->_enabled = true;
        }
        return $this->_cached;
    }

    /**
     * Get compiler status
     *
     * @return boolean
     */
    public function isEnabled() {
        return $this->_enabled;
    }

    /**
     * Check if we're generating teh cache or reading from it
     *
     * @return boolean
     */
    public function isCached() {
        return $this->_cached;
    }

    /**
     * Get the full path to the cache file associated with the current active URL
     *
     * @return string
     */
    public function getCacheFilename() {
        return sprintf( '%s/SFC_%s.php', $this->_cacheDir,
            self::cleanUrl( self::getActiveUrl() ) );
    }

    public function getContentFilename( $content ) {
        return sprintf( '%s/SFC_%s.php', $this->_cacheDir,
            hash( 'sha256', $content ) );
    }

    /**
     * Autoload function to collect autoloaded classes
     *
     * @param string $className class to load
     */
    public function SFC_autoload( $className ) {
        $this->_classStack[] = $className;
        return false;
    }

    /**
     * Shutdown function to generate cache file from autoload class stack
     */
    public function SFC_shutdown() {
        //try to get the data out to the user so cache generation doesn't
        //affect them
        ob_flush();
        flush();
        ignore_user_abort( true );
        if( function_exists( 'fastcgi_finish_request' ) ) {
            fastcgi_finish_request();
        }
        if( !file_exists( $cacheFilename = $this->getCacheFilename() ) ) {
            if( !is_dir( $this->_cacheDir ) ) {
                mkdir( $this->_cacheDir, 0751, true );
            }
            $content = $this->_generateCacheFileContent();
            if( $this->_debug ) {
                $content = str_replace( '?><?php', '',
                    sprintf( '<?php /* %s : %s (%d classes) */ ?>',
                        self::getActiveUrl(), $this->getCacheFilename(),
                        count( $this->_classStack ) ) . $content,
                    $c=1 );
            }

            if( !file_exists( $contentFilename =
                    $this->getContentFilename( $content ) ) ) {
                file_put_contents(
                    $tempFilename = tempnam( $this->_outputDir, 'SFC' ),
                    $content );
                rename( $tempFilename, $contentFilename );
            }
            symlink( $contentFilename, $cacheFilename );
        }
    }

    /**
     * Get the relative path to a class file based on it's name
     *
     * @param  string $className
     * @return string
     */
    public function getClassPath( $className ) {
        return str_replace( '_', '/', $className ) . '.php';
    }

    /**
     * Recursively add a class and it's parents to the stack
     *
     * @param string $className class to add
     */
    protected function _addToStack( $className ) {
        if( !in_array( $className, $this->_classStack ) ) {
            $refClass = new ReflectionClass( $className );
            if( $parentRefClass = $refClass->getParentClass() ) {
                if( !$parentRefClass->isInternal() ) {
                    $this->_addToStack( $parentRefClass->getName() );
                }
            }
            //interfaces could possibly be needed before parent class, not sure
            if( $ifaces = $refClass->getInterfaces() ) {
                foreach( $ifaces as $iface ) {
                    if( !$iface->isInternal() ) {
                        $this->_addToStack( $iface->getName() );
                    }
                }
            }
            $this->_classStack[] = $className;
        }
    }

    /**
     * Get the cleaned code for a given class
     *
     * @param  string $className class to act on
     * @return string
     */
    protected function _getClassContent( $className ) {
        $content = '';
        foreach( $this->_includePaths as $includePath ) {
            $fullPath = $includePath . '/' . $this->getClassPath( $className );
            if( is_readable( $fullPath ) ) {
                $subcontent = $this->_debug ? file_get_contents( $fullPath ) :
                    php_strip_whitespace( $fullPath );
                if( $this->_debug ) {
                    $content .= sprintf( '<?php /* %s -> %s */ ?>', $className,
                        $fullPath );
                }
                //this is the easiest way to deal with the opening tag in each file
                $content .= $subcontent . '?>';
                return $content;
            }
        }
        error_log( 'Unable to locate file for autoloaded class: ' . $className );
        return $content;
    }

    /**
     * Combines the class code from the classes in the stack
     *
     * @return string
     */
    protected function _generateCacheFileContent() {
        $classLoadOrder = $this->_classStack;
        sort( $classLoadOrder );
        $this->_classStack = array();
        foreach( $classLoadOrder as $className ) {
            $this->_addToStack( $className );
        }
        $content = '';
        foreach( $this->_classStack as $className ) {
            $content .= $this->_getClassContent( $className );
        }
        return str_replace( '?><?php', '', $content );
    }
}

//passing the SFC_DISABLE ( /blah?SFC_DISABLE ) get param will disable SFC
if( !isset( $_REQUEST['SFC_DISABLE'] ) ) {
    SingleFileCompiler::get(
        'var/cache/sfc',
        //passing SFC_DEBUG enables debug output, has no effect if
        //file is already cached
        isset( $_REQUEST['SFC_DEBUG'] )
    )->enable();
}
