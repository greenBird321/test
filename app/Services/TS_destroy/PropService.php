<?php
/**
 * 破坏神     道具相关.
 * User: lihe
 * Date: 2017/5/23
 * Time: 上午11:46
 */
namespace Xt\Rpc\Services\TS_destroy;

use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;

class PropService extends Service
{

    private $utilsModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->utilsModel = new Utils();
    }

    public function attribute($parameter)
    {
        return [
            'code'  => 0,
            'msg'   => 'success',
            'data'  => [
                'coin'  => '货币(修改钻石)',                   //DO_UPDATE_ASSET    钻石ID   1002
                'exp'   => '经验',                            //DO_UPDATE_EXP
                'mail'  => '邮件',
                'attach_level'  => '等级',                    //DO_UPDATE_LEVEL
                'attach_money'  => '修改游戏币',               //AQ_DO_UPDATE_MONEY
                'attach_coin'   => '设置财产(设置钻石)',        //DO_SET_ASSET       钻石ID   1002
                'attach_vip'    => '修改VIP点数',              //DO_UPDATE_VIP_LEVEL
                'attach_physical' => '修改体力值'               //DO_UPDATE_PHYSICAL
            ]
        ];
    }

    /*
     * array:5 [
  "app" => 100007
  "uid" => "2052878"
  "coo_server" => "3002"
  "coo_uid" => "12313"
  "data" => array:1 [
    "AQ_DO_UPDATE_MONEY" => "111"
  ]
 ]
     */
    /*
     * array:5 [
      "app" => 100007
      "uid" => "2048031"
      "coo_server" => "3002"
      "coo_uid" => "51459"
      "data" => array:1 [
        "DO_UPDATE_ASSET" => "77"
      ]
    ]
    "do_update_asset"
    array:2 [
      "Value" => "77"
      "AssetId" => 1002
    ]
     */
    //发送货币
    public function coin($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['amount'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }
        //数据组装
        $cmd =  'DO_UPDATE_ASSET';
        $extraParam['AssetId'] = 1002;
        $extraParam['Value'] = $parameter['amount'];
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    //发送经验
    public function exp($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['amount'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }

        //数据组装
        $cmd = 'DO_UPDATE_EXP';
        $extraParam['Value'] = $parameter['amount'];
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    /*
     * array:7 [
                  "app" => 100007
                  "coo_server" => "3002"
                  "coo_uid" => array:1 [
                    0 => "51753"
                  ]
                  "title" => "111112222"
                  "content" => "TEST"
                  "attribute" => ""
                  "global" => ""
                ]
     */
    //发送邮件  目前只支持一个用户
    public function mail($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['msg']) || empty($parameter['title'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }

        //数据组装
        $extraParam['MailTitle'] = $parameter['title'];
        $extraParam['MailContent'] = $parameter['msg'];
        $extraParam['Time'] = time(); // 生效时间
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);
        $cmd = 'DO_SEND_MAIL_ITEM';   //普通邮件口令

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    //设置等级
    public function attach_level($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['attach'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }

        //数据组装
        $cmd = 'DO_UPDATE_LEVEL';
        $extraParam['Value'] = $parameter['attach'];
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    //修改游戏币
    public function attach_money($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['attach'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }

        //数据组装
        $cmd = 'AQ_DO_UPDATE_MONEY';
        $extraParam['Value'] = $parameter['attach'];
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    //设置财产(钻石)
    public function attach_coin($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['attach'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }

        //数据组装
        $cmd = 'DO_SET_ASSET';
        $extraParam['AssetId'] = 1002;
        $extraParam['Value'] = $parameter['attach'];
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    //修改VIP点数
    public function attach_vip($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['attach'])){
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }

        //数据组装
        $cmd = 'DO_UPDATE_VIP_LEVEL';
        $extraParam['Value'] = $parameter['attach'];
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    //修改体力值
    public function attach_physical($parameter){
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['attach'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }

        //数据组装
        $cmd = 'DO_UPDATE_PHYSICAL';
        $extraParam['Value'] = $parameter['attach'];
        $parameter['open_id'] = $this->getOpenIDByUserID($parameter['user_id'], $parameter['zone']);

        return $this->game_interface($parameter, strtolower($cmd), $extraParam);
    }

    //游戏接口
    private function game_interface($baseParam = array(), $cmd = '', $extraParam = array())
    {
        if (empty($baseParam['zone'])) {
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
            'Partition' => $baseParam['zone'],              // 服务器coo_server
            'OpenId'    => $baseParam['open_id'],           // 账号uid
            'RoleId'    => $baseParam['user_id'],           // 角色coo_uid
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
            'msg'  => 'success'
        );
    }

    //查询openID
    private function getOpenIDByUserID($user_id,$zone){
        $prefix = $this->di['db_cfg']['db_prefix']['prefix'];
        $gaccountName = $this->di['db_cfg']['gaccount_dbName']['name'];

        $sql = "SELECT * FROM `{$prefix}1000_game`.`role` role , `{$gaccountName}`.`openid` open WHERE role.accountID=open.accountID AND role.roleID=?";
        $openId = $this->gameDb($zone)->fetchAssoc($sql, [$user_id]);
        return $openId['openID'];
    }
}