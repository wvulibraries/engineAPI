## EngineAPI 4.0 Database Object 

This design is based PHP's PDO library and somewhat loosly on the Doctrine db abstraction library (http://www.doctrine-project.org/projects/dbal.html)

Classes:

 - db<br>
   the main, static, object that most interaction will be through
 - dbDriver<br>
   an abstract object which will be extended by each driver
 - dbDriver_{type}<br>
   The actual database driver class representing a 'connection' to a database
 - dbStatement<br>
   an abstract object that will be extended by each db statement
 - dbStatement_{type}<br>
   The actual database statement class representing an individual 'statement' (a single SQL call)
 
---
## db
This class will be the main class

### create(driver [,(array)options] [,(string)alias])
Create a db driver object (factory pattern) of the given driver type<br>
If an options array is passed, pass it along to the driver class for driver setup (can be driver-specific)<br>
If alias is passed, register the created object (registry pattern) under this name for easy access<br>
If alias passed already exists, throw error and return FALSE<br>
**Question** Do we want to setup a convention for 'default' aliases?

### __callStatic(name) 
[*PHP Magic Method*]
This will allow for very easy access to a specific db object like so: `db::systems->...` to use the 'systems' database. This will use an alias (as passed to create() above or with registerAs() below)<br>

### registerAs(dbObject, name)
Manually register an already created db object under a given name. Useful in cases where you forgot, or can't) set an alias via the create() method above. (This will be used internally as well)

### unregisterObject(name)
This method removes a registered object from the index (effectivly forgetting about it) which allows its name to be reused

### listDrivers()
[*Helper method*] This method parses the drivers dir and returns an array of all available drivers.

---
## dbDriver
This is an abstract class which represents a given database driver and connection. This abstract class provides driver-agnostic helper/utility methods, and enforces a basic level of interoperability between drivers similar to the way interfaces do.

### Helper/Utility Methods
*This object will most likely grow organically as more functionality is needed, and that functionality does NOT depend on the underlying driver*

### Abstract Methods 
*these methods must be implemented on the child (driver) object*
#### prepare(prepareSQL)
Returns a dbStatement object for fine-grained prepared statement operations
#### query(sql [,params])
Run a query where you expect a full result set, but do not need as finely-grained control as with prepare()<br>
IF an array of params are passed do some auto-prepared statement to correctly include them. (They will be included at sensible defaults, and in the order they appear in the array.
#### exec(sql)
Run a query where you only expect a bool result (ex: DELETE/DROP/TRUNCATE/etc)
#### escape(var)
Escape the given var for safe inclusion into SQL
#### beginTransaction()
Start up a transaction
#### commit()
Commit and end the current transaction (assuming we're on the 'root' transaction level*)
#### rollback()
Rollback and end the current transaction (assuming we're on the 'root' transaction level*)
#### inTransaction()
Return `TRUE` if we're inside a transaction, `FALSE` otherwise
#### errorCode()
Return the error Code/Number for the last error
#### errorInfo()
Return the error message for the last error

***Nested Transactions**<br>
We will use the same system as Doctrine uses for nested transactions. We keep an internal counter on the driver, which is incremented on `beginTransaction()` and decremented on `commit()` and `rollback()`. When this counter transitions from `0`->`1` We set the underlying connection into 'transaction mode' and when the counter transitions from `1`->`0` we either commit or rollback the underlying connection. If at any point `rollback()` is called we flag the overall transaction for 'Rollback only' mode where the transactioin will be rolled back no matter what. (See: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/transactions.html#transaction-nesting)


---
## dbStatement
This is an abstract class which represents a given database statement. (CRUD operations) This abstract class provides driver-agnostic helper/utility methods, and enforces a basic level of interoperability between drivers similar to the way interfaces do. 

The dbStatement object will keep an internal state determined by if the underlying SQL has been executed or not. When not in the appropriate mode for a given method call, the method will return `NULL` and log a DEBUG error message for debugging. (for example, if you call `rowCount()` before the SQL has executed you will get `NULL` back)

### Helper/Utility Methods
*This object will most likely grow organically as more functionality is needed, and that functionality does NOT depend on the underlying driver*

### Abstract Methods 
*these methods must be implemented on the child (statement) object*
#### bindValue(pos,val[,type])
Bind a given value to the positional or named parameter in the prepared statement.
#### bindParam(pos,param[,type])
Bind a given reference to the positional or named parameter in the prepared statement.
#### execute()
Execute the prepared statement against the database
#### fieldCount()
Return the number of columns/fields in the result
#### fieldNames()
Return an array of names of the columns/fields in the result
#### rowCount()
Returns the number of rows in the result
#### affectedRows()
Returns the number of affected rows
#### insertId()
Returns the insertID from an INSERT operation
#### fetch([fetchMode])
Fetch one row at a time from the result setup according to the current fetch mode.<br>
Fetchmode corrisponds to PDO's Fetch modes (which control stuff like numeric-key array, string-key array, object, etc)
#### fetchAll([fetchMode])
Return an array of all rows from the result set.
#### fetchField([string|int $field])
Return only the given field from one row at a time.
#### errorCode()
Return the error Code/Number for the last error
#### errorInfo()
Return the error message for the last error


---
## dbDriver_{type}
This is the underlying database driver object and represents a 'connection' to a database server.
There will be an unknown number (but at least 1) of these drivers, with each implementing a single backend storage system (MySQL, SQLite, Oracle, MongoDB, etc) In order to be used, and for db::create() to build them, these classes MUST extend the dbDriver class which forces the basic level of functionality needed from a driver.

---
## dbStatement_{type}
This is the underlying database statement object and represents a single 'statement' made against a given database. (a sinle SQL call, etc)