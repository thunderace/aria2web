<?php defined( '_ARIA2WEB' ) or die();
/**
* @version		$Id: README.php 8 2010-01-21 16:05:04Z soeren_nb $
* @package	aria2web
* @copyright	Copyright (C) 2010 soeren. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Aria2Web is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* http://sourceforge.net/projects/aria2web/
*/
?>

== About ==
Aria2Web is a utility for the web-based control of a download manager called "aria2".

	"aria2 can download a file from multiple sources/protocols and tries to utilize your maximum download bandwidth. 
	It supports downloading a file from HTTP(S)/FTP and BitTorrent at the same time, while the data downloaded from 
	HTTP(S)/FTP is uploaded to the BitTorrent swarm. Using Metalink's chunk checksums, aria2 automatically validates 
	chunks of data while downloading a file like BitTorrent."

While aria2 itself is designed to be operated from the command line, newer versions now feature an XML-RPC interface that opens the power of aria2 to other applications. 
Aria2Web uses this XML-RPC interface to communicate with a running instance of aria2c (or it can even fire up an instance of aria2c when run in "local" mode). 
This way you can add or remove downloads to aria2 and check the progress of the downloads. 


== Target Audience ==
A download manager like aria2 is extremely useful if you have a home server (like a NAS or a router) with allows you to access it via ssh or telnet. With Aria2 and Aria2Web you can turn it into a Download Station that downloads files to your local network so you don't need to let your computer turned on all night long.
Development.


== Requirements ==

to run Aria2Web you need
	* a Web Server (Apache, IIS, whatever can run PHP)
	* PHP >= 5.1 (with curl, xml-rpc)
	* aria2 >= 1.8.0 (command-line utility, download from http://sourceforge.net/projects/aria2/files/stable/latest)

Aria2Web itself can be installed on a different machine than aria2, both will communicate through the Web-based protocol XML-RPC.
aria2 has various package dependencies (http://packages.debian.org/sid/aria2).
On Linux/Unix-Systems you will need the following libraries: libc-ares2, libmxml2, libgnutls26, libgpg-error0, libslite3, libgcrypt11, zlib1g
	
If you have installed "ipkg", you can directly install the necessary packages.	
For my Buffalo LS-WXL (ARMEL architecture) I downloaded the aria2web deb package and all necessary libraries 
from here: http://packages.debian.org/sid/armel/aria2. You can exctract a .deb package on any Debian-based Linux using 
">dpkg -x XXXXXXX-1_armel.deb .", then copy the contents to your NAS.


== Installation ==
Copy the contents of the Aria2Web package to a directory of your choice and edit the file "config.php" with your own settings.
If you set "aria2_mode" to "local", then Aria2Web will try to execute the aria2c program on the machine itself runs. You should set "$aria2_xmlrpc_host" to the name
of the machine Aria2Web and aria2c are located
If you set "aria2_mode" to "web", you need to take care of starting aria2c on the host first.

== Development State ==
Currently Aria2Web is in development and not feature-complete (the same is valid for the development of the XML-RPC interface of Aria2). 
If you have a feature request, don't hesitate to add it to our tracker. Coders are very welcome, knowledge in PHP and/or Javascript (especially ExtJS) is required.
