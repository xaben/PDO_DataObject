--TEST--
find() test
--FILE--
<?php
require_once 'includes/init.php';
PDO_DataObject::debugLevel(1);
PDO_DataObject::config(array(
        'class_location' => __DIR__.'/includes/sample_classes/DataObjects_',
        'database' => 'mysql://user:pass@localhost/gettest'
));
 

echo "\n\n--------\n";
echo "find with a single name\n" ;

$company = PDO_DataObject::factory('Companies');
$company->name = 'fred';
$rows = $company->find();
echo "Got $rows rows from find\n";
while ($company->fetch()) {
    print_r($company->toArray());
}

echo "\n\n--------\n";
echo "find & fetch with a single name\n" ;

$company = PDO_DataObject::factory('Companies');
$company->name = 'fred';
$company->limit(1);
$company->find(true);

print_r($company->toArray());


echo "\n\n--------\n";
echo "find - mixing where and properties\n" ;

$company = PDO_DataObject::factory('Companies');
$company->name = 'fred';
$company->where('id !=1 ');
$company->limit(1);
$company->find(true);
print_r($company->toArray());



echo "\n\n--------\n";
echo "error running find twice..\n" ;
$company = PDO_DataObject::factory('Companies');
$company->name = 'fred';
$company->find();
while ($company->fetch()) {
    print_r($company->toArray());
}
$company->find();










?>
--EXPECT--
