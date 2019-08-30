<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use api\summary\model\OrderModel;

class OrderController extends AdminBaseController
{
  public function index()
  {
    return $this->fetch('orders');
  }

  public function fetchData()
  {
    $page = $this->request->page;
    $limit = $this->request->limit;

    $count = OrderModel::count();
    $result = OrderModel::where([])->page($page, $limit)->with('item1,user')->select();
    $this->json_success('获取成功！', $result, [], $count);
  }

  public function delete()
  {
    $id = $this->request->id;
    if (!$id) {
      $this->json_error('缺少参数');
    }
    $result = OrderModel::destroy($id);
    if ($result) {
      $this->json_success('操作成功！');
    } else {
      $this->json_error('操作失败');
    }
  }
}
