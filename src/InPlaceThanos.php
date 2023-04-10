<?php

use Aternos\Thanos\World\AnvilWorld;

class InPlaceThanos extends \Aternos\Thanos\Thanos
{
    public function snap(AnvilWorld $world): int
    {
        $removedChunks = 0;
        $output = tempnam(sys_get_temp_dir(), 'thanos');

        foreach ($world->getRegionDirectories() as $regionDirectory) {
            $forcedChunks = $this->getForceLoadedChunks($regionDirectory);
            foreach ($regionDirectory->getRegionFiles() as $regionFile) {
                $regionFile = $regionDirectory->getPath() . DIRECTORY_SEPARATOR . $regionFile;
                $region = new \Aternos\Thanos\Region\AnvilRegion($regionFile, $output);
                foreach ($region->getChunks() as $chunk) {
                    if(in_array([$chunk->getGlobalXPos(), $chunk->getGlobalYPos()], $forcedChunks, true)) {
                        $chunk->save();
                        continue;
                    }
                    $time = $chunk->getInhabitedTime();
                    if ($time > $this->minInhabitedTime || ($time === -1 && !$this->removeUnknownChunks)) {
                        $chunk->save();
                    } else {
                        $removedChunks++;
                    }
                }
                $region->save();

                if (file_exists($output)) {
                    $old = $regionFile . '.old';
                    if (file_exists($old)) {
                        unlink($old);
                    }
                    rename($regionFile, $old);
                    rename($output, $regionFile);
                    if (file_exists($regionFile)) {
                        unlink($old);
                    }
                } else {
                    unlink($regionFile);
                }
            }
        }

        return $removedChunks;
    }
}
