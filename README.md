```

    try {
        $EPhone = new \Janfish\EPhone\Client([

        ]);

//        $result = $EPhone->dial('158****4751', '182****6530', 'http://www.xxx.cn:8081/3d.php',['a'=>'4552','b'=>'123']);
  
        print_r($result);

    } catch (\Janfish\EPhone\Exception\ServerException $e) {
        echo $e->getMessage() . $e->getCode() . PHP_EOL;
    }

```