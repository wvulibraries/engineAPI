<?php
/**
 * Database statement
 *
 * @package EngineAPI\modules\db\statements
 */

/**
 * MySQL database statement
 *
 * @package EngineAPI\modules\db\statements\mysql
 */
class dbStatement_mysql extends dbStatement{

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function __construct($parentConnection, $sql){
        $this->dbDriver     = $parentConnection;
        $this->pdo          = $this->dbDriver->getPDO();
        $this->pdoStatement = ($sql instanceof PDOStatement) ? $sql : $this->pdo->prepare($sql);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function execute(){
        if($this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement already executed)", errorHandle::DEBUG);
            return FALSE;
        }

        if(func_num_args()){
            $args = array();
            for($n=0; $n<func_num_args(); $n++){
                $arg = func_get_arg($n);

                if($arg instanceof keyValuePairs){
                    foreach($arg as $key => $value){
                        if(is_object($value) or is_array($value)) $value = serialize($value);
                        $this->bindValue($key, $value, $this->determineParamType($value));
                    }
                }else{
                    if(is_object($arg) or is_array($arg)) $arg = serialize($arg);
                    $this->bindValue($n+1, $arg, $this->determineParamType($arg));
                }
            }
        }

        // Time to execute the prepared statement!
        $start  = microtime(TRUE);
        $result = $this->pdoStatement->execute();
        $stop   = microtime(TRUE);
        $this->executedTime = $stop - $start;
        $this->executedAt   = new DateTime;

        return $result;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function bindParam($param, &$value, $type=PDO::PARAM_STR, $length=NULL, $options=NULL){
        if($this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement already executed)", errorHandle::DEBUG);
            return FALSE;
        }

        if(isset($options)) return $this->pdoStatement->bindParam($param, $value, $type, $length, $options);
        if(isset($length))  return $this->pdoStatement->bindParam($param, $value, $type, $length);
        if(isset($type))    return $this->pdoStatement->bindParam($param, $value, $type);
        return $this->pdoStatement->bindParam($param, $value);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function bindValue($param, $value, $type=PDO::PARAM_STR, $length=NULL){
        if($this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement already executed)", errorHandle::DEBUG);
            return FALSE;
        }

        if(isset($length)) return $this->pdoStatement->bindValue($param, $value, $type, $length);
        if(isset($type))   return $this->pdoStatement->bindValue($param, $value, $type);
        return $this->pdoStatement->bindValue($param, $value);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fieldCount(){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return 0;
        }
        return $this->pdoStatement->columnCount();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fieldNames(){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return array();
        }

        if(!isset($this->fieldNames)){
            for($n=0; $n<$this->fieldCount(); $n++){
                $field = $this->pdoStatement->getColumnMeta($n);
                $this->fieldNames[] = $field['name'];
            }
        }
        return $this->fieldNames;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function rowCount(){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
        }
        return $this->pdoStatement->rowCount();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function affectedRows(){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
        }
        return $this->pdoStatement->rowCount();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function insertId(){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
        }
        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetch($fetchMode=PDO::FETCH_ASSOC){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
        }

        return $this->pdoStatement->fetch($fetchMode);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetchAll($fetchMode=PDO::FETCH_ASSOC){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
        }

        return $this->pdoStatement->fetchAll($fetchMode);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetchField($field=0){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
        }

        // We need to convert to the field number
        if(is_string($field)) $field = array_search($field, $this->fieldNames());
        // Return the requested field
        return $this->pdoStatement->fetchColumn($field);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetchFieldAll($field=0){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
        }

        $rows = array();
        while($field = $this->fetchField($field)){
            $rows[] = $field;
        }
        return $rows;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function sqlState(){
        return $this->pdoStatement->errorCode();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function errorCode(){
        $errorMsg = $this->pdoStatement->errorInfo();
        return $errorMsg[1];
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
  public function errorMsg(){
      $errorMsg = $this->pdoStatement->errorInfo();
      return $errorMsg[2];
  }
} 