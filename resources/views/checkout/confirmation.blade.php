<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
</head>

<style>
    html, body, * {
        font-family: "Roboto", sans-serif;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }


    .receipt {
        height: 100vh;
        display: flex;
        align-items: center;
        flex-direction: column;
        justify-content: space-around;
        border: 1px solid black;
    }
    .header {
        display: flex;
        gap: 20px;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        
    }

    .header h1{
        font-weight: 500;
    }

    .body {
        width: 40rem;
        border: 1px solid black;
    }


    .text-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-direction: row;
        width: 100%;
        margin: 5px;
    }

    .text-group .label {
    }

    .text-group .value {
        
    }

    .button-container a {
        padding: 10px 25px;
        background-color: rgba(248, 12, 12, 0.829);
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 18px;
        text-decoration: none;
    }

    .button-container a:hover {
        background-color: white; 
        color: rgba(248, 12, 12, 0.829);
        border: 1px solid rgba(248, 12, 12, 0.829);
        cursor: pointer;
        transition: 1s;
    }

</style>
<body>
    <div class="receipt">
        <div class="header">
            <h1> Payment Successful! </h1>
            <img src="{{ asset('images/check.gif') }}" alt="">
        </div>

        {{-- <div class="body">
            <div class="text-group">
                <p class="label"> Payment type </p>
                <p class="value"> Net Banking </p>
            </div> 
            <div class="text-group">
                <p class="label"> Payment type </p>
                <p class="value"> Net Banking </p>
            </div>  
            <div class="text-group">
                <p class="label"> Payment type </p>
                <p class="value"> Net Banking </p>
            </div>  
            <div class="text-group">
                <p class="label"> Payment type </p>
                <p class="value"> Net Banking </p>
            </div>  
        </div> --}}

        <div class="button-container">
            {{-- <button> Print </button> --}}
            <a href="/"> Go Back </a>
        </div>
        
    </div>

</body>
</html>