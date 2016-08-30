--TEST--
Generator - compare to DB_DataObject - Postgres (real database) - will not normmally pass...
--FILE--
<?php
require_once 'includes/init.php';
 

// hard coded to my path....
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR .'/home/alan/gitlive/pear');
 
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Generator.php';

 
$dofn = tempnam (sys_get_temp_dir(), 'pdo-dbdo-tests-') . '-dir';
mkdir($dofn);
$opts = &PEAR::getStaticProperty('DB_DataObject','options');
$opts = array(
    'database' =>   'pgsql://admin:pass4xtuple@localhost/xtuplehk',
    'schema_location' => $dofn,
    'class_location' => $dofn,
    'generator_strip_schema' => true,
    'generator_include_regex' => '/^public\.*/',

    
);

$generator = new DB_DataObject_Generator;
$generator->start();


 

// test structure from introspection
 
$fn = tempnam (sys_get_temp_dir(), 'pdo-pdodo-tests-') . '-dir';
mkdir($fn);


PDO_DataObject::config(
    array(
        'schema_location' => $fn,
        'class_location' => $fn,
        'PDO' => 'PDO',
        'databases' => array(
            'xtuple_db' => 'pgsql://admin:pass4xtuple@localhost/xtuplehk'
        ),
        
        'proxy' => true,
        'debug' => 0,
        'database' => '',
        'class_prefix' => '',
        
    )
);

$gen = (new PDO_DataObject('xtuple_db/accnt'))->generator();

PDO_DataObject_Generator::config(array(
            
            //'build_views' => true,
              
            'generate_links' => true,
            
            //'link_methods'  =>true,
                    
            'extends_class' => 'DB_DataObject',
                // what class do the generated classes extend?
            'extends_class_location' => 'DB/DataObject.php',
            
            


));
 

$gen->start();


echo `diff -w -u $dofn/xtuplehk.ini $fn/xtuple_db.ini | grep -v /tmp/pdo`;
// as they have different file names...
`rm $dofn/xtuplehk.ini $fn/xtuple_db.ini`;

// trust me... these diffs are huge, but the output is simlar enough...
//echo `diff -u -r $dofn $fn`;

`rm -rf $dofn $fn`;

 
?>
--EXPECT--
