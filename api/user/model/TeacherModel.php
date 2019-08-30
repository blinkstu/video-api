<?php

namespace api\user\model;

use think\Model;

class TeacherModel extends Model
{
  public function institution()
  {
    return $this->hasOne('InstitutionModel','id','institution_id');
  }

  public function user()
  {
    return $this->hasOne('UserModel','id','user_id');
  }
}