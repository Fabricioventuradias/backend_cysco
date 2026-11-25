<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fleet Details</title>

    <link rel="stylesheet" href="{{ asset('global/css/bootstrap.min.css') }}">
</head>
<body>

<div class="container mt-5">

    <div class="card">
        <div class="card-header">
            <h3>{{ $merchant->BusinessName }}</h3>
        </div>

        <div class="card-body">

            <h4 class="mb-4">Fleet Information</h4>

            <table class="table table-bordered">
 

                <tr>
                    <th>Fleet Name</th>
                    <td>{{ $user->fleet_name }}</td>
                </tr>

                <tr>
                    <th>Fleet Owner Name</th>
                    <td>{{ $user->fleet_owner_name }}</td>
                </tr>

                <tr>
                    <th>Phone</th>
                    <td>{{ $user->fleet_owner_phone }}</td>
                </tr>

                <tr>
                    <th>Email</th>
                    <td>{{ $user->fleet_owner_email }}</td>
                </tr>

                <tr>
                    <th>Address</th>
                    <td>{{ $user->address }}</td>
                </tr>

                <tr>
                    <th>Bank Name</th>
                    <td>{{ $user->bank_name }}</td>
                </tr>

                <tr>
                    <th>Merchant</th>
                    <td>{{ $merchant->BusinessName }}</td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td>
                        @if(isset($user->fleet_status))
                            {{ $user->fleet_status }}
                        @endif
                    </td>
                </tr>

                <tr>
                    <th>Created At</th>
                    <td>{{ $user->created_at }}</td>
                </tr>
            </table>

            <form method="POST" action="{{ route('fleet.delete') }}">
                @csrf

                <input type="hidden" name="fleet_id" value="{{ $user->id }}">

                <button type="submit" class="btn btn-danger">
                    Delete Account
                </button>
            </form>

            <a href="{{ route('fleet.logout') }}" class="btn btn-secondary mt-2">
                Logout
            </a>

        </div>
    </div>

</div>

</body>
</html>