[![Build Status](https://travis-ci.org/catalyst/moodle-block_carousel.svg?branch=master)](https://travis-ci.org/catalyst/moodle-block_carousel)

https://moodle.org/plugins/block_carousel

![Screen shot](/pix/screenshot.png?raw=true)

* [Branches](#branches)
* [What is this?](#what-is-this)
* [Installation and configuration](#installation-and-configuration)
* [Issues and feedback](#issues-and-feedback)
* [Credits and Thanks](#credits-and-thanks)

Branches
--------

For Moodle 3.6 Onwards, use the master branch. For Moodle 3.5 and older use the MOODLE_35_STABLE branch.


What is this?
-------------

An easy to use carousel block:

* responsive
* touch and mouse friendly
* video support


Installation and Configuration
------------------------------

1. Install the same as any other moodle plugin:

    Using git

     git clone git@github.com:catalyst/moodle-block_carousel.git blocks/carousel

    Or install via the Moodle plugin directory:

     https://moodle.org/plugins/block_carousel

2. Then run the Moodle upgrade

3. For use of videos in the carousel, install the [FFprobe tool](https://ffmpeg.org/download.html).

4. Visit Site Administration -> Plugins -> Blocks -> Carousel and configure the path to FFprobe.

3. Now add the block to a page, then configure it and add the slides


Contributing
------------

Pull requests are welcome, please adhere to the Moodle code standards
and use travis to check everything is green.

The slick JS library doesn't conform exactly to Moodle's code standards
so to rebuild the JS files run:

grunt amd --force


Issues and feedback
-------------------

If you have issues please log them in github here:

https://github.com/catalyst/moodle-block_carousel/issues

Or if you want paid support please contact Catalyst IT Australia:

https://www.catalyst-au.net/contact-us


Credits and thanks
------------------

The core of this plugin is the excellent 'slick' JS library by @kenwheeler:

http://kenwheeler.github.io/slick/

This plugin was sponsored by Central Queesnland University:

https://www.cqu.edu.au/

![CQU](/pix/cqu.png?raw=true)

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

<img alt="Catalyst IT" src="https://cdn.rawgit.com/catalyst/moodle-auth_saml2/master/pix/catalyst-logo.svg" width="400">
