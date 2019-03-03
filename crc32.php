<?php
declare(strict_types=1);

class CRC32
{
    // IEEE is used by ethernet (IEEE 802.3), v.42, fddi, gzip, zip, png, ...
    // PHP calls it "crc32b".
    const IEEE = 0xedb88320;

    // Castagnoli's polynomial, used in iSCSI, SCTP, Google Cloud Storage,
    // Apache Kafka, and has hardware-accelerated in modern CPUs.
    // https://dx.doi.org/10.1109/26.231911
    const Castagnoli = 0x82f63b78;

    // Koopman's polynomial.
    // https://dx.doi.org/10.1109/DSN.2002.1028931
    const Koopman = 0xeb31d82e;

    // The size of the checksum in bytes.
    const Size = 4;

    private $table;

    public function __construct(int $polynomial)
    {
        $this->table = self::createTable($polynomial);
        $this->crc = ~0;
    }

    private static function createTable(int $polynomial) : array
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

    private static function int2hex($i) : string
    {
        return str_pad(dechex($i), 8, '0', STR_PAD_LEFT);
    }

    public function hash(bool $raw_output = false) : string
    {
        $crc = ~$this->crc & 0xffffffff;
        if ($raw_output) {
            return pack('L', $crc); // TODO Test this works
        }
        return self::int2hex($crc);
    }
}
