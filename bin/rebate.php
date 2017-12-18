<?php
/**
 * 公测返利脚本 所有服务器
 * User: lihe
 * Date: 2017/10/24
 * Time: 上午11:19
 */
use Symfony\Component\Yaml\Yaml;

ini_set("date.timezone", "UTC");
ini_set("momory_limit", -1);
ini_set("max_execution_time", 0);


class  rebate {

    private $db;

    private $trade_db;

    public function run()
    {
        //1003001 最开始的服务器ID
        //1003020 结束的服务器ID
        for ($serverID = 103001; $serverID < 103020; $serverID++)
        {
            $this -> init_db($serverID);
        }
    }

    private function init_db($zone = 0)
    {
        if (!empty($this->db)) {
            return $this->db;
        }
        $config = Yaml::parse(file_get_contents(__DIR__ . "/../app/Config/HT_taohua/zh_CN.yml"));
        $params = $config[$zone];
        $dsn = 'mysql:host=' . $params['host'] . ';port=' . '3306' . ';dbname=' . $params['db'];
        $db = new PDO($dsn, $params['user'], $params['pass']);
        $db->query('set names ' . $params['charset']);
        $this->db = $db;
        return $this->db;
    }


}

include __DIR__ . '/../vendor/autoload.php';
$rebate = new rebate();
$rebate -> run();