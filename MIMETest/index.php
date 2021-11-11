<?php

header('Content-Type: application/json');
// header('Content-Disposition: attachment; filename=test.json; Content-Type: application/json');

$test3=array('lol' => 'no tak','i' => 'co?');
$test2=array('ha',$test3,'hu');
$test=array('To' => 'cycki','prosty' => 'kutas','test' => $test2);

echo json_encode($test);

?>
