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
    public function __construct($parentConnection, $sql = NULL){
        $this->dbDriver     = $parentConnection;
        $this->pdo          = $this->dbDriver->getPDO();

        if(is_string($sql) && ($stmt = $this->pdo->prepare($sql)) !== FALSE) {
            $this->set_pdoStatement($stmt);
        }
        else {
            errorHandle::newError(__METHOD__."() Failed to prepare SQL '$sql'", errorHandle::DEBUG);
        }
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

        if(!$this->pdoStatement){
            errorHandle::newError(__METHOD__."() - pdoStatement has not been set! (set though set_pdoStatement())", errorHandle::DEBUG);
            return FALSE;
        }

        if(func_num_args()){
            if(func_num_args(0) instanceof keyValuePairs){
                $arg = func_num_args(0);
                foreach($arg as $key => $value){
                    if(is_object($value) or is_array($value)) $value = serialize($value);
                    $this->bindValue($key, $value, $this->determineParamType($value));
                }
            }else{
                for($n=0; $n<func_num_args(); $n++){
                    $arg = func_get_arg($n);
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

        if(!$this->pdoStatement){
            errorHandle::newError(__METHOD__."() - pdoStatement has not been set! (set though set_pdoStatement())", errorHandle::DEBUG);
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

        if(!$this->pdoStatement){
            errorHandle::newError(__METHOD__."() - pdoStatement has not been set! (set though set_pdoStatement())", errorHandle::DEBUG);
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

        $res = $this->pdoStatement->fetch($fetchMode);
        return (FALSE === $res) ? NULL : $res;
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

        // Validate, and convert string to index, the given $field
        $fieldNames = $this->fieldNames();
        if(is_numeric($field)){
            if($field < 0 or $field > (sizeof($fieldNames)-1)){
                errorHandle::newError(__METHOD__."() Requested field '$field' out of bounds for this result set.", errorHandle::DEBUG);
                return FALSE;
            }
        }else{
            $field = array_search($field, $fieldNames);
            if(FALSE === $field){
                errorHandle::newError(__METHOD__."() Requested field '$field' doesn't exist in result set.", errorHandle::DEBUG);
                return FALSE;
            }
        }

        // Return the requested field
        $res = $this->pdoStatement->fetchColumn($field);
        return (FALSE === $res) ? NULL : $res;
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
        while($n = $this->fetchField($field)){
            $rows[] = $n;
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