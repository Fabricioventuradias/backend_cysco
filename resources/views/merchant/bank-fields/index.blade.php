@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">
                    <a href="{{ route('merchant.bank-fields.create') }}">
                        <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px" data-toggle="tooltip" title="Add New Field">
                            <i class="wb-plus"></i> Add New Field
                        </button>
                    </a>
                </div>
                <h3 class="panel-title">
                    <i class="fa fa-bank" aria-hidden="true"></i>
                    Bank Detail Fields
                </h3>
            </header>
            
            <div class="panel-body container-fluid">
                <!-- Country Filter Form -->
                

                <!-- Fields Table -->
                @if ($fields->isEmpty())
                    <div class="alert alert-info">
                        <p>No fields configured for this country yet.</p>
                        <a href="{{ route('merchant.bank-fields.create') }}" class="btn btn-primary">
                            Create First Field
                        </a>
                    </div>
                @else
                    <table id="bankFieldsTable" class="display nowrap table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Field Name</th>
                                <th>Display Text</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($fields as $field)
                                <tr>
                                    <td>
                                        <span class="badge badge-primary">{{ $field->field_order }}</span>
                                    </td>
                                    <td>
                                        <code>{{ $field->field_name }}</code>
                                    </td>
                                    <td>{{ $field->display_text }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $field->field_type }}</span>
                                    </td>
                                    <td>
                                        @if ($field->is_required)
                                            <span class="badge badge-danger">Required</span>
                                        @else
                                            <span class="badge badge-secondary">Optional</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($field->is_active)
                                            <span class="badge badge-success">
                                                <i class="fa fa-check"></i> Active
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fa fa-times"></i> Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('merchant.bank-fields.edit', $field->id) }}" 
                                           class="btn btn-sm btn-info menu-icon" 
                                           title="Edit Field">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                       
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No fields found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

       
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#bankFieldsTable').DataTable({
            "pageLength": 25,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, 'asc']]
        });
    });
</script>
@endsection