@extends('merchant.layouts.main')
@section('content')
<style>
        #map { height: 100%; }
        html, body { height: 100%; margin: 0; padding: 0; }
        /* ---- Tracking UI (ETA box + recenter button) ---- */
        .track-eta-box {
            background: #ffffff;
            padding: 8px 14px;
            margin: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
            font-family: -apple-system, Roboto, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.35;
            color: #1f2937;
            min-width: 120px;
        }
        .track-eta-box .eta-time { font-size: 18px; font-weight: 700; color: #111827; }
        .track-eta-box .eta-dist { color: #6b7280; }
        .track-recenter-btn {
            background: #ffffff;
            border: none;
            padding: 10px 14px;
            margin: 10px;
            border-radius: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
            font-family: -apple-system, Roboto, Arial, sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: #2563eb;
            cursor: pointer;
            display: none;
        }
</style>
<div class="page">
<div class="page-content">
<div class="panel panel-bordered">
<header class="panel-heading">
<div class="panel-actions"></div>
<h3 class="panel-title"><i class="fas fa-fw fa-map" aria-hidden="true"></i> @lang('admin.driver_tracking_map')</h3>
</header>
<div class="panel-body container-fluid">
<section id="gmaps-utils">
<div class="row">
<div class="col-12">
<div class="card">
<div class="card-content collapse show">
</div>
<div class="card-body">
<div id="map" style="width: 100%;height: 550px;"></div>
<input type="hidden" id="vehicle_type_image" value="{{view_config_image($booking->VehicleType->vehicleTypeMapImage)}}">
</div>
</div>
</div>
</div>
</section>
</div>
</div>
</div>
</div>
<br>
@endsection
@section('js')
<script>
 
        @php
        $driver = $booking->Driver;
        $lat = $driver->current_latitude;
        $long = $driver->current_longitude;
        $working_with_redis = $booking->Merchant->ApplicationConfiguration->working_with_redis;
 
        if($working_with_redis == 1){
            $driver_data = getDriverCurrentLatLong($driver);
            $lat =  $driver_data['latitude'];
            $long = $driver_data['longitude'];
        }
 
        // decode waypoints JSON -> change `waypoints` to your real column name.
        $waypoints = [];
        if (!empty($booking->waypoints)) {
            $waypoints = is_array($booking->waypoints)
                ? $booking->waypoints
                : json_decode($booking->waypoints, true);
        }

        /* ---------------------------------------------------------------
         * DRIVER / VEHICLE INFO FOR THE MOVING MARKER
         * ADJUST THESE to match your actual relationships/column names.
         * --------------------------------------------------------------- */
        $driverName = (optional($booking->driver)->first_name ?? 'Driver') . ' ' .
              (optional($booking->driver)->last_name ?? '');
        $rideId = "Ride id #".$booking->merchant_booking_id ;
        // dd($driverName);
        // If your vehicle number / vehicle name live on a different relation
        $vehicleName = ($booking->VehicleType->VehicleTypeName ?? 'Vehicle') . ' / ' . ($booking->driver->DriverVehicle[0]->VehicleModel->VehicleModelName ?? 'Model');
        // dd($vehicleName,$booking->driver->DriverVehicle[0]->VehicleModel->VehicleModelName);
        $vehicleNumber = $booking->driver->DriverVehicle[0]->vehicle_number ?? ($booking->driver->DriverVehicle[0]->vehicle_number ?? 'N/A');
        @endphp
 
        /* ============================================================
         * CONFIG / STATE  (mirrors NewTrackRideTaxiViewController)
         * ============================================================ */
 
        var map;
 
        // Initial driver position (redis or DB) — same source as before
        var position = [{{$lat}}, {{$long}}];
 
        var vehicleTypeImage = document.getElementById('vehicle_type_image').value;
        var waypoints        = {!! json_encode($waypoints ?: []) !!};
 
        // ploy_points = the ENCODED polyline stored in your DB (same as iOS polydata.polyline).
        var storedEncodedPolyline = {!! json_encode($booking->ploy_points ?? '') !!};

        // --- Driver / vehicle details shown on the moving marker ---
        var driverName     = {!! json_encode($driverName) !!};
        var vehicleName    = {!! json_encode($vehicleName) !!};
        var vehicleNumber  = {!! json_encode($vehicleNumber) !!};
        var rideId  = {!! json_encode($rideId) !!};
        

        // --- Static location text (used in info windows below) ---
        var pickupLocationText = {!! json_encode($booking->pickup_location ?? '') !!};
        var dropLocationText   = {!! json_encode($booking->drop_location ?? '') !!};
        var acceptTimeText     = {!! json_encode($booking->BookingDetail->accept_time ?? '') !!};
 
        // Animation/marker state (equivalent of the iOS private vars)
        var routePolyline   = null;   // the visible REMAINING polyline
        var polylinePath    = null;   // Array<google.maps.LatLng> of the remaining path
        var currentEncoded  = '';     // last drawn encoded string (== checkpolylinesame)
        var vehicleMarker   = null;   // RotatingMarker (== vehicleMarker)
        var lastMarkerBearing = 0;    // (== lastMarkerBearing)
        var totalRouteDistance = 0;   // meters (== totalRouteDistance, full route)
        var totalRouteDuration = 0;   // seconds (== totalRouteDuration, full route)
        var fullRouteDistanceGeom = 0; // geometric length of the FULL route (used for the ETA ratio)
 
        // Smooth car animation (== animateCarSmoothly / updateCarFrame)
        var carAnimStart  = null;
        var carAnimEnd    = null;
        var carAnimStartT = 0;
        var carAnimRAF    = null;
        var carSegmentBearing = 0;
        var carUseRoadBearing = true;
        var CAR_ANIM_DURATION = 1200; // ms  (== carAnimDuration 1.2s)
        var ROTATION_SMOOTHING = 0.25; // (== the 0.25 factor in updateCarFrame)
 
        // Camera control (== shouldAutoUpdateCamera / cameraMode + recenter)
        var followCar = true;
        var recenterBtnEl = null;
        var trackingInterval = null;
        var previousSnapped = null;
 
        // Live speed estimation for ETA — ONLY used as a fallback before we have server time
        var liveSpeed          = 0;     // m/s, smoothed
        var lastRawPos         = null;
        var lastUpdateTime     = null;
        var AVG_SPEED_FALLBACK = 8.33;  // m/s ≈ 30 km/h

        // Single shared InfoWindow used by every marker so only one is ever open at a time
        // (this is what fixes stops overlapping/showing on top of each other).
        var sharedInfoWindow = null;
        var driverInfoContent = ''; // built once google.maps is ready, see initMap()
 
        /* ============================================================
         * MATH HELPERS  (direct ports of the Swift geometry)
         * ============================================================ */
 
        function toRad(d){ return d * Math.PI / 180; }
        function toDeg(r){ return r * 180 / Math.PI; }
 
        // == getBearingBetweenTwoPoints
        function getBearing(p1, p2){
            var lat1 = toRad(p1.lat()), lon1 = toRad(p1.lng());
            var lat2 = toRad(p2.lat()), lon2 = toRad(p2.lng());
            var dLon = lon2 - lon1;
            var y = Math.sin(dLon) * Math.cos(lat2);
            var x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
            var brng = toDeg(Math.atan2(y, x));
            return (brng % 360 + 360) % 360; // normalise 0..360
        }
 
        // == shortestAngle
        function shortestAngle(from, to){
            var delta = to - from;
            while (delta > 180)  delta -= 360;
            while (delta < -180) delta += 360;
            return delta;
        }
 
        // == closestPointOnSegment  (planar lat/lng math, same as Swift)
        function closestPointOnSegment(start, end, point){
            var sx = start.lng(), sy = start.lat();
            var ex = end.lng(),   ey = end.lat();
            var px = point.lng(),  py = point.lat();
            var dx = ex - sx, dy = ey - sy;
            if (dx === 0 && dy === 0) return start;
            var t = ((px - sx) * dx + (py - sy) * dy) / (dx * dx + dy * dy);
            if (t < 0) return start;
            if (t > 1) return end;
            return new google.maps.LatLng(sy + t * dy, sx + t * dx);
        }
 
        // == snapToPolyline
        function snapToPolyline(location, path){
            if (!path || path.length === 0) return location;
            var closest = path[0];
            var minDist = Number.POSITIVE_INFINITY;
            for (var i = 0; i < path.length - 1; i++){
                var snapped = closestPointOnSegment(path[i], path[i + 1], location);
                var d = google.maps.geometry.spherical.computeDistanceBetween(snapped, location);
                if (d < minDist){ minDist = d; closest = snapped; }
            }
            return closest;
        }
 
        // == bearingFromPolyline  (road bearing of the closest segment)
        function bearingFromPolyline(position, path){
            if (!path || path.length < 2) return lastMarkerBearing;
            var closestIndex = 0;
            var minDist = Number.POSITIVE_INFINITY;
            for (var i = 0; i < path.length - 1; i++){
                var snapped = closestPointOnSegment(path[i], path[i + 1], position);
                var d = google.maps.geometry.spherical.computeDistanceBetween(snapped, position);
                if (d < minDist){ minDist = d; closestIndex = i; }
            }
            var p1 = path[closestIndex];
            var p2 = path[Math.min(closestIndex + 1, path.length - 1)];
            return getBearing(p1, p2);
        }
 
        // == distance(of path)
        function pathDistance(path){
            if (!path || path.length < 2) return 0;
            var total = 0;
            for (var i = 0; i < path.length - 1; i++){
                total += google.maps.geometry.spherical.computeDistanceBetween(path[i], path[i + 1]);
            }
            return total; // meters
        }
 
        function smoothstep(t){ return t * t * (3 - 2 * t); }

        /* ============================================================
         * INFO WINDOW HELPERS (location / status / time for every marker)
         * ============================================================ */

        function escapeHtml(str){
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function infoRow(label, value){
            if (value === null || value === undefined || value === '') return '';
            return '<div><strong>' + escapeHtml(label) + ':</strong> ' + escapeHtml(value) + '</div>';
        }

        // Builds a consistent "title + rows" block for every marker's info window.
        function buildInfoContent(title, rows){
            var html = '<div style="font-family:-apple-system,Roboto,Arial,sans-serif;font-size:13px;line-height:1.6;min-width:170px;">';
            html += '<div style="font-weight:700;font-size:14px;margin-bottom:4px;">' + escapeHtml(title) + '</div>';
            rows.forEach(function(r){ html += infoRow(r.label, r.value); });
            html += '</div>';
            return html;
        }

        // Opens the ONE shared InfoWindow at a marker OR at a raw LatLng
        // (the vehicle marker is a custom overlay, not a google.maps.Marker,
        // so it needs the LatLng branch).
        function openInfoWindow(content, anchorOrPosition){
            if (!sharedInfoWindow) return;
            sharedInfoWindow.close();
            sharedInfoWindow.setContent(content);
            if (anchorOrPosition instanceof google.maps.LatLng){
                sharedInfoWindow.setPosition(anchorOrPosition);
                sharedInfoWindow.open(map);
            } else {
                sharedInfoWindow.open(map, anchorOrPosition);
            }
        }
 
        /* ============================================================
         * ROTATING VEHICLE MARKER
         * ============================================================ */
        var RotatingMarker; // defined inside initMap once google.maps is ready
 
        function defineRotatingMarker(){
            RotatingMarker = class extends google.maps.OverlayView {
                constructor(position, iconUrl, size){
                    super();
                    this.position = position;
                    this.iconUrl  = iconUrl;
                    this.size     = size || 50;
                    this.rotation = 0;
                    this.div = null;
                }
                onAdd(){
                    var self = this;
                    var div = document.createElement('div');
                    div.style.position = 'absolute';
                    div.style.width  = this.size + 'px';
                    div.style.height = this.size + 'px';
                    div.style.cursor = 'pointer';
                    var img = document.createElement('img');
                    img.src = this.iconUrl;
                    img.style.width  = '100%';
                    img.style.height = '100%';
                    img.style.transformOrigin = 'center center';
                    img.style.willChange = 'transform';
                    div.appendChild(img);
                    this.img = img;
                    this.div = div;
                    this.getPanes().overlayMouseTarget.appendChild(div);

                    // --- Click the car/bike icon -> show driver name, vehicle name, vehicle number ---
                    div.addEventListener('click', function(){
                        openInfoWindow(driverInfoContent, self.getPosition());
                    });
                }
                draw(){
                    if (!this.div) return;
                    var proj = this.getProjection();
                    if (!proj) return;
                    var p = proj.fromLatLngToDivPixel(this.position);
                    if (!p) return;
                    this.div.style.left = (p.x - this.size / 2) + 'px';
                    this.div.style.top  = (p.y - this.size / 2) + 'px';
                    if (this.img) this.img.style.transform = 'rotate(' + this.rotation + 'deg)';
                }
                setPosition(latLng){ this.position = latLng; this.draw(); }
                setRotation(deg){
                    this.rotation = deg;
                    if (this.img) this.img.style.transform = 'rotate(' + deg + 'deg)';
                }
                getPosition(){ return this.position; }
                onRemove(){ if (this.div){ this.div.parentNode.removeChild(this.div); this.div = null; } }
            };
        }
 
        /* ============================================================
         * MAP INIT
         * ============================================================ */
        function initMap(){
            defineRotatingMarker();

            sharedInfoWindow = new google.maps.InfoWindow();

            // Content for the driver/vehicle marker, built once here since it
            // needs the google.maps types to already be loaded.
            driverInfoContent = buildInfoContent(rideId, [
                { label: 'Driver',      value: driverName },
                { label: 'Vehicle',     value: vehicleName },
                { label: 'Vehicle No.', value: vehicleNumber }
            ]);
 
            map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: {{$lat}}, lng: {{$long}}},
                zoom: 12
            });
 
            // --- Static markers ---
            var pickupLat = {{$booking->pickup_latitude}};
            var pickupLng = {{$booking->pickup_longitude}};
            var acceptLat = {{$booking->BookingDetail->accept_latitude}};
            var acceptLng = {{$booking->BookingDetail->accept_longitude}};
            var dropLat   = {{$booking->drop_latitude}};
            var dropLng   = {{$booking->drop_longitude}};
            var pickupLocation = new google.maps.LatLng(pickupLat, pickupLng);
            var dropLocation   = new google.maps.LatLng(dropLat, dropLng);
            var acceptLocation = new google.maps.LatLng(acceptLat, acceptLng);
 
            var pickupMarker = new google.maps.Marker({
                position: pickupLocation, map: map, title: "Pickup Location",
                icon: { url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png' }
            });
            var pickupContent = buildInfoContent('Pickup Location', [
                { label: 'Address', value: pickupLocationText }
            ]);
            pickupMarker.addListener('click', function(){ openInfoWindow(pickupContent, pickupMarker); });
 
            var dropMarker = new google.maps.Marker({
                position: dropLocation, map: map, title: "Drop Location",
                icon: { url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png' }
            });
            var dropContent = buildInfoContent('Drop Location', [
                { label: 'Address', value: dropLocationText }
            ]);
            dropMarker.addListener('click', function(){ openInfoWindow(dropContent, dropMarker); });
 
            var acceptMarker = new google.maps.Marker({
                position: acceptLocation, map: map, title: "Accept Location",
                icon: { url: "{{asset('basic-images/flag-marker.png')}}", scaledSize: new google.maps.Size(50, 50) }
            });
            var acceptContent = buildInfoContent('Accepted Location', [
                { label: 'Time', value: acceptTimeText }
            ]);
            acceptMarker.addListener('click', function(){ openInfoWindow(acceptContent, acceptMarker); });
 
            // --- Waypoint / multi-stop markers ---
            var waypointLatLngs = [];
            waypoints.forEach(function (wp, index){
                var wpLat = (wp.end_latitude  && wp.end_latitude  !== "") ? parseFloat(wp.end_latitude)  : parseFloat(wp.drop_latitude);
                var wpLng = (wp.end_longitude && wp.end_longitude !== "") ? parseFloat(wp.end_longitude) : parseFloat(wp.drop_longitude);
                if (isNaN(wpLat) || isNaN(wpLng)) return;
                var wpLocation = new google.maps.LatLng(wpLat, wpLng);
                waypointLatLngs.push(wpLocation);
                var stopNumber = wp.stop ? wp.stop : (index + 1);
                var isCompleted = (wp.status == 2);
                var iconUrl = isCompleted
                    ? 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
                    : 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png';
                var wpMarker = new google.maps.Marker({
                    position: wpLocation, map: map, title: "Stop " + stopNumber,
                    label: { text: String(stopNumber), color: "white", fontWeight: "bold" },
                    icon: { url: iconUrl, scaledSize: new google.maps.Size(40, 40) }
                });
                var statusText = isCompleted ? "Completed" : "Pending";
                // Every stop marker shows its own location, status and time on click.
                var wpContent = buildInfoContent('Stop ' + stopNumber, [
                    { label: 'Location', value: wp.drop_location },
                    { label: 'Status',   value: statusText },
                    { label: 'Time',     value: wp.end_time }
                ]);
                wpMarker.addListener('click', function(){ openInfoWindow(wpContent, wpMarker); });
            });
 
            // --- ETA box + recenter button overlays ---
            setupTrackingControls();
 
            // --- DRAW STORED POLYLINE (== createStillPolyline) ---
            if (storedEncodedPolyline && storedEncodedPolyline.length > 0){
                drawStoredPolyline(storedEncodedPolyline, 0, 0); // totals filled on first poll
            } else {
                // Fallback: planned route pickup -> waypoints -> drop so snapping/trimming still works.
                var fallback = [pickupLocation];
                waypointLatLngs.forEach(function(pt){ fallback.push(pt); });
                fallback.push(dropLocation);
                setPolylineFromPath(fallback);
            }
 
            // --- Create the moving vehicle marker at current driver position ---
            ensureVehicleMarker(new google.maps.LatLng({{$lat}}, {{$long}}));
 
            // --- Initial fit over everything ---
            var bounds = new google.maps.LatLngBounds();
            bounds.extend(pickupLocation);
            bounds.extend(dropLocation);
            bounds.extend(new google.maps.LatLng({{$lat}}, {{$long}}));
            waypointLatLngs.forEach(function(pt){ bounds.extend(pt); });
            if (polylinePath){ polylinePath.forEach(function(pt){ bounds.extend(pt); }); }
            map.fitBounds(bounds);
 
            // If the user drags, stop following and show recenter (== willMove)
            map.addListener('dragstart', function(){
                followCar = false;
                if (recenterBtnEl) recenterBtnEl.style.display = 'block';
            });
 
            // --- Start polling (== createTimer / getDeviceToken) ---
            startTracking(5000);
        }
 
        /* ============================================================
         * POLYLINE DRAW + TRIM
         * ============================================================ */
 
        // == createStillPolyline
        // serverDistance (meters) / serverDuration (seconds) come from location_updates.
        function drawStoredPolyline(encoded, serverDistance, serverDuration){
            var decoded = google.maps.geometry.encoding.decodePath(encoded);
            if (!decoded || decoded.length < 2) return;
            currentEncoded  = encoded;
            previousSnapped = null; // allow the marker to re-snap onto the new route
 
            setPolylineFromPath(decoded); // draws + sets fullRouteDistanceGeom
 
            // Bind ETA basis to the new route's server values (fall back to geometry)
            totalRouteDistance = (serverDistance && serverDistance > 0) ? serverDistance : fullRouteDistanceGeom;
            if (serverDuration && serverDuration > 0) totalRouteDuration = serverDuration;
 
            updateEta(fullRouteDistanceGeom);
            fitRoute();
        }
 
        function setPolylineFromPath(pathArr){
            polylinePath = pathArr;
            fullRouteDistanceGeom = pathDistance(pathArr); // length of the full route
            if (routePolyline) routePolyline.setMap(null);
            routePolyline = new google.maps.Polyline({
                path: pathArr,
                geodesic: true,
                strokeColor: '#2563eb',
                strokeOpacity: 0.9,
                strokeWeight: 6,
                map: map
            });
        }
 
        // == trimPolylineToCurrentPosition
        function trimPolylineToCurrentPosition(current){
            if (!polylinePath || polylinePath.length < 2) return;
 
            var bestIndex = 0;
            var bestDist  = Number.POSITIVE_INFINITY;
            for (var i = 0; i < polylinePath.length - 1; i++){
                var c = closestPointOnSegment(polylinePath[i], polylinePath[i + 1], current);
                var d = google.maps.geometry.spherical.computeDistanceBetween(c, current);
                if (d < bestDist){ bestDist = d; bestIndex = i; }
            }
 
            var newPath = [current];
            for (var j = bestIndex + 1; j < polylinePath.length; j++){
                newPath.push(polylinePath[j]);
            }
 
            routePolyline.setPath(newPath);
            polylinePath = newPath;
 
            var remaining = pathDistance(newPath);
            updateEta(remaining);
        }
 
        /* ============================================================
         * DRIVER UPDATE + SMOOTH ANIMATION
         * ============================================================ */
 
        // == editPolyLine + fallbackUserTrackingWithoutPolyline
        function onDriverUpdate(lat, lng){
            if (isNaN(lat) || isNaN(lng)) return;
            var newPos = new google.maps.LatLng(lat, lng);
 
            // --- live speed estimate (m/s) — fallback only ---
            var nowMs = Date.now();
            if (lastRawPos && lastUpdateTime){
                var dt = (nowMs - lastUpdateTime) / 1000;
                if (dt > 0){
                    var moved = google.maps.geometry.spherical.computeDistanceBetween(lastRawPos, newPos);
                    var inst  = moved / dt;
                    if (inst > 0.3){
                        liveSpeed = liveSpeed > 0 ? (liveSpeed * 0.7 + inst * 0.3) : inst;
                    }
                }
            }
            lastRawPos = newPos;
            lastUpdateTime = nowMs;
 
            if (polylinePath && polylinePath.length > 1){
                var snapped = snapToPolyline(newPos, polylinePath);
 
                if (previousSnapped &&
                    google.maps.geometry.spherical.computeDistanceBetween(previousSnapped, snapped) < 0.5){
                    return;
                }
                previousSnapped = snapped;
 
                trimPolylineToCurrentPosition(snapped);
                animateCarSmoothly(snapped, true);
            } else {
                animateCarSmoothly(newPos, false);
            }
        }
 
        // == animateCarSmoothly
        function animateCarSmoothly(to, useRoadBearing){
            if (carAnimRAF) cancelAnimationFrame(carAnimRAF);
 
            carAnimStart  = vehicleMarker ? vehicleMarker.getPosition() : to;
            carAnimEnd    = to;
            carAnimStartT = performance.now();
            carUseRoadBearing = useRoadBearing;
 
            if (!useRoadBearing && carAnimStart){
                carSegmentBearing = getBearing(carAnimStart, to);
            }
 
            carAnimRAF = requestAnimationFrame(updateCarFrame);
        }
 
        // == updateCarFrame
        function updateCarFrame(now){
            if (!carAnimStart || !carAnimEnd || !vehicleMarker){
                if (carAnimRAF) cancelAnimationFrame(carAnimRAF);
                return;
            }
 
            var elapsed = now - carAnimStartT;
            var t = Math.min(elapsed / CAR_ANIM_DURATION, 1);
            var eased = smoothstep(t);
 
            var lat = carAnimStart.lat() + (carAnimEnd.lat() - carAnimStart.lat()) * eased;
            var lng = carAnimStart.lng() + (carAnimEnd.lng() - carAnimStart.lng()) * eased;
            var pos = new google.maps.LatLng(lat, lng);
 
            var roadBearing = carUseRoadBearing ? bearingFromPolyline(pos, polylinePath) : carSegmentBearing;
            var delta = shortestAngle(lastMarkerBearing, roadBearing);
            lastMarkerBearing += delta * ROTATION_SMOOTHING;
 
            vehicleMarker.setPosition(pos);
            vehicleMarker.setRotation(lastMarkerBearing);
 
            if (followCar) map.panTo(pos);
 
            if (t < 1){
                carAnimRAF = requestAnimationFrame(updateCarFrame);
            } else {
                carAnimRAF = null;
            }
        }
 
        // == ensureVehicleMarker
        function ensureVehicleMarker(positionLatLng){
            if (!vehicleMarker){
                vehicleMarker = new RotatingMarker(positionLatLng, vehicleTypeImage, 50);
                vehicleMarker.setMap(map);
            } else {
                vehicleMarker.setPosition(positionLatLng);
                vehicleMarker.setMap(map);
            }
        }
 
        /* ============================================================
         * CAMERA / ETA / RECENTER
         * ============================================================ */
 
        // == fitRouteOnMap
        function fitRoute(){
            if (!polylinePath || polylinePath.length === 0) return;
            var bounds = new google.maps.LatLngBounds();
            polylinePath.forEach(function(pt){ bounds.extend(pt); });
            map.fitBounds(bounds, 60);
        }
 
        function setupTrackingControls(){
            var eta = document.createElement('div');
            eta.className = 'track-eta-box';
            eta.id = 'trackEtaBox';
            eta.innerHTML = '<div class="eta-time" id="etaTime">--</div><div class="eta-dist" id="etaDist">--</div>';
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(eta);
 
            recenterBtnEl = document.createElement('button');
            recenterBtnEl.className = 'track-recenter-btn';
            recenterBtnEl.type = 'button';
            recenterBtnEl.textContent = '\u2295 Recenter';
            recenterBtnEl.onclick = function(){
                followCar = true;
                recenterBtnEl.style.display = 'none';
                if (vehicleMarker) map.panTo(vehicleMarker.getPosition());
            };
            map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(recenterBtnEl);
        }
 
        function formatDistance(m){
            if (m < 1000) return Math.round(m) + ' m';
            return (Math.round((m / 1000) * 10) / 10).toFixed(1) + ' km';
        }
 
        // == navigateView.updateETA
        function updateEta(remainingMeters){
            var distEl = document.getElementById('etaDist');
            var timeEl = document.getElementById('etaTime');
 
            var etaSeconds;
            if (totalRouteDuration > 0 && fullRouteDistanceGeom > 0){
                // scale the route's total time by the fraction of route left (== iOS)
                var frac = remainingMeters / fullRouteDistanceGeom;
                if (frac > 1) frac = 1;
                if (frac < 0) frac = 0;
                etaSeconds = totalRouteDuration * frac;
            } else {
                // before we have server time -> estimate from live (or fallback) speed
                var speed = liveSpeed > 0.3 ? liveSpeed : AVG_SPEED_FALLBACK;
                etaSeconds = remainingMeters / speed;
            }
 
            var timeMin = Math.max(1, Math.ceil(etaSeconds / 60));
            if (timeEl) timeEl.textContent = timeMin + ' min';
            if (distEl) distEl.textContent = formatDistance(remainingMeters) + ' remaining';
        }
 
        /* ============================================================
         * POLLING  (== USERBOOKINGTRACKING -> handleBookingTrackingResponse)
         * Uses location_updates.distance_in_meter / time_in_min.
         * NOTE: time_in_min is actually SECONDS (1532 -> 26 min), same as iOS.
         * ============================================================ */
        function startTracking(intervalMs){
            trackingInterval = setInterval(fetchTracking, intervalMs);
            fetchTracking(); // first call immediately
        }
 
        function stopTracking(){
            if (trackingInterval){ clearInterval(trackingInterval); trackingInterval = null; }
        }
 
        function fetchTracking(){
            $.ajax({
                type: "GET",
                dataType: "json",
                url: "{{route('driverTrack')}}",
                data: {
                    driver_id:  {{ $booking->Driver->id }},
                    booking_id: {{ $booking->id }}
                },
                success: function(data){
 
                    // --- pull the real route totals from location_updates ---
                    var lu     = data.location_updates || {};
                    var luDist = parseFloat(lu.distance_in_meter) || 0; // meters
                    var luTime = parseInt(lu.time_in_min, 10)      || 0; // SECONDS (despite the name)
 
                    // --- polyline same vs changed (== checkpolylinesame) ---
                    if (data.polyline && data.polyline !== currentEncoded){
                        // Route changed (e.g. ride started -> 1004): redraw + rebind ETA
                        drawStoredPolyline(data.polyline, luDist, luTime);
                    } else if (luTime > 0 && totalRouteDuration === 0){
                        // Same polyline, but we just learned the totals -> bind them once
                        if (luDist > 0) totalRouteDistance = luDist;
                        totalRouteDuration = luTime;
                        if (polylinePath) updateEta(pathDistance(polylinePath));
                    }
 
                    // --- move the driver (snap + trim + smooth animate + ETA countdown) ---
                    if (data.current_latitude && data.current_longitude){
                        onDriverUpdate(parseFloat(data.current_latitude), parseFloat(data.current_longitude));
                    }
 
                    // --- ride completed -> stop polling (== bookingstatus 1005) ---
                    if (parseInt(data.booking_status, 10) === 1005){
                        stopTracking();
                    }
                }
            });
        }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{$booking->Merchant->BookingConfiguration->google_key_admin}}&libraries=geometry&callback=initMap"
            async defer></script>
@endsection