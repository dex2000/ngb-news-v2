<?php

//define some constants ##############################################

//thats where the crawler writes the articles it reads from
//other news pages. the display script (index.php) reads
//articles from those datafiles.
//www-data needs write access to this dir.
$DATAFILE_DIR = getcwd() . "/data";
$DATAFILE_ARTICLES = "articles.db";
$DATAFILE_COMMENTS = "comments.db";


//number of articles to scrape from EACH news page
$MAX_ARTICLES_TO_LOAD = 20;


//this is used later down below, to compare the age of articles
//articles older than this amount of days are not displayed
//as NEWS but only as COMMENTS (Neueste Kommentare)
$MAX_NEWS_AGE = 20; //days


//this number of articles is displayed by default, if the user
//does not specify url param num_articles when calling index.php
$ARTICLES_PER_PAGE = 50;
$ARTICLES_START_AT = 0;
$COMMENTS_PER_PAGE = 100;

?>