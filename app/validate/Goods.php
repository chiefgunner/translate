<?php
/**
 * Created by PhpStorm.
 * User: chiefgunner
 * Date: 2020/9/9
 * Time: 1:47 AM
 */

namespace app\validate;
use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'es|请填写英文名称'  =>  'require|max:25',
        'name|请填写名称'  =>  'require|max:25',
        'img|请长传图片' =>  'file|fileExt:jpg,jpeg,png',
    ];
}