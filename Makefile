COMPOSER_BIN := composer
PHPUNIT_BIN := ./vendor/bin/phpunit
PHPCS_BIN := ./vendor/bin/phpcs
BUGFREE_BIN := ./vendor/bin/bugfree

all: lint style test

depends: vendor

cleandepends: cleanvendor vendor

vendor: composer.json
	$(COMPOSER_BIN) --dev update
	touch vendor

cleanvendor:
	rm -rf composer.lock
	rm -rf vendor

lint: depends
	echo " --- Lint ---"
	$(BUGFREE_BIN) lint src
	echo

style:
	echo " --- Style Checks ---"
	# Currently only checking src/test as src/main still needs a bit of work
	phpcs --standard=PSR2 --warning-severity=6 src/test

test: depends
	echo " --- Unit tests ---"
	$(PHPUNIT_BIN)
	echo

.SILENT:
