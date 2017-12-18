<?php

namespace Xt\Rpc\Controllers;


class StatsController extends ControllerBase
{


    /**
     * 统计实时数据
     * @api {get} /stats/realTime 统计实时数据
     * @apiGroup stats
     * @apiName realTime
     *
     * @apiParam {String} date 日期
     * @apiParam {String} [channel] 固定true
     *
     */
    public function realTime()
    {
        $parameter['date'] = $this->request->query->get('date');
        $parameter['channel'] = $this->request->query->get('channel');
        return $this->api('Stats', __FUNCTION__, $parameter);
    }

}