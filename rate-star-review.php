<?php
/*
Plugin Name: Rate Star Review Vote - AJAX Reviews, Votes, Star Ratings
Plugin URI: https://videowhisper.com 
Description: <strong>Rate Star Review Vote - AJAX Reviews, Votes, Star Ratings</strong>: Multiple ratings and reviews for content (including custom post types) using AJAX. <a href='https://consult.videowhisper.com?topic=Rate-Star-Review'>Contact Us</a> | <a href='https://wordpress.org/support/plugin/rate-star-review/reviews/#new-post'>Review Plugin</a> 
Version: 1.6.3
Author: VideoWhisper.com
Author URI: https://videowhisper.com/
Contributors: videowhisper, VideoWhisper.com
Text Domain: rate-star-review
Domain Path: /languages/
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists("VWrateStarReview"))
{
	class VWrateStarReview {

		public function __construct()
		{
		}

		public function VWrateStarReview() { //constructor
			self::__construct();

		}

		static function install() {

			// do not generate any output here

			VWrateStarReview::review_post();

			flush_rewrite_rules();
		}

		function init()
		{
			//setup post
			VWrateStarReview::review_post();
		}

		static function plugins_loaded()
		{

			// translations
			load_plugin_textdomain( 'rate-star-review', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin",  array('VWrateStarReview','settings_link') );

			add_filter('the_content', array('VWrateStarReview','the_content'), 12);


			//shortcodes
			add_shortcode('videowhisper_vote', array( 'VWrateStarReview', 'videowhisper_vote'));

			add_shortcode('videowhisper_review', array( 'VWrateStarReview', 'videowhisper_review'));
			add_shortcode('videowhisper_reviews', array( 'VWrateStarReview', 'videowhisper_reviews'));
			add_shortcode('videowhisper_rating', array( 'VWrateStarReview', 'videowhisper_rating'));
			add_shortcode('videowhisper_ratings', array( 'VWrateStarReview', 'videowhisper_ratings'));
			add_shortcode('videowhisper_review_featured', array( 'VWrateStarReview', 'videowhisper_review_featured'));


			//web app ajax calls
			add_action( 'wp_ajax_vwrsr_vote', array('VWrateStarReview','vwrsr_vote') );
			add_action( 'wp_ajax_nopriv_vwrsr_vote', array('VWrateStarReview','vwrsr_vote') );

			add_action( 'wp_ajax_vwrsr_review', array('VWrateStarReview','vwrsr_review') );
			add_action( 'wp_ajax_nopriv_vwrsr_review', array('VWrateStarReview','vwrsr_review') );

			add_action( 'wp_ajax_vwrsr_reviews', array('VWrateStarReview','vwrsr_reviews') );
			add_action( 'wp_ajax_nopriv_vwrsr_reviews', array('VWrateStarReview','vwrsr_reviews') );
		}


		static function the_content($content)
		{

			if (!is_single()) return $content;

			$options = self::getOptions();
			if (!$options['review_posts'] && !$options['voting_categories']) return $content;

			$postID = get_the_ID() ;
			$post_type = get_post_type( $postID );
			$preCode = '';
			$afterCode = '';

			if (isset($options['voting_categories']) && $options['voting_categories'] && $options['vote_posts'])
			{ 
				$vote_posts = explode(',',$options['vote_posts']);

				if (is_array($vote_posts)) foreach ($vote_posts as $vote_post)
					if ( $post_type == trim($vote_post) )
					{
						// category checked in shortcode
						$preCode .= do_shortcode('[videowhisper_vote post_id="'. $postID .'"]' );
						break;
					}

			}

			if ($options['review_posts'])
			{
			$review_posts = explode(',',$options['review_posts']);

			if (is_array($review_posts)) foreach ($review_posts as $review_post)
				if ( $post_type == trim($review_post) )
				{
					$afterCode = '<h3>' . __('My Review', 'rate-star-review') . '</h3>' . do_shortcode('[videowhisper_review content_type="'. $post_type .'" post_id="' . $postID . '" content_id="' . $postID . '"]' );

					$afterCode .= '<h3>' . __('Reviews', 'rate-star-review') . '</h3>' . do_shortcode('[videowhisper_reviews post_id="' . $postID . '"]' );

					break;
				}

			}

			return $preCode . $content . $afterCode;

		}

		static function updatePostRating($post_id)
		{

			if (!$post_id) return;

			$options = VWrateStarReview::getOptions();

			$args = array(
				'post_type'    => $options['custom_post'], //review
				'meta_query' => array(
					'relation'  => 'AND',
					'post_id'   => array('key'     => 'post_id', 'value' => $post_id),
				),

			);

			$postslist = get_posts($args); //the ratings

			if (count($postslist))
			{
				$ratingsCount = 0;
				$ratingsSum = 0;

				$categoryCount = array();
				$categorySum = array();

				foreach ( $postslist as $item )
				{
					$post = get_post($item);

					$rating =  floatval(get_post_meta( $post->ID, 'rating', true));
					$rating_max =  floatval(get_post_meta( $post->ID, 'rating_max', true));
					if (!$rating_max) $rating_max = $options['rating_max'];

					$category=0;
					$cats = wp_get_post_categories( $post->ID);
					if (count($cats)) $category = array_pop($cats);
					
					
					if ( !isset($categorySum[$category]) ) $categorySum[$category]  = 0;
					if ( !isset($categoryCount[$category]) ) $categoryCount[$category]  = 0;
				

					//totals
					$ratingsSum += $rating / $rating_max ;
					$ratingsCount++;

					//by categories
					$categorySum[$category] += $rating / $rating_max ;
					$categoryCount[$category]++ ;
				}

				if ($ratingsCount)
				{
					$rating_average = number_format($ratingsSum / $ratingsCount, 2); // 2 decimals

					update_post_meta($post_id, 'rateStarReview_rating', $rating_average);
					update_post_meta($post_id, 'rateStarReview_ratingNumber', $ratingsCount);
					update_post_meta($post_id, 'rateStarReview_ratingPoints', $ratingsSum);
				}

				//set empty categories
				$categories = get_categories();
				foreach ($categories as $category)
					if (!array_key_exists($category->term_id, $categoryCount))
					{
						$cat = $category->term_id;
						delete_post_meta($post_id, 'rateStarReview_rating_category' . $cat);
						delete_post_meta($post_id, 'rateStarReview_ratingNumber_category'. $cat);
						delete_post_meta($post_id, 'rateStarReview_ratingPoints_category'. $cat);
					}

				//
				if (!empty($categoryCount))
					foreach ($categoryCount as $cat=>$value)
					{
						$rating_average = number_format($categorySum[$cat] / $categoryCount[$cat], 2); // 2 decimals

						update_post_meta($post_id, 'rateStarReview_rating_category' . $cat, $rating_average);
						update_post_meta($post_id, 'rateStarReview_ratingNumber_category'. $cat, $categoryCount[$cat]);
						update_post_meta($post_id, 'rateStarReview_ratingPoints_category'. $cat, $categorySum[$cat]);
					}



				return  $rating_average;
			}

			return 0;
		}

		static function enqueueScripts()
		{

			wp_enqueue_script("jquery");

			//wp_enqueue_style( 'semantic', plugin_dir_url(  __FILE__ ) . '/scripts/semantic/semantic.min.css');
			//wp_enqueue_script( 'semantic', plugin_dir_url(  __FILE__ ) . '/scripts/semantic/semantic.min.js', array('jquery'));

			wp_enqueue_style( 'fomantic', 'https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.css');
			wp_enqueue_script( 'fomantic', 'https://cdn.jsdelivr.net/npm/fomantic-ui@2.8.7/dist/semantic.min.js', array('jquery'));

		}

static function reviewCard($post, $rating_max = 5, $contentType = true)
{
					$rating_max = intval($rating_max);
					
					$rating =  floatval(get_post_meta( $post->ID, 'rating', true));
					$category = '';
					$cats = wp_get_post_categories( $post->ID);				
					if (count($cats)) $category = intval(array_pop($cats));
					
					$htmlCode = '<div class="card">';

					$htmlCode .= '<div class="content">
    <div class="right floated header">' . $rating . '/' . $rating_max . '</div>
   <div id="rating" class="ui yellow large star rating readonly" data-rating="' . $rating . '" data-max-rating="' . intval($rating_max) . '"></div>
  </div>';

					$htmlCode .= '<div class="content">';
					$htmlCode .= '<div class="header">  ' . esc_html($post->post_title) . ' </div>';
				
					if ($contentType)
					{
					$content_type =  sanitize_file_name( get_post_meta( $post->ID, 'content_type', true) );						
					$htmlCode .= '<div class="extra">Content Type: ' . $content_type . '</div>';
					}
				
					if ($category != '')
					{
						$cat = get_category($category);
						if ($cat) $htmlCode .= '<div class="extra">Category: ' . $cat->name . '</div>';
					}

					$htmlCode .= '<div class="description" style="max-height:40px; overflow-x: hidden; overflow-y: auto;"><p>' . sanitize_textarea_field( $post->post_content ). '</p></div>';

					$htmlCode .='</div>';

					$user = get_userdata($post->post_author);

					$htmlCode .= '<div class="extra content">
    <div class="right floated author">
      <img class="ui avatar image" src="' . get_avatar_url($post->post_author). '"> ' . $user->user_nicename. '
    </div>
          <span class="date">' . get_the_time("j M Y",$post->ID) . '
      </span>

  </div>';

					$htmlCode .='</div>';
					
					return $htmlCode;
}
		//!shortcodes

			static function videowhisper_vote($atts)
			{
				//display votes and button for a post, if in vote category
				$options = VWrateStarReview::getOptions();

				//shortocode attributes
				$atts = shortcode_atts(
					array(
						'post_id'=> 0, //associated with (to display for)
						'id' => '', //unique id (to identify when using multiple)
					), $atts, 'videowhisper_vote');

					
					$post_id = intval($atts['post_id']);
					if (!$post_id) if (is_single()) $post_id = get_the_ID(); //current post if not set

					$id = $atts['id'];
					if (!$id) $id = 'v' . $post_id . 'r' . rand(1000,9999);

					$voteCategories = self::categoryIDs($options['voting_categories'] ?? '');

					//check if post is in vote category
					$cats = wp_get_post_categories( $post_id);
					$vote = false;
					foreach ($cats as $cat)
						if (in_array($cat, $voteCategories))
						{
							$vote = true;
							break;
						}

					if (!$vote) return ''; //not enabled for this category

					$ajaxurl = admin_url() . 'admin-ajax.php?action=vwrsr_vote&post_id=' . $post_id . '&id=' . $id;

			VWrateStarReview::enqueueScripts();

			$loadingMessage = '<div class="ui active inline text tiny loader">...</div>';

			$htmlCode = <<<HTMLCODE
<script>
var aurl$id = '$ajaxurl';
var loader$id;

	function loadVotes$id(message = '', vars = ''){

	if (message)
	if (message.length > 0)
	{
	  jQuery("#videowhisperVote$id").html(message);
	}

		if (loader$id) loader$id.abort();

		loader$id = jQuery.ajax({
			url: aurl$id,
			data: "interfaceid=$id" + vars,
			success: function(data) {
				jQuery("#videowhisperVote$id").html(data);
				jQuery('.ui.rating.active').rating();
			}
		});
	}

	jQuery(document).ready(function(){
		loadVotes$id();
	});

</script>

<span id="videowhisperVote$id" class="videowhisperVote">
    $loadingMessage
</span>
HTMLCODE;

			return $htmlCode;
					
			}

			static function vwrsr_vote()
			{

				//output clean (clear 0)
				ob_clean();
	
				$options = VWrateStarReview::getOptions();

				$id = sanitize_file_name($_GET['id']); //used in JS function naming, may be used in file form caching
				$post_id = intval($_GET['post_id'] ?? 0);
				$vote = intval($_GET['vote'] ?? 0);
		
				$votes = get_post_meta( $post_id, 'rateStarReview_votes', true);
				if (!is_array($votes)) $votes = [];


				$paidVotes = false;
				if ($options['micropayments']) if (class_exists( 'VWpaidMembership' ))
				{
				$paidCategories = self::categoryIDs($options['paid_categories']);
				$postCategories = wp_get_post_categories( $post_id );
				$commonCategories = array_intersect($paidCategories, $postCategories);
				if (count($commonCategories)) $paidVotes = true;
				}

				$htmlCode = '';

				//process voting
				if (is_user_logged_in()) $user = wp_get_current_user();
				else $user = false;

				if ($user) 
				{

					if ($vote == 1)
					{
						$invalid = false;

						if ($paidVotes)
						{
							$cost = trim($options['micropaymentsVote']);
							$balance = \VWpaidMembership::balance();

							if ($cost <= $balance)
							{
								$ratio = floatval( $options['micropaymentsRatio'] );
								$earning = $cost * $ratio;
								
								$author_id = get_post_field( 'post_author', $post_id );
								$post_title =  get_post_field( 'post_title', $post_id );
								$post_type =  get_post_field( 'post_type', $post_id );

								$itemURL = get_permalink($post_id);
							
								\VWpaidMembership::transaction( 'rate_vote', $user->ID, - $cost, 'Vote on ' . $post_type .  ' <a href="' . $itemURL . '">#' . esc_html(  $post_title ) . '</a>'  );
							
								if ($earning) \VWpaidMembership::transaction( 'rate_vote_earn', $author_id , $earning  , 'Vote earning for ' . $post_type .  ' <a href="' . $itemURL . '">#' .  esc_html( $post_title ) . '</a> from ' . $user->user_login );
							} 
							else $invalid = true; 

						}

						if (!$invalid)
						{
						if ( !is_array($votes) ) $votes = array();
						if ( !in_array($user->user_login, $votes) ) $votes[] = $user->user_login;

						update_post_meta( $post_id, 'rateStarReview_votes', $votes);
						update_post_meta( $post_id, 'rateStarReview_voteCount', count($votes) );
						}

					}

					if ($vote == 2)
					{
						if (is_array($votes))
						{
							$index = array_search($user->user_login, $votes);
							if ($index !== false) unset($votes[$index]);
						}
						update_post_meta( $post_id, 'rateStarReview_votes', $votes);
						update_post_meta( $post_id, 'rateStarReview_voteCount', count($votes) );
					}
				}

			$voteCount = count($votes);
			$htmlCode .= '<span class="ui small label violet" data-tooltip="' . $voteCount . ' '. __('Votes', 'rate-star-review') . '"><i class="thumbs up icon outline"></i>' . $voteCount . '</span>';

			  if ($user)
			  {
				
				if (in_array($user->user_login, $votes)) 
				{
					if ($options['retractVote'] == '2' || ($options['retractVote'] == '1' && !$paidVotes))
					$htmlCode .= '<span class="ui mini compact icon button blue" data-tooltip="' . __('Retract Vote', 'rate-star-review') . '" onclick="loadVotes' . $id . '(\'Retracting..\', \'&vote=2\');"><i class="thumbs down icon"></i></span>';
				}
				else 
				{
					$paidInfo = '';
					$disabled = '';
					if ($paidVotes)
					{
						$cost = trim($options['micropaymentsVote']);
						$currency = \VWpaidMembership::option( 'currency' );
						$balance = \VWpaidMembership::balance();

						$paidInfo = ' (' . __('Pay', 'rate-star-review') . ' '. $cost  . ' ' . $currency . ')';

						if ($balance < $cost) $disabled = 'disabled';
					}

					$htmlCode .= '<span class="ui mini compact icon button blue ' . $disabled . '" data-tooltip="' . __('Vote', 'rate-star-review') . $paidInfo. '" onclick="loadVotes' . $id . '(\'Voting..\', \'&vote=1\');"><i class="thumbs up icon"></i></span>';
				}
			}

				echo $htmlCode;
				die();
			}

			static function videowhisper_review_featured($atts)
			{
				//displays a featured review

			$options = VWrateStarReview::getOptions();


			if (is_single()) $postID = get_the_ID(); //is on a post page
			else $postID = 0;

			//shortocode attributes
			$atts = shortcode_atts(
				array(
					'post_id'=> $postID, //associated with (to display for)
					'rating_max' => $options['rating_max'], //maximum rating
				), $atts, 'videowhisper_review_featured');

				$post_id = intval($atts['post_id']);
				
				$args = array(
					'post_type'    => $options['custom_post'], //review
					'orderby'          => 'meta_value_num',
					'meta_key' 			=> 'rating',					
					'order'             => 'DESC',
					'posts_per_page'    => 1,			
					'meta_query' => array(
						'relation'  => 'AND',
						'post_id'   => array('key'     => 'post_id', 'value' => $post_id),
					),
				);
				
				$postslist = get_posts($args); //the ratings
				if (empty($postslist)) $htmlCode = 'No reviews.';
				else foreach ($postslist as $item) 
				{
					$post = get_post($item);
					$htmlCode = self::reviewCard($post, $atts['rating_max'], false);
				}
				
				return $htmlCode;

				die();

			}
			
			
		static function videowhisper_rating($atts)
		{
			$options = VWrateStarReview::getOptions();

			if (is_single()) $postID = get_the_ID(); //is on a post page
			else $postID = 0;

			//shortocode attributes
			$atts = shortcode_atts(
				array(
					'post_id'=> $postID, //associated with (to display for)
					'rating_max' => $options['rating_max'], //maximum rating
					'category' => '',
				), $atts, 'videowhisper_rating');

			if ($atts['rating_max'] <= 0) return 'Invalid rating_max!';
			if ($atts['post_id'] <= 0) return 'Invalid post_id!';

			VWrateStarReview::enqueueScripts();

			$max = intval($atts['rating_max']);

			if ($atts['category'] == '') $rating = get_post_meta($atts['post_id'], 'rateStarReview_rating', true);
			else $rating = get_post_meta($atts['post_id'], 'rateStarReview_rating_category' . $atts['category'], true);

			if ($rating)
				$htmlCode .= '<label>' . number_format($rating * $max, 2) . '/' . $max . '</label> <div class="ui yellow huge star rating readonly" data-rating="' . round($rating * $max) . '" data-max-rating="' . $max . '"></div>';
			else $htmlCode .= 'No rating, yet!';

			$htmlCode .= '<script>
	jQuery(document).ready(function(){ 	jQuery(\'.ui.rating.readonly\').rating(\'disable\'); });
</script>';

			return $htmlCode;

		}


		static function videowhisper_ratings($atts)
		{
			$options = VWrateStarReview::getOptions();

			if (is_single()) $postID = get_the_ID(); //is on a post page
			else $postID = 0;

			//shortocode attributes
			$atts = shortcode_atts(
				array(
					'post_id'=> $postID, //associated with (to display for)
					'rating_max' => $options['rating_max'], //maximum rating
					'category' => '',
				), $atts, 'videowhisper_rating');

			if ($atts['rating_max'] <= 0) return 'Invalid rating_max!';
			if ($atts['post_id'] <= 0) return 'Invalid post_id!';

			VWrateStarReview::enqueueScripts();
			$max = intval($atts['rating_max']);

			$htmlCode = '<div class="ui message">';
			$htmlCode .= '<div class="ui header"> ' . __('Average Rating', 'rate-star-review') . ': ';
			$rating = get_post_meta($atts['post_id'], 'rateStarReview_rating', true);
			if ($rating)
				$htmlCode .= '<label>' . number_format($rating * $max, 2) . '/' . $max . '</label> <div class="ui yellow huge star rating readonly" data-rating="' . round($rating * $max) . '" data-max-rating="' . $max . '"></div>';
			else $htmlCode .= 'No rating, yet!';
			$htmlCode .= '</div>';

			//by category
			$categories = get_categories();
			foreach ($categories as $category)
			{
				$rating = get_post_meta($atts['post_id'], 'rateStarReview_rating_category' . $category->term_id, true);

				if ($rating) $htmlCode .= '<div><label>'. $category->name . ': '. number_format($rating * $max, 2) . '/' . $max . '</label> <div class="ui yellow small star rating readonly" data-rating="' . round($rating * $max) . '" data-max-rating="' . $max . '"></div></div>';
			}
			$htmlCode .= '</div>';

			return $htmlCode;
		}

		static function videowhisper_review($atts)
		{
			$options = VWrateStarReview::getOptions();

			if (is_single()) $postID = get_the_ID(); //is on a post page
			else $postID = 0;

			if ($postID) $content_type = get_post_type( $postID);
			else $content_type = 'default';

			//shortocode attributes
			$atts = shortcode_atts(
				array(
					'content_type'=> $content_type, //content reviewed: post type, session
					'content_id'=> $postID, //id of content reviewed (session, post)
					'post_id'=> $postID, //associated with (to display for)
					'rating_max' => $options['rating_max'], //maximum rating
					'update_id' => '', //id of reviews list to update
					'id' => '' //id of review form
				), $atts, 'videowhisper_review');

			if (!$atts['id']) $id = 'Review';
			else $id = 'Review' . sanitize_file_name( $atts['id'] );

			if (!$atts['update_id']) $updateid = 'Reviews';
			else $updateid = 'Reviews' . $atts['id'];

			if (!$atts['content_id']) return 'No content_id!';
			if (!$atts['rating_max']) return 'Invalid rating_max!';

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwrsr_review&content_id=' . urlencode($atts['content_id']) . '&content_type=' . urlencode($atts['content_type']) . '&post_id=' . urlencode($atts['post_id']) . '&rating_max=' . urlencode($atts['rating_max']) . '&id=' . urlencode($id). '&uid=' . urlencode($updateid);

			VWrateStarReview::enqueueScripts();

			$loadingMessage = '<div class="ui active inline text large loader">' . __('Review Form','rate-star-review') . '...</div>';

			$htmlCode = <<<HTMLCODE
<script>
var aurl$id = '$ajaxurl';
var loader$id;

	function loadContent$id(message = '', vars = ''){

	if (message)
	if (message.length > 0)
	{
	  jQuery("#videowhisperContainer$id").html(message);
	}

		if (loader$id) loader$id.abort();

		loader$id = jQuery.ajax({
			url: aurl$id,
			data: "interfaceid=$id" + vars,
			success: function(data) {
				jQuery("#videowhisperContainer$id").html(data);
				jQuery('.ui.rating.active').rating();
			}
		});
	}

	jQuery(document).ready(function(){
		loadContent$id();
	});

</script>

<div id="videowhisperContainer$id" class="videowhisperContainer ui segment">
    $loadingMessage
</div>
HTMLCODE;


			return $htmlCode;

		}

		static function vwrsr_review()
		{
			$options = VWrateStarReview::getOptions();

			$id =  sanitize_file_name($_GET['id']); //used in JS function naming, may be used in file form caching
			$uid = sanitize_file_name($_GET['uid']); //update $id


			//output clean (clear 0)
			ob_clean();

			if (!is_user_logged_in())
			{
				echo __('Login to review content!','rate-star-review') . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __('Login', 'rate-star-review') . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __('Register', 'rate-star-review') . '</a>';
				die();
			}
			
			$current_user = wp_get_current_user();

			$content_type = sanitize_text_field($_GET['content_type']);
			$content_id = intval($_GET['content_id']);
			$post_id = intval($_GET['post_id']);

			$rating_max = intval($_GET['rating_max']);
			if (!$rating_max) $rating_max = 5;

			$form = sanitize_key($_GET['form'] ?? '' );

			if ($form == 'insert' || $form == 'update')
			{
				//save
				$title = esc_attr( sanitize_text_field($_GET['title'] ?? '') );
				$content = sanitize_textarea_field($_GET['content'] ?? '' );
				$rating = intval($_GET['rating'] ?? 0);

				$rating_id = intval( $_GET['rating_id'] ?? 0 );

				//if not provided use default category
				$category = intval($_GET['category'] ?? 0);
				if ($_GET['category']=='') if ($post_id)
					{
						$cats = wp_get_post_categories( $post_id);
						if (count($cats)) $category = array_pop($cats);
					}

				$post = array(
					'post_title'     => $title,
					'post_author'    => $current_user->ID,
					'post_content'   => $content,
					'post_type'      => $options['custom_post'],
					'post_status'    => 'publish',
				);

				if ($form == 'insert' ) $rating_id = wp_insert_post($post);


				if ($form == 'update' )
				{
					$post['ID'] = $rating_id;
					wp_update_post($post);
				}

				//update rating
				update_post_meta($rating_id, 'rating', $rating);
				update_post_meta($rating_id, 'rating_max', $rating_max);

				update_post_meta($rating_id, 'content_id', $content_id);
				update_post_meta($rating_id, 'content_type', $content_type);
				update_post_meta($rating_id, 'post_id', $post_id);

				if($category) wp_set_post_categories($rating_id, array($category));

				if ($post_id) VWrateStarReview::updatePostRating($post_id);

				$loadingMessage = '<div class="ui active inline text large loader">' . __('Updating Reviews','rate-star-review') . '...</div>';

				echo <<<HTMLCODE
				<script>
if (typeof loadContent$uid === "function") { loadContent$uid('$loadingMessage'); }
				</script>
HTMLCODE;
			}

			//check if already reviewed
			$args = array(
				'author'     => $current_user->ID,
				'post_type'    => $options['custom_post'], //review

				'meta_query' => array(
					'relation'    => 'AND',
					'content_id' => array(
						'key' => 'content_id',
						'value' => $content_id
					),
					'content_type'    => array(
						'key'     => 'content_type',
						'value' => $content_type
					),
					'post_id'    => array(
						'key'     => 'post_id',
						'value' => $post_id
					),
				),

			);

			$postslist = get_posts($args);

			if (count($postslist)>1) echo 'Integration Error: Multiple reviews found for this context!';

			if (count($postslist))
				foreach ( $postslist as $item ) //update my review(s)
					{
					$post = get_post($item);

					$rating =  floatval(get_post_meta( $post->ID, 'rating', true));

					$category = 0;
					if ($post_id) //match post category as default
						{
						$cats = wp_get_post_categories( $post_id);
						if (count($cats)) $category = array_pop($cats);
					}

					//rating category
					$cats = wp_get_post_categories( $post->ID);
					if (count($cats)) $category = intval(array_pop($cats));

					echo '<div class="ui form">';
					echo'<div class="field"><label>Rating</label><div id="rating" class="ui yellow massive star rating active" data-rating="' . $rating . '" data-max-rating="' . intval($options['rating_max']) . '"></div></div>';

					if ($options['category_select'])
						echo '<div class="field"><label>' . __('Category', 'rate-star-review') . '</label>' . wp_dropdown_categories('echo=0&name=reviewCategory' . '&hide_empty=0&class=ui+dropdown&selected=' . $category ).'</div>';

					echo '<div class="field"><label>' . __('Title', 'rate-star-review') . '</label><input type="text" id="reviewTitle" value="' . esc_attr( $post->post_title ). '"></div>';
					echo '<div class="field"><label>' . __('Review', 'rate-star-review') . '</label><textarea id="reviewContent" rows="2">' . esc_textarea( $post->post_content ) . '</textarea></div>';
					echo '<button class="ui button" type="submit" onclick="loadContent' . $id . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Updating Review', 'rate-star-review') . '...</div>\', formVars(\'&form=update&rating_id=' . $post->ID . '\'))">' . __('Update Review', 'rate-star-review') . '</button></div>';
				}
			else //add my review
				{

				$category = 0;
				if ($post_id) //match post category as default
					{
					$cats = wp_get_post_categories( $post_id);
					if (count($cats)) $category = intval(array_pop($cats));
				}

				echo '<div class="ui form">';
				echo '<div class="field"><label>' . __('Rating', 'rate-star-review') . '</label><div id="rating" class="ui yellow massive star rating active" data-rating="3" data-max-rating="' . intval($options['rating_max']) . '"></div></div>';

				if ($options['category_select'])
					echo '<div class="field"><label>' . __('Category', 'rate-star-review') . '</label>' . wp_dropdown_categories('echo=0&name=reviewCategory' . '&hide_empty=0&class=ui+dropdown&selected=' . $category).'</div>';

				echo '<div class="field"><label>' . __('Title', 'rate-star-review') . '</label><input type="text" placeholder="Review Heading" id="reviewTitle" value=""></div>';
				echo '<div class="field"><label>' . __('Review', 'rate-star-review') . '</label><textarea id="reviewContent" rows="2"></textarea></div>';
				echo '<button class="ui button" type="submit" onclick="loadContent' . $id . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __('Saving Review', 'rate-star-review') . '...</div>\', formVars(\'&form=insert\'))">' . __('Add Review', 'rate-star-review') . '</button></div>';
			}


			echo '<script>
			function formVars(params)
			{
			var vars = params;
			vars = vars + \'&rating=\' + jQuery(\'#rating\').rating(\'get rating\');
			vars = vars + \'&title=\' + encodeURIComponent(jQuery(\'#reviewTitle\').val());
			vars = vars + \'&content=\' + encodeURIComponent(jQuery(\'#reviewContent\').val());
			vars = vars + \'&category=\' + encodeURIComponent(jQuery(\'#reviewCategory\').val());
			return vars;
			}
			</script>';


			die();
		}


		static function videowhisper_reviews($atts)
		{
			$options = VWrateStarReview::getOptions();

			if (is_single()) $postID = get_the_ID(); //is on a post page
			else $postID = 0;

			//shortocode attributes
			$atts = shortcode_atts(
				array(
					'content_type'=> '', //content reviewed: post type, session
					'content_id'=> '', //id of content reviewed (session, post)
					'post_id'=> $postID, //associated with (to display for)
					'show_average'=> '1', //show average rating if post_id available
					'id' => ''
				), $atts, 'videowhisper_reviews');

			if (!$atts['id']) $id = 'Reviews';
			else $id = 'Reviews' . $atts['id'];

			if (!$atts['content_id'] && !$atts['post_id']) return 'At least one required: content_id or post_id!';

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwrsr_reviews&content_id=' . urlencode($atts['content_id']) . '&content_type=' . urlencode($atts['content_type']) . '&post_id=' . urlencode($atts['post_id']) . '&show_average=' . urlencode($atts['show_average']) . '&id=' . urlencode($id);


			VWrateStarReview::enqueueScripts();

			$loadingMessage = '<div class="ui active inline text large loader">' . __('Loading Reviews','rate-star-review') . '...</div>';

			$htmlCode = <<<HTMLCODE
<script>
var aurl$id = '$ajaxurl';
var loader$id;

	function loadContent$id(message = '', vars = ''){

	if (message)
	if (message.length > 0)
	{
	  jQuery("#videowhisperContainer$id").html(message);
	}

		if (loader$id) loader$id.abort();

		loader$id = jQuery.ajax({
			url: aurl$id,
			data: "interfaceid=$id" + vars,
			success: function(data) {
				jQuery("#videowhisperContainer$id").html(data);
				//jQuery('.ui.rating').rating();
				jQuery('.ui.rating.readonly').rating('disable');
			}
		});
	}

	jQuery(document).ready(function(){
		loadContent$id();
	});

</script>

<div id="videowhisperContainer$id" class="videowhisperContainer ui container">
    $loadingMessage
</div>

HTMLCODE;

			return $htmlCode;

		}

		static function vwrsr_reviews()
		{
			$options = VWrateStarReview::getOptions();

			$id = sanitize_file_name($_GET['id']); //used in JS function naming, may be used in file form caching

			//output clean (clear 0)
			ob_clean();

			$post_id = intval($_GET['post_id']);
			$show_average = intval($_GET['show_average']);

			$content_type = sanitize_text_field($_GET['content_type']);
			$content_id = intval($_GET['content_id']);

			//check if already reviewed
			$args = array(
				'post_type'    => $options['custom_post'], //review

				'meta_query' => array(
					'relation'    => 'AND'
				),

			);

			if ($post_id) $args['meta_query']['post_id'] = array('key'     => 'post_id', 'value' => $post_id);
			if ($content_id) $args['meta_query']['content_id'] = array('key'     => 'content_id', 'value' => $content_id);
			if ($content_type) $args['meta_query']['content_type'] = array('key'     => 'content_type', 'value' => $content_type);

			if ($post_id>0 && $show_average) echo  do_shortcode('[videowhisper_ratings post_id="' . $post_id . '"]')  . '</div>';

			$postslist = get_posts($args);

			if (count($postslist))
			{
				echo '<div class="ui four stackable cards">';

				foreach ( $postslist as $item )
				{
					
					$post = get_post($item);

					echo self::reviewCard($post, $options['rating_max']);
					

				}
				echo '</div>';
			}
			else
			{
				echo  'No reviews, yet.';
			}

			//get votes list
			$votes = get_post_meta($post_id, 'rateStarReview_votes', true);
			if (!$votes || !is_array($votes)) $votes = array();


			//implode last 5 entries from $votes array
			$lastVotes = array_slice($votes, -5, 5);
			$lastVotesList = implode(', ', $lastVotes);
	
			$voteCount = count($votes);
			if ($voteCount>5) $lastVotesList .= ' ...';

			if ($voteCount) echo '<br/><div class="ui label"><i class="thumbs up icon outline"></i>' . $voteCount . ' '.__("Votes").'</div> <small> '. $lastVotesList . '</small><br/>';

			die();
		}

		static function settings_link($links) {
			$settings_link = '<a href="admin.php?page=rate-star-review">'.__("Settings").'</a>';
			array_unshift($links, $settings_link);
			return $links;
		}


		//! Register Custom Post Type
		static function review_post() {
			$options = VWrateStarReview::getOptions();

			//only if missing
			if (post_type_exists($options['custom_post'])) return;

			$labels = array(
				'name'                => _x( 'Reviews', 'Post Type General Name', 'rate-star-review' ),
				'singular_name'       => _x( 'Review', 'Post Type Singular Name', 'rate-star-review' ),
				'menu_name'           => __( 'Reviews', 'rate-star-review' ),
				'parent_item_colon'   => __( 'Parent Review:', 'rate-star-review' ),
				'all_items'           => __( 'All Reviews', 'rate-star-review' ),
				'view_item'           => __( 'View Review', 'rate-star-review' ),
				'add_new_item'        => __( 'Add New Review', 'rate-star-review' ),
				'add_new'             => __( 'New Review', 'rate-star-review' ),
				'edit_item'           => __( 'Edit Review', 'rate-star-review' ),
				'update_item'         => __( 'Update Review', 'rate-star-review' ),
				'search_items'        => __( 'Search Reviews', 'rate-star-review' ),
				'not_found'           => __( 'No Reviews found', 'rate-star-review' ),
				'not_found_in_trash'  => __( 'No Reviews found in Trash', 'rate-star-review' ),
			);

			$args = array(
				'label'               => __( 'Review', 'rate-star-review' ),
				'description'         => __( 'Browse Reviews', 'rate-star-review' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'editor', 'author', 'comments', 'custom-fields', 'page-attributes', ),
				'taxonomies'          => array( 'category', 'post_tag' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'can_export'          => true,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'map_meta_cap'        => true,
				'menu_icon' => 'dashicons-star-half',
				'capability_type'     => 'post',
				'capabilities' => array(
					'create_posts' => false
				)
			);
			register_post_type( $options['custom_post'], $args );


		}

		//! Settings

		static function getOptions()
		{
			$options = get_option('VWrateStarReview');
			if (!$options)  return VWrateStarReview::adminOptionsDefault();
			if (empty($options))  return VWrateStarReview::adminOptionsDefault();
			return $options;
		}

		static function adminOptionsDefault()
		{
			$root_url = get_bloginfo( "url" ) . "/";
			return array(
				'micropayments' => 1,
				'micropaymentsVote' => 1.00,
				'micropaymentsRatio' => 0.80,
				'paid_categories' => 'Uncategorized,Test',
				'retractVote' => 1,

				'vote_posts' => 'post, webcam, room, channel, video, picture, article, forum',
				'voting_categories' => 'Test',
				'review_posts' => 'post',
				'custom_post' => 'review',
				'category_select' =>1,
				'rating_max' => 5,
				'videowhisper' => 0
			);
		}

		static function categoryIDs($csv)
		{
			$ids = array();
			if (!$csv) return $ids;

			$categories = explode(',', $csv);
			foreach ($categories as $category)
			{
				$cat = get_term_by('name', trim($category), 'category');
				if ($cat) $ids[] = $cat->term_id;
			}
			return $ids;
		}

	function admin_bar_menu($wp_admin_bar)
		{
			if (!is_user_logged_in()) return;

			$options = self::getOptions();

			if( current_user_can('editor') || current_user_can('administrator') ) {

				//find VideoWhisper menu
				$nodes = $wp_admin_bar->get_nodes();
				if (!$nodes) $nodes = array();
				$found = 0;
				foreach ( $nodes as $node ) if ($node->title == 'VideoWhisper') $found = 1;

					if (!$found)
					{
						$wp_admin_bar->add_node( array(
								'id'     => 'videowhisper',
								'title' => '👁️ VideoWhisper',
								'href'  => admin_url('plugin-install.php?s=videowhisper&tab=search&type=term'),
							) );

						//more VideoWhisper menus

						$wp_admin_bar->add_node( array(
								'parent' => 'videowhisper',
								'id'     => 'videowhisper-add',
								'title' => __('Add Plugins', 'rate-star-review'),
								'href'  => admin_url('plugin-install.php?s=videowhisper&tab=search&type=term'),
							) );

						$wp_admin_bar->add_node( array(
								'parent' => 'videowhisper',
								'id'     => 'videowhisper-contact',
								'title' => __('Contact Support', 'rate-star-review'),
								'href'  => 'https://consult.videowhisper.com/?topic=WordPress+Plugins+' . urlencode($_SERVER['HTTP_HOST']),
							) );
					}


				$menu_id = 'videowhisper-ratestarreview';

				$wp_admin_bar->add_node( array(
						'parent' => 'videowhisper',
						'id'     => $menu_id,
						'title' => '⭐️ Rate Star Review',
						'href'  => admin_url('admin.php?page=rate-star-review')
					) );

						$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-settings',
						'title' => __('Settings', 'rate-star-review'),
						'href'  => admin_url('admin.php?page=rate-star-review')
					) );
		
		
					$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-apf',
						'title' => __('APF Optimizer', 'rate-star-review'),
						'href'  => admin_url('admin.php?page=rate-star-review-apf')
					) );
	
					$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-doc',
						'title' => __('Documentation', 'rate-star-review'),
						'href'  => admin_url('admin.php?page=rate-star-review-doc')
					) );

				$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-wpdiscuss',
						'title' => __('Discuss WP Plugin', 'rate-star-review'),
						'href'  => 'https://wordpress.org/support/plugin/rate-star-review/'
					) );
					
				$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-wpreview',
						'title' => __('Review WP Plugin', 'rate-star-review'),
						'href'  => 'https://wordpress.org/support/plugin/rate-star-review/reviews/#new-post'
					) );
													
				$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-vsv',
						'title' => __('Video Hosting', 'rate-star-review'),
						'href'  => 'https://videosharevod.com/hosting/'
					) );
					
				$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-webrtc',
						'title' => __('Live Stream Hosting', 'rate-star-review'),
						'href'  => 'https://webrtchost.com/hosting-plans/'
					) );

				$wp_admin_bar->add_node( array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-turnkey',
						'title' => __('Full Feature Plans', 'rate-star-review'),
						'href'  => 'https://paidvideochat.com/order/'
					) );
			}



		}


		static function admin_menu() {

			$options = VWrateStarReview::getOptions();

			add_menu_page('Rate Star Review', 'Rate Star Review', 'manage_options', 'rate-star-review', array('VWrateStarReview', 'optionsPage'), 'dashicons-star-half',83);
			add_submenu_page("rate-star-review", "Settings", "Settings", 'manage_options', "rate-star-review", array('VWrateStarReview', 'optionsPage'));
			add_submenu_page( 'rate-star-review', 'APF Optimizer', 'APF Optimizer', 'manage_options', 'rate-star-review-apf', array( 'VWrateStarReview', 'adminAPF' ) );
			add_submenu_page("rate-star-review", "Documentation", "Documentation", 'manage_options', "rate-star-review-doc", array('VWrateStarReview', 'docsPage'));
		}


		static function docsPage()
		{
?>


<div class="wrap">
<h2>Documentation: Star Rate Review - Review Content with Star Ratings,  by VideoWhisper.com</h2>

Any type of content (posts, pages, custom posts) can be reviewed with this review system and reviews can be listed as necessary.
You can configure this plugin from <a href="admin.php?page=rate-star-review">Settings</a>.

<h3>Links</h3>
<UL>
<LI><a href="https://consult.videowhisper.com/">Contact Developers: Support or Custom Development</a></LI>
<LI><a href="https://wordpress.org/plugins/rate-star-review/">WordPress Plugin Page</a></LI>
<LI><a href="https://wordpress.org/support/plugin/rate-star-review/">Plugin Forum: Discuss with other users</a></LI>
<LI><a href="https://wordpress.org/support/plugin/rate-star-review/reviews/#new-post">Review this Plugin</a></LI>
</UL>

Plugin can work with various custom post types. Some plugins manage ratings from own integration settings: <a href="https://videosharevod.com">VideoShareVOD</a> (video), <a href="https://paidvideochat.com">PaidVideochat</a> (webcam), <a href="https://broadcastlivevideo.com">BroadcastLiveVideo</a> (channel), <a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> (picture).
 
<h3>Shortcodes</h3>

<h4>[videowhisper_review post_id="" content_type="" content_id="" rating_max="5" id="" update_id=""]</h4>
Shows form to add and update review for specific post and content. AJAX based. Can also update reviews list if on same page.
<br>content_type = content type or aspect reviewed, ex: post, page, session, part, chapter, aspects (ex: Readability, Performance, Value, Design, Features) [string]
<br>content_id = id of content to use in combination with content type if necessary (ex: post id, session id, part number, chapter) [integer]
<br>post_id = id of post (if associated to a post, page or other custom post type) [integer]
<br>rating_max = maximum number of stars [integer]
<br>id = form id
<br>update_id = id of list to update

<h4>[videowhisper_reviews post_id="" show_average="1" content_type="" content_id="" id=""]</h4>
Lists reviews for specific content (by post,content). At least post_id or content_id must be specified. AJAX based.
<br>show_average = show rating average if post_id available, set 0 or blank to disable
<br>id = list id

<h4>[videowhisper_rating post_id="" rating_max="5" category="]</h4>
Displays average rating for a post (average of all ratings for that post). If no category is specified (as category id) overall rating will be shown. Static (not AJAX).

<h4>[videowhisper_ratings post_id="" rating_max="5" "]</h4>
Displays average ratings, by category. Static (not AJAX).

<h4>[videowhisper_review_featured post_id="" rating_max="5" "]</h4>
Displays a featured review card for that content (a review with top available rating). Static (not AJAX).

<h4>[videowhisper_vote post_id=""]
Enables users to vote for a post.

<h4>How to use this?</h4>
In example, if you have a post presenting an electronic product and want site members to be able to review and rate separately different aspects like Features and Performance these can be content types.
<BR>A review form for each content type can be setup: [videowhisper_review content_type="Features" post_id="1"] [videowhisper_review content_type="Performance" post_id="1"].
<BR>Then to show all reviews for that item, you can use [videowhisper_reviews post_id="1"] .
<BR>Another example, if an article is about a book with 2 parts, you can also use content_id to allow users to post a review for each part for each aspect.
</div>
<?php
		}

		static function setupOptions()
		{

			$adminOptions = VWrateStarReview::adminOptionsDefault();

			$options = get_option('VWrateStarReview');
			if (!empty($options)) {
				foreach ($options as $key => $option)
					$adminOptions[$key] = $option;
			}
			update_option('VWrateStarReview', $adminOptions);


			return $adminOptions;
		}

		static function optionsPage()
		{
			$options = VWrateStarReview::setupOptions();
			$optionsDefault = VWrateStarReview::adminOptionsDefault();

				if (isset($_POST)) if (!empty($_POST))
				{

				$nonce = $_REQUEST['_wpnonce'];
				if ( ! wp_verify_nonce( $nonce, 'vwsec' ) )
				{
					echo 'Invalid nonce!';
					exit;
				}
				
	
				foreach ($options as $key => $value)
					if (isset($_POST[$key])) $options[$key] = sanitize_textarea_field( $_POST[$key] );

					update_option('VWrateStarReview', $options);
			}

?>
<div class="wrap">
<h2>Settings : Star Rate Review - Review Content with Star Ratings, by VideoWhisper.com</h2>

For more details about using this plugin see <a href="admin.php?page=rate-star-review-doc">Documentation</a>.

<form method="post" action="<?php echo wp_nonce_url($_SERVER["REQUEST_URI"], 'vwsec'); ?>">

<h4>Review Post Types</h4>
<input name="review_posts" type="text" id="review_posts" size="50" maxlength="200" value="<?php echo esc_attr( strtolower($options['review_posts'] ) ) ?>"/>
<br>Post types that can be reviewed as comma separated values. Ex: post, page Default: <?php echo esc_attr( strtolower($optionsDefault['review_posts'] ) ) ?>
<br>Should automatically show review AJAX box and previous reviews after content, on the post page (not on archive pages with multiple items).
<br>Warning: Some plugins manage this from own integration settings: <a href="https://videosharevod.com">VideoShareVOD</a> (video), <a href="https://paidvideochat.com">PaidVideochat</a> (webcam), <a href="https://broadcastlivevideo.com">BroadcastLiveVideo</a> (channel), <a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> (picture) and their posts don't need to be configured here.


<h4>Maximum Stars Rating</h4>
<input name="rating_max" type="text" id="rating_max" size="12" maxlength="32" value="<?php echo esc_attr( strtolower($options['rating_max'] ) ) ?>"/>
<br>Default maximum rating. Ex: 5 Default: <?php echo esc_attr( strtolower($optionsDefault['rating_max'] ) )?>
<BR>Setup before using. Changing this will not change value of previous ratings: switching from 5 to 10 stars will leave a previous 4/5 stars reviews as 4/5.

<h4>Review Post Name</h4>
<input name="custom_post" type="text" id="custom_post" size="12" maxlength="32" value="<?php echo esc_attr( strtolower($options['custom_post'] ) ) ?>"/>
<br>Custom post name for reviews (only alphanumeric, lower case). Will be used for review urls. Ex: review Default: <?php echo esc_attr(strtolower($optionsDefault['custom_post']))?>
<br>Recommended: Do not change unless that custom post type is already in use.

<h4>Category Select</h4>
<select name="category_select" id="category_select">
  <option value="1" <?php echo $options['category_select']?"selected":""?>>Yes</option>
  <option value="0" <?php echo $options['category_select']?"":"selected"?>>No</option>
</select>
<br>User selects a category for rating.

<h4>Vote Post Types</h4>
<input name="vote_posts" type="text" id="vote_posts" size="50" maxlength="200" value="<?php echo esc_attr( strtolower($options['vote_posts'] ) ) ?>"/>
<br>Post types where to show vote count and button, if enabled for post category (see setting below), with shortcode [videowhisper_vote] 
<br>Ex: post, page, video, picture Default: <?php echo esc_attr( strtolower($optionsDefault['vote_posts'] ) ) ?>

<h4>Voting Categories</h4>
<input name="voting_categories" type="text" id="voting_categories" size="50" maxlength="200" value="<?php echo esc_attr( $options['voting_categories'] ) ?>"/> 
<br>Name of categories to enable voting for, as comma separated values.  Ex: Uncategorized, Test
<?php

if ($options['voting_categories']) echo '<br>Category IDs for "' . esc_html($options['voting_categories']) . '": ' . json_encode( self::categoryIDs($options['voting_categories']) );
?>
<h3>MicroPayments</h3>
MicroPayments integration enables paid votes (users get charged to vote).

<br>MicroPayments plugin:
				<?php
				if (!class_exists( 'VWpaidMembership' )) echo 'If you want to use this functionality, <a href="plugin-install.php?s=videowhisper+micropayments&tab=search&type=term">install and activate the MicroPayments plugin</a>.';
				else echo 'Detected.';
				?>

				<h4>Enable MicroPayments Integration</h4>
				<select name="micropayments" id="micropayments">
				  <option value="0" <?php echo !$options['micropayments'] ? 'selected' : ''; ?>>Disabled</option>
				  <option value="1" <?php echo $options['micropayments'] == '1' ? 'selected' : ''; ?>>Enabled</option>
				</select>
				<br>Enables paid votes, per settings below.

				<h4>Vote Cost</h4>
				<input name="micropaymentsVote" type="text" id="micropaymentsVote" size="10" maxlength="10" value="<?php echo esc_attr( $options['micropaymentsVote'] ); ?>"/>
				<BR>Cost to vote an item.

				<h4>Earning Ratio</h4>
				<input name="micropaymentsRatio" type="text" id="micropaymentsRatio" size="10" maxlength="10" value="<?php echo esc_attr( $options['micropaymentsRatio'] ); ?>"/>
				<BR>How much of cost is received by content owner. In example a ratio of 0.8 means content owner gets 80% and 20% remains to website.


<h4>Paid Categories</h4>
<input name="paid_categories" type="text" id="paid_categories" size="50" maxlength="200" value="<?php echo esc_attr( $options['paid_categories'] ) ?>"/> 
<br>Configure paid voting only for popular categories. Name of categories where to charge for voting, as comma separated values.  Ex: Uncategorized, Test
<?php

if ($options['paid_categories']) echo '<br>Category IDs for "' . esc_html($options['paid_categories']) . '": ' . json_encode( self::categoryIDs($options['paid_categories']) );
?>

<h4>Retract Vote</h4>
<select name="retractVote" id="retractVote">
  <option value="0" <?php echo $options['retractVote']?"":"selected"?>>No</option>
  <option value="1" <?php echo $options['retractVote']=='1'?"selected":""?>>Free Votes</option>
  <option value="2" <?php echo $options['retractVote']=='2'?"selected":""?>>All Votes</option>
</select>
<br>Allow users to retract their vote. Retracting does NOT refund payment for paid votes.
<br>Recommended: Allow retraction of Free Votes only, to prevent multiple payments when voting same item repeatedly.

<?php
			submit_button();

			echo '</form></div>';

	}



	static function adminAPF()
	{
		$thisAPFversion = '2023.08.27c'; //should match that in apf/allowed-plugins-filter.php
		$options = self::getOptions();
		
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>APF Optimizer: Configure Allowed Plugins Filter</h2>
</div>
Allowed Plugins Filter Optimizer is a <a href="https://wordpress.org/documentation/article/must-use-plugins">must use plugin</a> that allows website admin to control what plugins are active for specific requests, to reduce server load, improve security, increase setup scalability.
<br>Warning: This implements advanced functionality that should be carefully configured and tested because it can remove or break features, depending on each website.
<?php
$installed = 0;
if (defined('VIDEOWHISPER_APF_VERSION'))
{
	echo '<br>Detected APF Version: ' . VIDEOWHISPER_APF_VERSION;
	$installed = VIDEOWHISPER_APF_VERSION;
}
else echo '<br>APF not detected. Save Changes to install and activate!';
?>

<?php
$optionsAPF = get_option( 'videowhisper_apf_ajax' );

if ( isset( $_POST ) )
{
	if ( ! empty( $_POST ) )
	{

		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'vwsec' ) )
		{
			echo 'Invalid nonce!';
			exit;
		}

		if (isset($_POST['videowhisper_apf_install']))
		{
			$install = intval( $_POST['videowhisper_apf_install'] );

			if (!$install && $videowhisperAPFversion)
			{
				echo '<div>Removing APF...</div>';

				//remove from wp-content/mu-plugins 
				if (file_exists(WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php'))
				unlink ( WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php' );
				else echo '<div>APF file not found in mu-plugins folder: ' . WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php' . ' </div>';
			}

			if ($install && $thisAPFversion > $installed )
			{
				echo '<div>Installing latest APF. Reload to detect...</div>';

				//create mu-plugins folder if missing
				if (!file_exists(WP_CONTENT_DIR . '/mu-plugins')) mkdir ( WP_CONTENT_DIR . '/mu-plugins' );

				//copy from apf folder in plugin folder
				if (file_exists(WP_PLUGIN_DIR  . '/rate-star-review/apf/allowed-plugins-filter.code'))
				copy ( WP_PLUGIN_DIR  . '/rate-star-review/apf/allowed-plugins-filter.code', WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php' );
				else echo '<div>APF file not found in plugin folder: ' . WP_PLUGIN_DIR  . '/rate-star-review/apf/allowed-plugins-filter.code' . ' </div>';

				if (file_exists(WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php')) 
				{
					$installed = $thisAPFversion;
					echo '<div>APF file detected at expected location: ' . WP_CONTENT_DIR . '/mu-plugins/allowed-plugins-filter.php'  . ' </div>';
				}
			}
		}

		foreach (['vwrsr_vote', 'vwrsr_review', 'vwrsr_reviews'] as $action) 
		if (isset($_POST['videowhisper_apf_ajax_' . $action]) && !empty($_POST['videowhisper_apf_ajax_' . $action]) ) 
		{
			$csv = sanitize_text_field( $_POST['videowhisper_apf_ajax_' . $action] );
			$items = explode( ',', $csv );

			foreach ( $items as $key => $value )
			{
				$items[ $key ] = trim( $value );
			}

			//this plugin is always required
			$optionsAPF[$action] = $items;
			if (!in_array('rate-star-review', $optionsAPF[$action])) $optionsAPF[$action][] = 'rate-star-review';

		} else
		{
			$optionsAPF[$action] = ['rate-star-review'];
		}

		update_option( 'videowhisper_apf_ajax', $optionsAPF );
	}
}
?>
<form method="post" action="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'vwsec' ); ?>">

<h3>Use APF</h3>
<select name="videowhisper_apf_install">
	<option value="0">Do Not Install / Remove</option>
	<option value="1" <?php if ($installed) echo 'selected'; ?>>Install / Update / Keep</option>
</select>
<?php
echo '<br>Detected APF Version: ' . $installed;
echo '<br>Local APF Version: ' . $thisAPFversion . ' ' . ( $thisAPFversion > $installed ? 'Save to Update!' : '' );
?>

<h3>AJAX Actions</h3>
Configure plugings allowed to load on specific AJAX actions. This can be used to reduce server load, improve security, increase setup scalability. Check for errors or missing features before using in a production environment. Active plugins should only include those that are required for this action to work, like billing wallets, integrated features, notifications. As a rule of thumb no new plugins should be added to the lists unless they are somehow required by integrated plugin addons.

<h4>Votes (Likes) Button / AJAX action: vwrsr_vote</h4>
This action is used for vote (like) counter and button. Should include plugins powering integrated features like transactions if paid votes are enabled.</br>
<?php
$pluginList = '';
if ($optionsAPF) if (isset($optionsAPF['vwrsr_vote'])) foreach ($optionsAPF['vwrsr_vote'] as $plugin) $pluginList .= ($pluginList ? ', ' : '' ) . $plugin;

if (!$pluginList) $pluginList = 'paid-membership, woocommerce, woo-wallet, mycred';
?>
			<textarea name="videowhisper_apf_ajax_vwrsr_vote" cols="80" rows="3"><?php echo esc_attr( $pluginList ); ?></textarea>
			<br>Comma separated list of plugin names (folder name in wp-content/plugins if standard like plugin/plugin.php) or complete plugin filename. Only installed & enabled plugins that are also in this list should be loaded in this type of requests.
			<br>Suggested: paid-membership, woocommerce, woo-wallet, mycred

<h4>Review / AJAX action: vwrsr_review</h4>
This action is used for AJAX requests to review.</br>
<?php
$pluginList = '';
if ($optionsAPF) if (isset($optionsAPF['vwrsr_review'])) foreach ($optionsAPF['vwrsr_review'] as $plugin) $pluginList .= ($pluginList ? ', ' : '' ) . $plugin;

if (!$pluginList) $pluginList = '';
?>

			<textarea name="videowhisper_apf_ajax_vwrsr_review" cols="80" rows="3"><?php echo esc_attr( $pluginList ); ?></textarea>
			<br>Comma separated list of plugin names (folder name in wp-content/plugins if standard like plugin/plugin.php) or full path. Only installed & enabled plugins that are also in this list should be loaded in this type of requests.
			<br>Suggested: none

<h4>Reviews List / AJAX action: vwrsr_reviews</h4>
This action is used for AJAX requests to review.</br>
<?php
$pluginList = '';
if ($optionsAPF) if (isset($optionsAPF['vwrsr_reviews'])) foreach ($optionsAPF['vwrsr_reviews'] as $plugin) $pluginList .= ($pluginList ? ', ' : '' ) . $plugin;

if (!$pluginList) $pluginList = '';
?>

			<textarea name="videowhisper_apf_ajax_vwrsr_reviews" cols="80" rows="3"><?php echo esc_attr( $pluginList ); ?></textarea>
			<br>Comma separated list of plugin names (folder name in wp-content/plugins if standard like plugin/plugin.php) or full path. Only installed & enabled plugins that are also in this list should be loaded in this type of requests.
			<br>Suggested: none

<?php
submit_button();
echo '</form><br>All APF AJAX options:<pre>';
var_dump($optionsAPF);
echo '</pre>';
}

}
	

}

//instantiate
if (class_exists("VWrateStarReview")) {
	$rateStarReview = new VWrateStarReview();
}

//Actions and Filters
if (isset($rateStarReview)) {

	register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	register_activation_hook( __FILE__, array(&$rateStarReview, 'install' ) );

	add_action( 'init', array(&$rateStarReview, 'init'));

	add_action("plugins_loaded", array(&$rateStarReview, 'plugins_loaded'));
	add_action('admin_menu', array(&$rateStarReview, 'admin_menu'));
	add_action( 'admin_bar_menu', array(&$rateStarReview, 'admin_bar_menu'),100 );

}

?>