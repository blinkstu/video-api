<?php

namespace api\summary\controller;

use cmf\controller\RestUserBaseController;
use api\user\model\InstitutionModel;

class InstitutionController extends RestUserBaseController
{
  public function settings()
  {
    $userId = $this->getUserId();
    $institution = InstitutionModel::where('user_id', $userId)->find();
    $institution ?: $this->error('机构未找到！');

    $extra = $institution['extra'];
    $extra = json_decode($extra, true);
    $extra ?: $extra = [
      'show_enabled'  => false,
      'contact_name' => '',
      'contact_phone' => '',
      'contact_wx_url' => '',
      'logo_url' => '',
      'institution_pics' => []
    ];

    $this->success('获取成功', [
      'institution' => $institution,
      'extra' => $extra
    ]);
  }

  public function save_settings()
  {
    $userId = $this->getUserId();
    $institution = InstitutionModel::where('user_id', $userId)->find();
    $institution ?: $this->error('机构未找到！');
    $institutionId = $institution['id'];

    //验证
    $validate = new \think\Validate([
      'show_enabled'          => 'require',
      'contact_name'          => 'require',
      'contact_phone'         => 'require',
      'contact_wx_url'        => 'require',
      'logo_url'              => 'require',
      'institution_pics'      => 'require',
      'institution_name'      => 'require',
      'institution_loc'       => 'require',
      'institution_location'  => 'require',
      'description'           => 'require'
    ]);
    $validate->message([
      'contact_name.require'      => '请填写联系人',
      'contact_phone.require'     => '请填写联系电话',
      'contact_wx_url.require'    => '请上传联系人微信二维码',
      'logo_url.require'          => '请上传logo',
      'institution_pics.require'  => '请上传图片',
      'institution_name.require'  => '请输入机构名称',
      'institution_loc'           => '请输入机构所在地',
      'institution_location'      => '请输入机构详细地址',
      'description'               => '请输入机构简介'
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    //保存图片到本地
    $contact_wx_url = $this->saveBase64Image($data['contact_wx_url']);
    $contact_wx_url = $contact_wx_url['url'];
    strrpos($data['contact_wx_url'], 'base64') != false ?: $contact_wx_url = $data['contact_wx_url'];

    $logo_url = $this->saveBase64Image($data['logo_url']);
    $logo_url = $logo_url['url'];
    strrpos($data['logo_url'], 'base64') != false ?: $logo_url = $data['logo_url'];

    $imgList = [];
    foreach ($data['institution_pics'] as $key => $item) {
      if(strrpos($item['content'], 'base64')){
        $img = $this->saveBase64Image($item['content']);
        $imgList[] = $img['url'];
      }else{
        $imgList[] = $item['content'];
      }
    }

    $extra = [
      'show_enabled'  => $data['show_enabled'],
      'contact_name' => $data['contact_name'],
      'contact_phone' => $data['contact_phone'],
      'contact_wx_url' => $contact_wx_url,
      'logo_url' => $logo_url,
      'institution_pics' => $imgList
    ];


    $institution = InstitutionModel::get($institutionId);
    $institution->extra = json_encode($extra);
    $institution->description = $data['description'];
    $institution->location = $data['institution_loc'] . $data['institution_location'];
    $institution->name = $data['institution_name'];
    $institution->save();

    $this->success('保存成功！');
  }

  private function saveBase64Image($base64_image_content)
  {
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {

      //图片后缀
      $type = $result[2];

      //保存位置--图片名
      $image_name = date('His') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT) . "." . $type;
      $image_url = 'upload/images/' . date('Ymd') . '/' . $image_name;
      $dirname = dirname(CMF_ROOT . 'public/' . $image_url);
      if (!is_dir(dirname(CMF_ROOT . 'public/' . $image_url))) {
        mkdir(dirname(CMF_ROOT . 'public/' . $image_url));
        chmod(dirname(CMF_ROOT . 'public/' . $image_url), 0777);
        //umask($oldumask);
      }
      $domain = $this->request->domain() ;

      //解码
      $decode = base64_decode(str_replace($result[1], '', $base64_image_content));
      if (file_put_contents(CMF_ROOT . 'public/' . $image_url, $decode)) {
        $data['code'] = 0;
        $data['imageName'] = $image_name;
        $data['url'] = $domain . '/' . $image_url;
      } else {
        $data['code'] = 1;
        $data['imgageName'] = '';
        $data['url'] = '';
      }
    } else {
      $data['code'] = 1;
      $data['imgageName'] = '';
      $data['url'] = '';
    }
    return $data;
  }
}
