<?php
/**
 * 生化联盟    道具相关
 * User: lihe
 * Date: 2017/5/15
 * Time: 下午4:59
 */
namespace Xt\Rpc\Services\HT_shenghua;

use Exception;
use Xt\Rpc\Models\Utils;
use Xt\Rpc\Core\Service;

class PropService extends Service
{
    private $_utilsModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->_utilsModel = new Utils();
    }

    public function attribute($parameter)
    {
        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => [
                'coin'         => '货币',
                'exp'          => '经验',
                'attach'       => '道具 (默认)',
                'attach_cards' => '卡牌 ',
                'attach_equip' => '装备 ',
                'attach_stuff' => '材料',
                'attach_gold'  => '金币',
                'mail'         => '邮件',
            ]
        ];
    }

    //发送道具
    // message_type #   1:系统发奖, 6:群发消息
    // award_type   #   奖励类型（0默认礼包，1卡牌2装备3材料4金币10元宝）
    public function attach($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['attach']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }

        $result = $this->_propWay($parameter, '0');

        return $result;
    }

    //发送经验
    public function exp($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['amount']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $user_id = $parameter['user_id'];
        $zone = $parameter['zone'];
        $amount = intval($parameter['amount']);

        try {
            $sql = "UPDATE t_game_user SET user_exp = user_exp + $amount WHERE user_id = ?";
            $this->gameDb($zone)->executeUpdate($sql, [$user_id]);
        } catch (Exception $e) {
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }

        if (!empty($parameter['msg'])) {
            $this->_mailTo($zone, $user_id, $parameter['msg']);
        }

        return [
            'code' => 0,
            'msg'  => 'success'
        ];
    }

    //发送游戏币
    public function coin($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['amount']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $user_id = $parameter['user_id'];
        $zone = $parameter['zone'];
        $amount = intval($parameter['amount']);
        //游戏内时间
        $appDateTime = $this->_utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);
        $game_connect = $this->gameDb($zone);
        $game_connect->beginTransaction();

        try {
            $sql = "INSERT INTO t_game_user_recharge(`user_id`, `recharge_money`, `recharge_time`, `recharge_RMB`, `recharge_mode`, `recharge_order_num`, `goods_id`, `is_monthly_card`, `gift_money`) 
                                             VALUES ('$user_id', '$amount', '$appDateTime', '0', '0','0','0','0','0')";
            $game_connect->executeUpdate($sql);
            $game_connect->commit();
        } catch (Exception $e) {
            $game_connect->rollBack();
            $this->di['logger']->error('prop-coin error', $parameter);
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }

        if (!empty($parameter['msg'])) {
            $this->_mailTo($zone, $user_id, $parameter['msg']);
        }

        return [
            'code' => 0,
            'msg'  => 'success'
        ];
    }

    //发送邮件
    public function mail($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['msg']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $msg = $parameter['msg'];
        $user_id = $parameter['user_id'];
        $zone = $parameter['zone'];

        if (!$this->_mailTo($zone, $user_id, $msg)) {
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }

        return [
            'code' => 0,
            'msg'  => 'success'
        ];
    }

    //发送卡牌
    public function attach_cards($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['attach']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }

        $result = $this->_propWay($parameter, '1');

        return $result;
    }

    //发送装备
    public function attach_equip($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['attach']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }

        $result = $this->_propWay($parameter, '2');

        return $result;
    }

    //发送材料
    public function attach_stuff($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['attach']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }

        $result = $this->_propWay($parameter, '3');

        return $result;
    }

    //发送金币
    public function attach_gold($parameter)
    {
        if (empty($parameter['user_id']) || empty($parameter['attach']) || empty($parameter['zone'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }

        $result = $this->_propWay($parameter, '4');

        return $result;

    }

    // message_type #   1:系统发奖, 6:群发消息
    // award_type   #   奖励类型（0默认礼包，1卡牌2装备3材料4金币10元宝）
    private function _mailTo($zone = 0, $user_id = 0, $msg = '')
    {
        $appDateTime = $this->_utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);

        $game_connect = $this->gameDb($zone);
        try {
            $sql = "INSERT INTO t_game_player_msg(`sender_id`, `receiver_id`, `send_time`, `message_type`, `award_id`, `message`, `award_type`) 
                    VALUES ('0', '$user_id', '$appDateTime', '1', '0', '$msg', '0')";
            $game_connect->executeUpdate($sql);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    private function _propWay($parameter, $award_type)
    {

        $user_id = $parameter['user_id'];
        $zone = $parameter['zone'];
        $msg = empty($parameter['msg']) ? '' : $parameter['msg'];

        $appDateTime = $this->_utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);

        //开始处理
        $game_connect = $this->gameDb($zone);
        $attach_list = explode(",", $parameter['attach']);
        if (!$attach_list) {
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }
        foreach ($attach_list as $v) {
            if (strpos($v, '*')) {       //发送奖品的数量
                list($att, $num) = explode('*', $v);
            }
            else {
                $att = $v;
                $num = 1;
            }
            for ($i = 0; $i < $num; $i++) {
                //开始事物
                $game_connect->beginTransaction();

                try {
                    $sql = "INSERT INTO t_game_player_msg(`sender_id`, `receiver_id`, `send_time`, `message_type`, `award_id`, `message`, `award_type`) 
                            VALUES ('0', '$user_id', '$appDateTime','0', '$att','$msg', '$award_type')";
                    $game_connect->executeUpdate($sql);

                    $game_connect->commit();
                } catch (Exception $e) {
                    $game_connect->rollBack();
                    $this->di['logger']->error('prop-attach error', $parameter);
                    return [
                        'code' => 1,
                        'msg'  => 'failed'
                    ];
                }
            }
        }
        
        return [
            'code' => 0,
            'msg'  => 'success'
        ];
    }
}