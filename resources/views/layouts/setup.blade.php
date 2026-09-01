<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SheraziPOS Setup</title>

    <link id="style" href="{{ url('backend') }}/assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="{{ url('backend') }}/assets/css/icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/flatpickr/plugins/monthSelect/style.css">
    <link rel="stylesheet" href="{{ url('backend') }}/assets/libs/@simonwep/pickr/themes/nano.min.css">
    <link rel="stylesheet" href="{{ url('backend') }}/assets/css/select2.min.css">
    @stack('css')
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top, #dbeafe, #f8fafc);
        }

        /* Select2-এর ডিফল্ট বর্ডার এবং অ্যারো লুক কাস্টমাইজ করা */
        .select2-container--default .select2-selection--single {
            border: 1px solid #dee2e6 !important;
            /* আপনার নরমাল ইনপুটের বর্ডার কালার */
            border-radius: 8px !important;
            /* আপনার ইনপুটের বর্ডার রেডিয়াস */
            height: 40px !important;
            /* ইনপুটের উচ্চতা */
            padding: 3px 10px !important;
        }

        /* টেক্সট এলাইনমেন্ট ঠিক করা */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 35px !important;
            padding-left: 0 !important;
            color: #495057;
        }

        /* অ্যারো আইকনটি নরমাল ইনপুটের সাথে মিলিয়ে দেওয়া */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important;
        }

        /* ফোকাস অবস্থায় বর্ডার যেন অন্যরকম না হয় */
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #86b7fe !important;
            /* box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important; */
        }
    </style>
</head>

<body class="min-vh-100 d-flex align-items-center justify-content-center p-3">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ url('backend') }}/assets/js/jquery-3.7.1.min.js"></script>
    <script src="{{ url('backend') }}/assets/libs/@popperjs/core/umd/popper.min.js"></script>
    <script src="{{ url('backend') }}/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{ url('backend') }}/assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>
    <script src="{{ url('backend') }}/assets/js/select2.min.js"></script>
    <script src="{{ url('backend/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ url('backend/assets/libs/flatpickr/plugins/monthSelect/index.js') }}"></script>
    <script src="{{ url('backend/assets/js/date&time_pickers.js') }}"></script>

    @stack('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
            });

            $('.datePicker').flatpickr({
                dateFormat : 'd-m-Y',
                width : '100%',
                static : true
            })
        })
    </script>
</body>

</html>
