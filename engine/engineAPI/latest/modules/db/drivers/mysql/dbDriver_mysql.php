<?php
/**
 * Database driver
 *
 * @package EngineAPI\modules\db\drivers
 */

// Load any related MySQL files
require_once __DIR__.DIRECTORY_SEPARATOR.'dbStatement_mysql.php';

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
     * @var DateTime
     */
    private $createdAt;
    /**
     * @var array
     */
    private $debugInfo;

    /**
     * Construct a MySQL
     *
     * @author David Gersting
     * @param array $params
     */
    public function __construct($params=array()){
        if($params instanceof PDO){
            $this->pdo = $params;
            $this->debugInfo['Connected to'] = $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        }elseif(is_string($params)){
            $this->pdo = new PDO($params);
            $this->debugInfo['Connected to'] = $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        }elseif(is_array($params)){
            if(isset($params['pdo']) and $params['pdo'] instanceof PDO){
                $this->pdo = $params['pdo'];
                $this->debugInfo['Connected to'] = $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
            }else{
                // Build the DSN string
                if(isset($params['dsn'])){
                    $dsn = $params['dsn'];
                }else{
                    $query            = array();
                    $query['dbname']  = $this->extractParam('dbName', $params);
                    $query['charset'] = $this->extractParam('charset', $params, self::DEFAULT_CHARSET);
                    if(isset($params['socket'])){
                        $query['unix_socket'] = $this->extractParam('socket', $params);
                    }else{
                        $query['host'] = $this->extractParam('host', $params, self::DEFAULT_HOST);
                        $query['port'] = $this->extractParam('port', $params, self::DEFAULT_PORT);
                    }
                    $dsn = $this->buildDSN(self::PDO_DRIVER, $query);
                }

                // Figure out the user/pass
                $user = $this->extractParam('user', $params, self::DEFAULT_USER);
                $pass = $this->extractParam('pass', $params, self::DEFAULT_PASS);

                // Create the PDO object!
                $this->pdo = new PDO($dsn, $user, $pass, $params);
                $this->debugInfo['DSN'] = $dsn;
                $this->debugInfo['Connected to'] = $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
            }
        }else{
            errorHandle::newError(__METHOD__."() - Unknown params passed!", errorHandle::DEBUG);
            return FALSE;
        }

        $this->createdAt = new DateTime;
    }

    /**
     * Class destructor
     * @author David Gersting
     */
    public function __destruct(){
        $this->destroy();
    }

    public function __toString(){
        $debugEnv = TRUE; // TODO: switch to EngineAPI environments (when they are done)
        if($debugEnv){
            $header = sprintf('%s(%s)', __CLASS__, $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
            $width  = strlen($header) + 20;
            $return = str_repeat("=", $width)."\n";
            $return .= str_pad($header, $width, ' ', STR_PAD_BOTH)."\n";
            $return .= str_repeat("=", $width)."\n";
            $return .= sprintf("Opened at: %s\n", $this->createdAt->format('g:i:s a'));
            $return .= sprintf("In transaction: %s\n", ($this->inTransaction() ? sprintf('Yes (depth: %s)', $this->transNestingCounter) : 'No'));
            if($this->inTransaction()) $return .= sprintf("Rollback only: %s\n", ($this->transRollbackOnly ? 'Yes' : 'No'));
            if(is_array($this->debugInfo) and sizeof($this->debugInfo)){
                $return .= str_repeat("-", $width)."\n";
                foreach($this->debugInfo as $label => $data){
                    $return .= "$label: $data\n";
                }
            }
            $return .= str_repeat("=", $width)."\n";

            return $return;
        }else{
            parent::__toString();
        }
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function query($sql, $params=NULL){
        if($this->isReadOnly() and !$this->chkReadOnlySQL($sql)){
            errorHandle::newError(__METHOD__."() - Driver is in read-only mode!", errorHandle::DEBUG);
            return FALSE;
        }

        $stmt = new dbStatement_mysql($this, $sql);
        switch(true){
            case !isset($params):
                $stmt->execute();
                return $stmt;
            case !$params:
                return $stmt;
            case (is_array($params) and sizeof($params)):
                call_user_func_array(array($stmt, 'execute'), $params);
                return $stmt;
        }
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
        return $this->transNestingCounter == 1
            ? $this->pdo->beginTransaction()
            : TRUE;
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