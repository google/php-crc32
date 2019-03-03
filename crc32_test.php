<?php
declare(strict_types=1);

require('crc32.php');

$tests = array(
	CRC32::IEEE => array(
		'' => '00000000',
		'a' => 'e8b7be43',
		'abc' => '352441c2',
		'message digest' => '20159d7f',
		'abcdefghijklmnopqrstuvwxyz' => '4c2750bd',
		'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' => '1fc2e6d2',
		'12345678901234567890123456789012345678901234567890123456789012345678901234567890' => '7ca94a72',
	),
	CRC32::Castagnoli => array(
		'' => '00000000',
		'a' => 'c1d04330',
		'abc' => '364b3fb7',
		'message digest' => '02bd79d0',
		'abcdefghijklmnopqrstuvwxyz' => '9ee6ef25',
		'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' => 'a245d57d',
		'12345678901234567890123456789012345678901234567890123456789012345678901234567890' => '477a6781',
	),
);

foreach ($tests as $poly => $cases) {
	$crc = new CRC32($poly);

	foreach ($cases as $input => $expected) {
		$crc->update($input);
		$actual = $crc->hash();

		if ($actual !== $expected) {
			echo "fail: crc32($poly).hash($input) = $actual wanted $expected\n";
		}

		$crc->reset();
	}
}