define php_run
	@mkdir -p "$(CURDIR)/tmp"
	@printf '%s\n' 'opcache.enable=1' 'opcache.enable_cli=1' > "$(CURDIR)/tmp/opcache.ini"
	@status=0; \
	docker run --rm $(1) --name LITEWIRE_DI__php -w /app \
		--user "$$(id -u):$$(id -g)" \
		-v "$(CURDIR):/app" \
		-v "$(CURDIR)/tmp/opcache.ini:/usr/local/etc/php/conf.d/opcache.ini:ro" \
		chialab/php-pcov:8.5 sh -c "$2" || status=$$?; \
	exit $$status
endef

define node_run
	@docker run --rm --init $(1) --name LITEWIRE_DI__node \
		--user node \
		-v "$(CURDIR):/usr/src/app" \
		-w /usr/src/app \
		node:24-alpine sh -c "$(2)"
endef

php.connect:
	$(call php_run, -it, sh)

composer:
	$(call php_run,, composer  $(filter-out $@,$(MAKECMDGOALS)))
composer.install:
	$(call php_run,, composer install)
composer.update:
	$(call php_run,, composer update)

phpunit:
	$(call php_run, -e WP_LINE="$(WP_LINE)", composer run phpunit -- --colors=always)

coverage:
	$(call php_run,, composer run coverage -- --colors=always --coverage-html tmp/litewire-coverage)

phpstan:
	$(call php_run,, composer run phpstan -- --memory-limit=1G)

benchmark:
	$(call php_run,, composer install --working-dir=benchmarks --no-interaction --prefer-dist --no-progress && composer run --working-dir=benchmarks benchmark)

docs.install:
	$(call node_run,, npm --prefix docs ci)

docs.build:
	$(call node_run,, npm --prefix docs run build)

docs.dev:
	$(call node_run,-p 127.0.0.1:5173:5173, npm --prefix docs run dev -- --host 0.0.0.0)

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
