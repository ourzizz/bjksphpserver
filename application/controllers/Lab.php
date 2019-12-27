<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \QCloud_WeApp_SDK\Conf as Conf;
use \QCloud_WeApp_SDK\Cos\CosAPI as Cos;
use \QCloud_WeApp_SDK\Constants as Constants;

class Lab extends CI_Controller {
    public function rmfile(){
        $cosClient = Cos::getInstance();
        $cosConfig = Conf::getCos();
        $result = $cosClient->deleteObject(array(
            'Bucket' =>'community',
            'Key' => 'message/1a7a99e613f1445e8b50bc583fe58df4-wx98b3be7df79c0bdc.o6zAJs4k-xTiu0aU33eQS8Ng4sC4.dQXD7hklK2Ul8f15469dff8b27e5918a1dad4db1c534.png'));
        $this->json($result->toArray());
    }

}
