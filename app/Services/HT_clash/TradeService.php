<?php
/**
 * 末世      交易相关.
 * User: lihe
 * Date: 2017/5/23
 * Time: 上午11:03
 */
namespace Xt\Rpc\Services\HT_clash;

use Exception;
use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;
use Xt\Rpc\Models\Trade;

class TradeService extends Service
{

    private $utilsModel;
    private $tradeModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->utilsModel = new Utils();
        $this->tradeModel = new Trade();
    }

    /**
     *交易方法
     * @param $parameter
     * @return 交易是否成功
     */
    public function purchase($parameter)
    {
        //暂不检查签名
        unset($parameter['sign'], $parameter['timestamp']);
        if (!strpos($parameter['user_id'], '-')) {
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }
        list($zone, $user_id) = explode('-', $parameter['user_id']);

        //rpc 系统时间
        $dateTimeNow = date('Y-m-d H:i:s');

        $log = time();

        //游戏时间
        $appDataTime = $this->utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);

        //订单id
        $transaction = $parameter['transaction'];

        //检查订单
        $sql = "SELECT id FROM logs_purchase WHERE transaction=?";
        if ($this->db_data->fetchAssoc($sql, [$transaction])) {
            $this->di['logger']->error('purchase error - transaction exist', $parameter);
            return false;
        }

        //检查产品
        $sql = "SELECT product_id, type, gateway, price, currency, coin, custom FROM products WHERE  product_id=? AND status=1";
        $bind[] = $parameter['product_id'];
        $product = $this->db_data->fetchAssoc($sql, $bind);
        if (!$product) {
            $this->di['logger']->error('purchase error - no product', $parameter);
            return false;
        }
        $coin = $product['coin'];
        $prop = null;

        //检查促销
        $sql = "SELECT type,lowest,coin,prop FROM products_cfg WHERE  product_id=? AND lowest<=? AND ('$dateTimeNow' BETWEEN start_time AND  end_time)";
        $bind[] = $parameter['amount'];
        $cfg = $this->db_data->fetchAll($sql, $bind);
        if ($cfg) {
            foreach ($cfg as $k => $v) {
                switch ($v['type']) {
                    case 'promo' :                        //促销
                        $coin += $v['coin'];
                        break;
                    case 'first_purchase' :               //首冲
                        if (!$this->tradeModel->checkFirstPurchase($parameter['user_id'], $parameter['product_id'])) {
                            $coin += $v['coin'];
                        }
                        break;
                }
            }
        }

        //开始处理充值
        $connect = $this->gameDb($zone);

        //开始事物
        $connect->beginTransaction();

        try {
            //数据整理
            $recharge_type = 0;     //固定值
            $time = date('Y-m-d H:i:s');
            $order_id = $parameter['transaction'];  //订单ID
            $good_id = $parameter['product_id'];    //商品ID
            $role_id = $user_id;                    //用户ID
            $money = $coin;                         //游戏代币
            $amount = $parameter['amount'];         //RMB
            $is_monthly_card = 0;
            $money_extra = 0;       //默认值

            $sql = "INSERT INTO t_game_user_recharge(`user_id`, `recharge_money`, `recharge_time`, `recharge_RMB`,`recharge_mode`, `recharge_order_num`, `goods_id`, `is_monthly_card`,`gift_money`) 
                    VALUES ('$role_id', '$money', '$time', '$amount', '$recharge_type', '$order_id', '$good_id', '$is_monthly_card', '$money_extra')";
            $connect->executeUpdate($sql);
            $connect->commit();
        } catch (Exception $e) {
            $connect->rollBack();
            $this->di['logger']->error('purchase error', $parameter);
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
}