<?php
/**
 * 破坏神     用户相关.
 * User: lihe
 * Date: 2017/5/23
 * Time: 上午11:47
 */
namespace Xt\Rpc\Services\TS_destroy;

use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;

class  UserService extends Service
{

    private $utilsModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->utilsModel = new Utils();
    }

    public function profile($parameter)
    {
        if (empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];

        $prefix = $this->di['db_cfg']['db_prefix']['prefix'];
        $gaccountName = $this->di['db_cfg']['gaccount_dbName']['name'];

        //用户查询
        if (!empty($parameter['user_id'])) {
            //此处的1000为测试数据, 正式应该是{$zone}
            $sql = "SELECT * FROM `{$prefix}1000_game`.`role` role , `{$gaccountName}`.`openid` open WHERE role.accountID=open.accountID AND role.roleID=? ";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['user_id']]);
        }
        elseif (!empty($parameter['name'])) {
            $sql = "SELECT * FROM `{$prefix}1000_game`.`role` role, `{$gaccountName}`. `openid` open WHERE role.accountID=open.accountID AND role.name LIKE  '{$parameter['name']}' LIMIT 20";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql);
        }
        elseif (!empty($parameter['account_id'])) {
            $sql = "SELECT * FROM `{$prefix}1000_game`.`role` role, `{$gaccountName}`.`openid` open WHERE role.accountID=open.accountID AND role.accountID=?";
            $user_info = $this->gameDb($zone)->fetchAssoc($sql, [$parameter['account_id']]);
        }

        if (!$user_info) {
            return [
                'code' => 1,
                'msg'  => 'no data'
            ];
        }

        //组装数据  coo_server = 3002  coo_uid = 50286  uid = 2048031
        $data['coo_server'] = $zone;
        $data['coo_uid'] = $user_info['roleID'];
        $data['uid'] = $user_info['openID'];

        $role_extra = $this->game_interface($data, strtolower('QUERY_USER_INFO'));
        $user_info = array_merge($user_info, $role_extra['data']);

        $result['account_id'] = $user_info['accountID'];
        $result['role_id'] = $user_info['roleID'];
        $result['name'] = $user_info['name'];
        $result['coin'] = isset($user_info['Diamond']) ? $user_info['Diamond'] : '';       //容错处理
        $result['level'] = isset($user_info['Level']) ? $user_info['Level'] : '';          //容错处理
        $result['vip'] = $user_info['vipPoint'];                                           //不确定是否是vipPoint 还有一个字段是vip等级vipLevel
        $result['create_time'] = $this->utilsModel->switchTimeZone(
            'UTC', $this->di['db_cfg']['setting']['timezone'], $user_info['createTime'], 'Y-m-d H:i:s O'
        );
        $result['attribute'] = $user_info;

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => $result
        ];
    }

    private function game_interface($baseParam = array(), $cmd = '', $extraParam = array())
    {
        if (empty($baseParam['coo_server'])) {
            return array(
                'code' => 1,
                'msg'  => '失败,服务器不能为空'
            );
        }

        if (!$cmd) {
            return array(
                'code' => 1,
                'msg'  => '操作失败'
            );
        }

        $target = $this->di['db_cfg']['setting']['target'];
        $key = "{$target}_gm_server";
        $uri = "http://{$this->di['db_cfg']['setting']["$key"]}/api/$cmd";

        $data = array(
            'AreaId'    => 2,                               // 需与IDIP服务器配置对应, 服务器：微信（1），手Q（2）
            'PlatId'    => 1,                               // 需与IDIP服务器配置对应, 平台：IOS（0），安卓（1），全部（2）
            'Partition' => $baseParam['coo_server'],        // 服务器coo_server
            'OpenId'    => $baseParam['uid'],               // 账号uid
            'RoleId'    => $baseParam['coo_uid'],           // 角色coo_uid
        );
        $data = array_filter($data);
        $data = array_merge($data, $extraParam);
        $url = $uri . '?' . http_build_query($data);
        // 针对邮件附件功能的格式处理
        $url = str_replace('%7C', '|', $url);
        $url = str_replace('+', '%20', $url);

        $response = file_get_contents($url);
        $result = json_decode(json_decode($response, true), true);
        if (!(isset($result['0']['Result']) && $result['0']['Result'] == 0)) {
            return array(
                'code' => 1,
                'msg'  => $result['0']['RetErrMsg'],
                'data' => []            //由于除了泰国 cp查询接口其他语言都有问题 容错处理
            );
        }

        return array(
            'code' => 0,
            'msg'  => '操作成功',
            'data' => $result['1']
        );
    }
}