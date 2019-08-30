<?php

namespace api\summary\controller;

use cmf\controller\RestUserBaseController;
use think\Db;
use api\summary\model\SummaryModel;
use api\summary\model\TeacherModel;
use api\summary\model\SummaryTemplateModel;

class SummaryController extends RestUserBaseController
{

  public function summary_template()
  {
    $result = Db::name('summary_comment')->select();

    return  $this->success('获取成功', $result);
  }

  public function publish_summary()
  {

    //验证
    $validate = new \think\Validate([
      'class'         => 'require',
      'count'         => 'require',
      //'object'        => 'require',
      'season'        => 'require',
      'subject'       => 'require',
      'theme'         => 'require',
      'rates'         => 'require',
      'content'      => 'require',
      'teacher_name'  => 'require',
      'school_name'   => 'require'
    ]);
    $validate->message([
      'class.require'         => '请填写班级',
      'count.require'         => '请填写课次',
      //'object.require'        => '请填写目标',
      'season.require'        => '请填写学期',
      'subject.require'       => '请填写科目',
      'theme.require'         => '请填写课程主题',
      'rates.require'         => '请评分',
      'content.require'      => '请填写详细内容',
      'teacher_name.require'  => '请填写老师姓名',
      'school_name.require'   => '请填写学校名称'
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    $userId = $this->getUserId();
    $teacher = Db::name('teacher')->where('user_id', $userId)->find();
    if (!$teacher) {
      $this->error('此账号不是老师账号！');
    }
    $institution_id = $teacher['institution_id'];


    //开始写入
    $summaryModel = new SummaryModel();
    $summaryModel->class            = $data['class'];
    $summaryModel->count            = $data['count'];
    //$summaryModel->object           = $data['object'];
    $summaryModel->season           = $data['season'];
    $summaryModel->subject          = $data['subject'];
    $summaryModel->theme            = $data['theme'];
    $summaryModel->content          = $data['content'];
    $summaryModel->rates            = $data['rates'];
    $summaryModel->teacher_name     = $data['teacher_name'];
    $summaryModel->school_name      = $data['school_name'];
    $summaryModel->comments         = $data['comments'];
    $summaryModel->user_id          = $userId;
    $summaryModel->institution_id   = $institution_id;
    empty($data['reviewerUserId']) ?: $summaryModel->reviewer_user_id = $data['reviewerUserId'];
    empty($data['reviewerUserName']) ?: $summaryModel->reviewer_user_name = $data['reviewerUserName'];
    $summaryModel->save();

    $this->success('发表成功！');
  }

  public function edit_summary()
  {
    $id = $this->request->id;

    //验证
    $validate = new \think\Validate([
      'id'            => 'require',
      'class'         => 'require',
      'count'         => 'require',
      //'object'        => 'require',
      'season'        => 'require',
      'subject'       => 'require',
      'theme'         => 'require',
      'rates'         => 'require',
      'content'       => 'require',
      'teacher_name'  => 'require'
    ]);
    $validate->message([
      'class.require'         => '请填写班级',
      'count.require'         => '请填写课次',
      //'object.require'        => '请填写目标',
      'season.require'        => '请填写学期',
      'subject.require'       => '请填写科目',
      'theme.require'         => '请填写课程主题',
      'rates.require'         => '请评分',
      'content.require'       => '请填写详细内容',
      'teacher_name.require'  => '请填写老师姓名'
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    $userId = $this->getUserId();
    $result = SummaryModel::where('id', $id)->find();
    if ($result['user_id'] !== $userId && $result['reviewer_user_id'] !== $userId) {
      $this->error('您没有权限修改此内容！');
    }

    $summaryModel = SummaryModel::get($id);
    $summaryModel->class            = $data['class'];
    $summaryModel->count            = $data['count'];
    //$summaryModel->object           = $data['object'];
    $summaryModel->season           = $data['season'];
    $summaryModel->subject          = $data['subject'];
    $summaryModel->theme            = $data['theme'];
    $summaryModel->content          = $data['content'];
    $summaryModel->rates            = $data['rates'];
    $summaryModel->comments         = $data['comments'];
    $summaryModel->teacher_name     = $data['teacher_name'];
    empty($data['reviewerUserId']) ?: $summaryModel->reviewer_user_id = $data['reviewerUserId'];
    empty($data['reviewerUserName']) ?: $summaryModel->reviewer_user_name = $data['reviewerUserName'];
    $summaryModel->save();

    $this->success('保存成功！');
  }

  public function summary_list()
  {
    $userId = $this->getUserId();
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $limit ?: $limit = 10;

    $result = SummaryModel::where('user_id', $userId)->whereOr('reviewer_user_id', $userId)->order('id', 'desc')->page($page, $limit)->select();

    $this->success('获取成功！', $result);
  }

  public function teachers()
  {
    $page = $this->request->page;
    $limit = $this->request->limit;
    $page ?: $page = 1;
    $limit ?: $limit = 10;
    $userId = $this->getUserId();
    $institution = Db::name('institution')->where('user_id', $userId)->find();
    $institution_id = $institution['id'];
    $teachers  = TeacherModel::where('institution_id', $institution_id)->with('user')->page($page, $limit)->select();

    $this->success('获取成功', $teachers);
  }

  public function teacher_summary()
  {
    $page = $this->request->page;
    $limit = $this->request->limit;
    $id = $this->request->id;
    if (!$id) {
      $this->error('缺少参数');
    }
    $page ?: $page = 1;
    $limit ?: $limit = 10;

    $userId = $this->getUserId();
    $institution = Db::name('institution')->where('user_id', $userId)->find();
    $teacher = Db::name('teacher')->where('id', $id)->find();

    $teacher ?: $this->error('未找到用户');
    $institution['id'] == $teacher['institution_id'] ?: $this->error('权限不足！');

    $summary = SummaryModel::where('user_id', $teacher['user_id'])->page($page, $limit)->select();

    $data = [
      'summary' => $summary,
      'teacher' => $teacher
    ];

    $this->success('获取成功', $data);
  }

  public function remove_summary()
  {
    $id = $this->request->id;
    $userId = $this->getUserId();
    if (!$id) {
      $this->error('缺少参数！');
    }
    $summary = SummaryModel::get($id);
    if (!$summary) {
      $this->error('未找到！');
    }
    if ($summary->user_id != $userId) {
      $this->error('权限不足！');
    }
    $result = $summary->delete();
    if ($result) {
      $this->success('删除成功！');
    } else {
      $this->error('操作失败！');
    }
  }

  public function list_templates()
  {
    $templates = SummaryTemplateModel::where([])->select();
    $host = $this->request->server();
    foreach ($templates as $key => $value) {
      $thumbnail = $templates[$key]['thumbnail'];
      $thumbnail = 'http://' . $host['SERVER_NAME'] . '/upload/' . $thumbnail;
      $templates[$key]['thumbnail'] = $thumbnail;
    }
    $this->success('获取成功！', $templates);
  }

  public function change_summary_template()
  {
    $template_id = $this->request->template_id;
    $summary_id = $this->request->summary_id;
    $user_id = $this->getUserId();

    if (!$summary_id || !$template_id) {
      $this->error('缺少参数');
    }

    $summary = SummaryModel::where('id', $summary_id)->find();
    if ($summary['user_id'] != $user_id) {
      $this->error('您的权限不足！');
    }

    $summary = SummaryModel::get($summary_id);
    $summary->template_id = $template_id;
    $result = $summary->save();

    if ($result) {
      $this->success('修改成功');
    } else {
      $this->error('修改失败');
    }
  }
}
