<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">

<head>
    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>  @yield('title', 'SheraziPOS') </title>
    <meta name="Description" content="Bootstrap Responsive Admin Web Dashboard HTML5 Template">
    <meta name="Author" content="Spruko Technologies Private Limited">
	<meta name="keywords" content="admin dashboard template,admin panel html,bootstrap dashboard,admin dashboard,html template,template dashboard html,html css,bootstrap 5 admin template,bootstrap admin template,bootstrap 5 dashboard,admin panel html template,dashboard template bootstrap,admin dashboard html template,bootstrap admin panel,simple html template,admin dashboard bootstrap">

    <!-- Favicon -->
    <link rel="icon" href="{{asset('backend')}}/assets/images/brand-logos/favicon.ico" type="image/x-icon">

    <!-- Choices JS -->
    <script src="{{asset('backend')}}/assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Main Theme Js -->
    <script src="{{asset('backend')}}/assets/js/main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="{{asset('backend')}}/assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet" >

    <!-- Style Css -->
    <link href="{{asset('backend')}}/assets/css/styles.min.css" rel="stylesheet" >

    <!-- Icons Css -->
    <link href="{{asset('backend')}}/assets/css/icons.css" rel="stylesheet" >

    <!-- Node Waves Css -->
    <link href="{{asset('backend')}}/assets/libs/node-waves/waves.min.css" rel="stylesheet" >

    <!-- Simplebar Css -->
    <link href="{{asset('backend')}}/assets/libs/simplebar/simplebar.min.css" rel="stylesheet" >

    <!-- Color Picker Css -->
    <link rel="stylesheet" href="{{asset('backend')}}/assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="{{asset('backend')}}/assets/libs/@simonwep/pickr/themes/nano.min.css">
    <link rel="stylesheet" href="{{asset('backend')}}/assets/css/select2.min.css">

    <!-- Choices Css -->
    <link rel="stylesheet" href="{{asset('backend')}}/assets/libs/choices.js/public/assets/styles/choices.min.css">

    @stack('css')

</head>

<body>

    @include('landlord.layouts.partials.switchtheme')

    <div class="page">
        @include('landlord.layouts.partials.topbar')
        <!-- Start::app-sidebar -->
        <aside class="app-sidebar sticky" id="sidebar">

            @include('landlord.layouts.partials.sidebar')

        </aside>
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
        <!-- End::app-content -->

        @include('landlord.layouts.partials.footer')

    </div>

    @yield('modals')

    <!-- Delete Confirm Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deleteConfirm">Delete</button>
                </div>
            </div>
        </div>
    </div>

    @include('landlord.layouts.partials.alerts')
    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="las la-angle-double-up"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    <!-- Scroll To Top -->
    <script>
        const baseUrl = "{{url('/')}}";
    </script>
    <!-- Popper JS -->
    <script src="{{asset('backend')}}/assets/js/jquery-3.7.1.min.js"></script>

    <script src="{{asset('backend')}}/assets/libs/@popperjs/core/umd/popper.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="{{asset('backend')}}/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Defaultmenu JS -->
    <script src="{{asset('backend')}}/assets/js/defaultmenu.min.js"></script>

    <!-- Node Waves JS-->
    <script src="{{asset('backend')}}/assets/libs/node-waves/waves.min.js"></script>

    <!-- Sticky JS -->
    <script src="{{asset('backend')}}/assets/js/sticky.js"></script>

    <!-- Simplebar JS -->
    <script src="{{asset('backend')}}/assets/libs/simplebar/simplebar.min.js"></script>
    <script src="{{asset('backend')}}/assets/js/simplebar.js"></script>
    <script src="{{asset('backend')}}/assets/js/select2.min.js"></script>

    <!-- Color Picker JS -->
    <script src="{{asset('backend')}}/assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>
    <script src="{{asset('backend')}}/assets/js/custom-selectall.js"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>


    @stack('js')

    <script src="{{asset('js/passwordToggle.js')}}"></script>
    <!-- Custom-Switcher JS -->
    <script src="{{asset('backend')}}/assets/js/custom-switcher.min.js"></script>
    <!-- Custom JS -->
    <script src="{{asset('backend')}}/assets/js/custom.js"></script>
    <script src="{{asset('backend')}}/assets/js/customalerts.js"></script>

    <script>
        function imageHandler() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = function() {
                const file = input.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('image', file);

                    $.ajax({
                        url: "{{ route('landlord.upload.image') }}",
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            if (data.url) {
                                const range = quill.getSelection();
                                quill.insertEmbed(range.index, 'image', data.url);
                            } else {
                                alert('Upload failed');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Upload error:', error);
                            alert('Image upload failed!');
                        }
                    });
                }
            };
        }
    </script>


</body>

</html>
