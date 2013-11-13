<?php
/**
 * EngineAPI Key-Value store
 * @package EngineAPI
 */

/**
 * EngineAPI Key-Value store
 *
 * @package EngineAPI\helpers
 */
class keyValuePairs extends ArrayObject{
    /**
     * Class constructor
     *
     * Will optionally take an array, object, or string and provide a key-value store for the data
     *
     * @param array|object|string|null $data
     */
    function __construct($data=NULL){
        switch(true){
            case !isset($data):
                parent::__construct();
                break;
            case is_array($data):
                parent::__construct($data);
                break;
            case is_object($data):
                parent::__construct($data);
                break;
            case is_string($data):
                // TODO (pretty much copy attrPairs())
                break;
            default:
                errorHandle::newError(__METHOD__."() - Unsupported data type! (only supports array, string, and object)", errorHandle::DEBUG);
                return FALSE;
        }
    }
} 