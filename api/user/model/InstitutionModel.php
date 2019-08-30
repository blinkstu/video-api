<?php

namespace api\user\model;

use think\Model;

class InstitutionModel extends Model
{
  public function user(){
    return $this->hasOne('UserModel','id','user_id');
  }
}