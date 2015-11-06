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
use pocketmine\item\Item;
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
		foreach($this->main->getQueueManager()->getShuffledQueues() as $queues){
			$q = count($queues);
			if($q & 1) array_pop($queues);
			if($q){
				$kit = new ClassicBattleKit('Default battle kit',
					[Item::get(306), Item::get(307), Item::get(308), Item::get(309)],
					[Item::get(276), Item::get(260)],
					[]);
				$index = 0;
				foreach($queues as $queue){
					if(!isset($queues[$index])) break;
					new ClassicBattle($this->main, [[$queue->getSession()], [$queues[++$index]->getSession()]], 3, 60, $kit);
					$index++;
				}
			}
		}
	}
}
