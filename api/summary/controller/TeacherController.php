<?php

namespace api\summary\controller;

use cmf\controller\RestUserBaseController;
use api\user\model\InstitutionModel;
use app\admin\model\UserModel;
use api\summary\model\TeacherModel;

class TeacherController extends RestUserBaseController
{
  public function list_own_teachers(){
    $userId = $this->getUserId();
    $teacher = TeacherModel::where('user_id',$userId)->find();
    $teacher?:$this->error('未找到老师');

    //找到对应机构
    $institution = InstitutionModel::where('id',$teacher['institution_id'])->find();
    $institution?:$this->error('未找到对应机构！');

    $teachers = TeacherModel::where('institution_id',$institution['id'])->where('user_id','<>',$userId)->with('user')->select();
    
    $this->success('获取成功',$teachers);
  }
}