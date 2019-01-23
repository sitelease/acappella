FROM php:fpm-alpine

ARG composer_url=http://localhost:8085
ARG composer_dir=/var/www/compolab
ARG gitlab_url
ARG gitlab_token
ARG gitlab_method=url_token

RUN sh docker/install_composer.sh

RUN mkdir /var/www/compolab \
    && composer create-project --no-scripts --no-progress --quiet bricev/compolab /var/www/compolab \
    && touch /var/www/compolab/public/packages.json \
    && touch /var/www/compolab/public/packages.json 

