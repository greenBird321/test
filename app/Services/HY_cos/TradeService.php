<?php

/**
 * 百万亚瑟王   交易相关
 * User: lihe
 * Date: 2017/5/5
 * Time: 下午4:32
 */
namespace Xt\Rpc\Services\HY_cos;

use Exception;
use Xt\Rpc\Models\Trade;
use Xt\Rpc\Core\Service;

class TradeService extends Service
{
    private $_tradeModel;

    public function __construct($di)
    {
        parent::__construct($di);
        $this->_tradeModel = new Trade();
    }

    /**
     *交易方法
     * @param $parameter
     * @return 交易是否成功
     */
    public function purchase($parameter)
    {
        //暂不检查签名
        unset($parameter['timestamp'], $parameter['sign']);
        $secret_key = $this->di['db_cfg']['setting']['secret_key'];
        $callback_pay = $this->di['db_cfg']['setting']['callback_pay'];
        list($zone, $user_id) = explode('-', $parameter['user_id']);
        //数据组装
        $data = [
            'order_id'          => $parameter['transaction'],            //平台订单ID
            'uid'               => '',                                   //平台用户user_id
            'amount'            => $parameter['amount'],                 //真实充值金额
            'currency'          => $parameter['currency'],               //货币类型
            'amount_original'   => $parameter['amount'],                 //真实充值金额
            'currency_original' => $parameter['currency'],               //真实货币类型
            'gateway'           => $parameter['gateway'],                //充值渠道
            'product_id'        => $parameter['product_id'],             //产品ID
            'coo_order_id'      => '',                                   //厂商订单ID
            'coo_server'        => $zone,                                //厂商服序列ID
            'coo_uid'           => $user_id,                             //厂商服UID
            'extra'             => '',                                   //拓展字段
            'time'              => time()
        ];

        $data['sign'] = Common::create_sign($data, $secret_key);

        //发起请求
        $data = http_build_query($data);

        $response = file_get_contents($callback_pay, 0, stream_context_create(array(
            'http' => array(
                'timeout' => 30,
                'method'  => 'POST',
                'header'  => "Content-Type: text/html; charset=utf-8",
                'content' => $data
            )
        )));

        if ($response == 'fail'){
            $this->di['logger']->error('purchase error', $parameter);
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }

        //开始数据库事务
        $this->db_data->beginTransaction();

        try{
            //写入日志数据
            $parameter['create_time'] = date('Y-m-d H:i:s');
            $this->db_data->insert('logs_purchase', $parameter);
            //提交事务
            $this->db_data->commit();
        }catch (Exception $e){
            $this->db_data->rollBack();
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