@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Roi Income Report</h4>
                </div>
                <form id="searchForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-primary">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-3 ml-5">
                                            <div class="form-group">
                                                <label>From Date</label>
                                                <div>
                                                    <div class="input-group">
                                                        <input
                                                            type="date"
                                                            class="admin-form-control"
                                                            name="frm_date"
                                                            format="dateFormat"
                                                            placeholder="From Date"
                                                            id="frm_date"
                                                        />
                                                        <!-- <input type="text" class="admin-form-control" placeholder="From Date" id="datepicker"> -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>To Date</label>
                                                <div>
                                                    <div class="input-group">
                                                        <input
                                                            type="date"
                                                            class="admin-form-control"
                                                            name="to_date"
                                                            format="dateFormat"
                                                            placeholder="To Date"
                                                            id="to_date"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>User Id</label>
                                                <input
                                                    class="admin-form-control"
                                                    placeholder="Enter user id"
                                                    type="text"
                                                    id="user_id"
                                                                                                    />
                                               </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="text-center">
                                                    <button
                                                        type="button"
                                                        class="
                                  btn btn-primary
                                  waves-effect waves-light
                                  ml-4
                                "
                                                        id="onSearchClick"
                                                    >
                                                        Search
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="
                                  btn btn-info
                                  waves-effect waves-light
                                  ml-4
                                "
                                                        {{--onclick="exportToExcel()"--}}
                                                        id="exportToExcel"
                                                    >
                                                        Export To Excel
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="
                                  btn btn-dark
                                  waves-effect waves-light
                                  ml-4
                                "
                                                        id="onResetClick"
                                                    >
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
                <div class="admin-card-body">
                    <div class="table-responsive admin-table">
                        <table
                            v-once
                            id="binary-income-report"
                            class="display nowrap"
                            style="width: 100%"
                        >
                            <thead>
                            <tr>
                                <th>Sr.No</th>
                                <th>Date</th>
                                <th>User Id</th>
                                <th>Top Up Id</th>
                                <th>Amount</th>
                                <th>On Amount</th>
                                <th>Percentage</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>
<script>

    $(document).ready(function () {
        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        var i = 0;
        var reportsTable = $("#binary-income-report").DataTable({
            responsive: true,
            lengthMenu: [
                [10, 20, 30, 40, 50, 100],
                [10, 20, 30, 40, 50, 100],
            ],
            retrieve: true,
            destroy: true,
            processing: false,
            serverSide: true,
            stateSave: true,
            ordering: false,
            dom: "Brtip",

            buttons: [
                // "excelHtml5",
                "pageLength",
            ],
            ajax: {
                url: '{{url('admin/e-wallet/roi-income-data')}}',
                type: "POST",
                data: function (d) {
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
                dataSrc: function (json) {
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
            columns: [
                {
                    render: function () {
                        //return meta.row + 1;
                        return i++;
                    },
                },
                {
                    data: "entry_time",
                    render: function (data) {
                        if (data === null || data === undefined || data === "") {
                            return `-`;
                        } else {
                            return moment(String(data)).format("YYYY-MM-DD");
                        }
                    },
                },
                {
                    data: { user_id: "user_id", fullname: "fullname" },
                    render: function (data) {
                        return `<span>${data.user_id}</span><span>(${data.fullname})</span>`;
                    },
                },
                { data: "pin" },
                { data: "amount" },


                { data: "on_amount" },
                { data: "daily_percentage" },

                {
                    data: "status",
                    render: function (data) {
                        return '<label class="text-success">' + data + "</label>";
                    },
                },

                // {
                //     render: function (row) {
                //         return (row.entry_time === null || row.entry_time === undefined ||  row.entry_time === '')? '-' : moment(String(row.entry_time)).format('YYYY-MM-DD');

                //     }
                // }
            ],


        });

        $("#onSearchClick").click(function () {
            var startDate = $("#from-date").val();
            var endDate = $("#to-date").val();
            if (endDate < startDate) {
                toastr.error('To date should be greater than from date');
                return false;
            }
            reportsTable.ajax.reload();
        });
        $("#onResetClick").click(function () {
            $("#searchForm").trigger("reset");
            reportsTable.ajax.reload();
        });
        $('#deposit_id').keypress(function (e) {
            var key = e.which;
            if(key == 13)  // the enter key code
            {
                e.preventDefault();
                $('#onSearchClick').click();
                reportsTable.ajax.reload();
            }
        });

        $("#exportToExcel").click(function () {
            var params = {
                frm_date: $("#frm_date").val(),
                to_date: $("#to_date").val(),
                id: $("#user_id").val(),
                action: "export",
                responseType: "blob",
            };

            $.ajax({
                url: '{{url('admin/e-wallet/roi-income-data')}}',
                type: "POST",
                data: params,
                headers: {
                    "X-CSRF-TOKEN": csrf_token
                },
                dataType: "json",
                success: function (resp) {
                    if (resp.code === 200) {
                        var mystring = resp.data.data;
                        var myblob = new Blob([mystring], {
                            type: "text/plain",
                        });
                        var fileURL = window.URL.createObjectURL(new Blob([myblob]));
                        var fileLink = document.createElement("a");

                        fileLink.href = fileURL;
                        fileLink.setAttribute("download", "ROIincome.xls");
                        document.body.appendChild(fileLink);

                        fileLink.click();
                    } else {
                        toastr.error(resp.data.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                    console.log(error);
                }
            });
        });


    });

</script>
