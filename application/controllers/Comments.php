<?PHP
//本文件为前端提供商品的信息
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Cos\CosAPI as Cos;
use \QCloud_WeApp_SDK\Constants as Constants;
defined('BASEPATH') OR exit('No direct script access allowed');
class Comments extends CI_Controller {
    public function get_formarted_comments_by_fileId($file_id){
        $rows = DB::select('file_comment',['*'],['file_id'=>$file_id],'and','order by floor,father_id');
        $comments = [];
        $floor = [];
        $floorId = '';
        foreach($rows as $row){
            if($row->floor != $floorId){//发生换层
                if(!empty($floor)){
                    array_push($comments,$floor);
                }
                $floorId = $row->floor;
                $floor = [];
                $floor['root'] = $row;
                $floor['sons'] = [];
            }else{
                array_push($floor['sons'],$row);
            }
        }
        if(!empty($floor)){
            array_push($comments,$floor);
        }
        $this->json($comments);
    }

    public function get_comments_by_fileId($fileId){
        $rows = DB::select('file_comment',['*'],['file_id'=>$fileId],'and','order by floor,father_id');
        $this->json($rows);
    }

    public function get_user_approved_list(){
        $openId=$_POST['openId'];
        $fileId =$_POST['fileId'];
        $rows = DB::select('user_approve',['comment_id'],['open_id'=>$openId,'file_id'=>$fileId]);
        $this->json($rows);
    }

    public function storege_comment(){
        $comment = json_decode($_POST['comment'],true);
        DB::insert('file_comment',$comment);
        $row = DB::row('file_comment',['comment_id'],['open_id'=>$comment['open_id'],'pubtime'=>$comment['pubtime']]);
        $this->json($row);
    }

    //赞同取消赞同
    public function opera_approve(){
        if(isset($_POST['approveStatus'])){
            $approveTiems = (DB::row('file_comment',['approve'],['file_id'=>$_POST['file_id'],'comment_id'=>$_POST['comment_id']]))->approve;
            if($_POST['approveStatus'] == 'approve'){
                DB::insert('user_approve',['open_id'=>$_POST['open_id'],'file_id'=>$_POST['file_id'],'comment_id'=>$_POST['comment_id']]);
                $approveTiems++;
            }else if($_POST['approveStatus'] == 'cancle'){//取消赞
                DB::delete('user_approve',['open_id'=>$_POST['open_id'],'file_id'=>$_POST['file_id'],'comment_id'=>$_POST['comment_id']]);
                $approveTiems--;
            }
            $updates = ['approve'=>$approveTiems];
            $updateCondition = 'comment_id =' . $_POST["comment_id"];
            print($_POST["file_id"]);
            DB::update('file_comment',$updates,$updateCondition);
        }
    }

    /*用户修改评论
     * */
    public function update_comment(){
        $comment_id = $_POST['comment_id'];
        $content = $_POST['content'];
        $condition = "comment_id = '$comment_id'";
        DB::update('file_comment',['content'=>$content],$condition);
    }
}
