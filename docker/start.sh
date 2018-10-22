#!/bin/bash

cd /app && \
#php /usr/bin/composer.phar --no-interaction update && \
echo "Serving on 0.0.0.0:80" && \
php -S 0.0.0.0:80 -t www
