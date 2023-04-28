# Acappella <!-- omit in toc -->
Acappella is a private [composer](https://getcomposer.org/) repository server that syncs with [Gitea](https://docs.gitea.io/en-us/). You can think of it as a self hosted, headless, packagist with Gitea super powers.

## Table of Contents <!-- omit in toc -->
- [Why Acappella](#why-acappella)
- [How does it work?](#how-does-it-work)
- [Security disclaimer](#security-disclaimer)
- [Requirements](#requirements)
- [Installation](#installation)
- [CLI Commands](#cli-commands)
- [Connecting to Acappella](#connecting-to-acappella)
    - [Using a global setting](#using-a-global-setting)
    - [Using a package setting](#using-a-package-setting)
- [What's next?](#whats-next)


## Why Acappella
While there are several private composer repository projects already out there ([satis](https://github.com/composer/satis), [CompoLab](https://github.com/bricev/CompoLab), [gitlab-composer](https://github.com/wemakecustom/gitlab-composer)) none of them where exclusively for Gitea. Acappella is.

## How does it work?
When you first setup Acappella it will connect to your Gitea server (using the API) and parse through all the repositories its given access to. It will then register any valid **\*** composer packages it finds and generate out a package.json file for composer to use.

**\*** In order for Acappella to register a Gitea repository as a "valid" composer package it must contain a properly formatted `composer.json` file in it's root directory.

## Security disclaimer

By default, Acappella is not secured and will let anyone who has access to your server, access your packages. In order to secure access to your packages, you will need to setup some form of security layer (using IP whitelisting, SSL certificates, or some other system of your choosing).

## Requirements

A (preferably unix) server configured with:
- PHP 7.2 or above (only tested on PHP 7.4.2)
- Git / Composer
- A web server (Nginx or Apache)
- A working instance of Gitea (preferably with an dedicated account for Acappella)

## Installation

1. From your Gitea instance, go to your account settings, then go to the `Applications` tab and create a token with the name of your choice (eg. Acappella). Once it redirects you, copy the generated token (displayed in the blue alert box).

2. Run the following composer command on the server you want to use as a Composer repository: `composer create-project sitelease/acappella --no-dev --keep-vcs /var/www/acappella` (where the last command argument is the
path where you want to install Acappella).

3. Now travel to the Acappella directory and execute the installation script by running `php bin/install` (from the terminal).

    You will need to enter your Gitea URL and paste in the API token you copied earlier.

    **NOTE:** Settings will be persisted in the `config/settings.yml` file (which is not versioned). You may use the `config/settings.yml.example` template to create this manually if you prefer.

4. Next you will need to ensure that your web server is properly configured to receive Gitea webhooks and `GET /packages.json` requests.

	The main concern is to make `public/packages.json` and `public/archives` accessible from the root of your domain (eg. https://composer.my-website.com/packages.json). **All other queries must be forwarded to `public/index.php`.**

    **Apache:** If your using Apache, this functionality should be working out of the box as we've included some .htacess files (in the root and `public/` folder). Burt to ensure it is make sure you can access the packages.json (via `https://composer.my-website.com/packages.json`) file and the `/gitea` route (via `https://composer.my-website.com/gitea`).

    The first url should display an empty JSON string (or perhaps not empty if you have already synced packages), and the second should give you a warning that says `"FAILED: Not POST - The request was not sent using the POST protocol"`. Which is to be expected (in other words thats a good thing).

    *"No route found" ERROR -* If you go to access the packages.json file and you get a JSON response with the message `"No route found for \"GET /packages.json\""`, you may have an anti-hacking software running that is blocking access to `package.json` and `packages.json` files (we encountered this on one of our server installations). in which case you may need to contact your server admin or support team for assistance.

	**Nginx:** Nginx users can find a configuration file in the `config/templates/` folder, but its untested so I cannot guarantee that it will work (sorry yall I don't use NGINX)

5. With that complete, you can now register your existing composer packages with Acappella using it's `cli` script. Open a terminal on your server (over SSH if need be) and run the following command:

	```
	php bin/cli sync
	```

	This will fully synchronize your Gitea server with your Acappella repository. Once executed, all distribution archives will be stored in the Acappella cache and the `packages.json` index will be up to date.

6. Last but not least, you will need to create a `Push Events` webhook.

	For this you have two options (well, technically three), create a default webhook (admin only), create an organization webhook, or create a Repository webhook (not recommended). Each option has its own pros and cons.

	**Default Webhooks:** Default webhooks will be applied (or copied) to **all future respositories**, and are therefor very useful for ensuring all new repositories have the webhook. Unfortunitly they are not applied to existing repositories and once added **cannot be changed** without updating each repository manually.

    **Organization Webhooks:** Organization webhooks overcome many of the drawbacks of default webhooks. They apply to **all current and future respositories**, and **can be easily changed** after they are created. But they have there own drawback as they are **only applied to repositories that are owned by the organization**.

    **Repository Webhooks:** These webhooks are applied to **individual repositories** and therefor (in the context of Acappella) are **only useful for testing** (e.i. to check if Acappella has been configured correctly).

    **Multiple Webhooks:** If none of the three options works for your particular use case you do have the option of setting up multiple webhooks. Just remember that they may overlap for certain repositories, in which case you will have two webhooks trigger for the same repository. While this will not cause any direct problems for Acappella (that I know of) it may create slow downs if you have a large number of packages.

    In this documentation we will setup a "Organization Webhook". To do so login to your Gitea account, go to the Organization you wish to add the webhook to and click on the small gear beside its name. Then click on `Webhooks` > `Add Webhook` and then `Gitea`. A new screen will appear where you can configure the new webhook.

	Set the Target URL to `https://composer.my-website.com/gitea` (where `composer.my-website.com` is the domain or IP you want to use with your Acappella instance), set HTTP Method to `POST`, POST Content Type to `application/json` and ensure that Trigger On is set to `Push Events`.

    Last but not least, pop open a terminal and generate a 16 character secret key by running:
    ```
    openssl rand -hex 16
    ```
    Paste the generated key into the `Secret` field and save the webhook.

**You're all set up!** Your repository is now complete and any future push/tag made to Gitea will be registred by Acappella.

## CLI Commands

Below is a list of the CLI's commands:

* `php bin/cli sync` - The `sync` command will fully synchronize your Gitea server with Acappella.

    Once executed, all Gitea repositories containing a composer package will be downloaded as archives and stored in Acappella's system. cache and the `packages.json` index will be up to date.
* `php bin/cli update argument` - The `update` command accepts a single argument and will retrieve updates from Gitea for the specified package.

    The argument can either be the name of a composer package (`firesphere/social`), the name of a Gitea repository (`Sitelease/sugar-cube-client`) or the ID of the Gitea repository (`241`)
* `php bin/cli install` - The `install` command is used to configure Acappella for the first time.

## Connecting to Acappella

To allow your local Composer installation to connect to Acappella, you need to create a new `repository` configuration. You can create this setting in your global composer config file or directly in your composer package.

#### Using a global setting
You may execute the following commands on your local computer/server to let Composer know about the existance of Acappella and to disable secure HTTP:
```
composer config -g repositories.acappella composer http://composer.mygit.ca
composer config --global secure-http false
```

This command should add a `~/.composer/config.json` (on Unix systems) file containing the following lines:
```json
{
    "config": {
        "secure-http": false
    },
    "repositories": {
        "acappella": {
            "type": "composer",
            "url": "http://composer.mygit.ca"
        }
    }
}
```

#### Using a package setting

OR you may set the repository address directly in your package's composer.json file:
```json
{
    "config": {
        "secure-http": false
    },
    "repositories": [
        {
            "type": "composer",
            "url": "http://composer.mygit.ca"
        }
    ]
}
```

## What's next?
- [x] Fix the cli "update" command
- [ ] Update PHPUnit tests
- [ ] Update and test the Dockerfile
- [x] Add an example Apache configuration file
- [ ] Create a simple web based interface
