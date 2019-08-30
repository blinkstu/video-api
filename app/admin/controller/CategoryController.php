<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\View;
use api\video\model\CategoryModel;
use tree\Tree;

class CategoryController extends AdminBaseController
{
  function index()
  {
    $tree     = new Tree();
    $categories = CategoryModel::select();
    $tree->init($categories->toArray());
    $str = "<tr data-id='\$id'><td>\$spacer \$name</td><td><a href='/admin/category/edit?id=\$id' class='layui-btn layui-btn-xs'>编辑</a><div class='layui-btn layui-btn-xs layui-btn-danger delete'>删除</div></td></tr>";
    $categories = $tree->getTree(0, $str);
    $this->assign('categories', $categories);
    return $this->fetch();
  }

  function add()
  {
    $tree     = new Tree();
    $categories = CategoryModel::select();
    $array = $categories->toArray();
    $tree->init($array);
    $str = "<option value='\$id' >\$spacer \$name</option>";
    $selectCategory = $tree->getTree(0, $str);
    $this->assign('categories', $selectCategory);
    return $this->fetch();
  }

  function edit()
  {
    $id = $this->request->id;
    $category = CategoryModel::where(['id' => $id])->find();
    $parentId = $category['parent_id'];
    $tree     = new Tree();
    $categories = CategoryModel::select();
    foreach ($categories->toArray() as $r) {
      $r['selected'] = $r['id'] == $parentId ? 'selected' : '';
      $array[]       = $r;
    }
    $tree->init($array);
    $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
    $selectCategory = $tree->getTree(0, $str);
    $this->assign('categories', $selectCategory);
    $this->assign('category', $category);
    return $this->fetch();
  }

  function addPost()
  {
    $name = $this->request->name;
    $parent_id = $this->request->upperId;
    $thumbnail = $this->request->thumbnail;

    $cateogryModel = new CategoryModel();
    $cateogryModel->name = $name;
    $cateogryModel->thumbnail = $thumbnail;
    $cateogryModel->parent_id = $parent_id;
    $result = $cateogryModel->save();

    if ($result) {
      $this->success('保存成功');
    } else {
      $this->error('保存失败');
    }
  }

  function EditPost()
  {
    $id = $this->request->id;
    $name = $this->request->name;
    $upperId = $this->request->upperId;
    $thumbnail = $this->request->thumbnail;
    $parent_id = $this->request->upperId;

    $cateogryModel = CategoryModel::get($id);
    $cateogryModel->name = $name;
    $cateogryModel->upperId = $upperId;
    $cateogryModel->thumbnail = $thumbnail;
    $cateogryModel->parent_id = $parent_id;
    $result = $cateogryModel->save();

    if ($result) {
      $this->success('保存成功');
    } else {
      $this->error('保存失败');
    }
  }

  function DeletePost()
  {
    $id = $this->request->id;
    $result = CategoryModel::where(['id' => $id])->whereOr(['parent_id' => $id])->delete();
    $this->success('删除成功');
  }
}
