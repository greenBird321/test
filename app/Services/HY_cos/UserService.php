<?php
/**
 * 百万亚瑟王  用户相关
 * User: lihe
 * Date: 2017/5/10
 * Time: 上午10:56
 */
namespace Xt\Rpc\Services\HY_cos;

use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;

class UserService extends Service
{
    public function __construct($di)
    {
        parent::__construct($di);
        $this->utilsModel = new Utils();
    }

    /**
     * 用户信息
     * @param $parameter
     * @return 返回用户详细信息
     */
    public function profile($parameter)
    {
        if (empty($parameter['zone'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];

        //查询
        if (!empty($parameter['user_id'])){
            $sql = "SELECT * FROM player_base b, player_extern_info e WHERE b.p_id=e.p_id AND b.p_id=?";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql , [$parameter['user_id']]);
        }elseif (!empty($parameter['name'])){
            $sql = "SELECT * FROM player_base b, player_extern_info e WHERE  b.p_id=e.p_id AND  b.p_name LIKE '{$parameter['name']}' LIMIT 20";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql);
        }elseif (!empty($parameter['account_id'])){
            $sql = "SELECT * FROM player_base b, player_extern_info e WHERE b.p_id=e.p_id AND b.p_account=?";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['account_id']]);
        }

        if (!$userInfo){
            return [
                'code' => 1,
                'msg'  => 'no data'
            ];
        }
        
        $result['account_id'] = $userInfo['p_account'];
        $result['user_id']  = $userInfo['p_id'];
        $result['name'] = $userInfo['p_name'];
        $result['coin'] = $userInfo['curRMB'];
        $result['level'] = $userInfo['p_level'];
        $result['exp']  = $userInfo['curExp'];
        $result['vip'] = '暂无';
        $result['create_time'] = $this->utilsModel->switchTimeZone(
                'UTC', $this->di['db_cfg']['setting']['timezone'], $userInfo['time_create'], 'Y-m-d H:i:s O');
        $result['attribute'] = $userInfo;
        
        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => $result
        ];

    }
}