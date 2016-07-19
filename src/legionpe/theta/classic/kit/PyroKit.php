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

namespace legionpe\theta\classic\kit;

use legionpe\theta\classic\ClassicSession;
use legionpe\theta\classic\kit\ClassicKit;
use legionpe\theta\classic\kit\power\FirePower;
use legionpe\theta\classic\kit\power\NoFireDamagePower;
use legionpe\theta\classic\kit\power\NoLavaDamagePower;
use pocketmine\item\Item;

class PyroKit extends ClassicKit{
	public $id = self::KIT_ID_PYRO;
	public function __construct($level){
		$this->setName("Pyro");
		$this->setDescription("I like to play with fire.");
		$this->setLevel($level);
	}
	/**
	 * @param ClassicSession $session
	 */
	public function equip(ClassicSession $session){
		$inventory = $session->getPlayer()->getInventory();
		$inventory->clearAll();
		$inventory->setContents($this->getItems());
		for($i = 0; $i < 9; $i++){
			$inventory->setHotbarSlotIndex($i, $i);
		}
		$count = 0;
		foreach($this->getPowers() as $power){
			if(isset($power->item)){
				$inventory->setItem(15 + $count, clone $power->item);
				$inventory->setHotbarSlotIndex(5 - $count, 15 + $count++);
			}
		}
		$inventory->setArmorContents($this->getArmorItems());
		$inventory->sendContents($session->getPlayer());
		$inventory->sendArmorContents($session->getPlayer());
		if(count($this->getPowers()) !== 0){
			foreach($this->getPowers() as $power){
				$power->onGeneral($session);
			}
		}
	}
	public function setLevel($level){
		$orangeDye = Item::get(Item::DYE);
		$orangeDye->setDamage(14);
		switch($level){
			case 1:
				$food = Item::get(Item::MELON_SLICE);
				$food->setCount(64);
				$items = [
					Item::get(Item::WOODEN_SWORD),
					$food
				];
				$armorItems = [
					Item::get(Item::CHAIN_HELMET),
					Item::get(Item::LEATHER_TUNIC),
					Item::get(Item::LEATHER_PANTS),
					Item::get(Item::LEATHER_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new FirePower("Fire", "Set players on fire when you hit them", $level, $orangeDye)
				];
				$this->setPowers($powers);
				$this->setPrice(1500);
				break;
			case 2:
				$food = Item::get(Item::APPLE);
				$food->setCount(64);
				$items = [
					Item::get(Item::GOLDEN_SWORD),
					$food
				];
				$armorItems = [
					Item::get(Item::CHAIN_HELMET),
					Item::get(Item::CHAIN_CHESTPLATE),
					Item::get(Item::CHAIN_LEGGINGS),
					Item::get(Item::LEATHER_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new FirePower("Fire", "Set players on fire when you hit them", $level, $orangeDye),
					new NoLavaDamagePower("No lava", "You will not receive any damage when you're in lava", $level)
				];
				$this->setPowers($powers);
				$this->setPrice(3000);
				break;
			case 3:
				$food = Item::get(Item::APPLE);
				$food->setCount(64);
				$items = [
					Item::get(Item::GOLDEN_SWORD),
					$food
				];
				$armorItems = [
					Item::get(Item::GOLD_HELMET),
					Item::get(Item::CHAIN_CHESTPLATE),
					Item::get(Item::CHAIN_LEGGINGS),
					Item::get(Item::GOLD_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new FirePower("Fire", "Set players on fire when you hit them", $level, $orangeDye),
					new NoLavaDamagePower("No lava", "You will not receive any damage when you're in lava", $level),
					new NoFireDamagePower("No fire", "You will not receive any damage when on fire", $level)
				];
				$this->setPowers($powers);
				$this->setPrice(4000);
				break;
			case 4:
				$food = Item::get(Item::COOKED_CHICKEN);
				$food->setCount(64);
				$items = [
					Item::get(Item::STONE_SWORD),
					$food
				];
				$armorItems = [
					Item::get(Item::GOLD_HELMET),
					Item::get(Item::GOLD_CHESTPLATE),
					Item::get(Item::CHAIN_LEGGINGS),
					Item::get(Item::GOLD_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new FirePower("Fire", "Set players on fire when you hit them", $level, $orangeDye),
					new NoLavaDamagePower("No lava", "You will not receive any damage when you're in lava", $level),
					new NoFireDamagePower("No fire", "You will not receive any damage when on fire", $level)
				];
				$this->setPowers($powers);
				$this->setPrice(5000);
				break;
		}
		$this->level = $level;
	}
}
