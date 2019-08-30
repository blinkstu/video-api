<?php

namespace api\summary\controller;

use cmf\controller\RestBaseController;
use api\summary\model\SummaryModel;
use api\user\model\UserModel;

class PublicController extends RestBaseController
{

  public function summary()
  {
    $id = $this->request->id;

    $result = SummaryModel::where('id', $id)->find();
    $user_id = $result['user_id'];
    $user = UserModel::where('id',$user_id)->find();
    $avatar = $user['avatar'];
    $result['avatar'] = $avatar;

    $this->success('获取成功！', $result);
  }

  public function get_settings()
  {
    $icon_url = config('wx.share_summary_icon');
    $pyq_text = config('wx.pyq_text');
    $wx_text = config('wx.wx_text');
    $home_share_text = config('wx.home_share_text');
    $app_name = config('wx.app_name');
    $wx_title = config('wx.wx_title');
    $website_domian = config('wx.website_domian');

    $this->success('获取成功', [
      'icon_url' => $icon_url,
      'pyq_text' => $pyq_text,
      'wx_text'  => $wx_text,
      'home_share_text' => $home_share_text,
      'app_name'  => $app_name,
      'wx_title' => $wx_title,
      'website_domian' => $website_domian
    ]);
  }


}
