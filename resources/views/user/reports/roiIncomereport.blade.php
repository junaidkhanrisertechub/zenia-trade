@extends('layouts.user_type.auth-app')
@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">ROI Income Report</h4>
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
                                                        id="deposit_id" placeholder="Transaction ID">
                                                </div>
                                            </div>
                                            <div class="col-md-3 d-flex justify-content-center mt-2">
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
                                        <table v-once id="structure-balance-receive" class="display nowrap table-striped" style="width: 100%">
                                           <thead>
                                              <tr>
                                                 <th>Sr No.</th>
                                                 <th>Date</th>
                                                 <th>Income</th>
                                                 <th>Lapse Amount</th>
                                                 <th>Transaction Id</th>
                                                 <th>Investment Amount</th>
                                                 <th>ROI %</th>
                                                 <th>Status</th>
                                                 <th>Remark</th>
                                              </tr>
                                           </thead>
                                           <tbody>
                                           </tbody>
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

    <script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            var csrf_token = $('meta[name="csrf-token"]').attr('content');
            var i = 0;
            var reportsTable = $("#structure-balance-receive").DataTable({
                responsive: true,
                // lengthMenu: [
                //     [10, 20, 30, 40, 50, 100],
                // ],
                retrieve: true,
                destroy: true,
                processing: false,
                serverSide: true,
                stateSave: true,
                ordering: false,
                dom: 'lrtip',
                buttons: [

                    "pageLength",
                ],
                ajax: {
                    url: '{{ url('/reports/roi-reports') }}',
                    type: "POST",
                    data: function(d) {
                        i = 0;
                        i = d.start + 1;

                        let params = {
                            id: $("#user_id").val(),
                            frm_date: $("#frm_date").val(),
                            to_date: $("#to_date").val(),
                        };
                        Object.assign(d, params);
                        return d;
                    },
                    headers: {
                        "X-CSRF-TOKEN": csrf_token
                    },
                    dataSrc: function(json) {
                        if (json.code === 200) {
                            let arrGetHelp = json.data.records;

                            json["recordsFiltered"] = json.data.recordsFiltered;
                            json["recordsTotal"] = json.data.recordsTotal;
                            return json.data.records;
                        } else if (json.code === 401 || json.code === 403) {
                            location.reload();
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
                                return moment(String(row.entry_time)).format("YYYY-MM-DD");
                            }
                        },
                    },

                    {
                        render: function(data, type, row) {
                            return `<span>${Number(row.amount).toFixed(2)}</span>`;
                        },
                    },

                    {
                        render: function(data, type, row) {
                            return `<span>${Number(row.laps_amount).toFixed(2)}</span>`;
                        }
                    },

                    {
                        data: 'pin'
                    },

                    {
                        render: function(data, type, row) {
                            return `<span>${Number(row.on_amount).toFixed(3)}</span>`;
                        }
                    },
                    {
                        data: 'daily_percentage'
                    },
                    {
                        render: function(data, type, row) {
                            if (row.status === "Paid") {
                                return `<span class="label label-success"><b>PAID</b></span>`;
                            } else {
                                return `<span class="label label-danger">UNPAID</span>`;
                            }
                        }
                    },
                    {
                        data: 'remark'
                    },


                ],

            });

            $("#onSearchClick").click(function() {
                var startDate = $("#from-date").val();
                var endDate = $("#to-date").val();
                if (endDate < startDate) {
                    toastr.error('To date should be greater than from date');
                    return false;
                }
                reportsTable.ajax.reload();
            });
            $("#onResetClick").click(function() {
                $("#searchForm").trigger("reset");
                reportsTable.ajax.reload();
            });
            $('#deposit_id').keypress(function(e) {
                var key = e.which;
                if (key == 13) // the enter key code
                {
                    e.preventDefault();
                    $('#onSearchClick').click();
                    reportsTable.ajax.reload();
                }
            });

        });
    </script>
@endsection
