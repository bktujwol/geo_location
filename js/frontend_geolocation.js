
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
});
}(jQuery)); 

function GetMap() {

     var geoLocationMapType = geolocation_params.bing_map_type; 
     var visitorsData = JSON.parse(geolocation_params.bing_map_visitors);
     var map = new Microsoft.Maps.Map('#myMap', {
         credentials: geolocation_params.bing_map_key,
     });
 
 
 if(geolocation_params.bing_map_type === 'aerial'){
     map.setView({
         mapTypeId: Microsoft.Maps.MapTypeId.aerial,
         zoom: 2,
     });
 }else if(geolocation_params.bing_map_type === 'canvasLight'){
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
            Microsoft.Maps.Events.addHandler(pin, 'click', function(event){
 
             if (event.target.metadata) {
                 //Set the infobox options with the metadata of the pushpin.
                 infobox.setOptions({
                     location: event.target.getLocation(),
                     title: event.target.metadata.title,
                     description: event.target.metadata.description,
                     visible: true
                 });
             }
 
            });  
          map.entities.push(pin);
         }
     });
 
 } 


