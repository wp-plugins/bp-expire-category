<?php
/**
 * Plugin Name: BP Expire Category
 * Plugin URI:  http://beyond-paper.com/sandbox/wordpress/themetester/bp-expire-category/
 * Description: This plugin allows users to add a category with an expiration date to posts.
 * Version: 0.1
 * Author: Diane Ensey
 * Author URI: http://beyond-paper.com
 * License: GPL2
**/

/*  Copyright 2015 Diane Ensey (email: diane@beyond-paper.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined( 'ABSPATH' ) or die( 'No direct access permitted!' );

register_activation_hook( __FILE__, 'bp_expire_category_install' ); //registers a plugin function to run when the plugin is activated
register_uninstall_hook( __FILE__, 'bp_expire_category_uninstall' ); //deletes table 

//Actions
add_action( 'add_meta_boxes', 'bp_expire_category_add_meta_box' );
add_action( 'init', 'bp_expire_category_expire' );
add_action('wp_ajax_bp_expire_category_save', 'bp_expire_category_save');

// Check/Set that there is a cron job
if ( ! wp_next_scheduled( 'bp_expire_category_expire' ) ) {
  wp_schedule_event( time(), 'hourly', 'bp_expire_category_expire' );
}
add_action( 'expire_category_check', 'expire_category_expire' );

//Create a table to hold the term_id, post_id and expiration date
function bp_expire_category_install() {    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    global $wpdb;
    global $charset_collate;

    $table_name = $wpdb->prefix . 'expire_category';

    $sql = "CREATE TABLE $table_name (
        id int(11) unsigned NOT NULL AUTO_INCREMENT,
		post_id int(11) unsigned NOT NULL,
        term_taxonomy_id int(11) unsigned NOT NULL,
        expiration_date date NOT NULL DEFAULT '0000-00-00',
        UNIQUE KEY id (id)
        ) $charset_collate;";    

    dbDelta( $sql );
}
//Delete the table on uninstallation
function bp_expire_category_uninstall(){
    global $wpdb;
	$wpdb->prepare("DROP TABLE IF EXISTS ".$wpdb->prefix . 'expire_category');	
}

//function that removed expired cat from posts
function bp_expire_category_expire() {
    global $wpdb; 
			
    $date = date("Y-m-d");
    $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."expire_category WHERE expiration_date <= %s", $date));

    if ($posts){  
        foreach($posts as $post) {  
			$wpdb->delete( $wpdb->prefix."term_relationships", array( 'object_id' => $post->post_id, 'term_taxonomy_id'=> $post->term_taxonomy_id ) );   
			//delete the relationship table item  
			$wpdb->delete( $wpdb->prefix."expire_category", array( 'post_id' => $post->post_id ) );
		}
    }
}

//Create the function to add the metabox to the post page
function bp_expire_category_add_meta_box(){
	//$screens = array( 'post','page' );
	//foreach ( $screens as $screen ) {
		add_meta_box(
			'bp-expire-cat',
			'Add Expiring Category',
			'bp_expire_category_callback',
			'post',
			'side'
		);
	//}
}

//Create a box for the Post edit page to collect the category and date info
function bp_expire_category_callback($post){
	global $wpdb;
	global $post;
	//datepicker includes
	$calicon = plugins_url('assets/calendar.gif', __FILE__ );
	wp_register_script('bp_expire_category_js', plugins_url( '/js/bp_expire_category.js', __FILE__ ),array('jquery','jquery-ui-datepicker') );
    wp_enqueue_script('bp_expire_category_js' );
    wp_enqueue_style( 'jquery-style', plugins_url( '/css/jquery-ui.min.css', __FILE__ ) );
    wp_enqueue_style( 'bp-style', plugins_url( '/css/style.css', __FILE__ ) );
	wp_localize_script( 'bp_expire_category_js', 'bpCalImgURL', array('url'=>plugins_url( '/assets/1430785968_schedule.png', __FILE__ )) );

	
	//find the current expiring category, if any
	$current = $wpdb->get_results($wpdb->prepare("SELECT * FROM  ".$wpdb->prefix."expire_category WHERE post_id = %d LIMIT 1", $post->ID ));
	if($current){$current = $current[0];}
	$args = array();
	$id = 0;
	if($current){
		$args = array('selected'=>$current->term_taxonomy_id);
		$id = $current->id;
	}
	?>
    <div class="inside">
        <div id="bp-taxonomy-category" class="categorydiv">
        	<p>Category: <?php wp_dropdown_categories( $args ); ?> </p>
            <p> Expire On: <input type="text" maxlength="15" class="datepicker" id="bp_expiration_date" name="bp_expiration_date" value="<?php if($current){echo $current->expiration_date;} ?> "></p>
           <input type="hidden"  name="id" id="bp_expire_category_id" value="<?php echo $id ?>" />
           <input type="hidden"  name="bp_post_id" id="bp_post_id" value="<?php echo $post->ID ?>" />
           <div id="bp-expire-category-box"> <span id="category-ajax-response"></span>
          <input type="button" name="bp_expire_cat_submit" id="bp_expire_cat_submit" value="OK" />	
          </div>
		
        </div>
    </div>
    
    <?php
	
}

function bp_expire_category_save() {
    global $wpdb;      
    $table = $wpdb->prefix."expire_category";
	$status = array();
    if ($_POST) {
		$term_id = bp_filter('id',$_POST['term_id']); //allow only num
		$id =  bp_filter('id',$_POST['old_id']);//allow only num
		$date = bp_filter('date',$_POST['date']); //allow only xxxx-xx-xx
		$post_id =  bp_filter('id',$_POST['post_id']); //allow only num
		if($term_id == NULL || $id == NULL || $date == NULL || $post_id == NULL){
			//if any of the fields are NULL, bad info was submitted.
			$status['result'] = 'Error';
			$status['error'] = 'Bad data received';
		}else{
			//get the taxonomy_term_id
			$tti = $wpdb->get_results ($wpdb->prepare(
							"SELECT term_taxonomy_id 
							FROM  ".$wpdb->prefix."term_taxonomy WHERE term_id = %d
							LIMIT 1",
							$term_id
							));
			//if($tti){$term_taxonomy_id = $tti[0]['term_taxonomy_id'];	}
			$tti = $tti[0];
			$term_taxonomy_id = $tti->term_taxonomy_id;
	
			$data = array(
			'post_id' => $post_id,
			'term_taxonomy_id' => $term_taxonomy_id, 
			'expiration_date' => $date
			);
		
			$status['data'] = $data;
			$old_term_tax_id = NULL;
			
			//Adding/Updating the bp_expire_category table
			//check if the record is already there
			$exists = $wpdb->get_results ($wpdb->prepare(
							"SELECT * FROM  ".$wpdb->prefix."expire_category WHERE id = %d", $id));
		   if (!$exists) { 
				//adding it new                  
				$res = $wpdb->insert($table, $data);
				if($res){
					$id = $wpdb->insert_id;
					$status['result'] = "OK";
					$status['id'] = $id;
				}else{
					$status['result'] = "Error";
					$status['error'] = "1";	
				}
			} else {  
				$old_term_tax_id = $exists[0]->term_taxonomy_id;
				//updating the old                  
				$where = array ('id' => $id);
				$res = $wpdb->update($table, $data, $where);
				if($res){
					$status['id'] = $id;
					$status['result'] = "OK";
				}else{
					$status['result'] = "Error";
					$status['error'] = "2";	
				}
			}
			if($old_term_tax_id){
				//If there is an old relationship, delete it
				$wpdb->delete( $wpdb->prefix."term_relationships", array( 'object_id' => $post_id, 'term_taxonomy_id'=> $old_term_tax_id ) );   
			}
	
			//Adding the Post id and Taxomony Term ID to the Term_Relationships table
			$table = $wpdb->prefix."term_relationships";
			$data = array(
			'object_id' => $post_id,
			'term_taxonomy_id' => $term_taxonomy_id, 
			);
			
			//check if the pairing already exists
			$exists = $wpdb->get_results ($wpdb->prepare(
							"SELECT * FROM  ".$wpdb->prefix."term_relationships WHERE term_taxonomy_id = %d 
							AND object_id = %d", $term_taxonomy_id, $post_id
							));
			if(!$exists){
				//add it new
				$res = $wpdb->insert($table, $data);
				if($res){
					$status['result'] = "OK";
				}else{
					$status['result'] = "Error";
					$status['error'] = "3";	
				}
			}
			
			
		} 
	}
	$status = json_encode($status);
	echo $status;  
	wp_die();         
}        

function bp_filter($what, $data){
	$data = sanitize_text_field($data); //strip tags, line breaks, white space, octets, etc
	$resp = NULL;
	if($what == 'id'){
		is_numeric($data) ? $resp = $data : NULL;
	}
	if($what == 'date'){
		if(preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $data, $matches)){
	   		if(checkdate($matches[2], $matches[3], $matches[1])){ 
			 $resp = $data;
			}
	  	}
	}
	return $resp;
}
?>