# PDO_DataObject

PDO replacement for PEAR's DB_DataObject

Work has been funded by CentralNic Group plc 

In General, this should be API compatible with DB_DataObject, except for the getDatabaseConnection(), which is replaced with PDOConnection() - and returns a PDO object, rather than a PEAR DB object.

In addition
a) chained methods (prefixed with 'c', and throw exceptions)
$key_value =  DB_DAtaObject::Factory('table')
      ->cautoJoin()
      ->cwhere('A=12')
      ->climit(0,10)
      ->fetchAll('id','name');

b) Default behaviour is to throw exceptions (compatibility - PEAR::Error is available as a setting)

c) Overloading has been removed - you should be able to wrap the DataObject and add it back in (it's not recommended - causes more problems than it solves)

---------------------
Commit Log

* note we use git autocommit on save - so the early history does not have much valuable information - as we near completion, valid commit messages will be used.
