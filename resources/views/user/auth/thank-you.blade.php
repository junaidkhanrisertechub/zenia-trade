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
                                </a>
                                <p class="text-muted font-size-15 w-75 mx-auto mt-3 mb-0">Congratulations your submission
                                    has been successfully received .</p>
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
                                            <div class="avatar-lg mx-auto">
                                                <div
                                                    class="avatar-title bg-soft-primary text-primary display-5 rounded-circle">
                                                    <i class="uil uil-thumbs-up"></i>
                                                </div>
                                            </div>
                                            <div class="text-center mt-4 pt-2">
                                                <h4>Thank You !</h4>
                                                <p>You are now a registered member of <span class="fw-semibold">Zenia</span>
                                                </p>
                                                <p class="p-text">
                                                    You can login to your account using below credentials :
                                                </p>
                                                <div class="row">
                                                    <div class="col-sm-12 border-right text-center mt-3 mb-3">
                                                        <p class="c-yellow" style="color:#474743;">User Id</p>
                                                        <h4>{{$user_id}}</h4>
                                                    </div>
                                                    <div class="col-sm-12 border-right text-center mt-3 mb-3">
                                                        <p class="c-yellow" style="color:#474743;">Password</p>
                                                        <h4>{{$password}}</h4>
                                                    </div>
                                                </div>
                                                <p class="hint-text">Do not share your login details with anyone</p>
                                                <div class="mt-4">
                                                    <a href="{{url('/login')}}" class="btn btn-primary w-100">Login to Dashboard</a>
                                                </div>
                                            </div>
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

    <script>
        function CopyData(id, data) {
            var copyText = document.createRange();
            copyText.selectNode(document.getElementById(id));
            document.getSelection().removeAllRanges();
            document.getSelection().addRange(copyText);
            document.execCommand("copy");
            console.log(copyText);

            var tooltip = $(`#${data}`);
            tooltip.html(
                "<i class='fa fa-copy text-denger-icon text-gradient-primary'></i> Copied"
            );

            setTimeout(function() {
                tooltip.html(
                    "<i class='fa fa-copy text-denger-icon text-light'></i> Copy"
                );
            }, 3000);
        }
    </script>
@endsection
