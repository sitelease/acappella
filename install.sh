#!/bin/sh

# In case script is run manually,
# check if Composer is installed...
if [ ! -d "vendor/" ]
then
    if hash composer 2>/dev/null
    then
        composer install

    else
        # https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
        EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

        if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
        then
            echo 'ERROR: Invalid installer signature'
            rm composer-setup.php
            exit 1
        fi

        php composer-setup.php --quiet
        rm composer-setup.php
    fi
fi

# Create the `packages.json` as this file
# is excluded from Git (.gitignore)...
if [ ! -f "public/packages.json" ]
then
    touch public/packages.json
    echo '{"packages":{}}' > public/packages.json
fi

# Collect settings and save them locally...
if [ ! -f "config/settings.yml" ]
then
    cp config/settings.yml.example config/settings.yml

    echo "Enter the composer server URL (eg. https://composer.my-website.com):"
    read composerUrl

    echo "Enter your Gitlab server URL (eg. https://gitlab.my-website.com):"
    read gitlabUrl

    echo "Enter a valid Gitlab authentication token (url_token method):"
    read gitlabToken

    compolabDir=$(pwd)

    sed -i '' \
        -e "s|https://composer.my-website.com   # The composer repository base URL (with http/https protocol)|$composerUrl|g" \
        -e "s|/path/to/CompoLab/public          # Path to /public dir|$compolabDir\/public|g" \
        -e "s|https://gitlab.my-website.com     # Gitlab base URL (with http/https protocol)|$gitlabUrl|g" \
        -e "s|XXXXXXXXXXXXXXXXXXXX              # Gitlab token|$gitlabToken|g" \
        -e "s|url_token                         # Gitlab authentication method|url_token|g" \
    "$compolabDir/config/settings.yml"
fi

echo "CompoLab is successfully installed"
