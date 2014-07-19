## Connection params
### Before
`$engine->dbConnect()` supports the following connection params:
  - username
  - password
  - port
  - server
  - database

### After
*Params are highly dependant on the underlying database driver being used. (See: [PDO Drivers](http://us3.php.net/manual/en/pdo.drivers.php))

`db::create()` Uses, for MySQL, the following connection params:
  - username *Always accepted (driver agnostic)*
  - password *Always accepted (driver agnostic)*
  - host
  - port
  - dbName
  - unix_socket
  - charset

------------------------------------

## Create a new connection (not preserved)
### Before
```
$engine->dbConnect("username","foo");  
$engine->dbConnect("password","bar");  
$db = $engine->dbConnect("database","testing");
```
### After
```
$options = array(
    'username' => 'foo',
    'password' => 'bar',
    'dbName'   => 'testing'
);
$db = db::create('mysql', $options);
```

------------------------------------

## Create a new connection (preserved)
### Before
*Connection is hard-coded to `$engine->openDB`*
```
$engine->dbConnect("username","foot");
$engine->dbConnect("password","bar");
$engine->dbConnect("database","testing", FALSE);
$engine->openDB->...
```
### After
*Connection can be named anything. In this case: `appDB`*
```
$options = array(
    'username' => 'foo',
    'password' => 'bar',
    'dbName'   => 'testing'
);
db::create('mysql', $options, 'appDB');
db::get('appDB')->...
```

------------------------------------

## Manualy escape values
### Before
```
$after = $engine->openDB->escape($before);
```
### After
```
$after = db::get('appDB')->escape($before);
```

------------------------------------

## Perform SELECT
### Before
```
$sqlResult = $engine->openDB->query('SELECT ...');
```
### After
```
$sqlResult = db::get('appDB')->query('SELECT ...');
```

------------------------------------

## Perform INSERT
### Before
```
$sql = sprintf("INSERT INTO foo VALUES('%s','%s')", 
  $engine->openDB->escape('foo'),
  $engine->openDB->escape('bar'),
)
$sqlResult = $engine->openDB->query($sql);
```
### After
*Data is automatly bound to the prepared statement, no manual escaping is needed.<br>Escaping won't break the SQL (as the data never touches it), but you will get escaped content back out.*
```
$data = array('foo','bar');
$sqlResult = db::get('appDB')->query('INSERT INTO foo VALUES(?,?)', $data);
```

------------------------------------

## Perform UPDATE
### Before
```
$sql = sprintf("UPDATE test SET foo='%s' WHERE bar='%s'", 
  $engine->openDB->escape('dog'),
  $engine->openDB->escape('cat'),
)
$sqlResult = $engine->openDB->query($sql);
```
### After
*Data is automatly bound to the prepared statement, no manual escaping is needed.<br>Escaping won't break the SQL (as the data never touches it), but you will get escaped content back out.*
```
$data = array('dog','cat');
$sqlResult = db::get('appDB')->query('UPDATE test SET foo=? WHERE bar=?', $data);
```

------------------------------------

## Get Error Code & Message
### Before
```
$sqlResult = $engine->openDB->query(...);
$sqlResult['errorNumber']; // Error code
$sqlResult['error'];       // Error message
```
### After
```
$sqlResult = db::get('appDB')->query(...);
$sqlResult->errorCode() // Error code (driver specific)
$sqlResult->errorMsg()  // Error message (driver specific)
$sqlResult->sqlState()  // Error SQL_STATE (driver agnostic)
```

------------------------------------

## Get number of rows in result set
### Before
```
$sqlResult = $engine->openDB->query(...);
$sqlResult['numRows'];
-or-
$sqlResult['numrows'];
```
### After
```
$sqlResult = db::get('appDB')->query(...);
$sqlResult->rowCount()
```

------------------------------------

## Get number of affected rows
### Before
```
$sqlResult = $engine->openDB->query(...);
$sqlResult['affectedRows'];
```
### After
```
$sqlResult = db::get('appDB')->query(...);
$sqlResult->affectedRows()
```

------------------------------------

## Get the insertID
### Before
```
$sqlResult = $engine->openDB->query(...);
$sqlResult['id'];
```
### After
*Assuming the underlying driver supports insert id's. Undefined behavior otherwise (it depends on what PDO does)*
```
$sqlResult = db::get('appDB')->query(...);
$sqlResult->insertId()
```

------------------------------------

## Specify how we want our result set is returned (assoc array, index'ed array, etc)
### Before
This was done by changing the mysql_fetch* function depending on the need.
  - `mysql_fetch_assoc()` for an associative array
  - `mysql_fetch_row()` for an index'd array
  - *Etc.*

### After
We provide a optional PDO 'fetch style' constant to the fetch*() method
 - `PDO::FETCH_ASSOC` Returns an array indexed by column name (**Default**)
 - `PDO::FETCH_BOTH` Returns an array indexed by both column name and 0-indexed column number
 - `PDO::FETCH_NUM` returns an array indexed by column number, starting at column 0
 - *A few more are available. (See: [fetch_style](http://us2.php.net/manual/en/pdostatement.fetch.php#refsect1-pdostatement.fetch-parameters))*

------------------------------------

## Loop through each row in a result set
### Before
```
$sqlResult = $engine->openDB->query(...);
while($row = mysql_fetch_assoc($sqlResult['result'])){
    // Do stuff
}
```
### After
```
$sqlResult = db::get('appDB')->query(...);
while($row = $sqlResult->fetch()){
    // Do stuff
}
```

------------------------------------

## Get all the rows from a result set into a native array
*This lets you do lots of stuff like pass the result to another function as a simple array, or store it somewhere, etc.*
### Before
```
$rows = array();
$sqlResult = $engine->openDB->query(...);
while($row = mysql_fetch_assoc($sqlResult['result'])){
    $rows[] = $row;
}
// $rows is now the full result set
```
### After
```
$sqlResult = db::get('appDB')->query(...);
$rows = $sqlResult->fetchAll()

// Usage example
foreach($rows as $rowNum => $rowData){
    // Do stuff
}
```

------------------------------------

## Loop through each row in result set, getting only the requested field
### Before
```
$sqlResult = $engine->openDB->query(...);

while($row = mysql_fetch_row($sqlResult['result'])){
    $field = $row[2]; // Get field by offset
    // Do stuff
}

-or-

while($row = mysql_fetch_assoc($sqlResult['result'])){
    $field = $row['fieldName']; // Get field by name
    // Do stuff
}
```
### After
```
$sqlResult = db::get('appDB')->query(...);

// Get field by offset (0: 1st field)
while($field = $sqlResult->fetchField(2)){
    // Do stuff
}

-or-

// Get field by field name
while($field = $sqlResult->fetchField('fieldName')){
    // Do stuff
}

// Practical example: We need to know how many users are named 'Smith'
$sqlResult = db::get('appDB')->query("SELECT COUNT(*) FROM users WHERE name = 'Smith'");
$usersNamedSmith = $sqlResult->fetchField(); // Defaults to the 1st (0'th) field
```

------------------------------------

## Get a full result set of only the requested field
*This works the same was as essentialy combining fetchAll() with fetchField()*
### Before
```
$sqlResult = $engine->openDB->query(...);

$rows = array();
while($row = mysql_fetch_row($sqlResult['result'])){
    $rows[] = $row[2]; // Get field by offset
}
// $rows is now the full result set for column 2

-or-

$rows = array();
while($row = mysql_fetch_assoc($sqlResult['result'])){
    $rows[] = $row['fieldName']; // Get field by name
}
// $rows is now the full result set for column 'fieldName'
```
### After
```
$sqlResult = db::get('appDB')->query(...);

$rows = $sqlResult->fetchFieldAll(2) // Get field by offset (0: 1st field)
-or-
$rows = $sqlResult->fetchFieldAll('fieldName') // Get field by name
```

------------------------------------

## Get the number, and names, of fields in a result set
### Before
*This operation alters the state of the result set.<br>There's no easy way, that I can think of, to just get this infomation w/o altering the statet*
```
$sqlResult = $engine->openDB->query(...);
$row = mysql_fetch_assoc($sqlResult['result']);
$fieldCount = sizeof($row);
$fieldNames = array_keys($row);
```

### After
*Does not alter the state of the result set*
```
$sqlResult = db::get('appDB')->query(...);
$fieldCount = $sqlResult->fieldCount();
$fieldNames = $sqlResult->fieldNames();
```

------------------------------------

## Transaction operations
### Before

 $engine->openDB->transBegin();  
 $engine->openDB->transCommit();  
 $engine->openDB->transRollback();  
 $engine->openDB->transEnd();  
 $engine->openDB->inTransaction();  


### After
*Follows the naming scheme of underlying PDO object. Nested transaction work as they do currently.*

db::get('appDB')->beginTransaction();  
db::get('appDB')->commit();  
db::get('appDB')->rollback();  
db::get('appDB')->inTransaction();  


------------------------------------

## Put connection into 'read-only' mode
*Note: There is a performane hit when in read-only mode due to additional pattern matching needed to block all write actions. It's much more efficient (and probably more secure) to just remove write permissions (INSERT, UPDATE, DELETE, etc) from the user in the database)*
### Before
```
// This feature does not exist for the current engineDB
```

### After
```
db::get('appDB')->readOnly(TRUE);  // Turn read-only mode ON
db::get('appDB')->readOnly(FALSE); // Turn read-only mode OFF
db::get('appDB')->isReadOnly();    // Returns TRUE if we're in read-only mode
```

------------------------------------

## Destroy (disconnect) from server
### Before
```
// Not really a 'clean' disconnect, or at least as clean as it could be
$engine->openDB->__destruct(); // Force disconnect
$engine->openDB = NULL; // De-reference the instance
```

### After
```
db::get('appDB')->destroy();
```

------------------------------------

## Debugging
### Before
```
$engine->openDB->testConnection();

$sqlResult = $engine->openDB->query(...);
$engine->openDB->test(); // Prints debug info

$engine->openDB->displayQuery(...);
```

### After
```
db::get('appDB')->isConnected();

$sqlResult = db::get('appDB')->query(...);
$sqlResult->debug(); // Returns debug info
```
