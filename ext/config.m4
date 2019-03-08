dnl config.m4 for extension crc32c

dnl If your extension references something external, use with:

PHP_ARG_WITH(crc32c, for crc32c support,
[  --with-crc32c             Include crc32c support. File is the optional path to google/crc32c])

if test "$PHP_CRC32C" != "no"; then
  dnl Write more examples of tests here...

  # --with-crc32c -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  SEARCH_FOR="include/crc32c/crc32c.h"
  if test -r $PHP_CRC32C/$SEARCH_FOR; then # path given as parameter
    CRC32C_DIR=$PHP_CRC32C
  else # search default path list
    AC_MSG_CHECKING([for crc32c files in default path])
    for i in $SEARCH_PATH ; do
      if test -r $i/$SEARCH_FOR; then
        CRC32C_DIR=$i
        AC_MSG_RESULT(found in $i)
      fi
    done
  fi
  
  if test -z "$CRC32C_DIR"; then
    AC_MSG_RESULT([not found])
    AC_MSG_ERROR([Please reinstall the crc32c distribution])
  fi

  # --with-crc32c -> add include path
  PHP_ADD_INCLUDE($CRC32C_DIR/include)

  # --with-crc32c -> check for lib and symbol presence
  LIBNAME=crc32c          # you may want to change this
  LIBSYMBOL=crc32c_extend # you most likely want to change this

  PHP_CHECK_LIBRARY($LIBNAME, $LIBSYMBOL,
  [
    PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $CRC32C_DIR/$PHP_LIBDIR, CRC32C_SHARED_LIBADD)
    AC_DEFINE(HAVE_CRC32CLIB,1,[ ])
  ],[
    AC_MSG_ERROR([wrong crc32c lib version or lib not found])
  ],[
    -L$CRC32C_DIR/$PHP_LIBDIR -lm
  ])
  
  PHP_SUBST(CRC32C_SHARED_LIBADD)

  # In case of no dependencies
  AC_DEFINE(HAVE_CRC32C, 1, [ Have crc32c support ])

  PHP_NEW_EXTENSION(crc32c, crc32c.c hash_crc32c.c, $ext_shared)
fi
