<?php

//客户端请求/发送/拉取
return $requests = array(
	'3' => array(
		'302' => array('act'=>'GET_HORNE',		'name'=>'全服广播'),
		'303' => array('act'=>'GET_ALERT',		'name'=>'系统警告'),
		'305' => array('act'=>'GET_POPWIN',		'name'=>'系统弹窗'),
		'307' => array('act'=>'GET_TIPS',		'name'=>'系统提示'),
		'309' => array('act'=>'GET_MAIL',		'name'=>'系统邮箱'),
		'311' => array('act'=>'SET_PROMPT',		'name'=>'填空内容'),
		'313' => array('act'=>'SET_CONFIRM',	'name'=>'确认结果'),
		'315' => array('act'=>'SET_TELL',		'name'=>'交谈内容'),
		'317' => array('act'=>'GET_TOLD',		'name'=>'交谈内容'),
	),
	'4' => array(	//辅助协议
		//基础数据协议
		'101' => array('act'=>'REQ_GAME_VERSI',	'name'=>'版本编号'),// version/verconf/verfile/vertips待扩展
		'103' => array('act'=>'REQ_GAME_CONFS',	'name'=>'游戏配置'),
		'105' => array('act'=>'REQ_GAME_FILES',	'name'=>'游戏素材'),
		'107' => array('act'=>'REQ_GAME_TIPS_',	'name'=>'游戏提示',	'user'=>1),
		'109' => array('act'=>'GET_USER_INFOR',	'name'=>'用户刷新',	'user'=>1),
		'111' => array('act'=>'GET_USER_MAILS',	'name'=>'用户新邮',	'user'=>1),
		// '113' => array('act'=>'GET_USER_AVATA',	'name'=>'用户形象'),
		// '115' => array('act'=>'GET_USER_STATE',	'name'=>'道具加身'),
		// '121' => array('act'=>'REQ_DATA_PACK_',	'name'=>'用户背包'),//pack
		// '123' => array('act'=>'REQ_DATA_RECHA',	'name'=>'充值选项'),//recharge
		// '125' => array('act'=>'REQ_DATA_CHARG',	'name'=>'兑换选项'),//charge
		// '127' => array('act'=>'REQ_DATA_PROP_',	'name'=>'道具列表'),//propertys
		// '129' => array('act'=>'REQ_DATA_TOPIC',	'name'=>'活动列表'),//topic
		// '131' => array('act'=>'REQ_DATA_NOTIC',	'name'=>'公告列表'),//notic
		// '133' => array('act'=>'REQ_DATA_TASK_',	'name'=>'任务列表'),//task
		// '135' => array('act'=>'REQ_DATA_RICH_',	'name'=>'大户榜列'),//rich
		// '137' => array('act'=>'REQ_DATA_GOLD_',	'name'=>'富豪榜列'),//gold
		// '139' => array('act'=>'REQ_DATA_DAILY',	'name'=>'每日榜列'),//daily
		// '141' => array('act'=>'REQ_DATA_WEEKY',	'name'=>'每周榜列'),//weeky
		//用户相关协议 资料 邮箱 背包 消费记录 ... 帮助
		'201' => array('act'=>'REQ_USER_INDEX',	'name'=>'资料面板',	'user'=>1),
		// '203' => array('act'=>'REQ_USER_NICK_',	'name'=>'修改昵称',	'user'=>1),
		// '205' => array('act'=>'REQ_USER_SEX__',	'name'=>'修改性别',	'user'=>1),
		'207' => array('act'=>'REQ_USER_AGE__',	'name'=>'修改年龄',	'user'=>1),
		// '209' => array('act'=>'REQ_USER_WORD_',	'name'=>'修改签名',	'user'=>1),
		'211' => array('act'=>'REQ_USER_INBOX',	'name'=>'邮箱面板',	'user'=>1),
		'213' => array('act'=>'REQ_MAIL_READ_',	'name'=>'阅读邮件',	'user'=>1),
		'215' => array('act'=>'REQ_MAIL_ITEM_',	'name'=>'领取邮件',	'user'=>1),
		'217' => array('act'=>'REQ_MAIL_DELET',	'name'=>'删除邮件',	'user'=>1),
		// '221' => array('act'=>'REQ_USER_PACK_',	'name'=>'背包面板',	'user'=>1),
		// '223' => array('act'=>'REQ_PROP_USEIT',	'name'=>'使用道具',	'user'=>1),
		'231' => array('act'=>'REQ_USER_COST_',	'name'=>'消费记录',	'user'=>1),
		// '241' => array('act'=>'REQ_USER_KEYS_',	'name'=>'按键提示'),
		// '291' => array('act'=>'REQ_USER_HELP_',	'name'=>'用户帮助'),
		//商城相关协议 充值 兑换 道具 ... 帮助
		// '301' => array('act'=>'REQ_MALL_INDEX',	'name'=>'商城主页'),
		// '311' => array('act'=>'REQ_MALL_CHARG',	'name'=>'兑换面板'),
		// '321' => array('act'=>'REQ_MALL_PROP_',	'name'=>'道具面板'),
		// '391' => array('act'=>'REQ_MALL_HELP_',	'name'=>'商城帮助'),
		//活动相关协议 活动 礼包 抽奖 公告 ... 帮助
		'401' => array('act'=>'REQ_TOPI_INDEX',	'name'=>'活动主页',	'user'=>1),
		// '403' => array('act'=>'REQ_TOPI_LOBBY',	'name'=>'热门活动'),
		// '411' => array('act'=>'REQ_TOPI_ACTIV',	'name'=>'礼包面板'),
		// '413' => array('act'=>'REQ_ACTIV_CODE',	'name'=>'礼包领奖'),
		// '421' => array('act'=>'REQ_TOPI_LUCKY',	'name'=>'抽奖面板'),
		// '423' => array('act'=>'REQ_LUCKY_DRAW',	'name'=>'免费抽奖'),
		'431' => array('act'=>'REQ_TOPI_NOTIC',	'name'=>'公告面板',	'user'=>1),
		// '491' => array('act'=>'REQ_TOPI_HELP_',	'name'=>'活动帮助'),
		//任务相关协议 任务 签到 ... 帮助
		'501' => array('act'=>'REQ_TASK_INDEX',	'name'=>'任务主页',	'user'=>1),
		// '511' => array('act'=>'REQ_TASK_CHECK',	'name'=>'签到面板'),
		// '513' => array('act'=>'REQ_CHECK_DONE',	'name'=>'每日签到'),
		// '591' => array('act'=>'REQ_TASK_HELP_',	'name'=>'任务帮助'),
		//榜单相关协议 恶霸榜 富豪榜 每日榜 每周榜 ... 帮助
		// '601' => array('act'=>'REQ_LIST_INDEX',	'name'=>'榜单主页'),
		// '603' => array('act'=>'REQ_EVIL_ROBIT',	'name'=>'明抢恶霸'),
		// '611' => array('act'=>'REQ_LIST_GOLD_',	'name'=>'富豪榜单'),
		'621' => array('act'=>'REQ_LIST_DAILY',	'name'=>'每日榜单',	'user'=>1),
		'631' => array('act'=>'REQ_LIST_WEEKY',	'name'=>'每周榜单',	'user'=>1),
		// '691' => array('act'=>'REQ_LIST_HELP_',	'name'=>'榜单帮助),
		),
	'5' => array(	//游戏协议
		//牌桌协议
		'0'	  => array('act'=>'USER_INTO_ROOM',	'name'=>'用户进房',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		'1'	  => array('act'=>'USER_GET_READY',	'name'=>'用户再局',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		'2'	  => array('act'=>'USER_JOIN_TRIO',	'name'=>'用户凑桌',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		// '3' => '???',			//闲置
		// '4' => 'GAME_SHUFFLE ',	//发牌
		'5'	  => array('act'=>'USER_CALL_LORD',	'name'=>'用户叫庄',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		'6'	  => array('act'=>'USER_GRAB_LORD',	'name'=>'用户抢庄',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		'7'	  => array('act'=>'USER_SHOW_CARD',	'name'=>'用户明牌',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		'8'	  => array('act'=>'USER_SEND_CARD',	'name'=>'用户出牌',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		// '9' => 'GAME_FOLLOW',	//跟牌
		'10'  => array('act'=>'USER_NO_FOLLOW',	'name'=>'用户不跟',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		// '11'=> 'GAME_COUNT_OVER',//结完
		// '12'=> 'USER_AFK',		//暂离
		'13'  => array('act'=>'USER_EXIT_ROOM',	'name'=>'用户退房',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		'14'  => array('act'=>'USER_NEW_TABLE',	'name'=>'用户换桌',	'user'=>1,	'locku'=>1,	'lock'=>'tableId'),
		'15'  => array('act'=>'USER_SET_TRUST',	'name'=>'用户托管',	'user'=>1),
		'16'  => array('act'=>'USER_OUT_TRUST',	'name'=>'用户解托',	'user'=>1),
		'31'  => array('act'=>'USER_SAY_TABLE',	'name'=>'用户发言',	'user'=>1),
		//用户协议
		'20'  => array('act'=>'USER_EDIT_NICK',	'name'=>'用户改名',	'user'=>1),
		'21'  => array('act'=>'USER_EDIT_SEX_',	'name'=>'用户改性',	'user'=>1),
		'22'  => array('act'=>'USER_EDIT_WORD',	'name'=>'用户改签',	'user'=>1),
		'23'  => array('act'=>'USER_GET_INFOR',	'name'=>'用户刷新',	'user'=>1),
		'41'  => array('act'=>'USER_GOLD_COIN',	'name'=>'兑换金币',	'user'=>1,	'locku'=>1),
		'42'  => array('act'=>'USER_COUP_COIN',	'name'=>'兑换奖券',	'user'=>1,	'locku'=>1),
		'123' => array('act'=>'REQ_PROP_DRESS',	'name'=>'换穿服装',	'user'=>1),
		'125' => array('act'=>'REQ_PROP_BUYIT',	'name'=>'购买服装',	'user'=>1,	'locku'=>1),
		//竞技协议
		'101' => array('act'=>'REQ_MODEL_LOOKUP','name'=>'竞技面板',	'user'=>1),
		'103' => array('act'=>'REQ_MODEL_ENROLL','name'=>'报名竞技',	'user'=>1,	'locku'=>1),
		'105' => array('act'=>'REQ_MODEL_CANCEL','name'=>'取消竞技',	'user'=>1,	'locku'=>1),
		'109' => array('act'=>'REQ_MODEL_GIVEUP','name'=>'放弃竞技',	'user'=>1,	'locku'=>1),
		'111' => array('act'=>'REQ_MODEL_READY','name'=>'竞技入场',	'user'=>1,	'locku'=>1),
		'127' => array('act'=>'REQ_MODEL_LOOK',	'name'=>'刷新竞技',	'user'=>1,	'locku'=>1),
		'131' => array('act'=>'REQ_LUCKY_SHOW',	'name'=>'抽奖面板',	'user'=>1),
		'133' => array('act'=>'REQ_LUCKY_DRAW',	'name'=>'抽奖操作',	'user'=>1,	'locku'=>1),
		'135' => array('act'=>'REQ_LUCKY_HIST',	'name'=>'抽奖记录',	'user'=>1),
		'137' => array('act'=>'REQ_LOGIN_DAY0',	'name'=>'签到面板',	'user'=>1),
		'139' => array('act'=>'REQ_LOGIN_SIGN',	'name'=>'签到操作',	'user'=>1,	'locku'=>1),
		'141' => array('act'=>'REQ_LIST_TODAY',	'name'=>'今日榜单',	'user'=>1),
		'143' => array('act'=>'REQ_LIST_TWEEK',	'name'=>'本周榜单',	'user'=>1),
		'145' => array('act'=>'REQ_LIST_LWEEK',	'name'=>'上周榜单',	'user'=>1),
		'147' => array('act'=>'REQ_BOARD_SHOW',	'name'=>'公告面板',	'user'=>1),
		'149' => array('act'=>'REQ_ACTIVATION',	'name'=>'激活礼包',	'user'=>1,	'locku'=>1),//
		'151' => array('act'=>'REQ_TRIAL_SHOW',	'name'=>'救济面板',	'user'=>1,	'locku'=>1),//50152
		'153' => array('act'=>'REQ_TRIAL_LAPA',	'name'=>'拉霸面板',	'user'=>1,	'locku'=>1),//50154
		'155' => array('act'=>'REQ_TRIAL_EXEC',	'name'=>'拉霸操作',	'user'=>1,	'locku'=>1),//50156,40110,
	),
	'6' => array(	//道具协议
		'101' => array('act'=>'USE_IN_TABLE_A',	'name'=>'使用私有',	'user'=>1,	'locku'=>1),
		'103' => array('act'=>'USE_IN_TABLE_B',	'name'=>'使用公共',	'user'=>1,	'locku'=>1),
		'105' => array('act'=>'USE_IN_TABLE_C',	'name'=>'使用争抢',	'user'=>1,	'locku'=>1),
		'107' => array('act'=>'USE_IN_TABLE_D',	'name'=>'争抢道具',	'user'=>1,	'locku'=>1),
		'201' => array('act'=>'USE_IN_PACK',	'name'=>'使用背包',	'user'=>1,	'locku'=>1),
	),
);

