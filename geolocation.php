<?php
/*
Plugin Name: Geo Location
Plugin URI: https://ujwol.000webhostapp.com/display-map
Description: Geo Location plugin to map visitor of the site
Version: 1.0
Author: Ujwol Bastakoti
Author URI: 
License: GPLv2 
*/


add_action('wp_head', 'gelocation_blockedIp');
 
 //get latitude and longitube from ip
 function geolocation_geoLocationInfo($ip)
 {  global $wpdb;
 	//$ip = $_SERVER['REMOTE_ADDR'];
 	
 	
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
 
//function to check if the ip is blocked
 function gelocation_blockedIp($ip){
     global $wpdb;
     $ip = $_SERVER['REMOTE_ADDR'];
   
     $tableName = $wpdb->prefix."visitorInfo";
     $sql = 'SELECT COUNT(ip) FROM '.$tableName.' WHERE ip ="'.$ip.'" AND block = 1;';  
    
     $myrows = $wpdb->get_results($sql, ARRAY_N);
     $ifblock = intval($myrows[0][0]);

     if($ifblock >= 1){
         
         if (shortcode_exists( 'getip' ) ) { 
             echo " Your IP address has been blocked by site administrator.";
             exit();
         }
     }
     
 }//end of function
 
 
 //insert visitor information to database
 function geolocation_getVisitorIp()
 {
 	global $wpdb;
 	$ip = $_SERVER['REMOTE_ADDR'];
 	$time = current_time('timestamp');
 
 	 // gelocation_blockedIp($ip);
 	
         	//call function to get latitude and longitude
         	$location = geolocation_geoLocationInfo($ip);
         	$tableName = $wpdb->prefix."visitorInfo";
         	if(!is_null($location)){
         	                  $wpdb->insert($tableName, 
                 	 				array('time'=>$time,'ip'=>$ip, 'long'=>$location['longitude'],'lat'=>$location['latitude'] ),
                 	 				array('%s','%s')
                 	);
         	}
         	else{
         	    
         	   
         	    $wpdb->insert($tableName, array('time'=>$time,'ip'=>$ip));
         	}

 }
 add_shortcode('getip', 'geolocation_getVisitorIp');
 
 
 /*run when you register plugin */
 register_activation_hook(__FILE__,'geolocation_geoLocationInstall');

 /*run when plugin is deactivated*/
 register_deactivation_hook(__FILE__, 'geolocation_gelocationDeactivate');
 /*runs when you remove plugin */
 register_uninstall_hook(__FILE__,'geolocation_geoLocationRemove');
 //function to add table
 function geolocation_geoLocationInstall()
 {
 
 
 	global $wpdb;
 	delete_option( 'my_option' );
 	
 	$tableName = $wpdb->prefix."visitorInfo"; 
 	
 
 $sql = "CREATE TABLE `". $tableName."`(
 			`id` mediumint(9) NOT NULL AUTO_INCREMENT,
 			`time` varchar(15) NOT NULL,
 			`ip` varchar(15) NOT NULL,
 			`long` varchar(15),
 			`lat` varchar(15),
            `block` mediumint(9) NOT NULL,
 			PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
 	
    $wpdb->query($sql);
 	
 
 }
 //function to remove shortcode from header on deactivation
 function geolocation_gelocationDeactivate(){
     remove_action( 'wp_header', 'geolocation_getVisitorIp');
     
 }
 

 //function to delete  table
function geolocation_geoLocationRemove() {
 	/* Deletes the database field */
    global $wpdb;
    unregister_setting( 'api_key_group', 'infodbApiKey');
    unregister_setting( 'api_key_group', 'mapApiKey');
    
    unregister_setting( $option_group, $option_name, $sanitize_callback );
    
    $tableName = $wpdb->prefix."visitorInfo"; 
    $sql = "DROP TABLE ".$tableName.";";

   $wpdb->query($sql);

 }

/*Code to execute javascript on visitor block*/
 add_action( 'admin_footer', 'block_visitor_from_site_javascript' ); // Write our JS below here
 
 //function to add javascript part for delete
 function block_visitor_from_site_javascript(){
     
     wp_enqueue_script( 'jquery');
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
	

		/* section that deals with porfolio item item*/

		
	});
	</script> 
 <?php 
 }//end of ajax javasript function
 //add action to handle ajax request
add_action( 'wp_ajax_block_visitor_from_site', 'block_visitor_from_site' );

//handle funtion for ajax delete
function block_visitor_from_site(){
    
    global $wpdb;
     
    //var_dump( intval($_POST['rowId']));
    $table = $wpdb->prefix."visitorInfo";
     
    $update = $wpdb->update($table,array('block'=> $_POST['block']) ,array('id'=>$_POST['rowId']),array('%d'),array('%d')); 
     var_dump($update);
     exit();
        echo $update;
    
    
    wp_die(); // this is required to terminate immediately and return a proper response
    
}



/*Ajax to deal with delete functionality*/

add_action( 'admin_footer', 'delete_visitor_from_table_javascript' ); // Write our JS below here

function delete_visitor_from_table_javascript() { ?>
	<script type="text/javascript" >

	jQuery(document).ready(function($) {

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

//add action to handle ajax request
add_action( 'wp_ajax_delete_visitor_from_table', 'delete_visitor_from_table' );

function delete_visitor_from_table(){
   global $wpdb;
   //print_r($_POST);
   //exit();
   $table = $wpdb->prefix."visitorInfo";
   $deleteVisitor = $wpdb->delete( $table, array( 'id' => $_POST['rowId'] ), array( '%d' ) );
   
   echo( $deleteVisitor);
    
}//end of function to ajax request










 
//for admin panel activities
if ( is_admin() ){
 
 	/* Call the html code */
 	add_action('admin_menu', 'geolocation_geoLocationAdminMenu');
 	
 	

 	function geolocation_geoLocationAdminMenu() {

 	    //add__page('My Custom Page', 'My Custom Page', 'manage_options', 'my-top-level-slug');
 	    add_menu_page('IP Geo Location', 'IP Geo Location', 'administrator','geoLocation', 'gelocation_geoLocationHtmlPage','dashicons-admin-plugins');
 	    
 	}
 	
 	//action to register ipinfodb key
 	add_action('admin_init','gelocation_registerApiKey');
 	
 	function gelocation_registerApiKey() { // whitelist options
 	    register_setting( 'api_key_group', 'infodbApiKey');
 	    register_setting( 'api_key_group', 'mapApiKey');
 	   
 	  
 	}
 	

 }
 
 
 //function to add ipinfodb API KEY
 function gelocation_getMapIpdbinfoApiKey(){
    global $wpdb;
 echo '<div id="api_key_form">';
 echo  '<form method="post" action="options.php">';
 
 settings_fields('api_key_group');
 do_settings_sections('api_key_group');
 ?>
  <h3><span class="dashicons dashicons-admin-network"></span>Please insert API keys below</h3>
 <table class="form-table" style="width:100%;">
 <tr valign="top">
 <th scope="row">InfoDB API Key</th>
 </tr>
 <tr valign="top">
 <td><input type="text" name="infodbApiKey" size="85" value="<?php echo esc_attr( get_option('infodbApiKey') );  ?>" /></td>
 </tr>
 <tr valign="top">
 <th scope="row">Bing Map API Key</th>
 </tr>
 <tr valign="top">
 <td ><input type="text" name="mapApiKey" size="85" value="<?php echo esc_attr( get_option('mapApiKey') );  ?>" /></td>
 </tr>

  <tr valign="top"><td><?php submit_button(); ?></td><td></td></tr>
 
 </table>
 
 <?php 
 

 echo '</form>';
 echo '</div>';
 
 }
 
//function to generate visitor location on table
 function gelocation_visitorsInfoTable($wpdb){
     add_thickbox();
     
     $tableName = $wpdb->prefix."visitorInfo";
     /*Pagintions of the visitor info*/
     
     $pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
    // Find total numbers of records
     
     $limit = 15; // number of rows in page
     $offset = ( $pagenum - 1 ) * $limit;
     $total = $wpdb->get_var( "SELECT COUNT(`id`) FROM  `".$tableName."`;" );
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
     
     $sql = "SELECT * FROM `".$tableName."` ORDER BY time DESC LIMIT ".$offset.",".$limit.";";
     

     $result = $wpdb->get_results($sql,ARRAY_A );
     
   
     if(!empty($result))
     {
       

         echo "<div id='visitorInfo_table'>"; 
         echo '<table height="10%" width="90%" valign="top">';
          echo '<tr>';
         echo '<td><h2><span class="dashicons dashicons-groups"></span>The Visitor Information List </h2></td>';
         
         if ( $page_links ) {
             echo '<td align="right">';
             echo '<div class="tablenav style="margin-left:10px;  position: absolute !important; margin-bottom:-50px!important;">';
             echo'<div class="tablenav-pages" style="margin: 1em 0;">' . $page_links . '</div></div>';
             echo '</td>';
         }
         echo'</tr>';
         echo '</table>';
         
         echo '<table width="98.5%" height="98%" >';
         
         echo '<tr bgcolor="white"  ><th style="text-align:center">No</th><th style="text-align:center">Time</th><th style="text-align:center">IP</th><th style="text-align:center">Map It</th><th style="text-align:center">Delete</th><th style="text-align:center">Block It!</th></tr>';
         
         $i =1;
         foreach ($result as $row)
         {
             $sNo =$i++;
             
             echo '<div id="my-content-id-'.$sNo.'" style="display:none;">';
             
             echo '<div>';
             echo '<iframe width="600" height="550" frameborder="0" src="https://www.bing.com/maps/embed?h=550&w=600&cp='.$row['lat'].'~'.$row['long'].'&lvl=10&typ=d&sty=r&src=SHELL&FORM=MBEDV8&pp='.$row['lat'].'~'.$row['long'].'"&scrolling="no">';
             echo '</iframe>';
             echo '</div>';
             
             echo '<div style="white-space: nowrap; text-align: center; width: 600px; padding: 6px 0;">';
        
             echo '</div>';
             echo'</div>';
             
             echo '</div>';
             
             
             echo '<tr style="cursor: default;"  align="center" bgcolor="#D3D3D3" onMouseOver="this.style.backgroundColor=\'#C0C0C0\'" onMouseOut="this.style.backgroundColor=\'#D3D3D3\'">';
             echo '<td>'.$sNo.'</td><td>'. gmdate( "m/d/y - g:i A", $row['time']  ).'</td><td>'.$row['ip'].'</td><td><a href="#TB_inline?width=600&height=575&inlineId=my-content-id-'.$sNo.'" class="thickbox"><span class="dashicons dashicons-location-alt"></span></a></td>';
             echo '<td><a id="'.$row['id'].'" href="#" class="geolocation_ip_delete"><span class="dashicons dashicons-no" style="color:red;"></span></a></td>';
             
           
             
             if($row['block']== 1){
                 
                 $ipblocked = 'value="'.$row['block'].'" checked';
             }
             else{
                 $ipblocked = 'value = "'.$row['block'].'"';
             }
             
             echo  '<td> <input id="'.$row['id'].'"  class="geolocation_ip_info" type="checkbox" name="block_ip "'.$ipblocked.' ></input></tr>';
             
         }
         echo "</table>";
        
         
         echo "</div>";
       
  
         
     }
     else{
         
        echo '<p><span class="dashicons dashicons-flag"></span>No vistors has been tracked yet.</p>';
     }
   
 }
 
//The admin panel html display

 function gelocation_geoLocationHtmlPage() 
{
 global $wpdb;	
 echo '<h2><span class="dashicons dashicons-admin-site"></span>Geo Location</h2>';
 
 
 if( isset( $_GET[ 'tab' ] ) ) {
     $active_tab = $_GET[ 'tab' ];
 } //
 
 $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'geolocation_api_form';
 ?>
    
    <h2 class="nav-tab-wrapper">
    <a href="?page=geoLocation&tab=geolocation_api_form" class="nav-tab <?php echo $active_tab == 'geolocation_api_form' ? 'nav-tab-active' : ''; ?> ">API Information</a>
    <a href="?page=geoLocation&tab=geolocation_visitor_list" class="nav-tab <?php echo $active_tab == 'geolocation_visitor_list' ? 'nav-tab-active' : ''; ?>">The Visitor Info List </a>
    </h2>
<?php  
         if( $active_tab == 'geolocation_api_form' ) {
                     
                     //admin panel tab for porfolio item
                            
                             echo '<div class="wrap" style="float:left; ">';  
                             gelocation_getMapIpdbinfoApiKey();
                             echo "</div>";
                             echo '<div style="float:right; margin-right:200px;">';
                             echo '<h3><span class="dashicons dashicons-megaphone"></span>Information.</h3>';
                             echo '<ol>';
                             echo '<li style="font-size:110%;"><b>Insert Short Code to track visitor:&nbsp;&nbsp;[getip].</b></li>';
                             echo '<li style="font-size:110%;"><b>Insert Short Code to Display Map: &nbsp;&nbsp;[displaymap].</b></li>';
                             echo '<li>Check , check boxes to block IP address , in Visitor information list table.So - </li>';
                             echo '<li>Despite deactivating plugin, blocked IPs will still be blocked.<br/>';
                              echo ' unblock them from vistor list if you wish to unblock them.</li>';
                             echo "</div>";
         
         } 
         
         else { 
                        echo '<div class="wp-list-table widefat fixed striped">';
                         $visitor_list = gelocation_visitorsInfoTable($wpdb);
                         echo '</div>';      
               
                }
}//end of function


//function to display map on page
function gelocation_displaySiteVisitorMap()
{
	global $wpdb;
	$tableName = $wpdb->prefix."visitorInfo";
	$sql = "SELECT DISTINCT `long`, `lat` FROM  `".$tableName."`;";
	//$sql = "SELECT * FROM  `".$tableName."`;";
	$result = $wpdb->get_results($sql,ARRAY_A );
	
	
	
?>
	<!--<script type="text/javascript" src="http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0"></script>-->
	<script type='text/javascript' src='https://www.bing.com/api/maps/mapcontrol?key=<?php echo esc_attr( get_option('mapApiKey') );  ?>&callback=loadMapScenario' async defer></script>
     <!--   <script type="text/javascript" language="JavaScript"> -->
	<div id="myMap" style="position:relative; width: 720px; height: 500px; align:center"></div>
	
	 <script type="text/javascript">
	
    var latlongs = [
   <?php 
   $i=1;     
   foreach ($result as $row)
   {        
    if($i<sizeof($result))
    { 
   	echo "{lat:".$row['lat'].",lon:".$row['long']."},\n";
    }
    else
    {
     echo "{lat:".$row['lat'].",lon:".$row['long']."}\n";
    }
     $i++;
     
   }
   
   
    ?>
      ];
  
   // alert(latlongs.length);
		//Bing map
		
		
		
		var map = null;
    

        function getMap()
        {

          map = new Microsoft.Maps.Map(document.getElementById('myMap'), {
        	  center: new Microsoft.Maps.Location(34, -4),
					zoom:2
                  });

        //Create an infobox at the center of the map but don't show it.
          //infobox = new Microsoft.Maps.Infobox(map.getCenter(), {
            //  visible: true
          //});

          for(var i=0,len = latlongs.length;i<len;i++){
        	    var pin = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(latlongs[i].lat, latlongs[i].lon));
        	    map.entities.push(pin);
        	    


          //Add pushpin to the map.
          //map.entities.push(pin);

          //console.log(map);
         // alert(latlongs.length);
          }  
        }

        document.getElementsByTagName("body")[0].setAttribute('onload', 'getMap()');
       /* document.getElementsByTagName("body")[0].innerHTML="<div id='myMap' style='position:static; width:400px; height:400px;'></div>"; */
  
  </script>
	
<?php 	
}
add_shortcode('displaymap', 'gelocation_displaySiteVisitorMap');
?>
