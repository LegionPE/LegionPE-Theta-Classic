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
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\DisconnectPacket;

class ClassicListener extends BaseListener{
	public function onPacketRecv(DataPacketReceiveEvent $event){
		parent::onPacketRecv($event);
		$pk = $event->getPacket();
		if($pk instanceof DisconnectPacket){
			if($pk->message !== "client disconnect"){
				$ses = $this->getMain()->getSession($event->getPlayer());
				if($ses instanceof ClassicSession){
					$ses->setCombatMode(false);
				}
			}
		}
	}
}
