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
use legionpe\theta\classic\kit\power\IceWaterPower;
use legionpe\theta\classic\kit\power\ShieldPower;
use legionpe\theta\classic\kit\power\StrengthPower;
use legionpe\theta\classic\utils\ResetBlocksTask;
use pocketmine\item\Item;

class FrozoneKit extends ClassicKit{
	/** @var \legionpe\theta\classic\utils\ResetBlocksTask */
	private $task;
	public $id = self::KIT_ID_FROZONE;
	public function __construct($level, ResetBlocksTask $task){
		$this->setName("Frozone");
		$this->setDescription("Ice.");
		$this->setLevel($level);
		$this->task = $task;
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
		$cyanDye = Item::get(Item::DYE);
		$cyanDye->setDamage(6);
		switch($level){
			case 1:
				$items = [
					Item::get(Item::WOODEN_SWORD),
					Item::get(Item::MELON_SLICE)
				];
				$armorItems = [
					Item::get(Item::GOLD_HELMET),
					Item::get(Item::CHAIN_CHESTPLATE),
					Item::get(Item::LEATHER_PANTS),
					Item::get(Item::LEATHER_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new IceWaterPower("Water to ice", "Change water to ice so you can walk over it.", $level, $blueDye, $this->task)
				];
				$this->setPowers($powers);
				$this->setPrice(3500);
				break;
			case 2:
				$items = [
					Item::get(Item::GOLDEN_SWORD),
					Item::get(Item::APPLE)
				];
				$armorItems = [
					Item::get(Item::GOLD_HELMET),
					Item::get(Item::CHAIN_CHESTPLATE),
					Item::get(Item::LEATHER_PANTS),
					Item::get(Item::GOLD_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new IceWaterPower("Water to ice", "Change water to ice so you can walk over it.", $level, $blueDye, $this->task)
				];
				$this->setPowers($powers);
				$this->setPrice(6500);
				break;
			case 3:
				$items = [
					Item::get(Item::GOLDEN_SWORD),
					Item::get(Item::COOKED_CHICKEN)
				];
				$armorItems = [
					Item::get(Item::GOLD_HELMET),
					Item::get(Item::CHAIN_CHESTPLATE),
					Item::get(Item::LEATHER_PANTS),
					Item::get(Item::GOLD_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new IceWaterPower("Water to ice", "Change water to ice so you can walk over it.", $level, $blueDye, $this->task),
					new IceWaterPower("Ground to ice", "Change the ground to ice so you can walk over it faster.", $level, $cyanDye, $this->task)
				];
				$this->setPowers($powers);
				$this->setPrice(6500);
				break;
			case 4:
				$items = [
					Item::get(Item::STONE_SWORD),
					Item::get(Item::COOKED_CHICKEN)
				];
				$armorItems = [
					Item::get(Item::GOLD_HELMET),
					Item::get(Item::CHAIN_CHESTPLATE),
					Item::get(Item::GOLD_LEGGINGS),
					Item::get(Item::GOLD_BOOTS)
				];
				$this->setItems($items, $armorItems);
				$powers = [
					new IceWaterPower("Water to ice", "Change water to ice so you can walk over it.", $level, $blueDye, $this->task),
					new IceWaterPower("Ground to ice", "Change the ground to ice so you can walk over it faster.", $level, $cyanDye, $this->task)
				];
				$this->setPowers($powers);
				$this->setPrice(10000);
				break;
		}
	}
}
