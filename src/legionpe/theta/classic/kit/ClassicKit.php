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

abstract class ClassicKit{
	CONST KIT_ID_DEFAULT = 0, KIT_ID_FROZONE = 1, KIT_ID_KNIGHT = 2, KIT_ID_PYRO = 3;
	/** @var string */
	protected $name;
	public $level;
	public $id;
	public $maxLevel = 4;
	/** @var int */
	private $price;
	/** @var string */
	protected $description;
	/** @var power\ClassicKitPower[] */
	protected $powers = [];
	/** @var \pocketmine\item\Item[] */
	protected $items = [];
	/** @var \pocketmine\item\Item[] */
	protected $armorItems = [];
	public abstract function setLevel($level);
	public abstract function equip(ClassicSession $session);

	/**
	 * @param $id
	 * @return mixed
	 */
	public static function getKitClassById($id){
		switch($id){
			case self::KIT_ID_DEFAULT:
				return DefaultKit::class;
			case self::KIT_ID_FROZONE:
				return FrozoneKit::class;
			case self::KIT_ID_KNIGHT:
				return KnightKit::class;
			case self::KIT_ID_PYRO:
				return PyroKit::class;
		}
	}
	/**
	 * @param string $name
	 */
	protected function setName($name){
		$this->name = $name;
	}
	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}
	/**
	 * @return int
	 */
	public function getPrice(){
		return $this->price;
	}
	/**
	 * @param int $price
	 */
	protected function setPrice($price){
		$this->price = $price;
	}
	/**
	 * @param string $description
	 */
	protected function setDescription($description){
		$this->description = $description;
	}
	/**
	 * @return string
	 */
	public function getDescription(){
		return $this->description;
	}
	/**
	 * @param \pocketmine\item\Item[] $items
	 * @param \pocketmine\item\Item[] $armorItems
	 */
	protected function setItems($items, $armorItems){
		$this->items = $items;
		$this->armorItems = $armorItems;
	}
	/**
	 * @return \pocketmine\item\Item[]
	 */
	protected function getItems(){
		return $this->items;
	}
	/**
	 * @return \pocketmine\item\Item[]
	 */
	protected function getArmorItems(){
		return $this->armorItems;
	}
	/**
	 * @param power\ClassicKitPower[] $powers
	 */
	protected function setPowers($powers){
		$this->powers = $powers;
	}
	/**
	 * @return power\ClassicKitPower[]
	 */
	public function getPowers(){
		return $this->powers;
	}
}
