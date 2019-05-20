(function($){

$(document).ready(function(){


        /*section that deals with category item delete*/
        $( "input.geolocation_ip_info").click(function() {
            var blockId = $(this).attr('id');
            var blockOption = $(this).attr('value');
            //if blocked unblock , if not blocked block
              if(blockOption == '1'){
                  var blockConfirm = confirm("Do you want to unblock this IP address!");
                  var block = '0';
                  }
              else{
                   var blockConfirm = confirm("Do you want to block this IP address!");
                   var block = '1';
                  }

            if(blockConfirm){
                var data = {
                            'action': 'block_visitor_from_site',
                            'rowId': blockId,
                            'block' : block
                        };

                  $.post(ajaxurl, data, function(response) {
      
                          if(response === '1' ){ 
                                if(block === '1'){
                                    alert("IP successfully blocked.");
                                     $("input[id*='"+blockId+"']").attr("value","1");
                                    } else{
                                      alert("IP sucessfully unblocked.");
                                      $("input[id*='"+blockId+"']").attr("value","0");
                                    }	
                          }
                          else{
                                alert("For some reason uable to carryout action.");
                              }	
                  
                });
              }
            });
            
    //delete ip record        
    $("a.geolocation_ip_delete").click(function() {
                var deleteConfirm = confirm ('This visitor will be deleted');
                if(deleteConfirm == true){
                var rowToDelete = $(this).attr('id');
                        var data = {
                                'action': 'delete_visitor_from_table',
                                'rowId': rowToDelete
                            };
            
                                //script to send ajax request
                              $.post(ajaxurl, data, function(serverResponse) {
                                    if(serverResponse != false){
                                        alert("Visitor sucessfully deleted.");
                                        
                                        $("a[id*='"+rowToDelete+"']").closest("tr").remove();
                                        }
                                    else{
                                            alert("Visitor could not be deleted.");
                                        }
                                });
                        
    
                    }
                
    
                });//end of click link function 


         // display user map in modal window
        $("a.display_visitor_map_modal").click(function() {
            var latLong = $(this).attr('data-lat-long');
            $.ctcOverlayEl({elemHeight: '550px',elemWidth:'600px',iframeUrl:'https://www.bing.com/maps/embed?h=550&w=600&cp='+latLong+'&lvl=10&typ=d&sty=r&src=SHELL&FORM=MBEDV8&pp='+latLong+'"&scrolling="no"'});
            $(document).find(".overlayElContainer").css('overflow','hidden');
            });             


//section to load map
if( $('#myMap').length >= 1){
    var latlongs = JSON.parse(geolocation_backend_params.bing_map_visitors);
 
            if(geolocation_backend_params.bing_map_type.length > 0 ){

                var geoLocationMapType = geolocation_backend_params.bing_map_type;
                } else {
                var geoLocationMapType = 'road';
            }

                var map = new Microsoft.Maps.Map('#myMap', {
                                                                                  center: new Microsoft.Maps.Location(34, -4),
                                                                                 zoom:2,
                                                                                 mapTypeId: Microsoft.Maps.MapTypeId.geoLocationMapType,
                                                                                 supportedMapTypes: [Microsoft.Maps.MapTypeId.road, Microsoft.Maps.MapTypeId.aerial, Microsoft.Maps.MapTypeId.canvasLight] 
                                                                            });
                                                                                                                                                               
     

        for( var i in  latlongs ){
            if( null !== latlongs[i].lat ||   null !== latlongs[i].long){
             var pin = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(latlongs[i].lat, latlongs[i].long),{ text:'.' });
            }
            map.entities.push(pin);
        }
  }  

});



}(jQuery))