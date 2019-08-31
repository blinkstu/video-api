<?php

namespace api\video\controller;

use cmf\controller\RestUserBaseController;
use api\video\model\OrderModel;
use api\video\model\ItemModel;
use api\video\model\UserVipModel;
use think\Db;
use think\Config;

class OrderController extends RestUserBaseController
{
  public function order_list()
  {
    $userId = $this->getUserId();
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $page ?: $limit = 10;

    $orders = OrderModel::where('user_id', $userId)->order('id desc')->with('item1')->order('id desc')->page($page, $limit)->select();

    $this->success('获取成功', $orders);
  }

  public function create_order()
  {

    $itemId = $this->request->item_id;
    $itemId ?: $this->error('缺少参数！');
    $userId = $this->getUserId();

    //获取商品信息
    $item = ItemModel::get($itemId);
    $item ?: $this->error('购买商品不存在！');

    //订单号
    $stamp = date('YmdHis');
    $out_trade_no = $stamp . $userId;

    $amount = (int) $item['price'];

    $infinite = $item['days'] == 9 ? 1 : 0;

    //防止多次提交！
    $currentTime = time();
    $order = OrderModel::where(['user_id' => $userId, 'item_id' => $itemId, 'status' => 0])->order('id', 'desc')->find();
    if ($order) {
      $lastTime = $order['create_time'];
      if (($lastTime + 60 * 3) > $currentTime) {
        $this->success('操作成功！', $order->toArray());
      }
    }

    //创建本地订单
    $order = new OrderModel();
    $order->out_trade_no = $out_trade_no;
    $order->user_id = $userId;
    $order->item_id = $itemId;
    $order->status = 0;
    $order->amount = $amount;
    $order->days = $item['days'];
    $order->infinite = $infinite;
    $result = $order->save();
    $result ?: $this->error('创建订单失败');

    $this->success('操作成功！', $order->toArray());
  }

  public function order_pay()
  {
    $out_trade_no = $this->request->id;
    $out_trade_no ?: $this->error('缺少参数');
    $order = OrderModel::where('out_trade_no', $out_trade_no)->with('item1')->find();

    if ($order['status'] != 0) {
      $this->error('订单已支付！');
    }

    $appId = config('app.appId');
    $appKey = config('app.appKey');
    $channelId = config('app.channelId');
    $callBackUrl = config('app.callBackUrl');
    $money = $order['amount'];

    $data = [
      'appid' => $appId,
      'type' => 2,
      'money' => $money,
      'callback' => $callBackUrl
    ];
    ksort($data);
    $sign = md5(http_build_query($data) . $appKey);

    $url = "http://pay.epayok.xyz/?appid=" . $appId . "&type=2&money=" . $money . "&callback=" . $callBackUrl . "&sign=" . $sign;

    $this->success('操作成功', ['url' => $url, 'order' => $order]);
  }

  public function order_query()
  {
    $out_trade_no = $this->request->out_trade_no;
    $out_trade_no ?: $this->error('缺少参数');
    $order = OrderModel::where('out_trade_no', $out_trade_no)->find();

    if ($order['status'] == 1) {
      $this->success('支付成功！', $order);
    } else {
      $this->success('没有支付', '', [], 2);
    }
  }


}
