<?php
/**
 *银河  交易相关.
 * User: lihe
 * Date: 2017/5/22
 * Time: 上午11:56
 */
namespace Xt\Rpc\Services\HT_pocket;

use Exception;
use Xt\Rpc\Models\Trade;
use Xt\Rpc\Core\Service;
use Xt\Rpc\Models\Utils;

class TradeService extends Service{

    private $utilsModel;

    private $tradeModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->utilsModel = new Utils();
        $this->tradeModel = new Trade();
    }

    public function purchase($parameter){
        //暂不检查签名
        unset($parameter['sign'],$parameter['timestamp']);
        if (!strpos($parameter['user_id'], '-')){
            return [
                'code' => 1,
                'msg'  => 'missing parameter'
            ];
        }
        list($zone , $user_id) = explode('-', $parameter['user_id']);

        //rpc系统时间
        $dateTimeNow = date('Y-m-d H:i:s');

        $logTime = time();

        //游戏时间
        $appDataTime = $this->utilsModel->switchTimeZone($this->di['db_cfg']['setting']['timezone'],
            $this->di['config']['setting']['timezone']);

        //订单ID
        $transaction = $parameter['transaction'];

        //检查订单
        $sql = "SELECT id FROM logs_purchase WHERE transaction=?";
        if ($this->db_data->fetchAssoc($sql , [$transaction])){
            $this->di['logger']->error('purchase error - transaction exist', $parameter);
            return false;
        }

        //检查产品
        $sql = "SELECT product_id,type,name,gateway,price,currency,coin,custom FROM products WHERE product_id=? AND  status=1";
        $bind[] = $parameter['product_id'];
        $product = $this->db_data->fetchAssoc($sql, $bind);
        if (!$product){
            $this->di['logger']->error('purchase error - no product', $parameter);
            return false;
        }
        $coin = $product['coin'];
        $prop = null;

        //检查促销
        $sql = "SELECT type,lowest,coin,prop FROM products_cfg WHERE  product_id=? AND lowest<=? AND ('$dateTimeNow' BETWEEN start_time AND  end_time)";
        $bind[] = $parameter['amount'];
        $cfg = $this->db_data->fetchAll($sql, $bind);
        if ($cfg){
            foreach($cfg as $k => $v){
                switch ($v['type']){
                    case 'promo' :                        //促销
                        $coin += $v['coin'];
                        break;
                    case 'first_purchase' :               //首冲
                        if (!$this->tradeModel->checkFirstPurchase($parameter['user_id'], $parameter['product_id'])){
                            $coin += $v['coin'];
                        }
                        break;
                }
            }
        }

        $year = date("Y",time());
        $month = date("m", time());
        //开始处理
        $game_connect = $this->gameDb($zone);
        //开始事物
        $game_connect->beginTransaction();

        try{
            $sql = "INSERT INTO gold_log_{$year}_{$month} (role_id, gold, total_gold, order_id, log_time) VALUES ('$user_id', '$coin', '$coin', '$transaction', '$logTime')";
            $game_connect->executeUpdate($sql);
            $game_connect->commit();
        }catch (Exception $e){
            $game_connect->rollBack();
            $this->di['logger']->error('purchase error', $parameter);
            return [
                'code'  => 1,
                'msg'   => 'failed'
            ];
        }
        
        $this->db_data->beginTransaction();
        try {
            // 写入日志数据
            $parameter['create_time'] = date('Y-m-d H:i:s');
            $this->db_data->insert('logs_purchase', $parameter);
            $this->db_data->commit();
        } catch (Exception $e) {
            $this->db_data->rollBack();
            return ['code' => 1, 'msg' => 'failed'];
        }

        return ['code' => 0, 'msg' => 'success'];
    }

}
