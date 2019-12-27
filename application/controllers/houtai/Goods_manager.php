<?PHP
//本文件，为商品分类提供基础操作
//这些后台操作功能都没有做验证，下一步需要完善验证操作
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Tunnel\ITunnelHandler as ITunnelHandler;
use \QCloud_WeApp_SDK\Tunnel\TunnelService as TunnelService;
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_manager extends CI_Controller{

    //get_parent_node(self,node):#返回父节点

    private static function get_node_by_id($class_id){
        $node = DB::row('goods_class',['*'],['class_id'=>$class_id]);
        return $node;
    }

    /*
     *获取该节点的所有祖先节点
     * */
    private static function get_ancestors_node($node){
        if ($node->lft != 1){//根节点左值为1,没有父节点
            $conditions = "lft < $node->lft and rgt > $node->rgt";
            $nodes = DB::select('goods_class',['*'],$conditions);
            return $nodes;
        } else{
            return [];
        }
    }

    //获取分类页面的商品，这里商品不需要详细信息
    private static function get_class_page_goods($class_id){
        $conditions = "class_id=$class_id";
        $goods = DB::select('goods',['*'],$conditions);
        return $goods;
    }

    /*
     *因为存在高级别分类有商品，而给出的是低级别的class_id此时也需要整个路径上的所有商品
     *
     * */
    public function get_goods_list_by_class_id($class_id){
        $node = self::get_node_by_id($class_id);
        $ancestors = self::get_ancestors_node($node);
        $goods_list = self::get_class_page_goods($class_id);
        foreach($ancestors as $a){
            $goods = self::get_class_page_goods($a->class_id);
            $goods_list = array_merge($goods_list,$goods);
        }
        $this->json($goods_list);
    }

    public function new_goods(){
        $goodsChanges = json_decode($_POST['new_goods'],true);
        DB::insert('goods',$goodsChanges);
    }
    public function update_goods(){
        $goodsChanges = json_decode($_POST['goods_info'],true);
        $goods_id = $_POST['goods_id'];
        DB::update('goods',$goodsChanges,['goods_id'=>$goods_id]);
    }
    public function delete_goods(){
        //$openId = $_POST['openId'];
        $goods_id = $_POST['goods_id'];//没有设置操作密码
        DB::delete('goods',['goods_id'=>$goods_id]);
    }
}
