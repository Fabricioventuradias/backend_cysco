@extends('developer.layouts.main')
@section('content')
<section class="content">
    {{--@include('admin.errors-and-messages')--}}
    <div class="box">
        <div class="box-header with-border">
            <h4 class="box-title">
                <i class="fa fa-edit"></i>
                Edit Strings — {{ $merchant->BusinessName }}
                <span class="label label-{{ $app_type=='user' ? 'primary' : ($app_type=='driver' ? 'warning' : 'success') }}"
                      style="font-size:13px;vertical-align:middle;margin-left:8px;">
                    {{ ucfirst($app_type) }} App
                </span>
                <span class="label label-default" style="font-size:13px;vertical-align:middle;margin-left:4px;">
                    {{ strtoupper($locale) }}
                </span>
            </h4>
            <div class="box-tools pull-right" style="display:flex;gap:6px;align-items:center;">
                {{-- App type switcher --}}
                <a href="?app_type=user&locale={{ $locale }}"
                   class="btn btn-sm {{ $app_type=='user' ? 'btn-primary' : 'btn-default' }}">
                    <i class="fa fa-user"></i> User
                </a>
                <a href="?app_type=driver&locale={{ $locale }}"
                   class="btn btn-sm {{ $app_type=='driver' ? 'btn-warning' : 'btn-default' }}">
                    <i class="fa fa-car"></i> Driver
                </a>
                <a href="?app_type=store&locale={{ $locale }}"
                   class="btn btn-sm {{ $app_type=='store' ? 'btn-success' : 'btn-default' }}">
                    <i class="fa fa-shopping-cart"></i> Store
                </a>
                <a href="{{ route('merchant-application-strings-view', $merchant->id) }}?app_type={{ $app_type }}"
                   class="btn btn-default btn-sm" style="margin-left:6px;">
                    <i class="fa fa-cog"></i> Configure
                </a>
                <a href="{{ route('merchant-application-strings') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="box-body">

            {{-- Filters --}}
            <form method="GET" class="form-inline" style="margin-bottom:20px;">
                <input type="hidden" name="app_type" value="{{ $app_type }}">

                <div class="form-group" style="margin-right:10px;">
                    <label style="margin-right:5px;">Module:</label>
                    <select name="module" class="form-control">
                        <option value="">-- All --</option>
                        @foreach($configured_modules as $mod)
                            <option value="{{ $mod }}" {{ $filter_module==$mod ? 'selected' : '' }}>
                                {{ ucfirst($mod) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Locale selector — admin can edit any locale for this merchant --}}
                <div class="form-group" style="margin-right:10px;">
                    <label style="margin-right:5px;">Locale:</label>
                    <select name="locale" class="form-control">
                        {{-- Common locales + any already used by this merchant --}}
                        @php
                            $allLocales = array_unique(array_merge(
                                ['en','ar','fr','es','pt-BR','de','tr','ru','zh'],
                                $used_locales
                            ));
                            sort($allLocales);
                        @endphp
                        @foreach($allLocales as $loc)
                            <option value="{{ $loc }}" {{ $locale==$loc ? 'selected' : '' }}>
                                {{ strtoupper($loc) }}
                                @if(in_array($loc, $used_locales)) â @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-right:10px;">
                    <label style="margin-right:5px;">Search:</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="key, value, module, screen..."
                           value="{{ $search ?? '' }}" style="width:200px;">
                </div>

                <button type="submit" class="btn btn-{{ $app_type=='user' ? 'primary' : ($app_type=='driver' ? 'warning' : 'success') }}">
                    <i class="fa fa-filter"></i> Filter
                </button>
                <a href="{{ route('merchant-application-strings-edit', $merchant->id) }}?app_type={{ $app_type }}&locale={{ $locale }}"
                   class="btn btn-default">
                    <i class="fa fa-refresh"></i> Reset
                </a>
            </form>

            {{-- Stats --}}
            <div class="alert alert-{{ $app_type=='user' ? 'info' : ($app_type=='driver' ? 'warning' : 'success') }}">
                <div class="row">
                    <div class="col-md-2"><strong>App:</strong>
                        <span class="label label-{{ $app_type=='user' ? 'primary' : ($app_type=='driver' ? 'warning' : 'success') }}">
                            {{ ucfirst($app_type) }}
                        </span>
                    </div>
                    <div class="col-md-2"><strong>Locale:</strong>
                        <span class="label label-default">{{ strtoupper($locale) }}</span>
                    </div>
                    <div class="col-md-2"><strong>Total:</strong> {{ $total_strings }}</div>
                    <div class="col-md-2"><strong class="text-success">Translated:</strong> {{ $translated_count }}</div>
                    <div class="col-md-2"><strong class="text-warning">Pending:</strong> {{ $pending_count }}</div>
                    <div class="col-md-2">
                        <strong>Progress:</strong>
                        {{ $total_strings > 0 ? round(($translated_count / $total_strings) * 100) : 0 }}%
                    </div>
                </div>
            </div>

            @if(count($localizations) > 0)
                <form method="POST" action="{{ route('merchant-application-strings-update') }}" id="localization-form">
                    @csrf
                    <input type="hidden" name="merchant_id" value="{{ $merchant->id }}">
                    <input type="hidden" name="type"        value="{{ $type_int }}">
                    <input type="hidden" name="app_type"    value="{{ $app_type }}">
                    <input type="hidden" name="locale"      value="{{ $locale }}">

                    <div class="row">
                        @foreach($localizations as $index => $loc)
                            <div class="col-md-4">
                                <div class="form-group string-card {{ $loc['is_translated'] ? 'translated' : 'pending' }}">
                                    <label>
                                        <span class="string-number">{{ $index + 1 }}.</span>
                                        {{-- key_name is the label — no en_value column --}}
                                        {{ $loc['key_name'] }}
                                        @if($loc['is_translated'])
                                            <i class="fa fa-check-circle text-success pull-right"></i>
                                        @else
                                            <i class="fa fa-exclamation-circle text-warning pull-right"></i>
                                        @endif
                                    </label>
                                    <div class="string-meta">
                                        <small class="text-muted">
                                            <strong>Module:</strong> {{ $loc['module'] }} &nbsp;|&nbsp;
                                            <strong>Screen:</strong> {{ $loc['screen'] }}
                                        </small>
                                    </div>
                                    <input type="hidden" name="items[{{ $index }}][module]"   value="{{ $loc['module'] }}">
                                    <input type="hidden" name="items[{{ $index }}][screen]"   value="{{ $loc['screen'] }}">
                                    <input type="hidden" name="items[{{ $index }}][key_name]" value="{{ $loc['key_name'] }}">
                                    <input type="text"
                                           name="items[{{ $index }}][value]"
                                           value="{{ $loc['current_value'] }}"
                                           class="form-control translation-input"
                                           placeholder="Enter {{ strtoupper($locale) }} translation...">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Fixed Bottom Save Bar --}}
                    <div class="save-bar save-bar-{{ $app_type }}">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-8">
                                    <button type="submit"
                                            class="btn btn-{{ $app_type=='user' ? 'primary' : ($app_type=='driver' ? 'warning' : 'success') }} btn-lg">
                                        <i class="fa fa-save"></i> Save {{ ucfirst($app_type) }} [{{ strtoupper($locale) }}]
                                    </button>
                                    <span class="text-muted" style="margin-left:15px;">
                                        <i class="fa fa-info-circle"></i>
                                        <strong>{{ count($localizations) }}</strong> strings —
                                        {{ $merchant->BusinessName }}
                                    </span>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button type="button" class="btn btn-default" onclick="window.scrollTo(0,0)">
                                        <i class="fa fa-arrow-up"></i> Top
                                    </button>
                                    <button type="button" class="btn btn-warning" id="scroll-to-pending">
                                        <i class="fa fa-search"></i> First Pending
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="alert alert-warning">
                    <i class="fa fa-info-circle"></i>
                    No strings found for the selected filters.
                    @if(!empty($filter_module) || !empty($search))
                        Try clearing your filters.
                    @else
                        Make sure modules are configured for the <strong>{{ ucfirst($app_type) }}</strong> app type
                        and keys exist in the global key table.
                    @endif
                </div>
            @endif

        </div>
    </div>
</section>

<div id="loading-overlay" style="display:none;">
    <div class="loading-content">
        <i class="fa fa-spinner fa-spin fa-3x"></i>
        <p>Saving translations...</p>
    </div>
</div>

<style>
.string-card {
    border: 1px solid #ddd; padding: 10px; margin-bottom: 15px;
    border-radius: 4px; background: #fff; transition: all 0.2s;
}
.string-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-color: #66afe9; }
.string-card.translated { border-left: 3px solid #5cb85c; }
.string-card.pending    { border-left: 3px solid #f0ad4e; background-color: #fffbf0; }
.string-card label {
    font-weight: 600; margin-bottom: 5px; display: block;
    color: #333; font-size: 13px; line-height: 1.4; word-wrap: break-word;
}
.string-number { color: #999; font-size: 12px; }
.string-meta   { margin-bottom: 8px; padding: 4px 0; border-bottom: 1px solid #f0f0f0; }
.string-meta small { font-size: 11px; color: #777; }

.save-bar {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #fff; border-top: 3px solid #ddd;
    padding: 15px 0; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1000;
}
.save-bar-user   { border-top-color: #337ab7; }
.save-bar-driver { border-top-color: #f0ad4e; }
.save-bar-store  { border-top-color: #5cb85c; }
.box-body { padding-bottom: 80px; }

#loading-overlay {
    position: fixed; top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,0.7); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
}
.loading-content {
    background: #fff; padding: 30px 50px; border-radius: 8px; text-align: center;
}
.loading-content p { margin-top: 15px; font-size: 16px; color: #333; }
</style>

<script>
(function () {
    'use strict';

    var form        = document.getElementById('localization-form');
    var formChanged = false;

    if (form) {
        form.addEventListener('submit', function () {
            document.getElementById('loading-overlay').style.display = 'flex';
            formChanged = false;
        });
    }

    document.querySelectorAll('.translation-input').forEach(function (input) {
        input.addEventListener('input', function () {
            formChanged = true;
            var card = this.closest('.string-card');
            if (this.value.trim() !== '') {
                card.classList.remove('pending');
                card.classList.add('translated');
                var icon = card.querySelector('.fa-exclamation-circle');
                if (icon) { icon.className = 'fa fa-check-circle text-success pull-right'; }
            } else {
                card.classList.remove('translated');
                card.classList.add('pending');
                var icon2 = card.querySelector('.fa-check-circle');
                if (icon2) { icon2.className = 'fa fa-exclamation-circle text-warning pull-right'; }
            }
        });
    });

    window.addEventListener('beforeunload', function (e) {
        if (formChanged) { e.preventDefault(); e.returnValue = ''; }
    });

    var pendingBtn = document.getElementById('scroll-to-pending');
    if (pendingBtn) {
        pendingBtn.addEventListener('click', function () {
            var first = document.querySelector('.string-card.pending');
            if (first) {
                var y = first.getBoundingClientRect().top + window.pageYOffset - 100;
                window.scrollTo({ top: y, behavior: 'smooth' });
                setTimeout(function () {
                    var inp = first.querySelector('.translation-input');
                    if (inp) inp.focus();
                }, 500);
            } else {
                alert('All strings are translated for this locale!');
            }
        });
    }
}());
</script>
@endsection