<?php

header('Content-Type: application/json');
// header('Content-Disposition: attachment; filename=test.json; Content-Type: application/json');

$test3=array('test1' => 'test2','test3' => 'test4');
$test2=array('test5',$test3,'test6');
$test=array('test7' => 'test8','test9' => 'test10','test11' => $test2);

echo json_encode($test);

?>
