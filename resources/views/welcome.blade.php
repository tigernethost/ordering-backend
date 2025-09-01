<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            background-color: #fffff; /* Optional background color */
        }
        /* Center the logo container */
        .container {
            text-align: center;
        }

        /* Style the logo */
        .logo {
            max-width: 100%;
            height: auto;
            width: 150px; /* Adjust size as needed */
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('images/makimura.jpg') }}" alt="Logo" class="logo"><br/>
        Angeles City, Philippines<br/>
        makimura.ramen@gmail.com
    </div>
</body>
</html>