<?php

namespace Orbital\Framework;

use \ArrayAccess;
use \Exception;

class AppObject implements ArrayAccess {

    /**
     * Object data
     * @var array
     */
    protected $_data = array();

    /**
     * Object original data
     * @var array
     */
    protected $_original = array();

    /**
     * Object data changes
     * @var array
     */
    protected $_changes = array();

    /**
     * Normalize key name for object mapping
     * @param string $name
     * @return string
     */
    protected function normalizeKeyName($name){

        $name = preg_replace('/(.)([A-Z])/', "$1_$2", $name);
        $result = strtolower( $name );

        return $result;
    }

    /**
     * Set/Get attribute wrapper
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args){

        $key = $this->normalizeKeyName( substr($method, 3) );
        $action = substr($method, 0, 3);

        switch( $action ){
            case 'get' :
                $data = $this->getData(
                    $key, isset($args[0]) ? $args[0] : NULL);
                return $data;

            case 'set' :
                $result = $this->setData(
                    $key, isset($args[0]) ? $args[0] : NULL);
                return $result;

            case 'uns' :
                $result = $this->unsetData($key);
                return $result;

            case 'has' :
                $result = $this->hasData($key);
                return $result;
        }

        $message = 'Invalid method '. get_class($this). '::'. $method. '()';
        throw new Exception($message);

    }

    /**
     * Check if has data on object
     * @param string $key
     * @return boolean
     */
    public function hasData($key = ''){

        $key = $this->normalizeKeyName($key);

        if( empty($key) || !is_string($key) ){
            return !empty($this->_data);
        }

        return array_key_exists($key, $this->_data);
    }

    /**
     * Retrieve data from object
     * @param string $key
     * @return mixed
     */
    public function getData($key = ''){

        $key = $this->normalizeKeyName($key);

        if( $key === '' ){
            return $this->_data;
        }

        return isset($this->_data[$key]) ? $this->_data[$key] : NULL;
    }

    /**
     * Set object data
     * @param string $key
     * @param mixed $value
     * @return object
     */
    public function setData($key, $value){

        $key = $this->normalizeKeyName($key);

        if( isset($this->_data[$key]) ){
            if( $this->_data[$key] !== $value ){
                $this->_original[$key] = $this->_data[$key];
                $this->_changes[$key] = $value;
            }
        }else{
            $this->_changes[$key] = $value;
        }

        $this->_data[$key] = $value;

        return $this;
    }

    /**
     * Push object data
     * @param mixed $value
     * @return object
     */
    public function pushData($value){

        $this->_data[] = $value;
        $this->_changes[] = $value;

        return $this;
    }

    /**
     * Push data to the object
     * @param array $data
     * @return object
     */
    public function addData($data){
        foreach( $data as $key => $value ){
            $this->setData($key, $value);
        }
        return $this;
    }

    /**
     * Unset data on object
     * @param string $key
     * @return object
     */
    public function unsetData($key){

        $key = $this->normalizeKeyName($key);

        if( isset($this->_data[$key]) ){
            $this->_original[$key] = $this->_data[$key];
            $this->_changes[$key] = NULL;
            unset($this->_data[$key]);
        }

        return $this;
    }

    /**
     * Clean data on object
     * @param string $key
     * @return object
     */
    public function cleanData(){
        $this->_data = array();
        return $this->cleanChanges();
    }

    /**
     * Clean data changes on object
     * @param string $key
     * @return object
     */
    public function cleanChanges(){

        $this->_original = array();
        $this->_changes = array();

        return $this;
    }

    /**
     * Retrieve object data original
     * @return array
     */
    public function getOriginal(){
        return $this->_original;
    }

    /**
     * Retrieve object data changes
     * @return array
     */
    public function getChanges(){
        return $this->_changes;
    }

    /**
     * Convert object attributes to array
     * @param array $attributes
     * @return array
     */
    public function __toArray($attributes = array()){

        if( empty($attributes) ){
            $data = $this->_data;
        }else{

            $data = array();
            foreach( $attributes as $attribute ){
                if( isset($this->_data[$attribute]) ){
                    $data[$attribute] = $this->_data[$attribute];
                } else {
                    $data[$attribute] = NULL;
                }
            }

        }

        foreach( $data as $key => $item ){
            if( $item instanceof AppObject ){
                $item = $item->toArray();
                $data[$key] = $item;
            }
        }

        return $data;
    }

    /**
     * Public wrapper for __toArray
     * @param array $attributes
     * @return array
     */
    public function toArray($attributes = array()){
        return $this->__toArray($attributes);
    }

    /**
     * Implementation of ArrayAccess::offsetSet()
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value){
        $this->_data[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess::offsetExists()
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset){
        return isset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetUnset()
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset){
        unset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetGet()
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset){
        return isset($this->_data[$offset]) ? $this->_data[$offset] : NULL;
    }

}