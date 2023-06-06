<!doctype html>
<html dir="ltr" lang="en">

<head>

    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
    <title>Zenia</title>
    <link rel="shortcut icon" href="{{ asset('user-assets/images/favicon.ico') }}">

    <!-- plugin css -->
    <link href="{{ asset('user-assets/libs/jsvectormap/css/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- gridjs css -->
    <link rel="stylesheet" href="{{ asset('user-assets/libs/gridjs/theme/mermaid.min.css') }}">

    <!-- swiper css -->
    <link rel="stylesheet" href="{{ asset('user-assets/libs/swiper/swiper-bundle.min.css') }}">

    <!-- Bootstrap Css -->
    <link href="{{ asset('user-assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet"
        type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('user-assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('user-assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/2ea3577f09.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.min.css">
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css"> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script>
        var base_url = '{{ url('/') }}';
        var csrf_token = $('meta[name="csrf-token"]').attr('content');
    </script>

</head>

<body data-layout="horizontal" data-topbar="dark">

    <div id="layout-wrapper">

        @include('layouts.navbars.auth.nav')

        @yield('content')



        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <script>
                            document.write(new Date().getFullYear())
                        </script> &copy; Zenia.
                    </div>
                </div>
            </div>
        </footer>


    </div>
    <!-- END layout-wrapper -->
    @include('layouts.navbars.auth.sidebar')


    <!-- JAVASCRIPT -->

    <script src="{{ asset('user-assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('user-assets/libs/metismenujs/metismenujs.min.js') }}"></script>
    <script src="{{ asset('user-assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('user-assets/libs/feather-icons/feather.min.js') }}"></script>

    <!-- apexcharts -->
{{--    <script src="{{ asset('user-assets/libs/apexcharts/apexcharts.min.js') }}"></script>--}}

    <!-- Vector map-->
{{--    <script src="{{ asset('user-assets/libs/jsvectormap/js/jsvectormap.min.js') }}"></script>--}}
{{--    <script src="{{ asset('user-assets/libs/jsvectormap/maps/world-merc.js') }}"></script>--}}

    <!-- swiper js -->
    <script src="{{ asset('user-assets/libs/swiper/swiper-bundle.min.js') }}"></script>

{{--    <script src="{{ asset('user-assets/js/pages/dashboard.init.js') }}"></script>--}}

{{--    <script src="{{ asset('user-assets/libs/gridjs/gridjs.umd.js') }}"></script>--}}
{{--    <script src="{{ asset('user-assets/js/pages/gridjs.init.js') }}"></script>--}}

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js"></script>

    <script type="text/javascript" src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.4/dataTables.bootstrap5.js"></script>



    <script src="{{ asset('user-assets/js/app.js') }}"></script>

    <script>
        $(document).ready(function() {
            var base_url = '{{ url('/') }}'
            var csrf_token = $('meta[name="csrf-token"]').attr('content');
            $('#alert-btn').click(function() {
                new Swal({
                    title: "Are you sure?",
                    text: `you want to logout?`,
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    cancelButtonText: "No",
                    confirmButtonText: "Yes",
                }).then((result) => {
                    if (result.value) {
                        setTimeout(function() {
                            window.location.href = '{{ url('/logout') }}';
                        }, 100);
                    }
                });
            });

        });

        function sidebarLogout() {
            new Swal({
                title: "Are you sure?",
                text: `you want to logout?`,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: "No",
                confirmButtonText: "Yes",
            }).then((result) => {
                if (result.value) {
                    setTimeout(function() {
                        window.location.href = '{{ url('/logout') }}';
                    }, 100);
                }
            });
        }




        // jQuery code
        // $(document).ready(function() {
        //     $('#sidebar').show(); // hide the spinner initially


        //     // Exit access from impersonate login
        //     $(document).on('click', '.existAccess', function(e) {
        //         $('nav').addClass('adjust_navbar');
        //         var message = "Your current session will expire, Please click below to continue.";
        //         var id = $(this).data('logoutuserid');
        //         var endpoint = base_url + '/returnLogin/' + id;
        //         // bootbox.confirm({
        //         //     title: "Session Out",
        //         //     message: message,
        //         //     buttons: {
        //         //         confirm: {
        //         //             label: 'Continue',
        //         //             className: 'btn-primary'
        //         //         },
        //         //         cancel: {
        //         //             label: 'Close',
        //         //             className: 'btn-light'
        //         //         }
        //         //     },
        //         //     callback: function (result) {
        //         //         $('nav').removeClass('adjust_navbar');
        //         //         if (result == true) {
        //         //             var urls = endpoint;
        //         //             window.location.href = urls;
        //         //         }
        //         //     }
        //         // });

        //         Swal.fire({
        //             title: "Are you sure?",
        //             text: "You want to login to admin panel",
        //             type: "warning",
        //             showCancelButton: true,
        //             confirmButtonColor: "#3085d6",
        //             cancelButtonColor: "#d33",
        //             confirmButtonText: "Yes",
        //         }).then((result) => {
        //             if (result.value) {
        //                 var urls = endpoint;
        //                 window.location.href = urls;
        //             }
        //         });
        //     });

        // });
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            $("#right-bar-toggle").click(function() {
                $("body").toggleClass("right-bar-enabled");
            });
        });


        var base_url = '{{ url('/') }}'
        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        $(document).ready(function() {
            getDashboardData()

            function getDashboardData() {
                var csrf_token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    type: "GET",
                    url: "{{ url('/get-dashboard-data') }}",
                    // data: data,
                    headers: {

                        'X-CSRF-TOKEN': csrf_token

                    },
                    success: function(response) {
                        $('#roi_wallet_balance').text('$' + response.data.roi_wallet_balance.toFixed(2));
                        $('#fund_wallet_amount').text('$' + response.data.fund_Wallet_balance.toFixed(2));
                        $('#hscc_bonus_balance').text('$' + response.data.hscc_bonus_balance.toFixed(2));
                        $('#working_wallet').text('$' + response.data.working_wallet.toFixed(2));
                        $('#direct_income_balance').text('$' + response.data.direct_income.toFixed(2));
                        $('#total_binary_income_balance').text('$' + response.data.pre_binary_income.toFixed(2));
                        $('#binary_income_balance').text('$' + response.data.binary_income.toFixed(2));
                        $('#total_income_new').text('$' + response.data.total_income.toFixed(2));

                        if (Number(response.data.acpercentage3x) >= 80) {
                            window.$('#show_popup').modal('show');
                            send3xMailNotification(response.data.acpercentage3x);
                        } else {
                            window.$('#show_popup').modal('hide');
                            send3xMailNotification(response.data.acpercentage3x);
                        }
                    }
                });
            }
        });



        function send3xMailNotification(acpercentage3x) {
            var csrf_token = $('meta[name="csrf-token"]').attr('content');
            var mailInfo;
            if (acpercentage3x >= 80) {
                mailInfo = {
                    capping_percentage: acpercentage3x,
                    mail_status: 1,
                };
            } else {
                mailInfo = {
                    capping_percentage: acpercentage3x,
                    mail_status: 0,
                };
            }
            $.ajax({
                method: "POST",
                url: "{{ url('/send-3x-mail-notification') }}",
                data: mailInfo,
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                success: function(response) {
                    // alert(response.message);
                    console.log(response.message);
                }
            });
        }

        // google.charts.load('current', {
        //     'packages': ['corechart', 'bar']
        // });
        // google.charts.setOnLoadCallback(drawMontlyBillingChart);


        function myFunctionRefLeft() {
            var copyText = document.getElementById("referral-left");
            //
            copyText.select();
            document.execCommand("copy");

            var tooltip = document.getElementById("refcopy1");
            tooltip.innerHTML =
                "<span class='btn-icon-start text-secondary'><i class='fa fa-copy color-secondary'></i> </span>Copied !"; // + copyText.value;
        }

        function myFunctionRefRight() {
            var copyText = document.getElementById("myRightInput");
            copyText.select();
            document.execCommand("copy");

            var tooltip = document.getElementById("right-refcopy");
            tooltip.innerHTML =
                "<span class='btn-icon-start text-secondary'><i class='fa fa-copy color-secondary'></i> </span> Copied !"; // + copyText.value;
        }




        function timerDive() {
            // Get the timer div element
            const timerDiv = document.getElementById('dayT');


            // Set the target date and time for the timer
            const targetDate = new Date("{{ Auth::user()->three_x_achieve_date }}");



            // Calculate the target timestamp (48 hours in milliseconds)
            const targetTimestamp = targetDate.getTime() + (48 * 60 * 60 * 1000);

            // Update the timer every second
            setInterval(() => {
                // Get the current timestamp


                const options = {
                    timeZone: 'Europe/London'
                };
                const currentTimestampString = new Date().toLocaleString('en-US', options);
                const currentTimestamp = Date.parse(currentTimestampString);
                //const currentTimestamp = Date.now();

                // Calculate the time remaining until the target timestamp
                const timeRemaining = targetTimestamp - currentTimestamp;

                // Calculate the number of hours, minutes, and seconds remaining
                const hoursRemaining = Math.floor(timeRemaining / (1000 * 60 * 60));
                const minutesRemaining = Math.floor((timeRemaining / (1000 * 60)) % 60);
                const secondsRemaining = Math.floor((timeRemaining / 1000) % 60);

                // Format the timer text
                const timerText =
                    `${hoursRemaining} H, ${minutesRemaining} M, ${secondsRemaining} S`;

                // Update the timer div with the formatted text
                timerDiv.innerHTML = timerText;
            }, 1000);

        }


        if ({{ Auth::user()->three_x_achieve_status }} == 1) {
            //alert("test");
            timerDive();
        }
    </script>

</body>

</html>
