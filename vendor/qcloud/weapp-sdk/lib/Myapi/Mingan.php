<?php
namespace QCloud_WeApp_SDK\Myapi;

use \QCloud_WeApp_SDK\Helper\Request as Request;
use \QCloud_WeApp_SDK\Constants as Constants;
use \QCloud_WeApp_SDK\Conf as Conf;

class Mingan
{
    public static function includeMgc($text){
        $requestParams = [
            'grant_type' => 'client_credential',
            'appid' => 'wx98b3be7df79c0bdc',
            'secret' => '0d56c6ac9098cbbc0d58a7495a2d7591',
        ];
        $url = 'https://api.weixin.qq.com/cgi-bin/token?' . http_build_query($requestParams);
        list($status, $body) = array_values(Request::get([
            'url' => $url,
            'timeout' => Conf::getNetworkTimeout()
        ]));
        $access_token = $body['access_token'];

        //下面是提交api
        //$text = '朱元璋jk';
        $options = [];
        $data['content'] = $text;
        $options['url'] = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $access_token;
        $options['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        $options['timeout'] = Conf::getNetworkTimeout();
		$options = array_merge_recursive($options, array(
            'method' => 'POST',
            'headers' => array('Content-Type: application/json; charset=utf-8'),
        ));
        list($status, $body) = array_values(Request::send($options));
        return $body;
    }
}
