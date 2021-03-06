<?php 


//menu style list pages for custom post types with hierarchy 
wp_list_pages ( array(
        	'post_type' => 'the-post-type'
        	)
	)		


//ALT TEXT FOR THUMBNAILS 
$thumbnail_id = get_post_thumbnail_id( $post->ID );
$alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);   
the_post_thumbnail( 'full', array( 'alt' => $alt ) ); 


/*
**
DASHBOARD STUFF
**
*/


//HIDE OR MINIMAL WP

//hide posts from other authors for author level users
function posts_for_current_author($query) {
    global $pagenow;
    if( 'edit.php' != $pagenow || !$query->is_admin )
        return $query;
 
    if( !current_user_can( 'manage_options' ) ) {
        global $user_ID;
        $query->set('author', $user_ID );
    }
    return $query;
}
add_filter('pre_get_posts', 'posts_for_current_author');




//skip directly to posts instead of dashboard on login
//redirects from dashboard to edit post list 
function remove_the_dashboard () {
if (current_user_can('level_10')) {
    return;
    }else {
    global $menu, $submenu, $user_ID;
    $the_user = new WP_User($user_ID);
    reset($menu); $page = key($menu);
    while ((__('Dashboard') != $menu[$page][0]) && next($menu))
    $page = key($menu);
    if (__('Dashboard') == $menu[$page][0]) unset($menu[$page]);
    reset($menu); $page = key($menu);
    while (!$the_user->has_cap($menu[$page][1]) && next($menu))
    $page = key($menu);
    if (preg_match('#wp-admin/?(index.php)?$#',$_SERVER['REQUEST_URI']) && ('index.php' != $menu[$page][2]))
    wp_redirect(get_option('siteurl') . '/wp-admin/edit.php');}
}
add_action('admin_menu', 'remove_the_dashboard');




//remove AIM and Jabber from profile page
function modify_user_contact_methods( $user_contact ) { 
    // Remove user contact methods
    unset( $user_contact['aim']    );
    unset( $user_contact['jabber'] );
    return $user_contact;
}
add_filter( 'user_contactmethods', 'modify_user_contact_methods' );



//remove elements from the admin bar
show_admin_bar( true ); //show the admin bar to everyone
 


//remove a bunch of standard elements
function my_admin_bar_render() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
    $wp_admin_bar->remove_menu('wp-logo');
    $wp_admin_bar->remove_menu('site-name');
    $wp_admin_bar->remove_menu('customize');
    $wp_admin_bar->remove_menu('new-content');
    $wp_admin_bar->remove_menu('wp-rest-api-cache-empty');
}
add_action( 'wp_before_admin_bar_render', 'my_admin_bar_render' );


//hide admin bar for non-admin
add_action('after_setup_theme', 'remove_admin_bar');
 
function remove_admin_bar() {
  if (!current_user_can('administrator') && !is_admin()) {
    add_filter('show_admin_bar', '__return_false');
  }
}


//add to taxonomy to post type arguments to remove from menu construction view 
 $args = array(
'show_in_nav_menus' => false,


//change default media library settings

function better_default_image_size() {
    // Set default values for the upload media box
    update_option('image_default_align', 'center' );
    update_option('image_default_size', 'large' );

}
add_action('after_setup_theme', 'better_default_image_size');


/*------------------------------END DASHBOARD MINIMAL STUFF----------------------------------*/

)

/*
**
ACF STUFF
**
*/

//SHOW HIDDEN CUSTOM FIELDS
add_filter( 'is_protected_meta', '__return_false', 999 );


//ACF allow us to see custom fields in editor view
add_filter( 'acf/settings/remove_wp_meta_box', '__return_false' );


//add custom thumb sizes to media insert options
add_filter( 'image_size_names_choose', 'my_custom_sizes' );

function my_custom_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'your-custom-size' => __('Your Custom Size Name'),
    ) );
}


//ACF JSON SAVER
add_filter('acf/settings/save_json', 'my_acf_json_save_point');
 
function my_acf_json_save_point( $path ) {
    
    // update path
    $path = get_stylesheet_directory() . '/acf-json';
    
    // return
    return $path;
    
}


// Return acf field staff_group
function faculty_return_type( $object, $field_name, $request ) {
    global $post;
    $faculty_type = get_field('staff_group', $post->ID); 
    return $faculty_type[0];
}




//SORT ACF ENTRIES BY PARTICULAR FIELD AUTOMATICALLY
function sort_record_by_year( $value, $post_id, $field ) {
    // vars
    $order = array();
    
    // bail early if no value
    if( empty($value) ) {
        return $value;      
    }
    
    // populate order
    foreach( $value as $i => $row ) {
        
        $order[ $i ] = $row['field_5cf50f064b39c'];//field for sorting
        
    }
    
    // multisort
    array_multisort( $order, SORT_DESC, $value );
    
    // return   
    return $value;
    
}

add_filter('acf/load_value/name=faculty_record', 'sort_record_by_year', 10, 3);//replace faculty record with your repeater/container field

/*------------------------------END ACF----------------------------------*/


/*
**
API STUFF
**
*/


//CHANGE WP JSON RETURNS TO > 99

add_filter( 'rest_post_collection_params', 'big_json_change_post_per_page', 10, 1 );

function big_json_change_post_per_page( $params ) {
    if ( isset( $params['per_page'] ) ) {
        $params['per_page']['maximum'] = 200;
    }

    return $params;
}





//prevent addition of terms to custom taxonomy from https://wordpress.stackexchange.com/questions/112686/how-to-prevent-new-terms-being-added-to-a-custom-taxonomy
add_action( 'pre_insert_term', function ( $term, $taxonomy )
{
    return ( 'topics' === $taxonomy )
        ? new WP_Error( 'term_addition_blocked', __( 'You cannot add terms to this taxonomy' ) )
        : $term;
}, 0, 2 );



//add custom field (ACF in this case) to JSON endpoint for custom post type - faculty is the post type
add_action( 'rest_api_init', 'add_faculty_type_to_json' );
function add_faculty_type_to_json() {

    register_rest_field(
        'faculty',
        'faculty_type',
        array(
            'get_callback'    => 'faculty_return_type',
        )
    );
}


/*---------------------------------JSON MOD FOR ADDITIONAL SITE INFO----------------------------------*/

function extraJsonData($response){
    $blog_id = get_current_blog_id();
    $blog_details = get_blog_details($blog_id);
    $data = $response->data;
    $data['created'] =$blog_details->registered;
    $data['last_updated'] =$blog_details->last_updated;
    $data['post_count'] =$blog_details->post_count;
    $data['page_count'] = wp_count_posts('page','publish');
    $response->set_data($data);
    return $response;
}

add_filter('rest_index', 'extraJsonData');

/*------------------------------END API----------------------------------*/




 //add custom columns in editor list view based on https://code.tutsplus.com/articles/add-a-custom-column-in-posts-and-custom-post-types-admin-screen--wp-24934

 // ADD NEW COLUMN w header
function make_event_columns_head($defaults) {
    $defaults['make_event'] = 'Make Event';
    return $defaults;
}
 
// add the extra element
function make_event_columns_content($column_name, $post_ID) {
    if ($column_name == 'make_event') {
       echo 'foo';
    }
}


//for all posts
add_filter('manage_posts_columns', 'make_event_columns_head'); 
add_action('manage_posts_custom_column', 'make_event_columns_content', 10, 2);
//just for custom post type named workshop
add_filter('manage_workshop_posts_columns', 'make_event_columns_head'); 
add_action('manage_workshop_posts_custom_column', 'make_event_columns_content', 10, 2);





//CUSTOM page template for custom post type from a plugin 
function get_custom_post_type_template($single_template) {
     global $post;

     if ($post->post_type == '*********') {
          $single_template = dirname( __FILE__ ) . '/*********.php';
     }
     return $single_template;
}
add_filter( 'single_template', 'get_custom_post_type_template' );   



//LETS YOU CONTROL WHAT GETS STRIPPED IN CUT/PASTE TO MCE EDITOR !!!REVISIT WITH TINYMCE OPTIONS!!!!
//fix cut paste drama from https://jonathannicol.com/blog/2015/02/19/clean-pasted-text-in-wordpress/
add_filter('tiny_mce_before_init','configure_tinymce');

/**
 * Customize TinyMCE's configuration
 *
 * @param   array
 * @return  array
 */
function configure_tinymce($in) {
  $in['paste_preprocess'] = "function(plugin, args){
    // Strip all HTML tags except those we have whitelisted
    var whitelist = 'p,b,strong,i,em,h2,h3,h4,h5,h6,ul,li,ol,a,href';
    var stripped = jQuery('<div>' + args.content + '</div>');
    var els = stripped.find('*').not(whitelist);
    for (var i = els.length - 1; i >= 0; i--) {
      var e = els[i];
      jQuery(e).replaceWith(e.innerHTML);
    }
    // Strip all class and id attributes
    stripped.find('*').removeAttr('id').removeAttr('class').removeAttr('style');
    // Return the clean HTML
    args.content = stripped.html();
  }";
  return $in;
}




//REMOVE STUFF FROM CUT/PASTE VISUAL EDITOR
//fix cut paste drama from https://jonathannicol.com/blog/2015/02/19/clean-pasted-text-in-wordpress/
add_filter('tiny_mce_before_init','configure_tinymce');

/**
 * Customize TinyMCE's configuration
 *
 * @param   array
 * @return  array
 */
function configure_tinymce($in) {
  $in['paste_preprocess'] = "function(plugin, args){
    // Strip all HTML tags except those we have whitelisted
    var whitelist = 'p,b,strong,i,em,h2,h3,h4,h5,h6,ul,li,ol,a,href';
    var stripped = jQuery('<div>' + args.content + '</div>');
    var els = stripped.find('*').not(whitelist);
    for (var i = els.length - 1; i >= 0; i--) {
      var e = els[i];
      jQuery(e).replaceWith(e.innerHTML);
    }
    // Strip all class and id attributes
    stripped.find('*').removeAttr('id').removeAttr('class').removeAttr('style');
    // Return the clean HTML
    args.content = stripped.html();
  }";
  return $in;
}




//make sure comments reflect display name from https://wordpress.stackexchange.com/questions/31694/comments-do-not-respect-display-name-setting-how-to-make-plugin-to-overcome-thi
add_filter('get_comment_author', 'wpse31694_comment_author_display_name');
function wpse31694_comment_author_display_name($author) {
    global $comment;
    if (!empty($comment->user_id)){
        $user=get_userdata($comment->user_id);
        $author=$user->display_name;
    }

    return $author;
}


//allow some additional file types for upload
function my_custom_mime_types( $mimes ) {

        // New allowed mime types.
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        $mimes['studio3'] = 'application/octet-stream';

        // Optional. Remove a mime type.
        unset( $mimes['exe'] );

    return $mimes;
}
add_filter( 'upload_mimes', 'my_custom_mime_types' );



/*------------------------------------ENABLE CSS FOR ADMINS-------------------------------------------------*/
//from https://wordpress.org/plugins/multisite-custom-css/ just didn't want another plugin

add_filter( 'map_meta_cap', 'multisite_custom_css_map_meta_cap', 20, 2 );
function multisite_custom_css_map_meta_cap( $caps, $cap ) {
    if ( 'edit_css' === $cap && is_multisite() ) {
        $caps = array( 'edit_theme_options' );
    }
    return $caps;
}



/*------------------------------------H5P  ---------------------------------------------------*/
// Make H5P embeds flexible
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( is_plugin_active(  'h5p/h5p.php' ) ) {
  //plugin is activated
     add_action('wp_enqueue_scripts', 'h5pflex_widget_enqueue_script');
}


function h5pflex_widget_enqueue_script() {
    $h5p_script = plugins_url( 'h5p/h5p-php-library/js/h5p-resizer.js', __DIR__);
    wp_enqueue_script( 'h5p_flex', $h5p_script, true );

    }
