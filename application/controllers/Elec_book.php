<?PHP
//本文件，用户购买电子书后从列表拉取
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Tunnel\ITunnelHandler as ITunnelHandler;
use \QCloud_WeApp_SDK\Tunnel\TunnelService as TunnelService;
defined('BASEPATH') OR exit('No direct script access allowed');

class Elec_book extends CI_Controller{
    //raw pdo获取select结果的方法
    public static function get_all_payed_ebooks($open_id){
        $sql = "SELECT DISTINCT goods.* FROM goods,user_order,goods_in_order WHERE user_order.open_id='$open_id' and user_order.pay_status='SUCCESS' and user_order.order_id=goods_in_order.order_id and goods.goods_id=goods_in_order.goods_id and goods.type='ele'";
        $res = DB::raw($sql);
        $arr = $res->fetchAll(PDO::FETCH_ASSOC);
        return $arr;
    }

    public function get_all_ebooks(){
        $openId = $_POST['openId'];
        $arr = self::get_all_payed_ebooks($openId);
        $this->json($arr);
    }

    //判断发起请求的用户是否付费了
    public static function is_pay($open_id,$goods_id){
        $arr = self:: get_all_payed_ebooks($open_id);
        foreach($arr as $a){
            if($a['goods_id']===$goods_id){
                return true;
            }
        }
        return false;
    }

    public function get_timu_list(){
        $goods_id = $_POST['goods_id'];
        $openId = $_POST['openId'];
        if(self::is_pay($openId,$goods_id)){
            $res = [];
            $res['shijuan'] = (DB::row('goods',['name'],['goods_id'=>$goods_id]))->name;
            $res['timu_list'] = DB::select('tiku',['*'],['goods_id'=>$goods_id]);
            $this->json($res);
        }else{
            $this->json("notpay");
        }
    }
}
