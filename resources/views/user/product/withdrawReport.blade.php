@extends('layouts.user_type.auth-app')

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">Withdraw Report</h4>
                            </div>
                            <!-- end card header -->
                            <div class="card-header">
                                <div class="searchFormWrap position-relative">
                                    <form id="searchForm">
                                        <div class="row align-items-center align-items-center justify-content-center">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>From Date</label>
                                                    <input type="date" class="form-control" name="frm_date"
                                                        format="dateFormat" placeholder="From Date" id="from-date">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>To Date</label>
                                                    <input type="date" class="form-control" name="to_date"
                                                        format="dateFormat" placeholder="To Date" id="to-date">
                                                </div>
                                            </div>
                                            <div class="col-md-3 d-flex justify-content-center mt-2">
                                                <div class="searchFormButwrap">
                                                    <button type="button" name="signup1" value="Sign up" id="onSearchClick"
                                                        class="btn btn-primary btn-block">
                                                        Find </button>
                                                    <button type="button" name="signup1" value="Sign up" id="onResetClick"
                                                        class="btn btn-primary btn-block">
                                                        Reset </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="table-gridjs">
                                    <div class="table-responsive">
                                        <table id="withdrawals-report" class="display nowrap table-striped"
                                            style="width: 100%">
                                            <thead>
                                                <tr>
                                                    <th>Sr No</th>
                                                    <th>Amount</th>
                                                    <th>Deduction</th>
                                                    <th>Net Amount</th>
                                                    <th>Address</th>
                                                    <th>Status</th>
                                                    <th>Currency Type</th>
                                                    <th>Withdrawal Type</th>
                                                    <!-- <th>IP Address</th> -->
                                                    <th>Date</th>
                                                    <th>Remark</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                    <!-- end col -->
                </div>
            </div>
        </div>
    </div>

    <!--/ Kick start -->

    <script type="text/javascript">
        $(document).ready(function() {
            let i = 0;
            var reportsTable = $("#withdrawals-report").DataTable({
                responsive: true,
                lengthMenu: [
                    [10, 50, 100],
                    [10, 50, 100],
                ],
                retrieve: true,
                destroy: true,
                processing: false,
                serverSide: true,
                stateSave: false,
                ordering: false,
                dom: "lrtip",
                "language": {
                    "emptyTable": "No Data Detected in Zenia Database"
                },
                ajax: {
                    url: '{{ url('withdrwal-income') }}',
                    type: "POST",
                    data: function(d) {
                        i = 0;
                        i = d.start + 1;

                        let params = {
                            frm_date: $("#from-date").val(),
                            to_date: $("#to-date").val(),
                        };
                        Object.assign(d, params);
                        return d;
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    },
                    dataSrc: function(json) {
                        if (json.code === 200) {
                            let arrGetHelp = json.data.records;
                            json["recordsFiltered"] = json.data.recordsFiltered;
                            json["recordsTotal"] = json.data.recordsTotal;
                            return json.data.records;
                        } else if (json.code === 401 || json.code === 403) {
                            location.href = '{{ url('/login') }}';
                        } else {
                            json["recordsFiltered"] = 0;
                            json["recordsTotal"] = 0;
                            return json;
                        }
                    },
                },
                columns: [{
                        render: function() {
                            return i++;
                        },
                    },
                    {
                        render: function(data, type, row) {
                            return `<span>${(row.amount+row.deduction).toFixed(2)}</span>`;
                        },
                    },
                    {
                        render: function(data, type, row) {
                            return `<span>${(row.deduction).toFixed(2)}</span>`;
                        },
                    },
                    {
                        render: function(data, type, row) {
                            return `<span>${(row.amount).toFixed(2)}</span>`;
                        },
                    },
                    {
                        data: 'to_address'
                    },

                    /*{
                        render: function (data, type, row) {
                                   return `<a href="https://www.blockchain.com/btc/address/${row.to_address}">${row.to_address}</a>`;

                        }
                      },*/
                    {
                        render: function(data, type, row) {
                            if (row.status == 0) {
                                return `<span class="label label-warning"><b>PENDING</b></span>`;
                            } else if (row.status === 1) {
                                return `<span class="label label-success"><b>CONFIRMED</b></span>`;
                            } else {
                                return `<span class="label label-danger">REJECTED</span>`;
                            }
                            /*if (row.status === "Paid") {
                              return `<span class="label label-success"><b>PAID</b></span>`;
                            } else {
                              return `<span class="label label-danger">UNPAID</span>`;
                            }*/
                        }
                    },
                    {
                        data: "network_type"
                    },

                    /*{
                      render: function(data, type, row) {
                        if (row.network_type == 'BTC') {
                          return `Bitcoin(BTC)`;
                        } else if (row.network_type == 'TRX') {
                          return `TRON`;
                        } else if (row.network_type === 'ETH') {
                          return `Ethereum(ETH)`;
                        } else if (row.network_type === 'BNB.ERC20') {
                          return `Binance`;
                        }else {
                          return ``;
                        }
                      }
                    },*/
                    {
                        render: function(data, type, row) {
                            if (row.withdraw_type == 2) {
                                return `Working`;
                            } else if (row.withdraw_type == 3) {
                                return `Roi`;
                            } else if (row.withdraw_type === 6) {
                                return `Transfer`;
                            } else if (row.withdraw_type === 7) {
                                return `HSCC Bonus`;
                            } else {
                                return ``;
                            }
                        }
                    },
                    /*
                        {
                         render: function (data, type, row) {
                           return `<span>${row.ip_address}</span>`;
                         }
                       },*/




                    {
                        render: function(data, type, row) {
                            if (
                                row.entry_time === null ||
                                row.entry_time === undefined ||
                                row.entry_time === ""
                            ) {
                                return `-`;
                            } else {
                                return moment(String(row.entry_time)).format("YYYY-MM-DD");
                            }
                        },
                    },

                    {
                        render: function(data, type, row) {
                            if (
                                row.remark === null ||
                                row.remark === undefined ||
                                row.remark === ""
                            ) {
                                return `-`;
                            } else {
                                return `<span>${row.remark}</span>`;
                            }
                        },
                    },



                ],


            });

            $("#onSearchClick").click(function() {
                var startDate = $("#from-date").val();
                var endDate = $("#to-date").val();
                if (endDate < startDate) {
                    toastr.error("To date should be greater than from date");
                    return false;
                }
                reportsTable.ajax.reload();
            });
            $("#onResetClick").click(function() {
                $("#searchForm").trigger("reset");
                reportsTable.ajax.reload();
            });
        });
    </script>
@endsection
