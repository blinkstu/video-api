<?php

namespace api\video\controller;

use cmf\controller\RestUserBaseController;
use api\video\model\ItemModel;

class ItemController extends RestUserBaseController
{
  public function item_list()
  {
    $userId = $this->getUserId();

    $items = ItemModel::where([])->select();
    $this->success('获取成功', [
      'items' => $items
    ]);
  }
}
