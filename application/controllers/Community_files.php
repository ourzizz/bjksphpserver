<?PHP
//本文件为前端提供商品的信息
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Cos\CosAPI as Cos;
use \QCloud_WeApp_SDK\Constants as Constants;
defined('BASEPATH') OR exit('No direct script access allowed');
class Community_files extends CI_Controller {
    //给出前五条文件
    public function get_preview_file_list(){
        $files = DB::select('community_files',['file_id','title','pubtime','puborg'],'','and',$suffix = 'order by pubtime desc limit 5');
        $this->json($files);
    }

    //给出前五条文件
    public function get_all_file_list(){
        $files = DB::select('community_files',['file_id','title','pubtime','puborg'],'','and',$suffix = 'order by pubtime desc');
        $this->json($files);
    }

    //public function get_file_content($file_id){
        //$content = DB::row('community_files',['*'],['file_id'=>$file_id]);
        //$this->json($content);
    //}

    public function get_file_content($file_id){
        $content = DB::row('community_files',['*'],['file_id'=>$file_id]);
        $images = DB::select('imgs_buket',['url'],['type_id'=>$file_id,'type'=>'community_file']);
        $res = [];
        $res['content']=$content;
        $res['images']= array_values($images);
        $this->json($res);
    }
}
