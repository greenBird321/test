<?php
/**
 * 末世      道具相关.
 * User: lihe
 * Date: 2017/5/23
 * Time: 上午11:02
 */
namespace Xt\Rpc\Services\HT_clash;

use Exception;
use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;

class PropService extends Service
{

    private $utilsModels;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->utilsModels = new Utils();
    }

    public function attribute($parameter)
    {
        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => [
                'coin'   => '钻石',
                'mail'   => '邮件',
                'attach' => '道具'
            ]
        ];
    }

    public function coin($parameter)
    {
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['amount'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];
        $user_id = $parameter['user_id'];
        $money = intval($parameter['amount']);

        //开始处理
        $game_connect = $this->gameDb($zone);
        $game_connect->beginTransaction();
        //数据组装
        $recharge_type = 0;     //固定值
        $time = date('Y-m-d H:i:s');
        $order_id = 0;          //默认值
        $good_id = 0;           //默认值
        $amount = 0;
        $is_mothly_card = 0;    //默认值
        $moeny_extra = 0;       //默认值

        try {
            $sql = "INSERT INTO t_game_user_recharge(`user_id`, `recharge_money`, `recharge_time`, `recharge_RMB`, `recharge_mode`, `recharge_order_num`, `goods_id`, `is_monthly_card`, `gift_money`) 
                    VALUES ('$user_id', '$money', '$time', '$amount', '$recharge_type', '$order_id', '$good_id', '$is_mothly_card', '$moeny_extra')";
            $game_connect->executeUpdate($sql);
            $game_connect->commit();
        } catch (Exception $e) {
            $game_connect->rollBack();
            $this->di['logger']->error('prop-coin error', $parameter);
            return false;
        }

        if (!empty($parameter['msg'])) {
            $this->_mailTo($zone, $user_id, $parameter['msg']);
        }

        return [
            'code' => 0,
            'msg'  => 'success'
        ];
    }

    public function mail($parameter)
    {
        if (empty($parameter['zone']) || empty($parameter['user_ud']) || empty($parameter['msg'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];
        $user_id = $parameter['user_id'];
        $msg = $parameter['msg'];

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

    public function attach($parameter)
    {
        if (empty($parameter['zone']) || empty($parameter['user_id']) || empty($parameter['attach'])) {
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        $zone = $parameter['zone'];
        $user_id = $parameter['user_id'];
        $msg = empty($parameter['msg']) ? '' : $parameter['msg'];

        $appDateTime = $this->utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);

        //开始处理
        $connect = $this->gameDb($zone);
        $attach_list = explode(',', $parameter['attach']);

        foreach ($attach_list as $attach) {
            if (strpos('*', $attach)) {
                list($att, $num) = explode('*', $attach);
            }
            else {
                $att = $attach;
                $num = 1;
            }
        }
        for ($i = 0; $i < $num; $i++) {
            $connect->beginTransaction();
            try {
                $sql = "INSERT INTO t_game_player_msg(`sender_id`, `receiver_id`, `send_time`, `message_type`, `award_id`, `message`, `award_type`)
                        VALUES ('0', '$user_id', '$appDateTime', '1', '$att', '$msg', '1')";
                $connect->executeUpdate($sql);

                $connect->commit();
            } catch (Exception $e) {
                $connect->rollBack();
                $this->di['logger']->error('prop-attach error', $parameter);
            }
        }

        return [
            'code' => 0,
            'msg'  => 'success'
        ];
    }

    //mailTo方法 只发送消息, 不发送奖励
    // message_type #   1:系统发奖, 6:群发消息
    // award_type   #   奖励类型（0默认礼包，1卡牌2装备3材料4金币10元宝）
    private function _mailTo($zone, $user_id, $msg)
    {

        $appDateTime = $this->utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);

        $conn = $this->gameDb($zone);

        try {
            $sql = "INSERT INTO t_game_player_msg(`sender_id`, `receiver_id`, `send_time`, `message_type`, `award_id`, `message`, `award_type`) 
                    VALUES ('0', '$user_id', '$appDateTime', '1', '0', '$msg', '0')";
            $conn->executeUpdate($sql);
        } catch (Exception $e) {
            return false;
        }
    }


}