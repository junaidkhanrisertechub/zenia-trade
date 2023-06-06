@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Team View</h4>
                </div>
                <form id="searchForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-primary">
                                <div class="panel-body">
                                    <div class="row">

                                        <div class="col-md-4 ml-4">
                                            <div class="form-group">
                                                <label>From Date</label>
                                                <div>
                                                    <div class="input-group">
                                                        <input type="date" class="admin-form-control" name="frm_date"
                                                            placeholder="From Date" id="frm_date" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>To Date</label>
                                                <div>
                                                    <div class="input-group">
                                                        <input type="date" class="admin-form-control" name="to_date"
                                                            placeholder="To Date" id="to_date" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>User Id</label>
                                                <input class="admin-form-control" placeholder="Enter user id" type="text"
                                                    id="user_id" onkeyup="checkUserExisted(this.value)" />
                                                <p></p>
                                                <span></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 ml-4">
                                            <div class="form-group">
                                                <label>Sponsor ID</label>
                                                <input class="admin-form-control" placeholder="Enter Sponsor  ID"
                                                    type="text" id="leader_id" />
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Team</label>
                                                <select class="admin-form-control" id="position">
                                                    <option value="">Select Team</option>
                                                    <option value="">All</option>
                                                    <option value="1">Left Team</option>
                                                    <option value="2">Right Team</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label id="isAvialable"></label>
                                                <input id="fullname" class="admin-form-control d-none">
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="text-center">
                                                    <button type="button"
                                                        class="btn btn-primary waves-effect waves-light ml-4"
                                                        id="onSearchClick">
                                                        Search
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-dark waves-effect waves-light ml-4"
                                                        id="onResetClick">
                                                        Reset
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- panel-body -->
                            </div>
                            <!-- panel -->
                        </div>
                        <!-- col -->
                    </div>
                </form>

                <div class="row">
                    <div class="col-12">
                        <div class="admin-card-body">
                            <div class="table-responsive admin-table">
                                <table v-once id="team-view-report" class="display nowrap" style="width: 100%">
                                    <thead>
                                        <tr>
                                            <th>Sr.No</th>
                                            <th>User Id</th>
                                            <th>Full Name</th>
                                            <th>Sponsor ID</th>
                                            <th>Position</th>
                                            <th>Left Business</th>
                                            <th>Right Business</th>
                                            <th>Joining Date</th>
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



    <script>
        $(document).ready(function() {
            teamViewReport();
        });

        function teamViewReport() {
            let i = 1;
            var csrf_token = "{{ csrf_token() }}";

            setTimeout(function() {
                const table = $("#team-view-report").DataTable({
                    responsive: true,
                    retrieve: true,
                    destroy: true,
                    processing: false,
                    serverSide: true,
                    stateSave: false,
                    ordering: false,
                    dom: "Brtip",
                    lengthMenu: [
                        [10, 50, 100],
                        [10, 50, 100]
                    ],
                    buttons: [
                        // "excelHtml5",
                        "pageLength"
                    ],
                    ajax: {
                        url: "{{ url('admin/getteamviews') }}",
                        type: "POST",
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        data: function(d) {
                            i = 0;
                            i = d.start + 1;
                            let params = {
                                frm_date: $("#frm_date").val(),
                                to_date: $("#to_date").val(),
                                user_id: $("#user_id").val(),
                                sponsor_id: $("#leader_id").val(),
                                position: $("#position").val(),
                            };
                            Object.assign(d, params);
                            return d;
                        },
                        dataSrc: function(json) {
                            if (json.code === 200) {
                                let arrGetHelp = json.data.records;
                                json["recordsFiltered"] = json.data.recordsFiltered;
                                json["recordsTotal"] = json.data.recordsTotal;
                                return json.data.records;
                            } else if (json.code === 401 || json.code === 403) {
                                localStorage.removeItem("admin_token");
                                location.reload();
                            } else {
                                json["recordsFiltered"] = 0;
                                json["recordsTotal"] = 0;
                                return json;
                            }
                        }
                    },
                    columns: [{
                            render: function() {
                                return i++;
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.user_id + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.fullname + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.sponser_id + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.position + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.left_bv + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                return "<span>" + row.right_bv + "</span>";
                            }
                        },
                        {
                            render: function(data, type, row) {
                                if (row.joining_date === null || row.joining_date === undefined ||
                                    row
                                    .joining_date === '') {
                                    return "-";
                                } else {
                                    return row.joining_date;
                                }
                            }
                        },
                    ],
                });

                $("#onSearchClick").click(function() {
                    var startDate = $("#frm_date").val();
                    var endDate = $("#to_date").val();
                    if (endDate < startDate) {
                        toastr.error("To date should be greater than from date");
                        return false;
                        // alert("To date should not less than from date ");
                    }
                    table.ajax.reload();
                });

            });
        }

        $("#onResetClick").click(function() {
            $("#searchForm").trigger("reset");
            var startDate = $("#frm_date").val("");
            var endDate = $("#to_date").val("");
            var user_id = $("#user_id").val("");
            $('#team-view-report').DataTable().ajax.reload();
        });

        function checkUserExisted(username) {

            if (username != '') {
                var data = {
                    user_id: username
                };

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    }
                });
                $.ajax({
                    type: "POST",
                    url: '{{ url('/admin/checkuserexist') }}', // replace with the actual URL for the API endpoint
                    data: data,
                    dataType: "json",
                    success: (resp) => {

                        console.log(resp);
                        if (resp.code === 200) {
                            var fullname = $("#fullname");
                            var user_id = resp.data.id;
                            fullname.val(resp.data.fullname);
                            fullname.addClass('d-block');
                            fullname.removeClass('d-none');
                            fullname.removeClass('text-danger');
                            fullname.addClass('text-success');
                            var isAvialable = $("#isAvialable").html("User");

                            toastr.success(resp.message);
                        } else {
                            var fullname = $("#fullname");
                            var user_id = "";
                            var isAvialable = $("#isAvialable").html("User");
                            fullname.val("Not available");
                            fullname.addClass('d-block');
                            fullname.removeClass('d-none');
                            fullname.addClass('admin-form-control');
                            fullname.addClass('text-danger');
                            fullname.removeClass('text-success');
                            toastr.error(resp.message);
                        }

                    },
                    error: (err) => {
                        //   toastr.error(err)
                    }
                });

            }
        }
    </script>
@endsection
