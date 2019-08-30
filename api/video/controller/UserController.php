<?php 
namespace api\video\controller;

use api\video\model\UserModel;
use cmf\controller\RestUserBaseController;

class UserController extends RestUserBaseController
{
  public function user_info(){
    $userId = $this->getUserId();
    $user = UserModel::where(['id'=>$userId])->find();

    $this->success('获取成功！',$user);
  }
}