<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class SummaryController extends AdminBaseController
{
  public function Comments()
  {
    $data = Db::name('summary_comment')->select();

    $this->assign('data', $data);
    return $this->fetch();
  }

  public function Post()
  {
    $content = $this->request->content;

    if (!$content) {
      $this->error('缺少参数');
    }

    $result = Db::name('summary_comment')->insert([
      'content' => $content
    ]);

    $this->success('添加成功！');
  }

  public function remove()
  {
    $id = $this->request->id;

    if (!$id) {
      $this->error('缺少参数！');
    }

    $result = Db::name('summary_comment')->where('id', $id)->delete();

    if ($result) {
      $this->success('删除成功！');
    } else {
      $this->error('删除失败！');
    }
  }

  public function templates()
  {
    $result = Db::name('summary_template')->select();

    $this->assign('data', $result);
    return $this->fetch();
  }

  public function add_template()
  {
    $name = $this->request->name;
    $file_name = $this->request->file_name;
    $photo_url = $this->request->photo_url;

    if (!$name || !$file_name || !$photo_url) {
      $this->error('缺少参数');
    }

    $result = Db::name('summary_template')->insert([
      'name' => $name,
      'file_name' => $file_name,
      'thumbnail' => $photo_url
    ]);

    if ($result) {
      $this->success('添加成功！');
    }
  }

  public function edit_template()
  {
    $id = $this->request->id;
    if (!$id) {
      $this->error('缺少参数');
    }
    $result = Db::name("summary_template")->where('id', $id)->find();

    $dir = CMF_ROOT . '/app/index/view/summary/';
    $file_name = $result['file_name'] . '.html';
    $path = $dir . $file_name;
    try {
      $content = file_get_contents($path);
      $this->assign('content', $content);
    } catch (\Throwable $th) {
      file_put_contents($path, '');
      $this->assign('content', '');
    }

    $this->assign('data', $result);
    return $this->fetch();
  }

  public function save_template()
  {
    $id = $this->request->id;
    $name = $this->request->name;
    $file_name = $this->request->file_name;
    $photo_url = $this->request->photo_url;
    $content = $this->request->content;

    if (!$id || !$name || !$file_name || !$photo_url || !$content) {
      $this->error('缺少参数');
    }

    $result = Db::name("summary_template")->where('id', $id)->find();
    $path = CMF_ROOT . '/app/index/view/summary/' . $result['file_name'] . '.html';
    file_put_contents($path, $content);

    $save = Db::name('summary_template')->where('id', $id)->update([
      'name' => $name,
      'file_name' => $file_name,
      'thumbnail' => $photo_url
    ]);

    $this->success('保存成功');
  }

  public function remove_template()
  {
    $id = $this->request->id;
    if (!$id) {
      $this->error('缺少参数');
    }

    $result = Db::name('summary_template')->where('id', $id)->delete();

    if ($result) {
      $this->success('删除成功！');
    }
  }
}
