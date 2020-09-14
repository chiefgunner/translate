<?php
/**
 * Created by PhpStorm.
 * User: chiefgunner
 * Date: 2020/9/11
 * Time: 2:31 AM
 */

namespace app\model;


use think\Model;

class Item extends Model
{
    protected $autoWriteTimestamp = true;

    public function goods()
    {
        return $this->hasOne(Goods::class,'id','goods_id');
    }
}