@extends('layouts.user_type.auth-app')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">Fund Report</h4>
                            </div><!-- end card header -->
                            <div class="card-body">
                                <div id="table-gridjs">
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="card RepotPage">
                                                <div class="card-header">
                                                    <div class="searchFormWrap position-relative">
                                                        <form id="searchForm">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-3">
                                                                    <div class="form-group">
                                                                        <label>From Date</label>
                                                                        <input type="date" class="form-control" name="frm_date"
                                                                            format="dateFormat" placeholder="From Date" id="from-date" />
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-group">
                                                                        <label>To Date</label>
                                                                        <input type="date" class="form-control" name="to_date"
                                                                            format="dateFormat" placeholder="To Date" id="to-date" />
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="form-group">
                                                                        <label>Transaction Id</label>
                                                                        <input type="text" class="form-control" name="deposit_id" id="deposit_id"
                                                                            placeholder="Transaction ID"
                                                                            onkeypress="return event.charCode >= 48 && event.charCode <= 57" />
                        
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <label>Select Payment Mode</label>
                        
                                                                    <select id="payment-mode" class="form-select"
                                                                        aria-label="Default select example">
                                                                        <option selected="" value="">Select Payment Mode</option>
                                                                        @foreach ($currency as $cur)
                                                                            <option value="{{ $cur->currency_code }}">
                                                                                {{ $cur->currency_name }} ({{ $cur->currency_code }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-12 mx-auto mt-2">
                                                                    <div class="text-center searchFormButwrap">
                                                                        <button type="button" name="signup1" value="Sign up" id="onSearchClick"
                                                                            class="btn btn-success">
                                                                            Find </button>
                                                                        <button type="button" name="signup1" value="Sign up" id="onResetClick"
                                                                            class="btn btn-primary">
                                                                            Reset </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table id="fund-reports" class="display nowrap table-striped" style="width: 100%">
                                                            <thead>
                                                                <tr>
                                                                    <th>Sr No.</th>
                                                                    <th>Date</th>
                                                                    <th>Transaction Id</th>
                                                                    <th>Amount</th>
                                                                    <th>Payment Mode</th>
                                                                    <th>Address</th>
                                                                    <th>Status</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                        
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.4/dataTables.bootstrap5.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            var i = 0;

            var reportsTable = $("#fund-reports").DataTable({
                responsive: true,
                lengthMenu: [
                    [10, 50, 100],
                    [10, 50, 100],
                ],
                retrieve: true,
                destroy: false,
                processing: false,
                serverSide: true,
                stateSave: false,
                ordering: false,
                dom: "lrtip",
                "language": {
                    "emptyTable": "No Data Detected in Zenia Database"
                },

                ajax: {
                    url: '{{ url('/reportfund') }}',
                    type: "POST",
                    data: function(d) {
                        i = 0;
                        i = d.start + 1;

                        let params = {
                            deposit_id: $("#deposit_id").val(),
                            frm_date: $("#from-date").val(),
                            to_date: $("#to-date").val(),
                            payment_mode: $("#payment-mode").val(),
                        };
                        Object.assign(d, params);
                        return d;
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    },
                    dataSrc: function(json) {
                        if (json.code === 200) {
                            let total_amount = 0;
                            for (let j = 0; j < json.data.records.length; j++) {
                                total_amount = Number(
                                    total_amount + json.data.records[j].amount
                                ).toFixed(3);
                                $("#total_amount").text(total_amount);
                            }

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
                            if (
                                row.entry_time === null ||
                                row.entry_time === undefined ||
                                row.entry_time === ""
                            ) {
                                return `-`;
                            } else {
                                return moment(String(row.entry_time)).format("YYYY/MM/DD");
                            }
                        },
                    },
                    // {
                    //     data: "invoice_id"
                    // },
                    {
                        render: function(data, type, row) {
                            return row.invoice_id;
                        },
                    },
                    {
                        render: function(data, type, row) {

                            return `<span>$${Number(row.price_in_usd).toFixed(3)}</span>`;

                        },
                    },
                    {
                        render: function(data, type, row) {
                            return "<span class='fw-bold'>" + row.payment_mode +
                                "</span>";
                        },
                    },
                    {
                        render: function(data, type, row) {
                            if (row.payment_mode && row.payment_mode.toLowerCase() == 'btc') {
                                return (
                                    "<span style='word-break: break-word;'>" +
                                    `<a href="https://www.blockchain.com/${(row.payment_mode).toLowerCase()}/address/${row.address}" target="_blank">${row.address}</a>` +
                                    "</span>"
                                );
                            } else if (row.payment_mode && row.payment_mode.toLowerCase() ==
                                'eth' || row.payment_mode && row.payment_mode.toLowerCase() ==
                                'usdt.erc20') {
                                return (
                                    "<span style='word-break: break-word;'>" +
                                    `<a href="https://etherscan.io/address/${row.address}" target="_blank">${row.address}</a>` +
                                    "</span>"
                                );
                            } else if (row.payment_mode && row.payment_mode.toLowerCase() ==
                                'trx' || row.payment_mode && row.payment_mode.toLowerCase() ==
                                'usdt.trc20') {
                                return (
                                    "<span style='word-break: break-word;'>" +
                                    `<a href="https://tronscan.org/#/address/${row.address}" target="_blank">${row.address}</a>` +
                                    "</span>"
                                );
                            } else if (row.payment_mode && row.payment_mode.toLowerCase() ==
                                'doge') {
                                return (
                                    "<span style='word-break: break-word;'>" +
                                    `<a href="https://dogechain.info/address/${row.address}" target="_blank">${row.address}</a>` +
                                    "</span>"
                                );
                            } else if (row.payment_mode && row.payment_mode.toLowerCase() ==
                                'ltc') {
                                return (
                                    "<span style='word-break: break-word;'>" +
                                    `<a href="https://live.blockcypher.com/${(row.payment_mode).toLowerCase()}/address/${row.address}" target="_blank">${row.address}</a>` +
                                    "</span>"
                                );
                            } else if (row.payment_mode && row.payment_mode.toLowerCase() ==
                                'sol') {
                                return (
                                    "<span style='word-break: break-word;'>" +
                                    `<a href="https://solscan.io/account/${row.address}" target="_blank">${row.address}</a>` +
                                    "</span>"
                                );
                            }


                        },
                    },

                    {
                        render: function(data, type, row) {
                            if (row.in_status == 0) {
                                return `<label class="text-warning fw-bold">Pending<label>`;
                            } else if (row.in_status == 2) {
                                return `<label class="text-danger" fw-bold>Expired<label>`;
                            } else if (row.in_status == 1) {
                                return `<label class="text-success fw-bold">Confirmed<label>`;
                            }
                        },
                    },

                    {
                        render: function(data, type, row) {
                            return "<a href='" + row.status_url + "' target='_blank' class='btn bg-gradient-primary'>Checkout</a>";
                        },
                    },
                ],
            });


            $("#onSearchClick").click(function() {
                var startDate = $("#from-date").val();
                var endDate = $("#to-date").val();
                if (endDate < startDate) {
                    toastr.error('To date should be greater than from date')
                    return false;
                }
                reportsTable.ajax.reload();
            });
            $("#onResetClick").click(function() {
                $("#searchForm").trigger("reset");
                $("#deposit_id").val("");
                reportsTable.ajax.reload();
            });
            $('#deposit_id').keypress(function(e) {
                var key = e.which;
                if (key == 13) // the enter key code
                {
                    $('#onSearchClick').click();
                    reportsTable.ajax.reload();
                }
            });
        });
    </script>
@endsection
