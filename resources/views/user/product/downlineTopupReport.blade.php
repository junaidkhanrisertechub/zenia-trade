@extends('layouts.user_type.auth-app')

@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">My Downline Activation Report</h4>
                            </div>
                            <!-- end card header -->
                            <div class="card-header">
                                <div class="searchFormWrap position-relative">
                                    <form id="searchForm">
                                        <div class="row align-items-center">
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
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Transaction Id</label>
                                                    <input type="text" class="form-control" name="deposit_id"
                                                        id="deposit_id" placeholder="Transaction ID" />

                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>User ID</label>
                                                    <input type="text" class="form-control" name="user_id" id="user-id"
                                                        placeholder="User ID" />
                                                </div>
                                            </div>
                                            <div class="col-md-12 d-flex justify-content-center mt-2">
                                                <div class="searchFormButwrap">
                                                    <button type="button" name="signup1" value="Sign up" id="onSearchClick"
                                                        class="btn btn-success btn-block">
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
                                        <table id="downline-topup-report" class="display nowrap table-striped"
                                            style="width: 100%">
                                            <thead>
                                                <tr>
                                                    <th>Sr No.</th>
                                                    <th>Date</th>
                                                    <th>User Id</th>
                                                    <th>Amount</th>
                                                    <th>Fund Wallet</th>
                                                    <th>ROI Wallet</th>
                                                    <th>Working Wallet</th>
                                                    <th>HSCC Bonus wallet</th>
                                                    <th>Transaction Id</th>
                                                    <!-- <th>Wallet Type</th> -->
                                                    <th>Personal Note</th>
                                                    <!-- <th>ROI %</th> -->
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
            var reportsTable = $("#downline-topup-report").DataTable({
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
                    url: '{{ url('/get-downline-topup-report') }}',
                    type: "POST",
                    data: function(d) {
                        i = 0;
                        i = d.start + 1;

                        let params = {
                            deposit_id: $("#deposit_id").val(),
                            user_id: $("#user-id").val(),
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
                        data: "entry_time"
                    },
                    {
                        render: function(data, type, row, ) {
                            return `<span>${row.user_id}</span>`;
                            //`<span>(${row.fullname})</span>`;
                        }
                    },
                    {
                        render: function(data, type, row, ) {
                            return `<span>${Number(row.amount).toFixed(2)}</span>  `;
                        }
                    },
                    {
                        render: function(data, type, row, ) {
                            return `<span>${Number(row.fund_wallet_usage).toFixed(2)}</span>  `;
                        }
                    },
                    {
                        render: function(data, type, row, ) {
                            return `<span>${Number(row.roi_wallet_usage).toFixed(2)}</span>  `;
                        }
                    },
                    {
                        render: function(data, type, row, ) {
                            return `<span>${Number(row.working_wallet_usage).toFixed(2)}</span>  `;
                        }
                    },
                    {
                        render: function(data, type, row, ) {
                            return `<span>${Number(row.hscc_wallet_usage).toFixed(2)}</span>  `;
                        }
                    },
                    {
                        data: 'pin'
                    },
                    // { data: 'topupfrom' },
                    /*{ render: function (data, type, row,) {
                             if (row.topupfrom == '1' || row.topupfrom == 1) {

                               return `<span>Admin</span>  `;
                             } else {

                               return `<span>${row.topupfrom}</span>  `;
                             }
                           }
                       }, */
                    {
                        data: "remark"
                    }
                    // { data: 'percentage' },

                    /*{
                                        render: function (data, type, row,) {
                                            if (row.entry_time === null || row.entry_time === undefined || row.entry_time === '') {
                                              return `-`;
                                            } else {
                                                return moment(String(row.entry_time)).format('YYYY-MM-DD HH:MM');
                                            }
                                        }
                                    },*/
                    /* {
                       render: function (data, type, row,) {
                         return `<span>${Number(row.amount).toFixed(3)}</span>  `;
                       },
                     },
                     { data: "pin" },
                     { data: "remark" },
                     { data: "remark" },*/
                    /* {
                                        render: function (data, type, row,) {
                                            if (row.topupfrom === '') {
                                              return `Self`;
                                            } else {
                                                return row.topupfrom;
                                            }
                                        }
                                    },*/
                    /*{
                                        render: function (data, type, row,) {
                                            return `<span>${row.user_id}</span><span>(${row.fullname})</span>`;
                                        }
                                    },*/

                    /*{ data: 'amount' },*/

                    // { render: function (data, type, row,,) {
                    //      return row.name/* + ' ( ' + row.package_type + ' ) ' */ ;

                    //  }
                    // },
                    // { data: 'franchise_user_id' },
                    /*{ data: 'name' },*/
                    /* { data: 'top_up_by' },
                                    { data: 'top_up_type' },
                                    { data: 'payment_type' },*/
                    /*{ data: 'withdraw' },*/

                    /* {
                                        render: function (data, type, row,) {
                                            if (row.entry_time === null || row.entry_time === undefined || row.entry_time === '') {
                                              return `-`;
                                            } else {
                                                return `<label class="waves-effect" id="view" data-amount="${row.amount}" data-id="${row.franchise_user_id}" data-date="${row.entry_time}"data-currency="${row.currency_code}" style="color:#7367f0">View
                                        </label>`;
                                            }
                                        }
                                    }*/
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
                $("#deposit_id").val("");
                reportsTable.ajax.reload();
            });
            $('#deposit_id,#user-id').keypress(function(e) {
                var key = e.which;
                if (key == 13) // the enter key code
                {
                    e.preventDefault();
                    $('#onSearchClick').click();
                    reportsTable.ajax.reload();
                }
            });
            // $("#topup-report tbody").on("click", "#view", function () {
            //     onViewClick(
            //         $(this).data("id"),
            //         $(this).data("amount"),
            //         $(this).data("currency"),
            //         $(this).data("date")
            //     );
            // });

            // function onViewClick(id, amount, currency, date1, franchise_id) {
            //     $.ajax({
            //         url: '/certificate',
            //         type: 'GET',
            //         data: {
            //             amount: amount,
            //             currency: currency,
            //             user_id: id,
            //             date1: date1,
            //             franchise_id: franchise_id
            //         },
            //         success: function(response) {
            //             window.location.href = '/certificate';
            //         },
            //         error: function(xhr, status, error) {
            //             console.log(error);
            //         }
            //     });
            // }

        });
    </script>
@endsection
