<?php

interface CRC32Interface {
    function update($buffer);
    function reset();
    function hash(bool $raw_output = false) : string;

    function version() : string;
}

// TODO Make sure the following two functions are private!

function int2hex($i) : string
{
    return str_pad(dechex($i), 8, '0', STR_PAD_LEFT);
}

function crc_hash($crc, bool $raw_output = false) : string
{
    $crc = $crc & 0xffffffff;
    if ($raw_output) {
        return pack('L', $crc); // TODO Test this works
    }
    return int2hex($crc);
}


abstract class CRC32 {
    // IEEE is used by ethernet (IEEE 802.3), v.42, fddi, gzip, zip, png, ...
    const IEEE = 0xedb88320;

    // Castagnoli's polynomial, used in iSCSI, SCTP, Google Cloud Storage,
    // Apache Kafka, and has hardware-accelerated in modern CPUs.
    // https://dx.doi.org/10.1109/26.231911
    const CASTAGNOLI = 0x82f63b78;

    // Koopman's polynomial.
    // https://dx.doi.org/10.1109/DSN.2002.1028931
    const KOOPMAN = 0xeb31d82e;

    // The size of the checksum in bytes.
    const SIZE = 4;

    private static function has_builtin($algo) : bool {
        return in_array($algo, hash_algos());
    }

    public static function create(int $polynomial) : CRC32Interface
    {
        if ($polynomial === self::IEEE) {
            if (self::has_builtin('crc32b')) {
                return new CRC32_Builtin('crc32b');
            }
        }

        if ($polynomial === self::CASTAGNOLI) {
            if (function_exists('crc32c')) {
                return new CRC32C_Google();
            }

            if (self::has_builtin('crc32c')) {
                return new CRC32_Builtin('crc32b');
            } 
        }

        // Fallback to the pure PHP version
        return new CRC32_PHP($polynomial);
    }
}

// TODO Allow the classes to be copied
// TODO Document
// // Use the Google Hardware Accelerated version
final class CRC32C_Google implements CRC32Interface
{
    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->crc = hex2bin('00000000');
    }

    public function update($buffer)
    {
        crc32c($buffer, $this->crc);
    }

    public function hash(bool $raw_output = false) : string
    {
        if ($raw_output) {
            return $this->crc;
        }
        return bin2hex($this->crc);
    }

    public function version() : string
    {
        return 'Hardware accelerated (https://github.com/google/crc32c)'; 
    }
}

// TODO Document
final class CRC32_Builtin implements CRC32Interface
{
    public function __construct(string $algo)
    {
        // TODO Check this is actually a crc algo
        $this->algo = $algo;
        $this->reset();
    }

    public function reset()
    {
        $this->hc = hash_init($this->algo);
    }

    public function update($buffer)
    {
        hash_update($this->hc, $buffer);
    }

    public function hash(bool $raw_output = false) : string
    {
        return hash_final($this->hc, $raw_output);
    }

    public function version() : string
    {
        return $this->algo . ' PHP HASH';
    }
}

final class CRC32Table
{
    private static $tables = array();

    static function print(array $table)
    {
        foreach ($table as $i => $value) {
            echo "0x" . int2hex($value) . ",";
            if ($i % 4 == 3) {
                echo "\n";
            } else {
                echo " ";
            }
        }

        echo "\n\n";
    }

    static function get(int $polynomial) : array
    {
        if (array_key_exists($polynomial, self::$tables)) {
            return self::$tables[$polynomial];
        }
        self::$tables[$polynomial] = self::create($polynomial);
        return self::$tables[$polynomial];
    }

    static function create(int $polynomial) : array
    {
        $table = array_fill(0, 255, 0);

        for ($i = 0; $i < 256; $i++) {
            $crc = $i;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 1 == 1) {
                    $crc = ($crc >> 1) ^ $polynomial;
                } else {
                    $crc >>= 1;
                }
            }
            $table[$i] = $crc;
        }

        return $table;
    }
}

// TODO Class comments
// Pure PHP implementation of the CRC32 algorithm.
final class CRC32_PHP implements CRC32Interface
{
    private $table = array();

    public function __construct(int $polynomial)
    {
        $this->polynomial = $polynomial;
        $this->table = CRC32Table::get($polynomial);
        $this->reset();
    }

    public function reset()
    {
        $this->crc = ~0;
    }

    public function update($buffer)
    {
        $crc = $this->crc;
        $len = strlen($buffer);
        for ($i = 0; $i < $len; ++$i) {
            $crc = (($crc >> 8) & 0xffffff) ^ $this->table[($crc ^ ord($buffer[$i])) & 0xff];
        }
        $this->crc = $crc;
    }

    public function hash(bool $raw_output = false) : string
    {
        return crc_hash(~$this->crc, $raw_output);
    }

    public function version() : string
    {
        return 'crc32(' . int2hex($this->polynomial) . ') software version';
    }
}