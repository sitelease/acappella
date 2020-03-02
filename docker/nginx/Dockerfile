FROM php:fpm-alpine

ARG composer_url=http://localhost:8085
ARG composer_dir=/var/www/acappella
ARG gitea_url
ARG gitea_token
ARG gitea_pushEventSecret
ARG gitea_method=url_token

RUN sh docker/install_composer.sh

RUN mkdir /var/www/acappella \
    && composer create-project --no-scripts --no-progress --quiet sitelease/acappella /var/www/acappella \
    && touch /var/www/acappella/public/packages.json \
    && touch /var/www/acappella/public/packages.json

