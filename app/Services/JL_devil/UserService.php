<?php
/**
 * 罪恶之城   用户相关
 * User: lihe
 * Date: 2017/5/10
 * Time: 下午6:10
 */
namespace Xt\Rpc\Services\JL_devil;

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
        if (!empty($parameter['user_id'])){
            $sql = "SELECT * FROM db_player WHERE uid=?";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['user_id']]);
        }elseif (!empty($parameter['name'])){
            $sql = "SELECT * FROM db_player WHERE nickname LIKE '%{$parameter['name']}%' LIMIT 20";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql);
        }elseif (!empty($parameter['account_id'])){
            $sql = "SELECT * FROM db_player WHERE accountID=?";
            $userInfo = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['account_id']]);
        }

        if (!$userInfo) {
            return [
                'code' => 1,
                'msg'  => 'no data'
            ];
        }

        $result['account_id'] = $userInfo['accountID'];
        $result['user_id'] = $userInfo['uid'];
        $result['name'] = $userInfo['nickname'];
        $result['coin'] = $userInfo['hc'];
        $result['level'] = $userInfo['level'];
        $result['exp'] = $userInfo['exp'];
        $result['vip'] = $userInfo['vip'];
        $result['create_time'] = $this->_utilsModel->switchTimeZone(
                'UTC', $this->di['db_cfg']['setting']['timezone'],date('Y-m-d H:i:s',intval($userInfo['createTime'])), 'Y-m-d H:i:s O');
        $result['attribute'] = $userInfo;

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => $result
        ];
    }
}