<?PHP
//本文件，为商品分类提供基础操作
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
defined('BASEPATH') OR exit('No direct script access allowed');
class Index extends CI_Controller{
    public function get_all_act(){
        $open_id = $_POST['open_id'];
        $sql = "SELECT eventtime.*,ksfile.filetitle from eventtime,ksfile where eventtime.ksfileid = ksfile.fileid AND now()>eventtime.startime AND now()<eventtime.endtime AND act!='' order by eventtime.eventid,eventtime.endtime desc";
        $happening = (DB::raw($sql))->fetchAll(PDO::FETCH_ASSOC);
        $i = 0;
        $j = 0;
        $res = [];
        while(!empty($happening[$i])){ //这里没有指针，只能用这个方法判空  
            $res[$j]["event"] = $happening[$i]["act"];
            $res[$j]['filelist'] = array();
            while(!empty($happening[$i+1]) && $happening[$i]['act']== $happening[$i+1]['act']) {
                $status = DB::row('kaoshengbaoming',['*'],['open_id'=>$open_id,'ksid'=>$happening[$i]['ksfileid']]);
                array_push($res[$j]['filelist'],array_merge($happening[$i],['status'=>$status]));
                $i=$i+1;
            }
            $status = DB::row('kaoshengbaoming',['*'],['open_id'=>$open_id,'ksid'=>$happening[$i]['ksfileid']]);
            array_push($res[$j]['filelist'],array_merge($happening[$i],['status'=>$status]));
            $i=$i+1;
            $j=$j+1;
        }
        $this->json($res);
    }
}
