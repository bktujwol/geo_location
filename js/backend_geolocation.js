(function($){

$(document).ready(function(){


        /*section that deals with category item delete*/
        $( "input.geolocation_ip_info").click(function() {
            var blockId = $(this).attr('id');
            var blockOption = $(this).attr('value');
            //if blocked unblock , if not blocked block
              if(blockOption == '1'){
                  var blockConfirm = confirm(geolocation_backend_params.unblock_confirm);
                  var block = '0';
                  }
              else{
                   var blockConfirm = confirm(geolocation_backend_params.block_confirm);
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
                                    alert(geolocation_backend_params.ip_blocked);
                                     $("input[id*='"+blockId+"']").attr("value","1");
                                    } else{
                                      alert(geolocation_backend_params.ip_unblocked);
                                      $("input[id*='"+blockId+"']").attr("value","0");
                                    }	
                          }
                          else{
                                alert(geolocation_backend_params.action_error);
                              }	
                  
                });
              }
            });
            
    //delete ip record        
    $("a.geolocation_ip_delete").click(function() {
                var deleteConfirm = confirm (geolocation_backend_params.visitor_delete_confirm);
                if(deleteConfirm == true){
                var rowToDelete = $(this).attr('id');
                        var data = {
                                'action': 'delete_visitor_from_table',
                                'rowId': rowToDelete
                            };
            
                                //script to send ajax request
                              $.post(ajaxurl, data, function(serverResponse) {
                                    if(serverResponse != false){
                                        alert(geolocation_backend_params.visitor_deleted);
                                        
                                        $("a[id*='"+rowToDelete+"']").closest("tr").remove();
                                        }
                                    else{
                                            alert(geolocation_backend_params.visitor_not_deleted);
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
            


});



}(jQuery))

function GetMap() {
   // console.log(geolocation_backend_params.bing_map_visitors);
   var geoLocationMapType = geolocation_backend_params.bing_map_type;


    var visitorsData = JSON.parse(geolocation_backend_params.bing_map_visitors);
    var map = new Microsoft.Maps.Map('#myMap', {
        credentials: geolocation_backend_params.bing_map_key,
    });


if(geolocation_backend_params.bing_map_type === 'aerial'){
    map.setView({
        mapTypeId: Microsoft.Maps.MapTypeId.aerial,
        zoom: 2,
    });
}else if(geolocation_backend_params.bing_map_type === 'canvasLight'){
    map.setView({
        mapTypeId: Microsoft.Maps.MapTypeId.canvasLight,
        zoom: 2,
    });
}else {
    map.setView({
        mapTypeId: Microsoft.Maps.MapTypeId.road,
        zoom: 2,
    });
}
   

     //Create an infobox at the center of the map but don't show it.
     infobox = new Microsoft.Maps.Infobox(map.getCenter(), {
        visible: false
    });

    //Assign the infobox to a map instance.
    infobox.setMap(map);

    //Load the Clustering module.

    Microsoft.Maps.loadModule("Microsoft.Maps.Clustering", function () {
        for( var i in  visitorsData ){
            if( null !== visitorsData[i].lat ||   null !== visitorsData[i].long){
             var pin = new Microsoft.Maps.Pushpin(new Microsoft.Maps.Location(visitorsData[i].lat, visitorsData[i].long),{ text:visitorsData[i].visitCount });
            }


              //Store some metadata with the pushpin.
              pin.metadata = {
                title: 'Visitor Count :' + visitorsData[i].visitCount,
            };

           //Add a click event handler to the pushpin.
           Microsoft.Maps.Events.addHandler(pin, 'click', function(e){

            if (e.target.metadata) {
                //Set the infobox options with the metadata of the pushpin.
                infobox.setOptions({
                    location: e.target.getLocation(),
                    title: e.target.metadata.title,
                    description: e.target.metadata.description,
                    visible: true
                });
            }

           });  
         map.entities.push(pin);
        }
    });

} 