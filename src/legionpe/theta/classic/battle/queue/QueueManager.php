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

use legionpe\theta\classic\ClassicPlugin;

class QueueManager{
	/** @var \legionpe\theta\classic\ClassicPlugin */
	private $main;
	/** @var \legionpe\theta\classic\battle\queue\ClassicBattleQueue[] */
	private $queues = [];

	/**
	 * @param \legionpe\theta\classic\ClassicPlugin $main
	 */
	public function __construct(ClassicPlugin $main){
		$this->main = $main;
	}
	/**
	 * @param ClassicBattleQueue $queue
	 */
	public function addQueue(ClassicBattleQueue $queue){
		$this->queues[] = $queue;
		foreach($this->main->getQueueBlocks() as $queueBlock){
			if($queueBlock->getType() == $queue->getPlayersPerTeam()){
				$c = 0;
				foreach($this->queues as $queue){
					if($queue->getPlayersPerTeam() == $queueBlock->getType()){
						$c++;
					}
				}
				$queueBlock->setText($c . " players queueing");
			}
		}
	}
	/**
	 * @return \legionpe\theta\classic\battle\queue\ClassicBattleQueue[]
	 */
	public function getQueues(){
		return $this->queues;
	}
	/**
	 * @return \legionpe\theta\classic\battle\queue\ClassicBattleQueue[][]
	 */
	public function getShuffledQueues(){
		$queues = [];
		foreach($this->queues as $queue){
			if($queue->getSession()->getPlayer()->isOnline()){
				if(!isset($queues[$queue->getId()])){
					$queues[$queue->getId()] = [];
				}
				$queues[$queue->getId()][] = $queue;
				shuffle($queues[$queue->getId()]);
				$queue->getSession()->isQueueing = false;
			}
		}
		foreach($this->main->getQueueBlocks() as $queueBlock){
			$queueBlock->setText('0 queueing');
		}
		$this->queues = [];
		return $queues;
	}
}
