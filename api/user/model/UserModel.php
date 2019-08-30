<?php
namespace api\user\model;

use think\Model;

class UserModel extends Model
{
  public function roles()
  {
    return $this->belongsToMany('RoleModel', 'role_user', 'role_id', 'user_id');
  }

  public function institution(){
    return $this->hasOne('InstitutionModel');
  }

  public function teacher(){
    return $this->hasOne('TeacherModel');
  }
}
