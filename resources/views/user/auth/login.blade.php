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
                                        <img src="{{ asset('user-assets/images/logo.svg')}}" alt="" height="30">
                                    </span>
                                </a>
                                <p class="text-muted font-size-15 w-75 mx-auto mt-3 mb-0">Login your account and continue
                                    your journey.</p>
                            </div>
                        </div>

                        <div class="my-auto">
                            <div class="p-3 text-center">
                                <img src="{{ asset('user-assets/images/auth-img.png')}}" alt="" class="img-fluid">
                            </div>
                        </div>

                        <div class="mt-4 mt-md-5 text-center">
                            <p class="mb-0">©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> Zenia
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
                                                <h5 class="mb-0">Welcome Back !</h5>
                                                <p class="text-muted mt-2">Sign in to continue to Zenia.</p>
                                            </div>
                                            <form class="mt-4 pt-2" method="post" action="{{ url('/store-login') }}"
                                                id="my-form">
                                                @csrf
                                                <div class="form-floating form-floating-custom mb-3">
                                                    <input type="text" class="form-control" name="user_id" id="user_id"
                                                        placeholder="Enter User ID">
                                                    <label for="input-username">User ID</label>
                                                    <div class="form-floating-icon">
                                                        <i class="uil uil-users-alt"></i>
                                                    </div>
                                                    <span id="isUserAvailable"></span>
                                                </div>
                                                <input type="hidden" id="auto_add_hscc_check" class="form-control" value="1">
                                                <div class="form-floating form-floating-custom mb-3 auth-pass-inputgroup">
                                                    <input type="password" class="form-control" name="password"
                                                        id="password" placeholder="Enter Password">
                                                    <button type="button"
                                                        class="btn btn-link position-absolute h-100 end-0 top-0"
                                                        id="password-addon">
                                                        <i class="fas fa-regular fa-eye font-size-18 text-muted"></i>
                                                    </button>
                                                    <label for="password-input">Password</label>
                                                    <div class="form-floating-icon">
                                                        <i class="uil uil-padlock"></i>
                                                    </div>

                                                </div>
                                                <div class="form-check form-check-primary font-size-16 py-1">
                                                    <div class="float-end">
                                                        <a href="{{ url('/forget-password') }}"
                                                            class="text-muted text-decoration-underline font-size-14">Forgot
                                                            your password?</a>
                                                    </div>
                                                </div>

                                                <div class="mt-4 center-align">
                                                    <div>
                                                    @if ($data['appenv'] === 'local')
                                                        <div class="g-recaptcha"
                                                            data-sitekey="{{ config('constants.settings.RECAPTCHA_SITE_KEY_v2') }}">
                                                        @else
                                                            <div class="g-recaptcha"
                                                                data-sitekey="{{ config('constants.settings.RECAPTCHA_SITE_KEY_v2_live') }}">
                                                    @endif
                                                    </div>
                                                </div>
                                                <div id="recaptcha-score"></div>
                                                <p class="text-xs mb-0 error" id="catcha_error"
                                                    style="display: none;font-weight: 500;margin-bottom: 5px;font-size: 15px;color:red;">
                                                    Cpatch filed is required
                                                </p>

                                                <div class="mt-3">
                                                    <button class="btn btn-primary w-100" type="submit">Log In</button>
                                                </div>



                                                <div class="mt-4 pt-3 text-center">
                                                    <p class="text-muted mb-0">Don't have an account ? <a
                                                            href="{{ url('/sign-up') }}"
                                                            class="fw-semibold text-decoration-underline"> Signup Now </a>
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

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @if (getenv('APP_ENV') === 'local')
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('constants.settings.RECAPTCHA_SITE_KEY') }}">
        </script>
    @else
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('constants.settings.RECAPTCHA_SITE_KEY_live') }}">
        </script>
    @endif
    <script>
        $(document).ready(function() {
            $('#my-form').validate({
                errorPlacement: function(error, element) {
                    if (element.parent('.input-group').length) {
                        error.insertAfter(element
                            .parent()); // place the error message after the input-group element
                    } else {
                        error.insertAfter(element); // place the error message after the input element
                    }
                },
                rules: {
                    user_id: {
                        required: true,
                    },
                    password: {
                        required: true,
                        // minlength: 6,
                        // maxlength: 20,
                        // strongPassword: true
                    },
                },
                messages: {
                    user_id: {
                        required: "User ID is required"
                    },
                    password: {
                        required: "Password is required"
                    }
                }
            });
        });


        // function checkUserId() {
        $('#user_id').on('input', function() {
            var user_id = $(this).val();
            var auto_add_hscc_check = $('#auto_add_hscc_check').val();
            if (user_id.length === 1 && auto_add_hscc_check == 1) {
                $(this).val("ZEN");
            }
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
                    if (data.code == 200) {
                        $("#isUserAvailable").css('display', 'block')
                        $("#isUserAvailable").removeClass('text-danger')
                        $("#isUserAvailable").addClass('text-success')
                        $("#isUserAvailable").text('User available')
                        $("#save_btn").removeAttr('disabled')

                    } else if (data.code == 404) {
                        $("#isUserAvailable").addClass('text-danger')
                        $("#isUserAvailable").css('display', 'block')
                        $("#isUserAvailable").text('User not available')
                        $("#save_btn").attr('disabled', true)
                    } else {
                        $("#isUserAvailable").css('display', 'none')
                        $("#save_btn").attr('disabled', true)
                    }
                    $('#auto_add_hscc_check').val(0);
                }
            });
            // }
        });
    </script>
@endsection
