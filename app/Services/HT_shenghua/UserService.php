<?php
/**
 * 生化联盟     用户相关.
 * User: lihe
 * Date: 2017/5/15
 * Time: 下午4:58
 */
namespace Xt\Rpc\Services\HT_shenghua;

use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;

class UserService extends Service
{
    private $_utilsModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->_utilsModel = new Utils();
    }

    /**
     * 用户信息
     * @param $parameter
     * @return mixed
     */
    public function profile($parameter)
    {
        if (empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];

        //查询
        ///user/profile?user_id=1001-&name=&account_id=100000 user_id 即服务器ID
        if (empty($parameter['user_id'])){
            $sql = "SELECT * FROM t_game_user WHERE user_id=?";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['user_id']]);
        }elseif (!empty($parameter['name'])){
            $sql = "SELECT * FROM t_game_user WHERE user_name LIKE  '%{$parameter['name']}%' LIMIT 20";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql);
        }elseif(!empty($parameter['account_id'])){
            $sql = "SELECT * FROM t_game_user WHERE account_id=? LIMIT 20";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['acoount']]);
        }

        
        if (!$userInfo) {
            return [
                'code' => 1,
                'msg'  => 'no data'
            ];
        }

        $result['account_id'] = $userInfo['account_id'];
        $result['user_id'] = $userInfo['user_id'];
        $result['name'] = $userInfo['user_name'];
        $result['coin'] = $userInfo['user_money'];
        $result['vip'] = '暂无';
        $result['level'] = $userInfo['user_lv'];
        $result['exp'] = $userInfo['user_exp'];
        $result['create_time'] = $this->_utilsModel->switchTimeZone(
            'UTC', $this->di['db_cfg']['setting']['timezone'], $userInfo['user_create_time'], 'Y-m-d H:i:s O');
        $result['attribute'] = $userInfo;

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => $result
        ];
    }

}