 <?php
 /**
  * Goods_model 为了能够从系统中剥离，整个商城模块的文件全部专项专做
  * 这个模型只管理对address这个表的增删改
  * @author qxnbd
  */
 class User_address_model extends CI_Model
 {
     public function __construct() {
         $this->load->database();
     }
 }
