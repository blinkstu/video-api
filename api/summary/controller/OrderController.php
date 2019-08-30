<?php

namespace api\summary\controller;

use cmf\controller\RestUserBaseController;
use api\summary\model\OrderModel;
use api\user\model\InstitutionModel;
use api\summary\model\TeacherModel;
use api\summary\model\ItemModel;
use api\summary\controller\WechatController;
use think\Db;

class OrderController extends RestUserBaseController
{
  public function order_list()
  {
    $userId = $this->getUserId();
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $page ?: $limit = 10;

    $orders = OrderModel::where('user_id', $userId)->order('id desc')->with('item')->order('id desc')->page($page, $limit)->select();

    $this->success('获取成功', $orders);
  }

  public function create_order()
  {
    /**
     * 前端创建订单
     * 1. 微信统一下单
     * 2. 本地创建订单
     * 3. package保存到本地订单中
     */

    $itemId = $this->request->item_id;
    $itemId ?: $this->error('缺少参数！');
    $userId = $this->getUserId();

    //确定用户是为哪个机构充值
    $institution = InstitutionModel::where('user_id', $userId)->find();
    if ($institution) {
      $institution_id = $institution['id'];
    } else {
      $teacher = TeacherModel::where('user_id', $userId)->find();
      $institution_id = $teacher['institution_id'];
    }
    $institution = InstitutionModel::get($institution_id);
    $institution ?: $this->error('您未绑定机构，或机构不存在，订单创建失败！');


    //获取商品信息
    $item = ItemModel::get($itemId);
    $item ?: $this->error('购买商品不存在！');

    //订单号
    $stamp = date('YmdHis');
    $out_trade_no = $stamp . $userId;
    $amt = $item['price'] * 100;

    //微信统一下单
    $third = Db::name('third_party_user')->where('user_id',$userId)->find();
    $openid = $third['openid'];
    $wechat = new WechatController();
    $package = $wechat->wxOrder($out_trade_no, $item['name'], $amt, $openid);
    $packageStr = json_encode($package);

    //创建本地订单
    $order = new OrderModel();
    $order->out_trade_no = $out_trade_no;
    $order->user_id = $userId;
    $order->item_id = $itemId;
    $order->institution_id = $institution_id;
    $order->status = 0; //表示未支付
    $order->item_name = $item['name'];
    $order->item_price = $item['price']; 
    $order->item_days = $item['days'];
    $order->package = $packageStr;
    $result = $order->save();
    $result ?: $this->error('创建订单失败');

    $this->success('操作成功！', $order->toArray());
  }

  public function order_pay()
  {
    $out_trade_no = $this->request->id;
    $out_trade_no ?: $this->error('缺少参数');
    $order = OrderModel::where('out_trade_no', $out_trade_no)->find();

    if ($order['status'] != 0) {
      $this->error('订单已支付！');
    }

    $package = $order['package'];

    $this->success('操作成功', $package);
  }

  public function order_query()
  {
    $out_trade_no = $this->request->id;
    $out_trade_no ?: $this->error('缺少参数');
    $order = OrderModel::where('out_trade_no', $out_trade_no)->find();

    if ($order['status'] == 1) {
      $this->success('支付成功！', $order);
    } else {
      $this->error('支付失败！');
    }
  }
}
