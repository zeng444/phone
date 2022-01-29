<?php

namespace Janfish\EPhone;

use RuntimeException;
use Swlib\Saber;
use Janfish\EPhone\Exception\ServerException;
use Yurun\Util\HttpRequest;

/**
 * Class Client
 * @author Robert
 * @package Janfish\EPhone
 */
class Client
{

    protected $host = 'http://new.02110000.com:8088';
//    private $host = 'http://www.janfish.cn:8081';
    protected $appId = '9449';

    protected $appSecret = 'SmX8SnsRsdZ6GN3SJLLImHzLZz9T5wY4';

    protected $proxyHost = '47.112.123.35';

    protected $proxyPort = '34491';

    const CACHE_FILE_NAME = '.jiuhua.phone.';

    const TOKEN_EXPIRE_TIME = 86000;
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
     * @return array
     * @author Robert
     */
    protected function getCache(): array
    {
        $cacheFile = __DIR__ . '/' . self::CACHE_FILE_NAME . $this->appId;
        if (!file_exists($cacheFile)) {
            return [];
        }
        $data = file_get_contents($cacheFile);
        if (!$data) {
            return [];
        }
        $data = unserialize($data);
        if (!isset($data['expiredAt']) || time() > $data['expiredAt']) {
            return [];
        }
        return $data['entity'] ?? [];
    }

    /**
     * @param array $data
     * @param int $expire
     * @return bool
     * @author Robert
     */
    protected function setCache(array $data, int $expire = 20): bool
    {
        $cacheFile = __DIR__ . '/' . self::CACHE_FILE_NAME . $this->appId;
        if (!file_put_contents($cacheFile, serialize(['entity' => $data, 'expiredAt' => time() + $expire]))) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     * @throws ServerException
     * @author Robert
     */
    public function getToken(): string
    {
        $body = $this->getCache();
        if (!$body) {
            $body = $this->httpRequest('/api/login', $this->protocol([
                'Username' => $this->appId,
                'Password' => $this->appSecret,
            ]));
            if (!$body || !$body = json_decode($body, true)) {
                throw new ServerException('请求失败');
            }
            if (isset($body['Error'])) {
                throw new ServerException($body['Error'] . ' code:' . $body['result'] ?? '');
            }
            $this->setCache($body, self::TOKEN_EXPIRE_TIME);

        }
        return $body['token'] ?? '';
    }

    /**
     * @param string $caller
     * @param string $callee
     * @param string $notifyUrl
     * @param array $extent
     * @return array
     * @throws ServerException
     * @author Robert
     */
    public function dial(string $caller, string $callee, string $notifyUrl, array $extent = []): array
    {
        $req = [
            'Callid' => $this->makeCallId(),
            'App_id' => $this->appId,
            'Caller' => $caller,
            'Callee' => $callee,
            'Call_minutes' => '',//最大呼叫时间(单位分钟),不大于500分钟
            'Extends' => json_encode($extent),//扩展字段
            'Cdr_url' => $notifyUrl,//接收推送话单的url
        ];
        $body = $this->httpRequest('/api/CallRequest', $this->protocol($req), $this->getToken());
        if (!$body || !$body = json_decode($body, true)) {
            throw new ServerException('请求失败');
        }
        if (!isset($body['result'])) {
            throw new ServerException($body['msg'], 500);
        }
        if ($body['result'] != 0) {
            throw new ServerException($body['msg'], (int)$body['result']);
        }
        return $body;
    }

    /**
     * @return string
     * @author Robert
     */
    public function makeCallId(): string
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
     * @param string $route
     * @param string $body
     * @param string $token
     * @return string
     * @throws ServerException
     * @author Robert
     */
    private function httpRequest3(string $route, string $body = '', string $token = ''): string
    {
        $opt = [
            'base_uri' => $this->host,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
        if ($this->proxyHost) {
            $opt['proxy'] = $this->proxyHost;
        }
        if ($token) {
            $opt['headers'][] = 'Authorization:JH ' . $token;
        }
        $saber = Saber::create($opt);
        $response = $saber->post($this->host . $route, $body);
        if (!$response->isSuccess()) {
            throw new ServerException('Request Failed');
        }
        $body = (string)$response->getBody();
        if (!$body) {
            throw new ServerException('Request Failed');
        }
        return $body;

    }

    private function httpRequest2(string $route, string $body = '', string $token = ''): string
    {
        $http = HttpRequest::newSession();
        if ($this->proxyHost) {
            $http->proxy($this->proxyHost, $this->proxyPort);
        }
        $http->header('Content-Type', 'application/json');
        if ($token) {
            $http->header('Authorization', 'JH ' . $token);
        }
        $response = $http->post($this->host . $route, $body);
        if ($response->getStatusCode() != 200) {
            throw new ServerException('请求失败');
        }
        if ($response->errno()) {
            throw new ServerException($response->getError());
        }
        $body = (string)$response->getBody();
        if (!$body) {
            throw new ServerException('Request Failed');
        }
        return $body;

    }

    /**
     * @param string $route
     * @param string $body
     * @param string $token
     * @return string
     * @throws ServerException
     * @author Robert
     */
    private function httpRequest(string $route, string $body = '', string $token = ''): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->host . $route);
        $header = [
            'Content-Type: application/json'
        ];
        if ($token) {
            $header[] = 'Authorization: JH ' . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if ($this->proxyHost) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost . ":" . $this->proxyPort);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $body = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new ServerException(curl_error($ch), 0);
        }
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 !== $httpStatusCode) {
            throw new ServerException($body, $httpStatusCode);
        }
        if (!$body) {
            throw new ServerException('Request Failed');
        }
        curl_close($ch);
        return $body;
    }

    /**
     * 通知接受
     * @param string $raw
     * @return array
     * @throws ServerException
     * @author Robert
     */
    public function notify(string $raw): array
    {
        $data = json_decode($raw, true);
        //{"App_id":"200","Call_answertime":"2017-08-22 15:54:10","Call_bill":0.07,"Call_duration":6,"Call_endtime":"2017-08-22 15:54:16","Call_id":"9a530b67-8742-11e7-a924-00163e0ea174appIp:116.231.90.150","Call_starttime":"2017-08-22 15:54:01","Callee":"+8617717028007","Caller":"+8618939892185","Request_time":"2017-08-22 23:53:46"}
        if (!$data) {
            throw new \RuntimeException('电话不存在');
        }
        if (!isset($data['App_id']) || $this->appId != $data['App_id']) {
            throw new ServerException('App_id is not correct');
        }
        return $data;

    }


}
