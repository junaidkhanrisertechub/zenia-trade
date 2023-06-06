@extends('layouts.user_type.guest-app')
@section('content')
    <div class="auth-page d-flex align-items-center min-vh-100">
        <div class="container-fluid p-0">
            <div class="row g-0">
                <div class="col-xxl-3 col-lg-4 col-md-5">
                    <div class="d-flex flex-column h-100 py-5 px-4">
                        <div class="text-center text-muted mb-2">
                            <div class="pb-3">
                                <a href="index.html">
                                    <span class="logo-lg">
                                        <img src="{{asset('user-assets/images/logo.svg')}}" alt="" height="30">
                                    </span>
                                </a>
                                <p class="text-muted font-size-15 w-75 mx-auto mt-3 mb-0">Re-Password with Zenia</p>
                            </div>
                        </div>

                        <div class="my-auto">
                            <div class="p-3 text-center">
                                <img src="{{asset('user-assets/images/auth-img.png')}}" alt="" class="img-fluid">
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
                                                <h5 class="mb-0">Reset Password</h5>
                                            </div>
                                            <div class="alert font-size-14 alert-success text-center mb-3 mt-3"
                                                role="alert">
                                                Note:- Password must be more than 8 characters. It should contain uppercase,
                                                lowercase, numerical and special characters.
                                            </div>
                                            <form class="mt-4 pt-2" action="{{ route('reset-password') }}" method="POST" id="reset-form">
                                             @csrf
                                             <input type="hidden" name="token" value="{{ $token }}">
                                                <div class="form-floating form-floating-custom mb-3">
                                                    <input type="text" class="form-control" id="user_id"
                                                        placeholder="User ID" autofocus="" disabled value="{{ $user_id }}">
                                                        <input type="hidden" name="user_id" value="{{ $user_id }}">
                                                    <label for="user_id">User Id</label>
                                                    <div class="form-floating-icon">
                                                        <i class="uil uil-padlock"></i>
                                                    </div>
                                                </div>

                                                <div class="form-floating form-floating-custom mb-3">
                                                   <input type="password" class="form-control" id="password" name="password" autofocus=""
                                                       placeholder="Password">
                                                   <label for="input-newpassword">New Password</label>
                                                   <div class="form-floating-icon">
                                                       <i class="uil uil-padlock"></i>
                                                   </div>
                                               </div>

                                                <div class="form-floating form-floating-custom mb-3">
                                                    <input type="password" class="form-control" id="confirmpassword" name="confirm_password"
                                                        placeholder="Retype Password" autofocus="">
                                                    <label for="input-confirmpassword">Confirm Password</label>
                                                    <div class="form-floating-icon">
                                                        <i class="uil uil-check-circle"></i>
                                                    </div>
                                                </div>
                                                {{-- <div class="mt-4 text-center">
                                                    Please add capcha v3
                                                </div> --}}
                                                <div class="mt-4">
                                                    <button class="btn btn-primary w-100" id="save_btn" type="submit">Reset</button>
                                                </div>

                                                <div class="mt-4 text-center">
                                                    <p class="text-muted mb-0">Remember It ? <a href="{{ url('/login') }}"
                                                            class="fw-semibold text-decoration-underline"> Sign In </a> </p>
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


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#reset-form').validate({
                errorPlacement: function(error, element) {
                    if (element.parent('.input-group').length) {
                        error.insertAfter(element
                            .parent()); // place the error message after the input-group element
                    } else {
                        error.insertAfter(element); // place the error message after the input element
                    }
                },
                rules: {
                    password: {
                        required: true,
                        minlength: 6,
                        maxlength: 20,
                        strongPassword: true
                    },
                    confirm_password: {
                        required: true,
                        equalTo: "#password"
                    },
                },

                messages: {
                    password: {
                        required: "New passowrd is required.",
                    },
                    confirm_password: {
                        required: "Confirm password is required.",
                        equalTo: "New password and confirm password not match",
                    },
                }
            });

        });
    </script>
@endsection
