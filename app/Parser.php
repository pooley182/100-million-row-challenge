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
use function count;
use function ksort;
use function json_encode;
use function fwrite;

final class Parser
{
    public function parse(string $inputPath, string $outputPath): void
    {
        $fileHandle = fopen($inputPath, 'rb');
        
        stream_set_read_buffer($fileHandle, 1 << 27); // 128MB buffer
        gc_disable();

        $output = [];

        while(($line = fgets($fileHandle)) !== false) {
            $comma = strpos($line, ',');
            $path = substr($line, 19, $comma - 19);
            $date = substr($line, $comma + 1, 10);
            
            if (!isset($output[$path][$date])){
                $output[$path][$date] = 1;
            } else {
                $output[$path][$date]++;
            }
        }

        fclose($fileHandle);
        gc_enable();
        unset($fileHandle, $line);

        foreach ($output as &$dates) {
            if (count($dates) > 1) {
                ksort($dates, SORT_STRING);
            }
        }
        unset($dates);

        $json = json_encode($output, JSON_PRETTY_PRINT);
        unset($output);
        $fileHandle = fopen($outputPath, 'wb');
        fwrite($fileHandle, $json);
        fclose($fileHandle);
        
    }
}