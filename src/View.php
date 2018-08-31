<?php

namespace Orbital\Framework;

abstract class View {

    /**
     * View data
     * @var array
     */
    private static $data = array();

    /**
     * View level data
     * @var integer
     */
    private static $level = -1;

    /**
     * Define global variables from view level data
     * Also require view file if need
     * @return void
     */
    public static function process($file = NULL){

        $exists = isset( self::$data[ self::$level ] );

        if( $exists ){
            $variables = self::$data[ self::$level ];

            foreach( $variables as $key => $value ){
                global $$key;
                $$key = $value;
            }
        }

        if( $file ){
            require $file;
        }

    }

    /**
     * Retrieve view
     * @param string $view
     * @param array $data
     * @return string
     */
    public static function get($view, $data = array()){

        if( $data ){
            self::$level = self::$level + 1;
            self::$data[ self::$level ] = (array) $data;
        }

        $file = SRC. "{$view}.php";

        // Parse view
        ob_start();

            self::process($file);

            if( $data ){

                unset(self::$data[ self::$level ]);
                self::$level = self::$level - 1;
                self::process(NULL);

            }

            $view = ob_get_contents();

        ob_end_clean();

        return $view;
    }

    /**
     * Render view
     * @param string|array $view
     * @param array $data
     * @return void
     */
    public static function render($view, $data = array()){

        if( is_array($view) ){

            foreach( $view as $item ){
                self::render($item, $data);
            }

            return;
        }

        echo self::get($view, $data);
    }

}