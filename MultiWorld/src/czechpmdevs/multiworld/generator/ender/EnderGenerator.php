<?php

/**
 * MultiWorld - PocketMine plugin that manages worlds.
 * Copyright (C) 2018 - 2020  CzechPMDevs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace czechpmdevs\multiworld\generator\ender;

use czechpmdevs\multiworld\generator\ender\populator\EnderPilar;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\biome\BiomeSelector;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\level\generator\noise\Noise;
use pocketmine\level\generator\noise\Simplex;
use pocketmine\level\generator\populator\Populator;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\utils\Random;

/**
 * Class EnderGenerator
 * @package czechpmdevs\multiworld\Generator\ender
 */
class EnderGenerator extends Generator {

    private static $GAUSSIAN_KERNEL = null;
    private static $SMOOTH_SIZE = 2;

    /** @var Populator[] */
    private $populators = [];

    /** @var ChunkManager */
    protected $level;

    /** @var Random */
    protected $random;

    private $waterHeight = 0;
    private $emptyHeight = 32;
    private $emptyAmplitude = 1;
    private $density = 0.6;

    /** @var Populator[] */
    private $generationPopulators = [];

    /** @var Simplex */
    private $noiseBase;

    /**
     * VoidGenerator constructor.
     *
     * @param ChunkManager $level
     * @param int $seed
     * @param array $settings
     */
    public function __construct(ChunkManager $level, int $seed, array $settings = []){
        parent::__construct($level, $seed, $settings);
        if (self::$GAUSSIAN_KERNEL === null) {
            self::generateKernel();
        }
        $this->noiseBase = new Simplex($this->random, 4, 1 / 4, 1 / 64);
        $pilar = new EnderPilar;
        $pilar->setBaseAmount(0);
        $pilar->setRandomAmount(0);
        $this->populators[] = $pilar;
    }

    private static function generateKernel() {
        self::$GAUSSIAN_KERNEL = [];
        $bellSize = 1 / self::$SMOOTH_SIZE;
        $bellHeight = 2 * self::$SMOOTH_SIZE;
        for ($sx = -self::$SMOOTH_SIZE; $sx <= self::$SMOOTH_SIZE; ++$sx) {
            self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE] = [];
            for ($sz = -self::$SMOOTH_SIZE; $sz <= self::$SMOOTH_SIZE; ++$sz) {
                $bx = $bellSize * $sx;
                $bz = $bellSize * $sz;
                self::$GAUSSIAN_KERNEL[$sx + self::$SMOOTH_SIZE][$sz + self::$SMOOTH_SIZE] = $bellHeight * exp(-($bx * $bx + $bz * $bz) / 2);
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "ender";
    }

    /**
     * @return int
     */
    public function getWaterHeight(): int {
        return $this->waterHeight;
    }

    /**
     * @return array
     */
    public function getSettings(): array {
        return [];
    }


    /**
     * @param int $chunkX
     * @param int $chunkZ
     */
    public function generateChunk(int $chunkX, int $chunkZ): void {
        $this->random->setSeed(0xa6fe78dc ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
        if(class_exists(GeneratorManager::class)) {
            $noise = $this->noiseBase->getFastNoise3D(16, 128, 16, 4, 8, 4, $chunkX * 16, 0, $chunkZ * 16);
        }
        else {
            $noise = $this->noiseBase->getFastNoise3D(16, 128, 16, 4, 8, 4, $chunkX * 16, 0, $chunkZ * 16);
        }

        $chunk = $this->level->getChunk($chunkX, $chunkZ);
        for ($x = 0; $x < 16; ++$x) {
            for ($z = 0; $z < 16; ++$z) {
                // 9 = biome end
                $chunk->setBiomeId($x, $z, 9);
                for ($y = 0; $y < 128; ++$y) {
                    $noiseValue = (abs($this->emptyHeight - $y) / $this->emptyHeight) * $this->emptyAmplitude - $noise[$x][$z][$y];
                    $noiseValue -= 1 - $this->density;
                    $distance = new Vector3(0, 64, 0);
                    $distance = $distance->distance(new Vector3($chunkX * 16 + $x, ($y / 1.3), $chunkZ * 16 + $z));
                    if ($noiseValue < 0 && $distance < 100 or $noiseValue < -0.2 && $distance > 400) {
                        $chunk->setBlockId($x, $y, $z, Block::END_STONE);
                    }
                }
            }
        }
        foreach ($this->generationPopulators as $populator) {
            $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
        }
    }

    /**
     * @param int $chunkX
     * @param int $chunkZ
     */
    public function populateChunk(int $chunkX, int $chunkZ): void {
        $this->random->setSeed(0xa6fe78dc ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);
        foreach ($this->populators as $populator) {
            $populator->populate($this->level, $chunkX, $chunkZ, $this->random);
        }
    }

    /**
     * @return Vector3
     */
    public function getSpawn():Vector3 {
        return new Vector3(48, 128, 48);
    }
}