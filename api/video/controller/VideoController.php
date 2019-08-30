<?php
namespace api\video\controller;

use cmf\controller\RestBaseController;
use api\video\model\CategoryModel;
use api\video\model\VideoModel;

class VideoController extends RestBaseController
{
  public function random(){
    $videos = VideoModel::where([])->orderRand()->limit(4)->select();

    $this->success('获取成功', $videos); 
  }

  public function detail(){
    $id = $this->request->id;

    $video = VideoModel::where(['id'=>$id])->find();

    $this->success('获取成功',$video);
  }
}