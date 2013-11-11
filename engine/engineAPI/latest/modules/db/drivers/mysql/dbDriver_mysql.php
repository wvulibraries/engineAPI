<?php
/**
 * Database driver
 *
 * @package EngineAPI\modules\db\drivers
 */

// Load any related MySQL files
require_once __DIR__.DIRECTORY_SEPARATOR.'dbStatement_mysql.php'{}

/**
 * MySQL database driver
 *
 * @package EngineAPI\modules\db\drivers\mysql
 */
class dbDriver_mysql extends dbDriver{
    const PDO_DRIVER      = 'mysql';
    const DEFAULT_HOST    = 'localhost';
    const DEFAULT_PORT    = '3306';
    const DEFAULT_USER    = 'root';
    const DEFAULT_PASS    = '';
    const DEFAULT_CHARSET = 'utf8';

    /**
     * Construct a MySQL
     *
     * @author David Gersting
     * @param array $params
     */
    public function __construct($params=array()){
        if($params instanceof PDO){
            $this->pdo = $params;
        }elseif(is_string($params)){
            $this->pdo = new PDO($params);
        }elseif(is_array($params)){
            if(isset($params['pdo']) and $params['pdo'] instanceof PDO){
                $this->pdo = $params['pdo'];
            }elseif(isset($params['dsn'])){
                $dsn = $this->extractParam('dsn', $params);
                $this->pdo = new PDO($dsn, $params);
            }else{
                // Build the DSN string
                $query            = array();
                $query['dbname']  = $this->extractParam('dbName', $params);
                $query['charset'] = $this->extractParam('charset', $params, self::DEFAULT_CHARSET);

                // A socket file will take precedence over a host:port pair
                if(isset($params['socket'])){
                    $query['unix_socket'] = $this->extractParam('socket', $params);
                }else{
                    $query['host'] = $this->extractParam('host', $params, self::DEFAULT_HOST);
                    $query['port'] = $this->extractParam('port', $params, self::DEFAULT_PORT);
                }

                // Figure out the user/pass
                $user = $this->extractParam('user', $params, self::DEFAULT_USER);
                $pass = $this->extractParam('pass', $params, self::DEFAULT_PASS);

                // Create the PDO object!
                $dsn = $this->buildDSN(self::PDO_DRIVER, $query);
                $this->pdo = new PDO($dsn, $user, $pass, $params);
            }
        }else{
            errorHandle::newError(__METHOD__."() - Unknown params passed!", errorHandle::DEBUG);
            return FALSE;
        }
    }

    /**
     * Class destructor
     * @author David Gersting
     */
    public function __destruct(){
        $this->destroy();
    }


    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function query($sql, array $params=array()){
        if($this->isReadOnly() and !$this->chkReadOnlySQL($sql)){
            errorHandle::newError(__METHOD__."() - Driver is in read-only mode!", errorHandle::DEBUG);
            return FALSE;
        }
        return new dbStatement_mysql($this, $sql, $params);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function exec($sql){
        if($this->isReadOnly() and !$this->chkReadOnlySQL($sql)){
            errorHandle::newError(__METHOD__."() - Driver is in read-only mode!", errorHandle::DEBUG);
            return FALSE;
        }

        return $this->exec($sql);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function escape($var){
        return $this->pdo->quote($var);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function beginTransaction(){
        $this->transNestingCounter++;
        return $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function commit(){
        if(!$this->inTransaction() or !$this->transNestingCounter){
            errorHandle::newError(__METHOD__."() Cannot commit when not in a transaction!", errorHandle::DEBUG);
            return FALSE;
        }

        $this->transNestingCounter--;
        if(!$this->transNestingCounter){
            if(!$this->transRollbackOnly){
                return $this->pdo->commit();
            }else{
                $this->transRollbackOnly = FALSE;
                return $this->pdo->rollBack();
            }
        }
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function rollback(){
        if(!$this->inTransaction() or !$this->transNestingCounter){
            errorHandle::newError(__METHOD__."() Cannot rollback when not in a transaction!", errorHandle::DEBUG);
            return FALSE;
        }

        $this->transNestingCounter--;
        if(!$this->transNestingCounter){
            return $this->pdo->rollBack();
        }else{
            $this->transRollbackOnly = TRUE;
            return TRUE;
        }
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function inTransaction(){
        return $this->pdo->inTransaction();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function readOnly($newState=TRUE){
        $this->readOnlyMode = (bool)$newState;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function destroy(){
        // If we're in a transaction, roll it back!
        while($this->inTransaction()){
            $this->rollback();
        }

        // Disconnect from database
        $this->pdo = NULL;

        // De-register this object
        db::unregisterObject($this);
    }
}