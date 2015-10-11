<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="de">
<?php

	//show error output ###########################################
	//ini_set('display_errors', 1);
	//error_reporting(E_ALL);


	//some includes ##############################################
	//include constants/settings for this script
	include_once("./settings.php");
	//include the worker functions for this script
	include_once("./ngbnews.inc.php");

	//set the default timezone to prevent warnings
	date_default_timezone_set('Europe/Vienna');

	//this flag is set if an error occurs when reading
	//articles from the data files. further down, if set to true
	//an error message is displayed to the users
	$ERROR_LOADING_DATA = FALSE;

	//those litsts will get filled with the data loaded from the input files
	$lstNews = array();
	$lstInteresting = array();

	$numNews = 0;
	$numInteresting = 0;
	
	//get number of articles and comments to display at once from url param or use default setting from settings.php
	//more articles are loaded when user scrolls down or clicks the show more button.
	$numArticlesPerPage = isset($_GET['articles_per_page']) ? (int)$_GET['articles_per_page'] : $ARTICLES_PER_PAGE;
	$idxArticlesStartAt = isset($_GET['articles_start']) ? (int)$_GET['articles_start'] : $ARTICLES_START_AT;
	$numCommentsPerPage = isset($_GET['comments_per_page']) ? (int)$_GET['comments_per_page'] : $COMMENTS_PER_PAGE;	
		
	
	
	//load articles and comments from datafiles
	try
	{
		//filename of articles datafile
		$articlesPath = $DATAFILE_DIR . "/" . $DATAFILE_ARTICLES;
		//filename of comments datafile
		$commentsPath = $DATAFILE_DIR . "/" . $DATAFILE_COMMENTS;

		//try to load articles from datafile
		if(file_exists($articlesPath))
		{
			$objData = file_get_contents($articlesPath);
			$lstNews = unserialize($objData);
			//remember number of loaded articles and comments
			$numNews = sizeof($lstNews);

			//date/time of last update of the datafile
			$dtLastUpdated = filemtime($articlesPath);
		}
		else
		{
			throw new Exception("Couldn't open articles datafile.");
		}

		//try to load latest comments
		if(file_exists($commentsPath))
		{
			$objData = file_get_contents($commentsPath);
			$lstInteresting = unserialize($objData);
			//remember number of loaded articles and comments
			$numInteresting = sizeof($lstInteresting);
		}
		else
		{
			throw new Exception("Couldn't open comments datafile.");
		}
	}
	//something went wrong while trying to load articles or comments
	catch(Exception $exc)
	{
		$ERROR_LOADING_DATA = TRUE;
		//if this error happens, we need to display menu etc. and
		//then die further down below.
	}


	//GET HEADING FILTERS
	//check if user specified a heading / category filter like (netzwelt, p&g, sport, technik, ...)

	$heading = "allenews"; //by default show all categories


	if(isset($_GET['heading']) )
	{
		//get user input from url parameter
		$tmpHeading = (string)$_GET['heading'];
		//our headings only consist of letters and spaces. everything else would be a fraud
		$tmpHeading = preg_replace("/[^A-Za-z0-9 ]/", '', $tmpHeading);
		//remember this beautified heading
		$heading = $tmpHeading;
	} //eof isset




	//GET THE USER'S SELECTED THEME FROM COOKIE OR URL PARAM

	/* bb_userstyleid cookie values:
	 * 1 == default, 2 == default mobile,
	 * 3 == darkfish, 4 == epicorp, 5 == fishmobile
	 */

	 //use epicorp theme as default
	$selectedTheme = "ngbnews.css";

	//use the theme that was defined in the url parameter
	//and update the theme saved in the cookie
	if(isset($_GET['theme']) )
	{
		if( (string)$_GET['theme'] == 'darkfish')
		{
			//use darkfish theme
			$selectedTheme = "ngbnews_darkfish.css";
			setcookie("bb_userstyleid", "3");
		}
		else
		{
			//use default epicorp theme
			$selectedTheme = "ngbnews.css";
			setcookie("bb_userstyleid", "4");
		}
	}
	//if no theme was defined in url parameter, try to read it from cookie value
	else
	{
		if(isset($_COOKIE['bb_userstyleid']))
		{
			$idTheme = (int)$_COOKIE['bb_userstyleid'];
			switch($idTheme)
			{
				//we use epicorp theme
				case 1:
				case 2:
				case 4:
				{
					$selectedTheme = "ngbnews.css";
					break;
				}

				//we use darkfish
				case 3:
				case 5:
				{
					$selectedTheme = "ngbnews_darkfish.css";
					break;
				}
			} //eof switch
		} //eof if isset cookie

	} //eof else



?>
<head>

	<title>ngb:NEWS - Wirklich unabhängige IT- und Tech-News</title>

	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="Shortcut Icon" href="images/favicon.ico" type="image/x-icon" />
	<meta name="keywords" content="ngb:news, ngb news, schlagzeilen, nachrichten,  ngb,g:b,technik,it,computer,security,konsolen,sony,nintendo,microsoft,windows,linux,mac,ios,iphone,apple" />
	<meta name="description" content="NGB:NEWS - Nachrichten von Tech-Heads für Tech-Heads. Im ngb, dem wirklich unabhängigen IT- und Tech-Board, findest du Diskussionen und Antworten auf Fragen zu technischen und netzpolitischen Themen." />
	<meta name="creator" content="Selbsthilfegruppe: Kene Chance für Koks & Nutten" />

	<style>
		/* lets place juuust a few basic styles inline. overwrite in ngbnews.css */
		body
		{
			font-family: Tahoma, Calibri, Verdana, Geneva, sans-serif;
			font-size: 13px;
			font-style: normal;
			font-variant: normal;
			background-color:#F3F2EE;
			color:#742C23;
		}

		a
		{
			color: rgb(116, 44, 35);
			text-decoration:none;
		}
	</style>

	<!-- include the stylesheet selected a little above -->
	<link type="text/css" rel="stylesheet" href="<?php print $selectedTheme; ?>" media="all" />

	<!-- some included logic here -->
	<script type="text/javascript" src="jquery.js"></script>
	<script type="text/javascript" src="ngbnews.js"></script>

</head>


<body>
	<!-- HEADER ######################################################### -->
	<div id="header">
		<img src="images/ngb_news_logo.png" width="382" height="70"/>
	</div>


	<div id="navbar" class="navbar">
		<ul id="navtabs" class="navtabs floatcontainer">
			<li  id="vbtab_forum">
				<a class="navtab" href="https://ngb.to/index.php">Board</a>
			</li>
			<li class="selected" id="tab_mte1_154">
				<a class="navtab" href="https://ngb.to/news">News</a>
			</li>
			<ul class="floatcontainer">
				<li id="link_mte1_547"><a href="https://ngb.to/forums/61-ngb-news">Forum-Ansicht</a></li>
				<li id="link_mte1_246"><a href="https://ngb.to/newthread.php?do=newthread&f=61">News einreichen</a></li>
				<li id="link_mte1_650"><a href="https://ngb.to/external.php?type=RSS2&forumids=61">RSS-Feed</a></li>
				<li id="link_mte1_299"><a target="_blank" href="http://twitter.com/ngb_news">Twitter</a></li>
			</ul>
			<li  id="vbtab_whatsnew">
				<a class="navtab" href="https://ngb.to/activity.php">Was ist neu?</a>
			</li>
			<li  id="tab_ndiz_506">
				<a class="navtab" href="https://ngb.to/faq.php?faq=ngb_community">Über uns</a>
			</li>
		</ul>
	</div> <!-- EOF HEADER -->




	<?php if($ERROR_LOADING_DATA === TRUE) { ?>
		<!-- DISPLAY ERROR MESSAGE TO USER IF ERROR OCCURED WHEN READING DATA FILES  -->

		<div class="message error">
			<p>
				Im Moment können hier keine News angezeigt werden.<br />
				Bitte verwende das <a href="https://ngb.to/forums/61-ngb-news" title="ngb:NEWS">ngb:news Forum</a> bis wir das Problem beheben konnten.
			</p>
		</div>

		<!-- EOF DISPLAY ERROR MESSAGE  -->
	<?php die(); }  ?>



	<div id="spacer"></div>


	<div id="content">

		<label for="dropdownmenu">
			<input type="checkbox" id="dropdownmenu" />
			<span id="top-menu" style="">
				<span class="menu">

					<!-- menu item - all news -->
					<span alt="allenews" class="menu-item allenews <?php print ($heading === 'allenews' ? 'selected' : ''); ?> ">
						<a href='./?heading=allenews'>Alle News</a>
					</span>

					<?php
						//print a menu item for each of the prefixes (technik,
						//netzpolitik, etc.) found.

						//we pass the heading as well
						//so we can highlight the selected heading in the menu
						//even without javascript being enabled
						$lstPrefixes = getArticlePrefixes($lstNews, $heading);
						//and print the menu LIs out.
						foreach($lstPrefixes as $p)
						{
							print "$p\r\n";
						}
					?>

				</span>
			</span> <!-- eof top-menu -->
		</label> <!-- eof label for dropdownmenu -->


		<!-- EOF HEADER PART WITH MENU ETC.  -->





		<!-- der wrapper für die news artikel -->
		<div id="latest-news">

			<?php
						
			//counts how many articles are already printed.  
			$numArticlesPrinted = 0;
			
			//number of articles skipped before output. we need this for paging
			$numArticlesSkipped = 0;
			
			
			//iterate all articles in the list of news objects
			foreach($lstNews as $article)
			{
				//if user supplied a heading, only display matching articles
				if($heading !== "allenews" && $article->prefixClass !== $heading)
				{
					//current article doesn't match, skip it and dont count it for the number visible on page :)
					continue;
				}							
				
				//we skip the number of articles at the beginning, if url parameter defines a starting index
				if($numArticlesSkipped < $idxArticlesStartAt)
				{
					$numArticlesSkipped++;
					continue;
				}
				
				
				//we only show the number of articles per page that was defined in url param or default value
				if($numArticlesPrinted >= $numArticlesPerPage)
				{
					break;
				}
				
				
			?>

			<div class="article <?php print $article->prefixClass; ?>">

				<!-- datum und autor -->
				<div class="date-published"><?php print $article->datePublished->format("d.m.Y, H:i"); ?> von
					<a target="_blank" href="<?php print "$article->authorUrl"; ?>">
						<?php print "$article->author"; ?>
					</a>
				</div>


				<div class="articleinner">

					<h2>
						<!-- rubrik des artikels -->
						<div class="article-prefix <?php print $article->prefixClass; ?>"><?php print $article->prefix; ?></div>
						<!-- titel des artikels -->
						<span class="title">
							<a target="_blank" href="<?php print "$article->url"; ?>">
								<?php print "$article->title"; ?>
							</a>
						</span>
					</h2>


					<!-- text des artikels -->
					<div class="body">

						<!-- show article image if it exists -->
						<?php if(isset($article->image)) { ?>
							<div class="image">
								<img src="<?php print "$article->image"; ?>" alt="<?php print "$article->title"; ?>" />
							</div>
						<?php } ?>

						<!-- the article text -->
						<?php 						
							//you could apply additional filters here. but better put them intoto ngb_newsentry.php
							//print preg_replace('/(<br\s*\/*>\s*){3,}/', '<br />&nbsp;<br />', $article->body);
							print "$article->body"; 						
						?>

					</div> <!-- eof class body -->

				</div> <!-- eof articleinner -->

				<div class="date-comment">
					<!-- link to latest comment -->
					<a  class="comment-link" href="<?php print $article->lastCommentUrl; ?>" target="_blank">
						Letzter Kommentar: <?php print $article->dateUpdated->format("d.m.Y, H:i"); ?>
					</a> von
					<!-- name and link to latest comment author profile-->
					<a class="author-url" href="<?php print $article->lastCommentAuthorUrl; ?>" target="_blank">
						<span class="author-name"><?php print $article->lastCommentAuthor; ?></span>
					</a>
				</div> <!-- eof date-comment -->


			</div> <!-- eof article -->

			<?php
			
				//increase the visible articles counter. used for the pager (see above)
				$numArticlesPrinted++;
			
			}
		?>

		<!-- this line is shown after the last news article -->
		<div id="article-bottom">

			<?php
				//no heading filter, show link to general forum
				if($heading == "allenews")
				{
					$strLink = 'https://ngb.to/forums/61-ngb-news';
					$strLinkTitle = 'Ältere ngb:news und Diskussionen';
				}
				//show link to filtered news page
				else
				{
					$strLink = 'https://ngb.to/forums/61-ngb-news?s=&pp=20&daysprune=-1&sort=lastpost&prefixid=' . $heading . '&order=desc';
					$strLinkTitle = 'Ältere ngb:news und Diskussionen zum Thema ' . $heading;

				}
			?>

			<a class="link-to-forum" href="<?php print $strLink; ?>" title="ngb:news Forum">
				<?php print $strLinkTitle; ?>
			</a>

		</div>


		</div> <!-- eof latest-news -->




		<div id="latest-comments">
			<div id="head">Neueste Kommentare</div>
			<?php
			
			//counts how many comments are already printed.  
			$numCommentsPrinted = 0;
			
			//test output of article details
			foreach($lstInteresting as $article)
			{
			
			//we only show the number of comments per page that was defined in url param or default value
			if($numCommentsPrinted >= $numCommentsPerPage)
			{
				break;
			}
			
			?>

			<div class="article">
				<!-- article prefix / category -->
				<div class="article-prefix <?php print $article->prefixClass; ?>"><?php print $article->prefix; ?></div>
				<!-- title of the article -->
				<h2 class="title"><a target="_blank" href="<?php print "$article->url"; ?>"><?php print "$article->title"; ?></a></h2>

				<!-- created by -->
				<div class="date-published"><?php print $article->datePublished->format("d.m.Y"); ?> von
					<a target="_blank" href="<?php print "$article->authorUrl"; ?>">
						<?php print "$article->author"; ?>
					</a>
				</div>
				
				<div class="date-comment">
					<!-- link to latest comment -->
					<a  class="comment-link" href="<?php print $article->lastCommentUrl; ?>" target="_blank">
						Letzter Kommentar: <?php print $article->dateUpdated->format("d.m.y, H:i"); ?>
					</a>
					von
					<!-- name and link to latest comment author profile-->
					<a class="author-url" href="<?php print $article->lastCommentAuthorUrl; ?>" target="_blank">
						<span class="author-name"><?php print $article->lastCommentAuthor; ?></span>
					</a>
				</div>

				

			</div> <!-- eof article -->

			<?php
			
			//remember number of comments shown on page
			$numCommentsPrinted++;
			
			}
			?>
		</div> <!-- eof latest-comments -->

		<div id="clear"></div>

		<div id="more-news-button" style="display:none;" >
			<a href="https://ngb.to" target="_blank">Mehr News und Diskussion im ngb</a>
		</div>

	</div> <!-- eof content div -->

	<div class="below_body">

		<div id="footer_time" class="shade footer_time">
		<?php print $numNews; ?> News und <?php print $numInteresting; ?> beliebte Artikel geladen. Letzte Aktualisierung: <?php print date("d.m.Y, H:i", $dtLastUpdated); ?>
		</div>

		<div id="footer_copyright" class="shade footer_copyright">
			<!-- Sorry, it's gone ;) -->
			<p>Powered by Handardbeit™. </p>
		<p>Copyright © 2015 ngb.to. Alle Rechte scheißegal. </p>
			<!-- Sorry, it's gone ;) -->
		</div>



	</div> <!-- eof below body -->



</body>

</html>