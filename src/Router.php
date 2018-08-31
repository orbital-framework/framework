<?php

namespace Orbital\Framework;

use \Orbital\Framework\App;
use \Orbital\Framework\Header;
use \Exception;

abstract class Router {

    /**
     * Router URL
     * @var string
     */
    public static $url = NULL;

    /**
     * Router Query
     * @var string
     */
    public static $query = NULL;

    /**
     * Ajax flag
     * @var boolean
     */
    public static $isAjax = NULL;

    /**
     * Request HTTP Method
     * @var string
     */
    public static $method = NULL;

    /**
     * Active route
     * @var array
     */
    public static $route = array();

    /**
     * Path for routers
     * @var string
     */
    public static $path = '/';

    /**
     * "Routers" for errors - 404, 401...
     * @var array
     */
    public static $errors = array();

    /**
     * Requests routers
     * HTTP / WebDAV methods
     * @var array
     */
    private static $routers = array(
        # HEAD == GET
        'GET' => array(),
        'POST' => array(),
        'PUT' => array(),
        'DELETE' => array(),
        'CONNECT' => array(),
        'OPTIONS' => array(),
        'TRACE' => array(),
        'COPY' => array(),
        'LOCK' => array(),
        'MKCOL' => array(),
        'MOVE' => array(),
        'PROPFIND' => array(),
        'PROPPATCH' => array(),
        'UNLOCK' => array(),
        'REPORT' => array(),
        'MKACTIVITY' => array(),
        'CHECKOUT' => array(),
        'MERGE' => array()
    );

    /**
     * Retrieve router active URL
     * @return string
     */
    public static function getActiveUrl(){

        if( self::$url == NULL ){
            self::processUrl();
        }

        return self::$url;
    }

    /**
     * Retrieve router query
     * @return string
     */
    public static function getQuery(){

        if( self::$query == NULL ){
            self::processUrl();
        }

        return self::$query;
    }

    /**
     * Retrieve route query segment
     * @param int $number
     * @return string|boolean
     */
    public static function getSegment($number){

        $number = (int) $number - 1;
        $segment = explode('/', ltrim(self::getQuery(), '/') );

        return (isset($segment[$number])) ? $segment[$number] : FALSE;
    }

    /**
     * Retrieve router HTTP Method
     * @return string
     */
    public static function getHttpMethod(){

        if( self::$method == NULL ){

            self::$method = ( isset($_SERVER['REQUEST_METHOD']) ) ?
                                  $_SERVER['REQUEST_METHOD'] : 'GET';

            // Force GET when method is HEAD
            if( self::$method == 'HEAD' ){
                self::$method = 'GET';
            }

        }

        return self::$method;
    }

    /**
     * Retrieve if router is ajax request
     * @return boolean
     */
    public static function getIsAjax(){

        if( self::$isAjax == NULL ){

            self::$isAjax = ( isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                AND $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) ? TRUE : FALSE;

        }

        return self::$isAjax;
    }

    /**
     * Set path prefix to routers
     * @param string $path
     * @return void
     */
    public static function setPath($path){

        $path = '/'. trim($path, '/'). '/';
        $path = str_replace('//', '/', $path);

        self::$path = $path;
    }

    /**
     * Retrieve path prefix to routers
     * @return string
     */
    public static function getPath(){
        return self::$path;
    }

    /**
     * Set active route
     * @param array $route
     * @return void
     */
    public static function setActiveRoute($route){
        self::$route = $route;
    }

    /**
     * Retrieve active route
     * @return array
     */
    public static function getActiveRoute(){

        if( !self::$route ){

            $route = self::getRoute(
                self::getQuery(),
                self::getHttpMethod()
            );

            self::setActiveRoute($route);

        }

        return self::$route;
    }

    /**
     * Retrieve route from $uri and $method
     * @param string $uri
     * @param string $method
     * @return array
     */
    public static function getRoute($uri, $method){

        $route = array();

        if( !array_key_exists($method, self::$routers) ){
            return $route;
        }

        $routers = self::$routers[ $method ];

        foreach( $routers as $router ){

            $pattern = '/^'.
                str_replace(
                    array('(:any)', '(:string)', '(:number)', '/'),
                    array('([-0-9a-z]+)', '([a-z]+)', '([0-9]+)', '\/'),
                $router['rule']).
                '$/i';

            if( preg_match($pattern, $uri) OR $router['rule'] == $uri ){

                $rule = $router['rule'];
                $callback = $router['callback'];
                $parameters = FALSE;
                $options = array();

                if( is_array($router['parameters']) ){
                    $parameters = array();

                    foreach( $router['parameters'] as $parameter ){

                        // Segments
                        if( strpos($parameter, '$') === 0
                            AND is_numeric(str_replace('$', '', $parameter)) ){
                            $parameter = str_replace('$', '', $parameter);
                            $parameter = self::getSegment($parameter);
                        }

                        $parameters[] = $parameter;
                    }

                }

                // Check for not authorized parameters
                if( $parameters == FALSE
                    AND self::getSegment( count(explode('/', $rule)) + 1) ){
                    continue;
                }

                if( !$parameters ){
                    $parameters = array();
                }

                if( is_array($router['options']) ){
                    $options = $router['options'];
                }

                $route = array(
                    'rule' => $rule,
                    'callback' => $callback,
                    'parameters' => $parameters,
                    'options' => $options
                );

                break;
            }

        }

        return $route;
    }

    /**
     * Process request and run callback
     * @return void
     */
    public static function runRequest(){

        $route = self::getActiveRoute();

        if( !$route ){
            return self::runError(404);
        }

        $options = $route['options'];

        if( $options
            AND isset($options['contentType'])
            AND !is_null($options['contentType']) ){
            Header::contentType($options['contentType']);
        }

        if( $options
            AND isset($options['status'])
            AND !is_null($options['status']) ){
            Header::status($options['status']);
        }

        Header::send();

        try{
            $result = App::runMethod(
                $route['callback'],
                $route['parameters']
            );
        } catch( Exception $e ) {
            $result = self::runError(500);
        }

        return $result;
    }

    /**
     * Force error on request
     * @param int $number
     * @return void
     */
    public static function runError($number = 404){

        if( !isset(self::$errors[$number]) ){
            die('Router error '. $number);
        }

        $callback = self::$errors[ $number ]['callback'];

        self::setActiveRoute(array(
            'rule' => $number,
            'callback' => $callback,
            'parameters' => array(),
            'options' => array('status' => $number)
        ));

        self::runRequest();

    }

    /**
     * Process request URL
     * @return void
     */
    private static function processUrl(){

        $url = str_replace('/index.php', '', $_SERVER['PHP_SELF']);

        if( isset($_SERVER['REQUEST_URI']) ){
            $url = str_replace($url, '', $_SERVER['REQUEST_URI']);
        }

        $query = explode('?', $url);
        $query = $query[0];
        $query = ($query !== '/') ? rtrim($query,'/') : $query;

        self::$url = $url;
        self::$query = strtolower($query);

    }

    /**
     * Set routers to APP
     * @param string $httpMethod
     * @param string $rule
     * @param string $callback
     * @param array $parameters
     * @param array $options
     * @return void
     */
    public static function set(
        $httpMethod,
        $rule,
        $callback,
        $parameters = array(),
        $options = array()
        ){

        if( is_array($httpMethod) ){

            foreach( $httpMethod as $new ){
                self::set(
                    $new,
                    $rule,
                    $callback,
                    $parameters,
                    $options
                );
            }

            return;
        }

        $path = self::getPath(). trim($rule, '/');
        $path = str_replace('//', '/', $path);

        if( $path != '/' ){
            $path = rtrim($path, '/');
        }

        $router = array(
            'rule' => $path,
            'callback' => $callback,
            'parameters' => $parameters,
            'options' => $options
        );

        self::$routers[ $httpMethod ][] = $router;
    }

    /**
     * Set error callback when router goes wrong
     * @param string $number
     * @param string $callback
     * @return void
     */
    public static function setError($number, $callback){

        self::$errors[$number] = array(
            'callback' => $callback
        );

    }

    // URL METHODS

    /**
     * Create valid URI
     * @param string $string
     * @return string
     */
    public static function createUri($string){

        $string = strtolower($string);

        $accents = array(
            'á', 'à', 'â', 'ã',
            'é', 'è', 'ê',
            'í', 'ì', 'î',
            'ó', 'ò', 'ô', 'õ',
            'ú', 'ù', 'û', 'ç'
        );

        $nonAccents = array(
            'a', 'a', 'a', 'a',
            'e', 'e', 'e',
            'i', 'i', 'i',
            'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'c'
        );

        $string = str_replace($accents, $nonAccents, $string);
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
        $string = preg_replace("/[\s-]+/", " ", $string);
        $string = preg_replace("/[\s_]/", "-", $string);
        $string = trim($string, '-');

        return $string;
    }

    /**
     * Create and format URL
     * @param string $url
     * @param string $location
     * @param string $query
     * @return string
     */
    public static function createUrl($url, $location = '', $query = NULL){

        $url = trim($url, '/');

        if( !empty($location) ){
            $url .= '/'. $location;
        }

        if( !empty($query) ){
            $url .= '/?'. str_replace('?', '', $query);
        }

        $url = preg_replace('/((?<!:)\/{2,4}\/?)/', '/', $url);

        return $url;
    }

    /**
     * Retrieve URL
     * @param string $location
     * @param string $query
     * @param boolean $ignorePath
     * @return string
     */
    public static function getUrl($location = '', $query = NULL, $ignorePath = TRUE){

        if( $location == '$this' ){
            $location = ($query == TRUE) ? self::getActiveUrl() : self::getQuery();
            $query = NULL;
            $ignorePath = TRUE;
        }

        $url = App::get('url');

        if( !$ignorePath
            AND self::getPath() ){
            $url .= '/'. trim(self::getPath(), '/');
        }

        return self::createUrl($url, $location, $query);
    }

    /**
     * Print URL
     * @param string $location
     * @param string $query
     * @param boolean $ignorePath
     * @return void
     */
    public static function url($location = '', $query = NULL, $ignorePath = TRUE){
        echo self::getUrl($location, $query, $ignorePath);
    }

    /**
     * Retrieve Path URL
     * @param string $location
     * @param string $query
     * @return string
     */
    public static function getPathUrl($location = '', $query = NULL){
        return self::getUrl($location, $query, FALSE);
    }

    /**
     * Print Path URL
     * @param string $location
     * @param string $query
     * @return void
     */
    public static function pathUrl($location = '', $query = NULL){
        echo self::getPathUrl($location, $query, FALSE);
    }

}