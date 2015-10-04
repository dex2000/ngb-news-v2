#############################################################

# ngb:news v2

Sept. 2015

Authors:

thom53281, https://ngb.to/members/45-thom53281
electric.larry, https://ngb.to/members/4421-electric-larry


#############################################################


Brief

The DOM crawler ngbnewscrawler.php reads articles from the
ngb:news Forum at https://ngb.to/forums/61-ngb-news and saves
them to the data directory defined in settings.php. By
default the data directory is located at /data. News articles
are saved to articles.db, comments are stored in comments.db;

The crawler is frequently executed by a cron job to update the
datafiles. The output script index.php reads from the datafiles
and allows filtering by heading/category.

The output script (index.php) comes with two themes, epicorp
and darkfish. If the user specified a theme within the ngb forum,
the output script trys to read the selection from the theme's
cookie.

The theme specified within the ngb forums can be overwritten
with the url parameter 'theme'. In order to force darkfish
theme for example, users would have to point their browsers to
./index.php?theme=darkfish. In order to switch back to epicorp
they could browse to ./index.php?theme=epicorp.

The ngb:news script uses the same theme cookie which is also
used by the vBulletin board software. Passing the theme
parameter to the news script hence overwrites previous settings
from within the ngb forum.

If no theme cookie can't be found, the script defaults to
epicorp.


#############################################################


Installation


Extract all files to a directory on the webserver.

Make sure the output directory (./data), which can be defined in
settings.php, exists and is writable by the user that executes
the crawler ngbnewscrawler.php.

Create a cronjob that executes the crawler in a reasonable
frequency in order to update the datafiles used to display the
latest news.

If you wanted to update the datafiles every 15 minutes, you would
create a cron job equal to this:

0,15,30,45 * * * * cd /absolute/path/to/crawler; php ngbnewscrawler.php

It is important to first cd to the directory that holds the scripts.
Otherwise the include_once(..) calls within the PHP Scripts will fail
to load the dependent include files.

Make sure the user who created/runs the cronjob is granted write-access
to the datafiles directory (./data) as well as to the two datafiles
articles.db and comments.db themselves.

Also grant read access to all php files for the user that executes the
cron job.


Larry luvs you!11
