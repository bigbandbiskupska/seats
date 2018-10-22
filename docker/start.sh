#!/bin/bash

cd /app && \
git config --global url."https://github.com/".insteadOf "git@github.com:" && \
php /usr/bin/composer.phar --no-interaction update && \
git config --global url."git@github.com".insteadOf "https://github.com/" && \
echo "Serving on 0.0.0.0:80" && \
php -S 0.0.0.0:80 -t www
