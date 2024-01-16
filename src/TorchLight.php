<?php

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;

class TorchLight extends PluginBase
{
    private static array $placedBlocks = [];

    public function onEnable() : void
    {
        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(
                function () : void
                {
                    foreach ($this->getServer()->getOnlinePlayers() as $player)
                    {
                        $itemInHand = $player->getInventory()->getItemInHand();
                        self::add($player, $itemInHand);
                        self::remove($player, $itemInHand);
                    }
                }
            ),
            1
        );
    }

    /**
     * @param Player $player
     * @param Item $itemInHand
     * @return void
     */
    public static function add(Player $player, Item $itemInHand) : void
    {
        if ($itemInHand->getTypeId() === VanillaBlocks::TORCH()->asItem()->getTypeId())
        {
            for ($y = 0; $y < 2; $y++)
            {
                $world = $player->getWorld();
                $position = $player->getPosition()->add(0, $y, 0);
                $block = $world->getBlock($position);
                if (($block->getTypeId() === VanillaBlocks::AIR()->getTypeId()))
                {
                    $world->orderChunkPopulation(($position->getFloorX() >> Chunk::COORD_BIT_SIZE), ($position->getFloorZ() >> Chunk::COORD_BIT_SIZE), null)->onCompletion(
                        function () use ($world, $position) : void
                        {
                            $world->setBlock($position, VanillaBlocks::LIGHT()->setLightLevel(14));
                            self::$placedBlocks[] = $position;
                        },
                        fn () => null
                    );
                }
            }
        }
    }

    /**
     * @param Player $player
     * @param Item $itemInHand
     * @return void
     */
    public static function remove(Player $player, Item $itemInHand) : void
    {
        /**
         * @var int $key
         * @var Position $position
         */
        foreach (self::$placedBlocks as $key => $position)
        {
            if (($position->distance($player->getPosition()) >= 2) or ($itemInHand->getTypeId() !== VanillaBlocks::TORCH()->asItem()->getTypeId()))
            {
                $world = $player->getWorld();
                if ($world->getBlock($position)->getTypeId() === VanillaBlocks::LIGHT()->getTypeId())
                {
                    $world->setBlock($position, VanillaBlocks::AIR());
                    unset(self::$placedBlocks[$key]);
                }
            }
        }
    }
}
