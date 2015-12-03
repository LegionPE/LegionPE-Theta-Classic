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

namespace legionpe\theta\classic\battle;

use legionpe\theta\classic\ClassicSession;
use pocketmine\entity\Effect;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;

class ClassicBattleKit{
	/** @var string */
	private $name;
	/** @var Item[] */
	private $armorItems = [];
	/** @var Item[] */
	private $items = [];
	/** @var Effect[] */
	private $effects = [];
	/** @var int */
	private $maxHealth = 40;

	/**
	 * @param string $name
	 * @param Item[] $armorItems
	 * @param Item[] $items
	 * @param Effect[] $effects
	 */
	public function __construct($name, $armorItems, $items, $effects){
		$this->name = $name;
		$this->armorItems = $armorItems;
		$this->items = $items;
		$this->effects = $effects;
	}
	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}
	/**
	 * @param ClassicSession $session
	 */
	public function apply(ClassicSession $session){
		$session->getPlayer()->setGamemode(0);
		$session->getPlayer()->setAllowFlight(false);
		$inventory = $session->getPlayer()->getInventory();
		if(!($inventory instanceof PlayerInventory)){
			return;
		}
		$inventory->clearAll();
		$inventory->setContents($this->items);
		for($i = 0; $i < 4; $i++){
			$inventory->setArmorItem($i, $this->armorItems[$i]);
		}
		$inventory->setArmorContents($this->armorItems);
		for($i = 0; $i < 9; $i++){
			$inventory->setHotbarSlotIndex($i, $i);
		}
		$inventory->sendContents($session->getPlayer());
		$inventory->sendArmorContents($session->getPlayer());
		$session->getPlayer()->removeAllEffects();
		if(count($this->effects) !== 0){
			foreach($this->effects as $effect){
				$session->getPlayer()->addEffect($effect);
			}
		}
		$session->getPlayer()->setMaxHealth($this->maxHealth);
		$session->getPlayer()->setHealth($this->maxHealth);
		// steadfast2
		$pk = new UpdateAttributesPacket();
		$pk->minValue = 0;
		$pk->maxValue = $this->maxHealth;
		$pk->value = $this->maxHealth;
		$pk->name = "generic.health";
		$session->getPlayer()->dataPacket($pk);
		//$ev = new EntityRegainHealthEvent($session->getPlayer(), 20, EntityRegainHealthEvent::CAUSE_MAGIC);
		//$session->getPlayer()->heal(20, $ev);
	}

}
