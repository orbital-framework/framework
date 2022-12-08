<?php
declare(strict_types=1);

namespace Orbital\Framework;

use \ArrayAccess;
use \Exception;

class Entity implements ArrayAccess {

    // Key normalization model
    const NORMALIZE_CAMEL_CASE = 'camel';
    const NORMALIZE_SNAKE_CASE = 'snake';
    const NORMALIZE_KEBAB_CASE = 'kebab';

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
     * Normalization method
     * @var string
     */
    protected $_normalize = self::NORMALIZE_SNAKE_CASE;
    
    /**
     * CONSTRUCTOR
     * @param string|null $normalize
     * @return void
     */
    public function __construct(string $normalize = null) {

        if( !is_null($normalize) ){
            $this->_normalize = $normalize;
        }

    }

    /**
     * Normalize key name for object mapping
     * @param string $name
     * @return string
     */
    protected function normalizeKeyName(string $name): string {

        switch( $this->_normalize ){
            case self::NORMALIZE_SNAKE_CASE:
                $name = preg_replace('/(.)([-_\s]+)/', '$1_', $name);
                $name = preg_replace('/(.)([A-Z])/', '$1_$2', $name);
                $name = strtolower($name);
            break;
            case self::NORMALIZE_KEBAB_CASE:
                $name = preg_replace('/(.)([-_\s]+)/', '$1-', $name);
                $name = preg_replace('/(.)([A-Z])/', '$1-$2', $name);
                $name = strtolower($name);
            break;
            case self::NORMALIZE_CAMEL_CASE:
                $name = preg_replace('/(.)([-_\s]+)/', '$1 ', $name);
                $name = lcfirst(ucwords($name));
                $name = str_replace(' ', '', $name);
            break;
        }

        return $name;
    }

    /**
     * Set/Get attribute wrapper
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args): mixed {

        $key = $this->normalizeKeyName( substr($method, 3) );
        $action = substr($method, 0, 3);

        switch( $action ){
            case 'get' :
                $data = $this->getData(
                    $key, isset($args[0]) ? $args[0] : null);
                return $data;

            case 'set' :
                $result = $this->setData(
                    $key, isset($args[0]) ? $args[0] : null);
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
    public function hasData(string $key = ''): bool {

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
    public function getData(string $key = ''): mixed {

        $key = $this->normalizeKeyName($key);

        if( $key === '' ){
            return $this->_data;
        }

        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    /**
     * Set object data
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setData(string $key, mixed $value): self {

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
     * @return self
     */
    public function pushData(mixed $value): self {

        $this->_data[] = $value;
        $this->_changes[] = $value;

        return $this;
    }

    /**
     * Push data to the object
     * @param array $data
     * @return self
     */
    public function addData(array $data): self {
        foreach( $data as $key => $value ){
            $this->setData($key, $value);
        }
        return $this;
    }

    /**
     * Unset data on object
     * @param string $key
     * @return self
     */
    public function unsetData(string $key): self {

        $key = $this->normalizeKeyName($key);

        if( isset($this->_data[$key]) ){
            $this->_original[$key] = $this->_data[$key];
            $this->_changes[$key] = null;
            unset($this->_data[$key]);
        }

        return $this;
    }

    /**
     * Clean data on object
     * @return self
     */
    public function cleanData(): self {
        $this->_data = array();
        return $this->cleanChanges();
    }

    /**
     * Clean data changes on object
     * @return self
     */
    public function cleanChanges(): self {

        $this->_original = array();
        $this->_changes = array();

        return $this;
    }

    /**
     * Retrieve object data original
     * @return array
     */
    public function getOriginal(): array {
        return $this->_original;
    }

    /**
     * Retrieve object data changes
     * @return array
     */
    public function getChanges(): array {
        return $this->_changes;
    }

    /**
     * Convert object attributes to array
     * @param array $attributes
     * @return array
     */
    public function __toArray(array $attributes = array()): array {

        if( empty($attributes) ){
            $data = $this->_data;
        }else{

            $data = array();
            foreach( $attributes as $attribute ){
                if( isset($this->_data[$attribute]) ){
                    $data[$attribute] = $this->_data[$attribute];
                } else {
                    $data[$attribute] = null;
                }
            }

        }

        foreach( $data as $key => $item ){
            if( $item instanceof Entity ){
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
    public function toArray(array $attributes = array()): array {
        return $this->__toArray($attributes);
    }

    /**
     * Implementation of ArrayAccess::offsetSet()
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->_data[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess::offsetExists()
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool {
        return isset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetUnset()
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void {
        unset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess::offsetGet()
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }

}