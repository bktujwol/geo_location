<?php
/*
 Plugin Name: Geo Location
 Plugin URI: 
 Description: Geo Location plugin to map visitor of the site and block user
 Version: 2.0
 Author: Ujwol Bastakoti
 Author URI:https://ujwolbastakoti.wordpress.com/
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
            //action to register ipinfodb key
            add_action('admin_init', array($this,'gelocation_registerApiKey'));
        endif;
        
        add_action( 'admin_enqueue_scripts', array($this,'geo_location_admin_eneque' ));
        add_action( 'wp_enqueue_scripts', array($this, 'geo_location_front_end_eneque'));
        
        add_action('wp_head', array($this,'gelocation_blockedIp'));
        /*Code to execute javascript on visitor block*/
        add_action( 'admin_footer',  array($this,'block_visitor_from_site_javascript'));
        add_action( 'admin_footer',  array($this,'delete_visitor_from_table_javascript')); 
        //action to handle ajax request
        add_action( 'wp_ajax_block_visitor_from_site', array($this,'block_visitor_from_site'));
        //action to handle ajax request
        add_action( 'wp_ajax_delete_visitor_from_table', array($this,'delete_visitor_from_table'));
        //action to display visitor on modal backend
        add_action( 'admin_footer',  array($this,'display_visitor_map_modal_javascript')); 
        //shortcodes
        add_shortcode('getip', array($this,'geolocation_getVisitorIp'));
        add_shortcode('displaymap',  array($this,'gelocation_displaySiteVisitorMap'));
        
        
        
        //frontend ajax function
        //ajax to return ipinfodb api key
        add_action('wp_ajax_geolocation_api_key', array($this,'geolocation_api_key'));
        add_action('wp_ajax_nopriv_geolocation_api_key', array($this ,'geolocation_api_key'));
        add_action('wp_ajax_geolocation_insert_visitor_info', array($this,'geolocation_insert_visitor_info'));
        add_action('wp_ajax_nopriv_geolocation_insert_visitor_info', array($this ,'geolocation_insert_visitor_info'));
        
    }
    public function geo_location_front_end_eneque(){
        wp_enqueue_script( 'jquery');
        //localize wordpress ajax url
       //var_dump( wp_localize_script( 'geoLocationAjax', 'geolocation_ajax_url', admin_url( 'admin-ajax.php' ) ));
    }
    public function geo_location_admin_eneque(){
        wp_enqueue_script('ctcOverlayScript',plugins_url('js/ctc_overlay.jquery.js',__FILE__), array('jquery'));
        wp_enqueue_style( 'ctcOverlayStyle', plugins_url('css/ctc_overlay_style.css',__FILE__));
    }
    
   
    //function to check if the ip is blocked
    public function gelocation_blockedIp(){
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'];
        $result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}visitorInfo WHERE ip = '{$ip}' AND block = 1;");
        if( $result >= 1):
            if (shortcode_exists( 'getip' ) ):
                wp_die(  " Your IP address has been blocked by site administrator.");
              endif;  
        endif;
    }
   
    public  function geolocation_geoLocationInfo($ip){ 
   
    
    	$apiKey = esc_attr( get_option('infodbApiKey') );
    
    	if(!empty($apiKey)){
        
        	    $url = "http://api.ipinfodb.com/v3/ip-city/?key=$apiKey&format=json&ip=$ip";
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
           if(!is_ssl()): 
            ?>
               <script type="text/javascript">
        		jQuery(document).ready(function(){
        			var geoLocationAjaxUrl = '<?=admin_url('admin-ajax.php')?>';
                    jQuery.post(geoLocationAjaxUrl,{action:'geolocation_api_key'}, function(apiKey){
        				if(apiKey.length>0){
        					   jQuery.get( "http://api.ipinfodb.com/v3/ip-city/?key="+apiKey+"&format=json",function(response){
        						   var data = {action:'geolocation_insert_visitor_info','lat':response.latitude,'long':response.longitude,'ip':response.ipAddress};
        						   				jQuery.post(geoLocationAjaxUrl,data,function(response){ return;});
        						   		});
        						  
        					}
                        });
        			});
               
               </script>
           <?php
           else:
           
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
    
   
    
    //function to add javascript part for delete also chandes color on hover
   public function block_visitor_from_site_javascript(){
       ?>
    
    
    <script type="text/javascript" >

            	jQuery(document).ready(function($){


            		
            		
            
            		/*section that deals with category item delete*/
            		jQuery( "input.geolocation_ip_info").click(function() {
            			var blockId = jQuery(this).attr('id');
            			var blockOption = jQuery(this).attr('value');
            			//if blocked unblock , if not blocked block
            		  	if(blockOption==1){
            		  		var blockConfirm = confirm("Do you want to unblock this IP address!");
            		  		var block = 0;
            			  	}
            		  	else{
            			  	 var blockConfirm = confirm("Do you want to block this IP address!");
            			  	 var block = 1;
            			  	}

            			if(blockConfirm == true){
    
            			    var data = {
                            			'action': 'block_visitor_from_site',
                            			'rowId': blockId,
                            			'block' : block
                            		};
            
            			      jQuery.post(ajaxurl, data, function(response) {
            			    	 
                    				  if(response != 0 ){
                    						
                        				    if(block == 1){
        
                        				    	alert("IP successfully blocked.");
                        						 jQuery("input[id*='"+blockId+"']").removeAttr("value").attr("value","1");
                        						}
                        					else{
                        						alert("IP sucessfully unblocked.");
                        						  jQuery("input[id*='"+blockId+"']").removeAttr("value").attr("value","0");
                        						}	
                    				  }
                    				  else{
                    						alert("For some reason uable to carryout action.");
                    					  }	
             				 
            				});
            			  }
            			});	
			
	});
	</script> 
 <?php 
 }


//handle funtion for ajax delete
public function block_visitor_from_site(){
    
    global $wpdb;
    $table = $wpdb->prefix."visitorInfo";
     echo $wpdb->update($table,array('block'=> $_POST['block']) ,array('id'=>$_POST['rowId']),array('%d'),array('%d')); 
    wp_die(); 
    
}



/*Ajax to deal with delete functionality*/
public function delete_visitor_from_table_javascript() { ?>
	<script type="text/javascript" >

	jQuery(document).ready(function() {

		jQuery("a.geolocation_ip_delete").click(function() {
    		var deleteConfirm = confirm ('This visitor will be deleted');
            if(deleteConfirm == true){
			var rowToDelete = jQuery(this).attr('id');
        			var data = {
                			'action': 'delete_visitor_from_table',
                			'rowId': rowToDelete
                		};
        
            				//script to send ajax request
            			  jQuery.post(ajaxurl, data, function(serverResponse) {
            					if(serverResponse != false){
            						alert("Visitor sucessfully deleted.");
                					
                					jQuery("a[id*='"+rowToDelete+"']").closest("tr").remove();
                					}
            					else{
        								alert("Visitor could not be deleted.");
                					}
            				});
					

                }
			

			});//end of click link function
	});// end of document load function
		</script>
		
	 <?php
}// end of ajax javascript function


//ajax function to delete user
public function delete_visitor_from_table(){
   global $wpdb;
   $table = $wpdb->prefix."visitorInfo";
   $deleteVisitor = $wpdb->delete( $table, array( 'id' => $_POST['rowId'] ), array( '%d' ) );  
   echo( $deleteVisitor);
   wp_die(); 
}



/*Ajax to deal with delete functionality*/
public function display_visitor_map_modal_javascript() { ?>
	<script type="text/javascript" >
	jQuery(document).ready(function(){
		jQuery("a.display_visitor_map_modal").click(function() {
			 var latLong = jQuery(this).attr('data-lat-long');
			jQuery.ctcOverlayEl({elemHeight: '550px',elemWidth:'600px',iframeUrl:'https://www.bing.com/maps/embed?h=550&w=600&cp='+latLong+'&lvl=10&typ=d&sty=r&src=SHELL&FORM=MBEDV8&pp='+latLong+'"&scrolling="no"'});
			jQuery(document).find(".overlayElContainer").css('overflow','hidden');
			});
	});
	</script>


<?php 
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
   //function to return api key to make request
 	public function geolocation_api_key(){
 	    echo get_option('infodbApiKey');
 	    wp_die();
 	}
 
 //function to add ipinfodb API KEY
 public function gelocation_getMapIpdbinfoApiKey(){
  
    ?>
 <div id="api_key_form" style="float:left;display:inline-block;margin-left:25px;width:50%;" >
 <form method="post" action="options.php">
 <?php 
 settings_fields('api_key_group');
 do_settings_sections('api_key_group');
 ?>
 
  <h3><span class="dashicons dashicons-admin-generic"></span>Settings</h3>
         <table class="form-table" style="width:100%;">
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
 </div>
 <?php 
 
?>
 </form>
 
 <?php 
 
 }
 
 
 //fucntion to get total visitors count
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
    // Find total numbers of records
     $limit = 17; // number of rows in page
     $offset = ( $pagenum - 1 ) * $limit;
     $total = self::geolocation_get_visitors_count();
     $num_of_pages = ceil( $total / $limit );
     
     
     $page_links = paginate_links( array(
         'base' => add_query_arg( 'pagenum', '%#%' ),
         'format' => '',
         'prev_text' => __( '&laquo;', 'text-domain' ),
         'next_text' => __( '&raquo;', 'text-domain' ),
         'total' => $num_of_pages,
         'current' => $pagenum
     ) );
  
  
     //to display visitor info in table
     $sql = "SELECT * FROM `{$tableName}` ORDER BY time DESC LIMIT {$offset},{$limit};";
     $result = $wpdb->get_results($sql,ARRAY_A );
     if(!empty($result)):
         ?>
    
         <div id='visitorInfo_table' style="margin-right: 20px;">
         <h2><span class="dashicons dashicons-groups"></span>The Visitor Information List </h2>
         
         <?php if ( $page_links ):?>
             
            <div class="tablenav ctcTablenav" style="margin-right:5px;" >
                                <div class="tablenav-pages ctcTablenav-pages" > <?=$page_links?> </div>
             </div>
            
          </div> 
         <?php endif;?>
           
  
         
         <table  class="wp-list-table widefat fixed striped media" style="text-align:center;margin-top:70px" >
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
    </h2>
    <div class="wrap"  >
<?php  
         if( $active_tab == 'geolocation_api_form' ):?>

                             <?php self::gelocation_getMapIpdbinfoApiKey();?>
                           
                             <div style="float:left; display:inline-block;margin-left:25px;">
                             <h3><span class="dashicons dashicons-megaphone"></span>Information.</h3>
                                <ol>
                             		<li style="font-size:110%;"><b>Insert Short Code to track visitor: [getip].</li>
                             		<li style="font-size:110%;"><b>Insert Short Code to Display Map: [displaymap].</li>
                             		<li style="font-size:110%;"><b>You can check visitor in map in admin area.</li>
                             		<li style="font-size:110%;"><b>Let visitor list load before taking any action.</li>
                             		<li>You can use it without IPinfodb API key,but no location info will be available.</li>
                             		<li>Mark,check boxes to block IP address , in Visitor information list table.</li>
                             		<li>On deactivate plugin, blocked IP will be unblocked.</li>
                             		
                             	</ol>
                             	
                             </div>
                             
         
                  
         <?php else: ?>
                        <div class="wp-list-table widefat fixed striped">
                         <?php  self::gelocation_visitorsInfoTable();?>
                         </div>      
               
               <?php endif;?>
               </div>
               <?php 
}


//function to display map on page
public function gelocation_displaySiteVisitorMap(){
	global $wpdb;
	$tableName = $wpdb->prefix."visitorInfo";
	$sql = "SELECT  `long`, `lat` FROM  `".$tableName."`;";
	$result = $wpdb->get_results($sql,ARRAY_A );	
	$width = '720px';
	$height = '500px';
	
	if(!empty(get_option( 'mapDimensionHeight'))):
	       $height = get_option( 'mapDimensionHeight').'px';
	endif;
	
	if(!empty(get_option( 'mapDimensionWidth'))):
	       $width =get_option( 'mapDimensionWidth').'px';
	endif;
?>

	<script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol?key=<?=get_option('mapApiKey')?>&callback=loadMapScenario' async defer></script>
  
	<div id="myMap" style="position:relative; width:<?=$width?>; height: <?=$height?>; align:center"></div>
	 <script type="text/javascript">
    var latlongs = [
   <?php 
           $i=1;     
          
           foreach ($result as $row): 
           
           if(strlen($row['lat'])>0 || strlen($row['long'])>0 ):
                        if($i<sizeof($result)):
                                ?>
                               	{lat:"<?=$row['lat']?>",lon:"<?=$row['long']?>"},
                                <?php else:?>
                                 {lat:"<?=$row['lat']?>",lon:"<?=$row['long']?>"} 
                                <?php  
                         endif;   
                    endif;
             $i++;
           endforeach;
    ?>
      ];

		var map = null;
        function getMap(){
          	  map = new Microsoft.Maps.Map(document.getElementById('myMap'), {
        	  																	center: new Microsoft.Maps.Location(34, -4),
																			     zoom:2,
																			     mapTypeId: Microsoft.Maps.MapTypeId.<?php if(!empty(get_option('mapType'))): echo get_option('mapType');else: echo 'road';endif;?>,
																			     supportedMapTypes: [Microsoft.Maps.MapTypeId.road, Microsoft.Maps.MapTypeId.aerial, Microsoft.Maps.MapTypeId.canvasLight] });
																			 																			     
                   
              
          for(var i in latlongs){
        	    var pin = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(latlongs[i].lat, latlongs[i].lon),{ text:'.' });
        	    map.entities.push(pin);
          }  
        }
        document.getElementsByTagName("body")[0].setAttribute('onload', 'getMap()');
     
  </script>
	
<?php 	
}





}

new geloLoaction();
