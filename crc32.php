<?php
/**
 * Various CRC32 implementations.
 *
 * <code>
 * require 'crc32.php';
 *
 * $crc = CRC32::create(CRC32::CASTAGNOLI);
 * $crc->update('hello');
 * echo $crc->hash();
 * </code>
 */

/**
 * CRC calculation interface.
 *
 * Lots of great info on the different algorithms used:
 * https://create.stephan-brumme.com/crc32/
 */
interface CRCInterface
{
    /**
     * Updates the CRC calculation with the supplied data.
     *
     * @param  string  $data  The data
     */
    public function update(string $data);

    /**
     * Resets the CRC calculation.
     */
    public function reset();

    /**
     * Return the current calculated CRC hash.
     *
     * @param boolean  $raw_output  When set to TRUE, outputs raw binary data.
     *                              FALSE outputs lowercase hexits.
     *
     * @return string  Returns a string containing the calculated CRC as
     *                 lowercase hexits unless raw_output is set to true in
     *                 which case the raw binary representation of the CRC is
     *                 returned.
     */
    public function hash(bool $raw_output = false) : string;

    /**
     * Returns information about the CRC implementation and polynomial.
     *
     * @return  string
     */
    public function version() : string;
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


abstract class CRC32
{
    /**
     * IEEE polynomial as used by ethernet (IEEE 802.3), v.42, fddi, gzip,
     * zip, png, ...
     */
    const IEEE = 0xedb88320;

    /**
     * Castagnoli's polynomial, used in iSCSI, SCTP, Google Cloud Storage,
     * Apache Kafka, and has hardware-accelerated in modern intel CPUs.
     * https://dx.doi.org/10.1109/26.231911
     */
    const CASTAGNOLI = 0x82f63b78;

    /**
     * Koopman's polynomial.
     * https://dx.doi.org/10.1109/DSN.2002.1028931
     */
    const KOOPMAN = 0xeb31d82e;

    /**
     * The size of the checksum in bytes.
     */
    const SIZE = 4;

    /**
     * Returns the best CRC implementation available on this machine.
     *
     * @param  integer  $polynomial  The CRC polynomial. Use a 32-bit number,
     *                               or one of the supplied constants, CRC32::IEEE,
     *                               CRC32::CASTAGNOLI, or CRC32::KOOPMAN.
     *
     * @return  CRC32Interface
     */
    public static function create(int $polynomial) : CRC32Interface
    {
        if (CRC32C_Google::supports($polynomial)) {
            return new CRC32C_Google();
        }

        if (CRC32_Builtin::supports($polynomial)) {
            return new CRC32_Builtin($polynomial);
        }

        // Fallback to the pure PHP version
        return new CRC32_PHP($polynomial);
    }

    private static $mapping = array(
        self::IEEE => 'IEEE',
        self::CASTAGNOLI => 'Castagnoli',
        self::KOOPMAN => 'Koopman',
    );

    public static function string(int $polynomial): string
    {
        if (array_key_exists($polynomial, self::$mapping)) {
            return self::$mapping[$polynomial];
        }
        return '0x' . int2hex($polynomial);
    }
}

/**
 * A CRC32 implementation based on the PHP hash functions.
 */
final class CRC32_Builtin implements CRCInterface
{
    private static $mapping = array(
        CRC32::IEEE => 'crc32b',
        CRC32::CASTAGNOLI => 'crc32c',
    );

    public static function supports($polynomial) : bool
    {
        if (!array_key_exists($polynomial, self::$mapping)) {
            throw new Exception("Unsupported polynomial.");
        }
        $algo = self::$mapping[$polynomial];
        return in_array($algo, hash_algos());
    }

    public function __construct(int $polynomial)
    {
        if (!self::supports($polynomial)) {
            throw new Exception("Unsupported polynomial.");
        }

        $this->algo = self::$mapping[$polynomial];
        $this->reset();
    }

    public function reset()
    {
        $this->hc = hash_init($this->algo);
    }

    public function update(string $data)
    {
        hash_update($this->hc, $data);
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

/**
 * A CRC32 implementation using hardware acceleration.
 *
 * This uses the C++ https://github.com/google/crc32c library, thus depends on
 * the `crc32c` PHP extension.
 */
final class CRC32C_Google implements CRCInterface
{
    public static function supports($algo) : bool
    {
        return $algo == CRC32::CASTAGNOLI;
    }

    public function __construct()
    {
        if (!function_exists('crc32c')) {
            throw new Exception("Please load the 'crc32c' extension.");
        }
        $this->reset();
    }

    public function reset()
    {
        $this->crc = hex2bin('00000000');
    }

    public function update(string $data)
    {
        $this->crc = crc32c($data, $this->crc);
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

final class CRC32Table
{
    private static $tables = array();

    public static function print(array $table)
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

    public static function get(int $polynomial) : array
    {
        if (array_key_exists($polynomial, self::$tables)) {
            return self::$tables[$polynomial];
        }
        self::$tables[$polynomial] = self::create($polynomial);
        return self::$tables[$polynomial];
    }

    /**
     * Create a CRC table.
     *
     * @param  integer  $polynomial  The polynomial.
     *
     * @return  array  The table.
     */
    public static function create(int $polynomial) : array
    {
        $table = array_fill(0, 256, 0);

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

    /**
     * Create a CRC table sliced by 4.
     *
     * @param  integer  $polynomial  The polynomial.
     *
     * @return  array  The table.
     */
    public static function create4(int $polynomial) : array
    {
        $table = array_fill(0, 4, array_fill(0, 256, 0));
        $table[0] = self::create($polynomial);

        for ($i = 0; $i < 256; $i++) {
            // for Slicing-by-4 and Slicing-by-8
            $table[1][$i] = ($table[0][$i] >> 8) ^ $table[0][$table[0][$i] & 0xFF];
            $table[2][$i] = ($table[1][$i] >> 8) ^ $table[0][$table[1][$i] & 0xFF];
            $table[3][$i] = ($table[2][$i] >> 8) ^ $table[0][$table[2][$i] & 0xFF];

            /*
            // only Slicing-by-8
            $table[4][$i] = ($table[3][$i] >> 8) ^ $table[0][$table[3][$i] & 0xFF];
            $table[5][$i] = ($table[4][$i] >> 8) ^ $table[0][$table[4][$i] & 0xFF];
            $table[6][$i] = ($table[5][$i] >> 8) ^ $table[0][$table[5][$i] & 0xFF];
            $table[7][$i] = ($table[6][$i] >> 8) ^ $table[0][$table[6][$i] & 0xFF];
            */
        }
        return $table;
    }
}

/**
 * PHP implementation of the CRC32 algorithm.
 *
 * Uses a simple lookup table to improve the performances.
 */
final class CRC32_PHP implements CRCInterface
{
    public static function supports($algo) : bool
    {
        return true;
    }

    private $table = array();

    /**
     * Creates a new instance for the particular polynomial.
     *
     * @param      integer  $polynomial  The polynomial
     */
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

    public function update(string $data)
    {
        $crc = $this->crc;
        $table = $this->table;
        $len = strlen($data);
        for ($i = 0; $i < $len; ++$i) {
            $crc = (($crc >> 8) & 0xffffff) ^ $table[($crc ^ ord($data[$i])) & 0xff];
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

/**
 * PHP implementation of the CRC32 sliced-by-4 algorithm.
 *
 * This is typically faster, but the PHP implementation seems slower than the
 * simple implementation.
 */
final class CRC32_PHP4 implements CRCInterface
{
    public static function supports($algo) : bool
    {
        return true;
    }

    private $table;

    public function __construct(int $polynomial)
    {
        $this->polynomial = $polynomial;
        $this->table = CRC32Table::create4($polynomial);
        $this->reset();
    }

    public function reset()
    {
        $this->crc = ~0;
    }

    public function update(string $data)
    {
        $crc = $this->crc;
        $table0 = $this->table[0];
        $table1 = $this->table[1];
        $table2 = $this->table[2];
        $table3 = $this->table[3];

        $len = strlen($data);
        $remain = ($len % 4);
        $len1 = $len - $remain;
        for ($i = 0; $i < $len1; $i += 4) {
            $b = (ord($data[$i+3])<<24) |
                 (ord($data[$i+2])<<16) |
                 (ord($data[$i+1])<<8) |
                 (ord($data[$i]));

            $crc = ($crc ^ $b) & 0xffffffff;

            $crc = $table3[ $crc      & 0xff] ^
                   $table2[($crc>>8) & 0xff] ^
                   $table1[($crc>>16) & 0xff] ^
                   $table0[($crc>>24) & 0xff];
        }

        switch ($remain) {
            case 3:
                $crc = (($crc >> 8) & 0xffffff) ^ $table0[($crc ^ ord($data[$i])) & 0xff];
                $crc = (($crc >> 8) & 0xffffff) ^ $table0[($crc ^ ord($data[$i+1])) & 0xff];
                $crc = (($crc >> 8) & 0xffffff) ^ $table0[($crc ^ ord($data[$i+2])) & 0xff];
                break;
            case 2:
                $crc = (($crc >> 8) & 0xffffff) ^ $table0[($crc ^ ord($data[$i])) & 0xff];
                $crc = (($crc >> 8) & 0xffffff) ^ $table0[($crc ^ ord($data[$i+1])) & 0xff];
                break;
            case 1:
                $crc = (($crc >> 8) & 0xffffff) ^ $table0[($crc ^ ord($data[$i])) & 0xff];
                break;
            case 0:
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
