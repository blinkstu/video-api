<?php

namespace api\summary\model;

use think\Model;
use think\model\concern\SoftDelete;

class SummaryModel extends  Model
{
  protected $autoWriteTimestamp = 'datetime';
  //use SoftDelete;
  //protected $deleteTime = 'delete_time';
}
