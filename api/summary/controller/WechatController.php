<?php

namespace api\summary\controller;

use cmf\controller\RestBaseController;
use wechatpay\WechatPay;
use payjssdk\Jssdk;
use think\Db;
use api\summary\model\OrderModel;
use api\user\model\InstitutionModel;
use app\admin\controller\TeacherController;
use api\user\model\TeacherModel;
use api\summary\model\ItemModel;

class WechatController extends RestBaseController
{
  protected $config;

  protected function initialize()
  {
    $this->config = array(
      'mch_id'            => config('wx.prod_mch_id'),
      'app_id'            => config('wx.prod_appID'),
      'app_secret'        => config('wx.prod_appSecret'),
      'api_key'           => 'ZVCWnpvNHJ7fz3Mx6hYUccEE7mkTzF7Q',
      'sign_type'         => 'MD5',
      'notify_url'        => config('wx.notify_url')
    );
  }

  public function index()
  {
    $wxpay = WechatPay::Jsapi($this->config);
    $stamp = date('YmdHis');
    $ext = ['attach' => ''];
    $desc = "desc$stamp";
    $amt = 101;
    $prepay_id = $wxpay->getPrepayId("校长文库-销售商品类目", "$stamp", $amt, 'oq9RH5wsjBlmGB5ZokXbO2156SBo');
    $package = $wxpay->getPackage($prepay_id);
    $package['id'] = $stamp;
    $this->success('操作成功！', $package);
  }

  public function wxOrder($orderId, $title, $amt, $openid)
  {
    $wxpay = WechatPay::Jsapi($this->config);
    $prepay_id = $wxpay->getPrepayId($title, $orderId, $amt, $openid);
    $package = $wxpay->getPackage($prepay_id);
    return $package;
  }

  public function order_query()
  {
    global $config;
    $transaction_id = $this->request->transaction_id;
    $wxpay = WechatPay::Jsapi($this->config);
    $package = $wxpay->queryOrderByOutTradeNo($transaction_id);
    $this->success('操作成功！', $package);
  }

  //用于微信支付的新的接口
  public function paySignature()
  {
    $url = $_GET['url'];
    $appId = config('wx.prod_appID');
    $appSecret = config('wx.prod_appSecret');

    $jssdk = new Jssdk($appId, $appSecret);
    $wxconfig = $jssdk->getSignPackage($url);
    header('Access-Control-Allow-Origin: *');
    $this->success('Success', $wxconfig);
  }

  public function notify()
  {
    $xml = file_get_contents("php://input");
    $payment = new WechatPay($this->config);
    $payment->onPaidNotify($xml, function ($notifydata) use ($payment) {

      $order = OrderModel::where('out_trade_no', $notifydata['out_trade_no'])->find();
      $order['status'] == 0 ?: exit;

      $userId = $order['user_id'];
      //确定用户是为哪个机构充值
      $institution = InstitutionModel::where('user_id', $userId)->find();
      if ($institution) {
        $institution_id = $institution['id'];
      } else {
        $teacher = TeacherModel::where('user_id', $userId)->find();
        $institution_id = $teacher['institution_id'];
      }

      //查询商品增加多少期限
      $current_time = date('Y-m-d');
      $item = ItemModel::where('id', $order['item_id'])->find();
      $addDays = $item['days'];
      $institution = InstitutionModel::get($institution_id);
      $expire_time = date('Y-m-d', strtotime($institution['expire_time']));


      //对比下是不是已经过期了
      $time_expire = strtotime($expire_time);
      $time_current = strtotime($current_time);
      if ($time_current > $time_expire) {
        $expire_time = date('Y-m-d', strtotime($current_time));
      }

      $new_expire_time = date('Y-m-d H:i:s', strtotime($expire_time . ' + ' . $addDays . ' days'));

      $institution = InstitutionModel::get($institution_id);
      $institution->expire_time = $new_expire_time;
      $institution->save();

      $order = OrderModel::where('out_trade_no', $notifydata['out_trade_no'])->update(['status' => 1]);
      $payment->responseNotify();
      exit;
    });
  }
}
