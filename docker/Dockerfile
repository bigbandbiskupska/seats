FROM php:5.6-cli
MAINTAINER Kryštof Tulinger <k.tulinger@gmail.com>

ENV WWW_ROOT /app

RUN apt-get -y update && \
  apt-get -y install git zip nano

RUN apt-get -y clean

RUN /usr/local/bin/docker-php-ext-install pdo
RUN /usr/local/bin/docker-php-ext-install pdo_mysql

WORKDIR /usr/bin

RUN curl -sS https://getcomposer.org/installer | php

WORKDIR $WWW_ROOT

EXPOSE 80

ADD . /docker
RUN chmod -R +x /docker
CMD ["/docker/start.sh"]