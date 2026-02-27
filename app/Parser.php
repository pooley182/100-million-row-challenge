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
use function str_replace;
use function fwrite;
use function str_pad;
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
                    $date = $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT) . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
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


        $json ="{\n";
        $first = true;
        foreach($output as $path =>$dates) {
            if(!$first) {
                $json .= ",\n";
            }
            $json .= '    "' . str_replace('/','\\/', $path) . '"' . ": {\n";
            $first_date = true;
            foreach($dates as $date => $count) {
                if($count === 0) continue;
                if(!$first_date) {
                    $json .= ",\n";
                }
                    $json .= '        "' . $date . '": ' . $count;
                $first_date = false;
            }
            $json .= "\n    }";
            $first = false;
        }
        $json .= "\n}";

        $fileHandle = fopen($outputPath, 'wb');
        fwrite($fileHandle, $json);
        fclose($fileHandle);
        
    }
}