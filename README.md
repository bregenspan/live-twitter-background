LIVE TWITTER BACKGROUND - (c) RegenspanTeractive
===========================================================

Description
-----------

Live Twitter Background produces a Twitter background using
the desired "live" input data.

Example: http://cloud.github.com/downloads/bregenspan/live-twitter-background/example-background.jpg

Current available inputs:

* Recent followers
* Skype status

Dependencies
------------

* PHP5+
* Imagemagick
* Imagick PHP extension
* Verdana: verdana.ttf and verdanab.ttf (get them from 
    http://corefonts.sourceforge.net/ and place in assets/fonts)
* twitter-async by Jaisen Mathai (included in "/lib" folder)
    More info: https://github.com/jmathai/twitter-async

Usage
-----

* Set up a new application on twitter.com
* In config.json, set the app keys and user token info
* Ensure that the folder containing generate-background.php,
    as well as the "retrieved" folder, arewriteable to the
    user you will run the script with
* Tweak other config.json settings as desired.
    For a different background image, place the image
    in assets/images/ and update the display->background_image
    config.json property to the new filename.

    Run: <strong>php generate-background.php</strong>

    You may want to first comment out the line:
    <strong>$twh->setBackground('background.jpg');</strong>

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

Verdana is not a very good font and could probably be replaced
with an open source one (or licensed Helvetica if you want
to be classy).

License
-------

BSD license (see LICENSE.txt)

Images and fonts in "assets" are (c) the respective
rightsholders, and included for convenience:

* /assets/images/clouds.png is (c) Twitter
* Skype logo (c) Skype
* League Gothic by The League of Moveable Type
    (http://www.theleagueofmoveabletype.com/)
