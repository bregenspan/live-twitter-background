LIVE TWITTER BACKGROUND - (c) RegenspanTeractive
===========================================================

Description
-----------

Live Twitter Background produces a Twitter background using
the desired "live" input data.

Current available inputs:

* Recent followers
* Skype status

Dependencies
------------

* PHP5+
* Imagemagick
* Imagick PHP extension

Usage
-----

* Set up a new application on twitter.com
* In config.json, set the app keys and user token info
* Tweak other config.json settings as desired.
    For a different background image, place the image
    in assets/images/ and update the display->background_image
    config.json property to the new filename.

    Run:
    > php generate-background.php

    You may want to first comment out the line:
    > $twh->setBackground('background.jpg');

    Until you are satisfied with the outputted background.jpg

* Add to a cron for "live" updates


Notes
-----

This is an old project with many TODOs including ability
to right-align the list of followers (default is left-align),
plus better comments and code cleanup.

I stopped developing this when Twitter increased the width
of the default desktop interface, however the generated
backgrounds are still fully visible in browser windows of 
1300px+ width.
