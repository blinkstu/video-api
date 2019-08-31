<?php

namespace api\video\controller;

use api\video\model\RewardLogModel;
use cmf\controller\RestUserBaseController;
use api\video\model\UserModel;
use api\video\model\UserPromotionModel;
use think\facade\Config;

class PromotionController extends RestUserBaseController
{
  private function generateRandomString($length = 10)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
  }

  private function getCode()
  {
    $check = true;
    while ($check) {
      $code = $this->generateRandomString();
      $check = UserPromotionModel::where('code', $code)->find();
    }
    return $code;
  }

  public function code()
  {
    $userId = $this->getUserId();
    $user = UserModel::where(['id' => $userId])->with('code')->find();
    $domain = Config::get('app.domain');
    if ($user['code']) {
      $code = $user['code']['code'];
      $this->success('获取成功', $domain . '?ic=' . $code);
    } else {
      $code = $this->getCode();
      UserPromotionModel::create(['user_id'=>$userId,'code'=>$code]);
      $this->success('获取成功！', $domain . '?ic=' . $code);
    }
  }

  public function user_promotion_log(){
    $userId = $this->getUserId();
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $page ?: $limit = 10;
    $logs = RewardLogModel::where('user_id',$userId)->order('id','desc')->page($page, $limit)->select();
    $this->success('获取成功',$logs);
  }

  public function promotion_info(){
    $userId = $this->getUserId();
    $number = RewardLogModel::where('user_id',$userId)->count();
    $this->success('获取成功',[
      'promotion_count' => $number
    ]);
  }
}
