<?php

/*
 * LegionPE
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

namespace legionpe\theta\classic\battle\queue;

use legionpe\theta\classic\battle\ClassicBattle;
use legionpe\theta\classic\battle\ClassicBattleKit;
use legionpe\theta\classic\ClassicPlugin;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;

class QueueTask extends PluginTask{
	/** @var \legionpe\theta\classic\ClassicPlugin */
	private $main;

	/**
	 * @param \legionpe\theta\classic\ClassicPlugin $main
	 */
	public function __construct(ClassicPlugin $main){
		parent::__construct($main);
		$this->main = $main;
	}
	public function onRun($ticks){
		/** @var ClassicBattleQueue[] $queues */
		foreach($this->main->getQueueManager()->getShuffledQueues() as $queues){
			$playersPerTeam = $queues[0]->getPlayersPerTeam();
			$queueCount = count($queues);
			$playersRemoved = 0;
			for($i = ($queueCount - ($queueCount % ($playersPerTeam * 2))); $i < $queueCount; $i++){
				$queues[$i]->getSession()->sendMessage(TextFormat::GOLD . "Sorry, we can't find any player for you to Battle with. Please queue again.");
				$playersRemoved++;
				unset($queues[$i]);
			}
			$queueCount -= $playersRemoved;
			$pIndex = 0;
			for($battle = 0; $battle < $queueCount / ($playersPerTeam * 2); $battle++){
				$queue = $queues[0];
				$kit = null;
				if($queue->getKitType() === ClassicBattleQueue::TYPE_FIXED){
					$kit = $queue->getKit();
				}else{
					$kits = $this->main->getKits();
					shuffle($kits);
					$kit = $kits[0];
				}
				$arena = null;
				if($queue->getArenaType() === ClassicBattleQueue::TYPE_FIXED){
					$arena = $queue->getArena();
				}else{
					$arenas = $this->main->getArenas();
					shuffle($arenas);
					$arena = $arenas[0];
				}
				$teams = [[], []];
				for($team = 0; $team < 2; $team++){
					for($i = 0; $i < $playersPerTeam; $i++){
						$teams[$team][] = $queues[$pIndex++]->getSession();
					}
				}
				new ClassicBattle($this->main, $teams, 3, 60, $kit, $arena);
			}
		}
	}
}
