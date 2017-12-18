<?php

namespace Xt\Rpc\Models;


use Xt\Rpc\Core\Model;

class Stats extends Model
{

    public function getRealTime($argv = [])
    {
        $date = $argv['date'];

        $month = substr(str_replace('-', '', $date), 0, 6);

        // channel
        $channel = '';
        if (!empty($argv['channel'])) {
            $channel = 'channel,';
        }

        // new account
        $sql_new = "SELECT SUBSTRING(create_time,1,13) `time`, {$channel} COUNT(1) new_account, COUNT(DISTINCT ip) new_ip FROM `account_login_{$month}`
WHERE type=1 AND create_time >='{$date} 00:00:00' AND create_time <='{$date} 23:59:59'
GROUP BY `time`";
        // activity account
        $sql_act = "SELECT SUBSTRING(create_time,1,13) `time`, {$channel} COUNT(DISTINCT user_id) act_account, COUNT(DISTINCT uuid) act_uuid , COUNT(DISTINCT ip) act_ip FROM `account_login_{$month}`
WHERE create_time >='{$date} 00:00:00' AND create_time <='{$date} 23:59:59'
GROUP BY `time`";

        // channel
        if (!empty($argv['channel'])) {
            $sql_new .= ',channel';
            $sql_act .= ',channel';
        }

        $data['new'] = $this->db_logs->fetchAll($sql_new);
        $data['act'] = $this->db_logs->fetchAll($sql_act);

        return $data;
    }


}