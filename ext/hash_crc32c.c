/* crc32c hash extension for PHP
   This file contains the crc32c hash function for
   http://php.net/manual/en/function.hash.php
 */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "php_crc32c.h"
#include "ext/standard/info.h"
#include "ext/hash/php_hash.h"
#include "ext/hash/php_hash_crc32.h"

#include "crc32c/crc32c.h"

PHP_HASH_API void CRC32CInit(PHP_CRC32_CTX *context)
{
	context->state = 0;
}

PHP_HASH_API void CRC32CUpdate(PHP_CRC32_CTX *context, const unsigned char *input, size_t len)
{
	context->state = crc32c_extend(context->state, input, len);
}

PHP_HASH_API void CRC32CFinal(unsigned char crc[4], PHP_CRC32_CTX *context)
{
	int2byte(context->state, crc);
	context->state = 0;
}

PHP_HASH_API int CRC32CCopy(const php_hash_ops *ops, PHP_CRC32_CTX *orig_context, PHP_CRC32_CTX *copy_context)
{
	copy_context->state = orig_context->state;
	return SUCCESS;
}

const php_hash_ops crc32_ops = {
	(php_hash_init_func_t) CRC32CInit,
	(php_hash_update_func_t) CRC32CUpdate,
	(php_hash_final_func_t) CRC32CFinal,
	(php_hash_copy_func_t) CRC32CCopy,
	4, /* what to say here? */
	4,
	sizeof(PHP_CRC32_CTX),
#if PHP_API_VERSION >= 20170718
	0
#endif
};