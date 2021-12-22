<?php

namespace Janfish\EPhone;

use RuntimeException;
use Swlib\Http\Exception\BadResponseException;
use Swlib\Http\Exception\ClientException;
use Swlib\Http\Exception\ConnectException;
use Swlib\Http\Exception\RequestException;
use Swlib\Http\Exception\ServerException;
use Swlib\Http\Exception\TooManyRedirectsException;
use Swlib\Saber;

/**
 * Class Client
 * @author Robert
 * @package Janfish\EPhone
 */
class Client
{

    private $host = 'http://new.02110000.com:8088';
//    private $host = 'http://www.janfish.cn:8081';
    private $appId = '9449';
    private $appSecret = 'SmX8SnsRsdZ6GN3SJLLImHzLZz9T5wY4';

    private $proxy = 'http://47.112.123.35:34491';
    //
    //curl http://new.02110000.com:8088/api/login -H "Content-Type: application/json" -d {"Username":"9449","Password":"SmX8SnsRsdZ6GN3SJLLImHzLZz9T5wY4"}
    //curl --proxy http://47.112.123.35:34491 -X POST http://new.02110000.com:8088/api/login -H "Content-Type: application/json" -d '{"Username":"9449","Password":"SmX8SnsRsdZ6GN3SJLLImHzLZz9T5wY4"}'


    const DIAL_ERROR_CODE = [
        '0' => '请求成功',
        '99' => 'call id格式错误',
        '101' => '系统繁忙',
        '102' => '账号异常',
        '103' => '账号不存在或者已禁用',
        '104' => '余额不足',
        '105' => '线路异常',
        '106' => '参数错误',
        '110' => '主叫或者被叫号码在黑名单',
        '111' => '主叫受限（主叫被呼叫太多次)',
        '112' => '被叫受限（被叫被呼叫太多次)',
        '113' => '主叫或被叫号码异常',
        '114' => '呼叫并发达到上限',
        '116' => '呼叫区域限制或盲区',
        '117' => 'Callid不唯一',
        '118' => '代理商或用户不存在',
        '119' => '代理商余额不足',
        '120' => '请求体太大',
        '124' => '主叫号码不允许,请联系管理员',
        '125' => '绑定号码错误',
        '141' => '客户端ip错误',
        '501' => 'ip地址未认证或者token无效',
        '999' => '其他错误',
    ];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['host'])) {
            $this->host = $options['host'];
        }
        if (isset($options['appId'])) {
            $this->appId = $options['appId'];
        }
        if (isset($options['appSecret'])) {
            $this->appSecret = $options['appSecret'];
        }
        if (isset($options['proxyHost'])) {
            $this->proxyHost = $options['proxyHost'];
        }
        if (isset($options['proxyPort'])) {
            $this->proxyPort = $options['proxyPort'];
        }
    }

    /**
     * 获取token
     * @return string
     * @author Robert
     */
    public function getToken(): string
    {

        $body = $this->http('post', '/api/login', $this->protocol([
            'Username' => $this->appId,
            'Password' => $this->appSecret,
        ]));
        if (!$body || !$body = json_decode($body, true)) {
            throw new RuntimeException('请求失败');
        }
        if (isset($body['Error'])) {
            throw new RuntimeException($body['Error'] . ' code:' . $body['result']);
        }
        return $body['token'] ?? '';
    }

    /**
     * @param string $mobile
     * @param string $caller
     * @param string $callee
     * @param string $notifyUrl
     * @param array $extent
     * @return array
     * @author Robert
     */
    public function dial(string $mobile, string $caller, string $callee, string $notifyUrl, array $extent = []): array
    {
        $body = $this->http('post', '/api/CallRequest', $this->protocol([
            'Callid' => $this->makeCallId(),
            'App_id' => $this->appId,
            'Caller' => $caller,
            'Callee' => $callee,
            'Call_minutes' => '',//最大呼叫时间(单位分钟),不大于500分钟
            'Extends' => json_encode($extent),//扩展字段
            'Cdr_url' => $notifyUrl,//接收推送话单的url
        ]), $this->getToken());
        if (!$body || !$body = json_decode($body, true)) {
            throw new RuntimeException('请求失败');
        }
        if (isset($body['result']) || $body['result'] != 0) {
            throw new RuntimeException($body['msg'] . ' code:' . $body['result']);
        }
        return $body;
    }

    /**
     * @return string
     * @author Robert
     */
    private function makeCallId(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * @param array $data
     * @return string
     * @author Robert
     */
    private function protocol(array $data): string
    {
        return json_encode($data);
    }

    /**
     * @param string $method
     * @param string $route
     * @param string $body
     * @param string $token
     * @return string
     * @author Robert
     */
    private function http(string $method, string $route, string $body = '', string $token = ''): string
    {
        $opt = [
            'base_uri' => $this->host,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
        if ($this->proxy) {
            $opt['proxy'] = $this->proxy;
        }
        if ($token) {
            $opt['headers'][] = 'Authorization:JH ' . $token;
        }
        $saber = Saber::create( $opt);
        $response = $saber->$method($this->host.$route, $body);
        print_r([(string)$response->getBody()]);
        return '';
        if (!$response->isSuccess()) {
            throw new RuntimeException('Request Failed');
        }
        $body = (string)$response->getBody();
        if (!$body) {
            throw new RuntimeException('Request Failed');
        }
        return $body;

    }

    /**
     * 通知接受
     * @param string $raw
     * @return array
     * @author Robert
     */
    public function notify(string $raw): array
    {
        $data = json_decode($raw, true);
        //{"App_id":"200","Call_answertime":"2017-08-22 15:54:10","Call_bill":0.07,"Call_duration":6,"Call_endtime":"2017-08-22 15:54:16","Call_id":"9a530b67-8742-11e7-a924-00163e0ea174appIp:116.231.90.150","Call_starttime":"2017-08-22 15:54:01","Callee":"+8617717028007","Caller":"+8618939892185","Request_time":"2017-08-22 23:53:46"}
        if (!$data) {
            throw new \RuntimeException('电话不存在');
        }
        return $data;

    }


}