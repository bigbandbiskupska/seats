FROM php:5.6-cli
MAINTAINER Kryštof Tulinger <k.tulinger@gmail.com>

ENV WWW_ROOT /app

RUN sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/archive.debian.org/g' /etc/apt/sources.list && \
    sed -i '/stretch-updates/d' /etc/apt/sources.list

RUN apt-get -y -o Acquire::Check-Valid-Until=false --allow-unauthenticated update && \
  apt-get -y --allow-unauthenticated install git zip nano zlib1g-dev

RUN apt-get -y clean

RUN /usr/local/bin/docker-php-ext-install pdo
RUN /usr/local/bin/docker-php-ext-install pdo_mysql
RUN /usr/local/bin/docker-php-ext-install zip

WORKDIR /usr/bin

RUN curl -sS https://getcomposer.org/installer | php

WORKDIR $WWW_ROOT

EXPOSE 80

ADD . /docker
RUN chmod -R +x /docker
CMD ["/docker/start.sh"]
