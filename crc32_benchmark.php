<?php
declare(strict_types=1);

require('crc32.php');

define('duration', 10);    // Duration of test in seconds.
define('chunk', 1024 * 8); // Size of chunk to read from disk.

interface Benchmark
{
    public function init();
    public function update($buf);
    public function finish();
}

class Native implements Benchmark
{
    private $crc;

    public function init()
    {
        $this->crc = hash_init('crc32b');
    }
    public function update($buf)
    {
        hash_update($this->crc, $buf);
    }
    public function finish()
    {
        return hash_final($this->crc);
    }
}

class PurePHP implements Benchmark
{
    private $crc;

    public function init()
    {
        $this->crc = new CRC32(CRC32::IEEE);
    }
    public function update($buf)
    {
        $this->crc->update($buf);
    }
    public function finish()
    {
        return $this->crc->hash();
    }
}

function test($name, $test)
{
    $fp = fopen('/dev/urandom', 'rb');
    if ($fp === false) {
        exit("failed to open file");
    }

    $test->init();

    //xdebug_start_trace();

    $now = microtime(true);
    $start = $now;
    $offset = 0;

    while (($now - $start) < duration) {
        $buf = fread($fp, chunk);
        if ($buf === false) {
            exit("failed to read file");
        }

        $test->update($buf);
        $offset += strlen($buf);

        $now = microtime(true);
    }

    $test->finish();

    echo sprintf("%s %0.2f MB/s\n", $name, $offset / ($now - $start) / 1000000);

    fclose($fp);
}

                                // Tested on my mid-2014 MacBook Pro
test('native', new Native());   // 12.36 MB/s
test('purephp', new PurePHP()); // 6.20 MB/s
