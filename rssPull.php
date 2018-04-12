<?php

//getRSS gets the contents of our rss feed and returns a simple XML Element.  This function is called with the getContent function.

function getRSS($content_url) {
    
    $content = file_get_contents($content_url);
    $x = new SimpleXMLElement($content);
    return $x;
}

//setThumbnails inserts the thumbnail data into our MySql table Thumbnails. We are storing each row with a randomly generated 24 character alphanumeric ID (set using the function RandString(24)) and check that ID with a trigger before inserting it into our table.  We are also utilizing prepared statements to help prevent against SQL injection.   We also generating a log with a timestamp so that we can review all successes and failures. This function is called with the getContent function.
function setThumnails($config, $GUID, $array) {
    require($config);
    $timestamp = date("Y-m-d H:i:s");
    $con = new mysqli($host, $username, $pw, $db_name);
    if(mysqli_connect_errno()) {
        printf("Connection failed: %x\n", mysqli_connect_errno());
        exit();
    } else {
        $stmt = $con->stmt_init();
        $insert = "INSERT INTO `Thumbnails` (ThumbnailID, GUID, Link, Size, Width, Height) VALUES (RandString(24),?,?,?,?,?)";
        if($stmt->prepare($insert)) {
            $stmt->bind_param('sssss',$GUID, $link, $size, $width, $height);
            $link = $array[0];
            $size = $array[1];
            $width = $array[2];
            $height = $array[3];
            $stmt->execute();
            $stmt->store_result();
            $rows = $stmt->affected_rows;
            $file = 'thumbnailInsert.log';
            if($rows >= 1) {
                $log = ''.$timestamp.' '.$rows.' were inserted successfully';
                file_put_contents($file, $log, FILE_APPEND);
            } else {
                $log = ''.$timestamp.' 0 rows were inserted or an error has occurred';
                file_put_contents($file, $log, FILE_APPEND);    
            }   
        }
    }
    $con->close();
}

//setRSSFeedContent inserts the RSS Feed data into our MySql table RssFeedContent. We are storing each row with a randomly generated 24 character alphanumeric ID (set using the function RandString(24)) and check that ID with a trigger before inserting it into our table.  We are also utilizing prepared statements to help prevent against SQL injection. We also generating a log with a timestamp so that we can review all successes and failures. This function is called with the getContent function. 

function setRssFeedContent($config, $GUID, $category, $title, $description, $pubDate, $link, $slug, $networks, $state, $tags) {
    require($config);
    $timestamp = date("Y-m-d H:i:s");
    $con = new mysqli($host, $username, $pw, $db_name);
    if(mysqli_connect_errno()) {
        printf("Connection failed: %x\n", mysqli_connect_errno());
        exit();
    } else {
        $stmt = $con->stmt_init();
        $insert = "INSERT INTO `RssFeedContent` (rssID, GUID, Category, Title, Description, PubDate, Link, Slug, Networks, State, Tags) VALUES (RandString(24),?,?,?,?,?,?,?,?,?,?)";
        if($stmt->prepare($insert)) {
            $stmt->bind_param('ssssssssss',$GUID, $category, $title, $description, $pubDate, $link, $slug, $networks, $state, $tags);
            $stmt->execute();
            $stmt->store_result();
            $rows = $stmt->affected_rows;
            $file = 'rssFeedInsert.log';
            if($rows >= 1) {
                $log = ''.$timestamp.' '.$rows.' were inserted successfully';
                file_put_contents($file, $log, FILE_APPEND);
            } else {
                $log = ''.$timestamp.' 0 rows were inserted or an error has occurred';
                file_put_contents($file, $log, FILE_APPEND);    
            }   
        }
    }
    $con->close();
}

//the getContent function is used to parse each page of the Rss feed and gather the data and insert it into our MySql database one line at a time. We are normalizing the Publication Date field by calling a conversion with the DateTime method, primarly did this to match the date format of the CreateDate in the MySql table. 

function getContent() {
    include "config/config.php";
    $config = "config/config.php";
    $GUID = "";
    $category = "";
    $title = "";
    $description = "";
    $pubDate = "";
    $link = "";
    for ($i = 1; $i < 21; $i++) {
        $t = 0;
        $url = "https://ign-apis.herokuapp.com/content/feed.rss?page=".$i;
        $rss = getRss($url);
        $ignNameSpace = $rss->entry->children('ign',true);
            foreach($rss->channel->item as $item) {
                $j = 0;
                $GUID = $item->guid;
                $category = $item->category;
                $title = $item->title;
                $description = $item->description;
                $pubDate = $item->pubDate;
                $date = new DateTime($pubDate);
                $dateToSave = date_format($date, 'Y-m-d H:i:s');
                $link = $item->link;
                $slug = $rss->xpath('//ign:slug')[$j];
                $networks = $rss->xpath('//ign:networks')[$j];
                $state = $rss->xpath('//ign:state')[$j];
                if (isset($rss->xpath('//ign:tags')[$j])) {
                    $tags = $rss->xpath('//ign:tags')[$j];
                } else {
                    $tags = "";
                }
                $thumbnailCompact = [$rss->xpath('//ign:thumbnail')[$t]->attributes()['link'],$rss->xpath('//ign:thumbnail')[$t]->attributes()['size'],$rss->xpath('//ign:thumbnail')[$t]->attributes()['width'],$rss->xpath('//ign:thumbnail')[$t]->attributes()['height']];
                setThumnails($config, $GUID, $thumbnailCompact);
                $thumbnailMedium = [$rss->xpath('//ign:thumbnail')[$t+1]->attributes()['link'],$rss->xpath('//ign:thumbnail')[$t+1]->attributes()['size'],$rss->xpath('//ign:thumbnail')[$t+1]->attributes()['width'],$rss->xpath('//ign:thumbnail')[$t+1]->attributes()['height']];
                setThumnails($config, $GUID, $thumbnailMedium);
                $thumbnailLarge = [$rss->xpath('//ign:thumbnail')[$t+2]->attributes()['link'],$rss->xpath('//ign:thumbnail')[$t+2]->attributes()['size'],$rss->xpath('//ign:thumbnail')[$t+2]->attributes()['width'],$rss->xpath('//ign:thumbnail')[$t+2]->attributes()['height']];
                setThumnails($config, $GUID, $thumbnailLarge);
                setRssFeedContent($config, $GUID, $category, $title, $description, $dateToSave, $link, $slug, $networks, $state, $tags);
                $t += 3;   
            }
    }
}





getContent();



?>