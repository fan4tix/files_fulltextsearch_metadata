lint:
	find . -name '*.php' -print0 | xargs -0 -n1 php -l

test:
	php tests/run.php
