<?php

namespace Orbital\Framework;

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
     * @param mixed $position
     * @return void
     */
    public static function on($event, $callback, $position = NULL){

        if( !isset(self::$observers[ $event ]) ){
            self::$observers[ $event ] = array();
        }

        if( $position ){

            while( isset(self::$observers[ $event ][ $position ]) ){
                $position++;
            }

            self::$observers[ $event ][ $position ] = $callback;

        }else{

            self::$observers[ $event ][] = $callback;

        }

    }

    /**
     * Remove one or all watches on event
     * @param string $event
     * @param mixed $callback
     * @return void
     */
    public static function off($event, $callback = NULL){

        if( !isset(self::$observers[ $event ]) ){
            return;
        }

        if( !$callback ){
            unset(self::$observers[ $event ]);
            return;
        }

        $index = array_search($callback, self::$observers[ $event ]);

        if( $index !== FALSE ){
            unset(self::$observers[ $event ][ $index ]);
        }

    }

    /**
     * Fire event and process all watches
     * @param string $event
     * @param mixed $data
     * @return mixed
     */
    public static function fire($event, $data = NULL){

        if( !isset(self::$observers[ $event ]) ){
            return $data;
        }

        $observers = self::$observers[ $event ];
        ksort($observers);

        foreach( $observers as $observer ){
            App::runMethod($observer, $data);
        }

        return $data;
    }

}
