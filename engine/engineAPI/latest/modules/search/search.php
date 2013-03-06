<?php
/**
 * EngineAPI search
 * @package EngineAPI\modules\search
 */

require_once dirname(__FILE__).'/searchSolr.php';

/**
 * EngineAPI search module
 * This object serves as a factory for multiple available back-end drivers
 * WARNING: This object is not finished, and has been abandoned for the moment
 *
 * @todo Finish the object and backend driver(s)
 * @package EngineAPI\modules\search
 */
class search{
	/**
	 * Array of available search client
	 * @var array
	 */
	private static $_clients = array();
    /**
     * Retrieve a fully instantiated SolrClient object
	 *
     * @static
     * @param string $provider
     * @param mixed $config
     *        Full array of config items
     *        A named EngineAPI config item
     *        URL to the solr search engine
     * @return searchSolr|mixed
     */
    public static function getClient($provider, $config=NULL){
        $engineVars = EngineAPI::$engineVars;
        $cfg = array();

        if(isset($config)){
            if(is_string($config)){
                if(isset($engineVars['solrSearch'][$config])){
                    $cfg = $engineVars['solrSearch'][$config];
                }
                if($configURL = parse_url($config)){
                    if(isset($configURL['host'])) $cfg['hostname'] = $configURL['host'];
                    if(isset($configURL['port'])) $cfg['port']     = $configURL['port'];
                    if(isset($configURL['path'])) $cfg['path']     = $configURL['path'];
                    if(isset($configURL['user'])) $cfg['login']    = $configURL['user'];
                    if(isset($configURL['pass'])) $cfg['password'] = $configURL['pass'];
                    if(isset($configURL['scheme']) and $configURL['scheme'] == 'https'){
                        $cfg['secure'] = TRUE;
                    }
                }
            }elseif(is_array($config)){
                $cfg = $config;
            }else{
                errorHandle::newError(__METHOD__."() - Malformed config sent!", errorHandle::HIGH);
            }
        }

        $cfgFinal = array_merge($cfg, (isset($engineVars['solrSearch']['default'])) ? $engineVars['solrSearch']['default'] : array() );
       $cfgSig = md5("$provider->".print_r($cfgFinal,TRUE));
        if(isset(self::$_clients[$cfgSig])){
            return self::$_clients[$cfgSig];
        }else{
            $fn = "search".ucfirst($provider);
            return self::$_clients[$cfgSig] = new $fn($cfgFinal);
        }
    }
}

/**
 * This is the common interface to ensure all search providers are providing the same interface
 * @package EngineAPI\modules\search
 */
interface searchProvider{
	// It's empty for the moment until we establish a 'baseline' for the searchSolr object
}