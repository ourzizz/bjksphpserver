<?PHP
defined('BASEPATH') OR exit('No direct script access allowed');
class Demo extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('file_model');
        $this->load->model('user_model');
        $this->load->model('eventtime_model');
        $this->load->model('home_model');
        $this->load->helper('url_helper');
    }
    //功能函数
    private function sortByEvent($arry)
    {//将事件冒泡排序
        $i =0;
        $res = array();
        while($i < count($arry) ) {
            if(!empty($arry[$i]["eventtime"])) {
                array_push($res,$arry[$i]) ;
            }
            $i++;
        }
        $i =0;
        while($i < count($arry) ) {
            if(empty($arry[$i]["eventtime"])) {
                array_push($res,$arry[$i]) ;
            }
            $i++;
        }
        return $res;
    }
    //功能函数
    //*首页的所有逻辑放在本段，有时间了研究下router，把控制器放出去一个文件还是太挤了
    public function get_file_list($typeid = "1") {
        $data['files'] = $this->file_model->get_files_title($typeid);
        $i = 0;
        while(!empty($data['files'][$i]))
        { //获取事件数组
            $data["files"][$i]['eventtime'] = array();
            $temp = $this->user_model->get_file_evnets($data['files'][$i]['fileid']);
            foreach($temp as $v)
            {
                array_push($data['files'][$i]['eventtime'],array('event'=>$v['event'],'starttime'=>$v['startime'],'endtime'=>$v['endtime']));
            }
            $i=$i+1;
        }
        $data['files'] = $this->sortByEvent($data['files']);
        $this->json( $data);
    }

    public function index() {
        $data['files'] = $this->file_model->get_files_title();
        $this->load->view('templates/header',$data);
        $this->load->view('article/filelist',$data);
        $this->load->view('templates/footer');
    }
    public function get_kstype() {
        $data['type'] = $this->file_model->get_kstype();
        $this->json(
            $data
        );
    }

    public function get_headline() 
    {
        $data['type'] = $this->file_model->get_headline();
        $this->json(
            $data
        );
    }
    public function get_homepage_json() {
        //首页包含头条，4个考试大类，展现指导文件列表
        $data['type'] = $this->file_model->get_kstype();
        $data['headline'] = $this->file_model->get_headline();
        $data['guidelist'] = $this->file_model->get_guide_list();
        $data['events'] = $this->home_model->get_ing_event();
        $data['newfiles'] = $this->file_model->get_new_files();
        $this->json(
            $data
        );
    }
    public function get_file_msg($typeid) 
    {//获取某个类型考试的文件和msg
        $data['msg'] = $this->file_model->get_msg($typeid);
        $data['files'] = $this->file_model->get_files_title($typeid);
        $this->json(
            $data
        );
    }
    public function get_zhuanji_list() 
    {//拿专技考试的列表
        $data['zhuanjilist'] = $this->file_model->get_zhuanji_list();
        $this->json(
            $data
        );
    }
    public function get_xianqu_list() 
    {//拿县区的列表
        $data['xianqulist'] = $this->home_model->get_county();
        $this->json(
            $data
        );
    }
    public function get_filesby_ksid_countyid($ksid,$countyid)
    {
        $data['files'] = $this->home_model->get_filesby_ksid_countyid($ksid,$countyid);
        $i = 0;
        while(!empty($data['files'][$i]))
        {//获取事件
            $data["files"][$i]['eventtime'] = array();
            $temp = $this->user_model->get_file_evnets($data['files'][$i]['fileid']);
            foreach($temp as $v)
            {
                array_push($data['files'][$i]['eventtime'],array('event'=>$v['event'],'starttime'=>$v['startime'],'endtime'=>$v['endtime']));
            }
            $i=$i+1;
        }
        $data['files'] = $this->sortByEvent($data['files']);
        $this->json( $data);
    }

    public function get_zhuanji_files_by_ksid($ksid)
    {
        $data['files'] = $this->file_model->get_zhuanji_files($ksid);
        $i = 0;
        while(!empty($data['files'][$i]))
        {
            $data["files"][$i]['eventtime'] = array();
            $temp = $this->user_model->get_file_evnets($data['files'][$i]['fileid']);
            foreach($temp as $v)
            {
                array_push($data['files'][$i]['eventtime'],array('event'=>$v['event'],'starttime'=>$v['startime'],'endtime'=>$v['endtime']));
            }
            $i=$i+1;
        }
        $this->json( $data);
    }
    public function get_new_event_files($typeid) {//将新文件和事件文件揉在一起,当首页二级目录没有选中的时候给出
        $newfile = $this->eventtime_model->get_new_files_bytype($typeid);
        $eventfile = $this->eventtime_model->get_happenning_event_files_bytype($typeid);
        $data['files'] = array();
        $i = 0;
        $j = 0;
        while(!empty($eventfile[$i])) { //将事件对应文件放入数组
            $data['files'][$j]['fileid'] = $eventfile[$j]['fileid'];
            $data['files'][$j]['pubtime'] = $eventfile[$j]['pubtime'];
            $data['files'][$j]['filetitle'] = $eventfile[$j]['filetitle'];
            $data['files'][$j]['readtime'] = $eventfile[$j]['readtime'];
            $data['files'][$j]['eventtime'] = array();
            while(!empty($eventfile[$i+1]) && $eventfile[$i]['fileid']== $eventfile[$i+1]['fileid'])
            {
                array_push ($data['files'][$j]['eventtime'],array('event'=>$eventfile[$i]["event"],'startime'=>$eventfile[$i]["startime"]));
                $i=$i+1;
            }
            array_push ($data['files'][$j]['eventtime'],array('event'=>$eventfile[$i]["event"],'startime'=>$eventfile[$i]["startime"]));
            $i=$i+1;
            $j=$j+1;
        }
        foreach($newfile as $new) {
            $i = 0;
            foreach($data['files'] as $d) {
                if($new['fileid'] == $d['fileid']) {//存在
                    $i = 1;//存在i=1
                    break;
                }
            }
            if($i == 0) {
                array_push($data['files'],$new);
            }
        }
        $this->json($data);
    }

    //********************************下面接口为给小程序的filepage提供json数据
    public function get_user_info()
    {
        $user=$this->user_model->get_user_info();
        $this->json(
            $user
        );

    }
    public function get_article_json($fileid=FALSE)
    {//只给出文章的内容
        $data = $this->file_model->get_article($fileid);
        $this->json(
            $data
        );
    }
    public function get_filepage_json($fileid)
    {
        $data['article'] = $this->file_model->get_article($fileid);
        $data['eventtime'] = $this->file_model->get_eventtime($fileid);
        $data['notify'] = $this->file_model->get_notify($fileid);
        $this->json(
            $data
        );
    }
    public function get_if_userhasfile($openId,$fileId)
    {
        $ifuserhasfile = $this->file_model->get_userHasFile($openId,$fileId);
        if(empty($ifuserhasfile))
        {
            $this->json(
                ['userhasfile' => false]
            );
        }
        else{
            $this->json(
                ['userhasfile' => true]
            );
        }
    }
    public function insert_user_file($openId,$fileId)
    {
        $this->file_model->insert_user_file($openId,$fileId);
    }
    public function delete_user_file($openId,$fileId)
    {
        $this->file_model->delete_user_file($openId,$fileId);
    }
    /*USER_PAGE*/
    public function get_user_files($openId)
    {
        $data = $this->user_model->get_user_files($openId);
        $this->json($data);
    }
    public function get_user_files_events($openId)
    {//测试OPENID opexV46WZFz9Is4xAI2zZWc4YiQE
        $data = $this->user_model->get_user_files($openId);
        $res = array();
        $i = 0;
        while(!empty($data[$i]))
        {
            $res[$i]["fileinfo"] = $data[$i];
            $res[$i]['eventtime'] = array();
            $temp = $this->user_model->get_file_evnets($data[$i]['fileid']);
            foreach($temp as $v)
            {
                array_push($res[$i]['eventtime'],array('event'=>$v['event'],'starttime'=>$v['startime'],'endtime'=>$v['endtime']));
            }
            $i=$i+1;
        }
        $res = $this->sortByEvent($res);
        $this->json($res);
    }
    //给正在进行页面提供数据
    public function get_event_files()
    {//本函数功能是将文件按照事件分类，一个事件下面要包含所有他的相关文件，数组键为事件名
        $happening = $this->eventtime_model->get_happenning_event_files();
        $impend = $this->eventtime_model->get_impend_event_files();
        $res['happening'] = array();
        $res['impend'] = array();
        $i = 0;
        $j = 0;
        while(!empty($happening[$i])) //这里没有指针，只能用这个方法判空
        { //用对了方法分分钟解决，这个算法虽然麻烦，但是可以作为例子解决其他的问题,写的挺辛苦留着吧
            //正在发生的数组放到res中，这里完全可以思考封装，赶鸭子先能跑再说
            $res['happening'][$j]["event"] = $happening[$i]["event"];
            $res['happening'][$j]['filelist'] = array();
            while(!empty($happening[$i+1]) && $happening[$i]['event']== $happening[$i+1]['event'])
            {
                array_push($res['happening'][$j]['filelist'],$happening[$i]);
                $i=$i+1;
            }
            array_push($res['happening'][$j]['filelist'],$happening[$i]);
            $i=$i+1;
            $j=$j+1;
        }
        $i = 0;
        $j = 0;
        while(!empty($impend[$i])) //这里没有指针，只能用这个方法判空
        { //用对了方法分分钟解决，这个算法虽然麻烦，但是可以作为例子解决其他的问题,写的挺辛苦留着吧
            $res['impend'][$j]["event"] = $impend[$i]["event"];
            $res['impend'][$j]['filelist'] = array();
            while(!empty($impend[$i+1]) && $impend[$i]['event']== $impend[$i+1]['event'])
            {
                array_push($res['impend'][$j]['filelist'],array('startime'=>$impend[$i]["startime"],'fileid'=>$impend[$i]['ksfileid'],'filetitle'=>$impend[$i]['filetitle']));
                $i=$i+1;
            }
            array_push($res['impend'][$j]['filelist'],array('startime'=>$impend[$i]["startime"],'fileid'=>$impend[$i]['ksfileid'],'filetitle'=>$impend[$i]['filetitle']));
            $i=$i+1;
            $j=$j+1;
        }
        $this->json($res);
        //$this->json($data);
    }
}
