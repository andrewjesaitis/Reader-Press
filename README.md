#Reader Press
Author: Andrew Jesaitis
Author URI: http://andrewjesaitis.com/
Plugin URI: http://andrewjesaitis.com/projects/reader-digest
Tags: google, reader, shareditems, feed, rss, post, digest, automatic
Requires at least: 2.8
Tested up to: 3.0.5

With the death of Google Reader comes the death of Reader Press. RIP.

###Installation
First, copy the reader-digest.php file to your plugin directory.

Next, activate the plugin under the plugins menu in your admin section.

Now we need to set up the options you'd like to use.

Here's how I do it:

1. Get Feed URL
a. Go to Google Reader
b. Click on shared items
c. Click on Sharing settings (Right hand side of header)
d. Click on "Preview your shared items page in a new window"
e. Click on atom feed icon
f. Copy this atom feed url to the field below

2. Enter a time when you'd like to post. Please note this is currently sync'd to EST.

3. Choose an interval. I use a weekly interval.

4. Pick a title for the post. All titles will be the same.

5. Select category for digest posts. Please note that this category needs to stay the same week to week and
needs to be unique to these digest posts. The reason being is that I use the time of the last post in the
category to figure out what shared items in reader are new.

6. Save

7. Start Automatic Posting

###Notes

If you'd like to sytle the links in the post you can add a section in you're CSS file for links with a
`class="digestLink"`.
