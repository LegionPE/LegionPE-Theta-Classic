<?php

/*
 * LegionPE Theta
 *
 * Copyright (C) 2015 PEMapModder and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace legionpe\theta\classic;

use legionpe\theta\BasePlugin;
use legionpe\theta\classic\battle\BattleTask;
use legionpe\theta\classic\battle\ClassicBattle;
use legionpe\theta\classic\battle\ClassicBattleArena;
use legionpe\theta\classic\battle\ClassicBattleKit;
use legionpe\theta\classic\battle\queue\ClassicBattleQueueBlock;
use legionpe\theta\classic\battle\queue\QueueManager;
use legionpe\theta\classic\battle\queue\QueueTask;
use legionpe\theta\classic\commands\BattleCommand;
use legionpe\theta\classic\commands\PvpStatsCommand;
use legionpe\theta\classic\commands\PvpTopCommand;
use legionpe\theta\classic\commands\TeleportHereCommand;
use legionpe\theta\classic\commands\TeleportToCommand;
use legionpe\theta\classic\query\ClassicLoginDataQuery;
use legionpe\theta\classic\query\ClassicSaveSinglePlayerQuery;
use legionpe\theta\command\session\friend\FriendlyFireActivationCommand;
use legionpe\theta\queue\Queue;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

//use legionpe\theta\classic\commands\OneVsOneCommand;

class ClassicPlugin extends BasePlugin{
	/** @var ClassicBattle[] */
	public $battles = [];
	/** @var \legionpe\theta\classic\battle\ClassicBattleArena[] */
	private $arenas = [];
	/** @var \legionpe\theta\classic\battle\ClassicBattleKit[] */
	private $kits = [];
	/** @var QueueManager */
	private $queueManager;
	/** @var ClassicBattleQueueBlock[] */
	private $queueBlocks = [];
	/** @var TeleportManager */
	private $tpMgr;
	protected static function defaultLoginData($uid, Player $player){
		$data = parent::defaultLoginData($uid, $player);
		$data["pvp_init"] = time();
		$data["pvp_kills"] = 0;
		$data["pvp_deaths"] = 0;
		$data["pvp_maxstreak"] = 0;
		$data["pvp_curstreak"] = 0;
		$data["pvp_kit"] = 0;
		return $data;
	}
	public function onEnable(){
		parent::onEnable();
		$level = $this->getServer()->getLevelByName('world_pvp');
		$this->arenas['cave'] = new ClassicBattleArena('Cave', $level, [[new Vector3(212, 16, 23), new Vector3(215, 16, 23)], [new Vector3(200, 16, 2), new Vector3(195, 16, 4)]], [[140, 140], [-35, -35]]);
		$apple = Item::get(260);
		$apple->setCount(6);
		$this->kits['default kit'] = new ClassicBattleKit('Default kit',
			[Item::get(306), Item::get(307), Item::get(308), Item::get(309)],
			[Item::get(276), $apple],
			[]);
		$this->queueManager = new QueueManager($this);
		$this->queueBlocks[] = new ClassicBattleQueueBlock($this, $level->getBlock(new Vector3(297, 38, -137)), '0 queueing', 1, false, false);
		$this->queueBlocks[] = new ClassicBattleQueueBlock($this, $level->getBlock(new Vector3(299, 38, -137)), '0 queueing', 1, false, false);
		$this->tpMgr = new TeleportManager($this);
		$this->getServer()->getCommandMap()->registerAll("c", [
			new TeleportHereCommand($this),
			new TeleportToCommand($this),
			new FriendlyFireActivationCommand($this),
			new PvpStatsCommand($this),
			new PvpTopCommand($this),
			new BattleCommand($this)
//			new OneVsOneCommand($this),
		]);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new BattleTask($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new QueueTask($this), 400);
		new FireballTask($this);
	}
	public function getLoginQueryImpl(){
		return ClassicLoginDataQuery::class;
	}
	public function getSaveSingleQueryImpl(){
		return ClassicSaveSinglePlayerQuery::class;
	}
	public function sendFirstJoinMessages(Player $player){
		// TODO: Implement sendFirstJoinMessages() method.
	}
	public function query_world(){
		return "pvp-1";
	}
	/**
	 * @return battle\ClassicBattleArena[]
	 */
	public function getArenas(){
		return $this->arenas;
	}
	/**
	 * @param string $name
	 * @return battle\ClassicBattleArena
	 */
	public function getArenaByName($name){
		return $this->arenas[strtolower($name)];
	}
	/**
	 * @return battle\ClassicBattleKit[]
	 */
	public function getKits(){
		return $this->kits;
	}
	/**
	 * @param string $name
	 * @return battle\ClassicBattleKit
	 */
	public function getKit($name){
		return $this->kits[strtolower($name)];
	}
	/**
	 * @return QueueManager
	 */
	public function getQueueManager(){
		return $this->queueManager;
	}
	/**
	 * @return TeleportManager
	 */
	public function getTeleportManager(){
		return $this->tpMgr;
	}
	/**
	 * @return ClassicBattle[]
	 */
	public function getBattles(){
		return $this->battles;
	}
	/**
	 * @param ClassicBattle $battle
	 */
	public function addBattle(ClassicBattle $battle){
		$this->battles[$battle->getId()] = $battle;
	}
	/**
	 * @param \legionpe\theta\classic\battle\ClassicBattle $battle
	 */
	public function removeBattle(ClassicBattle $battle){
		unset($this->battles[$battle->getId()]);
	}
	/**
	 * @param $id
	 * @return ClassicBattle|null
	 */
	public function getBattleById($id){
		return isset($this->battles[$id]) ? $this->battles[$id] : null;
	}
	/**
	 * @return battle\queue\ClassicBattleQueueBlock[]
	 */
	public function getQueueBlocks(){
		return $this->queueBlocks;
	}
	protected function createSession(Player $player, array $loginData){
		return new ClassicSession($this, $player, $loginData);
	}
}
