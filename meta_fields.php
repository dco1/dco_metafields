<?php
/*
Plugin Name: Meta Fields
Description: Allows the easy handling of meta fields for posts, taxomoies, and users.
Author: Danny Cohen
Version: September 10, 2015
Author URI: http://dannycohen.design
*/

date_default_timezone_set('America/Los_Angeles');



/*
Here's how it works:
1. Create a new MetaGroup($name, $post_type);
2. The object array will be made from the format of "$slug_metafields". So you would create something like add_filter('$slug_metafields', 'function_name'); And then also add a function_name( $meta_array) { and add your objects here. } 
3. Uhhhh THAT'S IT BOYO!

** Here is what a MetaObject looks like being added to an array called $meta_array :
** $meta_array['meta_item_key']    = new MetaObject( 'meta_item_key' , 'type' , 'Title' , 'description', $defaults);


*/

// The MetaGroups

class MetaGroup{
	public $name, $objects;
	
	public function __construct( $name, $post_type){
		$this->name = $name;
		$this->post_type = $post_type;
		$this->slug = str_replace('-', '_', sanitize_title($name) ); //Replace the dashes with the title
		$this->objects = apply_filters( $this->slug.'_metafields', array() );
		add_action("add_meta_boxes", array(&$this, 'add_the_meta_box' ) ); // The action to add a meta box
		add_action("save_post", array(&$this, 'save_meta' ) ); // Save our meta data
		add_action("the_post", array(&$this, 'add_metadata_to_post_object' ) ); //Add the meta data to the post object when requested. Makes it far easier.
	}
	
	public function add_the_meta_box(){
		add_meta_box( $this->slug."_detail", $this->name , array(&$this, 'meta_callback' ), $this->post_type , "advanced", "high");
		do_action(  $this->slug."_add_meta_boxes" , $this);
	}
	
	public function meta_callback(){
		echo '<table class="form-table"><tbody>';
		wp_nonce_field( $this->slug .'_nonce_action', $this->slug .'_nonce_field' );
		do_action(  $this->slug."_before_meta_fields" , $this);
		echo display_post_meta_fields($this->objects); 
		do_action(  $this->slug."_after_meta_fields" , $this );
 		echo '</tbody></table>';
	}
	
	public function save_meta( $post_id ){
		if ( wp_is_post_revision( $post_id ) )  return; 
		if ( !isset( $_POST[  $this->slug .'_nonce_field' ] ) ) return;
		if ( !check_admin_referer( $this->slug .'_nonce_action', $this->slug .'_nonce_field' ) )  return;
		save_meta_fields_post($this->objects, $post_id, $_POST);
		do_action(  $this->slug."_after_saved_meta" , $this, $post_id );
	}
	
	public function add_metadata_to_post_object( $post ){
		if ( $post->post_type != $this->post_type ) return;
		foreach ( $this->objects as $key => $meta_field ){
			$post->$key = get_post_meta( $post->ID, $key, true);
		}
		do_action( $this->slug . '_the_post', $post ); // For doing additional things just for this portfolio setting
	}
}


// Meta Object

class MetaObject{
	public $key, $type, $title, $description;
	public $defaults = '';
	public $enabled = true;
	
	
    public function __construct( $name, $type, $title, $description = '', $defaults = '' ) {
    	$textdomain = 'dco_fest';
        $this->key = $name;
        $this->type = $type;
        $this->title =       __($title, $textdomain);
        $this->description = __($description, $textdomain);
        $this->defaults = $defaults;
        $this->value = '';
    }

    public function enabled( $boolean = true){
	    $this->enabled = $boolean;
    }
    
    public function field_form_input(){
	    global $custom;
		$output = '';
		$enabled = $this->enabled ? '' :  'readonly="readonly"';

		switch( $this->type ){ // check what the meta field type is and serve up the proper input
		    case "text": // Just normal text input
		        $output .= "<input type='text' class='text_input' name='$this->key' id='$this->key' placeholder='$this->defaults' value='".  esc_textarea($this->value) . "' $enabled />";
		        break;
		    case "textarea": // Just normal text input
		        $output .= "<textarea class='textarea_input' style='height:135px;' name='$this->key' id='$this->key' $enabled>".  esc_textarea( $this->value ) . "</textarea>";
		        break;
		    case "url": // Just a normal link input, and because we want it to be used for either email or url, we won't use type="email" or type="url", but we will give it some special placeholder. Now I'm confused.
		        $output .= "<input type='text' class='link_input' name='$this->key' id='$this->key' placeholder='http://example.com/'   value='".   esc_textarea($this->value) . "' $enabled />";
		        break;
		    case "date": // If it's a date, we've stored the date in the database as a date, so let's present it in the format that the date picker likes. Plus, we are going to make it a shorter text field because we don't need all that space
		        if ( empty ($this->value )) {$this->value = "" ; } else {$this->value = esc_textarea( date('Y-m-d' , $this->value ) ) ; } // If the custom field is empty, we just want to return nothing, because putting an empty string into the date() function throws a warning
		        $output .= "<input type='date' class='date_input' name='$this->key' id='$this->key' placeholder='' value='$this->value'  style='width:20%;' $enabled/>";
		        break;
		    case "datetime": // If it's a date, we've stored the date in the database as a date, so let's present it in the format that the date picker likes. Plus, we are going to make it a shorter text field because we don't need all that space
		        $thisdate = ''; $thistime = '';
		        if ( empty ($this->value ) ) {$this->value = "" ; } else {
		          $thisdate = esc_textarea( date('m/d/Y' , $this->value ) ) ;
		          $thistime = esc_textarea( date('h:i A' , $this->value ) ) ;
		           } // If the custom field is empty, we just want to return nothing, because putting an empty string into the date() function throws a warning
		        $output .= "<input type='date' class='date_input' name='$this->key[date]' id='$this->key[date]' placeholder='' value='$thisdate'  style='width:20%;' $enabled/>";
		        $output .= "<input type='text' class='time_input' name='$this->key[time]' id='$this->key[time]' placeholder='' value='$thistime'  style='width:20%;' $enabled/>";
		    break;
		    case "telephone": // Some telephone numbers, eh?
		        $output .= "<input type='tel' pattern='[0-9]{10}' class='telephone_input' name='$this->key' id='$this->key' placeholder='(310) 555-5555' value='".  sanitize_text_field( $this->value ) . "' />";
		        break;
		    case "number": // Numbers, eh?
		        $output .= "<input type='number'  class='number_input' name='$this->key' id='$this->key' placeholder='$this->defaults' value='".  sanitize_text_field( $this->value) . "' />";
		    break;
		    case "email": // Numbers, eh?
		        $output .= "<input type='email'  class='number_input' name='$this->key' id='$this->key' placeholder='$this->defaults' value='".  sanitize_text_field( $this->value ) . "' />";
		    break;
		    case "list": // A list? A list of WHA?!?!?!?!? Oh, we declare the options in the defaults for the $this
		    	$output .= "<select name='$this->key' id='$this->key'>";
		    	foreach ($this->defaults as $default) {
		    	    $output .= '<option value="'. $default['value'].'"'.  selected( $this->value , $default['value'] , false).'>'. $default['name'].'</option>';
		    	}
		        $output .= "</select>";
		    break;
		    case 'radio': //Radio options
		        foreach ($this->defaults as $default) {
		            $output .= "<label class='radio-option'><input style='width:auto;' type='radio' name='$this->key' value='$default->value' ".  checked( $this->value , $default->value , false) . ">$default->name</span></label>";
		        }
		    break;
		    case 'checkbox': //Radio options
		            $output .= '<input style="width:auto;" type="checkbox" name="'. $this->key .'" id="'. $this->key .'" '.  checked( 'on' , $this->value , false).'>';
		    break;
		    case 'image':
		        if ( is_numeric($this->value) ) { $attachmenturl = wp_get_attachment_image_src(  $this->value  , array(64,64) ); $url = $attachmenturl[0]; } else { $url = plugins_url( 'empty-image-drop.png' ,  __FILE__  ) ; }
		        $defaultornot = ( is_numeric($this->value) ) ? 'not-default' : 'default' ;
		        $output .= "<img class='image-upload $defaultornot' src='$url' alt='$this->key-image' id='$this->key-image' name='$this->key-image'/><input type='hidden' class='image-id' value='$this->value' id='$this->key' name='$this->key' />";
		    break;
		    case 'color':
		        $output .= "<span class='color-picker'><input type='text'  name='$this->key' id='$this->key' style=' border-color: ". esc_attr( $this->value ) ."' value='". esc_attr( $this->value ) ."'/></input></span>";
		    break;
		    case "hidden": // Just normal text input
		        $output .= "<input type='hidden' class='' name='$this->key' id='$this->key' value='". sanitize_text_field( $this->value ) . "' $enabled />";
		        break;
		    }
	$output = apply_filters( 'dco_metafield_form_input_' . $this->key, $output, $this );		    
    return apply_filters( 'dco_meta_field_field_form_input', $output, $this->key, $this->type, $this->value );
	return $output;
    }
    
    
    
    function validate_save_data(){
	    switch( $this->type ){ // Depending on the type of meta it is, some formatting is done, aside from trimming strings of white space, to the value.
                    case "text": 
                        $this->value = trim( $this->value ); 
                        break;
                    case "textarea": 
                    	$this->value = trim( $this->value ); 
                        break;
                    case "link": 
                    	$this->value = esc_url( $this->value );
                        break;
                    case "date": 
                        date_default_timezone_set('America/Los_Angeles');
                        $date =  $this->value;
                        if ($date) {
                            if ( checkdate( date('m', $date) , date('d', $date ) , date('Y', $date) ) ) { $this->value = strtotime( $this->value . " 12AM" ); } else { $this->value = "";} // Store dates as dates!
                        }
                        break;
                    case "telephone": 
                        $this->value = format_phone(trim( $_POST[$key] )); // Just some trimming please.
                        break;
                    case "number": 
                    	if ( !is_numeric( $this->value ) ) $this->value = false;
                        break;
                    case "email": 
                    	$this->value = sanitize_email($this->value); // Sanitize the email, if it's an email
                    	if ( !is_email($this->value) ) $this->value = false;
                        break;
                    case "list": 
                        $this->value = trim( $this->value ); 
                        break;
                    case "checkbox";
                        if (empty($this->value)) $this->value = 'off';
                        break;
                    case "datetime":
                        if ( is_array($this->value)) $this->value = strtotime( implode(" ",  $this->value )  );
                        break;
                    case 'color':
    
                    	if(preg_match('/^#[a-f0-9]{6}$/i', $this->value)){
	                    	//Verified hex color
	                    }  else if(preg_match('/^[a-f0-9]{6}$/i', $this->value))   { //Check for a hex color string without hash 'c1c2b4' //hex color is valid
		                    	$this->value = '#' . $this->value;
		                }
    				break;
    				default:
    				    if ( !is_array( $this->value ) ) $this->value = trim( $this->value ); 
    				break;
                }
        $this->value = apply_filters('dco_meta_field_validate_save_data', $this->value, $this->type , $this->key);
    }
	
}


/// Meta Box Actions
if (!function_exists('display_post_meta_fields')) {
	function display_post_meta_fields( $meta_array ){
	global $post;

	do_action('display_meta_fields_styles', $post, $meta_array);

	$custom = get_post_custom( $post->ID );
	$output = '<div style="position: absolute;" id="colorpicker"></div>';
	    foreach ($meta_array as $meta_item){ // For each of the items in the custom keys array, let's display some fields!
	        $key  = trim($meta_item->key); $title = trim($meta_item->title); // We're going to store all of this stuff in some variables just in case we might want to effect everything, such as I did with trimming the values.
	        $type = trim($meta_item->type);
	        $meta_item->value   = isset( $custom[$key][0] ) ? $custom[$key][0] : $meta_item->defaults;
	        	if ($type == 'hidden' ) { 
	        		$output .= $meta_item->field_form_input(); 
	        	} else {
	        		$output .='<tr class="form-field" id="' . $key . '-row">';
	        		$output .='<th scope="row" valign="top"><label for="' . $key . '"> '. $title .'</label></th>'; // The label
	        		$output .="<td>";
	        		$output .= $meta_item->field_form_input();
	        		$output .='<p class="description">'. apply_filters( 'dco_meta_field_description_output', $meta_item->description, $meta_item ) .'</p></td>'; // The description
	        		$output .='</tr>';
	        	}
	    }
	return $output;
	}
}

if (!function_exists('enqueue_post_meta_fields_styles')) {
	add_action('display_meta_fields_styles', 'enqueue_post_meta_fields_styles', 99, 2);
	function enqueue_post_meta_fields_styles($object, $meta_array){
	    //global $post;
	    wp_enqueue_style( 'meta_field_styles' ,   plugins_url('meta_fields.css', __FILE__) , array(  'thickbox'  ), time() ); 
	    wp_enqueue_media();
	    wp_enqueue_script( 'meta_field_script' ,  plugins_url('meta_fields.js', __FILE__) , array( 'thickbox',  'media-models', 'media-upload',  'jquery')  , time() ); 
	    //wait, do we need to do extra stuff?
	   // global $meta_array;

	    foreach( $meta_array as $meta_item ){
            switch( $meta_item->type ){
                case 'date':
                    wp_enqueue_script('jquery-ui-datepicker');
                break;
                case 'datetime':
                    wp_enqueue_style('wp-jquery-ui-dialog');
                    wp_enqueue_script('jquery-ui-datepicker');
                    wp_enqueue_script( 'jquery-timepicker' ,  plugins_url('jquery.timePicker.min.js', __FILE__) , array( 'jquery')  , time() ); 
                break;        
	            case 'image':
	            	wp_localize_script( 'meta_field_script', 'dco_meta_field_object', array( 'post_id' => $object->ID) );
	            break;
	            case 'color':
	               wp_enqueue_style('wp-color-picker');
	               wp_enqueue_script('wp-color-picker');
	               wp_enqueue_style('farbtastic');   // These are probably done and over, correct?
	               wp_enqueue_script('farbtastic');  // These are probably done and over, correct?
	            break;
	        }
	    }
	}

	add_filter("attribute_escape", "replaceinsertintopost", 10, 2);
	function replaceinsertintopost($safe_text, $text) {
		if ($text == 'Insert into Post') return str_replace( __('Insert into Post'), __('Use This Image'), $text);
		return $safe_text;
	}

}



if (!function_exists('save_meta_fields_post')){   
    function save_meta_fields_post($meta_fields_array, $post_id, $post_from_server) { // This is to help us, using an array of keys and fields, to save meta data when a post is saved. All just to make things move faster.
	    foreach ($meta_fields_array as $meta_item){ // Let's run through the $article_meta_fields array and update the appropriate meta keys. This is easy.
            $key = trim( $meta_item->key ); // Just storing the key for this specific meta item in the $key variable. And we'll trim it all just to protect ourselves
            if ( !empty($post_from_server[$key]) ) { $meta_item->value = $post_from_server[$key];  $meta_item->validate_save_data();      } else { $meta_item->value = null; }
            update_post_meta($post_id, $meta_item->key,  $meta_item->value );  // update the meta key with the value if it hasn't been set false by some verification
        }
    }
}    
    
    
    
if (!function_exists('format_phone')){       
    function format_phone($phone)  { // Oh, thanks dawg http://snipplr.com/view.php?codeview&id=25
    	$number = trim(preg_replace('#[^0-9]#s', '', $phone));
    
        $length = strlen($number);
        if($length == 7) {
            $regex = '/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/';
            $replace = '$1-$2';
        } elseif($length == 10) {
            $regex = '/([0-9]{3})([0-9]{3})([0-9]{4})/';
            $replace = '($1) $2-$3';
        } elseif($length == 11) {
            $regex = '/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/';
            $replace = '$1 ($2) $3-$4';
        }
    
        $formatted = preg_replace($regex, $replace, $number);
    
        return $formatted;
    }
}        
        
// This one is simple: 
//	$custom_tax_fields = new TaxMetaGroup('$taxonomy_slug');


class TaxMetaGroup{

	public $slug, $name, $objects;
	
	public function __construct( $taxonomy ){
		
		$this->slug = $taxonomy;
		
		$this->create_taxonomy_meta_table($taxonomy); // Create the taxonomy table, please
			
		add_action('admin_init',  array( $this, 'tax_meta_group_admin_init' ) );
	}
	
	public function tax_meta_group_admin_init(){
		
		$this->taxonomy = get_taxonomy( $this->slug );
		if (empty($this->taxonomy->name)) return false;
		//$this->slug = $this->taxonomy->name;
		$this->objects = apply_filters( $this->slug.'_metafields', array() );
				
		add_action( $this->slug . '_edit_form_fields', array( $this, 'display_fields' ) , 10);
		//add_action( $this->slug . '_edit_form'       , array( $this, 'save_fields' ) , 10) ;
		add_action( $this->slug . '_add_form_fields' , array( $this, 'display_fields' ) , 10 );
		//add_action( $this->slug . '_add_form'        , array( $this, 'save_fields' ), 10);
		
		add_action( 'created_' . $this->slug , array( $this, 'save_fields' ) , 10 , 2);
		add_action( 'edited_' . $this->slug , array( $this, 'save_fields' ) , 10 , 2);
	}


	public function display_fields ($tag){
		$tag->ID = $tag->term_id;
		do_action('display_meta_fields_styles', $tag, $this->objects );
		$output = '<div style="position: absolute;" id="colorpicker"></div>';
				
	    if ( $tag->filter == 'edit') {$page = 'edit';} else {$page = 'add';}
        foreach( $this->objects as $meta_item ){
            
            switch($page){
	            
                case 'add':
                	$output .= '<div class="form-field">';
                	$output .= '<label for="'.$key.'">'.$meta_item->title.'</label>';
                	$output .= $meta_item->field_form_input();
                	$output .= '<p class="description">'.$meta_item->description.'</p>';
                	$output .= '</div>';
                break;
                
                case 'edit':
                	$key = trim( $meta_item->key ); // Just storing the key for this specific meta item in the $key variable. And we'll trim it all just to protect ourselves
					$meta_item->value = get_metadata( $tag->taxonomy, $tag->term_id, $key, true);
                	$output .= '<tr class="form-field"><th scope="row" valign="top">';
                	$output .= '<label for="'.$key.'">'.$meta_item->title.'</label>';
                	$output .= '</th><td>';
                	$output .= $meta_item->field_form_input();
                	$output .= '<p class="description">'.$meta_item->description.'</p>';
                	$output .= ' </td></tr>';
                break;
                
            }
            
        }
        echo $output;
    }
        
    public function save_fields($term_id, $taxonomy){
	    $this->term_id = $term_id;
	    
        foreach($this->objects as $meta_item){
            $key = trim( $meta_item->key );
            $type = trim( $meta_item->type ); 
            if ( isset($_POST[$key]) ) {
	            
	            $meta_item->value = $_POST[$key];
	            $meta_item->validate_save_data();  
	            
	            //$value = apply_filters( array($meta_item,'validate_meta_value'),  $type, trim( $_POST[$key] ) ) ;

                $update = update_metadata( $this->slug, $term_id, $key, $meta_item->value );
            }
            
        }
    }
    
	public function create_taxonomy_meta_table($taxonomy){
	   //Get the $wpdb global
	   $variable_name = $taxonomy . 'meta';
	   global $wpdb;
	   $wpdb->$variable_name = $wpdb->prefix. $variable_name;
	   //Set a default result
	   $result = false;
	   //Install table, if it doesnt exist already
	   $sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->$variable_name."` (
	      	  `meta_id` bigint(20) UNSIGNED NOT NULL auto_increment,
	      	  `". $taxonomy ."_id` bigint(20) UNSIGNED NOT NULL,
	      	  `meta_key` varchar(255),
	      	  `meta_value` longtext,
	      	  PRIMARY KEY (`meta_id`)
	      	  )";
	   $result = $wpdb->query($sql);
	   global $wpdb;
	}
}


/////////////
// AUTHORS //
/////////////

class DCoUserMetaGroup{
	public $name, $objects;
	
	public function __construct( $name , $user_roles ){
		$user = wp_get_current_user();
		$this->name = $name;
		$this->user = $user;
		$this->user_roles = $user_roles;
		$this->slug = str_replace('-', '_', sanitize_title($name) ); //Replace the dashes with the title
		$this->objects = apply_filters( $this->slug.'_metafields', array() );
		
		foreach($this->user_roles as $allowed_user_role ){		
			if ( in_array( $allowed_user_role , (array) $user->roles ) ) {
					add_action( 'show_user_profile', array( $this, 'display_fields' ), 10 );
					add_action( 'edit_user_profile', array( $this, 'display_fields' ), 10 );
					add_action( 'personal_options_update', array( $this, 'save_fields') );
					add_action( 'edit_user_profile_update', array( $this, 'save_fields' ) );
					add_action( 'set_current_user', array( $this, 'attach_fields_to_user' ) );
					add_action( 'dco_user_metagroup_created', $this);
			}
		}
	}
	
	public function display_fields($user){
		$this->user = $user;
		echo '<table class="form-table" id="user-fields-'. $this->slug .'"><tbody>';
			echo "<h3>" . apply_filters('the_user_metagroup_name_display' , $this->name ) ."</h3>";
			do_action( 'before_dco_metafields_display', $this);
			do_action(  $this->slug."_before_meta_fields" , $this);
			echo $this->display_meta_fields($this->objects);
			do_action(  $this->slug."_after_meta_fields" , $this ); 
		echo '</tbody><table><!-- .form-table #user-fields-'. $this->slug .'-->';
		
	}
	
	public function display_meta_fields( $meta_array ){
	if (empty($meta_array)) return 'No Items to Display';
	do_action('display_meta_fields_styles', $this->user, $meta_array);

	$output = '';
	    foreach ( $meta_array as $meta_item ){ // For each of the items in the custom keys array, let's display some fields!
	        $key  = trim($meta_item->key); $title = trim($meta_item->title); // We're going to store all of this stuff in some variables just in case we might want to effect everything, such as I did with trimming the values.
	        $type = trim($meta_item->type);
	        $meta_item->get_author_meta_value( $this->user );

	        	if ($type == 'hidden' ) { 
	        		$output .= $meta_item->field_form_input(); 
	        	} else {
	        		$output .='<tr class="form-field" id="' . $key . '-row">';
	        		$output .='<th scope="row" valign="top"><label for="' . $key . '"> '. $title .'</label></th>'; // The label
	        		$output .="<td>";
	        		$output .= $meta_item->field_form_input();
	        		$output .='<p class="description">'. apply_filters( 'dco_meta_field_description_output', $meta_item->description, $meta_item ) .'</p></td>'; // The description
	        		$output .='</tr>';
	        	}
	    }
	return $output;
	}
	
	
	public function save_fields( $user_id ){
	
	foreach (  $this->objects as $meta_item ){ // Let's run through the $article_meta_fields array and update the appropriate meta keys. This is easy.
            $key = trim( $meta_item->key ); // Just storing the key for this specific meta item in the $key variable. And we'll trim it all just to protect ourselves
            if ( !empty($_POST[$key]) ) { $meta_item->value = $_POST[$key];  $meta_item->validate_save_data(); } else { $meta_item->value = null; }
            update_user_meta( $user_id , $meta_item->key,  $meta_item->value );  // update the meta key with the value if it hasn't been set false by some verification
        }
		do_action(  $this->slug. "_after_saved_meta" , $this, $user_id );
	}
		
	public function attach_fields_to_user( $post ){
		global $current_user;
		foreach ( $this->objects as $key => $meta_field ){
			$post->$key = get_the_author_meta( $meta_field, $current_user );
		}
		do_action( $this->slug . '_the_user', $post ); // For doing additional things just for this portfolio setting
	}
}


class AuthorMetaObject extends MetaObject{
	

	function get_author_meta_value( $user ){
		if ( get_the_author_meta($this->key, $user->ID ) ){
			$this->value = get_the_author_meta( $this->key, $user->ID );
		} else {
			$this->value = $this->defaults;
		}
	}
	
}


add_action('before_dco_metafields_display', 'dco_user_metagroup_created_callback');
function dco_user_metagroup_created_callback(){
	add_filter('dco_meta_field_field_form_input', 'dco_authormeta_add_class_for_authors', 99, 4);
	
}

function dco_authormeta_add_class_for_authors($output, $key, $type, $value ){
	$output = str_replace("class='", "class='regular-text ", $output);
	return $output;
}
	



?>