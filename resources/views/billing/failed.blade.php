<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Failed</title>
<style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: #ECF0FA;
        /* background: linear-gradient(135deg, #00AEEF, #3D4690); */
        font-family: 'Poppins', sans-serif;
        overflow: hidden;
    }

    .card {
        background: #fff;
        padding: 40px 30px;
        border-radius: 25px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        text-align: center;
        width: 350px;
        position: relative;
        animation: shake 0.5s;
    }

    .icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: #dc3545;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 50px;
        color: #ffffff;
    }

    h1 {
        color: #dc3545;
        margin-bottom: 15px;
    }

    p {
        color: #555;
        margin-bottom: 30px;
    }

    .btn {
        padding: 12px 25px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
        display: inline-block;
        margin: 5px;
        cursor: pointer;
    }

    .btn-primary {
        background: #00AEEF;
        color: #fff;
    }

    .btn-primary:hover {
        background: #007bbd;
    }

    @keyframes shake {
        0% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        50% { transform: translateX(10px); }
        75% { transform: translateX(-10px); }
        100% { transform: translateX(0); }
    }
</style>
</head>
<body>

<div class="card">
    <div class="icon">&#10060;</div>
    <h1>Payment Failed!</h1>
    <p>Something went wrong with your transaction. Please try again.</p>
    <a href="{{ 'https://'. $tenant->primaryDomain().'/dashboard' }}" class="btn btn-primary">Try Again</a>
</div>

</body>
</html>
