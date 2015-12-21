<?php

//新牌库类
class Card
{
	public $aiConf = array(			//[机器|托管] AI的等级技能配置
		'0' => array(
			'is_peekunder' => 0,
			'is_peekother' => 0,
			'is_getrole' => 0,
			'is_remember' => 0,
			'is_remainder' => 0,
			'is_unpack' => 0,
			'is_betout' => 0,
			'is_bigout' => 0,
			'is_giveup' => 0,
			'is_unsame' => 0,
		),
		'1' => array(
			'is_peekunder' => 1,
			'is_peekother' => 1,
			'is_getrole' => 1,
			'is_remember' => 1,
			'is_remainder' => 1,
			'is_unpack' => 1,
			'is_betout' => 1,
			'is_bigout' => 1,
			'is_giveup' => 1,
			'is_unsame' => 1,
		),
	);
	public $aiLevel = 0;				//[机器|托管] 当前AI的等级
	public $aiSkill = array();			//[机器|托管] 当前AI的技能
	public $cardsPool	= array();		//总牌池。王红黑梅方依次大小排序的十六进制牌型，一旦初始化，不会变化
	public $table		= array(//桌面上的
			'hand'		=> array(),	//其他人手牌。
			'alltypes'	=> array(),	//其他人可能牌型
			'bidtypes'	=> array(),	//其他人叫牌牌型
			'besttypes'	=> array(),	//其他人最优牌型
			'under'		=> array(),	//底牌。抢地主后，被加入地主手牌
			'out'		=> array(),	//桌牌。所有人已经打出到桌面上的牌
			'cards'		=> array(),	//叫牌牌组
			'cardstypes'=> array(),	//叫牌牌型
			'bidder'	=> 0,		//叫牌位置
	);
	public $prev		= array(//上家的
			'hand'		=> array(),	//手牌
			'alltypes'	=> array(),	//可能牌型
			'bidtypes'	=> array(),	//叫牌牌型
			'besttypes'	=> array(),	//最优牌型
			'show'		=> array(),	//亮出的手牌(如果是地主，默认包含底牌)
			'out'		=> array(),	//桌牌
			'pos'		=> 2,		//位置标识
			'is_lord'	=> 0,		//是否是地主
			'is_bidder'	=> 0,		//是否是叫牌者
	);
	public $next		= array(//下家的
			'hand'		=> array(),	//手牌
			'alltypes'	=> array(),	//可能牌型
			'bidtypes'	=> array(),	//叫牌牌型
			'besttypes'	=> array(),	//最优牌型
			'show'		=> array(),	//亮出的手牌(如果是地主，默认包含底牌)
			'out'		=> array(),	//桌牌
			'pos'		=> 1,		//位置标识
			'is_lord'	=> 0,		//是否是地主
			'is_bidder'	=> 0,		//是否是叫牌者
	);
	public $mine		= array(//我的
			'hand'		=> array(),	//手牌
			'alltypes'	=> array(),	//可能牌型
			'bidtypes'	=> array(),	//叫牌牌型
			'besttypes'	=> array(),	//最优牌型
			'show'		=> array(),	//亮出的手牌(如果是地主，默认包含底牌)
			'out'		=> array(),	//桌牌
			'pos'		=> 0,		//位置标识
			'is_lord'	=> 1,		//是否是地主
			'is_bidder'	=> 1,		//是否是叫牌者
	);

	function __construct($aiLevel=1,$table=array(),$prev=array(),$next=array(),$mine=array())
	{
		$this->cardsPool = $this->initCardsPool();
		$this->aiLevel = $aiLevel;
		$this->aiSkill = $this->getAiSkill($aiLevel);
		if ( $prev )
		{
			$this->prev = array_merge($this->prev,$prev);
			if( $this->prev['hand'])
			   $this->prev['hand']  = $this->cardsSort($this->prev['hand']);
		}
		if ( $next )
		{
			$this->next = array_merge($this->next,$next);
			if($this->next['hand'] )
			  $this->next['hand'] = $this->cardsSort($this->next['hand']);
		}
		if ( $mine )
		{
			$this->mine = array_merge($this->mine,$mine);
			if($this->mine['hand'] )
			  $this->mine['hand'] = $this->cardsSort($this->mine['hand']);
		}
		if ( $table )
		{
			$this->table = array_merge($this->table,$table);
			if( $this->table['hand'] )
			  $this->table['hand'] = $this->cardsSort($this->table['hand']);
			$this->prev['is_bidder'] = $this->prev['pos'] == $this->table['bidder'] ? 1 : 0;
			$this->mine['is_bidder'] = $this->mine['pos'] == $this->table['bidder'] ? 1 : 0;
			$this->next['is_bidder'] = $this->next['pos'] == $this->table['bidder'] ? 1 : 0;
		}
	}

	//获取AI技能
	function getAiSkill($level)
	{
		$conf = $this->aiConf;
		return isset($conf[$level]) ? $conf[$level] : $conf[0];
	}

	//初始化总牌池
	function initCardsPool()
	{
		$cardsPool = array();
		for( $i = 0; $i <= 3; $i++ )	//方梅红黑王
		{
			for( $j = 0;$j <= 12;$j++ )	//3 4 5 ... T J Q K A 2
			{
				$cardsPool[] = dechex($i) . dechex($j);
			}
		}
		$cardsPool[] = dechex(4) . dechex(13);//小王
		$cardsPool[] = dechex(4) . dechex(14);//大王
		return $cardsPool;
	}

	//从原牌数值转移到新牌数值，单牌
	function cardEnTranse($card)
	{
		/*
			高位:
				原[1-4] 1黑 2红 3梅 4方 0王
				新[0-4] 0方 1梅 2红 3黑 4王
			低位:
				原[1-13] A 2 3 4 ... T J Q K 00小王 01大王
				新[0-E] 3 4 5 ... T J Q K A 2 w W
					3 4 5 6 7 8 9 T J Q K A 2
				0   0 1 2 3 4 5 6 7 8 9 A B C		方块
				1   0 1 2 3 4 5 6 7 8 9 A B C		梅花
				2   0 1 2 3 4 5 6 7 8 9 A B C		黑桃
				3   0 1 2 3 4 5 6 7 8 9 A B C		红桃
				4	                          D E	王色
			计算牌型值:
				转16进制(高位花色*16+低位牌值)，便于存储传输
			解析牌型值:
				高位花色=Math.floor(牌型值/16)
				低位牌值=Math.floor(牌型值%16)
		 */
		$dec = hexdec($card);
		$color = abs(floor($dec/16)-4);
		if ( $color == 4 )
		{
			$point = $dec%16+13;
		}
		else
		{
			$point = $dec%16-3;
			$point = ($point < 0) ? ($point+13) : $point;
		}
		$hex = dechex($color*16+$point);
		return isset($hex[1]) ? strval($hex) : ('0'.$hex[0]);
	}

	//从原牌数值转移到新牌数值，多牌
	function cardsEnTranse($cards)
	{
		$cards = ($cards && is_array($cards)) ? $cards : array();
		foreach ( $cards as $k=>$v )
		{
			$cards[$k] = self::cardEnTranse($v);
		}
		return $cards;
	}

	//从新牌数值转移到原牌数值，单牌
	function cardDeTranse($card)
	{
		$dec = hexdec($card);
		$color = abs(floor($dec/16)-4);
		if ( $color == 0 )
		{
			$point = $dec%16-13;
		}
		else
		{
			$point = $dec%16+3;
			$point = ($point > 13) ? ($point-13) : $point;
		}
		$hex = dechex($color*16+$point);
		return isset($hex[1]) ? strval($hex) : ('0'.$hex[0]);
	}

	//从新牌数值转移到原牌数值，多牌
	function cardsDeTranse($cards)
	{
		$cards = ($cards && is_array($cards)) ? $cards : array();
		foreach ( $cards as $k=>$v )
		{
			$cards[$k] = self::cardDeTranse($v);
		}
		return $cards;
	}

	//初始化牌池并发牌
	function newCardPool($is_old=0,$add_bombnum=0)
	{
		//洗牌
		$cards = $this->cardsShuffle($this->cardsPool);
		//分牌 [0][1][2][lord]
		$cards = $this->cardsDeal($cards,$add_bombnum);
		//返回原牌数值
		foreach( $cards as $k => $v )
		{
			if ( $is_old )
			{	//针对原毛竹版的牌面
				$cards[$k] = cardsSort2($this->cardsDeTranse($v));
			}
			else
			{
				$cards[$k] = $this->cardsSort($v);
			}
		}
		return $cards;
	}

	//洗牌
	function cardsShuffle( $cards )
	{
		shuffle($cards);
		return $cards;
	}

	//分牌 [0][1][2][lord]
	function cardsDeal($cardsPool,$add_bombnum=0)
	{
		if ( !$cardsPool || !is_array($cardsPool) || count($cardsPool) != 54 ) return array();
		$add_bombnum = ($add_bombnum && $add_bombnum < 13 && $add_bombnum > 0 ) ? intval($add_bombnum) : 0;
		if ( $add_bombnum )
		{
			//先发炸弹
			$bomb_points = array_merge(range(0,9),range('a','c'));
			shuffle($bomb_points);
			$bomb_points = array_slice($bomb_points,0,$add_bombnum);
			$cardsSeat = $seatBomb = array(array(),array(),array());
			foreach ( $bomb_points as $k=>$v )
			{
				$i = mt_rand(0,2);
				if ( count($seatBomb[$i]) > 3 )
				{
					$i++;
					if ( $i >= 3 ) $i = 0;
					if ( count($seatBomb[$i]) > 3 )
					{
						$i++;
						if ( $i >= 3 ) $i = 0;
						$seatBomb[$i][]=strval($v);
					}
					else
					{
						$seatBomb[$i][]=strval($v);
					}
				}
				else
				{
					$seatBomb[$i][]=strval($v);
				}
			}
			foreach( $cardsPool as $k => $v ){
				$point = strval($v[1]);
				foreach ( $seatBomb as $kk=>$vv )
				{
					if ( in_array($point,$vv) )
					{
						$cardsSeat[strval($kk)][]=$v;
						unset($cardsPool[$k]);
						break;
					}
				}
			}
			//再发其他牌
			$cardsPool = array_values($cardsPool);
			$cardsSeat['lord'] = array_slice($cardsPool,0,3);
			$cardsPool = array_diff($cardsPool,$cardsSeat['lord']);
			$cardsSeat['0'] = array_merge($cardsSeat['0'],array_slice($cardsPool,0,17-count($cardsSeat['0'])));
			$cardsPool = array_diff($cardsPool,$cardsSeat['0']);
			$cardsSeat['1'] = array_merge($cardsSeat['1'],array_slice($cardsPool,0,17-count($cardsSeat['1'])));
			$cardsPool = array_diff($cardsPool,$cardsSeat['1']);
			$cardsSeat['2'] = array_merge($cardsSeat['2'],$cardsPool);
			return $cardsSeat;
		}
		$i = 0;
		foreach( $cardsPool as $k => $v ){
			if( $k >= count($cardsPool) - 3 )
				$cardsPools['lord'][] = $v;
			else
				$cardsPools[$i][] = $v;
			$i++;
			if( $i >= 3 ) $i = 0;
		}
		return $cardsPools;
	}

	//牌组依据同点大小依次倒序
	function cardsSort( $cards )
	{
		$sorts = array();
		foreach( $cards as $k => $v )
		{
			$sorts[hexdec($v)%16*100+floor(hexdec($v)/16)] = $v;
		}
		krsort($sorts);
		return array_values($sorts);
	}

	//牌组低位解析为十进制数组，并倒序
	function cardsDec($cards)
	{
		$hexs = array();
		if ( $cards && is_array($cards) )
		{
			foreach( $cards as $card )
			{
				$hexs[] = $this->cardDec($card);
			}
			rsort($hexs);
		}
		return $hexs;
	}

	//单牌低位解析为十进制
	function cardDec($card)
	{
		return hexdec(isset($card[1])?$card[1]:$card[0]);
	}

	//底牌翻倍策略 需要原牌
	function getLordCardsRate( $cards )
	{
		//默认1倍=不翻倍
		$rate = 1;
		if ( !$cards || !is_array($cards) || count($cards) != 3 ) 
		{
			return $rate;
		}
		$cards = array_values($cards);
		//检测双王/单王 3倍/2倍
		if ( $arr = array_intersect(array('00','01'),$cards) )
		{
			$rate = count($arr) == 2 ? 3 : 2;
		}
		//检测同花 3倍
		elseif ( $cards[0][0] ==  $cards[1][0] &&  $cards[1][0] == $cards[2][0] )
		{
			$rate = 3;
		}
		else
		{
			//原牌翻译、获取低位十进制、倒序
			$cards = $this->cardsDec($this->cardsEnTranse($cards));
			//检测三条 3倍
			if ( $cards[0] ==  $cards[1] &&  $cards[1] == $cards[2] )
			{
				$rate = 3;//4;暂设3倍吧。。。当前游戏是斗地主，不是别的
			}
			//检测顺子 3倍
			elseif ( $cards[0] ==  $cards[1] + 1 &&  $cards[1] == $cards[2] + 1 )
			{
				$rate = 3;
			}
			//检测对子 2倍
			elseif ( $cards[0] == $cards[1] || $cards[1] == $cards[2] )
			{
				$rate = 2;
			}
		}
		return $rate;
	}

	//检查牌型
	/* return array(
	 *		'type' => 0,	//牌型标识，见下面
	 *		'value'=> 9,	//最大牌的数值，用于比较同牌型的大小
	 * )
	 * 0	无效
	 * 1	单牌		一张
	 * 2	对牌		一对
	 * 3	三不带		三条
	 * 4	三带单		三带一
	 * 5	三带对		三带一对
	 * 6	单顺		顺子
	 * 7	双顺		连对
	 * 8	三顺		飞机没翅膀
	 * 9 	三顺带单	飞机带翅膀
	 * 10	三顺带对	飞机大翅膀
	 * 11	四带二单	四带二
	 * 12	四带二对	四带二对
	 * 88	炸弹		四条
	 * 99	火箭		双王
	 */
	function cardsCheck( $cards )
	{
		$len = count($cards);
		if ( !$len || $len > 20 )
		{
			return array('type'=>'00','len'=>'00','value'=>'00','array'=>$cards);
		}
		rsort($cards);
		$value = $cards[0];
		$array = $cards;
		switch ( $len )
		{
			case 1:	//单牌
				$type = '01';		//单牌
				break;
			case 2:	//对牌/火箭/无效
				if ( $cards[0] == $cards[1] )
				{
					$type = '02';	//对牌
				}
				elseif ( $cards[0] == 14 && $cards[1] == 13 )
				{
					$type = '99';	//火箭
				}
				else
				{
					$type = '00';	//无效
				}
				break;
			case 3:	//三不带/无效
				if ( $cards[0] == $cards[1] && $cards[1] == $cards[2] )
				{
					$type = '03';	//三不带
				}
				else
				{
					$type = '00';	//无效
				}
				break;
			case 4:	//炸弹/三带单/无效
				if ( $cards[0] == $cards[1] && $cards[1] == $cards[2] && $cards[2] == $cards[3] )
				{
					$type = '88';	//炸弹
				}
				elseif ( $cards[0] == $cards[1] && $cards[1] == $cards[2] )
				{
					$type = '04';	//三带单，单牌较小
				}
				elseif ( $cards[1] == $cards[2] && $cards[2] == $cards[3] )
				{
					$type = '04';	//三带单，单牌较大
					$value = $cards[1];
					$array = array($cards[1],$cards[2],$cards[3],$cards[0]);
				}
				else
				{
					$type = '00';
				}
				break;
			case 5:	//三带对/单顺/无效
				if ( $cards[0] == $cards[1] && $cards[1] == $cards[2] && $cards[3] == $cards[4] )
				{
					$type = '05';	//三带对，单牌较小
				}
				elseif ( $cards[0] == $cards[1] &&  $cards[2] == $cards[3] && $cards[3] == $cards[4] )
				{
					$type = '05';	//三带对，单牌较大
					$value = $cards[2];
					$array = array($cards[2],$cards[3],$cards[4],$cards[0],$cards[1]);
				}
				elseif ( $val_ = $this->cardsCheckDanShun($cards) )
				{
					$type = '06';	//单顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				else
				{
					$type = '00';
				}
				break;
			case 6:	//单顺/双顺/三顺/四带二单/无效
				if ( $val_ = $this->cardsCheckDanShun($cards) )
				{
					$type = '06';	//单顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckShuangShun($cards) )
				{
					$type = '07';	//双顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckSanShun($cards) )
				{
					$type = '08';	//三顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckSiDaiErDan($cards) )
				{
					$type = '11';	//四带二单
					$value = $val_['value'];
					$array = $val_['array'];
				}
				else
				{
					$type = '00';
				}
				break;
			case 7:	//单顺/无效
				if ( $val_ = $this->cardsCheckDanShun($cards) )
				{
					$type = '06';	//单顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				else
				{
					$type = '00';
				}
				break;
			case 8:	//单顺/双顺/三顺带单/四带二对/无效
				if ( $val_ = $this->cardsCheckDanShun($cards) )
				{
					$type = '06';	//单顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckShuangShun($cards) )
				{
					$type = '07';	//双顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckSiDaiErDui($cards) )//优先检查四带二对牌型
				{//8张牌时的三顺带单(飞机带翅膀)是会有可能和四带二对(四带二)冲突的，比如33334444只能是四带二牌型。
					$type = '12';	//四带二对
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckSanShunDaiDan($cards) )
				{
					$type = '09';	//三顺带单
					$value = $val_['value'];
					$array = $val_['array'];
				}
				else
				{
					$type = '00';
				}
				break;
			default://超过8张	//单顺/双顺/三顺/三顺带单/三顺带对/无效
				if ( $val_ = $this->cardsCheckDanShun($cards) )
				{
					$type = '06';	//单顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckShuangShun($cards) )
				{
					$type = '07';	//双顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckSanShun($cards) )
				{
					$type = '08';	//三顺
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckSanShunDaiDan($cards) )
				{
					$type = '09';	//三顺带单
					$value = $val_['value'];
					$array = $val_['array'];
				}
				elseif ( $val_ = $this->cardsCheckSanShunDaiDui($cards) )
				{
					$type = '10';	//三顺带对
					$value = $val_['value'];
					$array = $val_['array'];
				}
				else
				{
					$type = '00';
				}
				break;
		}
		$len = $len > 9 ? strval($len) : ('0'.$len);
		$value = $value > 9 ? strval($value) : ('0'.$value);
		return array('type'=>$type,'len'=>$len,'value'=>$value,'array'=>$array);
	}

	//检查牌型：单顺
	function cardsCheckDanShun($cards)
	{
		if ( !$cards || !is_array($cards) || array_intersect(array(12,13,14),$cards) )
		{
			return false;
		}
		$num = count($cards);
		if ( $num < 5 || $num > 12 )
		{
			return false;
		}
		foreach ( $cards as $k=>$v )
		{
			if ( isset($cards[$k+1]) && $v-$cards[$k+1] != 1 )
			{
				return false;
			}
		}
		return array('value'=>$cards[0],'array'=>$cards);
	}

	//检查牌型：双顺
	function cardsCheckShuangShun($cards)
	{
		if ( !$cards || !is_array($cards) || array_intersect(array(12,13,14),$cards) )
		{
			return false;
		}
		$num = count($cards);
		if ( $num < 6 || $num%2 )
		{
			return false;
		}
		foreach ( $cards as $k=>$v )
		{
			if ( $k%2 == 1 )
			{
				if ( isset($cards[$k+1]) && $v-$cards[$k+1] != 1 )
				{
					return false;
				}
			}
			else
			{
				if ( isset($cards[$k+1]) && $v != $cards[$k+1] )
				{
					return false;
				}
			}
		}
		return array('value'=>$cards[0],'array'=>$cards);
	}

	//检查牌型：三顺 飞机没翅膀
	function cardsCheckSanShun($cards)
	{
		if ( !$cards || !is_array($cards) || array_intersect(array(12,13,14),$cards) )
		{
			return false;
		}
		$num = count($cards);
		if ( $num < 6 || $num%3 )
		{
			return false;
		}
		foreach ( $cards as $k=>$v )
		{
			if ( $k%3 == 2 )
			{
				if ( isset($cards[$k+1]) && $v-$cards[$k+1] != 1 )
				{
					return false;
				}
			}
			else
			{
				if ( isset($cards[$k+1]) && $v != $cards[$k+1] )
				{
					return false;
				}
			}
		}
		return array('value'=>$cards[0],'array'=>$cards);
	}

	//检查牌型：三顺带单 飞机带翅膀
	function cardsCheckSanShunDaiDan($cards)
	{
		if ( !$cards || !is_array($cards) )
		{
			return false;
		}
		$num = count($cards);
		if ( $num < 8 || $num%4 )
		{
			return false;
		}
		$cards_sanshun = $cards_danpai = array();
		$cards_count = array_count_values($cards);
		krsort($cards_count);//防止错误顺序
		foreach ( $cards_count as $k=>$v )
		{
			if ( $v == 4 )
			{
				$cards_sanshun[] = $k;
				$cards_sanshun[] = $k;
				$cards_sanshun[] = $k;
				$cards_danpai[] = $k;
			}
			elseif ( $v == 3 )
			{
				$cards_sanshun[] = $k;
				$cards_sanshun[] = $k;
				$cards_sanshun[] = $k;
			}
			elseif ( $v == 2 )
			{
				$cards_danpai[] = $k;
				$cards_danpai[] = $k;
			}
			elseif ( $v == 1 )
			{
				$cards_danpai[] = $k;
			}
			else
			{
				return false;
			}
		}
		if ( !$cards_sanshun || count($cards_sanshun)/3 != $num/4 || !$cards_danpai || count($cards_danpai) != $num/4 )
		{
			return false;
		}
		$res = $this->cardsCheckSanShun($cards_sanshun);
		if ( !$res )
		{
			return false;
		}
		return array('value'=>$res['value'],'array'=>array_merge($cards_sanshun, $cards_danpai));
	}

	//检查牌型：三顺带对 飞机大翅膀
	function cardsCheckSanShunDaiDui($cards)
	{
		if ( !$cards || !is_array($cards) )
		{
			return false;
		}
		$num = count($cards);
		if ( $num < 10 || $num%5 )
		{
			return false;
		}
		$cards_duizi = $cards_sanshun = array();
		$cards_count = array_count_values($cards);
		krsort($cards_count);//防止错误顺序
		foreach ( $cards_count as $k=>$v )
		{
			if ( $v == 4 )
			{
				$cards_duizi[]=$k;
				$cards_duizi[]=$k;
				$cards_duizi[]=$k;
				$cards_duizi[]=$k;
			}
			elseif ( $v == 3 )
			{
				$cards_sanshun[]=$k;
				$cards_sanshun[]=$k;
				$cards_sanshun[]=$k;
			}
			elseif ( $v == 2 )
			{
				$cards_duizi[]=$k;
				$cards_duizi[]=$k;
			}
			else
			{
				return false;
			}
		}
		if ( !$cards_sanshun || count($cards_sanshun)/3 != $num/5 || !$cards_duizi || count($cards_duizi)/2 != $num/5 )
		{
			return false;
		}
		$res = $this->cardsCheckSanShun($cards_sanshun);
		if ( !$res )
		{
			return false;
		}
		return array('value'=>$res['value'],'array'=>array_merge($cards_sanshun, $cards_duizi));
	}

	//检查牌型：四带二单
	function cardsCheckSiDaiErDan($cards)
	{
		if ( !$cards || !is_array($cards) )
		{
			return false;
		}
		$num = count($cards);
		if ( $num != 6 )
		{
			return false;
		}
		$cards_sitiao = $cards_danpai = array();
		$cards_count = array_count_values($cards);
		foreach ( $cards_count as $k=>$v )
		{
			if ( $v == 4 )
			{
				$cards_sitiao[]= $k;
				$cards_sitiao[]= $k;
				$cards_sitiao[]= $k;
				$cards_sitiao[]= $k;
			}
			elseif ( $v == 2 )
			{
				$cards_danpai[]= $k;
				$cards_danpai[]= $k;
			}
			elseif ( $v == 1 )
			{
				$cards_danpai[]= $k;
			}
			else
			{
				return false;
			}
		}
		if ( !$cards_sitiao || count($cards_sitiao) != 4 || !$cards_danpai || count($cards_danpai) != 2 )
		{
			return false;
		}
		return array('value'=>$cards_sitiao[0],'array'=>array_merge($cards_sitiao, $cards_danpai));
	}

	//检查牌型：四带二对
	function cardsCheckSiDaiErDui($cards)
	{
		if ( !$cards || !is_array($cards) )
		{
			return false;
		}
		$num = count($cards);
		if ( $num != 8 )
		{
			return false;
		}
		$cards_sitiao = $cards_duipai = array();
		$cards_count = array_count_values($cards);
		krsort($cards_count);//防止错误顺序
		foreach ( $cards_count as $k=>$v )
		{
			if ( $v == 4 && !$cards_sitiao )
			{
				$cards_sitiao[]= $k;
				$cards_sitiao[]= $k;
				$cards_sitiao[]= $k;
				$cards_sitiao[]= $k;
			}
			elseif ( $v == 4 )
			{
				$cards_duipai[]= $k;
				$cards_duipai[]= $k;
				$cards_duipai[]= $k;
				$cards_duipai[]= $k;
			}
			elseif ( $v == 2 )
			{
				$cards_duipai[]= $k;
				$cards_duipai[]= $k;
			}
			else
			{
				return false;
			}
		}
		if ( !$cards_sitiao || count($cards_sitiao) != 4 || !$cards_duipai || count($cards_duipai) != 4 )
		{
			return false;
		}
		return array('value'=>$cards_sitiao[0],'array'=>array_merge($cards_sitiao, $cards_duipai));
	}

	//检查牌型：四条	炸弹
	function cardsCheckSiTiao($cards)
	{
		if ( !$cards || !is_array($cards) || count($cards) != 4 || !($cards[0] == $cards[1] && $cards[1] == $cards[2] && $cards[2] == $cards[3]) )
		{
			return false;
		}
		return array('value'=>$cards[0],'array'=>$cards);
	}

	//检查牌型：火箭
	function cardsCheckHuojian($cards)
	{
		if ( !$cards || !is_array($cards) || count($cards) != 2 || !($cards[0] == 14 && $cards[1] == 13) )
		{
			return false;
		}
		return array('value'=>$cards[0],'array'=>$cards);
	}

	//
	function cardsGetLevel($type,$len,$value)
	{
		$level = 1;
		switch ( $type )
		{
			case 99:
				$level = 5;
				break;
			case 88:
				$level = 5;
				break;
			case 12:
				$level = $value > 8 ? 5 : 4;
				break;
			case 11:
				$level = $value > 9 ? 5 : 4;
				break;
			case 10:
				$level = $len > 10 ? 5 : ( $value > 7 ? 5 : ( $value > 4 ? 4 : 3 ) );
				break;
			case  9:
				$level = $len > 8 ? 5 : ( $value > 8 ? 5 : ( $value > 5 ? 4 : 3 ) );
				break;
			case  8:
				$level = $len > 6 ? 5 : ( $value > 5 ? 5 : ( $value > 5 ? 4 : 3 ) );
				break;
			case  7:
				$level = $len > 8 ? 5 : ( $len > 6 ? 4 : ( $value > 9 ? 5 : ( $value > 6 ? 4 : ( $value > 3 ? 3 : ( $value > 0 ? 2 : 1 ) ) ) ) );
				break;
			case  6:
				$level = ( $len > 8 || $value == 11 ) ? 5 : ( $len > 7 || $value == 10 ? 4 : ( $len > 5 || $value > 7 ? 3 : ( $value > 5 ? 2 : 1 ) ) );
				break;
			case  5:
				$level = $value > 10 ? 5 : ( $value > 8 ? 4 : ( $value > 5 ? 3 : ( $value > 2 ? 2 : 1 ) ) );
				break;
			case  4:
				$level = $value > 10 ? 5 : ( $value > 8 ? 4 : ( $value > 5 ? 3 : ( $value > 2 ? 2 : 1 ) ) );
				break;
			case  3:
				$level = $value > 10 ? 5 : ( $value > 8 ? 4 : ( $value > 5 ? 3 : ( $value > 2 ? 2 : 1 ) ) );
				break;
			case  2:
				$level = $value > 11 ? 5 : ( $value > 9 ? 4 : ( $value > 6 ? 3 : ( $value > 3 ? 2 : 1 ) ) );
				break;
			case  1:
				$level = $value > 12 ? 5 : ( $value > 10 ? 4 : ( $value > 7 ? 3 : ( $value > 4 ? 2 : 1 ) ) );
				break;
			default:
				break;
		}
		return $level;
	}

	//组装所有可能存在的牌型
	function cardsAllTypes($cards,$position=2)
	{
		if ( !$cards )
		{
			return array();
		}
		switch ( $position )
		{
			case 1:
				if ( $this->prev['alltypes'] )
				{
					return $this->prev['alltypes'];
				}
				break;
			case 2:
				if ( $this->mine['alltypes'] )
				{
					return $this->mine['alltypes'];
				}
				break;
			case 3:
				if ( $this->next['alltypes'] )
				{
					return $this->next['alltypes'];
				}
				break;
			default:
				if ( $this->table['alltypes'] )
				{
					return $this->table['alltypes'];
				}
				break;
		}
		//组装牌型，所有的牌型可能(含四带二)，各种用牌组合，各种重复用牌
		//01 单牌
		$danpai_r = $cards;						//1 单牌[有重复]
		$counts = array_count_values($danpai_r);//1 单牌[在索引]
		$danpai = array_keys($counts);			//1 单牌[不重复]
		foreach ( $danpai as $k=>$v )
		{
			$val = $v>9?$v:('0'.$v);
			$types['0101'.$val] = array($v);
		}
		//99 火箭
		if ( in_array(14,$danpai) && in_array(13,$danpai) )
		{
			$val = '14';
			$types['9902'.$val] = array(14,13);
			$danpai = array_diff($danpai,array(14,13));
		}
		$duipai = $duipai_r = $satiao = $sitiao = array();
		foreach ( $counts as $k=>$v )
		{
			switch ( $v )
			{
				case 4:
					$sitiao[$k] = 1;			//88四条[在索引]
					$satiao[$k] = 1;			//3 三条[在索引]
					$duipai[$k] = 2;			//2 对牌[在索引]
					break;
				case 3:
					$satiao[$k] = 1;			//3 三条[在索引]
					$duipai[$k] = 1;			//2 对牌[在索引]
					break;
				case 2:
					$duipai[$k] = 1;			//2 对牌[在索引]
					break;
				default:
					break;
			}
		}
		//2 对牌
		foreach ( $duipai as $k=>$v )
		{
			$val = $k>9?$k:('0'.$k);
			$types['0202'.$val] = array($k,$k);
			$duipai_r[] = $k;
			if ( $v == 2 ) $duipai_r[] = $k;	//2 对牌[有重复]
		}
		$duipai = array_keys($duipai);			//2 对牌[不重复]
		//3 三不带
		//4 三带单
		//5 三带对
		foreach ( $satiao as $k=>$v )
		{
			$val = $k>9?$k:('0'.$k);
			$types['0303'.$val] = array($k,$k,$k);					//3 三条

			foreach ( $danpai as $kk=>$vv )
			{
				//三带单不能==炸弹
				if ( $k != $vv )
				{
					$types['0404'.($k>9?$k:('0'.$k))] = array($k,$k,$k,$vv);		//4 三带单
				}
			}
			foreach ( $duipai as $kk=>$vv )
			{
				//三带对不能有炸弹
				if ( $k != $vv )
				{
					$types['0505'.($k>9?$k:('0'.$k))] = array($k,$k,$k,$vv,$vv);	//5 三带对
				}
			}
		}
		$satiao = array_keys($satiao);			//3 三条
		//6 单顺
		$types6 = $this->cardsCutShunzi($danpai,5);
		foreach ( $types6 as $k=>$v )
		{
			$len = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$val = floor($k/100);
			$val = $val > 9 ? $val :('0'.$val);
			$types['06'.$len.$val] = $v;
		}
		//7 双顺
		$types7 = $this->cardsCutShunzi($duipai,3);
		foreach ( $types7 as $k=>$v )
		{
			$v = array_merge($v,$v);
			rsort($v);
			$len = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$val = floor($k/100);
			$val = $val > 9 ? $val :('0'.$val);
			$types['07'.$len.$val] = $v;
		}
		//8 三顺不带
		//9 三顺带单
		//10三顺带对
		$types8 = $this->cardsCutShunzi($satiao,2);
		foreach ( $types8 as $k=>$v )
		{
			$len = count($v);
			$v = array_merge($v,$v,$v);
			rsort($v);
			$val = floor($k/100);
			$val = $val > 9 ? $val :('0'.$val);
			$types['08'.($len > 3 ? $len*3 :('0'.$len*3)).$val] = $v;

			$cards_9 = $this->cardsCombine($danpai_r,$len,$v);
			$tmp_9 = array();
			foreach ( $cards_9 as $kk=>$vv )
			{
				$len_ = count($vv);
				$str_ = join('',$vv);
				//不用重复排除
				if ( isset($tmp_9[$str_]) )
				{
					continue;
				}
				$count = array_count_values($vv);
				rsort($count);
				//排除张数过多：	444333-33
				if ( $count[0] >4 )
				{
					unset($cards_9[$kk]);
					continue;
				}
				//排除其他牌型：	444333-43这个牌型归属为四带二对牌型
				if ( !array_diff($count,array(4)) && $len_ == 8 )
				{
					unset($cards_9[$kk]);
					continue;
				}
				//排除其他牌型：	666555444-333这个牌型归属为三顺不带牌型
				if ( !array_diff($count,array(3)) && $len_ == 12 )
				{
					unset($cards_9[$kk]);
					continue;
				}
				$tmp_9[$str_] = $vv;
				$types['09'.($len_>9?$len_:('0'.$len_)).$val] = $vv;
			}
			$cards_10 = $this->cardsCombine($duipai_r,$len,$v,2);
			$tmp_10 = array();
			foreach ( $cards_10 as $kk=>$vv )
			{
				$len_ = count($vv);
				$str_ = join('',$vv);
				//不用重复排除
				if ( isset($tmp_10[$str_]) )
				{
					continue;
				}
				$count = array_count_values($vv);
				rsort($count);
				//排除张数过多：	444333-3322(N) 444555-3333(Y)
				if ( $count[0] >4 )
				{
					unset($cards_10[$kk]);
					continue;
				}
				$tmp_10[$str_] = $vv;
				$types['10'.($len_>9?$len_:('0'.$len_)).$val] = $vv;
			}
		}
		//11四带二单
		//12四带二对
		//88炸弹
		foreach ( $sitiao as $k=>$v )
		{
			$val = $k>9?$k:('0'.$k);
			$v = array($k,$k,$k,$k);
			$types['8804'.$val] = $v;
			$cards_11 = $this->cardsCombine($danpai_r,2,$v);
			$tmp_11 = array();
			foreach ( $cards_11 as $kk=>$vv )
			{
				$str_ = join('',$vv);
				//不用重复排除
				if ( isset($tmp_11[$str_]) )
				{
					continue;
				}
				$count = array_count_values($vv);
				rsort($count);
				//排除张数过多：	3333-32(N)
				if ( $count[0] >4 )
				{
					unset($cards_11[$kk]);
					continue;
				}
				//排除火箭双王：	3333-1413(N)
				if ( in_array(14,$vv) && in_array(13,$vv) )
				{
					unset($cards_11[$kk]);
					continue;
				}
				$types['1106'.$val] = $vv;
				$tmp_11[$str_] = $vv;
			}
			$cards_12 = $this->cardsCombine($duipai_r,2,$v,2);
			$tmp_12 = array();
			foreach ( $cards_12 as $kk=>$vv )
			{
				$str_ = join('',$vv);
				//不用重复排除
				if ( isset($tmp_12[$str_]) )
				{
					continue;
				}
				$count = array_count_values($vv);
				rsort($count);
				//排除张数过多：	3333-3333(N) 4444-3333(Y)
				if ( $count[0] >4 )
				{
					unset($cards_12[$kk]);
					continue;
				}
				//排除大炸弹在后：	4444-3333(Y) 3333-4444(N)
				if ( count($count) == 2 && $count[0] == 4 && $vv[0]<$vv[4] )
				{
					unset($cards_12[$kk]);
					continue;
				}
				$types['1208'.$val] = $vv;
				$tmp_12[$str_] = $vv;
			}
		}
		switch ( $position )
		{
			case 1:
				$this->prev['alltypes'] = $types;
				break;
			case 2:
				$this->mine['alltypes'] = $types;
				break;
			case 3:
				$this->next['alltypes'] = $types;
				break;
			default:
				$this->table['alltypes'] = $types;
				break;
		}
		return $types ;
	}

	//组装叫牌牌型
	function cardsBidTypes($cards,$position=2)
	{
		if ( !$cards )
		{
			return array();
		}
		switch ( $position )
		{
			case 1:
				if ( $this->prev['bidtypes'] )
				{
					return $this->prev['bidtypes'];
				}
				break;
			case 2:
				if ( $this->mine['bidtypes'] )
				{
					return $this->mine['bidtypes'];
				}
				break;
			case 3:
				if ( $this->next['bidtypes'] )
				{
					return $this->next['bidtypes'];
				}
				break;
			default:
				if ( $this->table['bidtypes'] )
				{
					return $this->table['bidtypes'];
				}
				break;
		}
		//组装牌型，所有的牌型可能(含四带二)，
		//各个主牌之间可重复用牌，如果能带，则尽量只带最小副牌(单牌/对牌)
		$danpai = $duipai = $satiao = $sitiao = $danpai_r = $duipai_r = $satiao_r = $sitiao_u = $satiao_u = $duipai_u = $danpai_u = $sitiao_b3 = $sitiao_b2 = $sitiao_b1 = $satiao_b2 = $satiao_b1 = $duipai_b1 = array();
		$counts = array_count_values($cards);
		foreach ( $counts as $k=>$v )
		{
			switch ( $v )
			{
				case 4:
					$sitiao[] = $k;	//88四条
					$satiao_r[$k] = $v;	//3 三条
					$duipai_r[$k] = $v;	//2 对牌
					$danpai_r[$k] = $v;	//1 单牌
					break;
				case 3:
					$satiao[] = $k;	//3 三条
					$duipai_r[$k] = $v;	//2 对牌
					$danpai_r[$k] = $v;	//1 单牌
					break;
				case 2:
					$duipai[] = $k;	//2 对牌
					$danpai_r[$k] = $v;	//1 单牌
					break;
				case 1:
					$danpai[] = $k;	//1 单牌
					break;
				default:
					break;
			}
		}
		if ( $sitiao ) rsort($sitiao);//倒序
		if ( $satiao ) rsort($satiao);//倒序
		if ( $duipai ) sort($duipai);//正序
		if ( $danpai ) sort($danpai);//正序
		if ( $satiao_r ) krsort($satiao_r);
		if ( $duipai_r ) ksort($duipai_r);
		if ( $danpai_r ) ksort($danpai_r);

		//99 火箭
		if ( in_array(14,$danpai) && in_array(13,$danpai) )
		{
			$danpai = array_diff($danpai,array(14,13));
			$val = '14';
			$types['9902'.$val] = array(14,13);
		}
		//88 炸弹
		foreach ( $sitiao as $k=>$v )
		{
			$sitiao_u[] = $v;
			$val = $v>9?$v:('0'.$v);
			$types['8804'.$val] = array($v,$v,$v,$v);
		}
		//08 三顺[不拆炸弹，用三条]
		$types08 = $this->cardsCutShunzi($satiao,2);
		foreach ( $types08 as $k=>$v )
		{
			$u_satiao = array_intersect($v,$satiao);
			if ( $u_satiao )
			{
				$satiao_u = array_merge($u_satiao,$satiao_u);
				$satiao = array_diff($satiao,$u_satiao);
			}
			$len = count($v);
			$v = array_merge($v,$v,$v);
			rsort($v);
			$val = floor($k/100);
			$val = $val > 9 ? $val :('0'.$val);
			$types['08'.($len > 3 ? $len*3 :('0'.$len*3)).$val] = $v;
		}
		//07 双顺[不拆炸弹，不拆三顺，拆三条，用对牌]
		$_tmp = array_merge($satiao,$duipai);
		if ( $_tmp )
		{
			$_tmp = array_unique($_tmp);
			rsort($_tmp);
		}
		$types07 = $this->cardsCutShunzi($_tmp,3);
		$shuangshun = array();
		foreach ( $types07 as $k=>$v )
		{
			$u_duipai = array_intersect($v,$duipai);
			if ( $u_duipai )
			{
				$duipai_u = array_merge($u_duipai,$duipai_u);
				$duipai = array_diff($duipai,$u_duipai);
			}
			$u_satiao = array_intersect($v,$satiao);
			if ( $u_satiao )
			{
				$satiao_u = array_merge($u_satiao,$satiao_u);
				$satiao = array_diff($satiao,$u_satiao);
				//拆开的三条剩余归单牌
				foreach ( $u_satiao as $kk=>$vv )
				{
					$danpai[] = $vv;
				}
			}
			$v = array_merge($v,$v);
			rsort($v);
			$len = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$val = floor($k/100);
			$val = $val > 9 ? $val :('0'.$val);
			$types['07'.$len.$val] = $v;
			$shuangshun[] = $v;
		}
		//06 单顺[不拆炸弹，不拆三顺，不拆双顺，拆三条，拆对子，用单牌]
		$_tmp = array_merge($satiao,$duipai,$danpai);
		if ( $_tmp )
		{
			$_tmp = array_unique($_tmp);
			rsort($_tmp);
		}
		$types06 = $this->cardsCutShunzi($_tmp,5);
		$types_ = $danshun = array();
		foreach ( $types06 as $k=>$v )
		{
			if ( count(array_intersect($types_,$v)) > 2 )//只取一个长顺即可，3即为双顺了
			{
				continue;
			}
			else
			{
				$types_ = array_merge($types_,$v);
			}
			$u_danpai = array_intersect($v,$danpai);
			$u_duipai = array_intersect($v,$duipai);
			$u_satiao = array_intersect($v,$satiao);
			//牌艰不拆。不能拆太多三条/对牌
			if ( count($u_satiao) >= (count($v)/3+0) || count($u_duipai) > (count($v)/2+0) )
			{
				continue;
			}
			if ( $u_danpai )
			{
				$danpai_u = array_merge($u_danpai,$danpai_u);
				$danpai = array_diff($danpai,$u_danpai);
			}
			if ( $u_duipai )
			{
				$duipai_u = array_merge($u_duipai,$duipai_u);
				$duipai = array_diff($duipai,$u_duipai);
				//拆开的对子剩余归单牌
				foreach ( $u_duipai as $kk=>$vv )
				{
					$danpai[] = $vv;
				}
			}
			if ( $u_satiao )
			{
				$satiao_u = array_merge($u_satiao,$satiao_u);
				$satiao = array_diff($satiao,$u_satiao);
				//拆开的三条剩余归对牌
				foreach ( $u_satiao as $kk=>$vv )
				{
					$duipai[] = $vv;
				}
			}
			$len = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$val = floor($k/100);
			$val = $val > 9 ? $val :('0'.$val);
			$types['06'.$len.$val] = $v;
			$danshun[] = $v;
		}
		//长度足够的，且头部或尾部在三条里面：缩短顺子，恢复三条
		foreach ( $danshun as $k=>$v )
		{
			//先断尾三条
			$len = $len_ = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$max_ = max($v);
			$min_ = min($v);
			if ( $duipai && $len_ > 5 && in_array($min_,$duipai) )
			{
				$val = $max_ > 9 ? $max_ :('0'.$max_);
				unset($types['06'.$len.$val]);
				$len_ -=1;
				$len_ = $len_ > 9 ? $len_ :('0'.$len_);
				$duipai = array_diff($duipai,array($min_));
				$satiao[] = $min_;
				array_pop($v);
				$danshun[$k] = $v;
				$types['06'.$len_.$val] = $v;
			}
			//再断头三条
			$len = $len_ = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$max_ = max($v);
			if ( $duipai && $len_ > 5 && in_array($max_,$duipai) )
			{
				$val = $max_ > 9 ? $max_ :('0'.$max_);
				unset($types['06'.$len.$val]);
				$len_ -=1;
				$len_ = $len_ > 9 ? $len_ :('0'.$len_);
				$val_ = $k-1;
				$val_ = $val_ > 9 ? $val_ :('0'.$val_);
				$duipai = array_diff($duipai,array($max_));
				$satiao[] = $max_;
				array_shift($v);
				$danshun[$k] = $v;
				$types['06'.$len_.$val_] = $v;
			}
		}
		foreach ( $shuangshun as $k=>$v )
		{
			//先断尾三条
			$len = $len_ = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$max_ = max($v);
			$min_ = min($v);
			if ( $danpai && $len_ > 6 && in_array($min_,$danpai) )
			{
				$val = $max_ > 9 ? $max_ :('0'.$max_);
				unset($types['07'.$len.$val]);
				$len_ -=2;
				$len_ = $len_ > 9 ? $len_ :('0'.$len_);
				$danpai = array_diff($danpai,array($min_));
				$satiao[] = $min_;
				array_pop($v);
				array_pop($v);
				$shuangshun[$k] = $v;
				$types['07'.$len_.$val] = $v;
			}
			//再断头三条
			$len = $len_ = count($v);
			$len = $len > 9 ? $len :('0'.$len);
			$max_ = max($v);
			$min_ = min($v);
			if ( $danpai && $len_ > 6 && in_array($max_,$danpai) )
			{
				$val = $max_ > 9 ? $max_ :('0'.$max_);
				unset($types['07'.$len.$val]);
				$len_ -=2;
				$len_ = $len_ > 9 ? $len_ :('0'.$len_);
				$val_ = $max_-1;
				$val_ = $val_ > 9 ? $val_ :('0'.$val_);
				$danpai = array_diff($danpai,array($max_));
				$satiao[] = $max_;
				array_shift($v);
				array_shift($v);
				$shuangshun[$k] = $v;
				$types['07'.$len_.$val_] = $v;
			}
		}
		//下面带牌不用上面已经用过的单牌对牌
		if ( $satiao ) sort($satiao);
		if ( $duipai ) sort($duipai);
		if ( $danpai ) sort($danpai);
		$duipai_len = count($duipai);
		$danpai_len = count($danpai);
		//11 四带二单
		//12 四带二对
		foreach ( $sitiao as $k=>$v )
		{
			$val = $v>9?$v:('0'.$v);
			//带上两个单
			for ( $i=0; $i < $danpai_len; $i++ )
			{
				if ( isset($danpai[$i+1]) && $danpai[$i] != $v && $danpai[$i+1] != $v )
				{
					$types['1106'.$val] = array($v,$v,$v,$v,$danpai[$i],$danpai[$i+1]);
					//带上的牌归为拆拆牌
					$types['0001'.($danpai[$i]>9?$danpai[$i]:('0'.$danpai[$i]))] = array($danpai[$i]);
					$types['0001'.($danpai[$i+1]>9?$danpai[$i+1]:('0'.$danpai[$i+1]))] = array($danpai[$i+1]);
					break;
				}
			}
			//带上两个对，且非33333333、33334444之类，44443333是可以的
			for ( $i=0; $i < $duipai_len; $i++ )
			{
				if ( isset($duipai[$i+1]) && $duipai[$i] != $v && $duipai[$i+1] != $v && !($duipai[$i] == $duipai[$i+1] && $duipai[$i] >= $v ) )
				{
					$types['1208'.$val] = array($v,$v,$v,$v,$duipai[$i],$duipai[$i],$duipai[$i+1],$duipai[$i+1]);
					//带上的牌归为拆拆牌
					$types['0002'.($duipai[$i]>9?$duipai[$i]:('0'.$duipai[$i]))] = array($duipai[$i]);
					$types['0002'.($duipai[$i+1]>9?$duipai[$i+1]:('0'.$duipai[$i+1]))] = array($duipai[$i+1]);
					break;
				}
			}
		}
		//03 三不带
		//04 三带单
		//05 三带对
		foreach ( $satiao as $k=>$v )
		{
			$val = $v>9?$v:('0'.$v);
			$is_bind = 0;
			//带上一个单
			for ( $i=0; $i < $danpai_len; $i++ )
			{
				if ( $danpai[$i] != $v )
				{
					$types['0404'.$val] = array($v,$v,$v,$danpai[$i]);
					//带上的牌归为拆拆牌
					$types['0001'.($danpai[$i]>9?$danpai[$i]:('0'.$danpai[$i]))] = array($danpai[$i]);
					$is_bind = 1;
					break;
				}
			}
			//带上一个对，且非33333之类
			for ( $i=0; $i < $duipai_len; $i++ )
			{
				if ( $duipai[$i] != $v )
				{
					$types['0505'.$val] = array($v,$v,$v,$duipai[$i],$duipai[$i]);
					//带上的牌归为拆拆牌
					$types['0002'.($duipai[$i]>9?$duipai[$i]:('0'.$duipai[$i]))] = array($duipai[$i]);
					$is_bind = 1;
					break;
				}
			}
			if ( $is_bind )
			{
				//拆拆牌
				$types['0003'.$val] = array($v,$v,$v);
			}
			else
			{
				//正常三条
				$types['0303'.$val] = array($v,$v,$v);
			}
		}
		//09 三顺带单
		//10 三顺带对
		foreach ( $types08 as $k=>$v )
		{
			$len = count($v);
			$v = array_merge($v,$v,$v);
			rsort($v);
			$val = floor($k/100);
			$val = $val > 9 ? $val :('0'.$val);
			//带上$len个单
			if ( $danpai_len >= $len )
			{
			for ( $i=0; $i < $danpai_len; $i++ )
			{
				$_v = $v;
				for ( $j=0; $j < $len; $j++)
				{
					if ( isset($danpai[$i+$j]) )
					{
					$_v[]=$danpai[$i+$j];
					}
				}
				$_count = array_count_values($_v);
				rsort($_count);
				$_len = array_sum($_count);
				if ( !( $_count[0] > 4 || (!array_diff($_count,array(4)) && $_len == 8) || (!array_diff($_count,array(3)) && $_len == 12) ) )
				{
					$types['09'.($_len>9?$_len:('0'.$_len)).$val] = $_v;
					break;
				}
			}
			}
			//带上$len个对
			if ( $duipai_len >= $len )
			{
			for ( $i=0; $i < $duipai_len; $i++ )
			{
				$_v = $v;
				for ( $j=0; $j < $len; $j++)
				{
					if ( isset($duipai[$i+$j]) )
					{
					$_v[]=$duipai[$i+$j];
					$_v[]=$duipai[$i+$j];
					}
				}
				$_count = array_count_values($_v);
				rsort($_count);
				$_len = array_sum($_count);
				if ( !( $_count[0] > 4 ) )
				{
					$types['10'.($_len>9?$_len:('0'.$_len)).$val] = $_v;
					break;
				}
			}
			}
		}
		//01 单牌
		foreach ( $danpai as $k=>$v )
		{
			$val = $v>9?$v:('0'.$v);
			$types['0101'.$val] = array($v);
		}
		//02 对牌
		foreach ( $duipai as $k=>$v )
		{
			$val = $v>9?$v:('0'.$v);
			$types['0202'.$val] = array($v,$v);
		}
		//没有对牌时 先拆最大三条
		if ( !$duipai && $satiao )
		{
			rsort($satiao);
			$tmp_ = reset($satiao);
			$v = $tmp_;
			$val = $v>9?$v:('0'.$v);
			$types['0002'.$val] = array($v,$v);
			$duipai[]=$v;
		}
		//没有对牌时 再拆最大双顺顶部
		if ( !$duipai && $shuangshun )
		{
			krsort($shuangshun);
			$tmp_ = reset($shuangshun);
			$v = reset($tmp_);
			$val = $v>9?$v:('0'.$v);
			$types['0002'.$val] = array($v,$v);
			$duipai[]=$v;
		}
		//没有单牌时 先拆最大对牌
		if ( !$danpai && $duipai )
		{
			krsort($duipai);
			$tmp_ = reset($duipai);
			$v = $tmp_;
			$val = $v>9?$v:('0'.$v);
			$types['0001'.$val] = array($v);
			$danpai[]=$v;
		}
		//没有单牌时 先拆最大三条
		if ( !$danpai && $satiao )
		{
			rsort($satiao);
			$tmp_ = reset($satiao);
			$v = $tmp_;
			$val = $v>9?$v:('0'.$v);
			$types['0001'.$val] = array($v);
			$danpai[]=$v;
		}
		//没有单牌时 再拆最大单顺
		if ( !$danpai && $danshun )
		{
			krsort($danshun);
			$shunzi = reset($danshun);
			$v = reset($shunzi);
			$val = $v>9?$v:('0'.$v);
			$types['0001'.$val] = array($v);
			$danpai[]=$v;
		}
		//最后肯定拆火箭
		if ( isset($types['990214']) )
		{
			$types['000114'] = array('14');
			$types['000113'] = array('13');
			$danpai[] = 14;
			$danpai[] = 13;
		}
		switch ( $position )
		{
			case 1:
				$this->prev['bidtypes'] = $types;
				break;
			case 2:
				$this->mine['bidtypes'] = $types;
				break;
			case 3:
				$this->next['bidtypes'] = $types;
				break;
			default:
				$this->table['bidtypes'] = $types;
				break;
		}
		return $types ;
	}

	//组装最优牌型
	function cardsBestTypes($cards,$position=2)
	{
		if ( !$cards )
		{
			return array();
		}
		switch ( $position )
		{
			case 1:
				if ( $this->prev['besttypes'] )
				{
					return $this->prev['besttypes'];
				}
				break;
			case 2:
				if ( $this->mine['besttypes'] )
				{
					return $this->mine['besttypes'];
				}
				break;
			case 3:
				if ( $this->next['besttypes'] )
				{
					return $this->next['besttypes'];
				}
				break;
			default:
				if ( $this->table['besttypes'] )
				{
					return $this->table['besttypes'];
				}
				break;
		}
		$counts = array_count_values($cards);
		$danpai = $duipai = $satiao = $sitiao = array();
		foreach ( $counts as $k=>$v )
		{
			switch ( $v )
			{
				case 4:
					$sitiao[] = $k;
					break;
				case 3:
					$satiao[] = $k;
					break;
				case 2:
					$duipai[] = $k;
					break;
				case 1:
					$danpai[] = $k;
					break;
				default:
					return array();
					break;
			}
		}
		//组装牌型，以下牌型的牌面不能重复
		//99 火箭
		if ( $danpai[0] == 14 && $danpai[1] == 13 )
		{
			$types['990214'][] = array(14,13);
			$danpai = array_diff($danpai,array(14,13));
		}
		//88 炸弹
		foreach ( $sitiao as $k=>$v )
		{
			$types['8804'.($v>9?$v:('0'.$v))][] = array($v,$v,$v,$v);
			$danpai = array_diff($danpai,array($v));
		}
		//06 单顺
		$type06 = $this->cardsCutShunzi($danpai,5,true);
		foreach ( $type06 as $k=>$v )
		{
			$len = $k%100+0;
			$len = $len>9?$len:('0'.$len);
			$val = floor($k/100);
			$val = $val>9?$val:('0'.$val);
			$types['06'.$len.$val][] = $v;
			$danpai = array_diff($danpai,$v);
		}
		//07 双顺
		$types07 = $this->cardsCutShunzi($duipai,3,true);
		foreach ( $types07 as $k=>$v )
		{
			$len = ($k%100+0)*2;
			$len = $len>9?$len:('0'.$len);
			$val = floor($k/100);
			$val = $val>9?$val:('0'.$val);
			$v = array_merge($v,$v);
			rsort($v);
			$types['07'.$len.$val][] = $v;
			$duipai = array_diff($duipai,$v);
		}
		//08 三顺
		$type08 = $this->cardsCutShunzi($satiao,2,true);
		$sanshun = array();
		foreach ( $type08 as $k=>$v )
		{
			$sanshun[]=$v;
			$satiao = array_diff($satiao,$v);
		}
		//10 三顺带对
		foreach ( $sanshun as $k=>$v )
		{
			$val = $v[0];
			$val = $val>9?$val:('0'.$val);
			$sanshun_len = count(array_unique($v));
			if ( $duipai && count($duipai) >= $sanshun_len )
			{
				$duipai_ = $duipai;
				sort($duipai_);
				$_v = array();
				for ( $i=0; $i < $sanshun_len; $i++ )
				{
					$_v[] = $duipai[$i];
					$_v[] = $duipai[$i];
				}
				$v = array_merge($v,$v,$v);
				rsort($v);
				$types['10'.($sanshun_len > 1 ? '' : '0').($sanshun_len*5).$val][] = array_merge($v,$_v);
				$duipai = array_diff($duipai,$_v);
				unset($sanshun[$k]);
			}
		}
		//09 三顺带单
		foreach ( $sanshun as $k=>$v )
		{
			$val = $v[0];
			$val = $val>9?$val:('0'.$val);
			$sanshun_len = count(array_unique($v));
			if ( $danpai && count($danpai) >= $sanshun_len )
			{
				$danpai_ = $danpai;
				sort($danpai_);
				$_v = array();
				for ( $i=0; $i < $sanshun_len; $i++ )
				{
					$_v[] = $danpai[$i];
				}
				$v = array_merge($v,$v,$v);
				rsort($v);
				$types['09'.($sanshun_len > 2 ? '' : '0').($sanshun_len*4).$val][] = array_merge($v,$_v);
				$danpai = array_diff($danpai,$_v);
				unset($sanshun[$k]);
			}
		}
		//08 三顺
		foreach ( $sanshun as $k=>$v )
		{
			$val = $v[0];
			$val = $val>9?$val:('0'.$val);
			$sanshun_len = count(array_unique($v));
				$v = array_merge($v,$v,$v);
				rsort($v);
				$types['08'.($sanshun_len > 3 ? '' : '0').($sanshun_len*3).$val][] = $v;
		}
		//05 三带二
		foreach ( $satiao as $k=>$v )
		{
			if ( $duipai )
			{
				$duipai_ = $duipai;
				sort($duipai_);
				$types['0505'.($v>9?$v:('0'.$v))][] = array($v,$v,$v,$duipai[0],$duipai[0]);
				unset($satiao[$k]);
				$duipai = array_diff($duipai,array($duipai[0]));
			}
		}
		//04 三带一
		foreach ( $satiao as $k=>$v )
		{
			if ( $danpai )
			{
				$danpai_ = $danpai;
				sort($danpai_);
				$types['0404'.($v>9?$v:('0'.$v))][] = array($v,$v,$v,$danpai[0]);
				unset($satiao[$k]);
				$danpai = array_diff($danpai,array($danpai[0]));
			}
		}
		//03 三条
		foreach ( $satiao as $k=>$v )
		{
			$types['0303'.($v>9?$v:('0'.$v))][] = array($v,$v,$v);
		}
		//02 对牌
		foreach ( $duipai as $k=>$v )
		{
			$types['0202'.($v>9?$v:('0'.$v))][] = array($v,$v);
		}
		//01 单牌
		foreach ( $danpai as $k=>$v )
		{
			$types['0101'.($v>9?$v:('0'.$v))][] = array($v);
		}

		switch ( $position )
		{
			case 1:
				$this->prev['besttypes'] = $types;
				break;
			case 2:
				$this->mine['besttypes'] = $types;
				break;
			case 3:
				$this->next['besttypes'] = $types;
				break;
			default:
				$this->table['besttypes'] = $types;
				break;
		}
		return $types ;
	}

	//排列组合-组合算法
	//$arr		目标数组
	//$len		从目标数组取元素的个数
	//$pre		前缀数组，在每组元素之前，追加元素。
	//$times	针对$arr的功能为对牌时所用，比如：33322
	//return	组合好的数组(含前缀)集合
	function cardsCombine( $arr, $len=0, $pre=array(), $times=1, $tmp=array(), $res=array() )
	{
		$len_ = count($arr);
		if( $len )
		{
			$k = array();
			for($i=0; $i<$len_-$len+1; $i++){
				$tmp_ = array_shift($arr);
				$arr_ = array();
				for ( $j=0; $j < $times; $j++)
				{
					$arr_[] = $tmp_;
				}
				$res = $this->cardsCombine( $arr, $len-1, $pre, $times, array_merge($tmp,$arr_), $res );
			}
		}
		else
		{
			$res[] = array_merge($pre,$tmp);
		}
		return $res;
	}

	//切开各种顺子
	//$cards	用于切开的各种单顺双顺三顺
	//$minlen	最小有效长度
	//$is_long	只取最长顺子，默认否
	//return	切好的顺子数组//key==1008, 以K(10)开头的8张不同牌的顺子
	function cardsCutShunzi($cards,$minlen,$is_long=false)
	{
		rsort($cards);
		$cards_ = array();
		foreach ( $cards as $v )
		{
			if ( in_array($v,array(14,13,12)) )
			{
				continue;
			}
			if ( isset($cards_[$v+1]) )
			{
				continue;
			}
			switch ( $minlen )
			{
				case 2:
					if ( !isset($cards_[$v]) && in_array($v-1,$cards) )
					{
						$cards_[$v] = $v-1;
					}
					break;
				case 3:
					if ( !isset($cards_[$v]) && in_array($v-1,$cards) && in_array($v-2,$cards) )
					{
						$cards_[$v] = $v-2;
					}
					break;
				case 5:
					if ( !isset($cards_[$v]) && in_array($v-1,$cards) && in_array($v-2,$cards) && in_array($v-3,$cards) && in_array($v-4,$cards)  )
					{
						$cards_[$v] = $v-4;
					}
					break;
				default:
					break;
			}
		}
		//获取最长顺子
		foreach ( $cards as $k=>$v )
		{
			foreach ( $cards_ as $start=>$end )
			{
				if ( $end == $v+1 )
				{
					$cards_[$start] = $v;
				}
			}
		}
		$cards = array();
		//只取最长顺子
		if ( $is_long )
		{
			foreach ( $cards_ as $start=>$end )
			{
				$cards[$start*100+$start-$end+1] = range($start,$end);
			}
		}
		//切顺子
		else
		{
			foreach ( $cards_ as $start=>$end )
			{
				$offset = $start-$end-$minlen+1;
				for ( ; $offset >= 0; $offset-- )
				{
					//key==1008, 以K(10)开头的8张牌的顺子
					$cards[$start*100+$start-$end-$offset+1] = range($start,$end+$offset);
					$start2 = $start-$offset;
					$offset2 = $start2 - $end - $minlen+1;
					for ( ; $offset2 >=0 ; $offset2--)
					{
						$cards[$start2*100+$start2-$end-$offset2+1] = range($start2,$end+$offset2);
					}
				}
			}
		}
		if ( $cards )
		{
			krsort($cards);
		}
		return $cards;
	}

	//适配牌型	返回可以叫牌/跟牌的牌型组合
	//$hands	被动适配者的手牌
	//$position	被动适配者的位置0未知1上家2自己3下家
	//$cards	叫牌者的叫牌牌型，如果没有，则直接返回出牌者的所有牌型
	function cardsViable($hands,$position=2,$cards=array(),$is_all = false)
	{
		$viable = array();
		//跟牌
		if ( $cards )
		{
			//跟牌牌型
			$cardstypes = $is_all ? $this->cardsAllTypes($hands,$position) : $this->cardsBidTypes($hands,$position);
			//检查叫牌方的牌型
			$bid = $this->cardsCheck($cards);
			if ( $bid['type'] == 99 && $position == 2 )
			{
				return $viable;
			}
			elseif ( $bid['type'] == 88 && $position == 2 )
			{
				//更大炸弹
				$tmp = $bid['type'].$bid['len'].$bid['value'];
				foreach ( $cardstypes as $k=>$v )
				{
					if ( $k > $tmp )
					{
						$viable[$k] = $v;
					}
				}
				return $viable;
			}
			elseif ( $bid['type'] )
			{
				$tmp = $bid['type'].$bid['len'];
				foreach ( $cardstypes as $k=>$v )
				{
					$tlv = str_split($k,2);
					$tmp2 = $tlv[0].$tlv[1];
					if ( $k == 990214 || $tmp2 == 8804 || ($tmp2 == $tmp && $tlv[2] > $bid['value'] ) || (!$tlv[0] && $tlv[1] == $bid['len'] && $tlv[2] > $bid['value'] ) )
					{
						$viable[$k] = $v;
					}
				}
			}
		}
		//叫牌
		else
		{
			//主牌牌型
			$cardstypes = $this->cardsBidTypes($hands,$position);
			$viable = $cardstypes;
		}
		if ( $viable )
		{
			krsort($viable);
		}
		return $viable;
	}

	//叫牌/跟牌逻辑
	function cardsLogic()
	{
		$aiSkill = $this->aiSkill;
		$table = $this->table;
		$prev = $this->prev;
		$next = $this->next;
		$mine = $this->mine;
		$is_self_bidr = $mine['is_bidder'];				//1我来叫牌,0我是跟牌
		$is_self_lord = $mine['is_lord'];				//1我是地主,0我是农民
		$is_bidr_rival = !!(!$is_self_bidr && ($is_self_lord || ($table['bidder']==$prev['pos'] && $prev['is_lord']) || ($table['bidder']==$next['pos'] && $next['is_lord'])));	//1对手正在叫牌,0非对手或非叫
		$is_next_rival = !!($is_self_lord || $next['is_lord']);	//1下家对手,0下家同伴
		//print_r($is_self_lord?'地主':'农民');
		//print_r($is_self_bidr?'叫牌':'跟牌');
		//print_r($is_bidr_rival?'/对手正在叫牌/':'/非对手或非叫/');
		//print_r($is_next_rival?'下家是对手':'下家是同伴');
		$is_remainder = $aiSkill['is_remainder'];	//通过对方手牌数量来计算回手牌
		$is_remember  = $aiSkill['is_remember'];	//通过获知桌牌牌面来计算回手牌
		$is_peekother = $aiSkill['is_peekother'];	//通过偷看对手手牌来计算回手牌
		$is_unpack = $aiSkill['is_unpack'];			//可以被迫拆牌，来跟对手牌或抬同伴牌
		$is_betout = $aiSkill['is_betout'];			//[手牌]只剩下两手时，直接先出[最大牌]以博取牌权,并不再计算其他的牌型组合
		$is_bigout = $aiSkill['is_bigout'];			//[预选牌]中只有一手不是[回手牌]时，先出最小的[回手牌]
		$is_getrole= $aiSkill['is_getrole'];		//角色识别，对上下家角色使用不同的出牌逻辑
		$is_giveup = $aiSkill['is_giveup'];			//下家为农民同伴时，如果知道下家必赢，以下家需要牌型放水。
		$is_unsame = $aiSkill['is_unsame'];			//下家为对手且手牌不多时，尽可能不放同牌型的。
		$mine_hand_dec = $this->cardsDec($mine['hand']);
		$prev_hand_dec = $this->cardsDec($prev['hand']);
		$next_hand_dec = $this->cardsDec($next['hand']);
		$table_bid_dec = $this->cardsDec($table['cards']);
		$table_out_dec = $this->cardsDec($table['out']);
		$table_bid_type= $this->cardsCheck($table_bid_dec);//叫牌牌型
		$bidr_hand_dec = ($table['bidder'] == $mine['pos'] ? $mine_hand_dec : ( $table['bidder'] == $prev['pos'] ? $prev_hand_dec : $next_hand_dec ));
		//$count_outs = array_count_values($table_out_dec);
		$min = min($mine_hand_dec);
		$max = max($mine_hand_dec);
		$num_bidr_hand = count($bidr_hand_dec);
		$num_prev_hand = count($prev_hand_dec);
		$num_next_hand = count($next_hand_dec);
		$num_mine_hand = count($mine_hand_dec);
		$num_bid_cards = count($table_bid_dec);

		$viables = $viable = $this->cardsViable($mine_hand_dec,2,$table_bid_dec);
		//$alltype= $table_bid_dec ? $this->cardsViable($mine_hand_dec,2,array()) : $viable;

		//无候选时，且对手叫牌，且对手手牌过少时，尝试拆牌
		$cards = array();
		$is_break = false;
		if ( !$viable )
		{
			//要不起-拆牌要
			if ( $is_unpack && !$is_self_bidr && $is_bidr_rival && $num_bidr_hand < 6 )
			{
				$viable = $this->cardsViable($mine_hand_dec,2,$table_bid_dec,1);
				if ( !$viable )
				{
					//print_r('“要不起-拆不掉”');
					return $cards;
				}
				else
				{
					$is_break = true;
				}
			}
			else
			{
				//print_r('“要不起-直接过”');
				return $cards;
			}
		}
		//规整预选牌，规整同时可依据技能直接返回牌型
		//优先牌		必胜牌		   回手牌		  起手牌		 其他牌			拆开牌
		$cards_first = $cards_ender = $cards_rebid = $cards_start = $cards_other = $cards_break = array();
		$levels = $viablecards = array();
		//获取回手牌
		foreach ( $viable as $k=>$v )
		{
			$viablecards = array_merge($viablecards,$v);
			$tlv = str_split($k,2);
			$levels[$k] = $this->cardsGetLevel($tlv[0],$tlv[1],$tlv[2]);
			//拆开的有效单牌/对牌/三条，尽量不使用
			if ( $tlv[0] == 0 && ($tlv[1] == 1 || $tlv[1] == 2 || $tlv[1] == 3) )
			{
				$cards_break[$k] = $v;
				unset($viable[$k]);
				continue;
			}
			$is_unset = false;
			//优先牌 可与其他牌型组合重合
			if ( $tlv[0] == 6 && ( $num_mine_hand < 10 || !($tlv[2] == 11) ) )
			{
				//单顺
				$cards_first[$k] = $v;
			}
			elseif ( $tlv[0] == 7 && ( $num_mine_hand < 12 || !($tlv[2] == 11) ) )
			{
				//双顺
				$cards_first[$k] = $v;
			}
			elseif ( in_array($tlv[0],array('09','10')) && ( $num_mine_hand < 14 || !in_array($tlv[2],array('09','10','11')) ) )
			{
				//三顺
				$cards_first[$k] = $v;
			}
			elseif ( in_array($tlv[0],array('04','05')) && ( $tlv[2] < 7 || $num_mine_hand < 8 || !array_intersect(array(11,12,13,14),$v) ) )
			{
				//三带
				$cards_first[$k] = $v;
			}
			//必胜牌
			if ( $tlv[0] == 99 )
			{
				//火箭必胜
				$cards_ender[$k] = $v;
				$is_unset = true;
			}
			elseif ( ($is_peekother || $is_remember) && !($this->cardsViable($prev_hand_dec,1,$v,1) || $this->cardsViable($next_hand_dec,3,$v,1)) )
			{
				//偷牌记牌 算出必胜
				$cards_ender[$k] = $v;
				$is_unset = true;
			}
			elseif ( false && $is_remember )//不做单独处理
			{
				//记牌
				$cards_ender[$k] = $v;
				$is_unset = true;
			}
			elseif ( $is_remainder && count($prev_hand_dec) < $tlv[1] && count($next_hand_dec) < $tlv[1] )
			{
				//算牌算出必胜(依据长度计算)
				$cards_ender[$k] = $v;
				$is_unset = true;
			}
			 //回手牌
			elseif ( $tlv[0] == 88 )
			{
				//炸弹默认回手
				$cards_rebid[$k] = $v;
				$is_unset = true;
			}
			elseif ( $tlv[0] == 12 || $tlv[0] == 11 )
			{
				//四带二默认回手
				$cards_rebid[$k] = $v;
				$is_unset = true;
			}
			elseif ( $tlv[2] == 14 )
			{
				//大王默认回手
				$cards_rebid[$k] = $v;
				$is_unset = true;
			}
			elseif ( $tlv[2] == 13 && in_array(14,$table_out_dec) )//这个逻辑暂用，在$is_remember完成之后抹掉
			{
				//小王默认回手
				$cards_rebid[$k] = $v;
				$is_unset = true;
			}
			elseif ( $tlv[2] == 12 && $tlv[1] > 1 )//这个逻辑暂用，在$is_remember完成之后抹掉
			{
				//22/222默认回手
				$cards_rebid[$k] = $v;
				$is_unset = true;
			}
			elseif ( $tlv[2] == 11 && in_array(intval($tlv[0]),range(6,10)) )
			{
				//单顺/双顺/三顺/飞机
				$cards_rebid[$k] = $v;
				$is_unset = true;
			}
			if ( $is_unset )
			{
				unset($viable[$k]);
			}
		}
		//获取起手牌
		foreach ( $viable as $k=>$v )
		{
			foreach ( $cards_ender as $kk=>$vv )
			{
				$tlv = str_split($kk,2);
				if ( $tlv[0] == 99 || $tlv[0] == 88 )
				{
					continue;//不对火箭/炸弹做匹配
				}
				if ( $this->cardsCompare($v,$vv) == 2 )
				{
					$cards_start[$k] = $v;
					unset($viable[$k]);
					break;
				}
			}
			foreach ( $cards_rebid as $kk=>$vv )
			{
				$tlv = str_split($kk,2);
				if ( $tlv[0] == 99 || $tlv[0] == 88 )
				{
					continue;//不对火箭/炸弹做匹配
				}
				if ( $this->cardsCompare($v,$vv) == 2 )
				{
					$cards_start[$k] = $v;
					unset($viable[$k]);
					break;
				}
			}
		}
		//获取其他牌
		foreach ( $viable as $k=>$v )
		{
			$cards_other[$k] = $v;
			unset($viable[$k]);
		}
		if ( $cards_start )
		{
			krsort($cards_start);
		}

		//出牌逻辑

		//大牌先出 -> 打出春天
		$is_myspring = false;
		if ( $is_bigout && $is_self_bidr )
		{
			$_cards_ender = $cards_ender;
			$_cards_rebid = $cards_rebid;
			$_cards_start = $cards_start;
			$_cards_other = $cards_other;
			foreach ( $_cards_ender as $k=>$v )
			{
				$tlv = str_split($k,2);
				if ( !in_array($tlv[0],array('04','05','09','10')) )
				{
					continue;
				}
				foreach ( $_cards_rebid as $kk=>$vv )
				{
					$tlv_ = str_split($kk,2);
					if ( in_array($tlv_[0],array('01','02')) && array_intersect($v,$vv) )
					{
						unset($_cards_rebid[$kk]);
					}
				}
				foreach ( $_cards_start as $kk=>$vv )
				{
					$tlv_ = str_split($kk,2);
					if ( in_array($tlv_[0],array('01','02')) && array_intersect($v,$vv) )
					{
						unset($_cards_start[$kk]);
					}
				}
				foreach ( $_cards_other as $kk=>$vv )
				{
					$tlv_ = str_split($kk,2);
					if ( in_array($tlv_[0],array('01','02')) && array_intersect($v,$vv) )
					{
						unset($_cards_other[$kk]);
					}
				}
			}
			if ( (count($_cards_rebid) + count($_cards_start) + count($_cards_other)) < 2 )
			{
				$cards = $cards_first ? end($cards_first) : ( $_cards_rebid ? end($_cards_rebid) : ( $cards_ender ? end($cards_ender) : ( $_cards_start ? end($_cards_start) : end($_cards_other) ) ) );
				$is_myspring = true;
			}
		}
		if ( $is_myspring )
		{
			//print_r('“耍大牌-春天啊”');
		}

		//continue 决定跟出炸弹时，要保证小于1的单牌低于2张，小于1的对牌低于3对。

		//跟牌 && 对手叫牌
		elseif ( !$is_self_bidr && $is_bidr_rival )
		{
			//优先牌 > 起手牌 > 其他牌 > 回手牌 > 必胜牌 > pass
			//对手手牌过少，且下家是对手，且下家手牌少于我时，跟最大，否则跟最小
			if ( $cards_first )
			{
				$cards = ($num_bidr_hand < 6 && $is_next_rival && $num_next_hand < $num_mine_hand) ? reset($cards_first) : end($cards_first);
				if ( $is_break )
				{
					//print_r('“拆了压-优先牌”');
				}
				else
				{
					//print_r('“跟对手-优先牌”');
				}
			}
			elseif ( $cards_start )
			{
				$cards = ($num_bidr_hand < 6 && $is_next_rival && $num_next_hand < $num_mine_hand) ? reset($cards_start) : end($cards_start);
				if ( $is_break )
				{
					//print_r('“拆了压-起手牌”');
				}
				else
				{
					//print_r('“跟对手-起手牌”');
				}
			}
			elseif ( $cards_other )
			{
				$cards = ($num_bidr_hand < 6 && $is_next_rival && $num_next_hand < $num_mine_hand) ? reset($cards_other) : end($cards_other);
				if ( $is_break )
				{
					//print_r('“拆了压-其他牌”');
				}
				else
				{
					//print_r('“跟对手-其他牌”');
				}
			}
			elseif ( $cards_rebid )
			{
				$is_pass = false;
				foreach ( $cards_rebid as $k=>$v )
				{
					$type_= str_split($k,2);
					if ( $type_[0] != $table_bid_type['type'] && $num_bidr_hand > 6 )
					{
						unset($cards_rebid[$k]);
						$is_pass = true;
					}
				}
				$cards = $cards_rebid ? end($cards_rebid) : array();
				if ( $cards )
				{
					if ( $is_break )
					{
						//print_r('“拆了压-回手牌”');
					}
					else
					{
						//print_r('“跟对手-回手牌”');
					}
				}
				else
				{
					//print_r('“跟对手-先不跟”');
				}
			}
			elseif ( $cards_ender )
			{
				$is_pass = false;
				foreach ( $cards_ender as $k=>$v )
				{
					$type_= str_split($k,2);
					if ( $type_[0] != $table_bid_type['type'] && $num_bidr_hand > 6 )
					{
						unset($cards_ender[$k]);
						$is_pass = true;
					}
				}
				$cards = $cards_ender ? end($cards_ender) : array();
				if ( $cards )
				{
					if ( $is_break )
					{
						//print_r('“拆了压-必胜牌”');
					}
					else
					{
						//print_r('“跟对手-必胜牌”');
					}
				}
				else
				{
					//print_r('“跟对手-放一马”');
				}
			}
			elseif ( $cards_break )
			{
				$cards = reset($cards_break);
				//print_r('“跟对手-拆了牌”');
			}
			else
			{
				$cards = array();
				//print_r('“跟对手-过一手”');
			}
		}
		//跟牌 && 同伴叫牌
		elseif ( !$is_self_bidr && !$is_bidr_rival )
		{
			//优先牌 > 起手牌 > 其他牌 > pass
			$val_ = reset($table_bid_dec);
			$next_hand_type = $this->cardsCheck($next_hand_dec);
			//如果同伴只剩一张牌且打出的牌数大于地主手牌数，肯定过了
			$num_lord_hand = $next['is_lord'] ? $num_next_hand : $num_prev_hand;
			$is_must_pass = false;
			if ( $num_bidr_hand == 1 && $num_bid_cards > $num_lord_hand )
			{
				$cards = array();
				$is_must_pass = true;
				//print_r('“跟同伴-必须过”');
			}
			//如果上家同伴叫牌少，且与地主的手牌牌型同样，且小于地主手牌，一定要管住
			elseif ( $next['is_lord'] && $num_bid_cards < 4 && $table_bid_type['type'] == $next_hand_type['type'] && $table_bid_type['value'] < $next_hand_type['value'] )
			{
				foreach ( $cards_rebid as $k=>$v )
				{
					$tlv = str_split($k,2);
					if ( $tlv[0] > 80 || $tlv[2] < $next_hand_type['value'] )
					{
						unset($cards_rebid[$k]);
					}
				}
				foreach ( $cards_ender as $k=>$v )
				{
					$tlv = str_split($k,2);
					if ( $tlv[0] > 80 || $tlv[2] < $next_hand_type['value'] )
					{
						unset($cards_ender[$k]);
					}
				}
				foreach ( $cards_start as $k=>$v )
				{
					$tlv = str_split($k,2);
					if ( $tlv[0] > 80 || $tlv[2] < $next_hand_type['value'] )
					{
						unset($cards_start[$k]);
					}
				}
				foreach ( $cards_break as $k=>$v )
				{
					$tlv = str_split($k,2);
					if ( $tlv[0] > 80 || $tlv[2] < $next_hand_type['value'] )
					{
						unset($cards_break[$k]);
					}
				}
				foreach ( $cards_other as $k=>$v )
				{
					$tlv = str_split($k,2);
					if ( $tlv[0] > 80 || $tlv[2] < $next_hand_type['value'] )
					{
						unset($cards_other[$k]);
					}
				}
				$cards = $cards_rebid ? end($cards_rebid) : ( $cards_ender ? end($cards_ender) : ( $cards_start ? reset($cards_start) : ( $cards_break ? reset($cards_break) : ( $cards_other ? reset($cards_other) : array() ) ) ) );
				if ( $cards )
				{
					//print_r('“跟同伴-抬的起”');
				}
				else
				{
					$viable_ = $is_unpack ? $this->cardsViable($mine_hand_dec,2,$table_bid_dec,1) : array();
					if ( $viable_ )
					{
						$cards = reset($viable_);
						//print_r('“跟同伴-拆牌抬”');
					}
					else
					{
						//print_r('“跟同伴-抬不起”');
					}
				}
			}
			elseif ( $cards_first )
			{
				foreach ( $cards_first as $k=>$v )
				{
					$type_= str_split($k,2);
					if ( $val_ < 7 && $type_[2] < 12 )
					{
						$cards = $v;
					}
				}
				$cards = $cards ? $cards : ( ($num_bidr_hand+3) < $num_mine_hand ? array() : end($cards_first) );
				if ( $cards )
				{
					//print_r('“跟同伴-优先牌”');
				}
				else
				{
					//print_r('“跟同伴-优先过”');
				}
			}
			elseif ( $cards_start )
			{
				foreach ( $cards_start as $k=>$v )
				{
					$type_= str_split($k,2);
					if ( $val_ < 7 && $type_[2] < 12 )
					{
						$cards = $v;
					}
				}
				$cards = $cards ? $cards : ( ($num_bidr_hand+3) < $num_mine_hand ? array() : end($cards_start) );
				if ( $cards )
				{
					//print_r('“跟同伴-起手牌”');
				}
				else
				{
					//print_r('“跟同伴-起手过”');
				}
			}
			elseif ( $cards_other )
			{
				foreach ( $cards_other as $k=>$v )
				{
					$type_= str_split($k,2);
					if ( $val_ < 7 && $type_[2] < 12 )
					{
						$cards = $v;
					}
				}
				$cards = $cards ? $cards : ( $num_bidr_hand > $num_mine_hand ? end($cards_other) : array() );
				if ( $cards )
				{
					//print_r('“跟同伴-其他牌”');
				}
			}
			//没有最佳跟牌策略时，尝试我来代替同伴一把出完
			if ( !$is_must_pass && !$cards && $viables )
			{
				$is_myspring = false;
				//我的叫牌牌型
				$_myall_viable = $this->cardsViable($mine_hand_dec,2);
				$_cards_ender = $_cards_viable = array();
				foreach ( $_myall_viable as $k=>$v )
				{
					$type_= str_split($k,2);
					if ( ($is_peekother || $is_remember) && !($is_next_rival ? $this->cardsViable($next_hand_dec,3,$v) : $this->cardsViable($prev_hand_dec,1,$v)) )
					{
						//偷牌记牌 算出必胜
						$_cards_ender[$k] = $v;
						unset($_myall_viable[$k]);
					}
					if ( $table_bid_type['type'] == $type_[0] && $table_bid_type['value'] < $type_[2] )
					{
						$_cards_viable[$k] = $v;
					}
				}
				if ( count($_cards_ender) > 0 && count($_myall_viable) < 1 && $_cards_viable )
				{
					$cards = end($_cards_viable);
					//print_r('“跟同伴-让我来”');
				}
				else
				{
					//print_r('“跟同伴-让他来”');
				}
			}
			elseif ( !$cards )
			{
				//print_r('“跟同伴-其他过”');
			}
		}
		//叫牌 && 下家对手
		elseif ( $is_self_bidr && $is_next_rival )
		{
			if ( $is_unpack && in_array($num_next_hand,array(1,2)) )
			{
				$next_hand_type = $this->cardsCheck($next_hand_dec);
				foreach ( $viables as $k=>$v )
				{
					$type_ = str_split($k,2);
					if ( $type_[1] != $num_next_hand || $type_[2] >= $next_hand_type  )
					{
						$cards = $v;
					}
				}
				if ( !$cards )
				{
					$viable_ = $this->cardsViable($mine_hand_dec,2,$table_bid_dec,1);
					foreach ( $viable_ as $k=>$v )
					{
						$type_ = str_split($k,2);
						if ( $type_[1] != $num_next_hand || $type_[2] >= $next_hand_type  )
						{
							$cards = $v;
						}
					}
				}
			}
			elseif ( $is_unsame && $num_next_hand < 6 )
			{
				foreach ( $cards_first as $k=>$v )
				{
					if ( substr($k,2,2) == $num_next_hand )
					{
						unset($cards_first[$k]);
						$cards_other[$k] = $v;
					}
				}
				foreach ( $cards_start as $k=>$v )
				{
					if ( substr($k,2,2) == $num_next_hand )
					{
						unset($cards_start[$k]);
						$cards_other[$k] = $v;
					}
				}
				if ( $cards_other )
				{
					krsort($cards_other);
				}
			}
			//优先牌 > 起手牌 > 其他牌 > 回手牌 > 必胜牌
			if ( $cards )
			{
				//print_r('“憋下家-急死他”');
			}
			elseif ( $cards_first )
			{
				//带牌最小
				$min_ = array();
				foreach ( $cards_first as $k=>$v )
				{
					$min_ = array_merge($min_,$v);
				}
				$min_ = min($min_);
				//长度最大
				$tmp_ = $tmp2_ = array();
				foreach ( $cards_first as $k=>$v )
				{
					$type_= str_split($k,2);
					//有最小牌的最小的三带单最优先
					if ( in_array($min_,$v) && $type_[0] == '04' )
					{
						$cards = $v;
					}
					//牌多的时候，带2的带w的尽可能放后面
					if ( $num_mine_hand > 14 && array_intersect(array(12,13,14),$v) )
					{
						$tmp2_[$k] = $v;
						continue;
					}
					else
					{
						$tmp_[$type_[1]][$type_[2]] = $v;
					}
				}
				if ( $tmp_ )
				{
					//等长最小
					krsort($tmp_);
					$tmp_ = reset($tmp_);	//长度最大
					ksort($tmp_);			//牌值最小
				}
				if ( $tmp2_ )
				{
					krsort($tmp2_);
				}
				$cards = $cards ? $cards : ($tmp_ ? reset($tmp_) : reset($tmp2_));
				//print_r('“憋下家-优先牌”');
			}
			elseif ( $cards_start )
			{
				//带牌最小
				$min_ = array();
				foreach ( $cards_start as $k=>$v )
				{
					$min_ = array_merge($min_,$v);
				}
				$min_ = min($min_);
				//长度最大
				$tmp_ = array();
				foreach ( $cards_start as $k=>$v )
				{
					$type_= str_split($k,2);
					if ( in_array($min_,$v) )
					{
						$tmp_[$type_[1]] = $v;
						//有最小牌的三带单最优先
						if ( $type_[0] == '03' )
						{
							$cards = $v;
						}
					}
				}
				krsort($tmp_);
				$cards = $cards ? $cards : end($tmp_);
				//print_r('“憋下家-起手牌”');
			}
			elseif ( $cards_other )
			{
				if ( $num_next_hand < 4 )
				{
					$cards = reset($cards_other);
				}
				else
				{
					$maxlen = 0;
					$maxlenkey = '';
					foreach ( $cards_other as $k=>$v )
					{
						if ( in_array($min,$v) && $max-$min > 4  )
						{
							$maxlenkey = $k;
						}
					}
					if ( !$maxlenkey )
					{
						foreach ( $cards_other as $k=>$v )
						{
							$_oldlen = $maxlen;
							$maxlen = max($maxlen,substr($k,2,2));
							$maxlenkey = ($_oldlen < $maxlen) ? $k : $maxlenkey;
						}
					}
					$cards = $cards_other[$maxlenkey];
				}
				//print_r('“憋下家-其他牌”');
			}
			elseif ( $cards_rebid )
			{
				$cards = end($cards_rebid);
				//print_r('“憋下家-回手牌”');
			}
			elseif ( $cards_ender )
			{
				$cards = end($cards_ender);
				//print_r('“憋下家-必胜牌”');
			}
			else
			{
				//echo '<div id="dump">';
				//var_dump($viables);
				//echo '</div>';
				//print_r('“憋下家-？？？”');
				$cards = end($viables);
			}
		}
		//叫牌 && 下家同伴
		elseif ( $is_self_bidr && !$is_next_rival )
		{
			$num_next_hand = count($next_hand_dec);
			if ( $cards_first )
			{
				if ( $is_giveup && $num_next_hand < 6 )
				{
					ksort($cards_first);
					foreach ( $cards_first as $k=>$v )
					{
						if ( $cards )
						{
							break;
						}
						$type_= str_split($k,2);
						if ( $type_[2] == $num_next_hand )
						{
							$cards = $v;
						}
					}
				}
				if ( !$cards )
				{
					//带牌最小
					$min_ = array();
					foreach ( $cards_first as $k=>$v )
					{
						$min_ = array_merge($min_,$v);
					}
					$min_ = min($min_);
					//长度最大
					$tmp_ = array();
					foreach ( $cards_first as $k=>$v )
					{
						$type_= str_split($k,2);
						if ( in_array($min_,$v) )
						{
							$tmp_[$type_[1]] = $v;
							//有最小牌的三带单最优先
							if ( $type_[0] == '03' )
							{
								$cards = $v;
							}
						}
					}
					krsort($tmp_);
					$cards = $cards ? $cards : end($tmp_);
				}
				//print_r('“放同伴-优先牌”');
			}
			elseif ( $cards_start )
			{
				if ( $is_giveup && $num_next_hand < 6 )
				{
					ksort($cards_start);
					foreach ( $cards_start as $k=>$v )
					{
						if ( $cards )
						{
							break;
						}
						$type_= str_split($k,2);
						if ( $type_[2] == $num_next_hand )
						{
							$cards = $v;
						}
					}
				}
				if ( !$cards )
				{
					//带牌最小
					$min_ = array();
					foreach ( $cards_start as $k=>$v )
					{
						$min_ = array_merge($min_,$v);
					}
					$min_ = min($min_);
					//长度最大
					$tmp_ = array();
					foreach ( $cards_start as $k=>$v )
					{
						$type_= str_split($k,2);
						if ( in_array($min_,$v) )
						{
							$tmp_[$type_[1]] = $v;
							//有最小牌的三带单最优先
							if ( $type_[0] == '03' )
							{
								$cards = $v;
							}
						}
					}
					krsort($tmp_);
					$cards = $cards ? $cards : end($tmp_);
				}
				//print_r('“放同伴-起手牌”');
			}
			elseif ( $cards_other )
			{
				if ( $is_giveup && $num_next_hand < 6 )
				{
					ksort($cards_other);
					foreach ( $cards_other as $k=>$v )
					{
						if ( $cards )
						{
							break;
						}
						$type_= str_split($k,2);
						if ( $type_[2] == $num_next_hand )
						{
							$cards = $v;
						}
					}
				}
				if ( !$cards )
				{
					//带牌最小
					$min_ = array();
					foreach ( $cards_other as $k=>$v )
					{
						$min_ = array_merge($min_,$v);
					}
					$min_ = min($min_);
					//长度最大
					$tmp_ = array();
					foreach ( $cards_other as $k=>$v )
					{
						$type_= str_split($k,2);
						if ( in_array($min_,$v) )
						{
							$tmp_[$type_[1]] = $v;
							//有最小牌的三带单最优先
							if ( $type_[0] == '03' )
							{
								$cards = $v;
							}
						}
					}
					krsort($tmp_);
					$cards = $cards ? $cards : end($tmp_);
				}
				//print_r('“放同伴-其他牌”');
			}
			elseif ( $cards_rebid )
			{
					$cards = end($cards_rebid);
					//print_r('“放同伴-回手牌”');
			}
			elseif ( $cards_ender )
			{
					$cards = end($cards_ender);
					//print_r('“放同伴-必胜牌”');
			}
			else
			{
					//echo '<div id="dump">';
					//var_dump($viables);
					//echo '</div>';
					//print_r('“放同伴-？？？”');
					$cards = end($viables);
			}
		}
		else
		{
				//echo '<div id="dump">';
				//var_dump($viables);
				//echo '</div>';
				//print_r('“？？？-？？？”');
				$cards = end($viables);
		}

		//配上花色
		if ( $cards )
		{
			$_cards = $cards;
			$cards = array();
			$_hands = $mine_hand_dec;
			$j=0;
			foreach ( $_cards as $k=>$v )
			{
				$i = array_search($v,$_hands);
				if ( isset($cards[$i]) )
				{
					$j++;
					$i+=$j;
				}
				else
				{
					$j=0;
				}
				$cards[$i]=$mine['hand'][$i];
			}
		}

		//返回牌面
		return $cards;

	}

	//牌型比较
	//$cards1	叫牌者的叫牌
	//$cards2	待出的一组预选牌
	//return	0无效，1牌小，2牌大
	function cardsCompare( $cards1, $cards2 )
	{
		$cards1 = $this->cardsCheck($cards1);
		$cards2 = $this->cardsCheck($cards2);

		if( !$cards1['type'] || !$cards2['type'] )
		{
			return false;
		}

		if ( $cards1['type'] == 99 )
		{
			return 1;
		}
		elseif ( $cards2['type'] == 99 )
		{
			return 2;
		}
		elseif ( $cards1['type'] != $cards2['type'] && $cards1['type'] == 88 )
		{
			return 1;
		}
		elseif ( $cards1['type'] != $cards2['type'] && $cards2['type'] == 88 )
		{
			return 2;
		}
		elseif ( $cards1['type'] == $cards2['type'] && $cards1['value'] < $cards2['value'] )
		{
			return 2;
		}
		else
		{
			return 1;
		}
	}

	function cardsTranse($cards)
	{
		foreach ( $cards as $k=>$v )
		{
			$cards[$k] = $this->cardTranse($v);
		}
		return $cards;
	}

	function cardsTranse2($cards)
	{
		foreach ( $cards as $k=>$v )
		{
			$cards[$k] = $this->cardTranse2($v);
		}
		return $cards;
	}

	function cardTranse($card)
	{
		$color = array(
			'0'=>'♢',//&9826',
			'1'=>'♣',//&9827',
			'2'=>'♠',//&9824',
			'3'=>'♡',//&9825',
			'4'=>'♚',//&9819',
		);
		$number= array(
			'0'=>'<span class="num">3</span>',
			'1'=>'<span class="num">4</span>',
			'2'=>'<span class="num">5</span>',
			'3'=>'<span class="num">6</span>',
			'4'=>'<span class="num">7</span>',
			'5'=>'<span class="num">8</span>',
			'6'=>'<span class="num">9</span>',
			'7'=>'<span class="num">T</span>',
			'8'=>'<span class="num">J</span>',
			'9'=>'<span class="num">Q</span>',
			'a'=>'<span class="num">K</span>',
			'b'=>'<span class="num">A</span>',
			'c'=>'<span class="num">2</span>',
			'd'=>'<span class="num">w</span>',
			'e'=>'<span class="num">W</span>',
		);
		$card = strlen($card) > 1 ? $card : ('0'.$card);
		$_card = str_split($card);
		$col = $color[$_card[0]];
		$num = $number[$_card[1]];
		return $col.$num;
	}

	function cardTranse2($card)
	{
		$number= array(
			'0'=>'<span class="num">3</span>',
			'1'=>'<span class="num">4</span>',
			'2'=>'<span class="num">5</span>',
			'3'=>'<span class="num">6</span>',
			'4'=>'<span class="num">7</span>',
			'5'=>'<span class="num">8</span>',
			'6'=>'<span class="num">9</span>',
			'7'=>'<span class="num">T</span>',
			'8'=>'<span class="num">J</span>',
			'9'=>'<span class="num">Q</span>',
			'10'=>'<span class="num">K</span>',
			'11'=>'<span class="num">A</span>',
			'12'=>'<span class="num">2</span>',
			'13'=>'<span class="num">w</span>',
			'14'=>'<span class="num">W</span>',
		);
		return $number[$card];
	}

	function cardsEcho($cards,$source = array())
	{
		foreach ( $cards as $k=>$v )
		{
			echo $v.' ';
		}
		if ( $source )
		{
			echo 'array("'.join('","',$source).'")';
		}
		echo '<hr/>';
	}

}//End Class


//为了便于直接使用，下面的这些函数从类中拿出来
//从原牌数值转移到新牌数值，单牌
function cardEnTranse($card)
{
	/*
		高位:
			原[1-4] 1黑 2红 3梅 4方 0王
			新[0-4] 0方 1梅 2红 3黑 4王
		低位:
			原[1-13] A 2 3 4 ... T J Q K 00小王 01大王
			新[0-E] 3 4 5 ... T J Q K A 2 w W
				3 4 5 6 7 8 9 T J Q K A 2
			0   0 1 2 3 4 5 6 7 8 9 A B C		方块
			1   0 1 2 3 4 5 6 7 8 9 A B C		梅花
			2   0 1 2 3 4 5 6 7 8 9 A B C		黑桃
			3   0 1 2 3 4 5 6 7 8 9 A B C		红桃
			4	                          D E	王色
		计算牌型值:
			转16进制(高位花色*16+低位牌值)，便于存储传输
		解析牌型值:
			高位花色=Math.floor(牌型值/16)
			低位牌值=Math.floor(牌型值%16)
	 */
	$dec = hexdec($card);
	$color = abs(floor($dec/16)-4);
	if ( $color == 4 )
	{
		$point = $dec%16+13;
	}
	else
	{
		$point = $dec%16-3;
		$point = ($point < 0) ? ($point+13) : $point;
	}
	$hex = dechex($color*16+$point);
	return isset($hex[1]) ? strval($hex) : ('0'.$hex[0]);
}

//从原牌数值转移到新牌数值，多牌
function cardsEnTranse($cards)
{
	$cards = ($cards && is_array($cards)) ? $cards : array();
	foreach ( $cards as $k=>$v )
	{
		$cards[$k] = cardEnTranse($v);
	}
	return $cards;
}

//[新牌版]牌组依据同点花色大小依次倒序
function cardsSort( $cards )
{
	if ( !$cards || !is_array($cards) )
	{
		return array();
	}
	$sorts = array();
	foreach( $cards as $k => $v )
	{
		$sorts[hexdec($v[1])*100+hexdec($v[0])] = $v;
	}
	krsort($sorts);
	return array_values($sorts);
}

//[原牌版]牌组依据同点花色大小依次倒序
function cardsSort2( $cards )
{
	if ( !$cards || !is_array($cards) )
	{
		return array();
	}
	$sorts = array();
	foreach( $cards as $k => $v )
	{
		$type = hexdec($v[0]);
		$point = hexdec($v[1]);
		if( $type == 0 && $point == 0 ) $point = 20;
		if( $type == 0 && $point == 1 ) $point = 21;
		if( $point < 3 )  $point += 13;
		$sorts[$point*100+$type] = $v;
	}
	krsort($sorts);
	return array_values($sorts);
}

//[原牌版]检查牌型并返回 牌型、牌值、规整后的牌面
function cardsCheck( $cards )
{
	$type = '00';
	$len = count($cards);
	$value = '00';
	if ( !$cards || !is_array($cards) )
	{
		return array('type'=>$type,'len'=>$len,'value'=>$value,'cards'=>$cards);
	}
	$cards = cardsSort2($cards);//重新排序
	$card = new Card();
	$cardsen = $card->cardsEnTranse($cards);//牌值转义到新
	$cardsnew = array();
	foreach ( $cardsen as $k=>$v )
	{
		$cardsnew[$v] = $cards[$k];	//新牌值->旧牌值
	}
	$res = $card->cardsCheck($card->cardsDec($cardsen));
	if ( $res['type'] > 0 )
	{
		$type = $res['type'];
		$len = $res['len'];
		$value = $res['value'];
		$array = $res['array'];//新十进制有序牌值
		$cards = array();
		foreach ( $array as $k=>$v )
		{
			foreach ( $cardsnew as $kk=>$vv )
			{
				$val = hexdec($kk)%16;
				if ( $val == $v )
				{
					$cards[]=$vv;
					unset($cardsnew[$kk]);
					break;
				}
			}
		}
		if ( count($cardsnew) )
		{
			$type = '00';
			$len = '00';
			$value = '00';
			$cards = array();
		}
	}
	return array('type'=>$type,'len'=>$len,'value'=>$value,'cards'=>$cards);
}

/*
echo '<!DOCTYPE html>
<html>
<head>
<title>New Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="generator" content="editplus" />
<meta name="author" content="" />
<meta name="keywords" content="" />
<meta name="description" content="" />
<style type="text/css">
body{font-size:16px;}
hr{margin:1px;color:#ddd;}
.num{font-size:18px;font-weight:bold;color:#666;}
</style>
</head>
<body>';


$card = new Card();

$pool = $card->newCardPool(0,4);

$table['hand'] = array_merge($pool[1],$pool['lord'],$pool[0],$pool[2]);
$table['out'] = array();
$table['cards'] = array();
$table['bidder'] = 0;
$prev['hand'] = $pool[0];
$prev['pos'] = 2;
$prev['is_lord'] = 0;
$prev['is_bidder'] = 0;
$next['hand'] = $pool[2];
$next['pos'] = 1;
$next['is_lord'] = 0;
$next['is_bidder'] = 0;
$mine['hand'] = array_merge($pool[1],$pool['lord']);//我是地主，用$pool[1]的牌，第一把由我叫牌
$mine['pos'] = 0;
$mine['is_lord'] = 1;
$mine['is_bidder'] = 1;


//$prev['hand'] =
//array("4d","1c","0c","3b","1b","19","09","28","07","35","24","04","13","32","22","20","10")
//;
//$next['hand'] =
//array("4e","3c","2c","0b","2a","1a","39","29","17","16","06","05","14","02","11","01","30")
//;
//$mine['hand'] =
//array("2b","3a","0a","38","18","08","37","27","25","15","34","33","23","03","12","31","21","36","26","00")
//;

//上家手牌：W 2 2 A K Q Q Q T 9 8 7 7 6 5 5 4 array("4e","2c","0c","2b","1a","39","19","09","27","06","15","14","04","13","22","02","21")
//下家手牌：2 A A K Q J 9 9 8 7 5 5 4 4 3 3 3 array("3c","3b","1b","3a","29","18","36","26","35","24","32","12","31","01","30","10","00")
//我的手牌：w 2 A K K J J J T T T 9 8 8 7 6 6 6 4 3 array("4d","1c","2a","0a","38","28","37","17","07","16","25","05","34","23","03","11","20","0b","08","33")


$i = 0;
$pos = 0;
$pass = 0;
$poss = array('我是地主','右边农民','左边农民');
$not_end = true;
while ( $not_end )
{
	$i++;

	$Card = new Card(1,$table,$prev,$next,$mine);

	echo "上家手牌：";
	$Card->cardsEcho($Card->cardsTranse2($Card->cardsDec($prev['hand'])),$prev['hand']);
	echo "下家手牌：";
	$Card->cardsEcho($Card->cardsTranse2($Card->cardsDec($next['hand'])),$next['hand']);
	echo "我的手牌：";
	$Card->cardsEcho($Card->cardsTranse2($Card->cardsDec($mine['hand'])),$mine['hand']);

	$out_ = $Card->cardsLogic();
	$out_ = $out_ ? $out_ : array();
	$res = $Card->cardsTranse($out_);

	echo '第 ['.($i>9?$i:('0'.$i)).'] 把， ['.$poss[$pos].'] 【'.($table['cards']?'跟牌':'叫牌').'】：';
	$Card->cardsEcho($res);

	$out = $out_;
	$mine_hand = $mine['hand'];
	foreach ( $mine_hand as $k=>$v )
	{
		foreach ( $out as $kk=>$vv )
		{
			if ( $v == $vv )
			{
				unset($out[$kk]);
				unset($mine_hand[$k]);
				break;
			}
		}
	}
	if ( !$out_  && $pass < 2 )
	{
		$pass++;
		$pass = $pass == 2 ? 0 : $pass;
	}
	else
	{
		$pass = 0;
	}

	$mine['hand'] = $mine_hand;
	$table['out'] = array_merge($Card->table['out'],$out_);
	$table['cards'] = $pass ? $table['cards'] : $out_;
	$table['bidder'] = $out_ ? $Card->mine['pos'] : $Card->table['bidder'];
	$tmp_ = $prev;
	$prev = $mine;
	$mine = $next;
	$next = $tmp_;

	$pos++;
	$pos = ($pos == 3) ? 0 : $pos;

	if ( !$prev['hand'] || !$next['hand'] || !$mine['hand'] )
	{
		$not_end = false;
	}
}

echo '</body></html>';
*/
