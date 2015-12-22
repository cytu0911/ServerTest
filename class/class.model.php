<?php

class model
{
	public $mysql = null;
	public $redis = null;
	public $shm = null;

	public $s_noob = array('yishiteng'=>5000);//特殊渠道新手筹码数
	public $s_trial = array('yishiteng'=>5000);//特殊渠道补助筹码数

	private $key_cache = "lord_cache";	//缓存数组
	private $key_api_task = "lord_api_task";//接口任务池
	private $key_queue_push = "lord_queue_push";//任务推送队列
	private $key_queue_file_ = "lord_queue_file_";//文件操作队列
	private $key_queue_tsgrab_ = "lord_queue_tsgrab_";//争抢暴奖队列
	private $key_list_file = "lord_list_file";//素材列表
	private $key_list_tips = "lord_list_tips";//提示列表
	private $key_list_topic = "lord_list_topic";//活动列表
	private $key_list_notice = "lord_list_notice";//公告列表
	private $key_list_newmail_ = "lord_list_newmail_";//个人新邮件列表key_list_newmail_.$uid，向在线用户新增时以mailid为key增加，每次定时推送后或者拉取完整邮件列表时删除
	private $key_list_room = "lord_list_room";//扩展房间列表
	private $key_game_version = "lord_game_version";//各种版本号

	private $key_model_rooms_ = "lord_model_rooms_";//赛事配置$modelId: $roomsId => 房间的配置
	private $key_model_weeks_ = "lord_model_weeks_";//周赛记录$modelId: $weeksId => 房间周赛数据，依据报名、取消、场次结束而变更
	private $key_model_games_ = "lord_model_games_";//场赛记录$modelId: $gamesId => 房间周赛场赛数据、场赛状态，用hostId识别执行服
	private $key_model_weekplay_ = "lord_model_weekplay_";//周赛玩家记录$modelId: $playersid => 房间周赛用户的参赛数据，依据参赛情况而变更
	private $key_model_gameplay_ = "lord_model_gameplay_";//场赛玩家记录$gamesId: $uid => 房间场赛用户的参赛数据，依据参赛情况而变更
	private $key_model_goonplay_ = "lord_model_goonplay_";//参赛报名数组$gamesId: list $gameplay
	private $key_modelgame_players_ = "lord_modelgame_players_";

	private $key_room_player_ = "lord_room_player_";
	private $key_table_info_  = "lord_table_info_";
	private $key_table_history_ = "lord_table_history_";

	private $key_robot = "lord_robot"; //当前值班机器人
	private $key_robot_list = "lord_robot_zlist";//待续
	private $key_user_task_ = "lord_user_task_";	//用户任务信息
	private $key_user_model_ = "lord_user_model_";	//用户赛事信息 $uid: modelId=>,roomId,weekId,gameId...
	private $key_user_queue_ = "lord_user_queue_";	//用户发包队列
	private $key_user_lottery_ = "lord_user_lottery_";	//用户彩票记录
	private $key_user_grabmail = "lord_user_grabmail";	//临时暴奖争抢邮件，暂无收件人
	private $key_user_tesksurprise = "lord_user_tesksurprise";//

	private $ini_user = array(
		'uid'=>0,
		//...
		'fd'=>0,
		'modelId'=>0,
		'roomId'=>0,
		'tableId'=>0,
		'seatId'=>0,
	);
	private $ini_table = array(//65
		'tableId'=>0,	//标记桌号
		'hostId'=>0,	//标记HOST
		'wid'=>false,
		'modelId'=>0,	//标记模式
		'roomId'=>0,	//标记房间
		'state'=>0,		//桌子状态
		'rate'=>0,		//牌桌倍率
		'rateMax'=>0,	//牌桌顶倍
		'rake'=>0,		//抽水倍率
		'baseCoins'=>0,	//底注基线
		'limitCoins'=>0,//赢钱上限
		'rate_'=>0,				//临时倍率 与rate、seat0rate、seat1rate、seat2rate用于叫抢地主算法
		'firstShow'=>4,			//哪个位置先明牌
		'lordSeat'=>4,			//哪个位置是地主[0|1|2]
		'turnSeat'=>4,			//哪个位置要操作[0|1|2]最先明牌的有叫地主优先权
		'lastCall'=>4,			//哪个席位叫牌/跟牌
		'lastCards'=>array(),	//叫牌/跟牌的内容
		'outCards'=>array(),	//废牌(已出的)
		'lordCards'=>array(),	//底牌(地主牌)
		'noFollow'=>0,			//记录不跟次数
		'isRegame'=>1,			//是否再来一局
		'isNewGame'=>1,			//是否是新一局
		'isStop'=>0,			//是否已经停止继续轮次
		'move'=>0,				//轮次每移动一次＋1
		'create'=>0,			//create time
		'update'=>0,			//update time
		'seats'=>array(),		//uid=>[0|1|2]
		'seat0info'=>array(),	//uid=>/sex=>/nick...
		'seat0fd'=>0,			//席位fd
		'seat0uid'=>0,			//席位uid
		'seat0coins'=>0,		//席位coins
		'seat0score'=>0,		//席位score
		'seat0show'=>0,			//席位是否名牌
		'seat0state'=>0,		//席位游戏状态
		'seat0queue'=>-1,		//席位在牌桌队列的掉线重发起点
		'seat0cards'=>array(),	//席位手牌
		'seat0rate'=>-1,		//席位叫抢地主的倍率
		'seat0robot'=>0,		//席位是否为机器人
		'seat0trust'=>0,		//席位托管状态
		'seat0delay'=>0,		//席位超时次数
		'seat0sent'=>0,			//席位出牌次数
		'seat1info'=>array(),
		'seat1fd'=>0,
		'seat1uid'=>0,
		'seat1coins'=>0,
		'seat1score'=>0,
		'seat1show'=>0,
		'seat1state'=>0,
		'seat1queue'=>-1,
		'seat1cards'=>array(),
		'seat1rate'=>-1,
		'seat1robot'=>0,
		'seat1trust'=>0,
		'seat1delay'=>0,
		'seat1sent'=>0,
		'seat2info'=>array(),
		'seat2fd'=>0,
		'seat2uid'=>0,
		'seat2coins'=>0,
		'seat2score'=>0,
		'seat2show'=>0,
		'seat2state'=>0,
		'seat2queue'=>-1,
		'seat2cards'=>array(),
		'seat2rate'=>-1,
		'seat2robot'=>0,
		'seat2trust'=>0,
		'seat2delay'=>0,
		'seat2sent'=>0,
	);

	function __construct( $redis=null, $mysql=null )
	{
		$this->redis = $redis;
		$this->mysql = $mysql;
	}

	function __destruct()
	{
		return true;
	}

	// push
	public function popPUSH()
	{
		return $this->redis->lpop($this->key_queue_push);
	}

	// API
	public function popAPIS()
	{
		return $this->redis->lpop($this->key_api_task);
	}

	// file
	public function popFILE()
	{
		return $this->redis->lpop($this->key_queue_file_.HOSTID);
	}

	public function bobGRTS( $id, $uid )
	{
		return $this->redis->ladd($this->key_queue_tsgrab_.$id, $uid);
	}

	public function popGRTS( $id )
	{
		return $this->redis->lpop($this->key_queue_tsgrab_.$id);
	}
	public function delGRTS( $id )
	{
		return $this->redis->del($this->key_queue_tsgrab_.$id);
	}
	public function retUserMail( $id, $uid )
	{
		$mail = $this->redis->hget($this->key_user_grabmail, $id);
		$mail['id'] = $id;
		$mail['uid'] = $uid;
		$sql = "UPDATE lord_user_inbox SET `uid` = $uid WHERE `id` = $id";
		$res = $this->mysql->runSql($sql);
		$res = $this->redis->hdel($this->key_user_grabmail, $id);
		return $mail;
	}
	public function delUserMail( $id )
	{
		return $this->redis->hdel($this->key_user_grabmail, $id);
	}
	public function updSurpriseRecord( $id, $uid )
	{
		$sql = "UPDATE lord_user_tesksurprise SET `uid` = $uid WHERE `id` = $id";
		return $this->mysql->runSql($sql);
	}
	public function delSurpriseRecord( $id )
	{
		$sql = "DELETE FROM lord_user_tesksurprise WHERE `id` = $id";
		return $this->mysql->runSql($sql);
	}


// 执行发送业务

	//发送错误到fd
	function sendError( $fd, $cmd, $code, $errno, $error=null, $data=array() )
	{
		if ( !$fd || !$cmd || !$errno || !is_array($data) ){
			gerr("发送错误失败 sendError($fd,$cmd,$code,$errno,$error,".json_encode($data) );
			return false;
		}
		$data['errno'] = $errno;
		$data['error'] = $error ? $error : "操作失败。";
		return $this->sendToFd($fd, $cmd, $code, $data);
	}

	//发送到牌桌所有用户，相同数据
	function sendToTable( $table, $cmd, $code, $data, $line=0 )
	{
		if ( !isset($table['seats']) || !$table['seats'] || !is_array($table['seats']) )
		{
			gerr("牌桌数据无效 sendToTable table=".json_encode($table));
			return false;
		}
		foreach ( $table['seats'] as $uid=>$sid ) {
			$player = array('fd'=>$table["seat{$sid}fd"], 'uid'=>$uid, 'tableId'=>$table['tableId']);
			$res = $this->sendToPlayer($player, $cmd, $code, $data,0);
		}
		return true;
	}

	//发送到牌桌某个用户，不同(相同)数据
	//is_save	当前发送的内容: 0不存历史+直接发送/1存入历史+可能缓发/2不存历史+可能缓发
	function sendToPlayer( $player, $cmd, $code, $data, $is_save=0 )
	{
		$fd = $player['fd'];//发送给牌桌用户的时候使用牌桌上的用户fd
		$uid = $player['uid'];
		$tableId = $player['tableId'];
		$data['log']['ud'] = $uid;
		$data['log']['td'] = $tableId;
		//添加到牌桌历史
		$is_save ==1 && $this->addTableHistory( $tableId, array('uid'=>$uid,'cmd'=>$cmd,'code'=>$code,'data'=>$data) );
		//掉线用户或机器人不发
		if ( !$fd ) { return false; }
		//不用存入历史的，绿色通道，直接发送
		if ( !$is_save ) { return $this->sendToFd($fd, $cmd, $code, $data); }
		//常规消息
		$fdinfo = getBind($fd);
		//阻塞状态，增加到缓发队列
		if ( $fdinfo && isset($fdinfo['is_lock']) && $fdinfo['is_lock'] )
		{	//return $index;
			return $this->addUserQueue( $uid, array('uid'=>$uid,'cmd'=>$cmd,'code'=>$code,'data'=>$data) );
		}
		//畅通状态，优先发送缓发数据
		//预留 这个地方需要优化，目前无优化方案
		while ( $queue = $this->popUserQueue($uid) )
		{
			$this->sendToFd($fd, $queue['cmd'], $queue['code'], $queue['data']);
		}
		//正式发送
		return $this->sendToFd($fd, $cmd, $code, $data);
	}

	//发送给不一定在牌桌的用户
	function sendToUser( $uid, $cmd, $code, $data )
	{
		if ( is_array($uid) && isset($uid['fd']) && isset($uid['uid']) && isset($uid['tableId']) ) {
			$user = $uid;
			$uid = intval($user['uid']);
			if ( $uid <= 0 ) return false;
		} elseif ( $uid > 0 ) {
			$user = $this->getUserInfo($uid);
			if ( !$user ) {
				return false;
			}
		} else {
			return false;
		}
		$fd = $user['fd'];	//常规发送使用用户信息里的fd
		$tableId = $user['tableId'];
		$data['log']['ud'] = $uid;
		$data['log']['td'] = $tableId;
		return $this->sendToFd($fd, $cmd, $code, $data);
	}

	//发送给FD
	function sendToFd( $fd, $cmd, $code, $data )
	{
		//暂不支持串型桌号、整型微信码、整型靓号
		if ( isset($data['check_code']) ) $data['check_code'] = strval($data['check_code']);
		if ( isset($data['cool_num']) ) $data['cool_num'] = strval($data['cool_num']);
		if ( isset($data['tableId']) ) $data['tableId'] = 1;
		if ( isset($data['seat0info']['tableId']) ) $data['seat0info']['tableId'] = 1;
		if ( isset($data['seat1info']['tableId']) ) $data['seat1info']['tableId'] = 1;
		if ( isset($data['seat2info']['tableId']) ) $data['seat2info']['tableId'] = 1;
		if ( isset($data['0']['tableId']) ) $data['0']['tableId'] = 1;
		if ( isset($data['1']['tableId']) ) $data['1']['tableId'] = 1;
		if ( isset($data['2']['tableId']) ) $data['2']['tableId'] = 1;
		return sendToFd($fd, $cmd, $code, $data);
	}

	//关闭连接uid
	function closeToUid( $uid, $data )
	{
		$user = $this->getUserInfo($uid);
		if ( $user && isset($user['fd']) && $user['fd'] )
		{
			$fd = $user['fd'];
			$info = getBind($fd);
			if ( $info && isset($info['uid']) && $info['uid'] == $uid ) {
				return closeToFd($fd, $data);
			}
		}
		gerr("[KILLUD][$uid] ".$data);
		$line = intval(str_replace('client-', '', $data));
		$this->desUserInfo($uid, $user, $line);
		return true;
	}


// 用户基础信息

	// 增减 原子操作
	function incUserInfo( $uid, $info, $type=0 )
	{
		$uid = intval($uid);
		if ( $uid <= 0 || !$info || !is_array($info) ) return false;
		foreach ( $info as $k => $v ) {
			$v = intval($v);
			if ( !$v ) continue;
			$res = incUser($uid, $k, $v);
			if ( $res===false ) return false;
			$info[$k] = $res;
			if ( $k == 'coins' && $v ) {
				$dateid = intval(date('Ymd'));
				$type = intval($type);
				$coins = $v;
				$after = $res;
				$date = date('Y-m-d H:i:s');
				$time = time();
				$sql = "INSERT INTO `lord_user_coinsrecord_$dateid` (`dateid`, `uid`, `type`, `coins`, `after`, `date`, `time`) VALUES ($dateid, $uid, $type, $coins, $after, '$date', $time)";
				$res = bobSql($sql);
			}
		}
		return $info;
	}
	// 入库
	function updUserInfo( $uid, $info=array() )
	{
		$uid = intval($uid);
		if ( $uid <= 0 || !is_array($info) ) return false;
		if ( !$info ) {
			$info = $this->getUserInfo($uid);
		}
		$info = is_array($info) && isset($info['sex']) && isset($info['age']) && isset($info['gold']) && isset($info['coins']) && isset($info['coupon']) && isset($info['lottery']) ? $info : array();
		$set = array();
		if (isset($info['sex'])) $set[]= " `sex`=".intval($info['sex']);
		if (isset($info['age'])) $set[]= " `age`=".intval($info['age']);
		if (isset($info['gold'])) $set[]= " `gold`=".intval($info['gold']);
		if (isset($info['coins'])) $set[]= " `coins`=".intval($info['coins']);
		if (isset($info['coupon'])) $set[]= " `coupon`=".intval($info['coupon']);
		if (isset($info['lottery'])) $set[]= " `lottery`=".intval($info['lottery']);
		$set = $set ? join(',', $set) : '';
		if ( $set ) {
			$sql = "UPDATE `lord_game_user` SET $set WHERE `uid` = $uid";
			bobSql($sql);
			return true;
		}
		return false;
	}
	// 获取用户昵称
	function getUserNick( $uid )
	{
		$info = $this->getUserInfo($uid);
		if ( $info && is_array($info) && isset($info['nick']) ) {
			return $info['nick'];
		} else {
			$sql = "SELECT `nick` FROM `lord_game_user` WHERE `uid` = $uid LIMIT 0,1";
			$nick = $this->mysql->getVar($sql);
		}
		if (!$nick) {
			$sql = "SELECT `nick` FROM `lord_game_robot` WHERE `uid` = $uid LIMIT 0,1";
			$nick = $this->mysql->getVar($sql);
		}
		$nick = $nick ? strval($nick) : ("新手".($uid+1234567));
		return $nick;
	}

	//销毁用户数据
	function desUserInfo( $uid, $a=array(), $line=0, $is_robot=0 )
	{
		if ( !$is_robot )
		{
			if ( $a && count($a) > 28 ) {
				$a['logout_coins'] = $a['coins'];
				$a['logout_gold'] = $a['gold'];
				$a['logout_time'] = time();
				$monthid = intval(date("Ym"));
				$sql = "INSERT INTO `lord_game_loginout_$monthid` (`dateid`,`uid`,`login_coins`,`login_gold`,`login_time`,`last_action`,`last_time`,`logout_coins`,`logout_gold`,`logout_time`,`online_time`) VALUES ";
				$sql.= " (".date("Ymd",$a['login_time']).",".$a['uid'].",".$a['login_coins'].",".$a['login_gold'].",'".date("H:i:s",$a['login_time'])."','".$a['last_action']."','".date("H:i:s",$a['last_time'])."',".$a['logout_coins'].",".$a['logout_gold'].",'".date("H:i:s",$a['logout_time'])."',".intval($a['logout_time']-$a['login_time']).")";
				bobSql($sql);
			}
			$this->updUserInfo($uid, $a);
			$this->updUserTask($uid);
			$this->updUserTesk($uid);
		}
		// glog("正常用户清理[$uid] ".json_encode($a));
		delUser($uid);
		$this->delUserTask($uid);
		$this->delUserTesk($uid);
		return true;
	}

	//销毁机器人
	function desRobot( $uid , $line=0 )
	{
		$sql = "UPDATE lord_game_robot SET state = 0 WHERE uid = $uid";
		bobSql($sql);
		// glog("机器人已清理[$uid] line=$line");
		delUser($uid);
	}

// 用户任务信息

	function getUserInfo( $uid )
	{
		$uid = intval($uid);
		if ( $uid < 1 ) return false;
		$user = getUser($uid);

		if ( $user && is_array($user) && isset($user['fd']) && isset($user['uid']) && isset($user['coins']) && isset($user['isRobot']) ) {  // && !(!$user['isRobot'] && count($user) < 29)
			return $user;
		} elseif ( $user ) {
			gerr("异常用户清理[$uid] ".json_encode($user));
			$this->delUserModel($uid);
			delUser($uid);
		}
		return false;
	}

	// 获取 默认在无redis数据时从数据库初始化数据
	function getUserTask( $uid, $isInit=1 )
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return array();
		$dateid = intval(date("Ymd"));
		$weekDay = date("N");//1-7
		$usertask = $this->redis->hgetall($this->key_user_task_.$uid);
		if (!$isInit) {
			return ($usertask && is_array($usertask)) ? $usertask : array();
		}
		if (!$usertask) 
		{
			$usertask = $this->mysql->getLine("SELECT * FROM `lord_user_task` WHERE `uid` = $uid");
			if (!$usertask) 
			{
				$res = $this->mysql->runSql("INSERT INTO `lord_user_task` ( `uid`, `dateid` ) VALUES ( $uid, ".intval(date("Ymd"))." )");
				$usertask = $this->mysql->getLine("SELECT * FROM `lord_user_task` WHERE `uid` = $uid");
			}
			if ($usertask && is_array($usertask)) 
			{
				$usertask['dateid'] = $dateid;
				foreach ( $usertask as $k => $v ) {
					$usertask[$k] = intval($v);
				}
				$this->redis->hmset($this->key_user_task_.$uid,$usertask);
			}
		}
		else
		{
			$olddateid = $usertask['login_this_dateid'];
			$is_newday = intval($olddateid!=$dateid);
			$is_neweek = intval($is_newday && !($olddateid>=($dateid-($weekDay-1)) && $olddateid<=($dateid+(7-$weekDay))));
			if ($is_newday) 
			{
				$newu['lottery'] = 0;//重设抽奖次数
				$res = setUser($uid,$newu);
				$newut['login_day_times'] = 0;
				$newut['gold_day'] = 0;
				$newut['coupon_day'] = 0;
				$newut['coins_day'] = 0;
				$newut['normal_day_play'] = 0;
				$newut['normal_day_win'] = 0;
				$newut['normal_day_earn'] = 0;
				$newut['normal_day_maxrate'] = 0;
				$newut['normal_day_maxearn'] = 0;
				$newut['match_day_play'] = 0;
				$newut['match_day_point'] = 0;
				$newut['lottery_day_times'] = 0;
				if ($is_neweek) 
				{
					$newut['gold_week'] = 0;
					$newut['coupon_week'] = 0;
					$newut['coins_week'] = 0;
					$newut['normal_week_play'] = 0;
					$newut['normal_week_win'] = 0;
					$newut['normal_week_earn'] = 0;
					$newut['normal_week_maxrate'] = 0;
					$newut['normal_week_maxearn'] = 0;
					$newut['match_week_play'] = 0;
					$newut['match_week_point'] = 0;
					$newut['lottery_week_times'] = 0;
				}
				$res = $this->setUserTask($uid,$newut);
				$usertask = array_merge($usertask,$newut);
			}
		}
		return ($usertask && is_array($usertask)) ? $usertask : array();
	}
	// 设置
	function setUserTask( $uid, $info )
	{
		$uid = intval($uid);
		if ( $uid <= 0 || !$info || !is_array($info) ) return false;
		return $this->redis->hmset($this->key_user_task_.$uid,$info);
	}
	// 加减
	function incUserTask( $uid, $info )
	{
		$uid = intval($uid);
		if ( $uid <= 0 || !$info || !is_array($info) ) return false;
		foreach ( $info as $k => $v ) {
			$res = $this->redis->hincrby($this->key_user_task_.$uid, $k, intval($v));
			if ($res===false) return false;
			$info[$k] = $res;
		}
		return $info;
	}
	// 入库
	function updUserTask( $uid, $info=array() )
	{
		$uid = intval($uid);
		if ( $uid <= 0 || !is_array($info) ) return false;
		if ( !$info ) $info = $this->getUserTask($uid, 0);
		if ($info && isset($info['uid'])) unset($info['uid']);
		$info = ( is_array($info) && count($info) ) ? $info : array();
		if ( !$info ) return false;
		$sql_ = array();
		foreach ( $info as $k => $v ) {
			$sql_[]= " `$k`=".intval($v);
		}
		$sql_ = $sql_ ? join(',', $sql_) : '';
		if ($sql_) return bobSql("UPDATE `lord_user_task` SET $sql_ WHERE `uid` = $uid");
		return false;
	}
	// 删除
	function delUserTask( $uid )
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return false;
		return $this->redis->del($this->key_user_task_.$uid);
	}
	// 入库
	function updUserTesk( $uid )
	{
		$key = 'lord_user_tesk_'.$uid;
		$tesk = $this->redis->hgetall($key);
		if ( !$tesk ) return false;
		$sql = "REPLACE INTO `lord_user_tesk` ( `uid`, `teskCode`, `update_time` ) VALUES ( $uid, '".addslashes(json_encode($tesk))."', ".time()." )";
		$res = bobSql($sql);
		return $this->delUserTesk($uid);
	}
	// 删除
	function delUserTesk( $uid )
	{
		// return $this->redis->del('lord_user_tesk_'.$uid);
	}

// 用户赛事信息

	// 获取
	function getUserModel( $uid )
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return false;
		return $this->redis->hgetall($this->key_user_model_.$uid);
	}
	// 设置
	function setUserModel( $uid, $info )
	{
		$uid = intval($uid);
		if ( $uid <= 0 || !$info || !is_array($info) ) return false;
		return $this->redis->hmset($this->key_user_model_.$uid,$info);
	}
	// 删除
	function delUserModel( $uid )
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return false;
		return $this->redis->del($this->key_user_model_.$uid);
	}

	//校验用户信息
	function checkUserInDb( $data )
	{
		if( !$data || !is_array($data) || !$data['open_id'] )
		{
			return 11;//设备号为空
		}
		$sql = "SELECT `uid` FROM `user_login` WHERE `open_type`='".$data['open_type']."' AND `open_id`='".$data['open_id']."' AND `extend`='".$data['extend']."'";
		$uid = $this->mysql->getVar($sql);
		if(!$uid)
		{
			gerr(__FUNCTION__.' '.$sql);
			return 13;//用户不存在
		}
		$sql = "SELECT `id`, `password` FROM `user_user` WHERE `id` = $uid";
		$res = $this->mysql->getLine($sql);
		if(!$res)
		{
			gerr(__FUNCTION__.' '.$sql);
			return 13;//用户不存在
		}
		if($res['password'] != $data['password'])
		{
			DEBUG && debug(__FUNCTION__.' res='.$res['password'].'<>req='.$data['password'].' '.$sql);
			return 15;//密码错误
		}
		return $res;
	}
	//从数据库获取用户信息
	function getUserInDb( $uid, $params=array() )
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return 15;
		$GAME = include(ROOT.'/conf/games.php');
		$_sql = "SELECT `uid`, `cool_num`, `nick`, `sex`, `age`, `word`, `gold`, `coins`, `coupon`, `lottery`, `level`, `exp`, `avatar`, `check_code`, `channel` FROM `lord_game_user` WHERE `uid` = $uid";
		$info = $this->mysql->getLine($_sql);
		$is_noob = 0;
		if ( !$info && $params && is_array($params) )
		{
			//尝试串号数据拷贝 待续
			$is_noob = 1;
			$channel = $params['channel'];
			$cool_num = $uid + 1234567;//新的靓号生成机制
			$nick = $GAME['user_nick_prev'].$cool_num;
			$GAME['user_noob_coins'] = 1000;//2015-02-10 14:10:00 新人乐豆调整
			$s_noob = $this->s_noob;
			$s_trial = $this->s_trial;
			$coins = (isset($s_noob[$channel]) ? $s_noob[$channel] : $GAME['user_noob_coins']) * ( $params['isRobot'] ? 100 : 1 );//新人乐豆
			$check_code = mt_rand(1000,9999);
			$sql = array();
			$sql[] = "INSERT INTO `lord_game_user` (`uid`, `cool_num`, `nick`, `coins`, `check_code`, `channel`) VALUES ('".$uid."','".$cool_num."','".$nick."', ".$coins.",'".$check_code."','".$params['channel']."')";
			$sql[] = "INSERT INTO `lord_game_analyse` (`uid`,`device`,`extend`,`add_time`,`ip`,`version`) VALUES ('".$uid."','".$params['device']."','".$params['extend']."','".$params['add_time']."','".$params['ip']."','".$params['version']."')";
			$flag = true;
			$this->mysql->runSql('begin');
			foreach( $sql as $v )
			{
				if ( !$this->mysql->runSql($v) ) {
					// echo $sql."\n";
					$flag = false;
					$sql_ = $v;
					break;
				}
			}
			if ( !$flag ) {
				$this->mysql->runSql('rollback');
				return 15;//建号失败==密码错误
			}
			$this->mysql->runSql('commit');
			$info = $this->mysql->getLine($_sql);
		}
		if ( !$info ) return 15;//查询失败==密码错误
		$info['is_noob'] = $is_noob;
		return $info;
	}
	// 机器人
	function getDbRobotInfo( $uid )
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return array();
		$sql = "SELECT `uid`, `cool_num`, `nick`, `sex`, `word`, `gold`, `coins`, `coupon`, `lottery`, `level`, `exp`, `avatar`, `check_code`, `channel` FROM `lord_game_robot` WHERE `uid` = $uid";
		$info = $this->mysql->getLine($sql);
		return ( $info && is_array($info) ) ? $info : array();
	}
	//从数据库获取用户道具信息
	function getDbUserDress( $uid )
	{
		$data = array('1'=>1);
		$uid = intval($uid);
		if ( $uid <= 0 ) return $data;
		$sql = "SELECT * FROM `lord_game_userprop` WHERE `uid` = $uid AND `categoryId` = 1";
		$props = $this->mysql->getData($sql);
		if ( !$props || !is_array($props) ) return $data;
		$data = array('1'=>0);
		foreach ( $props as $k=>$v ) {
			if ( $v['propState'] > 1 || ($v['propEnd'] > 0 && $v['propEnd'] < time()) ) {
				$sql = "DELETE FROM `lord_game_userprop` WHERE `id` = ".$v['id'];
				bobSql($sql);
				continue;
			}
			$data[$v['propId']] = intval($v['propState']);
		}
		!in_array(1, $data) && $data['1'] = 1;
		return $data;
	}
	//从数据库获取用户道具信息
	function getDbUserItems($uid)
	{
		$data = array();
		$uid = intval($uid);
		if ( $uid <= 0 ) return $data;
		$sql = "SELECT * FROM `lord_game_userprop` WHERE `uid` = $uid AND `categoryId` > 1";
		$props = $this->mysql->getData($sql);
		if ( !$props || !is_array($props) ) return $data;
		foreach ( $props as $k=>$v )
		{
			if ( $v['propState'] > 1 || $v['num'] < 1 ) {
				$sql = "DELETE FROM `lord_game_userprop` WHERE `id` = ".$v['id'];
				bobSql($sql);
				continue;
			}
			$data[$v['propId']]['id'] = $v['id'];
			$data[$v['propId']]['propId'] = $v['propId'];
			$data[$v['propId']]['num'] = isset($data[$v['propId']]['num']) ? ($data[$v['propId']]['num']+$num) : $num;
			$data[$v['propId']]['state'] = intval($v['propState']);
		}
		return $data;
	}
	//到数据库设定用户道具穿戴信息
	function setDbUserDress( $uid, $propId )
	{
		$uid = intval($uid);
		$propId = intval($propId);
		if ( $uid <= 0 || $propId <= 0 ) return false;
		if ( $propId == 1 ) return true;
		$sql = "SELECT * FROM `lord_game_userprop` WHERE `uid` = $uid AND `propId` = $propId";
		$prop = $this->mysql->getLine($sql);
		if ( !$prop || !is_array($prop) || $prop['categoryId'] != 1 || $prop['propState'] > 1 ) return false;
		$sql = "UPDATE `lord_game_userprop` SET `propState` = 0 WHERE `categoryId` = 1 AND `uid` = $uid AND `propState` = 1";
		bobSql($sql);
		$id = $prop['id'];
		$sql = "UPDATE `lord_game_userprop` SET `propState` = 1 WHERE `id` = $id";
		bobSql($sql);
		return true;
	}
	//存储用户滞留信息
	function insUserMsg( $uid, $data )
	{
		$uid = (int)$uid;
		$data = (array)$data;
		if ( $uid <= 0 || !$data ) return false;
		$sql = "INSERT INTO `lord_game_usermsg` ( `uid`, `msg` ) VALUES ( $uid, '".addslashes(json_encode($data))."' )";
		return bobSql($sql);
	}
	//重发用户滞留消息
	function exeUserMsg( $uid, $game )
	{
		$uid = (int)$uid;
		if ( $uid <= 0 ) return false;
		$sql = "SELECT * FROM `lord_game_usermsg` where `uid` = $uid";
		$list = $this->mysql->getData($sql);
		if (!$list || !is_array($list)) $list = array();
		foreach ( $list as $k=>$v )
		{
			$data = json_decode($v['msg'],1);//act/cmd/code/...
			$data['uid'] = $uid;
			$sceneId = 'USR_MSG_'.$uid;
			$act = $data['act']; unset($data['act']);
			$params = $data;
			$delay = $game['time_'.strtolower($act)] * 1000;
			setTimer($sceneId, $act, $params, $delay);
			// $act = $data['act']; unset($data['act']);
			// $params = $data;
			// $delay = $game['time_'.strtolower($act)] * 1000;
			// setEvent($act, $params, $delay);
		}
		$list && bobSql("DELETE FROM `lord_game_usermsg` where `uid` = $uid");
		return true;
	}
	//执行用户奖项写入（服装除外）
	function exeUserPrize( $uid, $prize )
	{
		if ( !$uid || !$prize || !is_array($prize) )
		{
			return false;
		}
		!isset($prize['gold']) && $prize['gold'] = 0;
		!isset($prize['coupon']) && $prize['coupon'] = 0;
		!isset($prize['lottery']) && $prize['lottery'] = 0;
		!isset($prize['props']) && $prize['props'] = array();
		$user = $this->getUserInfo($uid);
		if ( $user && isset($user['coins']) && isset($user['gold']) && isset($user['propDress']) )
		{
			$adding = array();
			$table_coinsadd = 0;
			if ($prize['gold']) {
				$adding['gold'] = $prize['gold'];
			}
			if ($prize['coins']) {
				$adding['coins'] = $prize['coins'];
				$table_coinsadd = $prize['coins'];
			}
			if ($prize['coupon']) {
				$adding['coupon'] = $prize['coupon'];
			}
			if ($prize['lottery']) {
				$adding['lottery'] = $prize['lottery'];
			}
			$prizeDress = $prize['props'];
			if ( $prizeDress )
			{
				$newu['propDress'] = $user['propDress'];
				foreach ( $prizeDress as $propId=>$name )
				{
					if ( !isset($newu['propDress'][$propId]) )
					{
						$newu['propDress'][$propId] = 0;
					}
				}
				if ( isset($newu['propDress']) && !in_array(1,$newu['propDress']) )
				{
					$newu['propDress']["1"] = 1;
				}
				$res = setUser($uid, $newu);
			}
			if ($user['tableId'] && $table_coinsadd) {
				//加事务锁
				$lockId = $tableId = $user['tableId'];
				$res = setLock($lockId);
				$seatId = $user['seatId'];
				$table = $this->getTableInfo($tableId);
				if ( $table && isset($table["seat{$seatId}coins"]) ) {
					$addT["seat{$seatId}coins"] = $table_coinsadd;
					$addT && $this->incTableInfo($tableId, $addT);
				}
				$adding && $this->incUserInfo($uid, $adding, 12);//12竞技发奖
				$res = delLock($lockId);
			}
			else{
				$adding && $this->incUserInfo($uid, $adding, 12);//12竞技发奖
			}
			return true;
		}
		else {
			$sql = "UPDATE `lord_game_user` SET `gold` = `gold` + ".$prize['gold'].", `coins` = `coins` + ".$prize['coins'].", `coupon` = `coupon` + ".$prize['coupon'].", `lottery` = `lottery` + ".$prize['lottery']." WHERE `uid` = $uid";
			return bobSql($sql);
		}
	}
	//执行用户奖励写入（含服装、道具）
	function addUserPrize( $uid, $prize, $user=array(), $type=0 )
	{
		if ( !$uid || !$prize || !is_array($prize) )
		{
			return false;
		}
		$prize_ = $prize;
		if(!isset($prize['gold'])) $prize['gold'] = 0;
		if(!isset($prize['coins'])) $prize['coins'] = 0;
		if(!isset($prize['coupon'])) $prize['coupon'] = 0;
		if(!isset($prize['lottery'])) $prize['lottery'] = 0;
		if(!isset($prize['propItems'])) $prize['propItems'] = array();
		if(isset($prize['props'])) $prize['propItems'] = array_merge($prize['propItems'],$prize['props']);
		if ( $user && isset($user['gold']) && isset($user['coins']) && isset($user['coupon']) && isset($user['lottery']) && isset($user['propDress']) && isset($user['propItems']) )
		{
			$adding = array();
			$table_coinsadd = 0;
			if ($prize['gold']) {
				$adding['gold'] = $prize['gold'];
			}
			if ($prize['coins']) {
				$adding['coins'] = $table_coinsadd = $prize['coins'];
			}
			if ($prize['coupon']) {
				$adding['coupon'] = $prize['coupon'];
			}
			if ($prize['lottery']) {
				$adding['lottery'] = $prize['lottery'];
			}
			$props = $prize['propItems'];//结构与其他竞技场时使用的不一样,这里是id=>num
			if ( $props )
			{
				$newu['propDress'] = $user['propDress'];
				$newu['propItems'] = $user['propItems'];
				foreach ( $props as $k=>$v )
				{
					$id = $v['id'];
					$num = $v['num'];
					$categoryId = isset($v['categoryId']) ? intval($v['categoryId']) : 0;
					if ( $categoryId == 1 ) {	//写入服装道具，叠加无效
						if ( !isset($newu['propDress'][$id]) ) {
							$newu['propDress'][$id] = 0;
							$res = $this->addDbUserDress(array($uid=>array($id=>(isset($v['ext'])?$v['ext']:0)*($v['num']>0?$v['num']:1))));
						}
					} else {					//写入其他道具，叠加有效
						$newu['propItems'][$id]['num'] = isset($newu['propItems'][$id]['num']) ? ($newu['propItems'][$id]['num'] + $num) : $num;
						$res = $this->addDbUserItems(array($uid=>array($id=>$num)));
					}
				}
				if ( !in_array(1,$newu['propDress']) )
				{
					$newu['propDress']["1"] = 1;
				}
				$res = setUser($uid, $newu);
			}
			if ( $user['tableId'] && $table_coinsadd ) {
				//加事务锁
				$lockId = $tableId = $user['tableId'];
				$res = setLock($lockId);
				$seatId = $user['seatId'];
				$table = $this->getTableInfo($tableId);
				if ( $table && isset($table["seat{$seatId}coins"]) ) {
					$addT["seat{$seatId}coins"] = $table_coinsadd;
					$addT && $this->incTableInfo($tableId, $addT);
				}
				$adding && $this->incUserInfo($uid, $adding, $type);
				$res = delLock($lockId);
			}
			else{
				$adding && $this->incUserInfo($uid, $adding, $type);
			}
		}
		else 
		{
			if ( $prize['propItems'] )
			{
				foreach ( $prize['propItems'] as $k=>$v )
				{
					$id = $v['id'];
					$num = $v['num'];
					$categoryId = isset($v['categoryId']) ? intval($v['categoryId']) : 0;
					if ( $categoryId == 1 )
					{	//写入服装道具，叠加无效
						$res = $this->addDbUserDress(array($uid=>array($id=>(isset($v['ext'])?$v['ext']:0)*($v['num']>0?$v['num']:1))));
					} else {	//写入其他道具，叠加有效
						$res = $this->addDbUserItems(array($uid=>array($id=>$num)));
					}
				}
			}
			$sql = "UPDATE `lord_game_user` SET `gold` = `gold` + ".$prize['gold'].", `coins` = `coins` + ".$prize['coins'].", `coupon` = `coupon` + ".$prize['coupon'].", `lottery` = `lottery` + ".$prize['lottery']." WHERE `uid` = $uid";
			$res = bobSql($sql);
		}
		return $prize_;
	}

	//校验用户乐豆，用户是否可以发放补助的乐豆 返回发豆相关数据
	function checkUserCoins( $uid, $user=array() )
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return array();
		$user = $user ? $user : $this->getUserInfo($uid);
		if ( !$user || !is_array($user) || count($user) < 29 ) return array();
		$fd = $user['fd'];
		//特殊渠道 使用改动后的补豆数据
		$GAME = include(ROOT.'/conf/games.php');
		$s_noob = $this->s_noob;
		$s_trial = $this->s_trial;
		$user_trial_count = $GAME['user_trial_count'];
		$user_trial_daily = $GAME['user_trial_daily'];
		if ( isset($user['channel']) && isset($s_trial[$user['channel']]) ) 
		{
			$rate = intval($s_trial[$user['channel']] / $user_trial_count);
			$rate = max($rate,1);
			$user_trial_count *= $rate;
			$user_trial_daily *= $rate;
		}
		$user_trial_daily = 12000;//shawn 20150803 每日救济乐豆总量扩大到12000;
		if ( $user['coins'] < $GAME['user_trial_count'] && $user['trial_daily'] < $user_trial_daily )
		{
			$newU['trial_count'] = $user['trial_count'] = $user_trial_count;
			$addU['trial_daily'] = $user_trial_count;
			$user['trial_daily'] += $user_trial_count;
			$addU['coins'] = $user_trial_count;
			$user['coins'] += $user_trial_count;
			if ( $fd ) {
				$addU && $this->incUserInfo($uid, $addU, 2);//2补豆
				$newU && setUser($uid, $newU);
			} else {
				$sql = "UPDATE `lord_game_user` SET `coins` = ".$user['coins']."  WHERE uid = $uid";
				bobSql($sql);
			}
			$sql = "UPDATE `lord_game_analyse` SET `trial_count` = ".$user['trial_count'].", `trial_daily` = `trial_daily` + $user_trial_count WHERE uid = $uid";
			bobSql($sql);
			$data['isSend'] = 1;
			$data['sendCoins'] = $user_trial_count;
		}
		else
		{
			$data['isSend'] = 0;
			$data['sendCoins'] = 0;
		}
		$data['sendCoinsTimesToday'] = intval($user['trial_daily']/$user_trial_count);//今日次数
		$data['sendCoinsTimes'] = intval($user_trial_daily/$user_trial_count);//总次数
		if ( $fd ) {	//在线用户 通知牌桌: 补豆，不管是否需要
			$cmd  = 5;
			$code = 1024;
			$this->sendToFd($fd, $cmd, $code, $data);
		}
		$user = array_merge($user,$data);
		return $user;
	}

	// 获取用户抽奖记录
	function getUserLottery( $uid )
	{
		$uid = max(intval($uid),0);
		$dateid = intval(date("Ymd"));
		$ut_now = time();
		$ut_last = $ut_now - 7 * 86400;//一周内
		$dt_now = date("Y-m-d H:i:s");
		$list = $this->redis->get($this->key_user_lottery_.$uid);
		if ($list) {
			unset($list['0']);
			return $list ? array_values($list) : array();
		}
		$data_lottery_prizes = array();
		include(ROOT.'/include/data_lottery_prizes.php');
		$prizes = $data_lottery_prizes;
		$res = $this->mysql->getData("SELECT * FROM `lord_user_lotteryrecord` WHERE `uid` = $uid AND `ut_create` > $ut_last");
		$list = array();
		$res = ( $res && is_array($res) ) ? $res : array();
		foreach ( $res as $k => $v )
		{
			if (!isset($prizes[$v['prizeid']])) continue;
			$name = $prizes[$v['prizeid']]['name'];
			$list[$v['ut_create']] = array('id'=>intval($v['id']), 'name'=>$name, 'datetime'=>date("Y-m-d H:i:s",$v['ut_create']), 'ut_create'=>$v['ut_create']);
		}
		if ($list) krsort($list);
		$list = array_values(array_merge(array('0'=>array('id'=>0, 'name'=>'刷新时间', 'datetime'=>$dt_now, 'ut_create'=>$ut_now)),$list));
		$res = $this->redis->set($this->key_user_lottery_.$uid,$list);
		unset($list['0']);
		return $list ? array_values($list) : array();
	}

	// 记录用户抽奖历史
	function addUserLottery( $user, $prize )
	{
		$uid = max(intval($user['uid']),0);
		$dateid = intval(date("Ymd"));
		$ut_now = time();
		$ut_last = $ut_now - 7 * 86400;//一周内
		$dt_now = date("Y-m-d H:i:s");
		$sql = "INSERT INTO `lord_user_lotteryrecord` "
			." ( `dateid`, `uid`, `cool_num`, `nick`, `prizeid`, `cateid`, `gold`, `coins`, `coupon`, `propid`, `ut_create` ) VALUES "
			." ( $dateid, $uid, ".$user['cool_num'].", '".mysqli_real_escape_string($this->mysql->db,$user['nick'])."', ".$prize['id'].", ".$prize['cateid'].", ".$prize['gold'].", ".$prize['coins'].", ".$prize['coupon'].", ".$prize['propid'].", $ut_now )";
		bobSql($sql);
		$id = intval($this->mysql->lastId());
		$list = $this->getUserLottery($uid);
		$list = array_values(array_merge(array('0'=>array('id'=>0, 'name'=>'刷新时间', 'datetime'=>$dt_now, 'ut_create'=>$ut_now),'1'=>array('id'=>$id, 'name'=>$prize['name'], 'datetime'=>$dt_now, 'ut_create'=>$ut_now)),$list));
		foreach ( $list as $k => $v ) {
			if ($v['ut_create'] < $ut_last) unset($list[$k]);
		}
		$list = $list ? array_values($list) : array();
		$res = $this->redis->set($this->key_user_lottery_.$uid,$list);
		return $res;
	}


	//新版-用户加入房间队列
	function addRoomPlayer( $user, $hostId=0 )
	{
		if ( !$user || !is_array($user) )
		{
			return false;
		}
		$roomId = $user['roomId'];
		$MODELS = include(ROOT.'/conf/rooms.php');
		if ( !in_array($roomId,array_keys($MODELS['0'])) )
		{
			return false;
		}
		$res = $this->redis->ladd($this->key_room_player_.($hostId?$hostId:HOSTID).'_'.$roomId,$user);
		if ( $res !== -1 )
		{
			return $res;
		}
		return false;
	}
	//新版-尽可能获取房间队列的?个用户(默认3个)
	function getRoomPlayer($roomId, $num=3, $hosts=array())
	{
		$MODELS = include(ROOT.'/conf/rooms.php');
		if ( !in_array($roomId, array_keys($MODELS['0'])) || !is_array($hosts) ) return false;
		$num = intval($num);
		$player = array();
		if ( $hosts )
		{
			foreach ( $hosts as $k => $hostId )
			{
				while ( $num && ($user = $this->redis->lpop($this->key_room_player_.$hostId.'_'.$roomId)) )
				{
					$oldu = $user;
					$uid = $user['uid'];
					$user = $this->getUserInfo($uid);
					if ( $user ) {
						$oldu['fd'] = $user['fd'];
						$oldu['coins'] = $user['coins'];
						$player[$uid] = $oldu;
						$num--;
					}
				}
			}
		}
		else
		{
			while ( $num && ($user = $this->redis->lpop($this->key_room_player_.HOSTID.'_'.$roomId)) )
			{
					$oldu = $user;
					$uid = $user['uid'];
					$user = $this->getUserInfo($uid);
					if ( $user ) {
						$oldu['fd'] = $user['fd'];
						$oldu['coins'] = $user['coins'];
						$player[$uid] = $oldu;
						$num--;
					}
			}
		}
		return $player;
	}
	//获取房间凑桌队列数
	function countRoomPlayer($roomId, $hosts=array())
	{
		$MODELS = include(ROOT.'/conf/rooms.php');
		if ( !in_array($roomId,array_keys($MODELS['0'])) || !is_array($hosts) )
		{
			return false;
		}
		$num = 0;
		if ( $hosts )
		{
			foreach ( $hosts as $k => $hostId )
			{
				$num += $this->redis->llen($this->key_room_player_.$hostId.'_'.$roomId);
			}
		}
		else
		{
			$num = $this->redis->llen($this->key_room_player_.HOSTID.'_'.$roomId);
		}
		return $num;
	}
	//从数据库获取用户统计信息
	function getDbUserAnalyseByUid($uid)
	{
		$uid = intval($uid);
		if ( $uid <= 0 ) return false;
		return $this->mysql->getLine("SELECT * FROM `lord_game_analyse` WHERE uid = ".$uid);
	}
	//追加牌桌信息队列
	function addTableHistory($tableId, $data)
	{
		if ( !$tableId || !$data || !is_array($data) ) return false;
		//$data = array('uid'=>$uid,'cmd'=>$cmd,'code'=>$code,'data'=>$data);
		$index = $this->redis->ladd($this->key_table_history_.$tableId, json_encode($data));
		if ( $index === -1 ) return false;
		return $index;
	}
	//获取牌桌信息队列
	function getTableHistory($tableId,$start,$end)
	{
		if ( !$tableId || !is_int($start) || !is_int($end) ) return false;
		return $this->redis->labc($this->key_table_history_.$tableId,$start,$end);
	}
	//删除牌桌信息队列
	function delTableHistory($tableId)
	{
		if ( !$tableId ) return false;
		return $this->redis->del($this->key_table_history_.$tableId);
	}
	//初始化牌桌信息
	function iniTableInfo($roomId, $players, $modelId=0,$roomMock=0,$weekId=0,$gameId=0)
	{

		$MODELS = include(ROOT.'/conf/rooms.php');
		if ( !in_array($roomId,array_keys($MODELS[$modelId])) || !$players || !is_array($players) || count($players) != 3 )
		{
			return false;
		}

		$roomConf = $MODELS[$modelId][$roomId];
		$table = $this->ini_table;
		$tableId = $roomId.'_'.join('_',array_keys($players));
		$res = $this->delTableHistory($tableId);
		$res = delTimer($tableId);
		$table['modelId'] = $modelId;
		$table['roomId'] = $roomId;
		$table['roomMock'] = $roomMock;
		$table['weekId'] = $weekId;
		$table['gameId'] = $gameId;
		$table['gamesId'] = $gameId ? ($modelId.'_'.$roomMock.'_'.$weekId.'_'.$gameId) : '';
		$table['tableId'] = $tableId;
		$table['rate'] = $roomConf['rate'];
		$table['rateMax'] = $roomConf['rateMax'];
		$table['rake'] = $roomConf['rake'];
		$table['baseCoins']  = $roomConf['baseCoins'];
		$table['limitCoins'] = $roomConf['limitCoins'];
		$table['lastSurprise'] = 0;
		$table['create'] = $table['update'] = microtime(1);

		foreach ( $players as $uid=>$user )
		{
			$user_ = $this->getUserInfo($uid);
			var_dump($user_);

			if ( $user_ && isset($user_['fd'])  ) {   // && $user_['fd']
				$user = array_merge($user, $user_, array('isShowcard'=>false));
				$fd = $user_['fd'];

				echo "--------------------------------------\n";
				var_dump($user_);
			} else {
				$user['fd'] = $fd = 0;
			}
			if ( !isset($user['wid']) ) $user['wid'] = false;
			if ( !isset($user['giveup']) ) $user['giveup'] = 0;
			if ( !isset($user['isRobot']) ) $user['isRobot'] = 0;
			if ( !isset($user['isShowcard']) ) $user['isShowcard'] = 0;
			if ( !$table['hostId'] && $fd )
			{
				$k_ = explode('_', $fd);
				$hostId = $k_[0]."_".$k_[1];
				$table['hostId'] = $hostId;
				$table['wid'] = $user['wid'];
			}
			$players[$uid] = $user;
		}
		if ( !$table['hostId'] ) {
			$table['hostId'] = HOSTID;
			$table['wid'] = false;
		}
		$i = 0;
		foreach ( $players as $uid=>$user )
		{
			$table['firstShow'] = ($table['firstShow'] == 4 && $user['isShowcard']) ? $i : $table['firstShow'];
			$table['seats'][$uid] = $i;
			$table['seat'.$i.'info'] = $user;
			$new['modelId'] = $table['seat'.$i.'info']['modelId'] = $modelId;
			$new['weekId'] = $table['seat'.$i.'info']['weekId'] = $weekId;
			$new['gameId'] = $table['seat'.$i.'info']['gameId'] = $gameId;
			$new['gamesId'] = $table['seat'.$i.'info']['gamesId'] = $table['gamesId'];
			$new['roomId'] = $table['seat'.$i.'info']['roomId'] = $roomId;
			$new['tableId'] = $table['seat'.$i.'info']['tableId'] = $tableId;
			$new['seatId'] = $table['seat'.$i.'info']['seatId'] = $i;
			$new['wid'] = $user['wid'];
			$new['twid'] = $table['wid'];
			$new['giveup'] = $user['giveup'];
			$new['lastSurprise'] = 0;
			$table['seat'.$i.'robot']= $user['isRobot'];	//用户，基础识别
			$table['seat'.$i.'uid'] = $uid;					//编号，基础识别
			$table['seat'.$i.'fd'] = $user['fd'];			//通道，需要变化
			$table['seat'.$i.'coins'] = $user['coins'];		//筹码，经常变化
			$table['seat'.$i.'score'] = $user['score'];		//赛币，需要变化
			$table['seat'.$i.'show'] = $user['isShowcard'];	//明牌，需要变化
			$table['seat'.$i.'giveup'] = $user['giveup'];	//弃赛，需要变化
			$table['seat'.$i.'state'] = 17;					//状态，经常变化，17:SYS开始发牌
			$res = setUser($uid, $new);
			$i++;
		}
		$table['turnSeat'] = $table['firstShow'];
		$res = $this->setTableInfo($tableId, $table);
		return $table;
	}
	//设置牌桌信息
	function setTableInfo( $tableId, $info )
	{
		if ( !$tableId || !$info || !is_array($info) ) return false;
		return $this->redis->hmset($this->key_table_info_.$tableId,$info);
	}
	//获取全部牌桌信息
	function getTableInfo( $tableId )
	{
		if ( !$tableId ) return false;
		$table = $this->redis->hgetall($this->key_table_info_.$tableId);
		if ( !$table ) {
			return false;
		} elseif ( count($table) < 66 ) {
			gerr("异常牌桌清理[$tableId] ".json_encode($table));
			$res = $this->delTableInfo($tableId);
			$res = delTimer($tableId, isset($table['hostId']) ? $table['hostId'] : 0);
			$res = $this->delTableHistory($tableId);
			return false;
		}
		return $table;
	}
	// 增减 原子操作
	function incTableInfo( $tableId, $info )
	{
		if ( !$tableId || !$info || !is_array($info) ) return false;
		foreach ( $info as $k => $v ) {
			$res = $this->redis->hincrby($this->key_table_info_.$tableId, $k, intval($v));
			if ($res===false) return false;
			$info[$k] = $res;
		}
		return $info;
	}
	//删除全部牌桌信息
	function delTableInfo( $tableId )
	{
		if ( !$tableId ) return false;
		return $this->redis->del($this->key_table_info_.$tableId);
	}
	//更新桌子状态
	function setTableState( $tableId, $state )
	{
		if ( !$tableId ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'state', $state);
	}
	//设定牌桌地主位
	function setTableLordto( $tableId, $seatId )
	{
		if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'lordSeat',$seatId);
	}
	//更新牌桌轮权
	function setTableTurnto( $tableId, $seatId )
	{
		if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'turnSeat',$seatId);
	}
	//获取牌桌轮权
	function getTableTurnto( $tableId )
	{
		if ( !$tableId ) return false;
		return $this->redis->hget($this->key_table_info_.$tableId,'turnSeat');
	}
	//设置牌桌首个明牌席位
	function setTableFirstShow( $tableId, $seatId )
	{
		if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'firstShow',$seatId);
	}
	//获取牌桌首个明牌席位
	function getTableFirstShow( $tableId )
	{
		if ( !$tableId ) return false;
		return $this->redis->hget($this->key_table_info_.$tableId,'firstShow');
	}
	//设置牌桌倍率
	function setTableRate( $tableId, $rate )
	{
		if ( !$tableId ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'rate',$rate);
	}
	//设置牌桌叫抢临时倍率
	function setTableRate_( $tableId, $rate )
	{
		if ( !$tableId ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'rate_',$rate);
	}
	//更新座位明牌状态
	function setSeatShow( $tableId, $seatId )
	{
		if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'seat'.$seatId.'show',1);
	}
	//更新座位用户状态
	function setSeatState( $tableId, $seatId, $state )
	{
		if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'seat'.$seatId.'state',$state);
	}
	//更新座位用户手牌
	function setSeatCards($tableId,$seatId,$cards)
	{
		if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'seat'.$seatId.'cards',$cards);
	}
	//更新座位用户抢叫地主的倍率
	function setSeatRate($tableId,$seatId,$rate)
	{
		if ( !$tableId || !in_array($seatId,range(0,2)) ) return false;
		return $this->redis->hset($this->key_table_info_.$tableId,'seat'.$seatId.'rate',$rate);
	}

	//处理某人准备再来一局，是否是明牌准备
	function setSeatReady( $table, $seatId, $isShowcard )
	{
		if ( !$table || !is_array($table) || !in_array($seatId,range(0,2)) ) return false;
		// $MODELS = include(ROOT.'/conf/rooms.php');
		// $GAME = include(ROOT.'/conf/games.php');
		$roomId = $table['roomId'];
		$tableId = $table['tableId'];
		$newT["seat{$seatId}state"] = $table["seat{$seatId}state"] = 16;//已经准备
		if ( $isShowcard )
		{
			$newT["seat{$seatId}show"] = $table["seat{$seatId}show"] = 1;//明牌开始
			if ( $table['firstShow'] == 4 )
			{
				$newT['firstShow'] = $table['firstShow'] = $seatId;
				$newT['turnSeat']  = $table['turnSeat']  = $seatId;
				$newT['rate']      = $table['rate']      = 75;//$MODELS['0'][$roomId]['rate'] * $GAME['rate_showcard'];
			}
		}
		$res = $this->setTableInfo($tableId, $newT);
		return $table;
	}
	//叫地主，返回最新牌桌数据
	function call_lord( $table, $doLord )
	{
		if ( !$table || !is_array($table) || !in_array($doLord,array(1,2)) ) return false;
		$GAME = include(ROOT.'/conf/games.php');
		$tableId = $table['tableId'];
		$seatId = $table['turnSeat'];
		$rate_ = $table['rate_'];
		$seat_rate = $rate_ * $GAME['rate_belord'];
		$is_want = intval($doLord == 1);//1叫抢/2不要
		$seat_rate = $seat_rate * $is_want;
		$res = $this->setSeatRate($tableId,$seatId,$seat_rate);
		if ( !$res ) return false;
		$table["seat{$seatId}rate"] = $seat_rate;
		//临时叫抢倍率变更
		if ( $seat_rate > $rate_ )
		{
			$res = $this->setTableRate_($tableId,$seat_rate);
			if ( !$res ) return false;
			$table['rate_'] = $seat_rate;
		}
		//只要有人叫，马上进入抢地主阶段
		if ( $is_want )
		{
			$state = 5;//抢地主阶段
			$res = $this->setTableState($tableId,$state);
			if ( !$res ) return false;
			$table['state'] = $state;
		}
		return $this->check_lord($table);
	}
	//抢地主，返回最新牌桌数据
	function grab_lord( $table, $doLord )
	{
		if ( !$table || !is_array($table) || !in_array($doLord,array(1,2)) ) return false;
		$GAME = include(ROOT.'/conf/games.php');
		$tableId = $table['tableId'];
		$seatId = $table['turnSeat'];
		$rate_ = $table['rate_'];//临时叫抢倍率
		$seat_rate = $rate_ * $GAME['rate_grablord'];
		$is_want = intval($doLord == 1);//1叫抢/2不要
		$seat_rate = $seat_rate * $is_want;
		$res = $this->setSeatRate($tableId,$seatId,$seat_rate);
		if ( !$res ) return false;
		$table["seat{$seatId}rate"] = $seat_rate;
		//临时叫抢倍率变更
		if ( $seat_rate > $rate_ )
		{
			$res = $this->setTableRate_($tableId,$seat_rate);
			if ( !$res ) return false;
			$table['rate_'] = $seat_rate;
		}
		return $this->check_lord($table);
	}
	//检查地主是否确定，返回最新牌桌数据
	function check_lord( $table )
	{
		if ( !$table || !is_array($table) ) return false;
		$tableId = $table['tableId'];
		$rate = $table['rate'];
		$seat_rates = array(
			'seat0rate'=>$table['seat0rate'],
			'seat1rate'=>$table['seat1rate'],
			'seat2rate'=>$table['seat2rate'],
		);
		$count = array_count_values($seat_rates);
		$new = array();
		$is_lordto = false;//是否确定地主
		//还有人没发话：轮转向下一位
		if ( isset($count['-1']) )
		{
			$seat = $this->getSeatNext($table['turnSeat']);
			$new['turnSeat'] = $seat;
		}
		//三人放弃，且无人明牌：轮转向空位，还原牌桌信息，用于重新发牌
		elseif ( isset($count['0']) && $count['0'] == 3 && $table['firstShow'] == 4 )
		{
			$seat = 4;
			$new['turnSeat'] = $seat;
			$new['state'] = 3;//发牌明牌阶段
			$new['lordCards'] = array();
			$new['seat0rate'] = $new['seat1rate'] = $new['seat2rate'] = -1;
			$new['seat0cards'] = $new['seat1cards'] = $new['seat2cards'] = array();
		}
		//三人放弃，且有人明牌：轮转向明牌的座位，确定地主，改变状态，处理牌组
		elseif ( isset($count['0']) && $count['0'] == 3 && $table['firstShow'] != 4 )
		{
			$seat = $table['firstShow'];
			$new['turnSeat'] = $seat;
			$new['lordSeat'] = $seat;
			$new['state'] = 6;//出牌过牌阶段
			$new["seat{$seat}cards"] = cardsSort2(array_merge($table["seat{$seat}cards"], $table['lordCards']));
		}
		//两人放弃，或都抢弃过：轮转最大倍率座位，确定地主，改变状态，处理牌组
		elseif ( isset($count['0']) && $count['0'] == 2  || !in_array($rate, $seat_rates))
		{
			$seat_rate = max($seat_rates);
			$seat = intval(substr(array_search($seat_rate,$seat_rates),4,1));
			$new['turnSeat'] = $seat;
			$new['lordSeat'] = $seat;
			$new['state'] = 6;//出牌过牌阶段
			$new["seat{$seat}cards"] = cardsSort2(array_merge($table["seat{$seat}cards"], $table['lordCards']));
		}
		//其他情形：轮转向下一个未放弃的座位
		else
		{
			$seat = $this->getSeatNext($table['turnSeat']);
			if ( !$table["seat{$seat}rate"] )
			{
				$seat = $this->getSeatNext($seat);
			}
			$new['turnSeat'] = $seat;
		}
		$res = $this->setTableInfo($tableId, $new);
		if ( !$res ) return false;
		return array_merge($table,$new);
	}
	//获取下一个席位
	function getSeatNext( $seatId )
	{
		if ( !in_array($seatId,range(0,2)) ) return 0;
		return --$seatId == -1 ? 2 : $seatId;
	}


	//追加一条用户的缓发队列
	function addUserQueue($uid, $data)
	{
		if ( !$uid )
		{
			return false;
		}
		return $this->redis->ladd($this->key_user_queue_.$uid,$data);
	}
	//取出首条用户的缓发队列
	function popUserQueue($uid)
	{
		if ( !$uid )
		{
			return false;
		}
		return $this->redis->lpop($this->key_user_queue_.$uid);
	}
	//清空当前用户的缓发队列
	function delUserQueue($uid)
	{
		if ( !$uid )
		{
			return false;
		}
		return $this->redis->del($this->key_user_queue_.$uid);
	}

	//获取模式最新赛场
	function getModelRoomWeekGameLast($modelId,$roomId,$weekId,$isNew=0,$gameId=1)
	{

		if ( !$modelId || !$weekId )
		{
			return false;
		}
		if ( $isNew )
		{
			$game = array();
		}
		else
		{
			$games = $this->getModelGames($modelId);
			$games = $games ? $games : array();

			$game = array();
			foreach ( $games as $k=>$v )
			{
				if ( $modelId == $v['modelId'] && ($roomId?($roomId==$v['roomId']):true) && $weekId == $v['weekId'] )
				{
					$game[$v['gameId']] = $v;
				}
			}
		}
		$now = time();
		$day0 = strtotime(date('Y-m-d'));
		$weekDay = date("N");	//周n[1-7]
		if ( $game )
		{
			krsort($game);
			$game = reset($game);
			$week = $this->getModelWeek($modelId,$roomId,$weekId);
			$game['thisWeekRank'] = $week ? $week['weekRank'] : array();
			$weekPrev = $this->getModelWeekPrev($modelId,$roomId,$weekId);
			$game['lastWeekRank'] = $weekPrev ? $weekPrev['weekRank'] : array();
			$game['weekPool'] = $week['weekPool'];
		}
		else
		{
			//lock it 待续
			$room = $this->getModelRoom($modelId,$roomId,1);

			if ( !$room )
			{
				return false;
			}
			$week = $this->getModelWeek($modelId,$roomId,$weekId,1,$room);
			if ( !$week )
			{
				return false;
			}
			$week['thisWeekRank'] = $week['weekRank'];
			$weekPrev = $this->getModelWeekPrev($modelId,$roomId,$weekId);
			$week['lastWeekRank'] = $weekPrev ? $weekPrev['weekRank'] : array();
			$game = $this->newModelGame($room,$week,$gameId);
			$game['weekPool'] = $week['weekPool'];
		}
		$game['gameIsOpen'] = 1;
		//判断赛场是否开启
		/*$setting = $game['gameOpenSetting'];
		$setting = ($setting && is_array($setting)) ? $setting : array();
		$game['gameIsOpen'] = 1;
		foreach ( $setting as $k=>$v )
		{
			$v = explode("|",$v);
			if ( count($v) != 3 )
			{
				break;
			}
			$start = explode(" ", $v[0]);
			$dateStart = strtotime($start[0].' 00:00:00');
			$todayStart = strtotime(date("Y-m-d ".$start[1]));
			$end = explode(" ", $v[1]);
			$dateEnd =  strtotime($end[0].' 23:59:59');
			$todayEnd = strtotime(date("Y-m-d ".$end[1]));
			$weeks = $v[2];
			if ( $day0 > $dateStart && $day0 < $dateEnd && $now > $todayStart && $now < $todayEnd && ($weeks ? (strpos($weeks,$weekDay) !== false) : 1) )
			{
				$game['gameIsOpen'] = 1;
				break;
			}
		} */
		return $game;
	}

	//获取模式所有赛场
	function getModelGames($modelId)
	{
		if ( !$modelId )
		{
			return false;
		}
		return $this->redis->hgetall($this->key_model_games_.$modelId);
	}

	//初始化一个赛场
	function newModelGame($room,$week,$gameId=1)
	{
		$modelId = $week['modelId'];
		$gamesId = $week['modelId'].'_'.$week['roomId'].'_'.$week['weekId'].'_'.$gameId;
		$game = array(
			'gamesId' => $gamesId,
			'modelId' => $week['modelId'],
			'roomId' => $week['roomId'],
			'weekId' => $week['weekId'],
			'gameId' => $gameId,
			'gameLevel' => $room['gameLevel'],
			'gamePool' => 0,
			'gamePerson' => 0,
			'gamePlay' => 0,
			'gameStart' => 0,
			'gameCreate' => time(),
		);
		$game = array_merge($room,$week,$game);
		$res = $this->redis->hset($this->key_model_games_.$modelId,$gamesId,$game);
		if ( !$res )
		{
			return false;
		}
		return $game;
	}

	//获取赛事某个房间
	function getModelRoom($modelId,$roomId,$isNew=0)
	{
		if ( !$modelId )
		{
			return false;
		}
		if ( !$isNew )
		{
			return $this->redis->hget($this->key_model_rooms_.$modelId,$modelId.'_'.$roomId);
		}
		$rooms = $this->getModelRooms($modelId,$isNew);
		if ( !$rooms )
		{
			return false;
		}
		krsort($rooms);
		$i = 1;
		foreach ( $rooms as $roomsId=>$v )
		{
			if ( $i ==1 )
			{
				$rand['1'] = $roomsId;
			}
			else if ( $i == 2 )
			{
				$rand['2'] = $rand['3'] = $rand['4'] = $roomsId;
			}
			else
			{
				$rand['5'] = $rand['6'] = $rand['7'] = $rand['8'] = $roomsId;
			}
			$i++;
		}
		return $rooms['1_1004'];//暂时固定为初级竞技场
		//return $rooms[$rand[mt_rand(2,8)]];
	}

	//获取赛事所有房间
	function getModelRooms($modelId,$isNew=0)
	{
		$MODELS = include(ROOT.'/conf/rooms.php');
		if ( !$modelId )
		{
			return false;
		}
		$rooms = $this->redis->hgetall($this->key_model_rooms_.$modelId);
		if ( $rooms )
		{
			return $rooms;
		}
		if ( !$isNew )
		{
			return false;
		}
		//载入房间配置
		//$sql = "SELECT * FROM `lord_model_rooms` where `modelId` = $modelId";
		//$rooms = $this->mysql->getData($sql);
		$rooms_all = require(ROOT."/conf/rooms.php");
		$rooms = isset($rooms_all[$modelId]) ?$rooms_all[$modelId]: null ;

		if ( $rooms )
		{
			$data = array();
			foreach ( $rooms as $k=>$v )
			{
				/*$v['gameOpenSetting']= json_decode($v['gameOpenSetting'],1);
				$v['gamePrizeCoins'] = json_decode($v['gamePrizeCoins'],1);
				$v['gamePrizePoint'] = json_decode($v['gamePrizePoint'],1);
				$v['gamePrizeProps'] = json_decode($v['gamePrizeProps'],1);
				$v['weekPrizeCoins'] = json_decode($v['weekPrizeCoins'],1);
				$v['weekPrizeProps'] = json_decode($v['weekPrizeProps'],1); */
				$data[$v['roomsId']] = $v;
			}
			$rooms = $data;

			$res = $this->redis->hmset($this->key_model_rooms_.$modelId,$rooms);
			if ( !$res )
			{
				return false;
			}
		}
		elseif ( isset($MODELS[$modelId]) && $MODELS[$modelId] && is_array($MODELS[$modelId]) )
		{
			$rooms_ = $MODELS[$modelId];
			$rooms = array();
			foreach ( $rooms_ as $k=>$v )
			{
				$rooms[$v['roomsId']] = $v;
			}
			//加事务锁	互斥锁
			$res = setLock(__FUNCTION__,1);
			if ( !$res )
			{
				return $rooms;//直接返回房间配置
			}
			$res = $this->redis->hmset($this->key_model_rooms_.$modelId,$rooms);
			if ( !$res )
			{
				//解事务锁
				$res = delLock(__FUNCTION__);
				return false;
			}
			$sql = "INSERT INTO `lord_model_rooms` ( `roomsId`, `modelId`, `roomId`, `roomReal`, `baseCoins`, `rate`, `limitCoins`, `rake`, `enterLimit`, `enterLimit_`, `gameName`, `gameLevel`, `gameScoreIn`, `gameScoreOut`, `gameEndTime`, `gameWinner`, `gameRanknum`, `gameBombAdd`, `gameWaitFirst`, `gameWaitOther`, `gameOpen`, `gameOpenSetting`, `gamePersonAll`, `gameInCoins`, `gameCancelTime`, `gameCancelPerson`, `gamePrizeCoins`, `gamePrizePoint`, `gamePrizeProps`, `gameRule`, `weekPeriod`, `weekPrizeCoins`, `weekPrizeProps`, `create_time`, `update_time` ) VALUES";
			foreach ( $rooms as $k=>$v )
			{
				$sql.= " ( '".$v['roomsId']."', ".$v['modelId'].", ".$v['roomId'].", ".$v['roomReal'].", ".$v['baseCoins'].", ".$v['rate'].", ".$v['limitCoins'].", ".$v['rake'].", ".$v['enterLimit'].", ".$v['enterLimit_'].", '".$v['gameName']."', ".$v['gameLevel'].", ".$v['gameScoreIn'].", ".$v['gameScoreOut'].", ".$v['gameEndTime'].", ".$v['gameWinner'].",  ".$v['gameRanknum'].", ".$v['gameBombAdd'].", ".$v['gameWaitFirst'].", ".$v['gameWaitOther'].", '".$v['gameOpen']."', '".addslashes(json_encode($v['gameOpenSetting']))."', ".$v['gamePersonAll'].", ".$v['gameInCoins'].", ".$v['gameCancelTime'].", ".$v['gameCancelPerson'].", '".addslashes(json_encode($v['gamePrizeCoins']))."', '".addslashes(json_encode($v['gamePrizePoint']))."', '".addslashes(json_encode($v['gamePrizeProps']))."', '".$v['gameRule']."', ".$v['weekPeriod'].", '".addslashes(json_encode($v['weekPrizeCoins']))."', '".addslashes(json_encode($v['weekPrizeProps']))."', NOW(), NOW() ),";
			}
			$res = $this->mysql->runSql(trim($sql,','));
			if ( !$res )
			{
				echo $sql."\n";
				//解事务锁
				$res = delLock(__FUNCTION__);
				return false;
			}
			//解事务锁
			$res = delLock(__FUNCTION__);
		}
		else
		{
			return false;
		}
		return $rooms;
	}
	//获取赛事房间所有周赛数据
	function getModelWeeks($modelId,$roomId)
	{
		if ( !$modelId )
		{
			return false;
		}
		$weeks = $this->redis->hgetall($this->key_model_weeks_.$modelId);
		$weeks = $weeks ? $weeks : array();
		foreach ( $weeks as $k=>$v )
		{
			if ( $v['roomId'] && $v['roomId'] != $roomId )
			{
				unset($weeks[$k]);
			}
		}
		return $weeks;
	}

	//设置赛事周赛数据
	function setModelWeek($modelId,$roomId,$weekId,$week)
	{
		if ( !$modelId  || !$weekId )
		{
			return false;
		}
		$weeksId = $modelId."_".$roomId."_".$weekId;
		return $this->redis->hset($this->key_model_weeks_.$modelId,$weeksId,$week);
	}

	//获取赛事某个周赛
	function getModelWeek($modelId,$roomId,$weekId,$isNew=0,$room=array())
	{
		if ( !$modelId  || !$weekId )
		{
			return false;
		}
		$weeksId = $modelId."_".$roomId."_".$weekId;
		$week = $this->redis->hget($this->key_model_weeks_.$modelId,$weeksId);
		if ( $week )
		{
			return $week;
		}
		if ( !$isNew )
		{
			return false;
		}
		//lock it 待续
		//载入数据库
		echo $weekId . "\n";

		$week = $this->mysql->getLine("SELECT * FROM `lord_model_weeks` where `weeksId` = '".$weeksId."'");


		if ( $week )
		{
			unset($week['id']);
			$week['modelId'] = intval($week['modelId']);
			$week['roomId'] = intval($week['roomId']);
			$week['weekId'] = intval($week['weekId']);
			$week['weekRank']       = json_decode($week['weekRank'],1);
			$week['weekPrizeCoins'] = json_decode($week['weekPrizeCoins'],1);
			$week['weekPrizeProps'] = json_decode($week['weekPrizeProps'],1);
			$res = $this->redis->hset($this->key_model_weeks_.$modelId,$weeksId,$week);
			if ( !$res )
			{
				return false;
			}
		}
		else
		{
			$date = str_split($weekId,2);
			$weekStart = strtotime($date[0].$date[1].'-'.$date[2].'-'.$date[3].' 00:00:00');
			$week = array(
				'weeksId' => $weeksId,
				'modelId' => $modelId,
				'roomId' => $roomId,
				'weekId' => $weekId,
				'weekPool' => 0,
				'weekRank' => array(),
				'weekPrizeCoins' => array(),
				'weekPrizeProps' => array(),
				'weekStart'=> $weekStart,
				'weekEnd'=> $weekStart+intval($room['weekPeriod']*86400)-1,
			);
			$res = $this->redis->hset($this->key_model_weeks_.$modelId,$weeksId,$week);
			if ( $res )
			{
				$sql = "INSERT INTO `lord_model_weeks` ( `weeksId`, `modelId`, `roomId`, `weekId`, `weekPool`, `weekRank`, `weekPrizeCoins`, `weekPrizeProps`, `weekStart`, `weekEnd` ) VALUES ('$weeksId',$modelId,$roomId,$weekId,0,'[]','[]','[]',".$week['weekStart'].",".$week['weekEnd'].")";
				$res = $this->mysql->runSql($sql);
				echo $sql."\n";
				if ( !$res ){
					$this->redis->hdel($this->key_model_weeks_.$modelId,$weeksId);
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		return $week;
	}
	//获取某周赛的上个周赛
	function getModelWeekPrev($modelId,$roomId,$weekId)
	{
		if ( !$modelId  || !$weekId )
		{
			return false;
		}
		$weeksAll = $this->redis->hgetall($this->key_model_weeks_.$modelId);
		if ( !$weeksAll )
		{
			return false;
		}
		$weeks = array();
		$is_in = 0;
		foreach ( $weeksAll as $k=>$v )
		{
			$v['weekId'] = intval($v['weekId']);
			$weeks[strval($v['weekId'])] = $v;
			if ( $v['weekId'] == $weekId )
			{
				$is_in = 1;
			}
		}
		if ( !$is_in )
		{
			return false;
		}
		krsort($weeks);
		$i = 0;
		$week = false;
		foreach ( $weeks as $k=>$v )
		{
			if ( $v['weekId'] < $weekId )
			{
				$week = $v;
				break;
			}
		}
		return $week;
	}
	//入库周赛信息
	function insModelWeek($modelId,$roomId,$weekId,$week)
	{
		if ( !$modelId || !$weekId || !$week || !is_array($week) )
		{
			return false;
		}
		$weeksId = $modelId.'_'.$roomId.'_'.$weekId;
		$data = $this->mysql->getLine("SELECT * FROM `lord_model_weeks` where `weeksId` = '".$weeksId."'");
		if ( $data )
		{
			$sql = "UPDATE `lord_model_weeks` SET `weekPool` = ".$week['weekPool'].", `weekRank` = '".addslashes(json_encode($week['weekRank']))."', `weekPrizeCoins` = '".addslashes(json_encode($week['weekPrizeCoins']))."', `weekPrizeProps` = '".addslashes(json_encode($week['weekPrizeProps']))."' WHERE `id` = ".$data['id'];
			bobSql($sql);
		}
		else
		{
			$sql = "INSERT INTO `lord_game_weeks` ( `weeksId`, `modelId`, `roomId`, `weekId`, `weekPool`, `weekRank`, `weekPrizeCoins`, `weekPrizeProps` ) VALUES ( '".$weeksId."', ".$modelId.", ".$roomId.", ".$weekId.", ".$week['weekPool'].", '".addslashes(json_encode($week['weekRank']))."', '".addslashes(json_encode($week['weekPrizeCoins']))."', '".addslashes(json_encode($week['weekPrizeProps']))."' )";
			bobSql($sql);
		}
		return true;
	}

	//获取上周weekId
	function getWeekLast($weekId)
	{
		$y = intval($weekId/100);
		$w = $weekId-$y*100;
		return ($w > 1) ? intval($weekId-1) : intval(($y-1).(($y==2016||$y==2021)?53:52));//十年之内只有2015/2020年为53周
	}
	//获取某个赛事信息
	function getModelGame($modelId,$roomId,$weekId,$gameId)
	{
		if ( !$modelId || !$weekId || !$gameId )
		{
			return false;
		}
		$gamesId = $modelId.'_'.$roomId.'_'.$weekId.'_'.$gameId;
		return $this->redis->hget($this->key_model_games_.$modelId,$gamesId);
	}
	//重设某个赛事信息
	function setModelGame($modelId,$roomId,$weekId,$gameId,$game)
	{
		if ( !$modelId || !$weekId || !$gameId || !$game || !is_array($game) )
		{
			return false;
		}
		$gamesId = $modelId.'_'.$roomId.'_'.$weekId.'_'.$gameId;
		return $this->redis->hset($this->key_model_games_.$modelId,$gamesId,$game);
	}
	//删除场赛信息
	function delModelGame($modelId,$gamesId)
	{
		if ( !$modelId || !$gamesId )
		{
			return false;
		}
		return $this->redis->hdel($this->key_model_games_.$modelId,$gamesId);
	}
	//入库场赛信息，并清空redis数据
	function insModelGame($modelId,$roomId,$weekId,$gameId,$game)
	{
		if ( !$modelId || !$weekId || !$gameId || !$game || !is_array($game) )
		{
			return false;
		}
		$gamesId = $game['gamesId'];
		$sql = "INSERT INTO `lord_model_games` ( `gamesId`, `modelId`, `roomId`, `weekId`, `gameId`, `gameLevel`, `gamePool`, `gamePerson`, `gamePlay`, `gameScore`, `gamePrizeCoins`, `gamePrizePoint`, `gamePrizeProps`, `gameStart`, `gameOver` ) VALUES ( '$gamesId', $modelId, $roomId, $weekId, $gameId, ".$game['gameLevel'].", ".$game['gamePool'].", ".$game['gamePerson'].",  ".$game['gamePlay'].", '".addslashes(json_encode($game['gameScore']))."', '".addslashes(json_encode($game['gamePrizeCoins']))."', '".addslashes(json_encode($game['gamePrizePoint']))."', '".addslashes(json_encode($game['gamePrizeProps']))."', ".$game['gameStart'].", ".$game['gameOver']." )";
		$res = $this->mysql->runSql($sql);
		if ( !$res )
		{
			echo $sql."\n";
			return false;
		}
		else
		{
			return $this->delModelGame($modelId,$gamesId);
		}
	}
	//获取场赛全部用户
	function getModelGamePlayAll($gamesId)
	{
		if ( !$gamesId )
		{
			return false;
		}
		echo $this->key_model_gameplay_.$gamesId."\n";
		return $this->redis->hgetall($this->key_model_gameplay_.$gamesId);
	}
	//获取场赛用户
	function getModelGamePlay($gamesId,$uid)
	{
		if ( !$gamesId || !$uid )
		{
			return false;
		}
		return $this->redis->hget($this->key_model_gameplay_.$gamesId,$uid);
	}
	//加入场赛用户
	function addModelGamePlay($game,$user)
	{
		echo "addModelGamePlay beg \n";
		if ( !$game || !is_array($game) || !$user || !is_array($user) ) { return false; }
		$now = time();
		$uid = $user['uid'];
		$modelId = $game['modelId'];
		$roomId = $game['roomId'];
		$roomReal = $game['roomReal'];
		$weekId = $game['weekId'];
		$gameId = $game['gameId'];
		$gamesId = $game['gamesId'];
		$gameplayId = $gamesId.'_'.$uid;
		$new['gameplayId']= $user['gameplayId']= $userModel['gameplayId']= $gameplayId;
		$new['gamesId']  = $user['gamesId']  = $userModel['gamesId']  = $gamesId;
		$new['modelId']  = $user['modelId']  = $userModel['modelId']  = $modelId;
		$new['roomId']   = $user['roomId']   = $userModel['roomId']   = $roomReal;//用户信息里面存储实际房间id
		$new['weekId']   = $user['weekId']   = $userModel['weekId']   = $weekId;
		$new['gameId']   = $user['gameId']   = $userModel['gameId']   = $gameId;
		$new['joinTime'] = $user['joinTime'] = $userModel['joinTime'] = $now;
		$new['score']    = $user['score']    = $userModel['score']    = $game['gameScoreIn'];
		$user['coins'] += $game['gameInCoins'];
		//$res = setUser($uid,$new);
		$addU['coins'] = $game['gameInCoins'] * -1;
		//$addU && $this->incUserInfo($uid, $addU, 3);//3报名竞技

		$res = $this->setUserModel($uid,$userModel);//掉线后用户参赛信息保留
		$gameplay = array(
			'roomId'=>$roomId,//参与情况里面使用伪造roomId
			'deadTime' => 0,
			'overTime' => 0,
			'create_time' => $now,
			'update_time' => $now,
		);
		$gameplay = array_merge($user,$gameplay);
		$res = $this->redis->hset($this->key_model_gameplay_.$gamesId,$uid,$gameplay);
		$game = $this->getModelGame($modelId,$roomId,$weekId,$gameId);
		$game['gamePool'] += $game['gameInCoins'];
		$game['gamePerson']++;
		$game['gamePlay']++;
		$res = $this->setModelGame($modelId,$roomId,$weekId,$gameId,$game);
		//$week = $this->getModelWeek($modelId,$roomId,$weekId);
		//$week['weekPool'] += $game['gameInCoins'];
		//$res = $this->setModelWeek($modelId,$roomId,$weekId,$week);
		echo "addModelGamePlay end \n";
		return $game;
	}
	//入库场赛用户，并清空redis数据
	function insModelGamePlay($gamesId,$gamePlayAll)
	{
		if ( !$gamesId || !$gamePlayAll || !is_array($gamePlayAll) )
		{
			return false;
		}
		$gamesId_ = explode('_', $gamesId);
		$modelId = $gamesId_[0];
		$roomId = $gamesId_[1];
		$weekId = $gamesId_[2];
		$gameId = $gamesId_[3];
		$sql = "INSERT INTO `lord_model_gameplay` (`gameplayId`, `modelId`, `roomId`, `weekId`, `gameId`, `uid`, `cool_num`, `joinTime`, `deadTime`, `overTime`, `coins`, `score`, `create_time`, `update_time`) VALUES ";
		foreach ( $gamePlayAll as $k=>$v )
		{
			if ( $v['gameplayId'] && $v['uid'] ) {
				$sql.="('".$v['gameplayId']."', $modelId, $roomId, $weekId, $gameId, ".$v['uid'].", ".$v['cool_num'].", ".$v['joinTime'].", ".$v['deadTime'].", ".$v['overTime'].", ".$v['coins'].", ".$v['score'].", ".$v['create_time'].", ".$v['update_time']."),";
				$res = $this->redis->hdel($this->key_model_gameplay_.$gamesId,$v['uid']);
			} else {
				gerr("[badplayer] ".json_encode($v));
			}
		}
		$res = $this->mysql->runSql(trim($sql,','));
		if ( !$res ) return false;
		return true;
	}
	//设置参赛用户
	function setModelGamePlay($gamesId,$uid,$gameplay)
	{
		if ( !$gamesId || !$uid || !$gameplay || !is_array($gameplay) )
		{
			return false;
		}
		return $this->redis->hset($this->key_model_gameplay_.$gamesId,$uid,$gameplay);
	}
	//更新参赛用户
	function updModelGamePlay($gamesId,$uid,$data)
	{
		if ( !$gamesId || !$uid || !$data || !is_array($data) )
		{
			return false;
		}
		$gameplay = $this->getModelGamePlay($gamesId,$uid);
		if ( !$gameplay )
		{
			return false;
		}
		$gameplay = array_merge($gameplay,$data);
		return $this->setModelGamePlay($gamesId,$uid,$gameplay);
	}
	//删除参赛用户
	function delModelGamePlay($game,$user)
	{
		if ( !$game || !is_array($game) || !$user || !is_array($user) )
		{
			return false;
		}
		$uid = $user['uid'];
		$modelId = $game['modelId'];
		$roomId = $game['roomId'];
		$weekId = $game['weekId'];
		$gameId = $game['gameId'];
		$gamesId = $game['gamesId'];
		$gameplayId = $gamesId.'_'.$uid;
		$coins = $game['gameInCoins'];
		$user = $this->getUserInfo($uid);
		if ( $user )
		{
			if ( isset($user['fd']) && isset($user['coins']) && $user['fd'] ) {
				$newU = array(
					'gameplayId' => '',
					'gamesId' => '',
					'modelId' => 0,
					'roomId' => 0,
					'weekId' => 0,
					'gameId' => 0,
					'joinTime' => 0,
					'score' => 0,
				);
				$res = setUser($uid, $newU);
				$addU['coins'] = $coins;
		 		$addU && $res = $this->incUserInfo($uid, $addU, 4);//4放弃竞技
			} elseif ( $user['isRobot'] ) {
				$res = $this->desRobot($uid, __LINE__);
			} else {
				$sql = "UPDATE `lord_game_user` SET `coins` = `coins` + $coins WHERE `uid` = $uid";
				bobSql($sql);
				$res = $this->desUserInfo($uid, $user, __LINE__);
			}
		}
		$res = $this->delUserModel($uid);
		$res = $this->redis->hdel($this->key_model_gameplay_.$gamesId,$uid);
		$week = $this->getModelWeek($modelId,$roomId,$weekId);
		$week['weekPool'] -= $game['gameInCoins'];
		$res = $this->setModelWeek($modelId,$roomId,$weekId,$week);
		$game = $this->getModelGame($modelId,$roomId,$weekId,$gameId);
		$game['gamePool'] -= $game['gameInCoins'];
		$game['gamePerson']--;
		$game['gamePlay']--;
		return $this->setModelGame($modelId,$roomId,$weekId,$gameId,$game);
	}
	//加入场赛再来队列
	function addModelGoonPlay($gamesId,$gameplay)
	{
		if ( !$gamesId || !$gameplay || !is_array($gameplay) )
		{
			return false;
		}
		$uid = $gameplay['uid'];
		$new['gameplayId']= $gameplay['gameplayId'];
		$new['gamesId']  = $gameplay['gamesId'];
		$new['modelId']  = $gameplay['modelId'];
		$new['roomId']   = $gameplay['roomId'];
		$new['weekId']   = $gameplay['weekId'];
		$new['gameId']   = $gameplay['gameId'];
		$new['joinTime'] = $gameplay['joinTime'];
		$new['score']    = $gameplay['score'];
		$res = setUser($uid,$new);
		return $this->redis->ladd($this->key_model_goonplay_.$gamesId, $gameplay);
	}
	//获取场赛再来队列的长度
	function lenModelGoonPlay($gamesId)
	{
		if ( !$gamesId )
		{
			return false;
		}
		return $this->redis->llen($this->key_model_goonplay_.$gamesId);
	}
	//获取场赛再来队列中的n个用户，默认3个
	function getModelGoonPlay($gamesId,$num=3)
	{
		if ( !$gamesId || !$num || !is_int($num) || $num < 1 )
		{
			return false;
		}
		$len = $num > 1 ? $this->lenModelGoonPlay($gamesId) : 1;
		if ( $len >= $num )
		{
			$gameplays = array();
			$i = 0;
			while ( $i < $num )
			{
				$gameplay = $this->redis->lpop($this->key_model_goonplay_.$gamesId);
				if ( $gameplay )
				{
					$user = $this->getUserInfo($gameplay['uid']);
					if ( !isset($user['giveup']) || !$user['giveup'] ) {
						$gameplays[] = $gameplay;
					} elseif ( isset($user['giveup']) && $user['giveup'] ) {
						$gamesId_ = explode('_', $gamesId);
						$modelId = $gamesId_[0];
						$roomId = $gamesId_[1];
						$weekId = $gamesId_[2];
						$gameId = $gamesId_[3];
						$fd = isset($user['fd'])?$user['fd']:0;
						if ( $fd ) {
						//发送通知: 后发你被淘汰
						$cmd = 5;
						$code = 112;
						$data = array();
						$data['errno'] = 0;
						$data['error'] = "您已放弃放弃竞技赛。";
						$data['modelId'] = $modelId;
						$data['gameId'] = $gameId;
						$data['score'] = 0;
						$this->sendToFd($fd, $cmd, $code, $data);
						}
					}
				}
				$i++;
			}
			if ( count($gameplays) == $num )
			{
				return $gameplays;
			}
			else
			{
				foreach ( $gameplays as $k=>$v )
				{
					$this->addModelGoonPlay($gamesId,$v);
				}
			}
		}
		return false;
	}
	//获取周赛用户全部
	function getModelWeekPlayAll($modelId,$roomId,$weekId)
	{
		if ( !$modelId || !$weekId )
		{
			return false;
		}
		$weeksId = $modelId.'_'.$roomId.'_'.$weekId;
		$list = $this->redis->hgetall($this->key_model_weekplay_.$modelId);
		if ( !$list )
		{
			return false;
		}
		$data = array();
		foreach ( $list as $k=>$v )
		{
			if ( $v['roomId'] == $roomId && $v['weekId'] == $weekId )
			{
				$data[$k]= $v;
			}
		}
		return $data ? $data : false;
	}
	//获取周赛用户
	function getModelWeekPlay($modelId,$roomId,$weekId,$uid)
	{
		if ( !$modelId || !$weekId || !$uid )
		{
			return false;
		}
		$weekplayId = $modelId.'_'.$roomId.'_'.$weekId.'_'.$uid;
		return $this->redis->hget($this->key_model_weekplay_.$modelId,$weekplayId);
	}
	//设置周赛用户
	function setModelWeekPlay($modelId,$roomId,$weekId,$uid,$weekplay)
	{
		if ( !$modelId || !$weekId || !$uid || !$weekplay || !is_array($weekplay) )
		{
			return false;
		}
		$weekplayId = $modelId.'_'.$roomId.'_'.$weekId.'_'.$uid;
		return $this->redis->hset($this->key_model_weekplay_.$modelId,$weekplayId,$weekplay);
	}
	//更新周赛用户
	function updModelWeekPlay($modelId,$roomId,$weekId,$uid,$data)
	{
		if ( !$modelId || !$weekId || !$uid || !$data || !is_array($data) )
		{
			return false;
		}
		$weekplay = $this->getModelWeekPlay($modelId,$roomId,$weekId,$uid);
		if ( !$weekplay )
		{
			return false;
		}
		$weekplay = array_merge($weekplay,$data);
		return $this->setModelWeekPlay($modelId,$roomId,$weekId,$uid,$weekplay);
	}
	//删除周赛用户
	function delModelWeekPlay($modelId,$roomId,$weekId,$uid)
	{
		if ( !$modelId || !$weekId || !$uid )
		{
			return false;
		}
		$weekplayId = $modelId.'_'.$roomId.'_'.$weekId.'_'.$uid;
		return $this->redis->hdel($this->key_model_weekplay_.$modelId,$weekplayId);
	}
	//入库周赛用户
	function insModelWeekPlay($modelId,$roomId,$weekId,$weekPlayAll)
	{
		if ( !$modelId || !$weekId || !$weekPlayAll || !is_array($weekPlayAll) )
		{
			return false;
		}
		foreach ( $weekPlayAll as $k=>$v )
		{
			if ( !isset($v['uid']) || !isset($v['weekplayId']) || !isset($v['cool_num']) || !isset($v['weekPoint']) || !isset($v['weekRank']) || !isset($v['weekPrizeCoins']) || !isset($v['weekPrizeProps']) || !isset($v['create_time']) || !isset($v['update_time']) ) {
				gerr("[BADATA] insModelWeekPlay $modelId,$roomId,$weekId,".json_encode($v));
				continue;
			}
			$uid = $v['uid'];
			$sql = "INSERT INTO `lord_model_weekplay` ( `weekplayId`, `modelId`, `roomId`, `weekId`, `uid`, `cool_num`, `weekPoint`, `weekRank`, `weekPrizeExp`, `weekPrizeCoins`, `weekPrizeProps`, `create_time`, `update_time` ) VALUES ( '".$v['weekplayId']."', ".$modelId.", ".$roomId.", ".$weekId.", ".$uid.", ".$v['cool_num'].", ".$v['weekPoint'].", ".$v['weekRank'].", ".$v['weekPrizeExp'].", ".$v['weekPrizeCoins'].", '".addslashes(json_encode($v['weekPrizeProps']))."', ".$v['create_time'].", ".$v['update_time']." )";
			bobSql($sql);
		}
		return true;
	}
	//写入用户服装道具
	function addDbUserDress($prizeProps)
	{
		if ( !$prizeProps || !is_array($prizeProps) )
		{
			return false;
		}
		$now = time();
		$propIds = $uids = array();
		foreach ( $prizeProps as $uid=>$props )
		{
			$uids[$uid] = $uid;
			foreach ( $props as $propId=>$name )
			{
				$propIds[$propId]=$propId;
			}
		}
		$propList = $this->mysql->getData("SELECT * FROM `lord_game_prop` WHERE `state` = 0 AND `id` IN (".join(',',$propIds).")");
		if ( !$propList )
		{
			return false;
		}
		$propIds = array();
		$propList_ = $propList;
		$propList = array();
		foreach ( $propList_ as $k=>$v )
		{
			$propList[$v['id']]=$v;
			$propIds[$v['id']]=$v['id'];
		}
		$userPropList = $this->mysql->getData("SELECT * FROM `lord_game_userprop` WHERE `propState` < 2 AND `uid` IN (".join(',',$uids).")");
		$userPropList = $userPropList ? $userPropList : array();
		$userProps = $userProps_ = array();
		foreach ( $userPropList as $k=>$v )
		{
			$userProps[$v['uid']][] = $v;
			$userProps_[$v['uid'].'_'.$v['propId']] = $v;
		}
		$data = $u_ = array();
		foreach ( $prizeProps as $uid=>$props )
		{
			foreach ( $props as $propId=>$ext )
			{
				if ( !in_array($propId,$propIds) )
				{
					continue;
				}
				$data[$uid.'_'.$propId] = array(
					'uid' => $uid,
					'fromUid' => 0,//系统
					'propId' => $propId,
					'categoryId' => $propList[$propId]['categoryId'],
					'propStart' => $now,
					'propEnd' => (is_numeric($ext) && $ext > 0) ? ($now + $ext*86400) : ($propList[$propId]['valid'] ? ($now + intval($propList[$propId]['valid']*86400)) : 0),
					'propState' => isset($userProps_[$uid.'_'.$propId])?$userProps_[$uid.'_'.$propId]['propState']:(isset($userProps[$uid])||in_array($uid,$u_) ? 0 : 1),//拥有//启用
				);
				$u_[]=$uid;
			}
		}
		foreach ( $data as $k=>$v )
		{
			if ( isset($userProps_[$k]) )
			{
				bobSql("UPDATE `lord_game_userprop` SET `fromUid` = ".$v['fromUid'].", `propStart` = ".$v['propStart'].", `propEnd` = ".$v['propEnd'].", `propState` = ".$v['propState']." WHERE `id` = ".$userProps_[$k]['id']);
			}
			else
			{	//优化，批量写入
				bobSql("INSERT INTO `lord_game_userprop` ( `uid`, `fromUid`, `propId`, `categoryId`, `num`, `propStart`, `propEnd`, `propState` ) VALUES ( ".$v['uid'].", ".$v['fromUid'].", ".$v['propId'].", ".$v['categoryId'].", 1, ".$v['propStart'].", ".$v['propEnd'].", ".$v['propState']." )");
			}
		}
		return true;
	}
	//写入用户其他道具
	function addDbUserItems( $prizeProps )
	{
		if ( !$prizeProps || !is_array($prizeProps) )
		{
			return false;
		}
		$now = time();
		$propIds = $uids = array();
		foreach ( $prizeProps as $uid=>$props )
		{
			$uids[$uid] = $uid;
			foreach ( $props as $propId=>$name )
			{
				$propIds[$propId]=$propId;
			}
		}
		$propList = $this->mysql->getData("SELECT * FROM `lord_game_prop` WHERE `state` = 0 AND `id` IN (".join(',',$propIds).")");
		if ( !$propList )
		{
			return false;
		}
		$propIds = array();
		$propList_ = $propList;
		$propList = array();
		foreach ( $propList_ as $k=>$v )
		{
			$propList[$v['id']]=$v;
			$propIds[$v['id']]=$v['id'];
		}
		$userPropList = $this->mysql->getData("SELECT * FROM `lord_game_userprop` WHERE `num` > 0 AND `propState` < 2 AND `uid` IN (".join(',',$uids).")");
		$userPropList = $userPropList ? $userPropList : array();
		$userProps = array();
		foreach ( $userPropList as $k=>$v )
		{
			$userProps[$v['uid'].'_'.$v['propId']] = $v['id'];
		}
		$data = array();
		foreach ( $prizeProps as $uid=>$props )
		{
			foreach ( $props as $propId=>$num )
			{
				if ( !in_array($propId,$propIds) )
				{
					continue;
				}
				if ( isset($userProps[$uid.'_'.$propId]) ) 
				{
					$id = $userProps[$uid.'_'.$propId];
					$sql = "UPDATE `lord_game_userprop` SET `num` = `num` + 1 WHERE `propState` < 2 AND `id` = $id";
					$res = $this->mysql->runSql($sql);
					if ( $res ) {
						continue;
					}
				}
				$data[$uid.'_'.$propId] = array(
					'uid' => $uid,
					'fromUid' => 0,//系统
					'propId' => $propId,
					'categoryId' => $propList[$propId]['categoryId'],
					'num' => $num,
					'propStart' => 0,
					'propEnd' => 0,
					'propState' => 0,//拥有//启用
				);
			}
		}
		foreach ( $data as $k=>$v )
		{	//优化，批量写入
			$this->mysql->runSql("INSERT INTO `lord_game_userprop` ( `uid`, `fromUid`, `propId`, `categoryId`, `num`, `propStart`, `propEnd`, `propState` ) VALUES ( ".$v['uid'].", ".$v['fromUid'].", ".$v['propId'].", ".$v['categoryId'].", ".$v['num'].", ".$v['propStart'].", ".$v['propEnd'].", ".$v['propState']." )");
		}
		return true;
	}




	//获取-各种版本号
	function getVersion( $name = "" )
	{
		$versions = $this->redis->hgetall($this->key_game_version);
		if ( !$versions ) {
			$sql = "SELECT `name`,max(`version`) vers FROM `lord_game_version` WHERE `is_done` = 1 GROUP BY `name`";
			$ver = $this->mysql->getData($sql);
			$ver = $ver ? $ver : array();
			$versions = array();
			foreach ( $ver as $k => $v ) $versions[$v['name']] = intval($v['vers']);
			$res = $versions ? $this->redis->hmset($this->key_game_version, $versions) : false;
		}
		$versions = $versions ? $versions : array();
		if ( $name ) {
			if ( isset($versions[$name]) ) {
				return $versions[$name];
			}
			return 0;
		}
		return $versions;
	}
	function newVersion( $name, $version=0, $time=0 )
	{
		if ( !in_array($name, array('version', 'verconf', 'verfile', 'vertips')) ) return false;
		$time = $time ? $time : time();
		$version++;
		if ( $version == $this->getVersion($name) ) {
			return $version;
		}
		$sql = "UPDATE `lord_game_version` SET `end_time` = $time, `comments`= '自动', `is_done` = 1 WHERE `name` = '$name' AND `version` = $version AND `is_done` = 0";
		$res = $this->mysql->runSql($sql);
		$sql = "INSERT INTO `lord_game_version` (`name`, `version`, `start_time`, `end_time`, `comments`, `is_done`) VALUES ('$name', ".($version+1).", ".($time+1).", 0, '', 0)";
		$res = $this->mysql->runSql($sql);
		return $version;
	}

}

