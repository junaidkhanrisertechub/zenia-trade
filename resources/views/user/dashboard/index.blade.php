@extends('layouts.user_type.auth-app')

@section('content')

    <div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb ms-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="font-weight-bolder mb-0">HSCC Panel</h6>
                        </div>
                        <div class="col-md-6">
                            <h6 class="float-right upp">
                                Topup Amount : $5000 | Status :
                                <!-- <span class="btn btn-danger btn-xs">Inactive</span> -->
                                <span class="btn btn-success btn-xs">Active</span>
                            </h6>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <div class="row row-cols-md-4 row-cols-1 mt-3 justify-content-center topcarddash">
            <div class="col">
                <a class="card" href="#">
                    <div class="card-body">
                        <div  data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                The Daily ROI appears here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold"> ROI <br> Wallet</p>
                                    <h5 class="font-weight-bolder mb-0">  $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-wallet text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card" href="#">
                    <div class="card-body">
                        <div data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                Your fund wallet appears here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold"> Fund <br> Wallet</p>
                                    <h5 class="font-weight-bolder mb-0"> $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-wallet text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card" href="#">
                    <div class="card-body">
                        <div data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                Your HSCC BONUS appears here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold"> HSCC BONUS Wallet</p>
                                    <h5 class="font-weight-bolder mb-0"> $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-wallet text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card" href="#">
                    <div class="card-body">
                        <div data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                Total of Direct Income & Daily Binary Income is shown here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold"> Working <br>Wallet  </p>
                                    <h5 class="font-weight-bolder mb-0"> $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-wallet text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card" href="affiliates-income-report">
                    <div class="card-body">
                        <div data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                Your Direct income is shown here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold"> Direct <br>Income </p>
                                    <h5 class="font-weight-bolder mb-0"> $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-circle-dollar-to-slot text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card" href="binary-income-report">
                    <div class="card-body">
                        <div data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                Your Total Binary income is shown here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Binary Income </p>
                                    <h5 class="font-weight-bolder mb-0">  $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-chart-line text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card" href="daily-bonus-report">
                    <div class="card-body">
                        <div data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                Your Daily Binary income is shown here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">Daily Binary Income </p>
                                    <h5 class="font-weight-bolder mb-0"> $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-chart-line text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a class="card" href="#">
                    <div class="card-body">
                        <div data-toggle="tooltip" data-placement="bottom" id="myTooltip1" class="EyeTooltip">
                            <i class="fa-solid fa-eye"></i>
                            <div class="EyeTree">
                                Total of ROI, Direct Income, Daily Binary Income & HSCC BONUS Income is shown here
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold"> Total <br> Earning </p>
                                    <h5 class="font-weight-bolder mb-0"> $0.00 </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                                    <i class="fa-solid fa-sack-dollar text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="row text-center mb-3 ">
            <div class="col-md-4">
                <button type="button" class="w-100 mb-4 btn bg-gradient-primary" data-bs-toggle="modal" data-bs-target="#myModal">
                    Live Trading Report
                </button>
            </div>
            <div class="col-md-4">
                <a href="javascript:void(0)" class=" w-100 mb-4 btn bg-gradient-primary">Package</a>
            </div>
            <div class="col-md-4">
                <a href="javascript:void(0)" class=" w-100 mb-4 btn bg-gradient-primary">Make Deposit</a>
            </div>
        </div>
        <div class="row dashsecund-row2">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card-header p-0">
                                    <h6>Revenue report</h6>
                                </div>
                                <div>
                                    Add Graph
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-gradient-primary border-radius-lg h-100 p-3">
                                    <form class="row">
                                        <div class="col-md-12 text-center">
                                        </div>
                                        <div class="col-md-12 mt-3">
                                            <div class="numbers text-center">
                                                <p class="text-sm mb-0 text-capitalize font-weight-bold"> Total Withdrawable Amount </p>
                                                <h5 class="font-weight-bolder mb-0 text-light"> $0.00 </h5>
                                                <img src="images/rocket-white.png" class="img-fluid mt-3 rocket-icons">
                                                <span><img src="images/rocks-fire.png" class="img-fluid mt-3 rock-fire"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-12 text-center">
                                            <a href="javascript:void(0)" class="btn btn-light" style="font-size: 12px;padding: 9px 3px;">Withdraw fund</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card min-height-206">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="card-header p-0">
                                    <h6>Investment Overview</h6>
                                </div>
                                <ul class="Affiliates mt-4">
                                    <li>
                                        <span class="text-sm mb-0 text-capitalize font-weight-bold"> Total Investment </span>
                                        <h5 class="font-weight-bolder mb-0">$0.00</h5>
                                    </li>
                                    <li>
                                        <span class="text-sm mb-0 text-capitalize font-weight-bold"> ROI Received </span>
                                        <h5 class="font-weight-bolder mb-0">$0.00</h5>
                                    </li>
                                    <li>
                                        <span class="text-sm mb-0 text-capitalize font-weight-bold"> ROI Remaining </span>
                                        <h5 class="font-weight-bolder mb-0">$0.00</h5>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-5">
                                <img src="images/InvestmentOverview.png" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row dashsecund-row3">
            <div class="col-md-6">
                <div class="card min-height-140 capping-wraper">
                    <div class="card-header">
                        <h6>10X Capping</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress">
                            <div class="progress-bar" style="background:#e30000;" >
                            </div>
                        </div>
                        <div class="capping-vals">
                            <div>
                                <span>Status :</span>
                                0.00%
                            </div>
                            <div>
                                <span>Amount :</span>
                                $0.00
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3">
                    <div class="BgReferral">
                        <div class="card-body position-relative">
                            <div class="row">
                                <div class="col">
                                    <div class="cardBlue">
                                        <div class="card-body pb-0">
                                            <div class="row">
                                                <div class="col-8">
                                                    <div class="numbers">
                                                        <h4 class="text-light">Marketing Tools</h4>
                                                        <a href="javascript:void(0)" class="btn btn-light w-100">Open</a>
                                                    </div>
                                                </div>
                                                <div class="col-4 text-end lightbulb-wraper">
                                                    <i class="fa-regular fa-lightbulb"></i>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12 text-center mt-4">
                                                    <button type="button" class="btn bg-gradient-primary w-100" data-bs-toggle="modal"
                                                            data-bs-target="#Referrallink"> Referral Links </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card min-height-400">
                    <div class="card-header pb-0">
                        <!--<h6>Residual Achievements</h6>-->
                        <h6 class="text-center">HSCC BONUS</h6>
                    </div>
                    <div class="card-body pt-0 pb-0 bonus-table">
                        <div class="table-responsive">
                            <table class="table display nowrap table-striped" id="hsccbonusreport" style="width: 100%">
                                <thead>
                                <tr>
                                    <th>NUMBER OF DIRECTS</th>
                                    <th>PERCENTAGE</th>
                                    <th>STATUS</th>
                                    <th>AMOUNT RECEIVED</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>10 DIRECTS</td>
                                    <td>5%</td>
                                    <td>-</td>
                                    <td>0.00</td>
                                </tr>
                                <tr>
                                    <td>20 DIRECTS</td>
                                    <td>7%</td>
                                    <td>-</td>
                                    <td>0.00</td>
                                </tr>
                                <tr>
                                    <td>50 DIRECTS</td>
                                    <td>10%</td>
                                    <td>-</td>
                                    <td>0.00</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="Referrallink" tabindex="-1" aria-labelledby="ReferrallinkLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ReferrallinkLabel"> Referral Links </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-4">
                                <img src="images/link_img.png" class="img-fluid">
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12 ReferralLink mt-3 mb-3">
                                        <label>Left Referral Link</label>
                                        <div class="input-group">
                        <span class="input-group-text font-size-14 bg-gradient-primary" id="refcopy1"
                              @click="myFunctionRefLeft()">
                          <i class="fa-regular fa-copy"></i> Copy
                        </span>
                                            <input type="text" class="form-control" readonly id="referral-left"
                                                   :value="referrallink + '&position=1'">
                                        </div>
                                    </div>
                                    <div class="col-md-12 ReferralLink mt-3 mb-3">
                                        <label>Right Referral Link</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" readonly id="myRightInput"
                                                   :value="referrallink + '&position=2'">
                                            <span class="input-group-text font-size-14 bg-gradient-primary" id="right-refcopy"
                                                  @click="myFunctionRefRight()">
                          <i class="fa-regular fa-copy"></i> Copy
                        </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

