<?PHP
//本文件为前端提供商品的信息
// community_file
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Cos\CosAPI as Cos;
use \QCloud_WeApp_SDK\Constants as Constants;
defined('BASEPATH') OR exit('No direct script access allowed');

class Pub_msg extends CI_Controller {

    //模块化，将删除对象的操作独立出来
    public static function delete_object($img_name){
        $cosClient = Cos::getInstance();
        $cosConfig = Conf::getCos();
        $result = $cosClient->deleteObject(array(
            'Bucket' =>'community',
            'Key' => $img_name));
        return $result;
    }

    public function get_all_messages(){
       $res = DB::select('community_files',['*']);
       $this->json($res);
    }

    public static function store_message ($message,$images){
        //print_r($images);
        DB::insert('community_files',$message);
        $file_id = DB::row('community_files',['file_id'],['title'=>$message['title'],'pubtime'=>$message['pubtime']]);
        foreach ($images as $img){
            DB::insert('imgs_buket',['type'=>'community_file','type_id'=>$file_id->file_id,'url'=>$img]);
        }
        return $file_id;
    }

    public function user_publish_message(){
        $message = json_decode($_POST['message'],true);
        $images = json_decode($_POST['images'],true);
        $file_id = self::store_message($message,$images);
        $this->json($file_id);
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

    public function get_file_by_id(){
        $file_id = $_POST['file_id'];
        $content = DB::row('community_files',['*'],['file_id'=>$file_id]);
        $images = DB::select('imgs_buket',['url'],['type_id'=>$file_id,'type'=>'community_file']);
        $res = [];
        $res['content']=$content;
        $res['images']= array_values($images);
        $this->json($res);
    }

    public function  delete_message(){
        $file_id = $_POST['file_id'];
        DB::delete('community_files',['file_id'=>$file_id]);
        $images = DB::select('imgs_buket',['url'],['type_id'=>$file_id]);
        foreach($images as $img){
            self::delete_object($img->url);
        }
        DB::delete('imgs_buket',['type_id'=>$file_id]);
    }

    public function  delete_db_img(){
        //$file_id = $_POST['file_id'];
        $img_name = $_POST['img_name'];
        DB::delete('imgs_buket',['url'=>$img_name]);
    }

    public function  img_to_db(){
        $file_id = $_POST['file_id'];
        $img_name = $_POST['img_name'];
        DB::insert('imgs_buket',['type_id'=>$file_id,'url'=>$img_name,'type'=>'community_files']);
    }

    public function pub_msgdb_add_img(){
    }

}
