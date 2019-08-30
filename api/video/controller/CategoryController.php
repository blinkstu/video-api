<?php

namespace api\video\controller;

use api\video\model\CategoryModel;
use cmf\controller\RestBaseController;

class CategoryController extends RestBaseController
{
  public function list()
  { 
    $categories = CategoryModel::where([])->select();
    $categories = $categories->toArray();

    $tree = $this->buildTree($categories);

    $this->success('获取成功！', $tree);
  }

  private function buildTree(array &$elements, $parentId = 0)
  {

    $branch = array();

    foreach ($elements as &$element) {

      if ($element['parent_id'] == $parentId) {
        $children = $this->buildTree($elements, $element['id']);
        if ($children) {
          $element['children'] = $children;
        }
        $branch[] = $element;
        unset($element);
      }
    }
    return $branch;
  }
}
