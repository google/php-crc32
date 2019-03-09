.PHONY : all benchmark test lint

all: lint test

benchmark:
	php -d extension=ext/modules/crc32c.so crc32_benchmark.php

test:
	php -d extension=ext/modules/crc32c.so crc32_test.php

lint:
	php-cs-fixer fix crc32.php
	php-cs-fixer fix crc32_benchmark.php
	php-cs-fixer fix crc32_test.php