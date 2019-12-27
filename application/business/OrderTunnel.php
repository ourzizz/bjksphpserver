<?php
use \QCloud_WeApp_SDK\Tunnel\ITunnelHandler as ITunnelHandler;
use \QCloud_WeApp_SDK\Tunnel\TunnelService as TunnelService;
use QCloud_WeApp_SDK\Constants as Constants;
use QCloud_WeApp_SDK\Auth\LoginService as LoginService;
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Helper\Util as Util;
/*
 *SDK不靠谱自己动手，我只需要服务器能广播消息即可
 *
 * */

class OrderTunnel{
    public static function broadcast($type,$content){
        $rows = DB::select("seller",['tunnelId'],['tunnelStatus'=>'on']);
        $connectedTunnelIds=array();
        foreach($rows as $tid){
            array_push($connectedTunnelIds,$tid->tunnelId);
        }
        $result = TunnelService::broadcast($connectedTunnelIds, $type, $content);
    }

    public function test(){
        $order_id = '1550849044tpBOa';
        $rows = DB::select("seller",['tunnelId'],['tunnelStatus'=>'on']);
        $connectedTunnelIds=array();
        foreach($rows as $tid){
            array_push($connectedTunnelIds,$tid->tunnelId);
        }
        $type = 'order';
        $content = ['order_id'=>$order_id];
        $result = TunnelService::broadcast($connectedTunnelIds, $type, $content);
    }
}

