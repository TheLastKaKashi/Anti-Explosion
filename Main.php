<?php

declare(strict_types=1);

namespace AntiExplosion;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\object\EndCrystal;
use pocketmine\block\TNT;
use pocketmine\block\RespawnAnchor;
use pocketmine\block\Fire;
use pocketmine\block\SoulFire;
use pocketmine\block\Lava;
use pocketmine\item\FlintSteel;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("§aAntiExplosion enabled — TNT, crystals, anchors & fire blocked!");
    }

    /**
     * Main explosion cancel (TNT, crystal, creeper, etc.) + despawn TNT/crystal
     * @priority HIGHEST
     */
    public function onPreExplode(EntityPreExplodeEvent $event): void {
        $event->cancel();
        $entity = $event->getEntity();
        if ($entity instanceof PrimedTNT || $entity instanceof EndCrystal) {
            $entity->flagForDespawn();
        }
    }

    /**
     * Backup if anything still tries to explode
     * @priority HIGHEST
     */
    public function onExplode(EntityExplodeEvent $event): void {
        $event->cancel();
        $event->setBlockList([]);
        $entity = $event->getEntity();
        if ($entity instanceof PrimedTNT || $entity instanceof EndCrystal) {
            $entity->flagForDespawn();
        }
    }

    /**
     * Block TNT ignite, Respawn Anchor, Flint & Steel, Fire Charge
     * @priority HIGHEST
     */
    public function onInteract(PlayerInteractEvent $event): void {
        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            return;
        }

        $block = $event->getBlock();
        $item = $event->getItem();
        $player = $event->getPlayer();

        if ($block instanceof TNT) {
            $event->cancel();
            $player->sendTip("§cTNT is disabled!");
            return;
        }

        if ($block instanceof RespawnAnchor) {
            $event->cancel();
            $player->sendTip("§cRespawn Anchor is disabled!");
            return;
        }

        if ($item instanceof FlintSteel) {
            $event->cancel();
            $player->sendTip("§cFire is disabled!");
            return;
        }

        // Fire charge (no dedicated class needed)
        if ($item->getTypeId() === VanillaItems::FIRE_CHARGE()->getTypeId()) {
            $event->cancel();
            $player->sendTip("§cFire is disabled!");
        }
    }

    /**
     * No lava placement from bucket
     * @priority HIGHEST
     */
    public function onBucketEmpty(PlayerBucketEmptyEvent $event): void {
        if ($event->getBucket()->getTypeId() === VanillaItems::LAVA_BUCKET()->getTypeId()) {
            $event->cancel();
            $event->getPlayer()->sendTip("§cLava is disabled!");
        }
    }

    /**
     * Fire cannot burn blocks
     * @priority HIGHEST
     */
    public function onBurn(BlockBurnEvent $event): void {
        $event->cancel();
    }

    /**
     * Fire / lava cannot spread
     * @priority HIGHEST
     */
    public function onSpread(BlockSpreadEvent $event): void {
        $new = $event->getNewState();
        if ($new instanceof Fire || $new instanceof SoulFire || $new instanceof Lava) {
            $event->cancel();
        }
    }

    /**
     * No damage from explosions or fire/lava
     * @priority HIGHEST
     */
    public function onDamage(EntityDamageEvent $event): void {
        $cause = $event->getCause();
        if (
            $cause === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION
            || $cause === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION
            || $cause === EntityDamageEvent::CAUSE_FIRE
            || $cause === EntityDamageEvent::CAUSE_FIRE_TICK
            || $cause === EntityDamageEvent::CAUSE_LAVA
        ) {
            $event->cancel();
        }
    }
}
