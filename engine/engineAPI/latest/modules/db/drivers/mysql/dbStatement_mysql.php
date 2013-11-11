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
    public function __construct($parentConnection, $sql, $params=NULL){
        $this->dbDriver     = $parentConnection;
        $this->pdo          = $this->dbDriver->getPDO();
        $this->pdoStatement = $this->pdo->prepare($sql);
        if(isset($params)) call_user_func_array(array($this, 'execute'), $params);
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
                if(is_array($arg) and sizeof($arg) == 2){
                    if(in_array($arg[0], $this->pdoParamTypes)){
                        $args[$n] = $arg[1];
                        $this->bindParam($n, $args[$n], $arg[0]);
                    }
                }else{
                    $args[$n] = $arg;
                    switch(gettype($arg)){
                        case 'boolean':
                            $this->bindParam($n, $args[$n], PDO::PARAM_BOOL);
                            break;
                        case 'integer':
                            $this->bindParam($n, $args[$n], PDO::PARAM_INT);
                            break;
                        case 'string':
                        case 'double':
                            $this->bindParam($n, $args[$n], PDO::PARAM_STR);
                            break;
                        case 'array':
                        case 'object':
                            $args[$n] = serialize($args[$n]);
                            $this->bindParam($n, $args[$n], PDO::PARAM_STR);
                            break;
                        case 'resource':
                            errorHandle::newError(__METHOD__."() - Unsupported param type! (can't store a resource)", errorHandle::DEBUG);
                            return FALSE;
                        case 'null':
                            $this->bindParam($n, $args[$n], PDO::PARAM_NULL);
                            break;
                        default:
                            errorHandle::newError(__METHOD__."() - Unsupported param type! (unknown type)", errorHandle::DEBUG);
                            return FALSE;
                    }
                }
            }
        }

        // Time to execute the prepared statement!
        return $this->pdoStatement->execute();
    }

    public function bindParam($param, &$value, $type=PDO::PARAM_STR, $length=NULL, $options=array()){
        if($this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement already executed)", errorHandle::DEBUG);
            return FALSE;
        }

        if(isset($options)) return $this->pdoStatement->bindParam($param, $value, $type, $options);
        if(isset($length))  return $this->pdoStatement->bindParam($param, $value, $type);
        return $this->pdoStatement->bindParam($param, $value);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fieldCount(){
        if(!$this->isExecuted()){
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);
            return FALSE;
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
            return FALSE;
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

        // We need to convert to the field number
        if(is_string($field)) $field = array_search($field, $this->fieldNames());
        // Return the requested field
        $rows = array();
        while($field = $this->pdoStatement->fetchColumn($field)){
            $rows[] = $field;
        }
        return $rows;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function errorCode(){
        return $this->pdoStatement->errorCode();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
  public function errorMsg(){
      $errorMsg = $this->pdoStatement->errorInfo();
      return $errorMsg[0];
  }
} 