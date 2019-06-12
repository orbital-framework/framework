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
     * Processed flag
     * @var boolean
     */
    private static $processed = FALSE;

    /**
     * Process input data
     * @return void
     */
    private static function process(){

        if( self::$processed ){
            return;
        }

        self::$_header = getallheaders();

        if( isset($_REQUEST) ){
            foreach( $_REQUEST as $key => $value ){
                if( !is_null($value) ){
                    self::$_request[ $key ] = $value;
                }
            }
        }

        if( isset($_POST) ){
            foreach( $_POST as $key => $value ){
                if( !is_null($value) ){
                    self::$_post[ $key ] = $value;
                }
            }
        }

        if( isset($_GET) ){
            foreach( $_GET as $key => $value ){
                if( !is_null($value) ){
                    self::$_get[ $key ] = $value;
                }
            }
        }

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

        if( isset($_COOKIE) ){
            foreach( $_COOKIE as $key => $value ){
                if( !is_null($value) ){
                    self::$_cookie[ $key ] = $value;
                }
            }
        }

        $input = file_get_contents('php://input');
        $parsed = json_decode($input, TRUE);

        if( $parsed === FALSE ){
            $parsed = array();
            parse_str($input, $parsed);
        }

        self::$_request = array_merge(
            self::$_request, $parsed
        );

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
     * Retrieve Client IP
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