# Experimentum

## Server Setup

### Mac OS X


## LAMP Installation

Install Homebrew

```
xcode-select --install
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew tap homebrew/homebrew-core
```

Unload old PHP/Apache and install via homebrew
```
sudo apachectl stop
sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist 2>/dev/null
brew install httpd24
brew services start httpd
brew install php72
```

Set date.timezone in `php.ini`

```
nano /usr/local/etc/php/7.2/php.ini
```
Set `date.timezone = Europe/London`

Edit httpd and hosts files

```
nano /etc/hosts
nano /usr/local/etc/httpd/httpd.conf
nano /usr/local/etc/httpd/extra/httpd-vhosts.conf 
```

## Databases

Install MariaDB

```
brew install mariadb
mysqladmin -u root password 'MYPASSWORD'
```

Create databases:

```
mysql -u root -pMYPASSWORD
CREATE DATABASE exp;
```


