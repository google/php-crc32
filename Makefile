.PHONY : all clean benchmark test test_all lint ext

COMPOSER ?= composer
PHP_CS_FIXER ?= php-cs-fixer

PHP_BIN ?= $(shell php-config --prefix)/bin
PHP ?= $(PHP_BIN)/php
PHP_CONFIG ?= $(PHP_BIN)/php-config
PHPIZE ?= $(PHP_BIN)/phpize

# This assumes the use of homebrew. TODO make generic.
CRC32C_DIR ?= $$(brew --prefix crc32c)

all: lint test

clean:
	-rm -r .php_cs.cache
	$(MAKE) -C ext clean

benchmark: ext vendor
	$(PHP) -d extension=ext/modules/crc32c.so crc32_benchmark.php

vendor: composer.lock
composer.lock: composer.json
	$(COMPOSER) install
	touch composer.lock

test: ext
	$(PHP) -v
	$(PHP) -d extension=ext/modules/crc32c.so crc32_test.php

test_all:
	for phpize in $$(ls $$(brew --prefix)/Cellar/php*/*/bin/phpize); do \
	  NO_INTERACTION=1 \
	  PHP_BIN=$$(dirname $$phpize) \
	  $(MAKE) clean test; \
	done

lint:
	$(PHP_CS_FIXER) fix crc32.php
	$(PHP_CS_FIXER) fix crc32_benchmark.php
	$(PHP_CS_FIXER) fix crc32_test.php

ext: ext/modules/crc32c.so

ext/modules/crc32c.so: ext/crc32c.c ext/hash_crc32c.c ext/php_crc32c.h
	cd ext && \
	$(PHPIZE) && \
	./configure \
	  --with-crc32c=$(CRC32C_DIR) \
	  --with-php-config=$(PHP_CONFIG)
	$(MAKE) -C ext test