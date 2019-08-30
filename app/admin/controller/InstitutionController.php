<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use api\user\model\InstitutionModel;

class InstitutionController extends AdminBaseController
{
  public function index()
  {
    return $this->fetch();
  }

  public function fetchInstitutions(){
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $limit ?: $limit = 10;

    $count = InstitutionModel::count();
    $institutions = InstitutionModel::where([])->order('id','desc')->with('user')->page($page,$limit)->select();

    return $this->json_success('获取成功！', $institutions, [], $count);
  }

  public function edit(){
    $id = $this->request->id;
    $this->assign('id',$id);
    $institution = InstitutionModel::get($id);
    $this->assign('data',$institution);
    return $this->fetch();
  }

  public function save(){
    $expire_time = $this->request->expire_time;
    $id          = $this->request->id;

    InstitutionModel::where('id',$id)->update(['expire_time' => $expire_time]);
    return $this->success('保存成功！');
  }
}
