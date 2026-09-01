<style>
    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 250px;
        max-width: 400px;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        opacity: 0;
        transform: translateX(100%);
        animation: slideIn 0.5s forwards, fadeOut 0.5s 4.5s forwards;
        color: #fff;
    }

    .floating-alert.alert-success {
        background-color: #28a745 !important;
    }
    .floating-alert.alert-danger {
        background-color: #dc3545 !important;
    }
    .floating-alert.alert-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }
    .floating-alert.alert-info {
        background-color: #17a2b8 !important;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
</style>

@if (session('success'))
    <div class="alert alert-success floating-alert">
        <strong>Success!</strong> {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger floating-alert">
        <strong>Error!</strong> {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning floating-alert">
        <strong>Warning!</strong> {{ session('warning') }}
    </div>
@endif

@if (session('message'))
    <div class="alert alert-info floating-alert">
        <strong>Message!</strong> {{ session('message') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger floating-alert">
        <strong>Error!</strong>  
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif