<?php

class SingleFileCompiler {
    static protected $_instance = null;

    protected $_classStack = null;
    protected $_cacheDir = null;
    protected $_debug = null;
    protected $_includePaths = array(
        'app/code/local',
        'app/code/community',
        'app/code/core',
        'lib'
    );

    static public function cleanURL( $url ) {
        return str_replace( '/', '--slash--', $url );
    }

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
            $this->_cacheDir = $this->_baseDir . DIRECTORY_SEPARATOR .
                trim( $cacheDir, '/' );
        }
        $this->_debug = $debug;
        foreach( $this->_includePaths as $includePath ) {
            $includePath = $this->_baseDir . '/' . $includePath;
        }
    }

    public function enable() {
        if( file_exists( $cacheFile = $this->getCacheFilename() ) ) {
            $origIncludePath = get_include_path();
            set_include_path( implode( ':', $this->_includePaths ) );
            include_once $cacheFile;
            set_include_path( $origIncludePath );
        } else {
            if( version_compare( phpversion(), '5.3.0', '>=' ) ) {
                spl_autoload_register( array( $this, 'SFC_autoload' ), true, true );
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
        }
    }

    public function getCacheFilename() {
        return sprintf( '%s/SFC_%s.php', $this->_cacheDir,
            self::cleanURL( $_SERVER['SCRIPT_URL'] ) );
    }

    public function SFC_autoload( $className ) {
        $this->_classStack[] = $className;
        return false;
    }

    public function SFC_shutdown() {
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
            $start = microtime( true );
            $content = $this->_generateCacheFileContent();
            $end = microtime( true );
            if( $this->_debug ) {
                $content = str_replace( '?><?php', '',
                    sprintf( '<?php /* %s : %s (%d classes) */ ?>',
                        $_SERVER['SCRIPT_URL'], $this->getCacheFilename(),
                        count( $this->_classStack ) ) . $content,
                    $c=1 );
            }
            file_put_contents( $tempFilename = tempnam( $this->_outputDir, 'SFC' ),
                $content );
            rename( $tempFilename, $cacheFilename );
        }
    }

    public function getClassPath( $className ) {
        return str_replace( '_', '/', $className ) . '.php';
    }

    protected function _addToStack( $className ) {
        if( !in_array( $className, $this->_classStack ) ) {
            $refClass = new ReflectionClass( $className );
            if( $parentRefClass = $refClass->getParentClass() ) {
                if( !$parentRefClass->isInternal() ) {
                    $this->_addToStack( $parentRefClass->getName() );
                }
            }
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

    protected function _getClassContent( $className ) {
        $content = '';
        foreach( $this->_includePaths as $includePath ) {
            $fullPath = $this->_baseDir . '/' . $includePath . '/' .
                $this->getClassPath( $className );
            if( is_readable( $fullPath ) ) {
                $subcontent = $this->_debug ? file_get_contents( $fullPath ) :
                    php_strip_whitespace( $fullPath );
                if( $this->_debug ) {
                    $content .= sprintf( '<?php /* %s -> %s */ ?>', $className,
                        $fullPath );
                }
                $content .= $subcontent . '?>';
                return $content;
            }
        }
        error_log( 'Unable to locate file for autoloaded class: ' . $className );
        return '';
    }

    protected function _generateCacheFileContent() {
        $classLoadOrder = array_reverse( $this->_classStack );
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

if( !isset( $_REQUEST['SFC_DISABLE'] ) ) {
    SingleFileCompiler::get( 'var/cache/sfc', isset( $_REQUEST['SFC_DEBUG'] )->enable();
}
