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

namespace legionpe\theta\classic;

use legionpe\theta\BaseListener;
use legionpe\theta\utils\CallbackPluginTask;
use pocketmine\entity\Arrow;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\DisconnectPacket;

class ClassicListener extends BaseListener{
	public function onPacketSend(DataPacketSendEvent $event){
		$pk = $event->getPacket();
		if($pk instanceof DisconnectPacket){
			if($pk->message === "client disconnect"){
				$ses = $this->getMain()->getSession($event->getPlayer());
				if($ses instanceof ClassicSession){
					$ses->onClientDisconnect();
				}
			}
		}
	}
	/**
	 * @param ProjectileHitEvent $event
	 * @priority HIGH
	 */
	public function onProjectileHit(ProjectileHitEvent $event){
		if($event->getEntity() instanceof Arrow){
			$this->getMain()->getServer()->getScheduler()->scheduleDelayedTask(new CallbackPluginTask($this->getMain(), function (Arrow $arrow){
				if($arrow->isAlive()){
					$arrow->kill();
				}
			}, $event->getEntity()), 1);
		}
	}
}
