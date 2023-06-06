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
                                <p class="text-muted font-size-15 w-75 mx-auto mt-3 mb-0">Start your investment journey here
                                </p>
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
                    <div class="auth-bg bg-light py-md-5 p-4 d-flex min-vh-100-register">
                        <div class="bg-overlay-gradient"></div>
                        <!-- end bubble effect -->
                        <div class="row justify-content-center g-0 align-items-center w-100">
                            <div class="col-xl-9 col-lg-9">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="px-3 py-3">
                                            <div class="text-center">
                                                <h5 class="mb-0">Register Account</h5>
                                                <p class="text-muted mt-2">Simple, Secured & Profitable</p>
                                            </div>
                                            <form class="mt-4 pt-2 row" id="register_form" method="post" action="{{ url('/sign-up-user') }}">
                                                @csrf
                                                @php
                                                    if (request()->input('ref_id') != '' && request()->input('position') != '') {
                                                        $ref_id = request()->input('ref_id');
                                                        $post = request()->input('position');
                                                        $bttn = 'disabled';
                                                        $sid = 'readonly';
                                                    } else {
                                                        $ref_id = '';
                                                        $post = '';
                                                        $bttn = '';
                                                        $sid = '';
                                                    }
                                                @endphp
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-floating form-floating-custom">
                                                        <input type="text" class="form-control" name="ref_user_id"
                                                            id="referral_id" placeholder="Enter Sponsor ID"
                                                            value="{{ $ref_id }}" maxlength="11"
                                                            pattern="[A-Z0-9]{1,11}" style="text-transform: uppercase;"
                                                            {{ $sid }}>
                                                        <label for="referral_id">Sponsor ID</label>
                                                        <div class="form-floating-icon">
                                                            <i class="uil uil-users-alt"></i>
                                                        </div>
                                                        <span id="isUserAvailable"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <button type="button" class="btn btn-primary" id="getSponsorId"
                                                        {{ $bttn }}>
                                                        <i class="uil uil-user me-2"></i> Get Sponsor ID
                                                    </button>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-floating form-floating-custom">
                                                        <input type="text" class="form-control" id="fullname"
                                                            name="fullname" maxlength="30" placeholder="Enter Full Name">
                                                        <label for="input-username">Full Name</label>
                                                        <div class="form-floating-icon">
                                                            <i class="uil uil-users-alt"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-floating">
                                                        <select class="form-select" id="s_id" name="position"
                                                            aria-label="Floating label select example">
                                                            @php
                                                                if ($post != '') {
                                                                    if ($post == 1) {
                                                                        echo '<option value="1" selected>Left</option>';
                                                                    } elseif ($post == 2) {
                                                                        echo '<option value="2" selected>Right</option>';
                                                                    }
                                                                } else {
                                                                    echo '
                                                                            <option value="">Select Position</option>
                                                                            <option value="1" selected>Left</option>
                                                                            <option value="2">Right</option>
                                                                        ';
                                                                }
                                                            @endphp
                                                        </select>
                                                        <label for="floatingSelectGrid">Select Position</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-floating form-floating-custom">
                                                        <input type="email" class="form-control" id="email"
                                                            name="email" placeholder="Enter Email" required="">
                                                        <div class="invalid-feedback">
                                                            Please Enter Email ID
                                                        </div>
                                                        <label for="input-email">Email ID</label>
                                                        <div class="form-floating-icon">
                                                            <i class="uil uil-envelope-alt"></i>
                                                        </div>
                                                    </div>
                                                    {{-- <div class="tooltip2">
                                                        <span class="error-msg-size tooltip-inner text-danger">
                                                        </span>
                                                    </div> --}}
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-floating form-floating-custom">
                                                        <input type="email" class="form-control" id="confirm_email"
                                                            name="confirm_email" placeholder="Enter Email" required="">
                                                        <div class="invalid-feedback">
                                                            Please Confirm Email ID
                                                        </div>
                                                        <label for="input-email">Confirm Email ID</label>
                                                        <div class="form-floating-icon">
                                                            <i class="uil uil-envelope-alt"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-floating">
                                                        <select class="form-select" id="country" name="country"
                                                            aria-label="Floating label select example">
                                                            {{-- <option selected="">Country</option>
                                                            <option value="1">India</option>
                                                            <option value="2">USA</option> --}}
                                                        </select>
                                                        <label for="floatingSelectGrid">Select Country</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-floating form-floating-custom">
                                                        <input type="text" class="form-control" id="mobile"
                                                            name="mobile" maxlength="10"
                                                            placeholder="Enter Mobile Number">
                                                        <label for="input-username">Mobile Number</label>
                                                        <div class="form-floating-icon">
                                                            <i class="uil uil-users-alt"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div
                                                        class="form-floating form-floating-custom mb-3 auth-pass-inputgroup">
                                                        <input type="password" class="form-control" id="password"
                                                            name="password" placeholder="Enter Password">
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
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <div
                                                        class="form-floating form-floating-custom mb-3 auth-pass-inputgroup">
                                                        <input type="password" class="form-control" id="confirmpassword"
                                                            name="password_confirmation"
                                                            placeholder="Enter Confirm Password">
                                                        <button type="button"
                                                            class="btn btn-link position-absolute h-100 end-0 top-0"
                                                            id="password-addon2">
                                                            <i class="fas fa-regular fa-eye font-size-18 text-muted"></i>
                                                        </button>
                                                        <label for="password-input">Confirm Password</label>
                                                        <div class="form-floating-icon">
                                                            <i class="uil uil-padlock"></i>
                                                        </div>
                                                    </div>
                                                </div>





                                                <div class="py-1">
                                                    <input type="checkbox" checked name="terms_condition"
                                                        id="terms_condition" style="margin-right: 5px;">
                                                    By registering you agree to the Zenia <a href="#"
                                                        target="_blank">Terms &
                                                        Conditions</a>
                                                </div>
                                                <div class="col-md-12">
                                                    <p class="pass-note text-danger">
                                                        Tip:- Password must include at least six characters
                                                        while including
                                                        uppercase, lowercase,
                                                        numerical and special characters.
                                                    </p>
                                                </div>
                                                <div class="mt-2 mx-auto">
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
                                                    style="display: none;font-weight: 500;margin-bottom: 5px;font-size: 15px;color:red;">
                                                    Cpatch filed is required
                                                </p>
                                                <div class="mt-3">
                                                    <button class="btn btn-primary w-100" id="save_btn"
                                                        type="submit">Register</button>
                                                </div>
                                                <div class="mt-4 pt-3 text-center">
                                                    <p class="text-muted mb-0">Already have an account ?
                                                        <a href="{{ url('/login') }}"
                                                            class="fw-semibold text-decoration-underline">
                                                            Login
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
@endsection

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@if (getenv('APP_ENV') === 'local')
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('constants.settings.RECAPTCHA_SITE_KEY') }}">
    </script>
@else
    <script
        src="https://www.google.com/recaptcha/api.js?render={{ config('constants.settings.RECAPTCHA_SITE_KEY_live') }}">
    </script>
@endif
<script>
    var base_url = '{{ url('/') }}'
    $(document).ready(function() {

        $('#register_form').validate({
            errorPlacement: function(error, element) {
                if (element.parent('.input-group').length) {
                    error.insertAfter(element
                        .parent()); // place the error message after the input-group element
                } else {
                    error.insertAfter(element); // place the error message after the input element
                }
            },
            rules: {
                fullname: {
                    required: true,
                },
                position: {
                    required: true,
                },
                confirm_email: {
                    required: true,
                    email: true,
                    equalTo: "#email",
                },
                email: {
                    required: true,
                    email: true,
                    validate_email: true
                },
                country: {
                    required: true,
                },
                mobile: {
                    required: true,
                },
                password_confirmation: {
                    required: true,
                    equalTo: "#password",
                },
                password: {
                    required: true,
                    minlength: 8,
                    maxlength: 20,
                    strongPassword: true
                },
                terms_condition: {
                    required: true,
                }
            },
            messages: {
                fullname: {
                    required: "Full name is required.",
                },
                position: {
                    required: "Position is required.",
                },
                email: {
                    required: "Email is required.",
                },
                country: {
                    required: "Country is required.",
                },
                mobile: {
                    required: "Mobile number is required.",
                },
                password: {
                    required: "Password is required.",
                },
                password_confirmation: {
                    equalTo: "Your passwords do not match",
                    required: "Confirm password is required.",

                },
                confirm_email: {
                    equalTo: "Your email do not match",
                    required: "Confirm email is required.",
                },
                terms_condition: {
                    required: "Please check terms and conditions",
                },

            },
            submitHandler: function(form) {
                // verify Google reCAPTCHA
                var response = grecaptcha.getResponse();
                if (response.length == 0) {
                    $("#catcha_error").css('display', 'block')
                } else {
                    // submit form if reCAPTCHA validation succeeds
                    grecaptcha.execute('6LcWpJQlAAAAACeq-KmbHb3l8lngJxneYyF4AM2F', {
                        action: 'submit'
                    }).then(function(token) {
                        $.post("{{ url('/verify-recaptcha') }}", {
                                token: token,
                                action: 'submit',
                                _token: '{{ csrf_token() }}',
                            })
                            .done(function(data) {
                                form.submit();
                            })
                            .fail(function(error) {
                                console.log('reCAPTCHA verification failed.');
                            });

                    });
                }
            }
        });

        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: base_url + '/country',
            type: 'GET',
            success: function(data) {
                $('#country').empty();
                console.log(data.data.location_info.countryCode);
                $.each(data.data.country_list, function(key, value) {

                    if (data.data.location_info.countryCode == value.iso_code) {
                        console.log("Called this loop");
                        $('#country').append($('<option>', {
                            value: value.iso_code,
                            text: value.country,
                            selected: true
                        }));
                        //$('#country').append($('<option></option>').attr('value', value.iso_code).text(value.country)).prop("selected", true);
                    } else {
                        console.log("Called this loop 1");
                        $('#country').append($('<option></option>').attr('value', value
                            .iso_code).text(value.country));
                    }

                });
            }
        });


        $('#referral_id').on('input', function() {
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
                    if (data.code == 200) {
                        $("#isUserAvailable").css('display', 'block')
                        $("#isUserAvailable").removeClass('text-danger')
                        $("#isUserAvailable").addClass('text-success')
                        $("#isUserAvailable").text(data.message)

                    } else {
                        $("#isUserAvailable").addClass('text-danger')
                        $("#isUserAvailable").css('display', 'block')
                        $("#isUserAvailable").text(data.message)
                    }
                }
            });
        });
        $('#getSponsorId').click(function() {
            var defaultUserName = '{{ getDefaultUserName() }}';
            $("#referral_id").val(defaultUserName)
            getDefaultUser(defaultUserName);
        });

        function getDefaultUser(uid) {
            $.ajax({
                url: base_url + "/get-user-id",
                type: "POST",
                dataType: "json",
                headers: {
                    "X-CSRF-TOKEN": csrf_token
                },
                data: {
                    uid: uid
                },
                success: function(data) {
                    console.log(data)
                    if (data.code == 200) {
                        $("#isUserAvailable").css('display', 'block')
                        $("#isUserAvailable").removeClass('text-danger')
                        $("#isUserAvailable").addClass('text-success')
                        $("#isUserAvailable").text("User ID Available to Use")
                    } else {
                        $("#isUserAvailable").addClass('text-danger')
                        $("#isUserAvailable").css('display', 'block')
                        $("#isUserAvailable").text("This ID Already Taken, Please Use Another")
                        $("#save_btn").attr('disabled', true)
                    }
                }
            });
        }


        $('#reg-emailCopy').click(function() {

            var copyText = document.getElementById("email");
            copyText.select();
            document.execCommand("copy");
        });
    });



    window.onload = () => {
        const myInput = document.getElementById('confirm_email');
        myInput.onpaste = e => e.preventDefault();


        const password = document.getElementById('password');
        password.onpaste = e => e.preventDefault();

        const confirmpassword = document.getElementById('confirmpassword');
        confirmpassword.onpaste = e => e.preventDefault();

    }
</script>
