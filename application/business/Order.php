<?php
/*关于订单的基础操作都放这里
 *用户需要操作订单,商家也需要
 * */
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;

class Order{
    public static function store_order($order_info){
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
        ];
        DB::insert('user_order',$order);
        foreach($order_info['goods_list'] as $goods) {
            $goods['order_id'] = $order['order_id'];
            DB::insert('goods_in_order',$goods);
            //从购物车删除商品
            DB::delete('shop_cart',['open_id'=>$order['open_id'],'goods_id'=>$goods['goods_id']]);
        }
    }

    public static function delete_order($order_id){
        DB::delete('goods_in_order',['order_id'=>$order_id]);
        $row = DB::delete('user_order',['order_id'=>$order_id]);
        echo $row;
    }

    /*
     *取出订单详细信息
     */
    public static function get_order_info($order_id){
        $order = DB::row('user_order',['*'],"order_id='$order_id'");
        if(isset($order)) {
            $address = DB::row('user_address',['*'],"address_id='$order->address_id'");
            $goods_list = DB::select('goods_in_order',['*'],['order_id'=>$order_id]);
            foreach($goods_list as &$goods){
                $goods_id = $goods->goods_id;
                $goods_info = DB::row('goods',['name','face_img','price','danwei'],['goods_id'=>$goods_id]);
                $goods = array_merge((array)($goods),(array)($goods_info));
            }
            $res = compact('order','address','goods_list');
            return $res;
        }
    }
}

