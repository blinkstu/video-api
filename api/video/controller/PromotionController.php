<?php

namespace api\video\controller;

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
      $this->success('获取成功', $domain . '?code=' . $code);
    } else {
      $code = $this->getCode();
      UserPromotionModel::create(['user_id'=>$userId,'code'=>$code]);
      $this->success('获取成功！', $domain . '?code=' . $code);
    }
  }
}
