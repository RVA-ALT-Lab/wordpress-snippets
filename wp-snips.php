<?php 

//hide admin bar for non-admin
add_action('after_setup_theme', 'remove_admin_bar');
 
function remove_admin_bar() {
  if (!current_user_can('administrator') && !is_admin()) {
    add_filter('show_admin_bar', '__return_false');
  }
}


//menu style list pages for custom post types with hierarchy 
wp_list_pages ( array(
        	'post_type' => 'the-post-type'
        	)
	)		

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


//CHANGE WP JSON RETURNS TO > 99

add_filter( 'rest_post_collection_params', 'big_json_change_post_per_page', 10, 1 );

function big_json_change_post_per_page( $params ) {
    if ( isset( $params['per_page'] ) ) {
        $params['per_page']['maximum'] = 200;
    }

    return $params;
}



//ALT TEXT FOR THUMBNAILS 
$thumbnail_id = get_post_thumbnail_id( $post->ID );
$alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);   
the_post_thumbnail( 'full', array( 'alt' => $alt ) ); ?>
