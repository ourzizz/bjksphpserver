<?php
/**
 * Shopcart_model 为了能够从系统中剥离，整个商城模块的文件全部专项专做
 * 这个模型只管理对goods这个表的增删改
 * @author qxnbd
 */
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
class Shopcart_model extends CI_Model {
    public function __construct() {
        $this->load->database();
    }

    /*本文件内部逻辑的功能*/
    public function is_goods_exsit_in_user_cart($open_id,$goods_id) {//判断商品是否已经存在
        $query = $this->db->query(sprintf(' SELECT * FROM shop_cart WHERE open_id="%s" AND goods_id="%s" ',$open_id,$goods_id));
        return $query->result_array();
    }

    public function get_goods_stock($goods_id) {//获得商品最大库存
        $query = $this->db->query(sprintf(' SELECT remain FROM goods WHERE goods_id="%s" ',$goods_id));
        return $query->result_array();
    }
    public function select_user_goods_count($open_id,$goods_id)
    {//数据库中查询过来的int数据都会变成字符串存入数组
        $query=$this->db->query(sprintf("select count from shop_cart WHERE  `open_id`='%s' AND `goods_id`='%s' ",$open_id,$goods_id));
        return $query->result_array();
    }
    public function is_over_stock($open_id,$goods_id) { //判断购物车中的量是否已经达到上限，是返回true否返回false
        $query=$this->db->query(sprintf("select shop_cart.count,goods.remain from shop_cart,goods WHERE  shop_cart.goods_id=goods.goods_id AND shop_cart.open_id='%s' AND goods.goods_id='%s' ",$open_id,$goods_id));
        $res = $query->result_array();
        if((int)$res[0]['count'] >= (int)$res[0]['remain']) {
            //echo "overflow";
            return true;
        }else{
            return false;
        }
    }
    /*本文件内部逻辑的功能*/
    public function reduce_count($open_id,$goods_id) {
        $count=$this->select_user_goods_count($open_id,$goods_id);
        if((int)$count[0]['count'] > 0){//数量如果<=0就不能再减少 
            //echo $count[0]['count'];
            $this->db->query(sprintf("UPDATE `shop_cart` SET count = count - 1 WHERE `open_id`='%s' AND `goods_id`='%s' ",$open_id,$goods_id));
            $count=$this->select_user_goods_count($open_id,$goods_id);
        }
        return $count;
    }

    public function add_count($open_id,$goods_id) {//插入成功返回true否则溢出false
        if($this->is_over_stock($open_id,$goods_id)) {
            return false;
        } else{
            $query=$this->db->query(sprintf("UPDATE `shop_cart` SET count = count + 1 WHERE `open_id`='%s' AND `goods_id`='%s' ",$open_id,$goods_id));
            return true;
        }
    }

    public function delete_goods($open_id,$goods_id) {
        $query=$this->db->query(sprintf("DELETE FROM `shop_cart` WHERE `shop_cart`.`cart_id` = 5 AND `shop_cart`.`open_id` = %s AND `shop_cart`.`goods_id` = '%s'",$open_id,$goods_id));
    }

    public function insert_user_chose_goods($open_id,$goods_id) {
        //如果数据库中已经存在这条记录，那么只需要增加它的数目,插入的数量不能大于最大库存
        $goods_in_db = $this->is_goods_exsit_in_user_cart($open_id,$goods_id);
        $type = (DB::row('goods',['type'],['goods_id'=>$goods_id]))->type;
        if($goods_in_db == NULL) {//res为空 不存在 直接加入
            $query = $this->db->query(sprintf("INSERT INTO shop_cart (`cart_id`, `timestrap`, `count`, `open_id`, `goods_id`) VALUES (NULL, now(), 1, '%s', '%s')",$open_id,$goods_id));
            return true;
        }elseif($type === 'ele'){//购物车中已经有该电子书了 直接返回false
            return false;
        } else{//goods已经存在，进行自增
            return  $this->add_count($open_id,$goods_id);
        }
    }

    public function get_cart_num($open_id) {
        $query = $this->db->query(sprintf("select count(*) from shop_cart where open_id='%s'",$open_id));
        return $query->result_array();
    }
    public function select_user_has_goods($open_id) {
        $query = $this->db->query(sprintf("SELECT shop_cart.open_id,shop_cart.count,shop_cart.timestrap,goods.name,goods.price,goods.goods_id,goods.face_img,goods.remain,goods.danwei,goods.type from goods,shop_cart WHERE goods.goods_id = shop_cart.goods_id and shop_cart.open_id = '%s' ",$open_id));
        return $query->result_array();
    }

    public function update_user_goods_count($open_id,$goods_id,$count) {//从前端来的数据已经已经过滤掉超过范围的商品数量
        $query = $this->db->query(sprintf("UPDATE `shop_cart` SET `count` = %s WHERE `shop_cart`.`open_id` = '%s' AND `shop_cart`.`goods_id` = '%s'",$count,$open_id,$goods_id));
        return "success";
    }
    public function delete_user_goods($open_id,$goods_id) {
        $query = $this->db->query(sprintf("DELETE FROM `shop_cart` WHERE `shop_cart`.`open_id` = '%s' AND  `shop_cart`.`goods_id` = %s ",$open_id,$goods_id));
        return "success";
    }
    public function select_sum_count($open_id) { //
        $query = $this->db->query(sprintf("select sum(count) as sum from shop_cart where open_id='%s'",$open_id));
        return $query->result_array();
    }
}
