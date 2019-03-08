--TEST--
crc32c() errors test
--SKIPIF--
<?php
if (!extension_loaded('crc32c')) {
	echo 'skip';
}
?>
--FILE--
<?php

var_dump(crc32c('ABCDEFG', ''));
var_dump(crc32c('ABCDEFG', 0));
var_dump(crc32c('ABCDEFG', 0x12345678));
var_dump(crc32c('ABCDEFG', '12345678'));

?>
--EXPECTF--
Warning: crc32c(): Supplied crc must be exactly 4 bytes in %s on line %d
bool(false)

Warning: crc32c(): Supplied crc must be exactly 4 bytes in %s on line %d
bool(false)

Warning: crc32c(): Supplied crc must be exactly 4 bytes in %s on line %d
bool(false)

Warning: crc32c(): Supplied crc must be exactly 4 bytes in %s on line %d
bool(false)