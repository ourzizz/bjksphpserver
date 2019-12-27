<?PHP
//本文件，为商品分类提供基础操作
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Tunnel\ITunnelHandler as ITunnelHandler;
use \QCloud_WeApp_SDK\Tunnel\TunnelService as TunnelService;
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_class extends CI_Controller{

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

    //onoff为off表示本类关闭，不需要显示
    private static function get_sons($node){
        $conditions = "lft>$node->lft AND rgt<$node->rgt AND layer=$node->layer+1 AND onoff='on' order by layer,priority";
        $sons = DB::select('goods_class',['*'],$conditions);
        return $sons;
    }

    //获取分类页面的商品，这里商品不需要详细信息
    private static function get_class_page_goods($class_id){
        //$goods = DB::select('goods',['goods_id','class_id','name','price','remain','face_img','danwei'],['class_id'=>$class_id]);
        //$goods = DB::select('goods',['*'],['class_id'=>$class_id,'onoff'=>'on']);
        $conditions = "class_id=$class_id AND onoff!='off'";
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

    /*
     *生成分类页面需要的父亲儿子节点列表
     * */
    public function get_parent_son(){
        $conditions = "rgt>lft+1 AND onoff='on' order by layer,priority";//相当于遍历了整棵树，得到所有非叶子节点
        $nodes = DB::select('goods_class',['*'],$conditions);
        $parent_son = [];
        foreach($nodes as $node){
            $sons = self::get_sons($node);
            $jnode = ['parent_id'=>$node->class_id,'son_list'=>$sons];
            array_push($parent_son,$jnode);
        }
        $this->json( $parent_son);
    }

    //根据父节点名称获取整个从父节点到叶子的分类树
    public function get_pslist_by_parent_name(){
        $name = $_POST['name'];
        $root = DB::row('goods_class',['*'],['class_name'=>$name]);
        if(isset($root)){
            $conditions = "rgt>lft+1 AND onoff='on' AND lft>$root->lft AND rgt<$root->rgt order by layer,priority";//相当于遍历了整棵树，得到所有非叶子节点
            $nodes = DB::select('goods_class',['*'],$conditions);
            array_unshift($nodes,$root);
            $parent_son = [];
            foreach($nodes as $node){
                $sons = self::get_sons($node);
                $jnode = ['parent_id'=>$node->class_id,'son_list'=>$sons];
                array_push($parent_son,$jnode);
            }
            $this->json( $parent_son);
        }
    }
}
