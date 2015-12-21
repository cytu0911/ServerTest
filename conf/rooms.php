<?php

// //房间设置模式设置
// modelId 		int		所属模式id
// roomId		int 	房间id
// roomName		string 	房间名字
// showRules	array()	展现规则。
// 						格式：array(array('channel'=>array('name1'),'gold'=>'','coins'=>'','mixtime'=>array('2014-06-01 09:00:00|2018-06-31 23:30:00|1234567')),array(...))；
// 						默认array()，无限制，无差别显示；array(array(...),...)，有限制，符合任意一条就显示；
// isOpen 		int 	展现后，当前是否开放
// sort 		int 	排序
// baseCoins 	int 	底分
// rate 		int 	基倍
// rateMax 		int 	顶倍，新版本使用
// limitCoins	int 	顶分，兼容旧版本
// rake 		int 	台费
// enter 		string	入场限制文字｜报名费文字
// enterLimit 	int 	入场限制最小分
// enterLimit_ 	int 	入场限制最大分
// gameBombAdd 	int 	本场炸弹基数
return $MODELS = array(
	"0" => array(
		'1000' => array(
			'modelId'    => 0,
			'roomId'     => 1000,
			'name'		 => '新手场',
			'showRules'	 => array(array('coins'=>'0|5000')),
			'showRules'	 => array(),
			'isOpen'	 => 1,
			'sort' 		 => 1000,
			'baseCoins'	 => 5,
			'rate'		 => 15,
			'rateMax'	 => 300,
			'limitCoins' => 3000,
			'rake'	 	 => 150,
			'enter'		 => '1千—5万乐豆',
			'enterLimit' => 1000,
			'enterLimit_'=> 50000,
			'gameBombAdd'=> 0,
		),
		'1001' => array(
			'modelId'    => 0,
			'roomId'     => 1001,
			'name'		 => '初级场',
			'showRules'	 => array(),
			'isOpen'	 => 1,
			'sort' 		 => 1001,
			'baseCoins'	 => 20,
			'rate'		 => 15,
			'rateMax'	 => 500,
			'limitCoins' => 20000,
			'rake'	 	 => 300,
			'enter'		 => '1千—20万乐豆',
			'enterLimit' => 1000,//考虑新旧版本兼容，由2000改为1000
			'enterLimit_'=> 300000,
			'gameBombAdd'=> 0,
		),
		'1002' => array(
			'modelId'    => 0,
			'roomId'     => 1002,
			'name'		 => '中级场',
			'showRules'	 => array(),
			'isOpen'	 => 1,
			'sort' 		 => 1002,
			'baseCoins'  => 60,
			'rate'		 => 15,
			'rateMax'	 => 600,
			'limitCoins' => 72000,
			'rake'	 	 => 600,
			'enter'		 => '1万以上乐豆',
			'enterLimit' => 10000,
			'enterLimit_'=> 2100000000,
			'gameBombAdd'=> 0,
		),
		'1003' => array(
			'modelId'    => 0,
			'roomId'     => 1003,
			'name'		 => '高级场',
			'showRules'	 => array(),
			'isOpen'	 => 1,
			'sort' 		 => 1003,
			'baseCoins'  => 300,
			'rate'		 => 15,
			'rateMax'	 => 700,
			'limitCoins' => 420000,
			'rake'	 	 => 2500,
			'enter'		 => '5万以上乐豆',
			'enterLimit' => 50000,
			'enterLimit_'=> 2100000000,
			'gameBombAdd'=> 0,
		),
	),
	'1' => array(	// 竞技模式
		'1004' => array(
			'modelId'    => 1,
			'roomId'	 => 1004,
			'name'		 => '初级竞技场',
			'showRules'	 => array(array('coins'=>'5001|0')),
			'showRules'	 => array(),
			'isOpen'	 => 1,
			'sort' 		 => 1004,
			'baseCoins'  => 20,
			'rate'		 => 15,
			'rateMax'	 => 2100000000,
			'limitCoins' => 2100000000,
			'rake'	 	 => 0,
			'enter'		 => '1000乐豆',
			'enterLimit' => 0,
			'enterLimit_'=> 2100000000,
			'gameBombAdd' => 1,
			'roomsId'     => '1_1004',
			'roomReal'   => 1004,
			'gameName' => '初级比赛场',
			'gameLevel' => 1,		//1初级2中级3高级
			'gameScoreIn' => 3000,	//初始n竞技币给用户
			'gameScoreOut' => 600,	//低于n竞技币被淘汰
			'gameEndTime' => 900,	//本场最多n秒结束
			'gameWaitFirst' => 10,	//本场第一局等待n秒后自动开始
			'gameWaitOther' => 5,	//本场其他局等待n秒后自动开始
			'gameOpen' => "每天 09:00-23:30",//开放时限的文字呈现
			'gameOpenSetting' => array(	//开放时限判断
				"2014-06-01 09:00:00|2018-06-31 23:30:00|1234567",//开始日期时间|结束日期时间|周n
			),
			'gamePersonAll' => 3,		//凑够人数后才开始
			'gameWinner' => 9,			//本场最多n人胜出
			'gameRanknum' => 30,		//周赛榜单排名人数
			'gameInCoins' => 1000,		//报名费
			'gameCancelTime' => 60,		//报名后晚于n秒时间时可以取消
			'gameCancelPerson' => 2,	//报名后低于n个空位时不可取消
			'gamePrizeCoins' => array(	//奖励筹码
				'1' => 30000,
				'2-4' => 15000,
				'5-9' => 6000,
			),
			'gamePrizePoint' => array(	//奖励积分
				'1' => 5,
				'2-4' => 3,
				'5-9' => 1,
			),
			'gamePrizeProps' => array(	//奖励道具
				'1-9' => array('2'=>'高手套装（7天）'),
			),
			'gameRule' => "游戏规则。",	//游戏规则
			'weekPeriod' => 7,			//赛制周期
			'weekPrizeCoins' => array(	//周奖筹码
				'1' => 1000000,
				'2-4' => 350000,
				'5-9' => 200000,
			),
			'weekPrizeProps' => array(	//周奖道具
				'1-9' => array('3'=>'大师套装（30天）'),
			),
		),
		'1005' => array(
			'modelId'    => 1,
			'roomId'	 => 1005,
			'name'		 => '中级竞技场',
			'showRules'	 => array(array('channel'=>array('none'))),
			'isOpen'	 => 1,
			'sort' 		 => 1005,
			'baseCoins'  => 20,
			'rate'		 => 15,
			'rateMax'	 => 2100000000,
			'limitCoins' => 2100000000,
			'rake'	 	 => 0,
			'enter'		 => '2万乐豆',
			'enterLimit' => 0,
			'enterLimit_'=> 2100000000,
			'gameBombAdd' => 2,
			'roomsId'     => '1_1005',
			'roomReal'   => 1005,
			'gameName' => '中级比赛场',
			'gameLevel' => 2,		//1初级2中级3高级
			'gameScoreIn' => 3000,	//初始n竞技币给用户
			'gameScoreOut' => 600,	//低于n竞技币被淘汰
			'gameEndTime' => 900,	//本场最多n秒结束
			'gameWaitFirst' => 10,	//本场第一局等待n秒后自动开始
			'gameWaitOther' => 5,	//本场其他局等待n秒后自动开始
			'gameOpen' => "每天 09:00-23:30",//开放时限的文字呈现
			'gameOpenSetting' => array(	//开放时限判断
				"2014-06-01 09:00:00|2018-06-31 23:30:00|1234567",//开始日期时间|结束日期时间|周n
			),
			'gamePersonAll' => 6,		//凑够人数后才开始
			'gameWinner' => 9,			//本场最多n人胜出
			'gameRanknum' => 30,		//周赛榜单排名人数
			'gameInCoins' => 20000,		//报名费
			'gameCancelTime' => 60,		//报名后晚于n秒时间时可以取消
			'gameCancelPerson' => 2,	//报名后低于n个空位时不可取消
			'gamePrizeCoins' => array(	//奖励筹码
				'1' => 120000,
				'2-4' => 60000,
				'5-9' => 24000,
			),
			'gamePrizePoint' => array(	//奖励积分
				'1' => 8,
				'2-4' => 4,
				'5-9' => 2,
			),
			'gamePrizeProps' => array(	//奖励道具
				'1-9' => array('2'=>'高手套装（7天）'),
			),
			'gameRule' => "游戏规则。",	//游戏规则
			'weekPeriod' => 7,			//赛制周期
			'weekPrizeCoins' => array(	//周奖筹码
				'1' => 1000000,
				'2-4' => 350000,
				'5-9' => 200000,
			),
			'weekPrizeProps' => array(	//周奖道具
				'1-9' => array('3'=>'大师套装（30天）'),
			),
		),
		'1006' => array(		//赛事1高级场
			'modelId'    => 1,
			'roomId'	 => 1006,
			'name'		 => '高级竞技场',
			'showRules'	 => array(array('channel'=>array('none'))),
			'isOpen'	 => 1,
			'sort' 		 => 1006,
			'baseCoins'  => 20,
			'rate'		 => 15,
			'rateMax'	 => 2100000000,
			'limitCoins' => 2100000000,
			'rake'	 	 => 0,
			'enter'		 => '5万乐豆',
			'enterLimit' => 0,
			'enterLimit_'=> 2100000000,
			'gameBombAdd' => 3,
			'roomsId'    => '1_1006',
			'roomReal'   => 1006,
			'gameName' => '高级比赛场',
			'gameLevel' => 3,		//1初级2中级3高级
			'gameScoreIn' => 3000,	//初始n竞技币给用户
			'gameScoreOut' => 600,	//低于n竞技币被淘汰
			'gameEndTime' => 900,	//本场最多n秒结束
			'gameWaitFirst' => 10,	//本场第一局等待n秒后自动开始
			'gameWaitOther' => 5,	//本场其他局等待n秒后自动开始
			'gameOpen' => "每天 09:00-23:30",//开放时限的文字呈现
			'gameOpenSetting' => array(	//开放时限判断
				"2014-06-01 09:00:00|2018-06-31 23:30:00|1234567",//开始日期时间|结束日期时间|周n
			),
			'gamePersonAll' => 6,		//凑够人数后才开始
			'gameWinner' => 9,			//本场最多n人胜出
			'gameRanknum' => 30,		//周赛榜单排名人数
			'gameInCoins' => 50000,		//报名费
			'gameCancelTime' => 60,		//报名后晚于n秒时间时可以取消
			'gameCancelPerson' => 2,	//报名后低于n个空位时不可取消
			'gamePrizeCoins' => array(	//奖励筹码
				'1' => 300000,
				'2-4' => 150000,
				'5-9' => 60000,
			),
			'gamePrizePoint' => array(	//奖励积分
				'1' => 10,
				'2-4' => 5,
				'5-9' => 3,
			),
			'gamePrizeProps' => array(	//奖励道具
				'1-9' => array('2'=>'高手套装（7天）'),
			),
			'gameRule' => "游戏规则。",	//游戏规则
			'weekPeriod' => 7,			//赛制周期
			'weekPrizeCoins' => array(	//周奖筹码
				'1' => 1000000,
				'2-4' => 350000,
				'5-9' => 200000,
			),
			'weekPrizeProps' => array(	//周奖道具
				'1-9' => array('3'=>'大师套装（30天）'),
			),
		),
	),
	// "91" => array(	// 广告模式91
	// 	'1091' => array(
	// 		'modelId'    => 91,
	// 		'roomId'     => 1091,
	// 		'name'		 => '',
	// 		'showRules'	 => array(),
	// 		'baseCoins'	 => 0,
	// 		'rateMax'	 => 0,
	// 		'enter'		 => '',
	// 		'isOpen'	 => 1,
	// 		'sort' 		 => 1091,
	// 		'sort' 		 => 998,
	// 		'apkurl'	 => '',
	// 		'appid'		 => 0,
	// 		'ver'		 => '',
	// 		'vercode'	 => 0,
	// 		'bytes'		 => 0,
	// 		'desc'		 => '',
	// 		'md5'		 => '',
	// 		'package'	 => '',
	// 	),
	// 	'1092' => array(
	// 		'modelId'    => 91,
	// 		'roomId'     => 1092,
	// 		'name'		 => '',
	// 		'showRules'	 => array(),
	// 		'baseCoins'	 => 0,
	// 		'rateMax'	 => 0,
	// 		'enter'		 => '',
	// 		'isOpen'	 => 1,
	// 		'sort' 		 => 1092,
	// 		'sort' 		 => 999,
	// 		'apkurl'	 => 'http://gt2.youjoy.tv/packages/youjoyddz_wukong.apk',
	// 		'appid'		 => 1000,
	// 		'ver'		 => '1.1.1',
	// 		'vercode'	 => 1,
	// 		'bytes'		 => 10240065,
	// 		'desc'		 => '测试把有乐斗地主更新成博雅斗地主',
	// 		'md5'		 => 'c5c3fc437b0815c15df4dbf41047b6e6',
	// 		'package'	 => 'com.boyaa.lordland.tv',
	// 	),
	// ),
);
