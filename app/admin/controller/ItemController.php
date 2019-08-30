<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use api\video\model\ItemModel;

class ItemController extends AdminBaseController
{
  public function index()
  {
    return $this->fetch('items');
  }

  public function addItem()
  {
    $name = $this->request->name;
    $desc  = $this->request->desc;
    $days = $this->request->days;
    $price = $this->request->price;

    if (!$name || !$days || !$price || !$desc) {
      $this->json_error('缺少参数！');
    }

    $item = new ItemModel();
    $item->name = $name;
    $item->description = $desc;
    $item->days = $days;
    $item->price = $price;
    $result = $item->save();
    if ($result) {
      $this->json_success('操作成功！');
    } else {
      $this->json_error('操作失败！');
    }
  }

  public function fetchItems()
  {
    $page = $this->request->page;
    $limit = $this->request->limit;

    $count = ItemModel::count();
    $result = ItemModel::where([])->page($page, $limit)->select();
    $this->json_success('获取成功！', $result, [], $count);
  }

  public function save()
  {

    $rules = [
      'id'  => 'require',
      'name'     => 'require',
      'description' => 'require',
      'days' => 'require|number',
      'price' => 'require|float',
    ];
    $validate = new \think\Validate($rules);
    $validate->message([
      'id.require'     => '缺少参数',
      'name.require'     => '缺少参数',
      'description.require'     => '缺少参数',
      'days.require'     => '缺少参数',
      'price.require'    => '缺少参数',
      'days.number'     => '天数只能是数字',
      'price.float'    => '价格只能为浮点数',
    ]);

    $data = $this->request->param();
    $data['price'] = (float)  $data['price'];
    if (!$validate->check($data)) {
      $this->json_error($validate->getError());
    }

    $item = ItemModel::get($data['id']);
    if (!$item) {
      $this->json_error('商品不存在');
    }
    $item->name = $data['name'];
    $item->description = $data['description'];
    $item->days = $data['days'];
    $item->price = $data['price'];
    $result = $item->save();
    if ($result) {
      $this->json_success('保存成功！');
    } else {
      $this->json_error('操作失败！');
    }
  }

  public function delete()
  {
    $id = $this->request->id;
    if (!$id) {
      $this->json_error('缺少参数');
    }
    $result = ItemModel::where('id', $id)->delete();
    if ($result) {
      $this->json_success('操作成功！');
    } else {
      $this->json_error('操作失败');
    }
  }
}
