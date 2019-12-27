<?PHP
//本文件社区业主发文逻辑
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Cos\CosAPI as Cos;
use \QCloud_WeApp_SDK\Constants as Constants;
use \QCloud_WeApp_SDK\Myapi\Mingan as MG;

defined('BASEPATH') OR exit('No direct script access allowed');
class Community extends CI_Controller {
    /*
     * 根据分类id获得消息列表
     * */
    public function get_msgs_by_class_id (){
        $class_id = $_POST['class_id'];
        $conditions = "class_id='$class_id' AND onoff='on' order by msg_id desc limit 5";
        $msgs = DB::select('community_msg',['*'],$conditions);
        $this->json($msgs);
    }

    /*
     * 根据消息id获取消息详细内容
     * */
    public function get_msg_by_id (){
        $msg_id = $_POST['msg_id'];
        $msg = DB::row('community_msg',['*'],['msg_id'=>$msg_id]);
        $class_name = DB::row('goods_class',['class_name'],['class_id'=>$msg->class_id]);
        $res['message'] = $msg;
        $res['class_name'] = $class_name->class_name;
        //$this->json($msg);
        $this->json($res);
    }

    /*获取用户发布的消息列表
     * */
    public function get_user_messages($open_id){
        $conditions = "open_id='$open_id' AND onoff='on'";
        $res = DB::select('community_msg',['*'],$conditions);
        $this->json($res);
    }

    /*
     *
     * */
    public function lazy_load_msg() {
        $class_id = $_POST['class_id'];
        $min_id = $_POST['min_id'];
        $conditions = "class_id='$class_id' AND msg_id<$min_id AND onoff='on' order by msg_id desc limit 5";
        $msgs = DB::select('community_msg',['*'],$conditions);
        $this->json($msgs);
    }

    /*
     * */
    public static function store_message ($message){
        $message['pubtime'] =  date("Y-m-d H:i:s",time());
        $message['onoff'] =  'on';
        $result = DB::insert('community_msg',$message);
        return  $result;
    }

    public function user_delete_msg($msg_id){
        $msg = DB::row('community_msg',['images_name'],['msg_id'=>$msg_id]);
        $nameList = explode(",",$msg->images_name);
        foreach($nameList as $img_name){
            if($img_name !== ''){
                self::delete_object($img_name);
            }
        }
        $conditions = "msg_id='$msg_id'";
        $res = DB::delete('community_msg',$conditions);
    }

    /*发布锁
    * */
    public static function forbidden_publish($open_id){
        //$conditions = "open_id='$open_id' order by pubtime";
        //$res = DB::row('community_msg',['pubtime'],$conditions);
        //print_r($res);
        //if(isset($res))
        //$seconds = time() - strtotime($res->pubtime);
        //if($seconds < 86400){// seconds大于24小时返回true表示禁止发布,否则返回false不禁止(看来熬夜真的是点火烧鸡吧这么明显的logic_bug都能写出来)
            //return true;     //小于86400还在限制期内返回true禁止发布
        //}else{
            return false;
        //}
    }

    public function user_publish_message(){
        $message = json_decode($_POST['message'],true);
        $ismingan = MG::includeMgc($message['content']);
        if($ismingan['errcode'] == 87014){//是否通过敏感测试
            $this->json(['result'=>'mingan']);//敏感
        }else{
            $forbidden = self::forbidden_publish($message['open_id']);
            if(isset($message) && !$forbidden){
                $result = self::store_message($message);
                $this->json(['result'=>$result]);
            }else{
                $this->json(['result'=>'forbidden']);
            }
        }
    }

    /*
     * 获取社区公告消息列表
     * */
    public function get_head_msgs (){
        $conditions = "class_id=255";
        $msgs = DB::select('community_msg',['*'],$conditions);
        $this->json($msgs);
    }

    /*
     *前端请求删除图片
     涉及cos和DB必须同步
     update数据库url
     * */
    public function delete_db_img(){
        $msg_id = $_POST['msg_id'];
        $imgName = $_POST['img_name'];
        $msg = DB::row('community_msg',['images_name'],['msg_id'=>$msg_id]);
        //print_r($msg->images_name);
        if(isset($msg)){
            $nameList = explode(",",$msg->images_name);
            $index = array_search($imgName,$nameList);
            if($index >= 0){
                array_splice($nameList,$index,1);
                $msg->images_name = join(",",$nameList);
                $res = DB::update('community_msg',['images_name'=>$msg->images_name],['msg_id'=>$msg_id]);
                $this->json($res);
            }
        }
    }

    /*
     *前端请求数据库添加图片
     *同步传入数据库
     * */
    public function db_add_img(){
        $msg_id = $_POST['msg_id'];
        $imgName = $_POST['img_name'];
        $msg = DB::row('community_msg',['images_name'],['msg_id'=>$msg_id]);
        $nameList = [];
        if(isset($msg)){
            if(isset($msg->images_name) && ($msg->images_name != '')){
                $nameList = explode(",",$msg->images_name);
            }
            array_push($nameList,$imgName);
            $msg->images_name = join(",",$nameList);
            $res = DB::update('community_msg',['images_name'=>$msg->images_name],['msg_id'=>$msg_id]);
            $this->json($res);
        }
    }

    /*
     * 前端请求删除图片接口
     * 删除cos中的图片
     * */
    public function delete_cos_img(){
        $img_name = $_POST['img_name'];
        $result = self::delete_object($img_name);
    }

    /*
     * 前端修改消息完毕提交的时候需要更新DB
     */
    public function update_message(){
        $message = json_decode($_POST['message'],true);
        $result = DB::update('community_msg',$message,['msg_id'=>$message['msg_id']]);
        return  $result;
    }

    //模块化，将删除对象的操作独立出来
    public static function delete_object($img_name){
        $cosClient = Cos::getInstance();
        $cosConfig = Conf::getCos();
        $result = $cosClient->deleteObject(array(
            'Bucket' =>'community',
            'Key' => $img_name));
        return $result;
    }
}
