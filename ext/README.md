```
brew install crc32c

./buildconf && \
./configure --with-crc32c=/Users/bramp/homebrew/Cellar/crc32c/1.0.7/ && \
make test TESTS=ext/crc32c/tests
```