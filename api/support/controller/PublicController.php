<?php

namespace api\support\controller;

use cmf\controller\RestBaseController;
use cmf\lib\Upload;
use jssdk\Jssdk;
use think\facade\Cache;

class PublicController extends RestBaseController
{
  public function upload()
  {
    header("Access-Control-Allow-Origin: *");
    $uploader = new Upload();

    $result = $uploader->upload();

    if ($result === false) {
      $this->error($uploader->getError());
    } else {
      $result['preview_url'] = cmf_get_image_preview_url($result["filepath"]);
      $result['url']         = cmf_get_image_url($result["filepath"]);
      $result['filename']    = $result["name"];
      $result['status'] = 1;
      echo json_encode($result);
      exit;
    }
  }

  public function wx_upload()
  {
    $media_id = $this->request->media_id;
    $appId = config('wx.appId');
    $appSecret = config('wx.appSecret');

    $jssdk = new JSSDK($appId, $appSecret);
    $access_token = $jssdk->getAccessToken();

    $result = file_get_contents('http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=' . $access_token . '&media_id=' . $media_id);
    $file = '/upload/wx_uploads/' . time() . rand(10, 999999) . '.jpg';
    file_put_contents(CMF_ROOT . '/public' . $file, $result);
    $this->success('操作成功！', $file);
  }

  public function upload_video()
  {
    header("Access-Control-Allow-Origin: *");
    $uploader = new Upload();

    $uploader->setFileType('video');
    $result = $uploader->upload();

    if ($result === false) {
      $this->error($uploader->getError());
    } else {
      $result['preview_url'] = cmf_get_image_preview_url($result["filepath"]);
      $result['url']         = cmf_get_image_url($result["filepath"]);
      $result['filename']    = $result["name"];
      $result['status'] = 1;
      echo json_encode($result);
      exit;
    }
  }

  public function upload_base64()
  {
    header("Access-Control-Allow-Origin: *");
    $base64 = $this->request->base64;
    $base64 ?: $this->error('缺少参数');
    $result = $this->saveBase64Image($base64);
    $this->success('保存成功', $result);
  }

  private function saveBase64Image($base64_image_content)
  {
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {

      //图片后缀
      $type = $result[2];

      //保存位置--图片名
      $image_name = date('His') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT) . "." . $type;
      $image_url = 'public/upload/images/' . date('Ymd') . '/' . $image_name;
      $dirname = dirname(CMF_ROOT . $image_url);
      if (!is_dir(dirname(CMF_ROOT . $image_url))) {
        mkdir(dirname(CMF_ROOT . $image_url));
        chmod(dirname(CMF_ROOT . $image_url), 0777);
        //umask($oldumask);
      }

      //解码
      $decode = base64_decode(str_replace($result[1], '', $base64_image_content));
      if (file_put_contents(CMF_ROOT . $image_url, $decode)) {
        $data['code'] = 0;
        $data['imageName'] = $image_name;
        $data['url'] = $image_url;
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
