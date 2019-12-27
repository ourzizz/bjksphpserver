<?PHP
//本文件，为商品分类提供基础操作
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Tunnel\ITunnelHandler as ITunnelHandler;
use \QCloud_WeApp_SDK\Tunnel\TunnelService as TunnelService;
defined('BASEPATH') OR exit('No direct script access allowed');

class Class_manager extends CI_Controller{
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
        $conditions = "lft>$node->lft AND rgt<$node->rgt AND layer=$node->layer+1 order by layer,priority";
        $sons = DB::select('goods_class',['*'],$conditions);
        return $sons;
    }

    //获取分类页面的商品，这里商品不需要详细信息
    private static function get_class_page_goods($class_id){
        $goods = DB::select('goods',['goods_id','class_id','name','price','remain','face_img','danwei'],['class_id'=>$class_id]);
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
        $conditions = "rgt>lft+1 order by layer,priority";//相当于遍历了整棵树，得到所有非叶子节点
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
        $res = [];
        if(isset($root)){
            $conditions = "rgt>lft+1 AND lft>$root->lft AND rgt<$root->rgt order by layer,priority";//相当于遍历了整棵树，得到所有非叶子节点
            $nodes = DB::select('goods_class',['*'],$conditions);
            array_unshift($nodes,$root);
            $parent_son = [];
            foreach($nodes as $node){
                $sons = self::get_sons($node);
                $jnode = ['parent_id'=>$node->class_id,'son_list'=>$sons];
                array_push($parent_son,$jnode);
            }
            $res['root']=$root;
            $res['parent_son']=$parent_son;
            $this->json( $res);
        }
    }

    public function user_update_class(){//name:1,telphone2,area3,detail:4
       //数据格式[{"key":1,"value":"chenhai"},{"key":2,"value":"123123123"},{"key":3,"value":["湖北省","黄石市","黄石港区"]},{"key":4,"value":"xxxxx"}] 
        $open_id = $_POST['open_id'];
        $class_id = $_POST['class_id'];
        $target =  json_decode($_POST['class_info'],true);
        //$this->json($target);
        foreach($target as $a) {
            switch ($a['key']) {
            case 1:
                DB::update('goods_class',['class_name'=>$a['value']],['class_id'=>$class_id]);
                break;
            case 2:
                DB::update('goods_class',['onoff'=>$a['value']],['class_id'=>$class_id]);
                break;
            }
        }
    }

    public function delete_class(){//避免误操作，本函数只提供叶子节点的删除功能
        $class_id = $_POST['class_id'];
        $node = DB::row('goods_class',['*'],['class_id'=>$class_id]);
        if($_POST['pwd'] == 'tubanfa'){//最土最土的办法post来的密码对的上才能修改
            $son_node_count = ($node->rgt - $node->lft + 1) / 2;
            if($son_node_count == 1){//确保前端传过来的是叶子节点
                $sql=sprintf('DELETE FROM goods_class WHERE class_id = %s',($node->class_id));   //先删去,否则做了-2操作后会出现多删掉一行的bug
                DB::raw($sql);
                $sql=sprintf('UPDATE goods_class SET rgt = rgt - 2 WHERE rgt > %s ',($node->rgt));   //先删去,否则做了-2操作后会出现多删掉一行的bug
                DB::raw($sql);
                $sql=sprintf('UPDATE goods_class SET lft = lft - 2 WHERE  lft > %s',($node->rgt));   //先删去,否则做了-2操作后会出现多删掉一行的bug
                DB::raw($sql);
            }
        }
    }

    public function new_class(){//这个函数会比较复杂
        $new_class =  json_decode($_POST['new_class'],true);
        $father_id = $_POST['father_id'];
        $father_node = DB::row('goods_class',['*'],['class_id'=>$father_id]);
        $new_class['onoff'] = "on";
        $new_class['layer'] = $father_node->layer + 1;
        $new_class['lft'] = $father_node->lft + 1;
        $new_class['rgt'] = $father_node->lft + 2;
        $new_class['priority'] = 1;
        //if($){
            $sql=sprintf('UPDATE goods_class SET rgt = rgt + 2 WHERE rgt > %s ',($father_node->lft));   
            DB::raw($sql);
            $sql=sprintf('UPDATE goods_class SET lft = lft + 2 WHERE lft > %s ',($father_node->lft));  
            DB::raw($sql);
            DB::insert('goods_class',$new_class);
            $res = DB::row('goods_class',['*'],['lft'=>$new_class['lft']]);
            $this->json($res);
        //}
    }
}
