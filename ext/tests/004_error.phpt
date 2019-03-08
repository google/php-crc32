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
--EXPECT--
Warning: crc32c(): Supplied crc must be exactly 4 bytes in /Users/bramp/vendor/php-src/ext/crc32c/tests/004_error.php on line 3
bool(false)

Warning: crc32c(): Supplied crc must be exactly 4 bytes in /Users/bramp/vendor/php-src/ext/crc32c/tests/004_error.php on line 4
bool(false)

Warning: crc32c(): Supplied crc must be exactly 4 bytes in /Users/bramp/vendor/php-src/ext/crc32c/tests/004_error.php on line 5
bool(false)

Warning: crc32c(): Supplied crc must be exactly 4 bytes in /Users/bramp/vendor/php-src/ext/crc32c/tests/004_error.php on line 6
bool(false)