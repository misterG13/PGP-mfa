# 'GnuPG' a PHP extension, helpful install tips

## Linux Debian Installation:

    * Is very easy because, GnuPG is apart of the
        repositories for both Debian and Ubuntu

    ### Debian 12 (bookworm)

        * Package 'php-gnupg' is the package equivalent to'php8.2-gnupg'

        * You can specify a PHP version of your choice by changing the
            install package to 'php#.#-gnupg'

        * Package 'gnupg2' installs the needed packages to run 'php-gnupg'

        Install via cli:
            $ sudo apt-get update
            $ sudo apt-get install -y php-gnupg gnupg2


    ### Ubuntu 22.04 LTS (jammy)

        * Package 'php-gnupg' is the package equivalent to'php8.1-gnupg'

        * You can specify a PHP version of your choice by changing the
            install package to 'php#.#-gnupg'

        * Package 'gnupg2' installs the needed packages to run 'php-gnupg'

        Install via CLI:
            $ sudo apt-get update
            $ sudo apt-get install -y php-gnupg gnupg2

## Verify GNUPG Extension in PHP:

    - Create a new file in web root directory
        'sudo nano phpinfo.php'

    - Add this line:
        'phpinfo();'

    - Exit & Save

    - Open phpinfo.php in a web browser

    - Search for 'gnupg'

    - If found = successful install

    ** DELETE this file, never have in production or public **
        'sudo rm -rf phpinfo.php'

## Windows Installation

    * Extremely sorry but I do not plan on testing this in a Windows environment,
        so the install support will be limited to none.

    Here are some installation links to provide some assistance:
        - Go to GnuPG's Official website: https://www.gnupg.org/download/index.html

        - Scroll down to "GnuPG BINARY RELEASES" follow the link
            for "Windows Gpg4win": https://gpg4win.org/download.html
