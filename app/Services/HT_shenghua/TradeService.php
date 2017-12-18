<?php
/**
 * 生化联盟     交易相关.
 * User: lihe
 * Date: 2017/5/15
 * Time: 下午4:58
 */
namespace Xt\Rpc\Services\HT_shenghua;

use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;
use Xt\Rpc\Models\Trade;

class TradeService extends Service{
    private $_utilsModel;
    private $_tradeModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->_utilsModel = new Utils();
        $this->_tradeModel = new Trade();
    }

    public function purchase($parameter){
        //暂不检查签名
        unset($parameter['sign'], $parameter['timestamp']);
        if (!strpos($parameter['user_id'], '-')){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }
        list($zone , $user_id) = explode('-', $parameter['user_id']);

        //当前RPC时间
        $dateTimeNow = date('Y-m-d H:i:s');

        //当前游戏时间
        $appDataTime = $this->_utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);

        //检查订单
        $sql = "SELECT id FROM logs_purchase WHERE  transaction=?";
        if ($this->db_data->fetchAssoc($sql, [$parameter['transaction']])){
            $this->di['logger']->error('purchase error - transaction exist', $parameter);
            return false;
        }

        //检查产品
        $sql = "SELECT product_id, type, name, gateway, price, currency, coin, custom FROM products WHERE product_id=?";
        $bind[] = $parameter['product_id'];
        $product = $this->db_data->fetchAssoc($sql, [$parameter['product_id']]);
        if (!$product){
            $this->di['logger']->error('purchase error - no product', $parameter);
            return false;
        }
        $coin = $product['coin'];
        $prop = null;

        //检查促销
        $sql = "SELECT type, lowest, coin, prop FROM products_cfg WHERE  product_id=1 AND lowest<=1 AND ('$dateTimeNow' BETWEEN  start_time AND end_time)";
        $bind[] = $parameter['amount'];
        $cfg = $this->db_data->fetchAll($sql, $bind);
        if ($cfg){
            foreach ($cfg as $k => $v){
                switch ($v['type']){
                    case 'promo':
                        $coin_extra = $v['coin'];
                        break;

                    case 'first_purchase':
                        if (!$this->_tradeModel->checkFirstPurchase($user_id, $parameter['product_id'])){
                            $coin_extra = $v['coin'];
                        }
                        break;
                }
            }
        }

        //开始处理
        $game_connect = $this->gameDb($zone);

        //月卡处理
        if ($product['type'] == 'special'){
            if ($product['custom'] == 'card_month'){
                $card_month = 1;
            }else{
                $card_month = 0;
            }
        }
        //参数整理
        $amount = $parameter['amount'];
        $recharge_type = 0; // 固定值
        $transaction = $parameter['transaction'];
        $good_id = $parameter['product_id'];
        
        //开始事物
        $game_connect->beginTransaction();

        try{
            //开始充值
            $sql = "INSERT INTO t_game_user_recharge(`user_id`, `recharge_money`, `recharge_time`, `recharge_RMB`, `recharge_mode`, `recharge_order_num`, `goods_id`, `is_monthly_card`, `gift_money`)
                VALUES ('$user_id', '$coin','$dateTimeNow', '$amount','$recharge_type','$transaction', '$good_id', '$card_month', '$coin_extra')";
            $game_connect->executeUpdate($sql);
            $game_connect->commit();
        }catch (Exception $e){
            $game_connect->rollBack();
            $this->di['logger']->error('purchase error', $parameter);
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }

        //写入日志
        $this->db_data->beginTransaction();
        try{
            $parameter['create_time'] = date('Y-m-d H:i:s');
            $this->db_data->insert('logs_purchase', $parameter);
            $this->db_data->commit();
        }catch (Exception $e){
            $this->db_data->rollBack();
            return [
                'code'  => 1,
                'msg'   => 'failed'
            ];
        }

        return [
            'code' => 0,
            'msg'  => 'success'
        ];

    }
}