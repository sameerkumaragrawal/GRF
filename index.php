<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
		<meta charset="utf-8">
		<title>Group Route Finder</title>
		<link href="http://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=places"></script>
		<script>
			var directionsDisplayArr=new Array();
			var directionsService = new google.maps.DirectionsService();
			var map;
			var stepDisplay;
			var markerArr=new Array();
			var starts;
			var end;
			var index = 0;
			var nRoutes=0;
			var responseArr=new Array();
			var requestArr=new Array();
			var commonPoints00, commonPoints01, commonPoints10, commonPoints11;
			var routeChoice;

			function initialize() {
				var mumbai = new google.maps.LatLng(19, 73);
				var mapOptions = {
					zoom:7,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					center: mumbai
				}
				map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
				stepDisplay=new google.maps.InfoWindow();

				inputElems = document.getElementsByTagName("input");
				for (var i=0; i<inputElems.length; i++) {
					new google.maps.places.Autocomplete(inputElems[i]);
				}
			}
			
			function processRoutes() {
				for (var i=0; i<nRoutes; i++) {
					if (responseArr[i].routes.length < 2) {
						responseArr[i].routes[1] = responseArr[i].routes[0];
					}
				}
			
				fillCommonPoints(0,0);
				fillCommonPoints(0,1);
				fillCommonPoints(1,0);
				fillCommonPoints(1,1);
				
				getRouteChoice();
			
				var elem,steps=new Array(nRoutes);
				for(var i=0; i<nRoutes; i++) {
					elem=responseArr[i];
					steps[i] = elem.routes[routeChoice[i]].legs[0].steps;
				}
				for(var i=0; i<nRoutes; i++){
					for(var j=i+1; j<nRoutes; j++){
						findMeetingPoint(steps[i], steps[j]);
					}
				}
				
				for (var i=0; i<nRoutes; i++) {
					responseArr[i].routes[0] = responseArr[i].routes[routeChoice[i]];
					directionsDisplayArr[i].setDirections(responseArr[i]);
				}
			}
			
			function getCommonPoints(i,j,m,n) {
				if (m==0 && n==0)  commonPoints = commonPoints00;
				if (m==0 && n==1)  commonPoints = commonPoints01;
				if (m==1 && n==0)  commonPoints = commonPoints10;
				if (m==1 && n==1)  commonPoints = commonPoints11;
				return commonPoints[i][j];
			}
			
			function getRouteChoice() {
				var maxVal=0;
				var index=0;
				for(var k=0; k<Math.pow(2,nRoutes); k++) {
					var sum=0;
					for(var i=0; i<nRoutes; i++) {
						for(var j=0; j<nRoutes; j++) {
							var m = (Math.floor(k/Math.pow(2,nRoutes-i-1)))%2;
							var n =  (Math.floor(k/Math.pow(2,nRoutes-j-1)))%2;
							if(i!=j) sum += getCommonPoints(i,j,m,n);
						}
					}
					if(sum > maxVal) {
						maxVal = sum;
						index = k;
					}
				}
				for(var i=0; i<nRoutes; i++) {
					routeChoice[i] = (Math.floor(index/Math.pow(2,nRoutes-i-1)))%2;
				}
			}
			
			function fillCommonPoints(m, n) {
				var commonPoints;
				if (m==0 && n==0)  commonPoints = commonPoints00;
				if (m==0 && n==1)  commonPoints = commonPoints01;
				if (m==1 && n==0)  commonPoints = commonPoints10;
				if (m==1 && n==1)  commonPoints = commonPoints11;
				
				var foundMeetingPoint = false;
				for (var p=0; p<nRoutes; p++) {
					for (var q=0; q<nRoutes; q++) {
						foundMeetingPoint = false;
						commonPoints[p][q] = 0;
						var steps1 = responseArr[p].routes[m].legs[0].steps;
						var steps2 = responseArr[q].routes[n].legs[0].steps;
						for(var i=0; i<steps1.length; i++) {
							for(j=0; j<steps2.length; j++) {
								if(steps1[i].start_point.equals(steps2[j].start_point)) {
									foundMeetingPoint=true;
									var sum1 = 0, sum2 = 0;
									for (var c=i; c<steps1.length; c++) {
										sum1 += steps1[c].distance.value;
									}
									for (var c=j; c<steps2.length; c++) {
										sum2 += steps2[c].distance.value;
									}
									commonPoints[p][q] = (Math.abs(sum1-sum2) < 2000 && p != q)? sum1: 0;
									break;
								}
							}
							if (foundMeetingPoint) {
								break;
							}
						}
					}
				}
			}
			
			function findMeetingPoint(steps1, steps2) {
				var foundMeetingPoint=false;
				for(var i=0; i<steps1.length; i++){
					for(j=0; j<steps2.length; j++){
						if(steps1[i].start_point.equals(steps2[j].start_point)){
							foundMeetingPoint=true;
							if (i==0) {
								addMarker(steps1[0]);
							}
							else if (j==0) {
								addMarker(steps2[0]);
							}
							else {
								addMarker(computeMeetingPoint(steps1[i-1], steps2[j-1], steps2[j]));
							}
							break;
						}
					}
					if (foundMeetingPoint) {
						break;
					}
				}
			}

			function 	computeMeetingPoint(a, b, ab) {
				var distanceAtoAB = a.distance.value;
				var distanceBtoAB = b.distance.value;
				var distanceAtoB = getDistance(a.start_point, b.start_point);
				var difference = 0;
				if (distanceAtoAB > distanceBtoAB) {
					difference = Math.abs(distanceAtoB + distanceBtoAB - distanceAtoAB);
					if (difference  < 4000) return b;
					else return ab;
				}
				else {
					difference = Math.abs(distanceAtoB + distanceAtoAB - distanceBtoAB);
					if (difference  < 4000) return a;
					else return ab;
				}
			}
			
			function getDistance(point1, point2) {
				var lat1 = point1.jb * 3.14 / 180;
				var lon1 = point1.kb * 3.14 / 180;
				var lat2 = point2.jb * 3.14 / 180;
				var lon2 = point2.kb * 3.14 / 180;
				
				var x = (lon2-lon1) * Math.cos((lat1+lat2)/2);
				var y = (lat2-lat1);
				var d = Math.sqrt(x*x + y*y) * 6371000;
				return d;
			} 
			
			function getMultipleRoute() {
				var requestarr=new Array();
				
				for (i=0;i<starts.length;i++) {
					requestarr.push({
						origin:starts[i],
						destination:end,
						provideRouteAlternatives: true,
						travelMode: google.maps.DirectionsTravelMode.DRIVING
					});
				}
				
				request = null;
				directionsDisplayArr= new Array();
				var j=0;
				for (var i = 0; i < nRoutes; i++) {
					request = requestarr[i];
					directionsDisplayArr.push(new google.maps.DirectionsRenderer({ draggable: true}));
					directionsDisplayArr[i].setMap(map);
							
					directionsService.route(request, function(response, status) {
						if (status == google.maps.DirectionsStatus.OK) {
								responseArr.push(response);
								//directionsDisplayArr[j].setDirections(response);
								//showSteps(response);
								j++;
						}
						if(j==nRoutes) processRoutes();
					});
				}
			}
			
			function addMarker(meetStep){
				var marker = new google.maps.Marker({
						position: meetStep.start_point,
						map: map
				});
				attachInstructionText(marker, getRoadName(meetStep.instructions));
				markerArr.push(marker);
			}
			
			function getRoadName(str) {
				var index1 = str.lastIndexOf("<b>");
				var index2 = str.lastIndexOf("</b>");
				var road = str.substr(index1, index2-index1+4);
				return road;
			}
			
			function showSteps(directionResult) {
				var myRoute = directionResult.routes[0].legs[0];

				for (var i = 0; i < myRoute.steps.length; i++) {
					var marker = new google.maps.Marker({
						position: myRoute.steps[i].start_point,
						map: map
					});
					attachInstructionText(marker, myRoute.steps[i].instructions);
					markerArr.push(marker);
					}
			}

		function attachInstructionText(marker, text) {
			google.maps.event.addListener(marker, "click", function() {
				stepDisplay.setContent(text);
				stepDisplay.open(map, marker);
			});
		} 
		
		function deleteOverlays() {
			for(var i=0;i<nRoutes;i++){
				directionsDisplayArr[i].setMap(null);
				
			}
			
			for (var i=0;i<markerArr.length;i++){
				markerArr[i].setMap(null);
			}
			
			markerArr.length = 0;
			directionsDisplayArr.length = 0;
		}
		
		function submitform() {
			deleteOverlays();
				
			responseArr=new Array();
			requestArr=new Array();
			markerArr=new Array();
			starts=new Array();
			directionsDisplayArr= new Array();
			
			var inputdivs = document.getElementById("source-panel").children;
			nRoutes=inputdivs.length;
			for (i=0;i<nRoutes;i++) {
				starts.push(inputdivs[i].children[0].value);
			}
			end = document.getElementById("end").value;
			routeChoice = new Array(nRoutes);
			commonPoints00 = new Array(nRoutes);
			commonPoints01 = new Array(nRoutes);
			commonPoints10 = new Array(nRoutes);
			commonPoints11 = new Array(nRoutes);
			for(var i=0; i<nRoutes; i++) {
				commonPoints00[i] = new Array(nRoutes);
				commonPoints01[i] = new Array(nRoutes);
				commonPoints10[i] = new Array(nRoutes);
				commonPoints11[i] = new Array(nRoutes);
				routeChoice[i] = 0;
			}
			getMultipleRoute();
		}
		
		function addInput() {
			var newdiv = document.createElement("DIV");
			var newinput = document.createElement("INPUT");
			var inputcount = document.getElementById("source-panel").childElementCount;
			
			new google.maps.places.Autocomplete(newinput);
			newinput.setAttribute("type", "text");
			newdiv.innerHTML = "Source"+(inputcount+1) + ": ";
			newdiv.appendChild(newinput);
			document.getElementById("source-panel").appendChild(newdiv);
		}
		
		function removeInput() {
			sp = document.getElementById("source-panel");
			if (sp.children.length>1)
				sp.removeChild(sp.lastElementChild);
		}
			
		google.maps.event.addDomListener(window, "load", initialize);
		</script>
	</head>
	<body>
		<div class="container">
			<h2 align="center">Group Route Finder</h2>
			<div style="float: left; width: 20%; margin-right: 40px;">
				<div id="source-panel">
					<div> Source1: <input type="text"> </div>
				</div>
				<div id="destination-panel">
					Destination: <input type="text" name="end" id="end">
				</div>
				<p>
					<button type="button" class="btn btn-primary" onclick="addInput();">Add Source</button>
					<button type="button" class="btn btn-primary" onclick="removeInput();">Remove Source</button>
				</p>
				<p>
					<button type="button" style="width:100%" class="btn btn-success" onclick="submitform();">Get routes</button>
				</p>
			</div>
			<div id="map-canvas" style="min-width: 400px; min-height: 600px;">Loading Google Map Api..</div>
		  </div>
	</body>
</html>
