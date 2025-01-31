<?php
/*
Plugin Name: System edit restriction
Description: Security!! No-one will be able edit/modify system files(theme+plugins) from Wordpress Dashboard,even admins (So, Only from FTP can be edited)...   It is useful, when you share Admin access to others (P.S.  OTHER MUST-HAVE PLUGINS FOR EVERYONE: http://bitly.com/MWPLUGINS  ) . IF PROBLEMS, JUST REMOVE PLUGIN.
Version: 1.23
Author: TazoTodua
Author URI: http://www.protectpages.com/profile
Plugin URI: http://www.protectpages.com/
Donate link: http://paypal.me/tazotodua
*/ 
if ( ! defined( 'ABSPATH' ) ) exit; //Exit if accessed directly


class SystemEditRestriction {
	public $Allow_ips_file	='ALLOWED_IPs_FOR_WP_MODIFICATION.php';
	public $pluginpage__SER	='system-edit-restriction-page';
	public $plugin_settings_page__SER;
	public $StartSYMBOL		='<?php ZZZ //';
				//======= Disallowed URLS =========//	
	public $restricted_places = array(
				'user-new.php',	'upgrade-functions.php','upgrade.php',	'themes.php',	'theme-install.php', 'theme-editor.php','setup-config.php','plugins.php',	'plugin-install.php','options-head.php','network.php',	'ms-users.php','ms-upgrade-network.php','ms-themes.php',	'ms-sites.php','ms-options.php','ms-edit.php','ms-delete-site.php','ms-admin.php','moderation.php','menu-header.php','menu.php','edit-comments.php',
				//======= Disallowed PLUGINS ========= any 3rd party plugins' menu pages, added under "settings"
				'page=system-edit-restriction-page',
				);
	
	public function __construct()	{
		$this->plugin_settings_page__SER = (is_multisite() ? network_admin_url('settings.php') : admin_url( 'options-general.php') ). '?page='.$this->pluginpage__SER  ;
		
		add_action('init', array($this,'start_admin_restrict_checkerr'),0);
		register_activation_hook( __FILE__,  array($this, 'sep_activate'));
		register_deactivation_hook( __FILE__,  array($this, 'sep_deactivate'));
		
		
		add_action( (is_multisite() ? 'network_admin_menu' : 'admin_menu') , function() {  add_submenu_page(   (is_multisite() ?  'settings.php' : 'options-general.php'),  'System Edit Restrict', 'System Edit Restrict', 'create_users',  $this->pluginpage__SER, array($this,'sep_output') );	  } );
									//===========  links in Plugins list ==========//
								add_filter( "plugin_action_links_".plugin_basename( __FILE__ ), function ( $links ) {   $links[] = '<a href="'.$this->plugin_settings_page__SER.'">Settings</a>'; $links[] = '<a href="http://paypal.me/tazotodua">Donate</a>';  return $links; } );
								//REDIRECT SETTINGS PAGE (after activation)
								add_action( 'activated_plugin', function($plugin ) { if( $plugin == plugin_basename( __FILE__ ) ) { exit( wp_redirect( $this->plugin_settings_page__SER.'&isactivation'  ) ); } } );
								
	}
	// (after activation)
	public function sep_activate()	{
		
			// die if not network (when MULTISITE )
			if ( is_multisite() && ! strpos( $_SERVER['REQUEST_URI'], 'wp-admin/network/plugins.php' ) ) {
				die ( __( '<script>alert("Activate this plugin only from the NETWORK DASHBOARD.");</script>') );
			}
	
	
	
			//old_version updating
		$new_dir =ABSPATH.'wp-content/ALLOWED_IP/'.$this->site_nm().'/'; 
		$old_dir =ABSPATH.'ALLOWED_IP/'.str_replace('www.','', $_SERVER['HTTP_HOST']).'/';
			if (file_exists($old_dir.$this->Allow_ips_file)) {@mkdir($new_dir, 0777); @rename($old_dir.$this->Allow_ips_file,$new_dir.$this->Allow_ips_file);@rmdir($old_dir);} 
		$old_dir = ABSPATH.'wp-content/ALLOWED_IP/'.str_replace('www.','', $_SERVER['HTTP_HOST']).'/';
			if (file_exists($old_dir.$this->Allow_ips_file)) {@mkdir($new_dir, 0777); @rename($old_dir.$this->Allow_ips_file,$new_dir.$this->Allow_ips_file);@rmdir($old_dir);} 
	}	
	public function sep_deactivate(){unlink($this->allowed_ips_filee());}	
	public function blockedMessage(){return '(HOWEVER,IF YOU BLOCK YOURSELF, enter FTP folder /WP-CONTENT----ALLOWED_IP/ and add your IP('.$_SERVER['REMOTE_ADDR'].') into that file.)';}
	public function site_nm()		{return preg_replace('/\W/si','_',str_replace('://www.','://', home_url()) );     }	
	public function Nonce_checker($value, $action_name)	{
		if ( !isset($value) || !wp_verify_nonce($value, $action_name) ) {die("not allowed due to SYSTEM_EDIT_RESTRICTION");}
	}
	

	/* not needed, no danger here as i consider.
	public function disable_admin_ajax(){
		if( defined('DOING_AJAX') && DOING_AJAX ) {
			if (!$this->check_enable_privilegies())	{
				define( 'DISALLOW_FILE_MODS', true );
				//from /wp-admin/admin_ajax.php
				$disallowed___core_actions_get = array(	);
				$disallowed___core_actions_post = array();
				if ( ! empty( $_GET['action'] ) ...
			}
		}
	}
	*/
	
	

	public function check_enable_privilegies(){
		//check, if  RESTRICTION enabled
		if (get_option('optin_for_sep_ipss') == 2){
			$allwd_ips = file_get_contents($this->allowed_ips_filee());
			//check - if USER's ip address not found, then RESTRICT!!!
				$IP = $_SERVER['REMOTE_ADDR'];  $IPx= preg_replace('/(.*?)\.(.*?)\.(.*?)\.(.*)/si','$1.$2.$3.'.'*', $_SERVER['REMOTE_ADDR']);
			if (stripos($allwd_ips, $IP ) === false  &&   stripos($allwd_ips,  $IPx) === false  ){ return false; }	
		}
		return true;
	}
		
	public function start_admin_restrict_checkerr()	{ 
		if (!$this->check_enable_privilegies())	{
			//Restriction constants for Wordpress
			if (!defined('DISALLOW_FILE_EDIT')) define('DISALLOW_FILE_EDIT', true );
			if (!defined('DISALLOW_FILE_MODS')) define('DISALLOW_FILE_MODS', true );
			if (!defined('WFMB__DISABLERUN')) define('WFMB__DISABLERUN', true );
			
				//remove_menu_page( 'edit-comments.php' );remove_menu_page( 'themes.php' );remove_menu_page( 'plugins.php' );
				//remove_menu_page( 'admin.php?page=mp_st' );remove_menu_page( 'admin.php?page=cp_main' );
				//remove_submenu_page( 'edit.php?post_type=product', 'edit-tags.php?taxonomy=product_category&amp;post_type=product' );
			foreach ($this->restricted_places as $each) { if (stripos($_SERVER['REQUEST_URI'],$each) !== false) {$disallow=true;} }
			if (isset($disallow)) {	die('no access to this page. error_534 ... <a href="./">Go Back</a> <br/><br/>'.$this->blockedMessage()); }
		}
	
	}
	
	
	public function allowed_ips_filee()	{
		//file path
		$pt_folder	= ABSPATH.'/wp-content/ALLOWED_IP/'. $this->site_nm();	if(!file_exists($pt_folder)){mkdir($pt_folder, 0755, true);}
		$file		= $pt_folder.'/'.$this->Allow_ips_file;
				if(!file_exists($file))		{
						//initial values
						$bakcup_of_ipfile = $this->StartSYMBOL . get_option("backup_ips_".$this->pluginpage__SER.'___'. $this->site_nm() );
					file_put_contents($file, (!empty($bakcup_of_ipfile)?  $bakcup_of_ipfile : $this->StartSYMBOL. '101.101.101.101 (its James, my friend)|||102.102.102.102 (its my pc)|||'.$_SERVER['REMOTE_ADDR'].'(my another pc2)||| and so on...') );
				}
		return $file;
	}
	
	public function sep_output() { ?>
			<?php
			//IF whitelist updated
			if (!empty($_POST['opt_of_whitelist_ips'])) 
			{
				$this->Nonce_checker($_POST['update_nonce'],'uupnonce');
				
				//update setting
				update_option('optin_for_sep_ipss',$_POST['opt_of_whitelist_ips']);
				//change IP file
					$final	= $_POST['sep_white_IPS'];
					$final	= str_replace("\r\n\r\n",	"",		$final);
					$final	= str_replace("\r\n",		"|||",	$final);
				file_put_contents($this->allowed_ips_filee(), $this->StartSYMBOL .$final );
					//make backup
					update_option("backup_ips_".$this->pluginpage__SER.'___'. $this->site_nm()  ,  $final);
			}
		
			$allowed_ips 	= str_replace($this->StartSYMBOL, '', file_get_contents($this->allowed_ips_filee()) );
			$whiteip_answer	= get_option('optin_for_sep_ipss');
			$d2 = $whiteip_answer == 2 ? "checked" : '';
			$d1 = $whiteip_answer == 1 || empty($whiteip_answer) ? "checked" : '';
			?>
			<br/><br/>	
			<form method="post" action="">
				<p class="submit">
					
				<div class="white_list_ipps" style="background-color: #1EE41E;padding: 5px; margin:0 0 0 20%;width: 50%;">
					<div style="font-size:1.2em;font-weight:bold;">
						RESTRICT PLUGIN/THEME EDIT&INSTALL from DASHBOARD: (<a href="javascript:alert('1)OFF:  Plugin will be inactive.. \r\n2)ON: Only the listed IPs can  EDIT&INSTALL PLUGINS or THEMES. Another IP (even if he is admin) cant EDIT&INSTALL them. \r\n\r\n <?php echo $this->blockedMessage();?> \r\n');">read more!!</a>):
					</div>
		<table style="border:1px solid;"><tbody>
			<tr><td>OFF	</td><td><input onclick="lg_radiod();" type="radio" name="opt_of_whitelist_ips" value="1" <?php echo $d1;?> /></td></tr>
			<tr><td>ON	</td><td><input onclick="lg_radiod();" type="radio" name="opt_of_whitelist_ips" value="2" <?php echo $d2;?> /></td></tr>
		</tbody></table>
					<div style="float:right;">(your IP is <b style="color:red;background-color:yellow;"><?php echo $_SERVER['REMOTE_ADDR'];?></b>)</div>
				<br/><div id="DIV_whiteipielddd" style="overflow-y:auto;">
						<?php	$liness=explode("|||",$allowed_ips); ?>
						<textarea id="whiteips_fieldd" style="width:100%;height:150px;" name="sep_white_IPS"><?php foreach ($liness as $line) {echo $line."\r\n";}?></textarea>
						<div style="float:right;">
							1)<a href="javascript:alert('You can insert Asterisk IP instead of last 3 chars. For example:\r\n 111.111.111.*');">Adding Variable IP</a>
							<br/>2)<a href="javascript:alert('In addition to \u0022DISABLE_FILE_MODS\u0022 command, this plugin disables access to these pages: \r\n\r\n <?php foreach($this->restricted_places as $each) {echo $each.'\r\n';} ?>');">See which pages will be disabled</a>
						</div>
					</div>
					
					<script type="text/javascript">
					function lg_radiod(){
						var valllue = document.querySelector('input[name="opt_of_whitelist_ips"]:checked').value;
						document.getElementById("DIV_whiteipielddd").style.opacity = (valllue != "1") ? "1" : "0.3";
					}
					lg_radiod();
					</script>
				</div>

					<br/><div style="clear:both;"></div>
					<input type="hidden" name="update_nonce" value="<?php echo wp_create_nonce('uupnonce');?>" />
					<input type="submit"  value="SAVE" onclick="return check_sep_ips();" />
					<script type="text/javascript">
					function check_sep_ips(){
						var IPLIST_VALUE=document.getElementById("whiteips_fieldd").value;
						var user_ip="<?php echo $_SERVER['REMOTE_ADDR'];?>";
						
						var TurnedONOFF = document.querySelector('input[name="opt_of_whitelist_ips"]:checked').value;
						if (TurnedONOFF != "1")	{
							if (IPLIST_VALUE.indexOf(user_ip) == -1){
								if(!confirm("YOUR IP(" + user_ip +") is not in list! Are you sure you want to continue?")){return false;}
							}
						}
						return true;
					}
					</script>
				</p> 
	
	
	<?php
	}
								
}
$GLOBALS['SystemEditProtectionzzz'] = new SystemEditRestriction;
?>