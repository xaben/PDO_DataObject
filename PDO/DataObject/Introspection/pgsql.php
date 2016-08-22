<?php 
/**
 * Object Based Database Query Builder and data store
 *  - Postgresql Introspection Component.
 *
 * For PHP versions  5 and 7
 * 
 * 
 * Copyright (c) 2015 Alan Knowles
 * 
 * This program is free software: you can redistribute it and/or modify  
 * it under the terms of the GNU Lesser General Public License as   
 * published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU 
 * Lesser General Lesser Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *  
 * @category   Database
 * @package    PDO_DataObject
 * @author     Alan Knowles <alan@roojs.com>
 * @copyright  2016 Alan Knowles
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html  LGPL 3
 * @version    1.0
 * @link       https://github.com/roojs/PDO_DataObject
 */
  
  
class_exists('PDO_DataObject_Introspection') ? '' : require_once 'PDO/DataObject/Introspection.php';
// move me to seperate classes...
class PDO_DataObject_Introspection_pgsql extends PDO_DataObject_Introspection
{
    
    function getSpecialQuery($type)
    {
        switch ($type) {
            
            case 'tables':
                
                return "SELECT table_name FROM information_schema.tables" .
                        " WHERE table_type = 'BASE TABLE'" .
                        " AND table_schema = 'public' order by table_name ASC";
            
            case 'tables.all': /// not sure if this really works....
                return 'SELECT c.relname AS "Name"'
                        . ' FROM pg_class c, pg_user u'
                        . ' WHERE c.relowner = u.usesysid'
                        . " AND c.relkind = 'r'"
                        . ' AND NOT EXISTS'
                        . ' (SELECT 1 FROM pg_views'
                        . '  WHERE viewname = c.relname)'
                        . " AND c.relname !~ '^(pg_|sql_)'"
                        . ' UNION'
                        . ' SELECT c.relname AS "Name"'
                        . ' FROM pg_class c'
                        . " WHERE c.relkind = 'r'"
                        . ' AND NOT EXISTS'
                        . ' (SELECT 1 FROM pg_views'
                        . '  WHERE viewname = c.relname)'
                        . ' AND NOT EXISTS'
                        . ' (SELECT 1 FROM pg_user'
                        . '  WHERE usesysid = c.relowner)'
                        . " AND c.relname !~ '^pg_'";
            case 'schema.tables':
                return "SELECT schemaname || '.' || tablename"
                        . ' AS "Name"'
                        . ' FROM pg_catalog.pg_tables'
                        . ' WHERE schemaname NOT IN'
                        . " ('pg_catalog', 'information_schema', 'pg_toast')";
            case 'schema.views':
                return "SELECT schemaname || '.' || viewname from pg_views WHERE schemaname"
                        . " NOT IN ('information_schema', 'pg_catalog')";
            case 'views':
                // Table cols: viewname | viewowner | definition
                return 'SELECT viewname from pg_views WHERE schemaname'
                        . " NOT IN ('information_schema', 'pg_catalog')";
            case 'users':
                // cols: usename |usesysid|usecreatedb|usetrace|usesuper|usecatupd|passwd  |valuntil
                return 'SELECT usename FROM pg_user';
            case 'databases':
                return 'SELECT datname FROM pg_database';
            case 'functions':
            case 'procedures':
                return 'SELECT proname FROM pg_proc WHERE proowner <> 1';
            default:
                return null;
        }
    }

    
    
    /**
     * Returns information about a table or a result set
     *
     * @param  string  $table   string containing the name of a table.
     *                           MUST BE QUOTED if required....
     *                          
     
     * @return array  an associative array with the information requested.
     *                 A DB_Error object on failure.
     *
     *
     *  multiple_key
        
     *
     */
    function tableInfo($table)
    {
        
        
        // currently only queries 'public'???
        $schema  ='public';
        if (strpos($table,'.') !== false) {
            list($schema, $table) =explode('.', $table);
        }
         
        $database = $this->do->database();
        
        $records =  $this->do
            ->query("
                    SELECT
                        columns.table_name as tablename,
                        columns.column_name as name,
                        constraint_column_usage.table_name as fk_table,
                        constraint_column_usage.column_name as fk_column
                        columns.column_default as default_value_raw,
                        data_type as type,
                        numeric_precision as len,
              			CONCAT(
                            CASE WHEN
                                IS_NULLABLE = 'YES'
                            THEN
                                '' ELSE 'not_null' END,
                            CASE WHEN
                                key_column_usage.position_in_unique_constraint is null AND column_default LIKE '%nextval%'
                            THEN
                                ' primary' ELSE '' END,
                            CASE WHEN
                                key_column_usage.position_in_unique_constraint is null AND column_default NOT LIKE '%nextval%'
                            THEN
                                ' unique' ELSE '' END
                        ) as flags
                    
                    FROM
                        INFORMATION_SCHEMA.columns
                    LEFT JOIN
                        INFORMATION_SCHEMA.key_column_usage
                    ON
                        key_column_usage.TABLE_SCHEMA = COLUMNS.TABLE_SCHEMA -- public
                        AND
                        key_column_usage.TABLE_CATALOG = COLUMNS.TABLE_CATALOG -- database
                        AND
                        key_column_usage.TABLE_NAME = COLUMNS.TABLE_NAME -- table
                        AND
                        key_column_usage.COLUMN_NAME = COLUMNS.COLUMN_NAME
                    LEFT JOIN 
                        information_schema.constraint_column_usage 
                        ON
                        key_column_usage.constraint_name = constraint_column_usage.constraint_name
                        
                    WHERE
                        COLUMNS.TABLE_NAME = '{$this->do->escape($table)}'
                        and
                        COLUMNS.TABLE_SCHEMA = '{$this->do->escape($schema)}' 
        
            ")
            ->fetchAll(false,false,'toArray');
        
        
        if (PDO_DataObject::config()['portability'] & PDO_DataObject::PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        
        $res   = array();

        //print_r($records);
        
        foreach($records as $r) {
            
            $r['name'] =  $case_func($r['name']);
            $r['table'] =  $case_func($table);
            $r['default_value']  = null;
            switch($r['default_value_raw']) {
                case '':
                    break;
                case "''::text":
                    $r['default_value'] = '';
                    break;
                case 'true';
                    $r['default_value']  = true;
                    break;
                case 'false';
                    $r['default_value']  = false;
                    break;
                default:
                    $r['default_value'] = $r['default_raw_value'];
                    break;
            }
            if (is_numeric($r['default_raw_value']) {
                $r['default_value'] *= 1.0; // hopefully...
            }
            
            
            
            $res[] = $r;
            
            
            array(
                'table' => $case_func($table),
                'name'  => $case_func($r['name']),
                'type'  => $bits[0],
                'len'   => isset($bits[1]) ? str_replace(')','', $bits[1])  : '',
                'flags' =>   ($r['notnull'] != '' ? ' not_null' : '').
                        ($r['primarykey'] == 't' ? ' primary' : '').
                        ($r['uniquekey'] == 't' ? ' unique' : '') .
                        ' '. $r['default']
                       
                        
            );
           
        }

        
        return $res;
    }
    /**
     * Returns information about a foriegn keys of a table.
     * Used to generate the links / join .. 
     * 
     * @param  string  $table   string containing the name of a table.
     *                           MUST BE QUOTED if required....
     *                          
     
     * @return array  an associative array (local column) ->  {related_table}:{related_column}
     * 
     *
     */
    function foreignKeys($table)
    {
           
        $fk = array();
        $res = $this->do->query("SELECT
                    pg_catalog.pg_get_constraintdef(r.oid, true) AS condef
                FROM pg_catalog.pg_constraint r,
                     pg_catalog.pg_class c
                WHERE c.oid=r.conrelid
                      AND r.contype = 'f'
                      AND c.relname = '{$this->do->escape($table)}'")
                ->fetchAll(false,false,'toArray');
        
        
        $treffer = array();
        // this may not work correctly -   see this: http://pear.php.net/bugs/bug.php?id=17049
        
        
        preg_match_all(
            "/FOREIGN KEY \((\w*)\) REFERENCES (\w*)\((\w*)\)/i",
            $r[0]['condef'],
            $treffer,
            PREG_SET_ORDER);
        if (!count($treffer)) {
            return $fk;
        }
        foreach($treffer as $m) {
            $fk[  $m[1]  ]  = $m[2] . ":" . $m[3];
        }
        return $fk;
    }
    
}
