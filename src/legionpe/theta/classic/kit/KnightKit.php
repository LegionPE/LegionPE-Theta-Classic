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
use legionpe\theta\classic\kit\power\ShieldPower;
use legionpe\theta\classic\kit\power\StrengthPower;
use pocketmine\item\Item;

class KnightKit extends ClassicKit{
	public $id = self::KIT_ID_KNIGHT;
	public function __construct($level){
		$this->setName("Knight");
		$this->setDescription("I have shiny silver armour!");
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
				$inventory->setItem(15 + (++$count), $power->item);
				$inventory->setHotbarSlotIndex(6 + $count, 15 + $count);
			}
		}
		$inventory->setHotbarSlotIndex(5, 5);
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
		$blueDye = Item::get(Item::DYE);
		$blueDye->setDamage(12);
		$orangeDye = Item::get(Item::DYE);
		$orangeDye->setDamage(14);
		switch($level){
			case 1:
				$items = [
					Item::get(Item::GOLDEN_SWORD),
					Item::get(Item::APPLE)
				];
				$armorItems = [
					Item::get(Item::LEATHER_CAP),
					Item::get(Item::GOLD_CHESTPLATE),
					Item::get(Item::LEATHER_PANTS),
					Item::get(Item::GOLD_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new ShieldPower("Shield", "Prevent damage when being hit", $level, $blueDye)
				];
				$this->setPowers($powers);
				$this->setPrice(5000);
				break;
			case 2:
				$items = [
					Item::get(Item::GOLDEN_SWORD),
					Item::get(Item::COOKED_CHICKEN)
				];
				$armorItems = [
					Item::get(Item::GOLD_HELMET),
					Item::get(Item::GOLD_CHESTPLATE),
					Item::get(Item::GOLD_LEGGINGS),
					Item::get(Item::GOLD_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new ShieldPower("Shield", "Prevent damage when being hit", $level, $blueDye)
				];
				$this->setPowers($powers);
				$this->setPrice(7000);
				break;
			case 3:
				$items = [
					Item::get(Item::STONE_SWORD),
					Item::get(Item::COOKED_CHICKEN)
				];
				$armorItems = [
					Item::get(Item::CHAIN_HELMET),
					Item::get(Item::GOLD_CHESTPLATE),
					Item::get(Item::GOLD_LEGGINGS),
					Item::get(Item::CHAIN_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new ShieldPower("Shield", "Prevent damage when being hit", $level, $blueDye),
					new StrengthPower("Strength", "Extra damage while hitting a player", $level, $orangeDye)
				];
				$this->setPowers($powers);
				$this->setPrice(8000);
				break;
			case 4:
				$items = [
					Item::get(Item::STONE_SWORD),
					Item::get(Item::STEAK)
				];
				$armorItems = [
					Item::get(Item::CHAIN_HELMET),
					Item::get(Item::CHAIN_CHESTPLATE),
					Item::get(Item::CHAIN_LEGGINGS),
					Item::get(Item::CHAIN_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new ShieldPower("Shield", "Prevent damage when being hit", $level, $blueDye),
					new StrengthPower("Strength", "Extra damage while hitting a player", $level, $orangeDye)
				];
				$this->setPowers($powers);
				$this->setPrice(12000);
				break;
		}
		$this->level = $level;
	}
}
