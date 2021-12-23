<?php
go(function () {
    include_once dirname(__DIR__) . '/vendor/autoload.php';
    try {
        $EPhone = new \Janfish\EPhone\Client([

        ]);
//    $result = $EPhone->getToken();
//        $result = $EPhone->dial('18215626530','18682653085','http://www.janfish.cn:8081/3d.php');
//        $result = $EPhone->dial('18215626530','18682653085','http://www.janfish.cn:8081/3d.php');
//        $result = $EPhone->dial('18215626530','18080803715','http://www.janfish.cn:8081/3d.php');
//        $result = $EPhone->dial('15884244751', '18215626530', 'http://www.janfish.cn:8081/3d.php',['a'=>'4552','b'=>'荷花']);
        $result = $EPhone->makeCallId();
        print_r($result);

    } catch (\Janfish\EPhone\Exception\ServerException $e) {
        echo $e->getMessage() . $e->getCode() . PHP_EOL;
    }

});
