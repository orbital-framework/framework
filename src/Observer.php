<?php
declare(strict_types=1);

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
     * @param int $position
     * @return void
     */
    public static function on(string $event, string $callback, int $position = 0): void {

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
     * @param string $callback
     * @return void
     */
    public static function off(string $event, string $callback = ''): void {

        if( !isset(self::$observers[ $event ]) ){
            return;
        }

        if( !$callback ){
            unset(self::$observers[ $event ]);
            return;
        }

        $index = array_search($callback, self::$observers[ $event ]);

        if( $index !== false ){
            unset(self::$observers[ $event ][ $index ]);
        }

    }

    /**
     * Fire event and process all watches
     * @param string $event
     * @param array|Entity $data
     * @return void
     */
    public static function fire(string $event, array|Entity $data = array()): void {

        if( !isset(self::$observers[ $event ]) ){
            return;
        }

        $observers = self::$observers[ $event ];
        ksort($observers);

        foreach( $observers as $observer ){
            App::runMethod($observer, $data);
        }

    }

}
