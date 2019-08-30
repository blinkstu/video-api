<?php

namespace api\user\controller;

use cmf\controller\RestBaseController;
use think\Db;
use jssdk\Jssdk;
use think\facade\Validate;

class PublicController extends RestBaseController
{
  /**
   *  用户注册
   * @throws \think\Exception
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   * @throws \think\exception\PDOException
   */
  public function register()
  {
    $validate = new \think\Validate([
      'username'          => 'require',
      'password'          => 'require'
    ]);

    $validate->message([
      'username.require'          => '请输入手机号,邮箱!',
      'password.require'          => '请输入您的密码!'
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    $user = [];

    $findUserWhere = [];

    $user['user_login'] = $data['username'];
    $findUserWhere['user_login'] = $data['username'];

    $findUserCount = Db::name("user")->where($findUserWhere)->count();

    if ($findUserCount > 0) {
      $this->error("此账号已存在!");
    }

    $user['create_time'] = time();
    $user['user_status'] = 1;
    $user['user_type']   = 2;
    $user['user_pass']   = cmf_password($data['password']);

    $result = Db::name("user")->insert($user);


    if (empty($result)) {
      $this->error("注册失败,请重试!");
    }

    $this->success("注册并激活成功,请登录!");
  }

  /**
   * 用户登录
   * @throws \think\Exception
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   * @throws \think\exception\PDOException
   */
  // TODO 增加最后登录信息记录,如 ip
  public function login()
  {
    $validate = new \think\Validate([
      'username' => 'require',
      'password' => 'require'
    ]);
    $validate->message([
      'username.require' => '请输入手机号,邮箱或用户名!',
      'password.require' => '请输入您的密码!'
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    $findUserWhere = [];

    if (Validate::is($data['username'], 'email')) {
      $findUserWhere['user_email'] = $data['username'];
    } else if (cmf_check_mobile($data['username'])) {
      $findUserWhere['mobile'] = $data['username'];
    } else {
      $findUserWhere['user_login'] = $data['username'];
    }

    $findUser = Db::name("user")->where($findUserWhere)->find();

    if (empty($findUser)) {
      $this->error("用户不存在!");
    } else {

      switch ($findUser['user_status']) {
        case 0:
          $this->error('您已被拉黑!');
        case 2:
          $this->error('账户还没有验证成功!');
      }

      if (!cmf_compare_password($data['password'], $findUser['user_pass'])) {
        $this->error("密码不正确!");
      }
    }

    $allowedDeviceTypes = $this->allowedDeviceTypes;

    if (empty($this->deviceType) && (empty($data['device_type']) || !in_array($data['device_type'], $this->allowedDeviceTypes))) {
      $this->error("请求错误,未知设备!");
    } else if (!empty($data['device_type'])) {
      $this->deviceType = $data['device_type'];
    }

    //        Db::name("user_token")
    //            ->where('user_id', $findUser['id'])
    //            ->where('device_type', $data['device_type']);
    $findUserToken  = Db::name("user_token")
      ->where('user_id', $findUser['id'])
      ->where('device_type', $this->deviceType)
      ->find();
    $currentTime    = time();
    $expireTime     = $currentTime + 24 * 3600 * 180;
    $token          = md5(uniqid()) . md5(uniqid());
    if (empty($findUserToken)) {
      $result = Db::name("user_token")->insert([
        'token'       => $token,
        'user_id'     => $findUser['id'],
        'expire_time' => $expireTime,
        'create_time' => $currentTime,
        'device_type' => $this->deviceType
      ]);
    } else {
      $result = Db::name("user_token")
        ->where('user_id', $findUser['id'])
        ->where('device_type', $this->deviceType)
        ->update([
          'token'       => $token,
          'expire_time' => $expireTime,
          'create_time' => $currentTime
        ]);
    }


    if (empty($result)) {
      $this->error("登录失败!");
    }

    $this->success("登录成功!", ['token' => $token, 'user' => $findUser]);
  }

  //简便方法获取微信jssdk签名
  public function getSingnature()
  {
    $url = $_GET['url'];
    $appId = config('wx.appId');
    $appSecret = config('wx.appSecret');
    $jssdk = new JSSDK($appId, $appSecret);
    $wxconfig = $jssdk->getSignPackage($url);
    header('Access-Control-Allow-Origin: *');
    $this->success('Success', $wxconfig);
  }

  //微信用户通过code登陆
  public function wechatLogin()
  {
    $validate = new \think\Validate([
      'code'           => 'require'
    ]);

    $validate->message([
      'code.require'           => '缺少参数code!'
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    $code = $data['code'];
    $appId = config('wx.appId');
    $appSecret = config('wx.appSecret');

    $response = cmf_curl_get("https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appId&secret=$appSecret&code=$code&grant_type=authorization_code");

    $response = json_decode($response, true);
    if (!empty($response['errcode'])) {
      $this->error('微信登陆失效!', $response);
    }

    $access_token = $response['access_token'];
    $refresh_token = $response['refresh_token'];
    $openid = $response['openid'];

    $userInfo = cmf_curl_get("https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN");
    $userInfo = json_decode($userInfo, true);

    $findThirdPartyUser = Db::name("third_party_user")
      ->where('openid', $openid)
      ->where('app_id', $appId)
      ->find();

    $currentTime = time();
    $ip          = $this->request->ip(0, true);

    if ($findThirdPartyUser) {
      $userId = $findThirdPartyUser['user_id'];
      $token  = cmf_generate_user_token($findThirdPartyUser['user_id'], 'wxapp');

      $userData = [
        'last_login_ip'   => $ip,
        'last_login_time' => $currentTime,
        'login_times'     => Db::raw('login_times+1'),
        'more'            => json_encode($userInfo)
      ];

      Db::name("third_party_user")
        ->where('openid', $openid)
        ->where('app_id', $appId)
        ->update($userData);
    } else {

      //TODO 使用事务做用户注册
      $userId = Db::name("user")->insertGetId([
        'create_time'     => $currentTime,
        'user_status'     => 1,
        'user_type'       => 2,
        'sex'             => $userInfo['sex'],
        'user_nickname'   => $userInfo['nickname'],
        'avatar'          => $userInfo['headimgurl'],
        'last_login_ip'   => $ip,
        'last_login_time' => $currentTime,
      ]);

      Db::name("third_party_user")->insert([
        'openid'          => $openid,
        'user_id'         => $userId,
        'third_party'     => 'wxapp',
        'app_id'          => $appId,
        'last_login_ip'   => $ip,
        'union_id'        => isset($userInfo['unionId']) ? $userInfo['unionId'] : '',
        'last_login_time' => $currentTime,
        'create_time'     => $currentTime,
        'login_times'     => 1,
        'status'          => 1,
        'more'            => json_encode($userInfo)
      ]);

      $token = cmf_generate_user_token($userId, 'wxapp');
    }

    $user = Db::name('user')->where('id', $userId)->find();

    $this->success("登录成功!", ['token' => $token, 'user' => $user]);
  }
}
