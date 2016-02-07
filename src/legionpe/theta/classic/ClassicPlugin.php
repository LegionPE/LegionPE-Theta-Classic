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
use legionpe\theta\classic\utils\ClassicResendPlayersTask;
use legionpe\theta\classic\utils\ResetBlocksTask;
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
	private $battleArenas = [];
	/** @var \legionpe\theta\classic\battle\ClassicBattleKit[] */
	private $battleKits = [];
	/** @var QueueManager */
	private $queueManager;
	/** @var utils\ResetBlocksTask */
	public $resetBlocksTask;
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
		$this->battleArenas['spawn'] = new ClassicBattleArena('Spawn', $level, [[new Vector3(-14.5, 38, 341.5), new Vector3(-16.5, 38, 341.5)], [new Vector3(-14.5, 38, 377.5), new Vector3(-16.5, 38, 377.5)]], [[0, 0], [-180, -180]]);
		$this->battleArenas['flyingship'] = new ClassicBattleArena('Flying ship', $level, [[new Vector3(-54.5, 52, 485.5), new Vector3(-54.5, 52, 487.5)], [new Vector3(-4.5, 52, 487.5), new Vector3(-4.5, 52, 485)]], [[-90, -90], [90, 90]]);
		$this->battleArenas['flyingcastle'] = new ClassicBattleArena('Flying castle', $level, [[new Vector3(-114.5, 38, 337.5), new Vector3(-115.5, 38, 336.5)], [new Vector3(-142.5, 38, 363.5), new Vector3(-141.5, 38, 364)]], [[45, 45], [-135, -135]]);
		$apple = Item::get(260);
		$apple->setCount(6);
		$this->battleKits['defaultkit'] = new ClassicBattleKit('Default kit',
			[Item::get(306), Item::get(307), Item::get(308), Item::get(309)],
			[Item::get(276), $apple],
			[]);
		$this->battleKits['diamondkit'] = new ClassicBattleKit('Diamond kit',
			[Item::get(310), Item::get(311), Item::get(312), Item::get(313)],
			[Item::get(267), $apple],
			[]);
		$this->queueManager = new QueueManager($this);
		$this->queueBlocks[] = new ClassicBattleQueueBlock($this, $level->getBlock(new Vector3(-5, 13, -5)), '0 queueing', 1, false, false);
		$this->queueBlocks[] = new ClassicBattleQueueBlock($this, $level->getBlock(new Vector3(-1, 13, -5)), '0 queueing', 1, false, false);
		$this->queueBlocks[] = new ClassicBattleQueueBlock($this, $level->getBlock(new Vector3(-1, 13, 7)), '0 queueing', 2, false, false);
		$this->queueBlocks[] = new ClassicBattleQueueBlock($this, $level->getBlock(new Vector3(-5, 13, 7)), '0 queueing', 2, false, false);
		$this->resetBlocksTask = new ResetBlocksTask($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->resetBlocksTask, 40);
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
		$RESEND_ADD_PLAYER = $this->getResendAddPlayerFreq();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new BattleTask($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new QueueTask($this), 400);
		//if($RESEND_ADD_PLAYER > 0){
		//	$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new ClassicResendPlayersTask($this), $RESEND_ADD_PLAYER, $RESEND_ADD_PLAYER);
			// have temporary replacement
		//}
		new FireballTask($this);
	}
	public function onDisable(){
		parent::onDisable();
		foreach($this->resetBlocksTask->blocks as $block){
			$block[0]->getLevel()->setBlock($block[0], $block[1]);
		}
	}
	public function getBasicListenerClass(){
		return ClassicListener::class;
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
	public function getBattleArenas(){
		return $this->battleArenas;
	}
	/**
	 * @param string $name
	 * @return battle\ClassicBattleArena
	 */
	public function getBattleArenaByName($name){
		return $this->battleArenas[strtolower($name)];
	}
	/**
	 * @return battle\ClassicBattleKit[]
	 */
	public function getBattleKits(){
		return $this->battleKits;
	}
	/**
	 * @param string $name
	 * @return battle\ClassicBattleKit
	 */
	public function getBattleKit($name){
		return $this->battleKits[strtolower($name)];
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
