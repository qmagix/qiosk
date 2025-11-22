<!DOCTYPE html>
<html>
<head>
    <title>New User Registered</title>
</head>
<body>
    <h1>New User Registration</h1>
    <p>A new user has registered on the platform.</p>

    <ul>
        <li><strong>Name:</strong> {{ $user->name }}</li>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Registered At:</strong> {{ $user->created_at->format('Y-m-d H:i:s') }}</li>
    </ul>
</body>
</html>
