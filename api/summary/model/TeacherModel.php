<?php

namespace api\summary\model;

use think\Model;

class TeacherModel extends Model
{

  public function user()
  {
    return $this->hasOne('UserModel','id','user_id');
  }

  public function summary(){
    return $this->hasMany('SummaryModel','user_id', 'user_id');
  }
}
