@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @csrf
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(count($merchant_segments) > 1)
                            <div class="d-inline-block mt-5">
                                <div class="form-group mt-5">
                                    {!! Form::select('segment_id',$merchant_segments,[],['class'=>'form-control', 'id'=>'segment_id','data-placeholder'=>trans("$string_file.segment"), 'onchange'=>"onSegmentChange()"]) !!}
                                </div>
                            </div>
                        @else
                            {!! Form::hidden('segment_id',array_key_first($merchant_segments)) !!}
                        @endif
                        @if(count($merchant_areas) > 1)
                            <div class="d-inline-block mt-5">
                                <div class="form-group mt-5">
                                    {!! Form::select('area_id',$merchant_areas,[],['class'=>'form-control', 'id'=>'area_id','data-placeholder'=>trans("$string_file.area"), 'onchange'=>"onAreaChange()"]) !!}
                                </div>
                            </div>
                        @else
                            {!! Form::hidden('area_id',array_key_first($merchant_areas)) !!}
                        @endif
                        {{--<div class="d-inline-block mt-5">
                            <div class="form-group mt-5">
                                <label class="mr-1">@lang("$string_file.min_bookings")</label>
                                <input type="number" id="min_threshold" value="1" min="0"
                                       style="width:80px;display:inline-block" class="form-control d-inline-block"
                                       onchange="loadHotspots()">
                            </div>
                        </div>--}}

                        <button type="button"
                                class="btn btn-outline-primary btn-min-width box-shadow-1 mr-1 mb-1"
                                style="margin:10px" onclick="loadHotspots()">
                            @lang("$string_file.refresh")
                        </button>

                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class=" wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.heat_map")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <div id="context-menu" style="width: 100%;height: 550px;"></div>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/heatmap.js/2.0.2/heatmap.min.js"></script>
<script>

    var map;
    var heatmapOverlay = null;

    // Called by Google Maps' own callback= param — this guarantees
    // google.maps (including OverlayView) is fully ready, not just
    // that the <script> tag finished executing.
    function initMap() {
        map = new google.maps.Map(document.getElementById('context-menu'), {
            zoom: 11,
            center: {lat: 28.7041, lng: 77.1025},
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        // Load the gmaps heatmap plugin only now — google.maps.OverlayView
        // is guaranteed to exist at this point, avoiding the race condition
        // that caused "gmaps.OverlayView is not a constructor".
        var pluginScript = document.createElement('script');
        pluginScript.src = "https://cdn.jsdelivr.net/gh/pa7/heatmap.js@2.0.5/plugins/gmaps-heatmap/gmaps-heatmap.js";
        pluginScript.onload = function () {
            heatmapOverlay = new HeatmapOverlay(map, {
                radius: 25,
                maxOpacity: 0.7,
                scaleRadius: false,
                useLocalExtrema: true,
                latField: 'lat',
                lngField: 'lng',
                valueField: 'value'
            });
            loadHotspots();
        };
        pluginScript.onerror = function () {
            console.log('Failed to load gmaps-heatmap plugin script');
        };
        document.head.appendChild(pluginScript);
    }

    function onSegmentChange() {
        loadHotspots();
    }

    function onAreaChange() {
        loadHotspots();
    }

    function loadHotspots() {
        if (!heatmapOverlay) {
            return; // plugin not ready yet
        }

        var token = $('[name="_token"]').val();
        var segment_id = null;
        if ($('#segment_id').val() != '') {
            segment_id = [$('#segment_id').val()];
        }
        var area_id = $('#area_id').val() != '' ? $('#area_id').val() : null;

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': token
            },
            method: 'POST',
            url: "{{ route('merchant.get-service-area-heatmap-points') }}",
            data: {
                segment_id: segment_id,
                area_id: area_id,
            },
            success: function (data) {
                if (!data || data.length == 0) {
                    console.log('No points returned for heatmap');
                    heatmapOverlay.setData({max: 1, min: 0, data: []});
                    return;
                }

                var bounds = new google.maps.LatLngBounds();
                var heatPoints = [];

                for (var i = 0; i < data.length; i++) {
                    var p = data[i];
                    var lat = parseFloat(p.lat);
                    var lng = parseFloat(p.lng);
                    if (isNaN(lat) || isNaN(lng)) continue;

                    heatPoints.push({lat: lat, lng: lng, value: 1});
                    bounds.extend(new google.maps.LatLng(lat, lng));
                }

                if (heatPoints.length == 0) {
                    console.log('No valid points to plot');
                    heatmapOverlay.setData({max: 1, min: 0, data: []});
                    return;
                }

                heatmapOverlay.setData({
                    max: 1,
                    min: 0,
                    data: heatPoints
                });

                map.fitBounds(bounds);
                google.maps.event.addListenerOnce(map, 'bounds_changed', function () {
                    if (map.getZoom() > 15) {
                        map.setZoom(15);
                    }
                });
            },
            error: function (e) {
                console.log(e);
            }
        });
    }
</script>
<script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?=get_merchant_google_key(NULL, 'admin_backend');?>&loading=async&callback=initMap">
</script>
@endsection