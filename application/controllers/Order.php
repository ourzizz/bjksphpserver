<?PHP
/*
 * 本文件专门处理客户订单的业务逻辑
 * 订单所有状态以及各个状态组合方式下的顾客、商家应该做出的操作
* */
use \QCloud_WeApp_SDK\Tunnel\ITunnelHandler as ITunnelHandler;
use \QCloud_WeApp_SDK\Tunnel\TunnelService as TunnelService;
use QCloud_WeApp_SDK\Constants as Constants;
use QCloud_WeApp_SDK\Auth\LoginService as LoginService;
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Helper\Util as Util;
use \QCloud_WeApp_SDK\Myapi\WeixinPay  as WeixinPay;

require APPPATH.'business/OrderTunnel.php';
require "/data/wwwroot/default/supermall/vendor/qcloud/qcloudsms_php/src/index.php";
use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;

class Order extends CI_Controller {
    /**
     * 生成订单,将用户选购商品入库 
     * @example 小程序前端给过来的order_info的json格式{'open_id':'xx','goods_list':[{'goods_id':1,'count':1}...],'total_fee':3300,'address_id':1}
     * @param string $tableName 数据库名
     * @param array  $data      要插入的数据
     */
    public function pay(){
        if(isset($_POST['order_info'])) {
            $timestamp    = time();
            $order_info   = json_decode($_POST['order_info'],true);
            $order_id     = $timestamp.Util::getNum(5);//时间戳加上5位随机码生成订单号
            $appid        = Conf::getAppId();
            $key          = Conf::getKey();
            $mch_id       = Conf::getMchId();
            $notify_url   = Conf::getNotifyUrl();
            $openid       = $order_info['open_id'];
            $total_fee    = $order_info['total_fee'];
            $out_trade_no = $order_id;
            $body = $order_info['body'];
            $weixinpay = new WeixinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee,$notify_url);
            $return = $weixinpay->pay();
            $return['order_id'] = $order_id;
            $order_info['order_id'] = $order_id;
            $order_info['timestamp'] = $timestamp;
            $order_info = array_merge($order_info,$return);
            $this->store_order($order_info);
            $this->json($return);
        }
    }

    /*
     *取出订单详细信息
     */
    public function get_order_info($order_id){
        $order = DB::row('user_order',['*'],"order_id='$order_id'");
        if(isset($order)) {
            $address = DB::row('user_address',['*'],"address_id='$order->address_id'");
            $goods_list = DB::select('goods_in_order',['*'],['order_id'=>$order_id]);
            foreach($goods_list as &$goods){
                $goods_id = $goods->goods_id;
                $goods_info = DB::row('goods',['name','face_img','price','danwei','type'],['goods_id'=>$goods_id]);
                $goods = array_merge((array)($goods),(array)($goods_info));
            }
            $res = compact('order','address','goods_list');
            return $res;
        }
    }

    public function re_pay($order_id){
        $this->json($this->get_order_info($order_id));
    }

    /*
     *用户确认收货
     *
     */
    public function user_sign_order($open_id,$order_id){
        $condition = "open_id='$open_id' AND order_id='$order_id' ";
        //if(!$condition){
            DB:: update('user_order',['logistics_status'=>'SIGNED'],$condition);
        //}
    }

    /*
     *Express 快递表
     * */
    public function get_express_info($order_id){
        $res = DB::row('express',['*'],['order_id'=>$order_id]);
        $this->json($res);
    }

    /*
     *取出待签收订单的信息
     *下一步如果加入快递单号，另外加个字段，尽量不要动能正常使用的代码
     *货到付款需要在conditions中加入查询限定
     */
    public function get_wait_sign_order_list(){
        $open_id = $_POST['open_id'];
        $conditions = "open_id='$open_id' AND (pay_status='SUCCESS' OR pay_status='OFFLINE') AND logistics_status ='' AND refund_reason='' ";
        //$conditions = array( 'open_id' => $open_id, 'pay_status'=>'SUCCESS', 'logistics_status' => '', 'refund_reason' => '');
        $row = DB::select('user_order',['order_id'],$conditions,'AND','order by timeStamp desc');
        $wait_sign_orders = [];
        foreach($row as $order){
            array_push($wait_sign_orders,$this->get_order_info($order->order_id));
        }
        $this->json($wait_sign_orders);
    }

    /*
     *取出已经完成订单的信息
     */
    public function get_finished_order_list(){
        $open_id = $_POST['open_id'];
        $conditions = "open_id='$open_id' AND (pay_status='SUCCESS' OR pay_status='OFFLINE') AND logistics_status = 'SIGNED' AND customer_act!='SHUTDOWN' ";
        $row = DB::select('user_order',['order_id'],$conditions,'and','order by order_date desc');
        $orders = [];
        foreach($row as $order){
            array_push($orders,$this->get_order_info($order->order_id));
        }
        $this->json($orders);
    }

    /*
     *退款完成，或者一些订单看着碍眼，用户设置其为shutdown就不再出现在用户列表中
     *用户删除订单，意思是前台不再显示，
     *这个可是最后要算总账的怎么可能删掉。故而将customer_act设置为SHUTDOWN
     * */
    public function shutdown_order($order_id){
        $conditions = "order_id='$order_id'";
        DB::update("user_order",['customer_act'=>'SHUTDOWN'],$conditions);
    }

    /*
     *取出待支付订单的信息
     */
    public function get_wait_pay_order(){
        $open_id = $_POST['open_id'];
        $conditions = "unix_timestamp(now()) - timeStamp <= 1800 AND open_id='$open_id' AND (pay_status!='SUCCESS' AND pay_status!='OFFLINE')";
        $row = DB::select('user_order',['order_id'],$conditions,'and','order by timeStamp desc');
        $wait_pay_orders = [];
        foreach($row as $order){
            array_push($wait_pay_orders,$this->get_order_info($order->order_id));
        }
        $this->json($wait_pay_orders);
    }

    /*
     *取出用户自己申请退款订单的信息
     *涉及到已经退款的还有关闭订单的
     *用户明确点击shutdown的订单，其他都给出去并在前端给出退款状态
     */
    public function get_refund_list() {
        $open_id = $_POST['open_id'];
        $conditions = "open_id='$open_id' AND refund_status!='' AND customer_act!='shutdown' ";
        $row = DB::select('user_order',['order_id'],$conditions);
        $refund_list = [];
        foreach($row as $order){
            array_push($refund_list,$this->get_order_info($order->order_id));
        }
        $this->json($refund_list);
    }

    /*
     *存储订单信息
     */
    public function store_order($order_info) {
        $order = [
            'order_id'   => $order_info['order_id'],
            'open_id'    => $order_info['open_id'],
            'address_id' => $order_info['address_id'],
            'timestamp'  => $order_info['timestamp'],
            'order_date' => date("Y-m-d H:i:s",time()),
            'total_fee'  => $order_info['total_fee'],
            'nonceStr'   => $order_info['nonceStr'],
            'paySign'    => $order_info['paySign'],
            'package'    => $order_info['package'],
            'user_words' => $order_info['user_words'],
        ];
        if(isset($order_info['pay_status'])){//如果货到付款，这里的pay_status为OFFLINE
            $order['pay_status'] = $order_info['pay_status'];
        }
        if(isset($order_info['seller_act'])){//如果货到付款，需要将其设置为WAIT_PICK
            $order['seller_act'] = $order_info['seller_act'];
        }
        DB::insert('user_order',$order);
        foreach($order_info['goods_list'] as $goods) {
            $goods['order_id'] = $order['order_id'];
            DB::insert('goods_in_order',$goods);
            //从购物车删除商品
            DB::delete('shop_cart',['open_id'=>$order['open_id'],'goods_id'=>$goods['goods_id']]);
        }
    }

    /*
     * 删除订单
     * 订单号唯一，所以只用给出order_id即可
    * */
    public function delete_order($order_id){
        DB::delete('goods_in_order',['order_id'=>$order_id]);
        $row = DB::delete('user_order',['order_id'=>$order_id]);
        echo $row;
    }

    public function pay_success($order_id){
        $res = $this->get_order_info($order_id);
        $this->json($res);
    }

    public function get_seller_telphone($order_id){
        $seller_id = (DB::row('user_order',['picker_id'],['order_id'=>$order_id]))->picker_id;
        $telphone =  DB::row('seller',['telphone'],['open_id'=>$seller_id]);
        if(!isset($telphone)) {
            $telphone =  DB::row('seller',['telphone'],['role'=>'root']);
        }
        $this->json($telphone);
    }

    //发起退款请求
    public function request_refund(){
        //if($this->checkLogin()){
        $open_id = $_POST['open_id'];
        $order_id = $_POST['order_id'];
        $reason = $_POST['reason'];
        if(isset($open_id) && isset($order_id) && isset($reason)){
            $conditions = "open_id='$open_id' AND order_id='$order_id' ";
            DB::update("user_order",['refund_reason'=>$reason,'refund_status'=>'W'],$conditions);
            echo "true";
            OrderTunnel::broadcast('refund',['order_id'=>$order_id]);;
        }else{
            echo "false";
        }
    }


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

    /*
     *货到付款
     *本函数需要完成1DB中订单支付态为offline
     *给商家信道发送订单
     * */ 
    public function pay_offline(){
        //生成订单详细信息存储DB
        $timestamp = time();
        $order_info = json_decode($_POST['order_info'],true);
        $order_info['order_id'] = $timestamp.Util::getNum(5);//时间戳加上5位随机码生成订单号
        $order_info['timestamp'] = $timestamp;
        $order_info['pay_status'] = 'OFFLINE';
        $order_info['seller_act'] = 'WAIT_PICK';
        $order_info['nonceStr']  = '';
        $order_info['paySign']  = '';
        $order_info['package']  = '';
        $this->store_order($order_info);
        self::sms_notify($order_info['order_id']);//短信告知商家
        //下单信息进行信道广播
        $broadcast_order_info = $this->get_order_info($order_info['order_id']);
        OrderTunnel::broadcast('order',array(
                'who' => "system",
                'order_info' => $broadcast_order_info,
            ));
        $this->json($broadcast_order_info);
    }

    public static function sms_notify($order_id){
        $appid=1400198588;
        $appkey='af0c3cc4ea6a6ac22a4c29f3c45a9ca2';
        // 需要发送短信的手机号码
        //$phoneNumbers = ["13308570523"];
        ////$phoneNumbers = ["13339676699"];
        //$templateId = 309624;  // NOTE: 这里的模板ID`7839`只是一个示例，真实的模板ID需要在短信控制台中申请
        //$smsSign = "快乐猫"; // NOTE: 签名参数使用的是`签名内容`，而不是`签名ID`。这里的签名"腾讯云"只是一个示例，真实的签名需要在短信控制台申请
        //try {
            ////$ssender = new SmsSingleSender($appid, $appkey);//单发
            //$ssender = new SmsMultiSender($appid, $appkey);//群发Kj
            //$params = [substr($order_id,11)];//数组具体的元素个数和模板中变量个数必须一致，例如事例中 templateId:5678对应一个变量，参数数组中元素个数也必须是一个;
            //$result = $ssender->sendWithParam("86", $phoneNumbers, $templateId,
                //$params, $smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            //$rsp = json_decode($result);
            //echo $result;
        //} catch(\Exception $e) {
            //echo var_dump($e);
        //}
    }
}
