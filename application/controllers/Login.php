<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use QCloud_WeApp_SDK\Auth\LoginService as LoginService;
use QCloud_WeApp_SDK\Constants as Constants;

class Login extends CI_Controller {
    public function index() {
        $result = LoginService::login();
        
        if ($result['loginState'] === Constants::S_AUTH) {
            $this->json([
                'code' => 0,
                'data' => $result['userinfo']
            ]);
        } else {
            $this->json([
                'code' => -1,
                'error' => $result['error']
            ]);
        }
    }

    /*
     *是否显示商家入口
     *这个函数后期必须做调整，多做几道安防
     *现在的目标是先跑起来
     * */
    public function is_adm($open_id){
        //$open_id = $_POST['open_id'];
        $row = DB::row('seller',['*'],['open_id' => $open_id]);
        $is_adm = ['is_adm'=>'false'];
        if(isset($row->role)){
            $row->is_adm = "true";
        }else{
            $row = ['is_adm'=>'false'];
        }
        $this->json($row);
    }

    public function get_role(){
        $open_id = $_POST['openId'];
        $row = DB::row('seller',['role'],['open_id' => $open_id]);
        if(!$row){
            $this->json(['role'=>'NULL']);
            return ;
        }
        $this->json($row);
    }
}

