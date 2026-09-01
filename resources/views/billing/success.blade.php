<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Success</title>
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
        z-index: 1;
    }

    .icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: #00AEEF;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 50px;
        color: #fff;
        animation: bounce 1s;
    }

    h1 {
        color: #00AEEF;
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

    .btn-secondary {
        border: 2px solid #2c2f6a;
        color: #2c2f6a;
    }

    .btn-secondary:hover {
        background: #2c2f6a;
        color: #fff;
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
        40% {transform: translateY(-15px);}
        60% {transform: translateY(-7px);}
    }

    /* Confetti */
    .confetti {
        position: absolute;
        width: 10px;
        height: 10px;
        background: red;
        top: -10px;
        z-index: 0;
        opacity: 0.8;
        animation: fall 3s linear infinite;
    }

    @keyframes fall {
        0% {transform: translateY(0) rotate(0deg);}
        100% {transform: translateY(800px) rotate(360deg);}
    }
</style>
</head>
<body>

<div class="card">
    <div class="icon">&#10003;</div>
    <h1>Payment Successful!</h1>
    <p>Your transaction has been completed successfully.</p>
    <a href="{{ 'https://'. $tenant->primaryDomain() }}" class="btn btn-primary">Go to Dashboard</a>
    <a href="#" class="btn btn-secondary">Download Invoice</a>
</div>

<script>
    // Confetti effect
    for(let i=0;i<50;i++){
        const confetti = document.createElement('div');
        confetti.classList.add('confetti');
        confetti.style.left = Math.random() * window.innerWidth + 'px';
        confetti.style.background = `hsl(${Math.random()*360}, 100%, 50%)`;
        confetti.style.animationDuration = (Math.random()*3 + 2) + 's';
        confetti.style.width = confetti.style.height = (Math.random()*7 + 7) + 'px';
        document.body.appendChild(confetti);
    }
</script>

</body>
</html>
