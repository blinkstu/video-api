<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use api\user\model\TeacherModel;
use api\summary\model\SummaryModel;

class TeacherController extends AdminBaseController
{
  public function index()
  {
    return $this->fetch();
  }

  public function summary()
  {
    $id = $this->request->id;
    $id?:$this->json_error('缺少参数');

    $this->assign('id',$id);
    return $this->fetch();
  }

  public function fetchTeachers()
  {
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $limit ?: $limit = 10;

    $count = TeacherModel::count();
    $teachers = TeacherModel::where([])->with('institution,user')->order('id','desc')->page($page,$limit)->select();

    return $this->json_success('获取成功！', $teachers, [], $count);
  }

  public function fetchSummary(){
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $limit ?: $limit = 10;
    $user_id = $this->request->id;
    if(!$user_id){
      $this->json_error('缺少参数！');
    }
    $count = TeacherModel::count();
    $summary = SummaryModel::where('user_id',$user_id)->order('id','desc')->page($page,$limit)->select();

    $this->json_success('获取成功！', $summary, [], $count);
  }
}
