<?php
/**
 * 银河           用户相关.
 * User: lihe
 * Date: 2017/5/22
 * Time: 上午11:55
 */
namespace Xt\Rpc\Services\HT_pocket;

use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;

class  UserService extends Service{

    private  $utilsModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->utilsModel = new Utils();
    }

    /*
     * 角色查询
     * */
    public function profile($parameter){
        if (empty($parameter['zone'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];

        if (!empty($parameter['user_id'])){
            $sql = "SELECT * FROM role WHERE role_id=?";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['user_id']]);
        }elseif (!empty($parameter['name'])){
            $sql = "SELECT * FROM role WHERE name LIKE '%{$parameter['name']}%' LIMIT 20";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql);
        }elseif (!empty($parameter['account_id'])){
            $sql = "SELECT * FROM role WHERE  account_id=?";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['account_id']]);
        }

        if (!$user_info){
            return [
                'code' => 1,
                'msg'  => 'no data'
            ];
        }
        
        $result['account_id'] = $user_info['account_id'];
        $result['user_id']  = $user_info['role_id'];
        $result['name'] = $user_info['name'];
        $result['coin'] = $user_info['gold'];
        $result['vip']  = $user_info['total_gold'];
        $result['level'] = $user_info['level'];
        $result['exp']  = '暂无';
        $result['create_time'] =  $this->utilsModel->switchTimeZone(
            'UTC', $this->di['db_cfg']['setting']['timezone'], date('Y-m-d H:i:s',intval($user_info['register_time'])), 'Y-m-d H:i:s 0'
        );
        $result['attribute'] = $user_info;

        return [
            'code'  => 0,
            'msg'   => 'success',
            'data'  => $result
        ];
    }
}