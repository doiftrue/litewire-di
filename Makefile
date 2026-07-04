define php_run
	docker run --rm $(1)  --name UNITEST_WP_COPY__php  --user 1000:1000  -v "$(CURDIR):/app"  -w /app \
		composer sh -c "$2"
endef

php.connect:
	$(call php_run, -it, sh)

composer:
	$(call php_run,, composer  $(filter-out $@,$(MAKECMDGOALS)))
composer.install:
	$(call php_run,, composer install  $(filter-out $@,$(MAKECMDGOALS)))
composer.update:
	$(call php_run,, composer update  $(filter-out $@,$(MAKECMDGOALS)))

phpunit:
	$(call php_run, -e WP_LINE="$(WP_LINE)", composer run phpunit -- --colors=always)

# make php.run code='echo "Hello World!\n";'
php.run:
	@if [ -z "$(strip $(value code))" ]; then \
		echo 'Use: make php.run code='\''echo "Hello World!"'\'''; \
		exit 1; \
	fi
	$(file >tmp/.phprun.php,<?php)
	$(file >>tmp/.phprun.php,$(value code))
	@status=0; \
	$(call php_run, , php tmp/.phprun.php) || status=$$?; \
	rm -f tmp/.phprun.php; \
	exit $$status
