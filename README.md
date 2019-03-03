# php-crc32

by [Andrew Brampton](https://bramp.net)

Simple pure-php CRC implementation. This works in a similar way to PHP's
[hash functions](https://secure.php.net/manual/en/ref.hash.php) but supports
all crc32 polynomials, including the useful Castagnoli (`crc32c`).

# Usage

```php
require('crc32.php');

$crc = new CRC32(CRC32::Castagnoli);
$crc->update("hello");
echo $crc->hash();
```

# Benchmark

For the `crc32b` polynomials, we can compare the native PHP implementation to this one.

```shell
$ php crc32_benchmark.php 

native: 12.54 MB/s
purephp: 6.09 MB/s
```

Thus this implementation is about twice as slow, however, it lets you use any polynomial.
