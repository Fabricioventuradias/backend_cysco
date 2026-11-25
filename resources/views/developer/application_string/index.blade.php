@extends('developer.layouts.main')
@section('content')
<section class="content">
    {{--@include('admin.errors-and-messages')--}}
    <div class="box">
        <div class="box-header with-border">
            <h4 class="box-title"><i class="fa fa-language"></i> Merchant Application Strings</h4>
        </div>
        <div class="box-body">

            {{-- Search --}}
            <form method="GET" class="mb-20" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Search by name or email..."
                                   value="{{ $search ?? '' }}">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                    @if(!empty($search))
                        <div class="col-md-2">
                            <a href="{{ route('merchant-application-strings') }}" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Reset
                            </a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="30%">Business Name</th>
                            <th width="25%">Email</th>
                            <th width="40%">Configure / Edit Strings</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($merchants as $merchant)
                            <tr>
                                <td>{{ $merchant->id }}</td>
                                <td>{{ $merchant->BusinessName }}</td>
                                <td>{{ $merchant->email }}</td>
                                <td>
                                    {{-- Configure --}}
                                    <a class="btn btn-primary btn-sm"
                                       href="{{ route('merchant-application-strings-view', $merchant->id) }}?app_type=user"
                                       title="Configure User App">
                                        <i class="fa fa-user"></i> User
                                    </a>
                                    <a class="btn btn-warning btn-sm"
                                       href="{{ route('merchant-application-strings-view', $merchant->id) }}?app_type=driver"
                                       title="Configure Driver App">
                                        <i class="fa fa-car"></i> Driver
                                    </a>
                                    <a class="btn btn-success btn-sm"
                                       href="{{ route('merchant-application-strings-view', $merchant->id) }}?app_type=store"
                                       title="Configure Store App">
                                        <i class="fa fa-shopping-cart"></i> Store
                                    </a>
                                    <span style="margin:0 6px;color:#ccc;">|</span>
                                    {{-- Edit Strings --}}
                                    <a class="btn btn-default btn-sm"
                                       href="{{ route('merchant-application-strings-edit', $merchant->id) }}?app_type=user&locale=en"
                                       title="Edit User Strings">
                                        <i class="fa fa-edit"></i> Edit Strings
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No merchants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</section>
@endsection