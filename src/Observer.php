<?php

namespace Orbital\Framework;

use \Orbital\Framework\App;

abstract class Observer {

    /**
     * Observers
     * @var array
     */
    public static $observers = array();

    /**
     * Add watches to event
     * @param string $event
     * @param string $callback
     * @return void
     */
    public static function on($event, $callback){

        if( !isset(self::$observers[ $event ]) ){
            self::$observers[ $event ] = array();
        }

        self::$observers[ $event ][] = $callback;
    }

    /**
     * Remove all watches on event
     * @param string $event
     * @return void
     */
    public static function off($event){
        unset(self::$observers[ $event ]);
    }

    /**
     * Fire event and process all watches
     * @param string $event
     * @param array $data
     * @return mixed
     */
    public static function fire($event, $data = array()){

        if( !isset(self::$observers[ $event ]) ){
            return $data;
        }

        $observers = self::$observers[ $event ];

        foreach( $observers as $observer ){
            App::runMethod($observer, $data);
        }

        return $data;
    }

}