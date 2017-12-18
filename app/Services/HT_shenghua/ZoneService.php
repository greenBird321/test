<?php
/**
 * 生化联盟     服务器相关.
 * User: lihe
 * Date: 2017/5/17
 * Time: 上午11:59
 */
namespace Xt\Rpc\Services\HT_shenghua;

use Xt\Rpc\Core\Service;
use Exception;

class ZoneService extends Service
{
    private $_status = [
        'on'      => '',   //打开
        'off'     => 0,    //关闭
        'sandbox' => 5     //维护
    ];

    private $_tag = [
        'hot'       => 3, // 服务器状态爆满 4代表的爆满状态
        'usual'     => 2, // 服务器状态良好
        'new'       => 1, // 新开推荐
    ];

    public function lists($parameter)
    {
        $sql = "SELECT * FROM t_game_zone WHERE 1=1";
        $lists = $this->gameDb('zone_list')->fetchAll($sql);

        $dict_status = array_flip($this->_status);
        $dict_tag  = array_flip($this->_tag);
        $zone_list = [];

        foreach ($lists as $k => $v){
            $zone_list[$k]['id'] = $v['zone_id'];
            $zone_list[$k]['name'] = $v['zone_name'];
            $zone_list[$k]['host'] = '';
            $zone_list[$k]['port'] = '';

            $tag = $v['zone_status'];
            $is_recommend = $v['is_recommend'];             //是否是推荐服

            if (isset($dict_status[$tag])){
                $zone_list[$k]['status'] = $dict_status[$tag];
            }elseif ($is_recommend){
                $zone_list[$k]['status'] = 'recommend';
            }else{
                $zone_list[$k]['status'] = 'on';
            }
            if (isset($dict_tag[$tag])){
                $zone_list[$k]['tag'] = $dict_tag[$tag];
            }elseif ($is_recommend){
                $zone_list[$k]['tag'] = 'recommend';
            }else{
                $zone_list[$k]['tag'] = 'usual';
            }
        }

        return [
            'code'  => 0,
            'msg'   => 'success',
            'data'  => $zone_list
        ];
    }

    public function item($parameter){
        if (empty($parameter['id'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }
        $server_id = intval($parameter['id']);

        $sql = "SELECT * FROM t_game_zone WHERE 1=1 AND zone_id=?";
        $server = $this->gameDb('zone_list')->fetchAssoc($sql, [$server_id]);
        if (!$server){
            return [
                'code'  => 1,
                'msg'   => 'no data'
            ];
        }

        $result = [
            'id'    => $server['zone_id'],
            'name'  => $server['zone_name'],
            'host'  => '',
            'port'  => ''
        ];

        $dict_status = array_flip($this->_status);
        $dict_tag  = array_flip($this->_tag);

        $tag = $server['zone_status'];
        $is_recommend = $server['is_recommend'];             //是否是推荐服

        if (isset($dict_status[$tag])){
            $result['status'] = $dict_status[$tag];
        }elseif($is_recommend){
            $result['status'] = 'recommend';
        }else{
            $result['status'] = 'on';
        }

        if (isset($dict_tag[$tag])){
            $result['tag'] = $dict_tag[$tag];
        }elseif($is_recommend){
            $result['tag'] = 'recommend';
        }else{
            $result['tag']  = 'usual';
        }

        return [
            'code'  => 0,
            'msg'   => 'success',
            'data'  => $result
        ];
    }

    public function create($parameter){
        if (empty($parameter['id']) || empty($parameter['name'])){
            return [
                'code'  => 1,
                'msg'   => 'missing parameter'
            ];
        }
        if  ($parameter['status'] != 'on'){
            $tag = $this->_status[$parameter['status']];
        }else{
            $tag = $this->_tag[$parameter['tag']];
        }

        if ($parameter['tag'] == 'recommend'){
            $is_recommend = 1;
        }else{
            $is_recommend = 0;
        }

        $data = [
            'zone_id'       => $parameter['id'],
            'zone_name'     => $parameter['name'],
            'is_recommend'  => $is_recommend,
            'zone_group_id' => '3',
            'zone_status'   => $tag
        ];

        try{
            $this->gameDb('zone_list')->insert('t_game_zone',$data);
        }catch (Exception $e){
            return [
                'code'  => 1,
                'msg'   => 'failed'
            ];
        }

        return [
            'code'  => 0,
            'msg'   => 'success'
        ];
    }

    public function modify($parameter){
        if (empty($parameter['id'])){
            return [
                'code'  => 1,
                'msg'   => 'failed'
            ];
        }
        $id = intval($parameter['id']);

        if  ($parameter['status'] != 'on'){
            $tag = $this->_status[$parameter['status']];
        }else{
            $tag = $this->_tag[$parameter['tag']];
        }

        if  ($parameter['tag'] == 'recommend'){
            $is_recommend = 1;
        }else{
            $is_recommend = 0;
        }

        $data = [
            'zone_id'   => $id,
            'zone_name' => $parameter['name'],
            'zone_status'   => $tag,
            'zone_group_id' => '3',
            'is_recommend'  => $is_recommend
        ];

        try{
            $this->gameDb('zone_list')->update('t_game_zone', $data ,['zone_id' => $parameter['id']]);
        }catch (Exception $e){
            return [
                'code'  => 1,
                'msg'   => 'failed'
            ];
        }

        return [
            'code'  => 0,
            'msg' => 'success'
        ];
    }

    public function remove($parameter){
        if (empty($parameter['id'])){
            return [
                'code' => 1,
                'msg'  => 'failed'
            ];
        }

        try{
            $this->gameDb('zone_list')->delete('t_game_zone', ['zone_id' => $parameter['id']]);
        }catch (Exception $e){
            return [
                'code'    => 1,
                'msg'     => 'failed'
            ];
        }

        return [
            'code'  => 0,
            'msg'   => 'success'
        ];
    }
}