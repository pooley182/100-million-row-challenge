<?php

namespace App;

use function fopen;
use function stream_set_read_buffer;
use function gc_disable;
use function fgets;
use function strpos;
use function substr;
use function fclose;
use function gc_enable;
use function json_encode;
use function fwrite;
use function sprintf;
use function array_filter;

final class Parser
{
    public function parse(string $inputPath, string $outputPath): void
    {
        gc_disable();

        $output = [];
        $dates = [];
        for ($y = 2020; $y <= 2026; $y++) {
            for ($m = 1; $m <= 12; $m++) {
                $days = match ($m) {
                    2 => ($y === 2020) || ($y === 2024) ? 29 : 28,
                    4, 6, 9, 11 => 30,
                    default => 31,
                };
                for ($d = 1; $d <= $days; $d++) {
                    $date = sprintf('%04d-%02d-%02d', $y, $m, $d);
                    $dates[$date] = 0;

                }
            }
        }
        unset($y, $m, $d, $date);

        $fileHandle = fopen($inputPath, 'rb');
        
        stream_set_read_buffer($fileHandle, 1 << 27); // 128MB buffer

        while(($line = fgets($fileHandle)) !== false) {
            $comma = strpos($line, ',');
            $path = substr($line, 19, $comma - 19);
            $date = substr($line, $comma + 1, 10);

            if(!isset($output[$path])) {
                $output[$path] = $dates;
            }

            $output[$path][$date]++;

        }

        fclose($fileHandle);
        gc_enable();
        unset($fileHandle, $line);

        foreach($output as $p =>$ds) {
            $output[$p] = array_filter($ds);
        }
        unset($p, $ds);

        $json = json_encode($output, JSON_PRETTY_PRINT);
        unset($output);
        $fileHandle = fopen($outputPath, 'wb');
        fwrite($fileHandle, $json);
        fclose($fileHandle);
        
    }
}