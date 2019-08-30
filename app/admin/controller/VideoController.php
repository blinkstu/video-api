<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use api\video\model\CategoryModel;
use api\video\model\VideoModel;
use tree\Tree;

class VideoController extends AdminBaseController
{
  public function index()
  {
    return $this->fetch();
  }

  public function indexGet()
  {
    $page = $this->request->page;
    $limit = $this->request->limit;

    $count = VideoModel::count();
    $result = VideoModel::where([])->page($page, $limit)->with('category')->select();
    $this->json_success('获取成功！', $result, [], $count);
  }

  public function add()
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

  public function addPost()
  {
    $data = $this->request->post();

    $is_free = isset($data['is_free']) ? 1 : 0;
    $is_local = isset($data['is_local']) ? 1 : 0;

    $videoModel = new VideoModel();
    $videoModel->title = $data['title'];
    $videoModel->category_id = $data['category'];
    $videoModel->description = $data['description'];
    $videoModel->is_free = $is_free;
    $videoModel->is_local = $is_local;
    $videoModel->file_name = $data['file_name'];
    $videoModel->thumbnail = $data['thumbnail'];
    if ($is_local == 1) {
      $videoModel->video_url = $data['local_video_url'];
    } else {
      $videoModel->video_url = $data['video_url'];
    }
    $videoModel->views = 0;
    $result = $videoModel->save();

    $this->success('成功！');
  }

  public function edit()
  {
    $id = $this->request->id;

    $video = VideoModel::where(['id' => $id])->find();
    $this->assign('video', $video);

    $parentId = $video['category_id'];
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


    return $this->fetch();
  }

  public function editPost()
  {
    $data = $this->request->post();
    $id = $data['id'];

    $is_free = isset($data['is_free']) ? 1 : 0;
    $is_local = isset($data['is_local']) ? 1 : 0;


    $videoModel = VideoModel::get($id);
    $videoModel->title = $data['title'];
    $videoModel->category_id = $data['category'];
    $videoModel->description = $data['description'];
    $videoModel->is_free = $is_free;
    $videoModel->is_local = $is_local;
    $videoModel->file_name = $data['file_name'];
    $videoModel->thumbnail = $data['thumbnail'];
    if ($is_local == 1) {
      $videoModel->video_url = $data['local_video_url'];
    } else {
      $videoModel->video_url = $data['video_url'];
    }

    $result = $videoModel->save();
    $this->success('成功！');
  }

  public function deletePost(){
    $id = $this->request->id;

    $result = VideoModel::where(['id'=> $id])->delete();

    if($result){
      $this->success('删除成功！');
    }else{
      $this->error('未知错误');
    }
  }
}
