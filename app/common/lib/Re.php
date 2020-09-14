<?php
/**
 * Created by PhpStorm.
 * User: chiefgunner
 * Date: 2020/9/9
 * Time: 2:06 AM
 */

namespace app\common\lib;


class Re
{
    /**
     * @param array $data
     * @param string $message
     * @return \think\response\Json
     */
    public static function success($data = [],$message = 'OK')
    {
        $response = [
            'code' => 1,
            'msg' => $message,
            'data' => $data
        ];
        return json($response);
    }

    /**
     * @param string $message
     * @param $status
     * @param array $data
     * @return \think\response\Json
     */
    public static function error($message = 'error',$status = 0,$data = [])
    {
        $response = [
            'code' => $status,
            'msg' => $message,
            'data' => $data
        ];
        return json($response,$status);
    }
}