<?php
/**
 * Created by PhpStorm.
 * User: chiefgunner
 * Date: 2020/9/11
 * Time: 2:50 AM
 */

namespace app\validate;


use think\Validate;

class Item extends Validate
{
    protected $rule = [
        'projectId|项目ID'=>'require|number',
        'goodsId|货物Id'=>'require|number',
        'num|数量'=>"require|number"
    ];
}