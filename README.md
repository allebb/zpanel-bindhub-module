ZPanel module for BindHub.com
=============================

What does it do?
----------------
This module, monitors your server's public IP address and when it detects a new IP address will send an IP update request to your BindHub.com account to keep your DNS in-sync with your static IP address.

This module can easily be installed into ZPanel to enable people to host ZPanel servers from home where the use of a static IP address is not possible.

You can also host your own domains from home too, meaning that you can CNAME your own domains to point to your BindHub.com account thus enabling you and your customers to host their websites on your server whilst using a dynamic IP address!

How to install it
-----------------

Installation on WIndows, Linux, MacOSX, UNIX and *BSD is simple!

There is an official ZPPY repository for this module, so you can download and install automatically on your ZPanel server by running the following commands:-

Add the zppy repository to your list of repositories:-
`zppy repo add repo.bindhub.com`

Update your packages cache:-
`zppy update`

Now install the package like so:-
`zppy install bindhubupdater`

Once that's done, you just need to login to your ZPanel server and go to the 'Module Admin' module, and then tick the 'Administators' checkbox next to the BindHub DNS updater module (to enable the module to be visible to all 'Server Admins')

Then click on the 'BindHub DNS Updater' module icon on the main screen to configure the module (eg. set your BindHub.com username and API key and then be able to choose what domains should be kept in-sync.)

Simple right?

How to use it!
--------------

Once you've configured your BindHub.com account details (by accessing the module), the ZPanel daemon will automatically keep your IP address in sync with all of your selected domains.

This means that every five minutes (when the daemon runs), the module checks your public IP address and if it is different to your current IP it will then send an update request to BindHub.com thus keeping your DNS up-to-date.
