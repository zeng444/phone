<?php
go(function (){
    include_once dirname(__DIR__) . '/vendor/autoload.php';
    $EPhone = new \Janfish\EPhone\Client([

    ]);
    $result = $EPhone->getToken();
    print_r([
        $result
    ]);
});
