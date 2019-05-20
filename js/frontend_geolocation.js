
(function($){
$(document).ready(function(){

 if( 'undefined' != typeof(geolocation_params.no_ssl) ){

        if(geolocation_params.ipinfodb_apiKey.length > 0){
               $.get( "http://api.ipinfodb.com/v3/ip-city/?key="+geolocation_params.ipinfodb_apiKey+"&format=json",function(response){
                   var data = {
                                    action:'geolocation_insert_visitor_info',
                                    'lat':response.latitude,
                                    'long':response.longitude,
                                    'ip':response.ipAddress
                                };
        
                    $.post(geolocation_params.ajax_url,data,function(response){ return;});
                });
                  
            }
    }



 
    if( $('#myMap').length >= 1){
        var latlongs = JSON.parse(geolocation_params.bing_map_visitors);
     
                if(geolocation_params.bing_map_type.length > 0 ){
    
                    var geoLocationMapType = geolocation_params.bing_map_type;
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
}(jQuery)); 


