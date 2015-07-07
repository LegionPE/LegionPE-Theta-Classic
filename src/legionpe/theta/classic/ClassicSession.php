<?php

/**
 * Theta
 * Copyright (C) 2015 PEMapModder
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace legionpe\theta\classic;

use legionpe\theta\BasePlugin;
use legionpe\theta\lang\Phrases;
use legionpe\theta\Session;
use legionpe\theta\utils\MUtils;
use pocketmine\block\Block;
use pocketmine\entity\Egg;
use pocketmine\entity\Projectile;
use pocketmine\entity\Snowball;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Apple;
use pocketmine\item\IronBoots;
use pocketmine\item\IronChestplate;
use pocketmine\item\IronHelmet;
use pocketmine\item\IronSword;
use pocketmine\item\LeatherPants;
use pocketmine\Player;

class ClassicSession extends Session{
	/** @var BasePlugin */
	private $main;
	/** @var bool */
	private $friendlyFire;
	/** @var float */
	private $lastHurtTime = 0.0;
	/** @var Block|null */
	private $lastDamagePosition = null;
	/** @var float */
	private $lastDamageTime = 0.0;
	/** @var EntityDamageByEntityEvent|null */
	private $lastFallCause = null;
	private $counter = 0;
	public function __construct(BasePlugin $main, Player $player, array $loginData){
		$this->main = $main;
		parent::__construct($player, $loginData);
		$this->setLoginDatum("pvp_init", time());
	}
	public function joinedClassicSince(){
		return $this->getLoginDatum("pvp_init");
	}
	public function addKill(){
		$this->incrLoginDatum("pvp_kills");
		$this->grantCoins(ClassicPlugin::COINS_ON_KILL);
		$this->incrLoginDatum("pvp_curstreak");
	}
	public function addDeath(){
		$this->incrLoginDatum("pvp_deaths");
	}
	public function getKills(){
		return $this->getLoginDatum("pvp_kills");
	}
	public function getDeaths(){
		return $this->getLoginDatum("pvp_deaths");
	}
	public function onDamage(EntityDamageEvent $event){
		if(!parent::onDamage($event)){
			return false;
		}
		$last = $this->lastHurtTime;
		$this->lastHurtTime = microtime(true);
		if($this->lastHurtTime - $last < ClassicConsts::COOLDOWN_TIMEOUT){
			return false;
		}
		if($event instanceof EntityDamageByEntityEvent){
			$fromEnt = $event->getDamager();
			if($fromEnt instanceof Player){
				$ses = $this->getMain()->getSession($fromEnt);
				if($ses instanceof ClassicSession){
					if(ClassicConsts::isSpawn($fromEnt) or ClassicConsts::isSpawn($this->getPlayer())){
						$fromEnt->sendTip($ses->translate(Phrases::PVP_ATTACK_SPAWN));
						return false;
					}
					$type = $this->getFriendType($ses->getUid());
					if($type >= self::FRIEND_LEVEL_GOOD_FRIEND and !$this->isFriendlyFire() and !$ses->isFriendlyFire()){
						$fromEnt->sendTip($this->translate(Phrases::PVP_ATTACK_FRIENDS, [
							"target" => $this->getInGameName(),
							"type" => $this->translate(self::$FRIEND_TYPES[$type])
						]));
						return false;
					}
				}
			}
			$this->lastDamagePosition = $this->getPlayer()->getLevel()->getBlock($this->getPlayer());
			$this->lastDamageTime = microtime(true);
		}elseif($event->getCause() === EntityDamageEvent::CAUSE_FALL){
			$last = $this->getPlayer()->getLastDamageCause();
			if($last instanceof EntityDamageByEntityEvent){
				$this->lastFallCause = $last;
			}
		}
		return true;
	}
	public function getMain(){
		return $this->main;
	}
	/**
	 * @return boolean
	 */
	public function isFriendlyFire(){
		return $this->friendlyFire;
	}
	/**
	 * @param boolean $friendlyFire
	 */
	public function setFriendlyFire($friendlyFire){
		$this->friendlyFire = $friendlyFire;
	}
	public function onDeath(PlayerDeathEvent $event){
		if(!parent::onDeath($event)){
			return false;
		}
		$event->setDeathMessage("");
		$event->setDrops([]);
		$this->incrLoginDatum("pvp_deaths");
		$streak = $this->getCurrentStreak();
		$maxStreak = $this->getMaximumStreak();
		$this->setMaximumStreak(max($streak, $maxStreak));
		$this->setCurrentStreak();
		$cause = $this->getPlayer()->getLastDamageCause();
		if(!($cause instanceof EntityDamageEvent)){
			$this->send(Phrases::PVP_DEATH_GENERIC);
			return true;
		}
		if($cause instanceof EntityDamageByEntityEvent){
			$killer = $cause->getDamager();
			if($killer instanceof Player){
				/** @var static $ks */
				$ks = $this->getMain()->getSession($killer);
				if($ks instanceof Session){
					$kn = $ks->getInGameName();
					$amount = ClassicConsts::getKillHeal($ks);
					$killer->heal($amount, new EntityRegainHealthEvent($killer, $amount, EntityRegainHealthEvent::CAUSE_CUSTOM));
				}
			}
			if(!isset($kn)){
				$kn = (new \ReflectionClass($killer))->getShortName();
				MUtils::word_camelToStd($kn);
				MUtils::word_addSingularArticle($kn);
			}
			$data = ["killer" => $kn, "action" => $this->translate(Phrases::PVP_ACTION_GENERIC)];
			if($cause instanceof EntityDamageByChildEntityEvent){
				$child = $cause->getChild();
				if($child instanceof Snowball){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_SNOWBALL);
				}elseif($child instanceof Egg){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_EGG);
				}elseif($child instanceof Projectile){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_ARROW);
				}
			}
			$this->send(Phrases::PVP_DEATH_KILLED, $data);
			if(isset($ks) and ($ks instanceof Session)){
				$data["victim"] = $this->getInGameName();
				$ks->send(Phrases::PVP_KILL_KILLED, $data);
			}
		}elseif($cause->getCause() === EntityDamageEvent::CAUSE_FALL and microtime(true) - $this->lastDamageTime < 2.5){
			$knock = $this->lastFallCause;
			$pos = $this->lastDamagePosition;
			$id = $pos->getId();
			if($id === Block::LADDER){
				$deathPhrase = Phrases::PVP_DEATH_FALL_LADDER;
				$killPhrase = Phrases::PVP_KILL_FALL_LADDER;
			}elseif($id === Block::VINE){
				$deathPhrase = Phrases::PVP_DEATH_FALL_VINE;
				$killPhrase = Phrases::PVP_KILL_FALL_VINE;
			}else{
				$deathPhrase = Phrases::PVP_DEATH_FALL_GENERIC;
				$killPhrase = Phrases::PVP_KILL_FALL_GENERIC;
			}
			$killer = $knock->getDamager();
			if($killer instanceof Player and ($ks = $this->getMain()->getSession($killer)) instanceof Session){
				$kn = $ks->getInGameName();
			}else{
				$kn = (new \ReflectionClass($killer))->getShortName();
				MUtils::word_camelToStd($kn);
				MUtils::word_addSingularArticle($kn);
			}
			$data = ["killer" => $kn, "victim" => $this->getInGameName()];
			if($knock instanceof EntityDamageByChildEntityEvent){
				$child = $knock->getChild();
				if($child instanceof Snowball){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_SNOWBALL);
				}elseif($child instanceof Egg){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_EGG);
				}elseif($child instanceof Projectile){
					$data["action"] = $this->translate(Phrases::PVP_ACTION_ARROW);
				}
			}
			$this->send($deathPhrase, $data);
			if(isset($ks) and $ks instanceof Session){
				$ks->send($killPhrase, $data);
			}
		}
		return true;
	}
	public function getCurrentStreak(){
		return $this->getLoginDatum("pvp_curstreak");
	}
	public function getMaximumStreak(){
		return $this->getLoginDatum("pvp_maxstreak");
	}
	public function setMaximumStreak($kills){
		$this->setLoginDatum("pvp_maxstreak", $kills);
	}
	public function setCurrentStreak($kills = 0){
		$this->setLoginDatum("pvp_curstreak", $kills);
	}
	public function login($method){
		parent::login($method);
		$this->onRespawn(new PlayerRespawnEvent($this->getPlayer(), $this->getPlayer()->getPosition()));
	}
	public function onRespawn(PlayerRespawnEvent $event){
		parent::onRespawn($event);
		$event->setRespawnPosition(ClassicConsts::getSpawnPosition($this->getMain()->getServer()));
		$inv = $this->getPlayer()->getInventory();
		$inv->clearAll();
		$inv->setHelmet(new IronHelmet);
		$inv->setChestplate(new IronChestplate);
		$inv->setLeggings(new LeatherPants);
		$inv->setBoots(new IronBoots);
		$inv->addItem(new IronSword, new Apple);
		$inv->sendContents([$this->getPlayer()]);
	}
	public function halfSecondTick(){
		parent::halfSecondTick();
		if((++$this->counter) === 10){
			$this->counter = 0;
			if($this->getPlayer()->getHealth() !== $this->getPlayer()->getMaxHealth()){
				$amount = ClassicConsts::getAutoHeal($this);
				$this->getPlayer()->heal($amount, new EntityRegainHealthEvent($this->getPlayer(), $amount, EntityRegainHealthEvent::CAUSE_REGEN));
			}
		}
	}
}
