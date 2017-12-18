<?php
/**
 * 通过 Open_id 给 游戏发送礼包.
 * User: lihe
 * Date: 2017/11/6
 * Time: 上午11:17
 */
use Symfony\Component\Yaml\Yaml;

ini_set("date.timezone", "UTC");
ini_set('memory_limit', -1);
ini_set('max_execution_time', '0');
//当Php不能识别结束符 或者 编码时候, 需要启用 auto_detect_line_endings 配置
ini_set('auto_detect_line_endings', true);

class  AwardByChannel
{
    static private $db;     //游戏 数据库 链接
    static private $web_Db; //网站 数据库 链接
    public $gift;          // 发送的礼物   (必须是数组)
    public $gift_name;     // 发送邮件的标题
    public $server;        // 发送的服务器ID

    public function run()
    {
        //获得CSV中的open_id数据
        $open_id = $this->readFile('test.csv');

        //通过open_id 获得 account_id
        foreach ($open_id as $id) {
            $accountId[] = $this->queryAccountIdByOpenId($id);
        }

        //从游戏库中读取本服务器的role 并 发送礼包
        foreach ($this->server as $server) {
            foreach ($accountId as $id) {
                $result[] = $this->queryRoleIdByAccountId($id, $server, $this->gift, $this->gift_name);
            }
        }

        dump($result);
        exit;
    }

    /**
     * 读取文件内容
     */
    public function readFile($fileName = '')
    {
        $data = file('./' . $fileName);
        foreach ($data as $key => $value) {
            $data[$key] = rtrim($value, "\r");
        }
        if (count($data) == 1) {
            $getData = explode(",", $data[0]);
        }

        return empty($getData) ? $data : $getData;
    }


    /**
     * 切换游戏服务器
     */
    public function gameDb($zone = 0)
    {
        if (!empty($this->db)) {
            return $this->db;
        }
        $config = Yaml::parse(file_get_contents(__DIR__ . "/../app/Config/HT_taohua/zh_CN.yml"));
        $params = $config[$zone];
        try {
            $dsn = 'mysql:host=' . $params['host'] . ';port=' . $params['port'] . ';dbname=' . $params['db'];
            $db = new PDO($dsn, $params['user'], $params['pass']);
            $db->query('set names ' . $params['charset']);
        } catch (Exception $e) {
            throw new Exception($e);
        }
        $this->db = $db;
        return $this->db;
    }

    /**
     * 切换到网站的数据库
     */
    public function webDb()
    {
        if (!empty($this->web_Db)) {
            return $this->web_Db;
        }
        $webConf = [
            'host'     => '192.168.1.129',
            'port'     => '3306',
            'dbname'   => 'oauth',
            'username' => 'root',
            'password' => 'root',
            'charset'  => 'utf8mb4'
        ];
        try {
            $dsn = 'mysql:host=' . $webConf['host'] . ';port=' . $webConf['port'] . ';dbname=' . $webConf['dbname'];
            $db = new PDO($dsn, $webConf['username'], $webConf['password']);
            $db->query('set names' . $webConf['charset']);
        } catch (ErrorException $e) {
            throw new Exception($e);
        }
        $this->web_Db = $db;
        return $this->web_Db;
    }

    /**
     * 通过open_id查询account_id 区分 QQ 以及微信
     */
    public function queryAccountIdByOpenId($openId)
    {
        //$openId = mb_convert_encoding($openId, 'ASCII' , 'UTF-8');
        //判断是qq的openId 还是 微信的openId
        if (substr($openId, 0, 1) == 'o')   //微信
        {
            $sql = "SELECT `user_id` FROM `oauth_yweixin` WHERE open_id='{$openId}'";
        }
        else {                              //qq
            $sql = "SELECT `user_id` FROM `oauth_yqq` WHERE open_id='{$openId}'";
        }
        $query = $this->webDb()->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $usr_id = $query->fetch();
        return $usr_id['user_id'];
    }

    /**
     * 通过account_id 去 游戏库中查询role_id 并且发奖
     */
    public function queryRoleIdByAccountId($account_id = '', $zone = 0, $gifts = [], $gift_name = '')
    {
        if (empty($account_id) || empty($zone)) {
            echo "account_id or zone is empty";
            return;
        }
        $sql = "SELECT role_id FROM role WHERE account_id = '{$account_id}'";
        $query = $this->gameDb($zone)->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $roleId = $query->fetch();
        //判断此服务器是否有此账号ID关联的角色 如果有的话 就发奖 先插入mail , mail_id 插入 maillog
        if (!empty($roleId['role_id'])) {
            //发送礼物
            foreach ($gifts as $gift) {
                $param = [
                    'role_id'    => $roleId['role_id'],
                    'other_role' => 0,
                    'type'       => 1,
                    'unread'     => 1,
                    'gift'       => $gift,
                    'content'    => $gift_name,
                    'sent_time'  => date('Y-m-d H:i:s')
                ];



                try {
                    $insert = "INSERT INTO `mail` (`role_id`, `other_role`, `type`, `unread`, `gift`, `content`, `sent_time`) VALUES ('{$param['role_id']}', '{$param['other_role']}', '{$param['type']}', '{$param['unread']}', '{$param['gift']}', '{$param['gift']}', '{$param['sent_time']}') ";
                    $qu = $this->gameDb($zone)->exec($insert);
                } catch (Exception $e) {
                    throw new Exception($e);
                }

                $mail_id = $this->gameDb($zone)->lastInsertId();
                $mail_log_datetime = date('Y-m-d H:i:s');

                try{
                    $mail_log_sql = "INSERT INTO `maillog` (`mid`, `roleid`, `datetime`) VALUES ('{$mail_id}', '{$param['role_id']}','{$mail_log_datetime}')";
                    $mail_log_pdo = $this->gameDb($zone)->exec($mail_log_sql);
                }catch (Exception $e){
                    throw new Exception($e);
                }

                return true;
            }
        }

    }
}

include __DIR__ . '/../vendor/autoload.php';
$award = new AwardByChannel();
$award->gift = ['11111', '22222'];
$award->server = ['103001', '103002'];
$award->gift_name = '测试礼包';
$award->run();