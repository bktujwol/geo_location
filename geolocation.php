<?php
/*
 Plugin Name: Geo Location
 Plugin URI: 
 Description: Geo Location plugin to map visitor of the site and block user
 Version: 2.5.0
 Author: Ujwol Bastakoti
 Author URI:https://ujwolbastakoti.wordpress.com/
 text-domain: geo-location
 License: GPLv2
 */

class geloLoaction{
    
    public function __construct(){

        self::addWpActionAndShortcode();
        self::pluginADU();
        
    }
    
    private function addWpActionAndShortcode(){
        
        //for admin panel activities
        if ( is_admin() ):

            /* Call the html code */
            add_action('admin_menu', array($this,'geolocation_geoLocationAdminMenu'));
            add_action('admin_init', array($this,'gelocation_registerApiKey'));
            add_action( 'admin_enqueue_scripts', array($this,'geo_location_admin_eneque' )); 

        endif;
       
        add_action( 'wp_enqueue_scripts', array($this, 'geo_location_front_end_eneque'));
        add_filter('script_loader_tag', array($this,'geoLocation_addAsyncAttribute'), 20, 2);
        add_action('wp_head', array($this,'gelocation_blockedIp'));
       
        //actions to handle ajax request on admin section
        add_action( 'wp_ajax_block_visitor_from_site', array($this,'block_visitor_from_site'));
        add_action( 'wp_ajax_delete_visitor_from_table', array($this,'delete_visitor_from_table'));
      
        //shortcodes
        add_shortcode('getip', array($this,'geolocation_getVisitorIp'));
        add_shortcode('displaymap',  array($this,'gelocation_displaySiteVisitorMap'));
        
        //frontend ajax function
        add_action('wp_ajax_geolocation_insert_visitor_info', array($this,'geolocation_insert_visitor_info'));
        add_action('wp_ajax_nopriv_geolocation_insert_visitor_info', array($this ,'geolocation_insert_visitor_info'));
        
    }
    public function geoLocation_addAsyncAttribute($tag, $handle) {
        if ( 'geoLocationBingMap' === $handle ):    
            return str_replace( ' src', '  async defer src ', $tag );
        endif;   
        return $tag;
    }

     //Script and styles for backend 
    public function geo_location_front_end_eneque(){
        if( !empty( get_option('mapApiKey' ) ) ) :
                wp_enqueue_script('geoLocationBingMap',"https://www.bing.com/api/maps/mapcontrol?callback=GetMap" );
                wp_enqueue_script('geoLocationFrontendJs',plugins_url('js/frontend_geolocation.js',__FILE__), array('jquery','geoLocationBingMap'),'', true);

                if( !is_ssl() ): 
                    wp_localize_script( 'geoLocationFrontendJs', 'geolocation_params', array(
                                                                                        'no_ssl' => 'true',
                                                                                        'ipinfodb_apiKey' =>get_option('infodbApiKey'),
                                                                                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                                                                                        'bing_map_key' => get_option('mapApiKey'),
                                                                                        'bing_map_type' => get_option('mapType'),
                                                                                        'bing_map_visitors' => $this->geolocation_getVisitorList(),
                                                                                    ));
                else:
                    wp_localize_script( 'geoLocationFrontendJs', 'geolocation_params', array(
                                                                                        'bing_map_key' => get_option('mapApiKey'),
                                                                                        'bing_map_visitors' => $this->geolocation_getVisitorList(),
                                                                                        'bing_map_type' => get_option('mapType'),
                    ));                                                                    
                endif;
            endif;
    }

    //Script and styles for backend 
    public function geo_location_admin_eneque(){
       
                wp_enqueue_script('geoLocationBackendBingMap',"https://www.bing.com/api/maps/mapcontrol?callback=GetMap" );
                wp_enqueue_script('geoLocationBackendJs',plugins_url('js/backend_geolocation.js',__FILE__), array('jquery','geoLocationBackendBingMap'),'',true);
                wp_localize_script( 'geoLocationBackendJs', 'geolocation_backend_params', array(
                                                                                        'bing_map_key' => get_option('mapApiKey'), 
                                                                                        'bing_map_visitors' => $this->geolocation_getVisitorList(),
                                                                                        'bing_map_type' => get_option('mapType'),
                                                                                        'block_confirm' => __("Do you want to block this IP address?",'geo-location'),
                                                                                        'unblock_confirm' => __("Do you want to unblock this IP address?",'geo-location'),
                                                                                        'ip_blocked'=> __("IP successfully blocked.",'geo-location'),
                                                                                        'ip_unblocked'=> __("IP sucessfully unblocked",'geo-location'),
                                                                                        'action_error' => __('For some reason uable to carryout action.','geo-location'),
                                                                                        'visitor_deleted' => __('Visitor sucessfully deleted.','geo-location'),
                                                                                        'visitor_not_deleted' => __("Visitor could not be deleted.",'geo-location'),
                                                                                        'visitor_delete_confirm' => __("This visitor will be deleted.",'geo-location'),

                                                                                        
                )); 
            

            wp_enqueue_script('ctcOverlayScript',plugins_url('js/ctc_overlay.jquery.js',__FILE__), array('jquery'));
            wp_enqueue_style( 'ctcOverlayStyle', plugins_url('css/ctc_overlay_style.css',__FILE__)); 
    }
    
   
    //function to check if the ip is blocked
    public function gelocation_blockedIp(){
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'];
        $result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}visitorInfo WHERE ip = '{$ip}' AND block = '1';");
        if( $result === '1'):
            if (shortcode_exists( 'getip' ) ):
                wp_die(  __(" Your IP address has been blocked by site administrator.",'geo-location'));
              endif;  
        endif;
    }
   
    public  function geolocation_geoLocationInfo($ip){ 
   
    
    	$apiKey = esc_url( get_option('infodbApiKey') );
    
    	if(!empty($apiKey)){
        
        	    $url = "http://api.ipinfodb.com/v3/ip-city/?key={$apiKey}&format=json&ip={$ip}";
        	    $info = json_decode(file_get_contents($url));
        	    $position = array('longitude'=>$info->{'longitude'}, 'latitude'=>$info->{'latitude'});
        
        	    return $position;
        	}
        	else{
            
            	    return null;
            	}
            
            
           	}
    
    //insert visitor information to database
    public function geolocation_getVisitorIp(){
        
   if(!empty(get_option('infodbApiKey'))):
           if(is_ssl()): 
                   global $wpdb;
                 	   $ip = $_SERVER['REMOTE_ADDR'];
                       $time = current_time('timestamp');
                       $location = $this->geolocation_geoLocationInfo($ip);
                       $tableName = $wpdb->prefix."visitorInfo";
                   	    if(!is_null($location)):
                               $sql = "INSERT INTO $tableName (`time`, `ip`, `long`, `lat`, `visitCount`) VALUES(".$time.", '".$ip."', ".$location['longitude'].",".$location['latitude'].",1) ON DUPLICATE KEY UPDATE time=".$time.", visitCount = visitCount+1 ;";
                       	        $wpdb->query($sql);
                       	 else:
                           	$sql = "INSERT INTO $tableName (`time`, `ip`,`long`, `lat`, `visitCount`) VALUES(".$time.", '".$ip."',0,0,1) ON DUPLICATE KEY UPDATE time=".$time.", visitCount = visitCount+1 ;";
                            $wpdb->query($sql);
                        endif;      
           
                 endif;
   else:
        self::geolocation_insert_no_api_key();
   endif;
    }
    
    public function geolocation_insert_no_api_key(){
        
        global $wpdb;
        $time = current_time('timestamp');
        $ip = $_SERVER['REMOTE_ADDR'];
        $tableName = $wpdb->prefix."visitorInfo";
        $sql = "INSERT INTO $tableName (`time`, `ip`,`long`, `lat`, `visitCount`) VALUES(".$time.", '".$ip."',0,0,1) ON DUPLICATE KEY UPDATE time=".$time.", visitCount = visitCount+1 ;";
        $wpdb->query($sql);
          
    }
    
    
    public function geolocation_insert_visitor_info(){
    global $wpdb;
            $time = current_time('timestamp');
            $longitude = $_POST['long'];
            $latitude = $_POST['lat'];
            $ip = $_POST['ip'];

            $sql=  "INSERT INTO {$wpdb->prefix}visitorinfo (`time`, `ip`, `long`, `lat`, `visitCount`) VALUES({$time}, %s,%s,%s,1) ON DUPLICATE KEY UPDATE time={$time}, visitCount = visitCount+1 ;";
            $wpdb->query( $wpdb->prepare($sql,array($ip, $longitude, $latitude)));

            
            wp_die();
    }
    
    private function pluginADU(){
        register_activation_hook(__FILE__,array($this,'geolocation_geoLocationInstall')); 
        register_deactivation_hook(__FILE__, array($this,'geolocation_gelocationDeactivate'));
        register_uninstall_hook(__FILE__,'geolocation_geoLocationRemove');  
    }
   
    public function geolocation_geoLocationInstall(){
        global $wpdb;
        delete_option( 'my_option' );
        
        $tableName = $wpdb->prefix."visitorInfo";
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE `". $tableName."`(
 			    `id` mediumint(9) NOT NULL AUTO_INCREMENT,
 			    `time` varchar(15) NOT NULL,
 			    `ip` varchar(15) NOT NULL,
 			    `long` varchar(15),
 			    `lat` varchar(15),
                `visitCount` int(255),
                `block` mediumint(9) NOT NULL,
 			 PRIMARY KEY (`id`),
             UNIQUE KEY (`ip`))".$charset_collate.";";
       
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
    }
    //function to remove shortcode from header on deactivation
    public function geolocation_gelocationDeactivate(){
        remove_action( 'wp_header', 'geolocation_getVisitorIp');
    }
    
    
    //function to delete  table
   public  function geolocation_geoLocationRemove(){
        global $wpdb;
        unregister_setting( 'api_key_group', 'infodbApiKey');
        unregister_setting( 'api_key_group', 'mapApiKey');
        unregister_setting( 'api_key_group', 'mapDimensionHeight');
        unregister_setting( 'api_key_group', 'mapDimensionWidth');
        unregister_setting( 'api_key_group', 'mapType');
        $wpdb->query("DROP TABLE {$wpdb->prefix}visitorInfo;");        
    }
    
   
//handle funtion for ajax delete
public function block_visitor_from_site(){

    global $wpdb;
    $table = $wpdb->prefix."visitorInfo";
     echo $wpdb->update($table,array('block'=> $_POST['block']) ,array('id'=>$_POST['rowId']),array('%d'),array('%d')); 
    wp_die(); 
    
}



//ajax function to delete user
public function delete_visitor_from_table(){
   global $wpdb;
   $table = $wpdb->prefix."visitorInfo";
   $deleteVisitor = $wpdb->delete( $table, array( 'id' => $_POST['rowId'] ), array( '%d' ) );  
   echo( $deleteVisitor);
   wp_die(); 
}


 	public function geolocation_geoLocationAdminMenu() {
 	    add_menu_page('IP Geo Location', 
 	                  'IP Geo Location', 
 	                  'administrator',
 	                  'geoLocation', 
 	                  array($this,'gelocation_geoLocationHtmlPage'),
 	                  'dashicons-admin-site'
                    );

 	}
 	
 	
 	
 	public function gelocation_registerApiKey() { // whitelist options
 	    register_setting( 'api_key_group', 'infodbApiKey');
 	    register_setting( 'api_key_group', 'mapApiKey');
 	    register_setting( 'api_key_group', 'mapDimensionHeight');
 	    register_setting( 'api_key_group', 'mapDimensionWidth');
 	    register_setting( 'api_key_group', 'mapType');
 	  
     }
  
 //function to add ipinfodb and bing API KEY
 public function gelocation_getMapIpdbinfoApiKey(){
  
    ?>
    
 <div id="api_key_form" style="float:left;display:inline-block;margin-left:25px;width:50%;" >
 <fieldset style="border:2px dotted rgba(0,0,0,0.8);">
  <legend align="center"><h3 class="dashicons-before dashicons-admin-generic">Settings</h3></legend>
 <form method="post" action="options.php">
 <?php 
 settings_fields('api_key_group');
 do_settings_sections('api_key_group');
 ?>

         <table class="form-table" style="width:100%; veritcal-align:middle;margin-left:50px;">
                 <tr valign="top">
                 <td scope="row">InfoDB API Key : </td>
                 <td><input type="text" name="infodbApiKey" size="45" value="<?=get_option('infodbApiKey')?>" /></td>
                 </tr>

                 <tr valign="top">
                 <td scope="row">Bing Map API Key : </td>
                 <td><input type="text" name="mapApiKey" size="45" value="<?=get_option('mapApiKey')?>" /></td>
               
                 <tr>
                   <td scope="row">Map Dimension <i>(in px)</i>: </td>
                   <td>
                      Height : <input type="number" min="0" style="width:50px" name="mapDimensionHeight" value="<?=get_option('mapDimensionHeight')?>" />
                  	  X Width  : <input type="number" min="0" style="width:50px" name="mapDimensionWidth" value="<?=get_option('mapDimensionWidth')?>" />
                  	  </td>
                 </tr>
                
                   <tr>
                   <td scope="row">Map Type : </td>
                 
                          <?php
                          
                            switch(get_option('mapType')):
                            case 'road':
                              $road = 'selected'; 
                             break;   
                            case 'aerial':
                                $aerial = 'selected'; 
                                break;
                                
                            case 'canvasLight':
                                $canvas  = 'selected';
                                break;
                              endswitch;  
                          ?>
                     <td>     
                  <select  name="mapType">
  						<option <?php if(!empty($road)):echo $road; endif;?> value="road">Road</option>
  						<option <?php if(!empty($aerial)):echo $aerial;endif;?> value="aerial">Aerial</option>
  						<option <?php if(!empty($canvas)):echo $canvas;endif;?> value="canvasLight">Canvas Light</option>
  					</select>	
  
                  	  
                  </td>
                 </tr>
                  <tr valign="top"><td><?php submit_button(); ?></td><td></td></tr>
                 
         </table>
    </form>
 </div>
<fieldset>
<fieldset style="border:2px dotted rgba(0,0,0,0.8);float:left; display:inline-block;margin-left:15px;padding:40px;">
  <legend align="center"><h3 class="dashicons-before dashicons-megaphone">Information</h3></legend>
 <div style="">
 
                                <ol style="font-size:14px;">
                             		<li><b>Insert Short Code to track visitor: <font style="font-size:18px;"> [getip] </font>.</li>
                             		<li ><b>Insert Short Code to Display Map: <font style="font-size:18px;">[displaymap]</font>.</li>
                             		<li ><b>You can check visitor in map in admin area.</li>
                             		<li ><b>Let visitor list load before taking any action.</li>
                             		<li>You can use it without IPinfodb API key,but no location info will be available.</li>
                             		<li>Mark,check boxes to block IP address , in Visitor information list table.</li>
                             		<li>On deactivate plugin, blocked IP will be unblocked.</li>
                             		
                             	</ol>
                         
                             </div>
                             </fieldset> 	        
 
 <?php 
 
 }
 
 
 //function to get total visitors count
 public function geolocation_get_visitors_count(){
     global $wpdb;
     return $wpdb->get_var( "SELECT COUNT(`id`) FROM  `{$wpdb->prefix}visitorInfo`;" );
 }
 
//function to generate visitor location on table
 public function gelocation_visitorsInfoTable(){
    global $wpdb;
     $tableName = $wpdb->prefix."visitorInfo";
     /*Pagintions of the visitor info*/
     $pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
     $limit = 17; 
     $offset = ( $pagenum - 1 ) * $limit;
     $total = self::geolocation_get_visitors_count();
     $num_of_pages = ceil( $total / $limit );
     
     
     $page_links = paginate_links( array(
         'base' => add_query_arg( 'pagenum', '%#%' ),
         'format' => '',
         'prev_next'          => true,
	     'prev_text'          => __('« Previous'),
         'next_text'          => __('Next »'),
         'total' => $num_of_pages,
         'current' => $pagenum
     ) );
  
  
     //to display visitor info in table
     $sql = "SELECT * FROM `{$tableName}` ORDER BY time DESC LIMIT {$offset},{$limit};";
     $result = $wpdb->get_results($sql,ARRAY_A );
     if(!empty($result)):
         ?>
    <div class="wp-list-table widefat fixed striped">
         <div id='visitorInfo_table' style="margin-right: 20px;">
         <h2><span class="dashicons dashicons-groups"></span>The Visitor Information List </h2>
         
         <?php if ( $page_links ):?>
            <div class="tablenav" style="margin-right:5px;" >
                <div class="tablenav-pages" > <?=$page_links?> </div>
            </div>
            
         <?php endif;?>
           
  
         
         <table  class="wp-list-table widefat fixed striped media" style="text-align:center;margin-top:20px" >
             <thead>
             <tr >
             <td scope="col" class="manage-column column-title column-primary sortable desc ctcProductColumn" style="text-align:center;font-weight:bold;width:20%;">
             	IP Address
             </td>
             <td scope="col" class="manage-column column-title column-primary sortable desc ctcProductColumn" style="text-align:center;font-weight:bold; width:20%;">
             Last Visit On
             </td>         
             <td scope="col" class="manage-column column-title column-primary sortable desc ctcProductColumn" style="text-align:center;font-weight:bold; width:10%;">
             Map It
             </td>
             <td scope="col" class="manage-column column-title column-primary sortable desc ctcProductColumn" style="text-align:center;font-weight:bold; width:10%;">
             Visit Count
             </td>
             <td scope="col" class="manage-column column-title column-primary sortable desc ctcProductColumn" style="text-align:center;font-weight:bold; width:10%;">
             Delete
             </td>
             <td scope="col" class="manage-column column-title column-primary sortable desc ctcProductColumn" style="text-align:center;font-weight:bold; width:10%;">
             Block It!
             </td>
             </tr>
           </thead>
       <tbody>
        <?php  
       
         foreach ($result as $row):     
    ?>
             <tr >
              <td>
              	<?=$row['ip']?>
              </td>
             <td>
             	<?=gmdate( "m/d/y - g:i A", $row['time']  )?>
             </td>
   
             <td>
                <?php if( !empty($row['lat']) && !empty($row['long'])):?>
             	<a  href="JavaScript:void(0);" class="display_visitor_map_modal" data-lat-long="<?=$row['lat'].'~'.$row['long']?>">
             		<span class="dashicons dashicons-location-alt"></span>
             		</a>
             	<?php else:?>	
             	<span class="dashicons dashicons-location-alt" title="no location info avilable"></span>
             	<?php endif;?>
             </td>
             <td>
             	<?=$row['visitCount']?>
             </td>
             <td>
             		<a id=<?=$row['id']?> href="JavaScript:void(0);" class="geolocation_ip_delete dashicons dashicons-no" style="color:red;"></a>
             </td>

             <?php 
             if($row['block']== 1):
                 $ipblocked = 'value="'.$row['block'].'" checked';
             else:
                 $ipblocked = 'value = "'.$row['block'].'"';
             endif;
             ?>
             <td> <input id="<?=$row['id']?>"  class="geolocation_ip_info" type="checkbox" name="block_ip"  <?=$ipblocked?> /></tr>
         <?php endforeach;?>
         </tbody>
          	</table>
        </div>
       
  
         
     <?php else:?>
        <p><span class="dashicons dashicons-flag"></span>No visitors has been tracked yet.</p>
     <?php endif; 
   
 }
 
//The admin panel html display

 public function gelocation_geoLocationHtmlPage() {

 ?>
     <h2><span class="dashicons dashicons-admin-site"></span>Geo Location</h2>
 
 <?php 
 if( isset( $_GET[ 'tab' ] ) ):
     $active_tab = $_GET[ 'tab' ];
 endif;
 
 $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'geolocation_api_form';
 ?>
    
    <h2 class="nav-tab-wrapper">
    <a href="?page=geoLocation&tab=geolocation_api_form" class="nav-tab <?php echo $active_tab == 'geolocation_api_form' ? 'nav-tab-active' : ''; ?> ">API Information</a>
    <a href="?page=geoLocation&tab=geolocation_visitor_list" class="nav-tab <?php echo $active_tab == 'geolocation_visitor_list' ? 'nav-tab-active' : ''; ?>">The Visitor Info List 
    <span title="total unique visitors" style="box-shadow: 2px 2px 3px rgba(0,0,0,0.8);padding:3px;text-alin:center;color:white;border-radius:5%;background-color: rgba(147, 83, 196, 0.8);"><?=self::geolocation_get_visitors_count()?></span></a>
     <?php if(!empty(get_option('mapApiKey')) ): ?>
      <a href="?page=geoLocation&tab=geolocation_visitors_map" class="nav-tab <?php echo $active_tab == 'geolocation_visitors_map' ? 'nav-tab-active' : ''; ?>"> Map Preview  </a>
    <?php endif;?>
      </h2>
    <div class="wrap"  >
<?php  
         if( $active_tab == 'geolocation_api_form' ):
            self::gelocation_getMapIpdbinfoApiKey();
        elseif( $active_tab == 'geolocation_visitors_map' ):
            $width = !empty( get_option( 'mapDimensionWidth') ) ? (get_option( 'mapDimensionWidth' )+20).'px' :'930px';
            echo '<fieldset style="border:2px dotted rgba(0,0,0,0.8);">';
            echo '<legend align="center" ><h3 class="dashicons-before dashicons-admin-site"  > Visitors On Map Preview </h3></legend>';
            echo '<div style="padding:30px; margin-bottom:40px; margin-top:20px;margin-left:auto;margin-right:auto;display:block;border : 1px solid rgba(0,0,0,0.7);width:'.$width.';  ">';
                self::gelocation_displaySiteVisitorMap();    
             echo '</div></fieldset>';    
        else: 
            self::gelocation_visitorsInfoTable();
        endif;
     ?>                  
    </div>
<?php 
}

//function to get visitor list for map
public function geolocation_getVisitorList(){
    global $wpdb;
    $tableName = $wpdb->prefix."visitorInfo";
	$sql = "SELECT  `long`, `lat`,`visitCount` FROM  `".$tableName."`;";
    $result = $wpdb->get_results($sql,ARRAY_A );
    
    return json_encode($result);
}

//function to display map on page
public function gelocation_displaySiteVisitorMap(){

 
    $height = !empty( get_option( 'mapDimensionHeight') )? get_option( 'mapDimensionHeight' ).'px' :'550px';
     $width = !empty( get_option( 'mapDimensionWidth') ) ? get_option( 'mapDimensionWidth' ).'px' :'920px';
     
     $subTab = isset( $_GET['tab']) ? $_GET['tab'] :'';
    
     if( $subTab === 'geolocation_visitors_map' || !is_admin() ):  
?>
	<div id="myMap" class="geolocation_visitorMap" style="position:relative; width:<?=$width?>; height: <?=$height?>; margin-left:auto;margin-right:auto;display:block;"></div>	
<?php 	
    endif;
}

}

new geloLoaction();
