<?php

namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use think\Db;

use api\user\model\UserModel;
use api\user\model\InstitutionModel;
use api\user\model\TeacherModel;

class UserController extends RestUserBaseController
{

  public function userInfo()
  {
    $userId   = $this->getUserId();
    $UserModel = new UserModel();
    $user = $UserModel->where(['id' => $userId])->with('roles,institution,teacher.institution')->find();
    $data = [
      'user' => $user
    ];
    $this->success('获取成功！', $data);
  }

  public function re_bind()
  {
    $code = $this->request->code;
    if (!$code || $code == '') {
      $this->error('缺少参数');
    }
    $userId = $this->getUserId();

    $teacher = TeacherModel::where('user_id', $userId)->find();
    if (!$teacher) {
      $this->error('此账号不是老师账号！');
    }

    $institution = Db::name('institution')->where('code', $code)->find();
    if (!$institution) {
      $this->error('机构未找到，请检查您的机构代码');
    }

    $result = TeacherModel::where('user_id', $userId)->update(['institution_id' => $institution['id']]);

    if ($result) {
      $this->success('操作成功！');
    } else {
      $this->error('您已经绑定此机构！');
    }
  }

  public function getInstitution()
  {
    $institution_id = $this->param->id;
  }

  function make_code()
  {
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0, 25)]
      . strtoupper(dechex(date('m')))
      . date('d') . substr(time(), -5)
      . substr(microtime(), 2, 5)
      . sprintf('%02d', rand(0, 99));
    for (
      $a = md5($rand, true),
      $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
      $d = '',
      $f = 0;
      $f < 5;
      $g = ord($a[$f]),
      $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
      $f++
    );
    return $d;
  }

  public function registerInstitution()
  {
    $userId = $this->getUserId();

    // TODO 同时检测是否已经是老师号
    $InstitutionModel = new InstitutionModel();
    $check = $InstitutionModel->where(['user_id' => $userId])->find();
    if ($check) {
      $this->error('此账号已经是机构号！');
    }

    $validate = new \think\Validate([
      'name'            => 'require',
      'loc'             => 'require',
      'location'        => 'require',
      'president_name'  => 'require',
      'phone_number'    => 'require',
      'staff_num'       => 'require',
      'description'     => 'require'
    ]);
    $validate->message([
      'name.require'            => '请填写学校名称',
      'loc.require'             => '请填写学校地址',
      'location.require'        => '请填写学校地址',
      'president_name.require'  => '请填写校长姓名',
      'phone_number.require'    => '请填写学校电话号码',
      'staff_num.require'       => '请填写老师数量',
      'description.require'     => '请填写学校简介'
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    //检查角色
    $institutionRoleId = config('wx.institutuionRoleId');
    $role = Db::name('role_user')->where('user_id', $userId)->find();
    if ($role) {
      if ($role['role_id'] == $institutionRoleId) {
        $this->error('此账号已经是机构账号！');
      }
    }

    //生成机构代码
    $code = $this->make_code();
    $result = Db::name('institution')->where('code', $code)->find();
    while ($result != null) {
      $code = $this->make_code();
      $result = Db::name('institution')->where('code', $code)->find();
    }

    //设定过期时间！
    $addDays = config('wx.free_days');
    $date = date("Y-m-d");
    $new_date = strtotime($date . "+ ".$addDays." days");
    $new_date = date("Y-m-d H:i:s",$new_date);

    //开始写入数据库
    Db::name('role_user')->insert(['user_id' => $userId, 'role_id' => $institutionRoleId]);
    $InstitutionModel = new InstitutionModel();
    $InstitutionModel->user_id        = $userId;
    $InstitutionModel->name           = $data['name'];
    $InstitutionModel->location       = $data['loc'] . $data['location'];
    $InstitutionModel->president_name = $data['president_name'];
    $InstitutionModel->phone_number   = $data['phone_number'];
    $InstitutionModel->staff_num      = $data['staff_num'];
    $InstitutionModel->description    = $data['description'];
    $InstitutionModel->code           = $code;
    $InstitutionModel->expire_time    = $new_date;
    $InstitutionModel->save();
    $id = $InstitutionModel->id;

    $this->success('注册成功！');
  }

  public function registerTeacher()
  {
    $userId = $this->getUserId();

    // TODO 同时检测是否已经是老师号
    $result = Db::name('teacher')->where('user_id', $userId)->find();
    if ($result) {
      $this->error('此账号已经是老师账号！');
    }

    $validate = new \think\Validate([
      'name'         => 'require',
      'position'     => 'require',
      'subject'      => 'require',
      'code'         => 'require',
    ]);
    $validate->message([
      'name.require'      => '请填写姓名',
      'position.require'   => '请填写职位',
      'subject.require'   => '请填写科目',
      'code.require'      => '请填写机构代码',
    ]);

    $data = $this->request->param();
    if (!$validate->check($data)) {
      $this->error($validate->getError());
    }

    //检查角色
    $teacherRoleId = config('wx.teacherRoleId');
    $role = Db::name('role_user')->where('user_id', $userId)->find();
    if ($role) {
      if ($role['role_id'] == $teacherRoleId) {
        $this->error('此账号已经是老师账号！');
      }
    }

    $code = $data['code'];
    $institution = Db::name('institution')->where('code', $code)->find();
    if (!$institution) {
      $this->error('机构未找到，请检查您的机构代码');
    }

    //所有检查完成开始写入数据库
    Db::name('role_user')->insert(['user_id' => $userId, 'role_id' => $teacherRoleId]);
    $teacherModel = new TeacherModel();
    $teacherModel['name'] = $data['name'];
    $teacherModel['user_id'] = $userId;
    $teacherModel['position'] = $data['position'];
    $teacherModel['subject'] = $data['subject'];
    $teacherModel['institution_id'] = $institution['id'];
    $teacherModel->save();

    $this->success('注册成功！');
  }

  public function getInsDetail()
  {
    $userId = $this->getUserId();
    $result = Db::name('institution')->where('user_id', $userId)->find();
    if ($result) {
      $this->success('获取成功！', $result);
    } else {
      $this->error('当前用户不是机构用户！');
    }
  }
}
