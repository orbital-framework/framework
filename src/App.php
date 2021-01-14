<?php

namespace Orbital\Framework;

use \Exception;
use \Orbital\Framework\Entity;

abstract class App {

    /**
     * Singleton instances
     * @var mixed
     */
    private static $instances = NULL;

    /**
     * Configs
     * @var mixed
     */
    private static $config = NULL;

    /**
     * Modules
     * @var array
     */
    private static $modules = array();

    /**
     * Import all files from folder
     * @param string|array $directory
     * @param string $extension
     * @return void
     */
    public static function importFolder($directory, $extension = '.php'){

        if( is_array($directory) ){

            foreach( $directory as $value ){
                self::importFolder($value, $extension);
            }

            return;
        }

        $directory = rtrim($directory, DS);

        // Include files from directory
        foreach( glob($directory. DS. '*'. $extension) as $file ){
            if( file_exists($file) ){
                require_once $file;
            }
        }

    }

    /**
     * Import files
     * @param string $directory
     * @param string|array $file
     * @param string $extension
     * @return void
     */
    public static function importFile($directory, $file, $extension = '.php'){

        if( is_array($file) ){

            foreach( $file as $value ){
                self::importFile($directory, $value, $extension);
            }

            return;
        }

        $directory = rtrim($directory, DS);

        // Include the file
        if( file_exists($directory. DS. $file. $extension) ){
            require_once $directory. DS. $file. $extension;
        }

    }

    /**
     * Load module config on App
     * @param string $namespace
     * @return void
     */
    public static function loadModule($namespace){

        if( self::loadedModule($namespace) ){
            return;
        }

        $folder = str_replace('\\', DS, $namespace);
        $folder = trim($folder, DS);

        self::$modules[] = $namespace;
        self::importFolder(SRC. $folder. DS. 'Config');

    }

    /**
     * Return if module has already been loaded
     * @param string $namespace
     * @return boolean
     */
    public static function loadedModule($namespace){
        return in_array($namespace, self::$modules);
    }

    /**
     * Retrieve Config object
     * @return Entity
     */
    public static function getConfig(){

        if( self::$config == NULL ){
            self::$config = new Entity;
        }

        return self::$config;
    }

    /**
     * Retrieve object instances
     * @return Entity
     */
    public static function getInstances(){

        if( self::$instances == NULL ){
            self::$instances = new Entity;
        }

        return self::$instances;
    }

    /**
     * Set config data
     * @param string|array $key
     * @param string $value
     * @return void
     */
    public static function set($key, $value = NULL){

        $config = self::getConfig();

        if( is_array($key) ){
            foreach( $key as $k => $v ){
                $config->setData($k, $v);
            }
            return;
        }

        $config->setData($key, $value);
    }

    /**
     * Remove config data
     * @param string|array $key
     * @return void
     */
    public static function delete($key){

        $config = self::getConfig();

        if( is_array($key) ){
            foreach( $key as $item ){
                $config->unsetData($item);
            }
            return;
        }

        $config->unsetData($key);
    }

    /**
     * Retrieve config data
     * @param string $key
     * @param string $sub
     * @return mixed
     */
    public static function get($key){

        $config = self::getConfig();

        if( is_array($key) ){
            $new = array();
            foreach( $key as $item ){
                $new[$item] = $config->getData($item);
            }
            return $new;
        }

        return $config->getData($key);
    }

    /**
     * Instantiate class as singleton
     * @param string $class
     * @return object
     */
    public static function singleton($class){

        $instances = self::getInstances();

        if( !$instances->hasData($class) ){
            $instances->setData($class, new $class);
        }

        return $instances->getData($class);
    }

    /**
     * Run method
     * Accepts syntax: \Namespace\Class@method
     * @param string $method
     * @param array $parameters
     * @return object
     */
    public static function runMethod($method, $parameters = array()){

        if( !is_array($parameters) ){
            $parameters = array($parameters);
        }

        if( is_string($method)
            AND strpos($method, '@') !== FALSE ){

            $method = explode('@', $method);
            $class = $method[0];
            $classMethod = $method[1];

            if( !class_exists($class) ){
                throw new Exception($class. ' not found');
            }

            // If method not exists or is not public
            if( !is_callable(array($class, $classMethod)) ){
                throw new Exception($class. '::'. $classMethod. ' is not callable or not exists');
            }

            $class = self::singleton($class);

            return $class->$classMethod(...$parameters);
        }

        // If function not exists or is not public
        if( !is_callable($method) ){
            throw new Exception($method. ' is not callable or not exists');
        }

        return $method(...$parameters);
    }

}