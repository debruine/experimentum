# Experimentum

## Install Experimentum

From the terminal, go to the directory where you want to keep the experimentum files. For example, if you want to keep the files in a directory called `html` in your home folder, type:

```
cd ~/html/
```

Clone Experimetum

```
git clone https://github.com/debruine/experimentum.git
```

You can rename the directory from `experimentum` to the name of your website.

## Server Setup (Mac OS X)

Install XCode

```
xcode-select --install
```

Install Homebrew

You may be asked to change permissions on `/usr/local/Cellar`. Do this and re-run the command below.

```
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
```

Install Homebrew core

```
brew tap homebrew/homebrew-core
```

Unload old PHP/Apache and install via homebrew

There will be a few minutes of installation and output while dependencies are installed.

```
sudo apachectl stop
sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null
brew install httpd24
brew services start httpd
brew install php72
```

At the end of installation, you will get some output that starts with:

```
==> php@7.2
To enable PHP in Apache add the following to httpd.conf and restart Apache:
```

Copy this text and edit httpd.conf file. We'll edit this with `nano`; if you have a preferred editor, use that instead. 

```
nano /usr/local/etc/httpd/httpd.conf
```

Search for `DocumentRoot` using control-W. Change it to the directory you put the experimentum directory inside. Also add MultiViews to the <Directory> as below.

```
DocumentRoot "/Users/lisad/html"

<Directory "/Users/lisad/html">
    #
    # Possible values for the Options directive are "None", "All",
    # or any combination of:
    #   Indexes Includes FollowSymLinks SymLinksifOwnerMatch ExecCGI MultiViews
    #
    # Note that "MultiViews" must be named *explicitly* --- "Options All"
    # doesn't give it to you.
    #
    # The Options directive is both complicated and important.  Please see
    # http://httpd.apache.org/docs/2.4/mod/core.html#options
    # for more information.
    #
    Options Indexes FollowSymLinks MultiViews
    MultiviewsMatch Any

    #
    # AllowOverride controls what directives may be placed in .htaccess files.
    # It can be "All", "None", or any combination of the keywords:
    #   AllowOverride FileInfo AuthConfig Limit
    #
    AllowOverride None

    #
    # Controls who can get stuff from this server.
    #
    Require all granted
    Allow from 127.0.0.1
</Directory>
```

Search for `php7_module` using control-W. Replace it with the text you copied. If you don't find that, go to the end of the file (control-V until you get there) and add the text you copied (which will look something like this):

```
LoadModule php7_module /usr/local/opt/php@7.2/lib/httpd/modules/libphp7.so

<FilesMatch \.php$>
    SetHandler application/x-httpd-php
</FilesMatch>
```

Search for `DirectoryIndex` (ctrl-W) and edit it to look like:

```
<IfModule dir_module>
    DirectoryIndex index.php index.html
</IfModule>
```

Search for `mod_negotiation` and make sure this line is not commented (remove the #).

```
LoadModule negotiation_module lib/httpd/modules/mod_negotiation.so
```

Save this file and exit (crtl-O, ctrl-X).


Set date.timezone in `php.ini`

```
nano /usr/local/etc/php/7.2/php.ini
```
Search for "date.timezone" using control-W. Look up your timezone at https://www.php.net/manual/en/timezones.php and set `date.timezone` (e.g., `date.timezone = Europe/London`). Save with control-O and exit with control-X.



Edit hosts file

```
sudo nano /etc/hosts
```

Do not delete anything, just add a line with the URL of your new test site, e.g.:

```
127.0.0.1       experimentum.test
```

Save with ctrl-O, ctrl-X


Edit the vhosts file

```
nano /usr/local/etc/httpd/extra/httpd-vhosts.conf 
```

Edit the first example for your test server. The important parts are getting the ServerName the same as in the hosts file and the DocumentRoot the same the directory where you put Experimentum in Step 1 (you may have renamed the directory). It should look something like this:

```
<VirtualHost *:80>
    ServerAdmin experimentum@glasgow.ac.uk
    DocumentRoot "/Users/lisad/html/experimentum"
    ServerName experimentum.test
    ErrorLog "/private/var/log/apache2/experimentum-error_log"
    CustomLog "/private/var/log/apache2/experimentum-access_log" common
</VirtualHost>
```

Restart apache

```
brew services start httpd
```

## Databases

Install MariaDB

```
brew install mariadb
```

Lunch MariaDB

```
brew services start mariadb
```

Set your admin password
```
mysqladmin -u root password 'MYPASSWORD'
```

Create databases:

Go to the directory where you saved experimentum and into the `setup/mysql` directory

```
cd /Users/lisad/experimentum/setup/mysql
```
Create the exp database

```
mysql -u root -pMYPASSWORD
CREATE DATABASE exp;
USE exp;
\. exp_setup.sql
```



Edit config file:

Change `config_edit_this.php` to `config.php` and edit everything with `**EDIT THIS**`

Set the MYSQL_USER to `root` and MYSQL_PSWD` to the admin password you set above for both researchers and users (for your test site only). For your actual site, create MySQL users for researchers and users who have appropriate permissions (*LISA EDIT THIS*).

Look at your website at http://name.test:8080

Sign up for an account, requesting researcher status.

Log into MySQL from the command line:

```
mysql -u root -p
USE exp;
SELECT * from user;
UPDATE user SET status = "admin" WHERE user_id = 1;
```

Log out and log back into your website. It should take you straight to the resaercher section.
