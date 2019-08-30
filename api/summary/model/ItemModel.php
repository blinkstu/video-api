<?php

namespace api\summary\model;

use think\Model;
use think\model\concern\SoftDelete;

class ItemModel extends Model{
  use SoftDelete;
  protected $autoWriteTimestamp = 'datetime';
  protected $deleteTime = 'delete_time';
}