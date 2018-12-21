#!/usr/bin/env bash

if [ ! -d "vendor/" ]
then
    if hash composer 2>/dev/null
    then
        composer install
    else
        echo "Please run the 'composer install' command manually"
    fi
fi

if [ ! -f "public/packages.json" ]
then
    touch public/packages.json
    echo '{"packages":{}}' > public/packages.json
fi

if [ ! -f "config/settings.yml" ]
then
    cp config/settings.yml.example config/settings.yml.tmp

    echo "Enter the composer server URL (eg. https://composer.my-website.com):"
    read composerUrl
    sed "s/https:\/\/composer.my-website.com   # The composer repository base URL (with http\/https protocol)/$composerUrl/g" config/settings.yml.tmp > config/settings.yml.tmp

    composerDir=$(pwd)
    sed "s/\/path\/to\/CompoLab\/public          # Path to \/public dir/$composerDir\/public/g" config/settings.yml.tmp > config/settings.yml.tmp

    echo "Enter your Gitlab server URL (eg. https://gitlab.my-website.com):"
    read gitlabUrl
    sed "s/https:\/\/gitlab.my-website.com     # Gitlab base URL (with http\/https protocol)/$gitlabUrl/g" config/settings.yml.tmp > config/settings.yml.tmp

    echo "Enter a valid Gitlab authentication token (url_token method):"
    read gitlabToken
    sed "s/********************              # Gitlab token/$gitlabToken/g" config/settings.yml.tmp > config/settings.yml.tmp

    echo "Enter the composer server URL (eg. https://composer.my-website.com):"
    read gitlabMethod
    sed "s/url_token                         # Gitlab authentication method/url_token/g" config/settings.yml.tmp > config/settings.yml.tmp

    mv config/settings.yml.tmp config/settings.yml
fi

echo "CompoLab has been successfully installed"
