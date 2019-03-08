/* crc32c extension for PHP
   This file sets up the crc32c module, and provide a
   simple php crc32c function.
 */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "php_crc32c.h"
#include "ext/hash/php_hash.h"
#include "ext/standard/info.h"

#include "crc32c/crc32c.h"

extern const php_hash_ops crc32_ops;

/* {{{ int crc32c( string $data [, int $crc ] )
 */
PHP_FUNCTION(crc32c)
{
	char *data_arg = NULL;
	size_t data_len = 0;
	char *crc_arg = NULL;
	size_t crc_len = 0;

	ZEND_PARSE_PARAMETERS_START(1, 2)
		Z_PARAM_STRING(data_arg, data_len)
		Z_PARAM_OPTIONAL
		Z_PARAM_STRING_EX(crc_arg, crc_len, 1, 0)
	ZEND_PARSE_PARAMETERS_END_EX(RETURN_FALSE);

	uint32_t crc = 0;

	if (crc_len == 4) {
		crc = byte2int((unsigned char *)crc_arg);

	} else if (crc_arg != NULL) {
		zend_error(E_WARNING, "crc32c(): Supplied crc must be exactly 4 bytes");
		RETURN_BOOL(false);
	}

	crc = crc32c_extend(crc, (const uint8_t *)data_arg, data_len);

	unsigned char hash[4];
	int2byte(crc, hash);

	RETURN_STRINGL((const char *)hash, sizeof(hash));
}
/* }}}*/


/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(crc32c)
{
#if defined(ZTS) && defined(COMPILE_DL_CRC32C)
	ZEND_TSRMLS_CACHE_UPDATE();
#endif

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(crc32c)
{
	php_hash_register_algo("crc32c", &crc32_ops);
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(crc32c)
{
	// TODO Unregister php_hash_register_algo
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(crc32c)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "Google CRC32C support", "enabled");
	php_info_print_table_end();
}
/* }}} */

/* {{{ arginfo
 */
ZEND_BEGIN_ARG_INFO_EX(arginfo_crc32c, 0, 0, 1)
	ZEND_ARG_INFO(0, str)
	ZEND_ARG_INFO(0, crc)
ZEND_END_ARG_INFO()
/* }}} */

/* {{{ crc32c_functions[]
 */
static const zend_function_entry crc32c_functions[] = {
	PHP_FE(crc32c, arginfo_crc32c)
	PHP_FE_END
};
/* }}} */

/* {{{ crc32c_deps
 */
static const zend_module_dep crc32c_deps[] = {
	ZEND_MOD_REQUIRED("hash")
	ZEND_MOD_END
};
/* }}} */

/* {{{ crc32c_module_entry
 */
zend_module_entry crc32c_module_entry = {
	STANDARD_MODULE_HEADER_EX, NULL,
	crc32c_deps,				/* Module dependencies */
	"crc32c",					/* Extension name */
	crc32c_functions,			/* zend_function_entry */
	PHP_MINIT(crc32c),			/* PHP_MINIT - Module initialization */
	PHP_MSHUTDOWN(crc32c),		/* PHP_MSHUTDOWN - Module shutdown */
	PHP_RINIT(crc32c),			/* PHP_RINIT - Request initialization */
	NULL,						/* PHP_RSHUTDOWN - Request shutdown */
	PHP_MINFO(crc32c),			/* PHP_MINFO - Module info */
	PHP_CRC32C_VERSION,			/* Version */
	STANDARD_MODULE_PROPERTIES
};
/* }}} */


#ifdef COMPILE_DL_CRC32C
# ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
# endif
ZEND_GET_MODULE(crc32c)
#endif
