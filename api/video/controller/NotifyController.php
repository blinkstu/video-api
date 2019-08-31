<?php

namespace api\video\controller;

use api\video\model\ItemModel;
use api\video\model\UserModel;
use api\video\model\OrderModel;
use api\video\model\UserVipModel;
use cmf\controller\RestBaseController;

class NotifyController extends RestBaseController
{
  public function notify()
  {
    $out_trade_no = $this->request->out_trade_no;
    $currentTime = time();

    $order = OrderModel::where('out_trade_no', $out_trade_no)->find();
    if (!$order) {
      $this->error('订单不存在');
    }
    if ($order['status'] != 0) {
      $this->error('订单已付款！');
    }

    $item = ItemModel::where('id', $order['item_id'])->find();
    if (!$item) {
      $this->error('商品不存在');
    }

    //获取需要充值的用户
    $user = UserModel::where('id', $order['user_id'])->with('vip')->find();
    if ($user['vip']) {
      $userExpireTime = $user['vip']['expire_time'];
      if ($userExpireTime < $currentTime) {
        $userExpireTime = null;
      }
    } else {
      $userExpireTime = null;
    }

    //为用户增加vip时间
    $days = $item['days'];
    if($days != 999){
      if ($userExpireTime) {
        $expireTime = $userExpireTime + $days * 24 * 60 * 60;
      } else {
        $expireTime = $currentTime + $days * 24 * 60 * 60;
      }
      $check = UserVipModel::where('user_id', $order['user_id'])->find();
      if ($check) {
        $result = UserVipModel::where('user_id', $order['user_id'])->update([
          'expire_time' => $expireTime,
          'vip_level_name' => $item['name']
        ]);
      } else {
        $result = UserVipModel::create(['user_id' => $order['user_id'], 'expire_time' => $expireTime, 'vip_level_name' => $item['name']]);
      }
    } else {
      //如果是无限卡会员
      $check = UserVipModel::where('user_id', $order['user_id'])->find();
      if ($check) {
        $result = UserVipModel::where('user_id', $order['user_id'])->update([
          'expire_time' => 0,
          'vip_level_name' => $item['name'],
          'infinite' => 1
        ]);
      } else {
        $result = UserVipModel::create(['user_id' => $order['user_id'], 'expire_time' => 0, 'vip_level_name' => $item['name'], 'infinite' => 1]);
      }
    }


    //更新订单状态
    $result = OrderModel::where('out_trade_no', $out_trade_no)->update(['status' => 1]);

    return 'SUCCESS';
    exit;
  }
}
