<?php

namespace api\summary\controller;

use cmf\controller\RestUserBaseController;
use api\user\model\TeacherModel;
use api\user\model\InstitutionModel;

class ExpireController extends RestUserBaseController
{
  public function expire_time()
  {
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
    $expire_time = date('Y-m-d',strtotime($institution['expire_time']));
    $expire_time = strtotime($expire_time);
    $this->success('获取成功',$expire_time);
  }
}
