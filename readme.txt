=== Rate Star Review Vote - AJAX Reviews, Votes, Star Ratings ===
Contributors: videowhisper
Author: VideoWhisper.com
Author URI: https://videowhisper.com
Plugin Name: Rate Star Review
Plugin URI: https://fanspaysite.com/
Donate link: https://videowhisper.com/?p=Invest
Tags: rate, star, review, vote, ajax
Requires at least: 5.1
Tested up to: 6.5
Stable tag: trunk

Boost engagement with AJAX-driven star ratings, reviews, vote buttons for content.


== Description ==

Enhance your WordPress site with the Rate Star Review plugin, enabling users to leave star ratings and detailed reviews for any type of content. This robust plugin supports multiple rating dimensions per post, vote buttons, AJAX updates for seamless user experience, and integrates customizable review types by content. Ideal for content creators aiming to improve interaction and obtain feedback, it also features integration options for monetizing votes, making it perfect for contests and premium content strategies.

=  Benefits = 
* Flexible Review System: Allow users to rate, vote, review any type of content including posts, pages, and custom post types.
* AJAX Functionality: Reviews, ratings, votes (likes) update dynamically without page reloads, offering a smooth user experience.
* Customizable Star Ratings: Choose from various scales (e.g., 3, 5, 10 stars) to suit different review contexts.
* Multiple Content Dimensions: Support for reviewing multiple aspects of a single item, such as different features or performance metrics.
* Monetization of Reviews: Integrate with the MicroPayments Plugin to enable paid votes (likes), adding a revenue stream.
* Comprehensive Shortcodes: Easily embed review forms and ratings on any page using customizable shortcodes.
* Live Updates: Review lists and average ratings update live on the page as users submit their feedback.
* Category-Specific Ratings: Manage and display ratings by category for targeted insights and contest management.
* Enhanced User Engagement: Encourage community interaction by allowing users to express their opinions and participate in reviews.
* Skin in the Game: Monetize user participation in contests and premium content by requiring paid votes.

= Key Features = 
* Star Ratings, Review Title and Text Content
* AJAX review and lists (no page reload required)
* Unlimited review types associated by content type, content id, post id
* Update review (after adding review, it can be updated anytime with same form)
* Ratings by category (rate and also get stats by category)
* Shortcodes to add review, list reviews, display ratings
* Separately review multiple aspects and content type for an item
* Live update of review list on same page when adding, updating review
* Updates and can display average rating per post (meta)
* Custom maximum stars (ex: 3, 5, 10 stars)
* Configure post types to include reviews for (post, page)
* Vote and vote count per post, for specific categories (i.e. for contests like Top Summer Videos)
* Integrates APF Optimizer for speed and resources on AJAX requests: Filter plugins allowed to run on specific pages
* Integrates with [MicroPayments](https://wordpress.org/plugins/paid-membership/ "MicroPayments/FansPaysite – Paid Author Subscriptions, Digital Assets, Downloads, Membership") Plugin to support paid votes on selected categories, introduce skin in the game for contests and premium content

= Recommended for use with these solutions =
* [Paid VideoChat](https://paidvideochat.com/ "Paid VideoChat - HTML5 Pay Per Minute Turnkey Site")
* [Broadcast Live Video](https://broadcastlivevideo.com/ "Broadcast Live Video - HTML5 Streaming Turnkey Site")
* [Video Share VOD](https://wordpress.org/plugins/video-share-vod/  "Video Share / Video On Demand Turnkey Site")
* [Picture Gallery](https://wordpress.org/plugins/picture-gallery/  "Picture Gallery – Frontend Image Uploads, AJAX Photo List") - Picture Gallery – Frontend Image Uploads, AJAX Photo List.


= Shortcodes =

[videowhisper_review post_id="" content_type="" content_id="" rating_max="5" id="" update_id=""]
Shows form to add and update review for specific post and content. AJAX based. Can also update reviews list if on same page.

[videowhisper_reviews post_id="" show_average="1" content_type="" content_id="" id=""]
Lists reviews for specific content (by post,content). At least post_id or content_id must be specified. AJAX based.

[videowhisper_rating post_id="" rating_max="5"]
Displays average rating for a post (average of all ratings for that post).

= Post Metas =

Updates these meta valuate when rating posts:
- rateStarReview_rating = average rating normalized as value between 0 and 1 (multiply with maximum to display)
- rateStarReview_ratingNumber = number of reviews
- rateStarReview_ratingPoints = sum of normalized ratings for easy sorting popular items (rating * ratingPoints)

Rating by category will update those for each rated category as:
- rateStarReview_rating_category$id
- rateStarReview_ratingNumber_category$id
- rateStarReview_ratingPoints_category$id

= How to use this? = 
In example, if you have a post presenting an electronic product and want site members to be able to review and rate separately different aspects like Features and Performance these can be content types.
A review form for each content type can be setup: 
[videowhisper_review content_type="Features" post_id="1"]
[videowhisper_review content_type="Performance" post_id="1"]
Then to show all reviews for that item, you can use [videowhisper_reviews post_id="1"] .

Another example, if an article is about a book with 2 parts, you can also use content_id to allow users to post a review for each part for each aspect (like Utility, Clarity).
[videowhisper_review content_type="Utility for Part" content_id="1" post_id="1"]
[videowhisper_review content_type="Utility for Part" content_id="2" post_id="1"]
[videowhisper_review content_type="Clarity for Part" content_id="1" post_id="1"]
[videowhisper_review content_type="Clarity for Part" content_id="2" post_id="1"]
Then list all reviews for all parts, [videowhisper_reviews post_id="1"] or just for an aspect or part.


== Screenshots ==
1. Review form and review list.

== Changelog ==

= 1.6 =
* Integrates APF Optimizer for speed and resources on AJAX requests: Filter plugins allowed to run on specific pages

= 1.5 = 
* Integrates with [MicroPayments Plugin](https://wordpress.org/plugins/paid-membership/ "MicroPayments – Paid Author Subscriptions, Digital Assets, Downloads, Membership") to support paid votes on selected categories

= 1.4 = 
* Content vote with [videowhisper_vote post_id=""] shortcoded (can automatically show on content pages)

= 1.3 =
* Rate and display ratings by category (if enabled)
* Support for special characters
* PHP 8 support

= 1.2 =
* Calculate and save average rating per post.

= 1.1.1 =
* First release.
