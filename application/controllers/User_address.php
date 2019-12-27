<?PHP
//本文件为前端提供商品的信息
use \QCloud_WeApp_SDK\Mysql\Mysql as DB;
use \QCloud_WeApp_SDK\Helper\Logger as Logger;
defined('BASEPATH') OR exit('No direct script access allowed');
class User_address extends CI_Controller {

    /*
     *设置前全部设置为非默认，再将前端给的address_id设置为默认地址
    * */
    public function set_default_address($open_id,$address_id){
         DB::update('user_address',['default_address'=>0],['open_id'=>$open_id]);
         DB::update('user_address',['default_address'=>1],['open_id'=>$open_id,'address_id'=>$address_id]);
    }

    public function user_delete_address($open_id,$address_id){
        DB::delete('user_address',['open_id'=>$open_id,'address_id'=>$address_id]);
    }

    public function get_user_default_address($open_id) {
        $data = DB::row('user_address',['*'],['open_id'=>$open_id,'default_address'=>1]);
        $this->json($data);
    }

    public function get_user_all_address($open_id) {
        $data = DB::select('user_address',['*'],['open_id'=>$open_id]);
        $this->json($data);
    }

    //新增地址
    public function user_add_address() {//insert成功后应该返回address_id 给前台,
        //Logger::debug('adress', $address);
        $open_id = $_POST['open_id'];
        $address = json_decode($_POST['address'],true);
        $address['default_address'] = 1;
        $address['open_id'] = $open_id;
        DB::insert('user_address',$address);
    }

    public function user_update_address(){//name:1,telphone2,area3,detail:4
       //数据格式[{"key":1,"value":"chenhai"},{"key":2,"value":"123123123"},{"key":3,"value":["湖北省","黄石市","黄石港区"]},{"key":4,"value":"xxxxx"}] 
        $open_id = $_POST['open_id'];
        $address_id = $_POST['address_id'];
        $address =  json_decode($_POST['address_info'],true);
        foreach($address as $a) {
            switch ($a['key']) {
            case 1:
                DB::update('user_address',['name'=>$a['value']],['address_id'=>$address_id,'open_id'=>$open_id]);
                break;
            case 2:
                DB::update('user_address',['telphone'=>$a['value']],['address_id'=>$address_id,'open_id'=>$open_id]);
                break;
            case 3:
                DB::update('user_address',['province'=>$a['value'][0],'city'=>$a['value'][1],'county'=>$a['value'][2]],['address_id'=>$address_id,'open_id'=>$open_id]);
                break;
            case 4:
                DB::update('user_address',['detail'=>$a['value']],['address_id'=>$address_id,'open_id'=>$open_id]);
                break;
            }
        }
    }
}
