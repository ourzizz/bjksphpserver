<?PHP
//本文件，为商品分类提供基础操作
use \QCloud_WeApp_SDK\Helper\Request as Request;
use \QCloud_WeApp_SDK\Constants as Constants;
use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\WxPay\WxPay as WP;
use \QCloud_WeApp_SDK\Myapi\Mingan as MG;
defined('BASEPATH') OR exit('No direct script access allowed');

class TestMinganci extends CI_Controller{
    public function  test(){
        $content = $_POST['content']
        MG::includeMgc($content);
    }
}
