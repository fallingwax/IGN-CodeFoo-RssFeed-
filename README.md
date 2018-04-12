# IGN-CodeFoo-RssFeed-
RSS Feed Pull

There a three files associated with my RSS feed pulling service. 

The file "ign-database-db.txt" describes the creation of a MySql database with two tables called RssFeedContent and Thumbnails. Both of the tables contain there own Primary Keys for indexing and a Forgein Key on the GUID from the Rss Feed. 

The config.php contains four variables for storing the username, password, server name or IP, and the database name. This file on a production server would be hidden would an .htaccess file. 

RssFeed.php is where all the work is being done.  That program is grabbing the contents of the RSS Feed into a simple XML Element, parsing through it and storing the data using prepared statements into the MySQL database. 
