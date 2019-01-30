CompoLab
========

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bricev/CompoLab/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bricev/CompoLab/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/bricev/CompoLab/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bricev/CompoLab/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/bricev/CompoLab/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bricev/CompoLab/build-status/master)

CompoLab is a PHP package repository server that makes all your GitLab compliant repositories available as Composer 
dependencies.

In order to be registered by CompoLab, your GitLab repository must contain a valid `composer.json` file in the root 
directory.

### Security disclaimer

By default, CompoLab is not secured and will let anyone access your packages. 
In order to secure access to your packages, you must configure your web server any way you want. 
Eg. you may filter with an IP whitelist, or require the use of your own self-signed SSL certificate.

### Requirements

A (preferably unix) server configured with: 
- PHP 7.1+
- Git / Composer
- A web server (Nginx or Apache)
- A working instance of GitLab (with an admin user account)

### Installation 

1. From your GitLab instance, edit your own profile, then go to `Access Tokens` and create a token with the name of your 
choice (eg. CompoLab), no expiration date, and check only `api` and `sudo` scopes. If you are not an admin with a `sudo` 
token, you may be restricted in term of the groups and projects you'll be able to cache in your CompoLab repository.

2. Run the following composer command on the server you want to use as a Composer repository:
`composer create-project bricev/compolab --no-dev --keep-vcs /var/www/compolab` (where the last command argument is the 
path where you want to install CompoLab).

3. Execute the installation script by running the command: `php bin/install` (from CompoLab directory). 
Get ready to register your GitLab URL and token during this step.
Settings will be persisted in the `config/settings.yml` file (which is not versioned). 
You may use the `config/settings.yml.example` template to create this manually if you prefer.

4. The web server must be properly configured to receive GitLab webhooks and receive `GET /packages.json` requests. 
The main concern is to make `public/packages.json` and `public/archives` accessible from the root of your domain (eg. 
https://composer.my-website.com/packages.json). All other queries must be forwarded to `public/index.php`.
A documented Nginx configuration example can be found here: `config/templates/nginx.conf`.

5. From your GitLab instance, go to the `Admin Area` > `System hooks` and configure a system hook with URL 
`https://composer.my-website.com/gitlab` (where `composer.my-website.com` is the domain or IP you want to use with your 
CompoLab instance), no secret token as you are advised to insure security at the web server level, and check only 
`Push events`, `Tag push events` and `Enable SSL verification` (if your web server is properly configured to accept SSL 
requests).

### Usage

In order to let your local Composer installation know where to find your CompoLab repository, you need to add some 
configuration.

##### Local setting
You may execute the following command on your local computer/server to let Composer knows about the existance of 
CompoLab:
```
composer config -g repositories.compolab composer https://composer.my-website.com
```

This command should add a `~/.composer/config.json` (on Unix systems) file containing the following lines:
```json
{
    "repositories": {
        "compolab": {
            "type": "composer",
            "url": "https://composer.my-website.com"
        }
    }
}
```

##### Package setting

OR you may set the repository address directly in your package's composer.json file:
```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://composer.my-website.com"
        }
    ]
}
```


