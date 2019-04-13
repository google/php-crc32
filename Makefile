##
# Copyright 2019 Google Inc. All Rights Reserved.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#      http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
##

.PHONY : all clean benchmark test test_all lint ext ext_test

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

vendor: composer.lock
composer.lock: composer.json
	$(COMPOSER) install
	touch composer.lock

fix: vendor
	$(PHP_CS_FIXER) fix crc32.php
	$(PHP_CS_FIXER) fix crc32_benchmark.php
	$(PHP_CS_FIXER) fix crc32_test.php
	$(PHP_CS_FIXER) fix ext/tests

lint: vendor
	$(PHP_CS_FIXER) fix --dry-run --diff crc32.php
	$(PHP_CS_FIXER) fix --dry-run --diff crc32_benchmark.php
	$(PHP_CS_FIXER) fix --dry-run --diff crc32_test.php
	$(PHP_CS_FIXER) fix --dry-run --diff ext/tests

benchmark: ext vendor
	$(PHP) -d extension=ext/modules/crc32c.so crc32_benchmark.php

test: ext vendor
	$(PHP) -v
	$(PHP) -d extension=ext/modules/crc32c.so crc32_test.php

# Test all the local versions of PHP
test_all:
	for phpize in $$(ls $$(brew --prefix)/Cellar/php*/*/bin/phpize); do \
	  NO_INTERACTION=1 \
	  PHP_BIN=$$(dirname $$phpize) \
	  $(MAKE) clean test; \
	done

ext: ext/modules/crc32c.so

ext_test: ext
	NO_INTERACTION=1 $(MAKE) -C ext test

ext/modules/crc32c.so: ext/crc32c.c ext/hash_crc32c.c ext/php_crc32c.h
	cd ext && \
	$(PHPIZE) && \
	./configure \
	  --with-crc32c=$(CRC32C_DIR) \
	  --with-php-config=$(PHP_CONFIG) && \
	$(MAKE) clean && $(MAKE)
