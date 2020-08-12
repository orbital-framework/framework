<?php

namespace Orbital\Framework;

abstract class Request {

    /**
     * HTTP Request Headers
     * @var array
     */
    private static $_header = array();

    /**
     * $_REQUEST
     * @var array
     */
    private static $_request = array();

    /**
     * $_POST
     * @var array
     */
    private static $_post = array();

    /**
     * $_GET
     * @var array
     */
    private static $_get = array();

    /**
     * $_PUT
     * @var array
     */
    private static $_put = array();

    /**
     * $_DELETE
     * @var array
     */
    private static $_delete = array();

    /**
     * $_FILES
     * @var array
     */
    private static $_files = array();

    /**
     * $_COOKIE
     * @var array
     */
    private static $_cookie = array();

     /**
     * $_ENV
     * @var array
     */
    private static $_env = array();

    /**
     * CLI $arg
     * @var array
     */
    private static $_arg = array();

    /**
     * Processed flag
     * @var boolean
     */
    private static $processed = FALSE;

    /**
     * Process input data
     * @return void
     */
    private static function process(){
        global $argv;

        if( self::$processed ){
            return;
        }

        // Headers
        $copy = array(
            'CONTENT_TYPE' => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5' => 'Content-Md5',
            'REDIRECT_HTTP_AUTHORIZATION' => 'Authorization',
            'PHP_AUTH_DIGEST' => 'Authorization'
        );

        foreach( $_SERVER as $key => $value ){
            if( substr($key, 0, 5) === 'HTTP_' ){
                $key = substr($key, 5);
                $key = ucwords(strtolower(str_replace('_', ' ', $key)));
                $key = str_replace(' ', '-', $key);
                self::$_header[$key] = $value;
            }elseif( isset($copy[$key]) ){
                self::$_header[ $copy[$key] ] = $value;
            }
        }

        if( !isset(self::$_header['Authorization'])
            AND isset($_SERVER['PHP_AUTH_USER']) ){

            $pass = isset($_SERVER['PHP_AUTH_PW'])
                ? $_SERVER['PHP_AUTH_PW'] : '';
            $encode = base64_encode($_SERVER['PHP_AUTH_USER']. ':'. $pass);
            $basic = 'Basic '. $encode;

            self::$_header['Authorization'] = $basic;

        }

        // Request
        if( isset($_REQUEST) ){
            foreach( $_REQUEST as $key => $value ){
                if( !is_null($value) ){
                    self::$_request[ $key ] = $value;
                }
            }
        }

        // Post
        if( isset($_POST) ){
            foreach( $_POST as $key => $value ){
                if( !is_null($value) ){
                    self::$_post[ $key ] = $value;
                }
            }
        }

        // Get
        if( isset($_GET) ){
            foreach( $_GET as $key => $value ){
                if( !is_null($value) ){
                    self::$_get[ $key ] = $value;
                }
            }
        }

        // Files
        if( isset($_FILES) ){
            foreach( $_FILES as $key => $value ){

                if( is_null($value) ){
                    continue;
                }

                $data = array();

                foreach( $value as $subKey => $subValue ){
                    if( is_array($subValue) ){
                        foreach( $subValue as $subFile => $subFileValue ){
                            $data[ $subFile ][ $subKey ] = $subFileValue;
                        }
                    }else{
                        $data[ $subKey ] = $subValue;
                    }
                }

                self::$_files[ $key ] = $data;
            }
        }

        // Cookie
        if( isset($_COOKIE) ){
            foreach( $_COOKIE as $key => $value ){
                if( !is_null($value) ){
                    self::$_cookie[ $key ] = $value;
                }
            }
        }

        // Environment
        if( isset($_ENV) ){
            foreach( $_ENV as $key => $value ){
                if( !is_null($value) ){
                    self::$_env[ $key ] = $value;
                }
            }
        }

        // CLI arg
        if( isset($argv) ){
            foreach( $argv as $key => $value ){

                if( $key == 0 ){
                    continue;
                }

                if( preg_match('/--([^=]+)=(.*)/', $value, $matches) ){
                    self::$_arg[ $matches[1] ] = $matches[2];

                }elseif( preg_match('/-([a-zA-Z0-9])/', $value, $matches) ){
                    self::$_arg[ $matches[1] ] = true;
                }

            }
        }

        // PHP Input
        $input = file_get_contents('php://input');
        $parsed = json_decode($input, TRUE);

        if( $parsed === FALSE ){
            $parsed = array();
            parse_str($input, $parsed);
        }

        if( !$parsed ){
            $parsed = array();
        }

        self::$_request = array_merge(
            self::$_request, $parsed
        );

        if( isset($_SERVER['REQUEST_METHOD']) ){

            switch( $_SERVER['REQUEST_METHOD'] ){
                case 'GET':
                    self::$_get = array_merge(
                        self::$_get, $parsed
                    );
                break;
                case 'POST':
                    self::$_post = array_merge(
                        self::$_post, $parsed
                    );
                break;
                case 'PUT':
                    self::$_put = array_merge(
                        self::$_put, $parsed
                    );
                break;
                case 'DELETE':
                    self::$_delete = array_merge(
                        self::$_delete, $parsed
                    );
                break;
            }

        }

        self::$processed = TRUE;

    }

    /**
     * Retrieve value by key on array
     * @param array $data
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private static function retrieve(
        $data = array(),
        $key = NULL,
        $default = NULL
    ){

        if( is_null($key) ){
            return $data;
        }

        if( isset($data[ $key ]) ){
            return $data[ $key ];
        }

        return $default;
    }

    /**
     * Retrieve values sent by HTTP Header
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function header($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_header,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_REQUEST
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function request($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_request,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_POST
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function post($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_post,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_GET
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_get,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_PUT
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function put($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_put,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_DELETE
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function delete($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_delete,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_FILES
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function files($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_files,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_COOKIE
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function cookie($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_cookie,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by $_ENV
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function env($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_env,
            $key,
            $default
        );
    }

    /**
     * Retrieve values sent by CLI $argv
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function arg($key = NULL, $default = NULL){

        self::process();

        return self::retrieve(
            self::$_arg,
            $key,
            $default
        );
    }

    /**
     * Retrieve request interface Method
     * @return string
     */
    public static function method(){

        if( php_sapi_name() === 'cli' OR defined('STDIN') ){
            $method = 'CLI';

        }else{

            $method = ( isset($_SERVER['REQUEST_METHOD']) ) ?
                        $_SERVER['REQUEST_METHOD'] : 'GET';

            // Force GET when method is HEAD
            if( $method == 'HEAD' ){
                $method = 'GET';
            }

        }

        return $method;
    }

    /**
     * Retrieve client IP
     * @return string
     */
    public static function clientIp(){

        if( isset($_SERVER) ){

            if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ){
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            if( isset($_SERVER['HTTP_CLIENT_IP']) ){
                return $_SERVER['HTTP_CLIENT_IP'];
            }

            if( isset($_SERVER['REMOTE_ADDR']) ){
                return $_SERVER['REMOTE_ADDR'];
            }

        }

        if( getenv('HTTP_X_FORWARDED_FOR') ){
            return getenv('HTTP_X_FORWARDED_FOR');
        }

        if( getenv('HTTP_CLIENT_IP') ){
            return getenv('HTTP_CLIENT_IP');
        }

        return getenv('REMOTE_ADDR');
    }

}