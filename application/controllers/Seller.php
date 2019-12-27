<?php
/*本接口为商家提供服务，商家接单、退款等功能均在此
 *
 * 流程说明 用户下单   ->picker捡单配单中 ->快递流程  ->送达签收
 * 商家操作:WAIT_PICK       ASSEMBLE        DELIVERY     SIGNED
 * @WAIT_PICK 待接单
 * @ASSEMBLE  配单中
 * @DELIVERY  投递中
 * @CANCLE    取消投递
 * @SIGNED    客户签收
 *
 * 流程说明 申请退款      
 * 商家操作:refund_reason:'',refund_status:'W'  写入
 *  ->广播各个角色                 ->快递流程拦截
 *
 *  前端快递获取送货列表的时候直接给出的是 DELIVERY 状态的全部单子
 *  前端快递小哥页面根据refund_status判断,
 *          1如果refund_status 不为空,页面给出警告不要送出
 *          2没有问题就正常派单
 *  小哥点击取消送单后台将商家act置为CANCLE,
 *  设置为CANCLE的单子表示小哥已经取消送货，也不会再出现在送单队列中
 *
 * 一定要避免已送达 已退款 refund_status=F seller_act=signed
 * 商家退款后 
 * 将seller_act 置为CANCLE
 * 退款refund_status W等待退款，F完成退款，R拒绝退款
 */

use \QCloud_WeApp_SDK\Auth\LoginService as LoginService;
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Helper\Util as Util;
use QCloud_WeApp_SDK\Constants as Constants;
use \QCloud_WeApp_SDK\WxPay  as Pay;
use \QCloud_WeApp_SDK\Tunnel\TunnelService as TunnelService;
require APPPATH.'business/Order.php';
require APPPATH.'business/OrderTunnel.php';
require APPPATH.'business/WeixinRefund.php';

class Seller extends CI_Controller { 
    /**
     *这个部分涉及到比较敏感的东西，订单和钱
     *还是需要加入身份验证的方法，这个地方先预留接口
     */
    private function checkLogin(){
            $result = LoginService::check();
            if ($result['loginState'] === Constants::S_AUTH) {
                return true;
            } else {
                $this->json([
                    'code' => -1,
                    'data' => []
                ]);
                return false;
            }
    }

    /**
     *取出还没有接单的订单发给商家(其商家态为WAIT_PICK)
     *在支付回调页面将订单商家态置WAIT_PICK
     *货到付款应该在user-order页面存储订单同时设置
     */
    public function get_wait_pick_orders(){
        if($this->checkLogin()){
            $conditions = "seller_act='WAIT_PICK' AND refund_status='' ";
            $rows = DB::select('user_order',['*'],$conditions);
            $orders = [];
            foreach($rows as $order){
                array_push($orders,Order::get_order_info($order->order_id));
            }
            $this->json($orders);
        }
    }

    /*
     *取出捡单的单子，因为需要知道是那个捡单员配的单子，所以要open_id
     * */
    public function get_assemble_orders(){
        $open_id = $_POST['open_id'];
        $conditions = "seller_act='ASSEMBLE' AND open_id= '$open_id'";
        $rows = DB::select('user_order',['order_id'],$conditions);
        $orders = [];
        foreach($rows as $order){
            array_push($orders,Order::get_order_info($order->order_id));
        }
        $this->json($orders);
    }

    /**
     *取出等待退款的单子
     */
    public function get_wait_refund_orders(){
        if($this->checkLogin()){
            $conditions = "refund_reason !='' AND refund_status!='SUCCESS' ";
            $rows = DB::select('user_order',['*'],$conditions);
            $orders = [];
            foreach($rows as $order){
                array_push($orders,Order::get_order_info($order->order_id));
            }
            $this->json($orders);
        }
    }

    /**
     *取出还没有发货的单子
     */
    public function get_delivery_orders(){
        if($this->checkLogin()){
            $conditions = "seller_act='DELIVERY'";
            $rows = DB::select('user_order',['*'],$conditions);
            $orders = [];
            foreach($rows as $order){
                array_push($orders,Order::get_order_info($order->order_id));
            }
            $this->json($orders);
        }
    }

    /*
     *快递小哥点击取消送货，老板可以放心了
     * */
    public function cancle_delivery($order_id){
        if($this->checkLogin()){
            $rows = DB::update('user_order',['seller_act'=>'CANCLE'],['order_id'=>$order_id]);
            OrderTunnel::broadcast('cancle_delivery',['order_id'=>$order_id]);
        }
    }

    /*
     *快递小哥点击已送到,必须广播，避免此时退款，造成财物两空的局面
     * */
    public function user_signed($order_id){
        if($this->checkLogin()){
            $rows = DB::update('user_order',['seller_act'=>'SIGNED'],['order_id'=>$order_id]);
            OrderTunnel::broadcast('user_signed',['order_id'=>$order_id]);
        }
    }

    //接单DB记录谁接了哪个单子
    public function pick_orders(){
        if(self::checkLogin()){
            $picker_id =  $_POST['open_id'];
            $order_id =  $_POST['order_id'];
            $rows = DB::update('user_order',['picker_id'=>$picker_id,'seller_act'=>'ASSEMBLE'],['order_id'=>$order_id],'and');
            OrderTunnel::broadcast('picked',['order_id'=>$order_id]);
        }
    }

    //配单完成广播给所有人，快递员会看到新进来的单子
    public function assemble_finish() {
        if(self::checkLogin()){
            $order_id =  $_POST['order_id'];
            $rows = DB::update('user_order',['seller_act'=>'DELIVERY'],['order_id'=>$order_id],'and');
            $order = Order::get_order_info($order_id);
            OrderTunnel::broadcast('delivery',['order_info'=>$order]);
        }
    }

    public function dilevery_orders(){
    }

    /**
     *同意退款
     */
    public function agree_refund(){
        $order_id = $_POST['order_id'];
        $order = DB::row('user_order',['open_id','total_fee'],['order_id'=>$order_id]);
        if(isset($order->open_id)){
            $timestamp = time();
            $out_refund_no = $timestamp.Util::getNum(5);//时间戳加上5位随机码生成订单号
            $seller_refund = new WeixinRefund($order->open_id,$order_id,$order->total_fee,$out_refund_no,$order->total_fee);
            $res = $seller_refund->refund();
            DB::update('user_order',['refund_status'=>$res['return_code'],'out_refund_no'=>$out_refund_no],['order_id'=>$order_id]);
            $this->json($res);
        }else{
            echo "无此订单";
        }
    }
}
