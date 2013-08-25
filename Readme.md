== Simple Twitter Bot
* Supports Tweet, Reply, Timeline Reply and Time Tweet.
* Supports changing user name, description, bio, and homepage.

== Usage
* Take a look at /core/config.php and put your DB details there!
* Use <code>crontab -e</code> to add <code>* * * * * (path)/tweet.sh >/dev/null 2>&1
* Take a look at /www/src/group.php to change encryption settings.
* Take a look at /www/src/twitter-login.php to change Bot Twitter API Keys!

== Path Description
* /bot/group: Where the group is stored.
* /core: Important files.
* /www: Web interface.
* /www/internal: Multi-processing purposed directory.
* /cron: Files required from cron actions.

== Used Library
* tmhOAuth