.PHONY : all clean benchmark test lint ext

all: lint test

clean:
	$(MAKE) -C ext clean

benchmark: ext
	php -d extension=ext/modules/crc32c.so crc32_benchmark.php

test: ext
	php -d extension=ext/modules/crc32c.so crc32_test.php

lint:
	php-cs-fixer fix crc32.php
	php-cs-fixer fix crc32_benchmark.php
	php-cs-fixer fix crc32_test.php

ext: ext/modules/crc32c.so

ext/modules/crc32c.so: ext/crc32c.c ext/hash_crc32c.c ext/php_crc32c.h
	cd ext && \
	phpize && \
	./configure --with-crc32c=$$(brew --prefix crc32c)
	$(MAKE) -C ext test