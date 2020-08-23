# Linux - Pool Control Daemon (poold)

Written by: *Jörg Wendel (linux at jwendel dot de)*
Homepage: https://github.com/horchi/linux-poold

## License
This code is distributed under the terms and conditions of the GNU GENERAL PUBLIC LICENSE. See the file LICENSE for details.

## Disclaimer
USE AT YOUR OWN RISK. No warranty.
The software was created for personal use, it's published here for free under the GPLv2.
The construction of the required hardware, especially in the handling of mains voltage (230V, 110V, ...), is carried out on one's own responsibility, local guidelines and regulations must also be observed especially in connection with water and pool! 
All explanations about the hardware are only indications of how it is technically feasible, security and legal provisions are not discussed here!

## Donation
If you like the project and want to support it
[![paypal](https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KUF9ZAQ5UTHUN)

## Prerequisites:
The described installation method is tested with Raspbian Buster, the poold should work also with other Linux distributions and versions but the installation process should adapted to them, for example they use other init processes or use different tools for the package management, other package names, ...
Language package 'de_DE.UTF-8' is required as language package (`dpkg-reconfigure locales`)

# Hardware

- Rapberry Pi >= 3.x
- Relay Board
- At least 3 One Wire sensors  
- ...
- to be completed


# Installation by package (only Raspbian Buster)

**The install package is not available yet! Please use the ""custom build" section below!  **

### install

```wget  www.jwendel.de/poold/install-deb.sh -O /tmp/install-deb.sh
sudo bash /tmp/install-deb.sh

```
### uninstall

```dpkg --remove poold
```


### uninstall (with remove of all configurations, data and the poold database)

```dpkg --purge poold`
```

# Building and installing by source (working for most Linux platforms)

## Preliminary
Update your package data:
`sudo apt update`
and, if you like, update your installation:
`sudo apt dist-upgrade`

Perform all the following steps as root user! Either by getting root or by prefix each command with sodo.

## Installation of the MySQL Database
It's not required to host the database local at the Raspberry. A remote database is supported as well!

```apt install mariadb-server
```

Set database password for root user during installation!

### Local database setup:
If the database server is located locally (on same host as the poold):

```
> mysql -u root -Dmysql -p
  CREATE DATABASE pool charset utf8;
  CREATE USER 'pool'@'localhost' IDENTIFIED BY 'pool';
  GRANT ALL PRIVILEGES ON pool.* TO 'pool'@'localhost' IDENTIFIED BY 'pool';
  flush privileges;
```
  
### Remote database setup:
if the database is running remote, or you like to have remote access to the database:
```
> mysql -u root -Dmysql -p
 CREATE DATABASE pool charset utf8;
 CREATE USER 'pool'@'%' IDENTIFIED BY 'pool';
 GRANT ALL PRIVILEGES ON pool.* TO 'pool'@'%' IDENTIFIED BY 'pool';
 flush privileges;
```

### install the build dependencies

```apt install build-essential libssl-dev libxml2-dev libcurl4-openssl-dev libssl-dev libmariadbclient-dev libmariadb-dev-compat wiringpi
```
 
### get the poold and build it

```
cd /usr/src/
git clone https://github.com/horchi/linux-poold/
cd linux-poold
make clean all
make install
```

Now the pool daemon is installed in folder `/usr/local/bin`
Check `/etc/poold/poold.conf` file for setting of your database login


# One Wire Sensors:

### Enable One Wire Sensors at your Raspberry PI
To make the sensors available to the Raspberry PI you have to load the `w1-gpio` module by registering it in `/etc/modules`
(here in detail : http://www.netzmafia.de/skripten/hardware/RasPi/Projekt-Onewire/index.html)

```
sudo -i
echo "w1-gpio" >> /etc/modules
echo "w1_therm" >> /etc/modules
echo "dtoverlay=w1-gpio,gpioin=4,pullup=on" >> /boot/config.txt
```

The poold checks automatically if there are 'One Wire Sensors' connected, each detected sensor will be
configurable via the web interface.

# Time to first start of poold

```systemctl start poold  
   systemctl enable poold
```


### to check it's current state call

```systemctl status poold
```

it should now 'enabled' and in state 'running'!

### also check the syslog about errors of the poold, this will show all its current log messages

```grep "poold:" /var/log/syslog
```


## The WEB interface:

```
The default username and password for the login is
User: pool
Pass: pool
```

### Fist steps to enable data logging:

1. Log in to the web interface
2. Go to Setup -> IO Setup
3. Select the values you like to display and record and store your settings

### Points to check
- reboot the device to check if poold is starting automatically during startup


# Backup
Backup the data of the poold database including all recorded values:

```
poold-backup
```

This will create the following files:

```
config-dump.sql.gz
errors-dump.sql.gz
jobs-dump.sql.gz
menu-dump.sql.gz
samples-dump.sql.gz
schemaconf-dump.sql.gz
sensoralert-dump.sql.gz
smartconfig-dump.sql.gz
valuefacts-dump.sql.gz
timeranges-dump.sql.gz
hmsysvars-dump.sql.gz
scripts-dump.sql.gz
```

To import the backup:
```
gunzip NAME-dump.sql.gz
mysql -u pool -ppool -Dpool <  NAME-dump.sql
```
replace NAME with the name of the dump

# Additional information

## MySQL HINTS
If you cannot figure out why you get Access denied, remove all entries from the user table that have Host values with wildcards contained (entries that match `%` or `_` characters). A very common error is to insert a new entry with `Host='%'` and `User='some_user'`, thinking that this allows you to specify localhost to connect from the same machine. The reason that this does not work is that the default privileges include an entry with `Host='localhost'` and `User=''`. Because that entry has a Host value `localhost` that is more specific than `%`, it is used in preference to the new entry when connecting from localhost! The correct procedure is to insert a second entry with `Host='localhost'` and `User='some_user'`, or to delete the entry with `Host='localhost'` and `User=''`. After deleting the entry, remember to issue a `FLUSH PRIVILEGES` statement to reload the grant tables.

To analyze this you can show all users:
```
use mysql
SELECT host, user FROM user;
```

## Raspberry PI's GPIO information's
https://www.raspberrypi.org/documentation/usage/gpio/
https://projects.drogon.net/raspberry-pi/wiringpi/pins/
http://wiringpi.com/reference/setup/

Overview of IO pins
```
apt install python3-gpiozero
pinout
```
