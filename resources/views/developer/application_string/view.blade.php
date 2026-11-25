@extends('developer.layouts.main')
@section('content')
<section class="content">
    {{--@include('admin.errors-and-messages')--}}
    <div class="box">
        <div class="box-header with-border">
            <h4 class="box-title">
                <i class="fa fa-cog"></i>
                String Configuration — {{ $merchant->BusinessName }}
                <span class="label label-{{ $app_type=='user' ? 'primary' : ($app_type=='driver' ? 'warning' : 'success') }}"
                      style="font-size:13px;vertical-align:middle;margin-left:8px;">
                    {{ ucfirst($app_type) }} App
                </span>
            </h4>
            <div class="box-tools pull-right" style="display:flex;gap:6px;align-items:center;">
                <a href="?app_type=user"   class="btn btn-sm {{ $app_type=='user'   ? 'btn-primary' : 'btn-default' }}"><i class="fa fa-user"></i> User</a>
                <a href="?app_type=driver" class="btn btn-sm {{ $app_type=='driver' ? 'btn-warning' : 'btn-default' }}"><i class="fa fa-car"></i> Driver</a>
                <a href="?app_type=store"  class="btn btn-sm {{ $app_type=='store'  ? 'btn-success' : 'btn-default' }}"><i class="fa fa-shopping-cart"></i> Store</a>
                <a href="{{ route('merchant-application-strings-edit', $merchant->id) }}?app_type={{ $app_type }}&locale=en"
                   class="btn btn-info btn-sm" style="margin-left:6px;">
                    <i class="fa fa-edit"></i> Edit Strings
                </a>
                <a href="{{ route('merchant-application-strings') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="box-body">

            {{-- Stats --}}
            <div class="row" style="margin-bottom:20px;">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-aqua"><i class="fa fa-list"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Configured</span>
                            <span class="info-box-number">{{ $total_configured }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-blue"><i class="fa fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">User (of {{ $global_key_counts->get(1, 0) }} keys)</span>
                            <span class="info-box-number">{{ $user_configured }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-yellow"><i class="fa fa-car"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Driver (of {{ $global_key_counts->get(2, 0) }} keys)</span>
                            <span class="info-box-number">{{ $driver_configured }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-green"><i class="fa fa-shopping-cart"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Store (of {{ $global_key_counts->get(3, 0) }} keys)</span>
                            <span class="info-box-number">{{ $store_configured }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                {{-- LEFT: Add new configuration --}}
                <div class="col-md-5">
                    <div class="box box-primary box-solid">
                        <div class="box-header">
                            <h4 class="box-title"><i class="fa fa-plus"></i> Add Module / Screen</h4>
                        </div>
                        <div class="box-body">
                            <form method="POST" action="{{ route('merchant-application-strings-save-config') }}" id="config-form">
                                @csrf
                                <input type="hidden" name="merchant_id" value="{{ $merchant->id }}">
                                <input type="hidden" name="app_type"    value="{{ $app_type }}">

                                <div class="form-group">
                                    <label>Module</label>
                                    <select id="module-select" class="form-control" required>
                                        <option value="">-- Select Module --</option>
                                        @foreach($available_modules as $mod)
                                            <option value="{{ $mod }}">{{ ucfirst($mod) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group" id="screens-container" style="display:none;">
                                    <label>Screens <small class="text-muted">(select one or more)</small></label>
                                    <div id="screens-list"></div>
                                </div>

                                <div id="selected-pairs-container"></div>

                                <button type="submit" class="btn btn-{{ $app_type=='user' ? 'primary' : ($app_type=='driver' ? 'warning' : 'success') }} btn-block"
                                        id="save-config-btn" disabled>
                                    <i class="fa fa-save"></i> Save Configuration
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Current configured pairs --}}
                <div class="col-md-7">
                    <div class="box box-default box-solid">
                        <div class="box-header">
                            <h4 class="box-title">
                                <i class="fa fa-list-ul"></i>
                                Configured for <span class="label label-{{ $app_type=='user' ? 'primary' : ($app_type=='driver' ? 'warning' : 'success') }}">{{ ucfirst($app_type) }}</span>
                            </h4>
                        </div>
                        <div class="box-body" style="padding:0;">
                            @if($configured->isNotEmpty())
                                @php $grouped = $configured->groupBy('module'); @endphp
                                <table class="table table-hover table-condensed" style="margin:0;">
                                    <thead>
                                        <tr>
                                            <th>Module</th>
                                            <th>Screen</th>
                                            <th width="80">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($grouped as $module => $screens)
                                            @foreach($screens as $i => $cfg)
                                                <tr>
                                                    @if($i === 0)
                                                        <td rowspan="{{ $screens->count() }}" style="vertical-align:middle;font-weight:600;">
                                                            <code>{{ $module }}</code>
                                                        </td>
                                                    @endif
                                                    <td><code>{{ $cfg->screen }}</code></td>
                                                    <td>
                                                        <button class="btn btn-danger btn-xs delete-config-btn"
                                                                data-merchant="{{ $merchant->id }}"
                                                                data-type="{{ $type_int }}"
                                                                data-module="{{ $cfg->module }}"
                                                                data-screen="{{ $cfg->screen }}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-warning" style="margin:15px;">
                                    <i class="fa fa-info-circle"></i>
                                    No modules configured for <strong>{{ ucfirst($app_type) }}</strong> app yet.
                                    Use the form on the left to add some.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
(function () {
    'use strict';

    var appType     = '{{ $app_type }}';
    var merchantId  = '{{ $merchant->id }}';
    var selectedPairs = [];   // [{module, screen}, ...]

    // ââ Load screens when module changes âââââââââââââââââââââââââââââââââââââ

    document.getElementById('module-select').addEventListener('change', function () {
        var module = this.value;
        var container = document.getElementById('screens-container');
        var list      = document.getElementById('screens-list');

        if (!module) { container.style.display = 'none'; return; }

        list.innerHTML = '<p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading...</p>';
        container.style.display = 'block';

        fetch('{{ route('merchant-application-strings-get-screens') }}?app_type=' + appType + '&module=' + module)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                list.innerHTML = '';
                if (!data.screens || data.screens.length === 0) {
                    list.innerHTML = '<p class="text-muted">No screens found for this module.</p>';
                    return;
                }
                data.screens.forEach(function (screen) {
                    var label = document.createElement('label');
                    label.style.cssText = 'display:block;font-weight:normal;cursor:pointer;';
                    label.innerHTML =
                        '<input type="checkbox" class="screen-checkbox" value="' + screen + '" ' +
                        'data-module="' + module + '" style="margin-right:6px;"> ' +
                        '<code>' + screen + '</code>';
                    list.appendChild(label);
                });
                list.querySelectorAll('.screen-checkbox').forEach(function (cb) {
                    cb.addEventListener('change', updateSelectedPairs);
                });
            });
    });

    // ââ Track selected module+screen pairs ââââââââââââââââââââââââââââââââââââ

    function updateSelectedPairs() {
        selectedPairs = [];
        var checkboxes = document.querySelectorAll('.screen-checkbox:checked');
        checkboxes.forEach(function (cb) {
            selectedPairs.push({ module: cb.dataset.module, screen: cb.value });
        });

        // Rebuild hidden inputs
        var container = document.getElementById('selected-pairs-container');
        container.innerHTML = '';
        var grouped = {};
        selectedPairs.forEach(function (p) {
            if (!grouped[p.module]) grouped[p.module] = [];
            grouped[p.module].push(p.screen);
        });

        var idx = 0;
        Object.keys(grouped).forEach(function (mod) {
            var input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'modules[' + idx + '][module]';
            input.value = mod;
            container.appendChild(input);
            grouped[mod].forEach(function (scr, si) {
                var si2 = document.createElement('input');
                si2.type  = 'hidden';
                si2.name  = 'modules[' + idx + '][screens][' + si + ']';
                si2.value = scr;
                container.appendChild(si2);
            });
            idx++;
        });

        document.getElementById('save-config-btn').disabled = selectedPairs.length === 0;
    }

    // ââ Delete single config entry ââââââââââââââââââââââââââââââââââââââââââââ

    document.querySelectorAll('.delete-config-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Remove ' + this.dataset.module + '/' + this.dataset.screen + ' from configuration?')) return;
            var data = {
                merchant_id: this.dataset.merchant,
                type:        this.dataset.type,
                module:      this.dataset.module,
                screen:      this.dataset.screen,
                _token:      '{{ csrf_token() }}'
            };
            fetch('{{ route('merchant-application-strings-delete-config') }}', {
                method:  'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token },
                body:    JSON.stringify(data)
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) { location.reload(); }
                else { alert('Error: ' + res.message); }
            });
        });
    });
}());
</script>
@endsection