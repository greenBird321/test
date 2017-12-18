<?php
/**
 * 末世   用户相关.
 * User: lihe
 * Date: 2017/5/23
 * Time: 上午11:03
 */
namespace Xt\Rpc\Services\HT_clash;


use Xt\Rpc\Core\Service;
use Xt\Rpc\models\Utils;

class UserService extends Service
{
    private $utilsModel;

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
        if (empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];

        //查询
        if (!empty($parameter['user_id'])) {
            $sql = "SELECT * FROM  t_game_user WHERE user_id=?";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['user_id']]);
        }
        elseif (!empty($parameter['name'])) {
            $sql = "SELECT * FROM t_game_user WHERE user_name LIKE '%{$parameter['name']}%' LIMIT 20";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql);
        }
        elseif (!empty($parameter['account_id'])) {     //account_id 与 user_id 查询一致
            $sql = 'SELECT * FROM t_game_user WHERE  user_id=?';
            $user_info = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['account_id']]);
        }

        if (!$user_info) {
            return [
                'code' => 1,
                'msg'  => 'no data'
            ];
        }

        //数据组装
        $result['account_id'] = $user_info['user_id'];
        $result['user_id'] = $user_info['user_id'];
        $result['name'] = $user_info['user_name'];
        $result['coin'] = $user_info['user_money'];
        $result['level'] = $user_info['user_lv'];
        $result['exp'] = $user_info['user_exp'];
        $result['vip'] = '暂无';
        $result['create_time'] = $this->utilsModel->switchTimeZone(
            'UTC', $this->di['db_cfg']['setting']['timezone'], $user_info['user_create_time'], 'Y-m-d H:i:s O'
        );
        
        $result['attribute'] = $user_info;

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => $result
        ];
    }
}