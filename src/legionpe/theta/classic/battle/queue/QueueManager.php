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
	/** @var ClassicBattleQueue[] */
	private $queues = [];

	/**
	 * @param ClassicPlugin $main
	 */
	public function __construct(ClassicPlugin $main){
		$this->main = $main;
	}
	/**
	 * @return ClassicBattleQueue[]
	 */
	public function getQueues(){
		return $this->queues;
	}
	public function getSortedQueueSessions(){
		$queues = [];
		foreach($this->queues as $queue){
			if(!isset($queues[$queue->getPlayersPerTeam()])){
				$queues[$queue->getPlayersPerTeam()] = [];
			}
			if(!isset($queues[$queue->getPlayersPerTeam()][$queue->getKitType()])){
				$queues[$queue->getPlayersPerTeam()][$queue->getKitType()] = [];
			}
			if($queue->getKitType() === ClassicBattleQueue::TYPE_FIXED){
				if(!isset($queues[$queue->getPlayersPerTeam()][$queue->getKit()][$queue->getKit()->getName()])){
					$queues[$queue->getPlayersPerTeam()][$queue->getKit()][$queue->getKit()->getName()] = [];
				}
				$queues[$queue->getPlayersPerTeam()][$queue->getKit()][$queue->getKit()->getName()][] = $queue->getSession();
			}
		}
	}
}
