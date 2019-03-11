/**
 * Copyright 2015 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * crc32c extension for PHP
 */
#ifndef PHP_CRC32C_H
# define PHP_CRC32C_H

extern zend_module_entry crc32c_module_entry;
# define phpext_crc32c_ptr &crc32c_module_entry

# define PHP_CRC32C_VERSION "1.0.0"

# if defined(ZTS) && defined(COMPILE_DL_CRC32C)
ZEND_TSRMLS_CACHE_EXTERN()
# endif

static void int2byte(uint32_t i, unsigned char b[4]) {
	b[0] = (unsigned char) ((i >> 24) & 0xff);
	b[1] = (unsigned char) ((i >> 16) & 0xff);
	b[2] = (unsigned char) ((i >> 8) & 0xff);
	b[3] = (unsigned char) (i & 0xff);
}

static uint32_t byte2int(const unsigned char hash[4]) {
	return (hash[0] << 24) | (hash[1] << 16) | (hash[2] << 8) | hash[3];
}

#endif	/* PHP_CRC32C_H */
