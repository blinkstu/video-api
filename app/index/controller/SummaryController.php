<?php

namespace app\index\controller;

use api\summary\model\SummaryModel;
use think\facade\View;
use cmf\controller\BaseController;
use api\summary\model\SummaryTemplateModel;
use api\user\model\InstitutionModel;
use api\user\model\TeacherModel;
use think\facade\Cache;

class SummaryController extends BaseController
{
  public function index()
  {
    $this->json_success('成功！');
  }

  public function summary()
  {
    $id = $this->request->id;

    $summary = SummaryModel::where('id', $id)->find();

    $summary ?: $this->error('404未找到');

    $this->assign('summary', $summary);
    $this->assign('rates', json_decode($summary['rates'], true));
    $this->assign('comments', json_decode($summary['comments'], true));

    $userId = $summary['user_id'];
    $teacher = TeacherModel::where('user_id', $userId)->find();
    $institution = InstitutionModel::where('id', $teacher['institution_id'])->find();

    $expire_time = date('Y-m-d', strtotime($institution['expire_time']));
    $current_time = date('Y-m-d');

    $time_expire = strtotime($expire_time);
    $time_current = strtotime($current_time);
    if ($time_current > $time_expire) {
      return View('/summary/expired');
    }

    //获取机构展示信息
    $institution['extra'] = json_decode($institution['extra'],true);
    $this->assign('institution',$institution);

    $template = SummaryTemplateModel::where('id', $summary['template_id'])->find();
    if (!$template) {
      $file_name = 'normal';
    } else {
      $file_name = $template['file_name'];
    }


    return View('/summary/' . $file_name);
  }

  public function add_flower()
  {
    $id = $this->request->id;

    $ip          = $this->request->ip(0, true);

    $summary = SummaryModel::get($id);
    if(!$summary){
      $this->error('未找到总结');
    }

    if(!Cache::get($ip . '-' . $id)){
      $summary->flowers = $summary['flowers']+1;
      $summary->save();
      Cache::set($ip . '-' . $id, 1, 3600);
      $this->success('送小花成功!');
    } else {
      $this->error('休息一会吧～');
    }

  }
}
