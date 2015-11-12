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
		// todo: implement 2v2 support
		/** @var ClassicBattleQueue[] $queues */
		foreach($this->main->getQueueManager()->getShuffledQueues() as $queues){
			$q = count($queues);
			if($q & 1){
				array_pop($queues)->getSession()->getPlayer()->sendMessage("Sorry, we couldn't find anyone. Please queue again.");
			}
			if($q){
				$index = 0;
				foreach($queues as $queue){
					if(!isset($queues[$index])){
						break;
					}
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
					new ClassicBattle($this->main, [[$queue->getSession()], [$queues[++$index]->getSession()]], 3, 60, $kit, $arena);
					$index++;
				}
			}
		}
	}
}
