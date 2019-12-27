
<?PHP
//本文件为前端提供商品的信息
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
require APPPATH.'controllers/Elec_book.php';
defined('BASEPATH') OR exit('No direct script access allowed');
class Shopcart extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('shopcart_model');
        $this->load->helper('url_helper');
    }
    public function user_add_goods($open_id,$goods_id) {//添加商品，成功返回true 溢出返回false 
        $ebook = new Elec_book();
        if(!$ebook::is_pay($open_id,$goods_id)){//ispay接口电子书且已支付返回true
            $res = $this->shopcart_model->insert_user_chose_goods($open_id,$goods_id);
            $this->json($res);
        }else{
            $this->json("payed");
        }
    }
    public function get_user_has_goods($open_id) {//返回用户收藏的所有商品
        $data = $this->shopcart_model->select_user_has_goods($open_id);
        //$data = DB::select('shop_cart',['*'],['open_id'=>$open_id]);
        $this->json($data);
    }
    public function get_cart_sum_count($open_id) {//购物车数量求和
        $data = $this->shopcart_model->select_sum_count($open_id);
        $this->json($data[0]);
    }
    public function add_count($open_id,$goods_id) {
        $data = $this->shopcart_model->add_count($open_id,$goods_id);
        $this->json($data[0]);
    }
    public function reduce_count($open_id,$goods_id) {
        $data = $this->shopcart_model->reduce_count($open_id,$goods_id);
        $this->json($data[0]);
    }
    public function delete_goods($open_id,$goods_id) {
        $data = $this->shopcart_model->delete_goods($open_id,$goods_id);
        $this->json($data);
    }
    public function update_count($open_id,$goods_id,$count) {
        $data = $this->shopcart_model->update_user_goods_count($open_id,$goods_id,$count);
        $this->json($data);
    }
    public function delete_user_goods($open_id,$goods_id) {
        $data = $this->shopcart_model->delete_user_goods($open_id,$goods_id);
        $this->json($data);
    }
}
