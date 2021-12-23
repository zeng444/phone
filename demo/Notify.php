<?php
include_once dirname(__DIR__) . '/vendor/autoload.php';
$raw = [
    'App_id' => '9449', //应用id
    'Aleg_answertime' => '2021-12-23 15:01:33', //A路呼叫应答时间,AB路都计费的情况下根据这个时间计算A路(Call_endtime减去该时间)和B路(Call_endtime减去Call_answertime)各自通话时长
    'Call_answertime' => '2021-12-23 15:01:53', //呼叫应答时间(为B路应答时间)
    'Call_bill' => '0.09',//呼叫通话费用
    'ACall_duration' => '32', //通话时间
    'Call_duration' => '12',//通话时间
    'Call_endtime' => '2021-12-23 15:02:05',//呼叫结束时间
    'Call_id' => 'ce42e30b-e6b5-4773-ae6c-9dd7b943717b',//呼叫唯一标识
    'Call_starttime' => '2021-12-23 15:01:33',//	呼叫开始时间
    'Callee' => '18215626530',//主叫
    'Caller' => '15884244751',//用户/被叫
    'Request_time' => '2021-12-23 15:01:27',
    'Extends' => '{"a":"4552","b":"荷花"}', //用户/呼叫请求时间
    'Record_url' => 'https://111.44.229.177:9443/sp-oh4a3mxz8vz2y2u0y3sj2fi8vplv/21122307015312033275128.wav?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Date=20211223T065830Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Credential=D2A70C89769BACFDC313%2F20211223%2F%2Fs3%2Faws4_request&X-Amz-Signature=8e3df9dd16031cc49ac1c9320c3d06f9fb02f4819f04be0198cc3749debde2ac', //录音文件存储地址，仅在服务端开通了录音功能才有值
    'Bind_number' => '+869717143006',
    'Sub_id' => ''
];
$raw = '{"App_id":9449,"Aleg_answertime":"2021-12-23 15:01:33","Call_answertime":"2021-12-23 15:01:53","Call_bill":0.09,"ACall_duration":32,"Call_duration":12,"Call_endtime":"2021-12-23 15:02:05","Call_id":"ce42e30b-e6b5-4773-ae6c-9dd7b943717b","Call_starttime":"2021-12-23 15:01:33","Callee":"18215626530","Caller":"15884244751","Request_time":"2021-12-23 15:01:27","Extends":"{\"a\":\"4552\",\"b\":\"\\u8377\\u82b1\"}","Record_url":"https://111.44.229.177:9443/sp-oh4a3mxz8vz2y2u0y3sj2fi8vplv/21122307015312033275128.wav?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Date=20211223T065830Z&X-Amz-SignedHeaders=host&X-Amz-Expires=604800&X-Amz-Credential=D2A70C89769BACFDC313%2F20211223%2F%2Fs3%2Faws4_request&X-Amz-Signature=8e3df9dd16031cc49ac1c9320c3d06f9fb02f4819f04be0198cc3749debde2ac","Bind_number":"+869717143006","Sub_id":""}';
$EPhone = new \Janfish\EPhone\Client([

]);
$result = $EPhone->notify($raw);
print_r($result);
