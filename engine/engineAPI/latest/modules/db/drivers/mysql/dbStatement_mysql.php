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
class dbStatement_mysql extends dbStatement {

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function __construct($parentConnection, $sql = NULL) {
        $this->dbDriver = $parentConnection;
        $this->pdo      = $this->dbDriver->getPDO();

        if (is_string($sql) && ($stmt = $this->pdo->prepare($sql)) !== FALSE) {
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
    public function execute() {
        if ($this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement already executed)", errorHandle::DEBUG);

            return FALSE;
        }

        if (!$this->pdoStatement) {
            errorHandle::newError(__METHOD__."() - pdoStatement has not been set! (set though set_pdoStatement())", errorHandle::DEBUG);

            return FALSE;
        }

        if (func_num_args()) {
            if (func_num_args(0) instanceof keyValuePairs) {
                $arg = func_num_args(0);
                foreach ($arg as $key => $value) {
                    if (!$this->bindValue($key, $value, $this->determineParamType($value))) {
                        errorHandle::newError(__METHOD__."() Failed to bind value of type ".gettype($value), errorHandle::DEBUG);
                        return FALSE;
                    }
                }
            }
            else {
                for ($n = 0; $n < func_num_args(); $n++) {
                    $arg = func_get_arg($n);
                    if (!$this->bindValue($n + 1, $arg, $this->determineParamType($arg))) {
                        errorHandle::newError(__METHOD__."() Failed to bind value of type ".gettype($arg), errorHandle::DEBUG);
                        return FALSE;
                    }
                }
            }
        }

        // Time to execute the prepared statement!
        $start              = microtime(TRUE);
        $result             = $this->pdoStatement->execute();
        $stop               = microtime(TRUE);
        $this->executedTime = $stop - $start;
        $this->executedAt   = new DateTime;

        return $result;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function bindParam($param, &$value, $type = PDO::PARAM_STR, $length = NULL, $options = NULL) {
        if ($this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement already executed)", errorHandle::DEBUG);

            return FALSE;
        }

        if (!$this->pdoStatement) {
            errorHandle::newError(__METHOD__."() - pdoStatement has not been set! (set though set_pdoStatement())", errorHandle::DEBUG);

            return FALSE;
        }

        if (isset($options)) return $this->pdoStatement->bindParam($param, $value, $type, $length, $options);
        if (isset($length)) return $this->pdoStatement->bindParam($param, $value, $type, $length);
        if (isset($type)) return $this->pdoStatement->bindParam($param, $value, $type);

        return $this->pdoStatement->bindParam($param, $value);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function bindValue($param, $value, $type = PDO::PARAM_STR, $length = NULL) {
        if ($this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement already executed)", errorHandle::DEBUG);

            return FALSE;
        }

        if (!$this->pdoStatement) {
            errorHandle::newError(__METHOD__."() - pdoStatement has not been set! (set though set_pdoStatement())", errorHandle::DEBUG);

            return FALSE;
        }

        // Make sure the value is encoded as needed
        if($this->dbDriver->autoEncode) $value = $this->encodeObject($value);

        // Bind the value
        if (isset($length)) return $this->pdoStatement->bindValue($param, $value, $type, $length);
        if (isset($type)) return $this->pdoStatement->bindValue($param, $value, $type);

        return $this->pdoStatement->bindValue($param, $value);
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fieldCount() {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return 0;
        }

        return $this->pdoStatement->columnCount();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fieldNames() {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return array();
        }

        if (!isset($this->fieldNames)) {
            for ($n = 0; $n < $this->fieldCount(); $n++) {
                $field              = $this->pdoStatement->getColumnMeta($n);
                $this->fieldNames[] = $field['name'];
            }
        }

        return $this->fieldNames;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function rowCount() {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return FALSE;
        }

        return $this->pdoStatement->rowCount();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function affectedRows() {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return FALSE;
        }

        return $this->pdoStatement->rowCount();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function insertId() {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return FALSE;
        }

        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetch($fetchMode = PDO::FETCH_ASSOC) {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return FALSE;
        }

        // Get the result set
        $row = $this->pdoStatement->fetch($fetchMode);

        // Return the result set
        if ($row === FALSE) {
            return NULL;
        }
        else {
            // @TODO Remove in_array() call with better decode logic
            return $this->dbDriver->autoEncode && in_array($fetchMode, array(PDO::FETCH_ASSOC, PDO::FETCH_NUM))
                ? array_map(array($this, 'decodeObject'), $row)
                : $row;
        }
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetchAll($fetchMode = PDO::FETCH_ASSOC) {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return FALSE;
        }

        $rows = array();
        while ($row = $this->fetch($fetchMode)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetchField($field = 0) {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return FALSE;
        }

        // Validate, and convert string to index, the given $field
        $fieldNames = $this->fieldNames();
        if (is_numeric($field)) {
            if ($field < 0 or $field > (sizeof($fieldNames) - 1)) {
                errorHandle::newError(__METHOD__."() Requested field '$field' out of bounds for this result set.", errorHandle::DEBUG);

                return FALSE;
            }
        }
        else {
            $field = array_search($field, $fieldNames);
            if (FALSE === $field) {
                errorHandle::newError(__METHOD__."() Requested field '$field' doesn't exist in result set.", errorHandle::DEBUG);

                return FALSE;
            }
        }

        // Get and return the requested field
        $value = $this->pdoStatement->fetchColumn($field);
        if ($value === FALSE) {
            return NULL;
        }
        else {
            return $this->dbDriver->autoEncode
                ? $this->decodeObject($value)
                : $value;
        }
    }

    /**
     * {@inheritdoc}
     * @author David Gersting
     */
    public function fetchFieldAll($field = 0) {
        if (!$this->isExecuted()) {
            errorHandle::newError(__METHOD__."() - Method not available! (statement not executed yet)", errorHandle::DEBUG);

            return FALSE;
        }

        $rows = array();
        while ($row = $this->fetchField($field)) {
            $rows[] = $row;
        }

        return $rows;
    }

    // -----------------------------------------------------------------------------------------------------------------

    private function encodeObject($input) {
        return (is_array($input) || is_object($input))
            ? db::STORED_OBJECT_MARKER.serialize($input)
            : $input;
    }

    private function decodeObject($input) {
        if (!is_string($input) || 0 !== strpos($input, db::STORED_OBJECT_MARKER)) return $input;
        $input = str_replace(db::STORED_OBJECT_MARKER, '', $input);

        return (FALSE !== ($output = unserialize($input))) ? $output : FALSE;
    }
} 