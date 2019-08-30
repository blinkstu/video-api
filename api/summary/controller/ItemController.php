<?php

namespace api\summary\controller;

use cmf\controller\RestUserBaseController;
use api\summary\model\ItemModel;
use api\user\model\InstitutionModel;
use api\summary\model\TeacherModel;

class ItemController extends RestUserBaseController
{
  public function item_list()
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
    $institution ?: $this->error('您未绑定机构，或机构不存在');

    $institutionName = $institution['name'];
    $text = '您现在将为机构 ['.$institutionName.'] 充值续期，请确认过后再充值！ ';

    $items = ItemModel::where([])->select();
    $this->success('获取成功', [
      'items' => $items,
      'text' => $text
    ]);
  }
}
