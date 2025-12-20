<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Pending Users</title>
</head>
<body>
    <h1>Pending Users</h1>

    @if(session('success'))
        <div style="color:green;">{{ session('success') }}</div>
    @endif

    @if($pendingUsers->isEmpty())
        <p>No pending users.</p>
    @else
        <table border="1" cellpadding="5">
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
            @foreach($pendingUsers as $user)
                <tr>
                    <td>{{ $user->firstName }}</td>
                    <td>{{ $user->lastName }}</td>
                    <td>{{ $user->phone }}</td>
                    <td>{{ $user->email ?? '-' }}</td>
                    <td>
                        <form action="{{ route('admin.approveUser', $user->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit">Approve</button>
                        </form>
                        <form action="{{ route('admin.rejectUser', $user->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit">Reject</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
