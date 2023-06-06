@extends('layouts.user_type.guest-app')
@section('content')
    <div class="auth-page d-flex align-items-center min-vh-100">
        <div class="container-fluid p-0">
            <div class="row g-0">
                <div class="col-xxl-3 col-lg-4 col-md-5">
                    <div class="d-flex flex-column h-100 py-5 px-4">
                        <div class="text-center text-muted mb-2">
                            <div class="pb-3">
                                <a href="{{ url('/') }}">
                                    <span class="logo-lg">
                                        <img src="{{ asset('user-assets/images/logo.svg') }}" alt="" height="30">
                                    </span>
                                </a>
                                <p class="text-muted font-size-15 w-75 mx-auto mt-3 mb-0">Enter your User Id and we'll send
                                    you instructions to reset your password</p>
                            </div>
                        </div>

                        <div class="my-auto">
                            <div class="p-3 text-center">
                                <img src="{{ asset('user-assets/images/auth-img.png') }}" alt="" class="img-fluid">
                            </div>
                        </div>

                        <div class="mt-4 mt-md-5 text-center">
                            <p class="mb-0">©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> Zenia.
                            </p>
                        </div>
                    </div>

                    <!-- end auth full page content -->
                </div>
                <!-- end col -->

                <div class="col-xxl-9 col-lg-8 col-md-7">
                    <div class="auth-bg bg-light py-md-5 p-4 d-flex">
                        <div class="bg-overlay-gradient"></div>
                        <!-- end bubble effect -->
                        <div class="row justify-content-center g-0 align-items-center w-100">
                            <div class="col-xl-6 col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="px-3 py-3">
                                            <div class="text-center">
                                                <h5 class="mb-0">Forgot Password</h5>
                                                <p class="text-muted mt-2">Forgot Your Password? </p>
                                            </div>
                                            <form class="mt-3" id="my-form" action="{{ route('forgot-password') }}" method="POST">
                                                @csrf
                                                <div class="form-floating form-floating-custom mb-3">
                                                    <input type="text" class="form-control" id="user_id" name="user_id"
                                                        placeholder="Enter User ID" style="text-transform: uppercase;">
                                                    <label for="user_id">User ID</label>
                                                    <div class="form-floating-icon">
                                                        <i class="uil uil-envelope-alt"></i>
                                                    </div>
                                                    <p class="text-danger" id="isUserAvailable"
                                                        style="display: none;font-weight: 500;margin-bottom: 5px;font-size: 15px;">
                                                        User not available
                                                    </p>
                                                </div>
                                                <div class="mt-4 text-center">
                                                    @if (getenv('APP_ENV') === 'local')
                                                        <div class="g-recaptcha"
                                                            data-sitekey="{{ config('constants.settings.RECAPTCHA_SITE_KEY_v2') }}">
                                                        @else
                                                            <div class="g-recaptcha"
                                                                data-sitekey="{{ config('constants.settings.RECAPTCHA_SITE_KEY_v2_live') }}">
                                                    @endif
                                                </div>
                                                <div id="recaptcha-score"></div>
                                                <p class="text-xs mb-0 error" id="catcha_error"
                                                    style="display: none;font-weight: 500;-bottom: 5px;font-size: 15px;color:red;">
                                                    Cpatch filed is required
                                                </p>
                                                <div class="mt-4">
                                                    <button type="submit" class="btn btn-primary w-100 save_btn" disabled>Send Request</button>
                                                </div>
                                                <div class="mt-4 text-center">
                                                    <p class="text-muted mb-0">Remember It ?
                                                        <a href="{{ url('/login') }}" class="fw-semibold text-decoration-underline">
                                                            Sign In
                                                        </a>
                                                    </p>
                                                </div>
                                            </form><!-- end form -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end container fluid -->
    </div>
    <!-- end authentication section -->


    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        
    {{-- <script src="https://www.google.com/recaptcha/api.js?render=6LcWpJQlAAAAACeq-KmbHb3l8lngJxneYyF4AM2F"></script> --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @if (getenv('APP_ENV') === 'local')
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('constants.settings.RECAPTCHA_SITE_KEY') }}">
        </script>
    @else
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('constants.settings.RECAPTCHA_SITE_KEY_live') }}">
        </script>
    @endif

    <script>
        $('#user_id').on('input', function() {
            var user_id = $(this).val();
            $.ajax({
                url: base_url + "/checkuserexist",
                type: "POST",
                dataType: "json",
                headers: {
                    "X-CSRF-TOKEN": csrf_token
                },
                data: {
                    user_id: user_id
                },
                success: function(data) {


                    console.log(data.message);
                    if (data.code == 200) {
                        $("#isUserAvailable").css('display', 'block')
                        $("#isUserAvailable").removeClass('text-danger')
                        $("#isUserAvailable").addClass('text-success')
                        //$("#isUserAvailable").text(data.response)
                        $("#isUserAvailable").text(data.message);
                        $(".save_btn").removeAttr('disabled')

                    } else if (data.code == 404) {
                        $("#isUserAvailable").addClass('text-danger')
                        $("#isUserAvailable").css('display', 'block')
                        //$("#isUserAvailable").text('Wrong user ID')
                        $("#isUserAvailable").text(data.message);
                        $(".save_btn").attr('disabled', true)
                    } else {
                        if (data.message == "Sponsor ID required")

                            //$("#isUserAvailable").css('display' , 'none')
                            $("#isUserAvailable").text("User ID required");

                        $(".save_btn").attr('disabled', true)
                    }

                }
            });
        });
    </script>
@endsection
