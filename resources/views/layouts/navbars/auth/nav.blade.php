@if (Session::has('toastr'))
    {!! Session::get('toastr') !!}
@endif

<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <div class="navbar-brand-box">
                <a href="{{ url('/dashboard') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('user-assets/images/logo-sm.svg') }}" alt="" height="30">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 d-lg-none header-item" data-bs-toggle="collapse"
                id="horimenu-btn" data-bs-target="#topnav-menu-content">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <div class="topnav">
                <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

                    <div class="collapse navbar-collapse" id="topnav-menu-content">
                        <ul class="navbar-nav">
                            {{-- <li class="nav-item">
                                <a class="nav-link dropdown-toggle arrow-none" href="{{ url('/dashboard') }}"
                                    id="topnav-dashboard" role="button" data-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    <span data-key="t-dashboard">Dashboard</span>
                                </a>
                            </li> --}}
                            <!-- <li class="nav-item">
                                <a class="nav-link dropdown-toggle arrow-none" href="profile.php" id="topnav-profile" role="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span data-key="t-profile">Profile</span>
                                </a>
                            </li> -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-fund-wallet"
                                    role="button">

                                    <span data-key="t-fund-wallet">Income</span>
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-fund-wallet">
                                    <a href="{{ url('/reports/binary-income') }}" class="dropdown-item" data-key="t-addfund">
                                        Binary Income Report
                                    </a>
                                    <a href="{{ url('/reports/daily-bonus-income') }}" class="dropdown-item" data-key="t-addfund">
                                        Daily Bonus Report
                                    </a>
                                    <a href="{{ url('/reports/direct-income') }}" class="dropdown-item" data-key="t-addfund">
                                        Direct Income Report
                                    </a>
                                    <a href="{{ url('/reports/hscc-bonus-reports-list') }}" class="dropdown-item" data-key="t-addfund">
                                        Zenia Income Report
                                    </a>
                                    <a href="{{ url('/reports/roi-reports-list') }}" class="dropdown-item" data-key="t-addfund">
                                        ROI Income Report
                                    </a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-fund-wallet"
                                    role="button">

                                    <span data-key="t-fund-wallet">Fund Wallet</span>
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-fund-wallet">
                                    <a href="{{ url('/addfund') }}" class="dropdown-item" data-key="t-addfund">Add
                                        Fund</a>
                                    <a href="{{ url('/fundreport') }}" class="dropdown-item"
                                        data-key="t-fundreport">Fund report</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-ac-activation"
                                    role="button">

                                    <span data-key="t-ac-activation">Account Activation</span>
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-ac-activation">
                                    <a href="{{ url('/topup') }}" class="dropdown-item"
                                        data-key="t-activation">Activation</a>
                                    <a href="{{ url('/self-topup-report') }}" class="dropdown-item"
                                        data-key="t-activation-report">Activation report</a>
                                    <a href="{{ url('/downline-topup-report') }}" class="dropdown-item"
                                        data-key="t-downline-a">My Downline Activation </a>
                                    <a href="{{ url('/downline-deposit-report') }}" class="dropdown-item"
                                        data-key="t-downline-d">Downline Deposit Report </a>
                                    <a href="{{ url('/downline-purchase-report') }}" class="dropdown-item"
                                        data-key="t-downline-p">Downline Purchase Report </a>
                                    <a href="{{ url('/get-team-purchase-report') }}" class="dropdown-item"
                                        data-key="t-team-p">Team Purchase Report </a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-Affiliate"
                                    role="button">

                                    <span data-key="t-Affiliate ">Affiliate</span>
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-Affiliate">
                                    <a href="{{ url('/getlevelviewtree') }}" class="dropdown-item"
                                        data-key="t-binary-tree"> Binary Tree </a>
                                    <a href="{{ url('/directsreport') }}" class="dropdown-item" data-key="t-directs">
                                        Directs</a>
                                    <a href="{{ url('/teamview') }}" class="dropdown-item" data-key="t-teamview"> Team
                                        View </a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-withdrawal"
                                    role="button">

                                    <span data-key="t-Withdrawal-Summary ">Withdrawal Summary </span>
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-withdrawal">
                                    <a href="{{ url('/withdrawal') }}" class="dropdown-item"
                                        data-key="t-place-Withdrawal">Place Withdrawal</a>
                                    <a href="{{ url('/withdrawal-report') }}" class="dropdown-item"
                                        data-key="t-Withdrawal-report">Withdrawal report</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle arrow-none" href="#" id="topnav-Transfer"
                                    role="button">

                                    <span data-key="t-Transfer-fund ">Transfer funds </span>
                                    <div class="arrow-down"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-Transfer">
                                    <a href="{{ url('/transferfromfundwallet') }}" class="dropdown-item"
                                        data-key="t-transfer-fund-wallet"> Transfer from Fund Wallet </a>
                                    <a href="{{ url('/transferfromworkingwallet') }}" class="dropdown-item"
                                        data-key="t-transfer-working-w"> Transfer from Working Wallet </a>
                                    <a href="{{ url('/transferfromroiwallet') }}" class="dropdown-item"
                                        data-key="t-Transfer-wallet"> Transfer from ROI Wallet </a>
                                    <a href="{{ url('/transferfromhsccwallet') }}" class="dropdown-item"
                                        data-key="t-transfer-bonus"> Transfer BONUS Wallet </a>
                                    <a href="{{ url('/reports/transfer-report') }}" class="dropdown-item"
                                        data-key="t-transfer-report"> Transfer Report </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

        </div>

        <div class="d-flex">


            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item noti-icon right-bar-toggle" id="right-bar-toggle">
                    <i class="fa-solid fa-link"></i>
                </button>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item user text-start d-flex align-items-center"
                    id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    <img class="rounded-circle header-profile-user"
                        src="{{ asset('user-assets/images/avatar-3.jpg') }}" alt="Header Avatar">
                    <span class="ms-2 d-none d-xl-inline-block user-item-desc">
                        <span class="user-name">{{ Auth::user()->fullname }} <i
                                class="mdi mdi-chevron-down"></i></span>
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end pt-0">

                    <h6 class="dropdown-header">Welcome {{ Auth::user()->fullname }}</h6>

                    <a class="dropdown-item" href="{{ url('/profile') }}"><i
                            class="mdi mdi-account-circle text-muted font-size-16 align-middle me-1"></i> <span
                            class="align-middle">Profile</span></a>

                    <a class="dropdown-item" href="{{ url('/google2fa') }}"><i
                            class="mdi mdi-message-text-outline text-muted font-size-16 align-middle me-1"></i> <span
                            class="align-middle"> Security </span></a>

                    <a class="dropdown-item" href="javascript:;" onclick="sidebarLogout()" id="alert-btn"><i
                            class="mdi mdi-logout text-muted font-size-16 align-middle me-1"></i> <span
                            class="align-middle">Logout</span></a>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse show dash-content" id="dashtoggle">
        <div class="container-fluid">
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Welcome !</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item">
                                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active">Welcome {{ Auth::user()->fullname }}</li>
                                {{-- <li class="breadcrumb-item">
                                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                                </li> --}}
                                {{-- <li class="breadcrumb-item">
                                    <a href="{{ url('/dashboard') }}">Binary Income</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ url('/dashboard') }}">Daily Bonus</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ url('/dashboard') }}">Direct Income</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ url('/dashboard') }}">Income Report</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ url('/dashboard') }}">ROI Income</a>
                                </li> --}}
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <!-- start dash info -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card dash-header-box shadow-none border-0">
                        <div class="card-body p-0">
                            <div class="row row-cols-xxl-6 row-cols-md-3 row-cols-1 g-0">
                                <div class="col">
                                    <div class="mt-md-0 py-3 px-4 mx-2">
                                        <p class="text-white-50 mb-2 text-truncate"> ROI Income </p>
                                        <h3 class="text-white mb-0" id="roi_wallet_balance"></h3>
                                    </div>
                                </div><!-- end col -->

                                <div class="col">
                                    <div class="mt-3 mt-md-0 py-3 px-4 mx-2">
                                        <p class="text-white-50 mb-2 text-truncate"> Direct Income </p>
                                        <h3 class="text-white mb-0" id="direct_income_balance"></h3>
                                    </div>
                                </div><!-- end col -->

                                <div class="col">
                                    <div class="mt-3 mt-md-0 py-3 px-4 mx-2">
                                        <p class="text-white-50 mb-2 text-truncate"> Binary Income </p>
                                        <h3 class="text-white mb-0" id="binary_income_balance"></h3>
                                    </div>
                                </div><!-- end col -->

                                <div class="col">
                                    <div class="mt-3 mt-md-0 py-3 px-4 mx-2">
                                        <p class="text-white-50 mb-2 text-truncate"> Daily Binary Income </p>
                                        <h3 class="text-white mb-0" id="working_wallet"></h3>
                                    </div>
                                </div><!-- end col -->

                                <div class="col">
                                    <div class="mt-3 mt-lg-0 py-3 px-4 mx-2">
                                        <p class="text-white-50 mb-2 text-truncate">Daily Average Income</p>
                                        <h3 class="text-white mb-0" id="total_income_new"></h3>
                                    </div>
                                </div><!-- end col -->

                                <div class="col">
                                    <div class="mt-3 mt-lg-0 py-3 px-4 mx-2">
                                        <p class="text-white-50 mb-2 text-truncate">Total Binary Income </p>
                                        <h3 class="text-white mb-0" id="total_binary_income_balance"></h3>
                                    </div>
                                </div><!-- end col -->

                            </div><!-- end row -->
                        </div><!-- end card body -->
                    </div><!-- end card -->
                </div><!-- end col -->
            </div>
            <!-- end dash info -->
        </div>
    </div>

    <!-- start dash troggle-icon -->
    <div>
        <a class="dash-troggle-icon" id="dash-troggle-icon" data-bs-toggle="collapse" href="#dashtoggle"
            aria-expanded="true" aria-controls="dashtoggle">
            <i class="fa-solid fa-arrow-up"></i>
        </a>
    </div>
    <!-- end dash troggle-icon -->

</header>

<div class="hori-overlay"></div>
