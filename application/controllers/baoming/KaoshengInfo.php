<?PHP
//本文件，为商品分类提供基础操作
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Cos\CosAPI as Cos;
use \QCloud_WeApp_SDK\Constants as Constants;
use \QCloud_WeApp_SDK\Myapi\Mingan as MG;
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
defined('BASEPATH') OR exit('No direct script access allowed');
class KaoshengInfo extends CI_Controller{

    public function get_kaoshengInfo(){
        $open_id = $_POST['open_id'];
        $ksid = $_POST['ksid'];
        $res = [];
        $res['kaoshengInfo'] = DB::row('kaoshengInfo',['*'],['open_id'=>$open_id,'ksid'=>$ksid]);
        $this->json($res);
    }

    public function get_baomingInfo(){
        $open_id = $_POST['open_id'];
        $ksid = $_POST['ksid'];
        $res = [];
        $res['baomingInfo'] = DB::row('kaoshengbaoming',['*'],['open_id'=>$open_id,'ksid'=>$ksid]);
        $this->json($res);
    }

    public function get_config(){
        $ksid = $_POST['ksid'];
        $res = [];
        $res['config'] = DB::row('eventtime',['*'],['ksfileid'=>$ksid,'config'=>'1']);
        $this->json($res);
    }

    public function get_zhiwei(){
        $ksid = $_POST['ksid'];
        $res = [];
        $sql = sprintf("select * FROM `zhiwei` WHERE ksid='%s' ORDER BY code",$ksid);
        $res['zhiwei'] = (DB::raw($sql))->fetchAll(PDO::FETCH_ASSOC);
        $this->json($res);
    }

    public function get_kaosheng_kaoshi(){
        $open_id = $_POST['open_id'];
        $ksid = $_POST['ksfileid'];
        $configId = $_POST['configId'];
        $res = [];
        $res['kaoshengInfo'] = DB::row('kaoshengInfo',['*'],['open_id'=>$open_id,'ksid'=>$ksid]);
        $sql = sprintf("select * FROM `zhiwei` WHERE ksid='%s' ORDER BY code",$ksid);
        $res['zhiwei'] = (DB::raw($sql))->fetchAll(PDO::FETCH_ASSOC);
        $res['baomingInfo'] = DB::row('kaoshengbaoming',['*'],['open_id'=>$open_id,'ksid'=>$ksid]);
        $res['config'] = DB::row('eventtime',['*'],['id'=>$configId]);
        $this->json($res);
    }

    public function bmconfirm(){
        $baomingInfo = json_decode($_POST['baomingInfo'],true);
        $open_id = $baomingInfo['open_id'];
        $ksid = $baomingInfo['ksid'];
        DB::update('kaoshengbaoming',['bmconfirm'=>1],['open_id'=>$open_id,'ksid'=>$ksid]);
    }
    public function baoming(){
        //最起码提供open_id ksid code 才能插入
        $baomingInfo = json_decode($_POST['baomingInfo'],true);
        $ksid = $baomingInfo['ksid'];
        $open_id = $baomingInfo['open_id'];
        if(!empty($baomingInfo)){
            $exist = DB::row('kaoshengbaoming',['*'],['open_id'=>$open_id,'ksid'=>$ksid]);
            if($exist == null){//在数据库中不存在新增报名信息 生成报名序号
                self::store_baokao_info($baomingInfo);
                $sql = " select count(*) from kaoshengbaoming where ksid='$ksid' and uqid<( select uqid from kaoshengbaoming where open_id='$open_id' and ksid='$ksid')";
                $xuhao =((DB::raw($sql)->fetchAll(PDO::FETCH_ASSOC))[0]['count(*)']) + 1; 
                $bmxh = self::g_bmxh($ksid,$xuhao);
                DB::update('kaoshengbaoming',['bmxh'=>$bmxh],['open_id'=>$open_id,'ksid'=>$ksid]);
            }else{//已经报过名直接update
                DB::update('kaoshengbaoming',['code'=>$baomingInfo['code']],['open_id'=>$open_id,'ksid'=>$ksid]);
            }
            $res = DB::row('kaoshengbaoming',['*'],['open_id'=>$open_id,'ksid'=>$ksid]);
            $this->json($res);
        }
    }
    public static function g_bmxh($ksid,$code){
        //config字段为1表示为本考试的配置项
        $prefix = (DB::select('eventtime',['prefix'],['ksfileid'=>$ksid,'config'=>'1'])[0])->prefix;
        $a = '00000';
        $b = $a . $code;
        $bmxh =$prefix . substr($b,-5);
        return $bmxh;
    }

    public function store_baokao_info($baomingInfo){
        //此函数考生第一次提交信息 新增报考信息
        //同时需要生成报名序号,insert后查询出uid 查询出所有同考试的uid 和前缀生成报名序号并返回给前端
        $baomingInfo["bmtime"] = date('Y-m-d H:i:s');
        DB::insert('kaoshengbaoming',$baomingInfo);
        //g_baoming_xuhao()
    }

    public function store_kaosheng(){
        $kaoshengInfo = json_decode($_POST['kaoshengInfo'],true);
        DB::insert('kaoshengInfo',$kaoshengInfo);
    }

    public function update_kaosheng(){
        $open_id = $_POST['open_id'];
        $ksid = $_POST['ksid'];
        $kaoshengInfo = json_decode($_POST['kaoshengInfo'],true);
        if(!empty($kaoshengInfo)){
            DB::update('kaoshengInfo',$kaoshengInfo,['ksid'=>$ksid,'open_id'=>$open_id]);
        }
    }


    /*
     *前端请求数据库添加图片
     *同步传入数据库
     * */
    public function update_db_img(){
        $open_id = $_POST['open_id'];
        $ksid = $_POST['ksid'];
        $imgUrl = $_POST['imgUrl'];
        $res = DB::update('kaoshengInfo',['photoUrl'=>$imgUrl],['open_id'=>$open_id,'ksid'=>$ksid]);
    }

    /*
     * 前端请求删除图片接口
     * 删除cos中的图片
     * 删除name格式 去掉前面的域名信息从 /filename/....开始
     * */
    public function delete_cos_img(){
        $img_name = $_POST['img_name'];
        $result = self::delete_object($img_name);
        $this->json($result);
    }

    //模块化，将删除对象的操作独立出来
    public static function delete_object($img_name){
        $cosClient = Cos::getInstance();
        $cosConfig = Conf::getCos();
        $result = $cosClient->deleteObject(array(
            'Bucket' =>'bjks',
            'Key' => $img_name));
        return $result;
    }
}
