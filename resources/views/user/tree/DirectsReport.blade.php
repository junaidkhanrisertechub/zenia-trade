@extends('layouts.user_type.auth-app')
@section('content')
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header justify-content-between d-flex align-items-center">
                                <h4 class="card-title">Directs Report</h4>
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
                                                    <label>User ID</label>
                                                    <input type="text" class="form-control" name="user_id" id="user-id"
                                                        placeholder="User ID" />
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label for="status">Select Position</label>
                                                <select id="position" class="form-control">
                                                    <option value="">All Position</option>
                                                    <option value="1">Left</option>
                                                    <option value="2">Right</option>
                                                </select>
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
                                        <table id="structure-balance-receive" class="display nowrap table-striped"
                                        style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th>Sr No.</th>
                                                <th>Date</th>
                                                <th>User Id</th>
                                                <th>Name</th>
                                                <th>Contact No</th>
                                                <th>Email Id</th>
                                                <th>Investment</th>
                                                <th>Position</th>
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
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>


<script type="text/javascript">
    var csrf_token = "{{ csrf_token() }}";

    $(document).ready(function() {
        var reportsTable = $("#structure-balance-receive").DataTable({
            lengthMenu: [
                [10, 50, 100],
                [10, 50, 100],
            ],
            retrieve: true,
            destroy: true,
            processing: false,
            serverSide: true,
            responsive: true,
            stateSave: false,
            ordering: false,
            dom: "lrtip",
            "language": {
                "emptyTable": "No Data Detected in Zenia Database"
            },
            ajax: {
                url: "{{ url('/directsreport-data') }}",
                type: "POST",
                data: function(d) {
                    i = 0;
                    i = d.start + 1;

                    let params = {
                        user_id: $("#user-id").val(),
                        frm_date: $("#from-date").val(),
                        to_date: $("#to-date").val(),
                        position: $("#position").val()
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
                            total_amount = total_amount + parseInt(json.data.records[j]
                                .price_in_usd);
                            $("#total_amount").text("$" + total_amount);
                        }
                        var arrGetHelp = json.data.records;
                        json["recordsFiltered"] = json.data.recordsFiltered;
                        json["recordsTotal"] = json.data.recordsTotal;
                        return json.data.records;
                    } else if (json.code === 401 || json.code === 403) {
                        location.href = '/login';
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
                            return row.entry_time;
                        }
                    },
                },
                {
                    data: "user_id"
                },
                {
                    data: "fullname"
                },
                {
                    data: "mobile"
                },
                {
                    data: "email"
                },
                {
                    render: function(data, type, row) {
                        return `<span>${Number(row.amount).toFixed(3)}</span>`;
                    },
                },
                {
                    data: "position"
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
        $('#user-id').keypress(function(e) {
            var key = e.which;
            if (key == 13) // the enter key code
            {
                e.preventDefault();
                $('#onSearchClick').click();
                reportsTable.ajax.reload();
            }
        });
        $("#onResetClick").click(function() {
            $("#searchForm").trigger("reset");
            reportsTable.ajax.reload();
        });
    });
</script>
