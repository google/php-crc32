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

# Related

* https://bugs.php.net/bug.php?id=71890

# Licence (Apache 2)

* This is not an official Google product (experimental or otherwise), it is just code that happens to be owned by Google. *

```
Copyright 2019 Google Inc. All Rights Reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```