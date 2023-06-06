@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Marketing Tool Report</h4>
                </div>
                <form id="searchForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-primary">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-4">
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
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="control-label">Tool Type</label>
                                                <select class="admin-form-control" id="tool_type">
                                                    <option value="">Select Tool type</option>
                                                    <option value="">All</option>
                                                    <option value="3">Creatives</option>
                                                    <option value="4">Business Presentation</option>
                                                    <option value="42">Founder & CEO</option>
                                                    <option value="1">Banner</option>
                                                    <option value="2">Business Presentation Video</option>
                                                    <option value="22">Tutorial Videos</option>
                                                    <option value="23">Promo Video</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="text-center">
                                                <button type="button" class="btn btn-primary waves-effect waves-light ml-4"
                                                    id="onSearchClick">
                                                    Search
                                                </button>
                                                <button type="button" class="btn btn-info waves-effect waves-light ml-4"
                                                    onclick="exportToExcel()">
                                                    Export To Excel
                                                </button>
                                                <button type="button" class="btn btn-dark waves-effect waves-light ml-4"
                                                    id="onResetClick">
                                                    Reset
                                                </button>
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
                        <table id="marketing-tool-report" class="display nowrap" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Sr No</th>
                                    <th>Date</th>
                                    <th>Tool Name</th>
                                    <th>Tool Type</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>

    <script>
        $(document).ready(function() {
            qualifiedUsersReport();
        });

        function qualifiedUsersReport() {
            let i = 1;
            var csrf_token = "{{ csrf_token() }}";

            setTimeout(function() {
                const table = $("#marketing-tool-report").DataTable({
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
                        "pageLength"
                    ],
                    ajax: {
                        url: "{{ url('admin/marketing-tool-report') }}",
                        type: "POST",
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        data: function(d) {
                            i = 0;
                            i = d.start + 1;
                            let params = {
                                tool_type: $('#tool_type').val(),
                                frm_date: $("#frm_date").val(),
                                to_date: $("#to_date").val()
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
                            data: "entry_time",
                            render: function(data) {

                                if (data === null || data === undefined || data === '') {
                                    return `-`;
                                } else {
                                    return moment(String(data)).format('YYYY-MM-DD');
                                }
                            }
                        },
                        {
                            data: 'tool_name'
                        },
                        {
                            data: 'tool_type'
                        },
                        {
                            data: "update_time",
                            render: function(data) {

                                if (data === null || data === undefined || data === '') {
                                    return `-`;
                                } else {
                                    return moment(String(data)).format('YYYY-MM-DD');
                                }
                            }
                        },
                        {

                            render: function(data, type, row) {
                                return `
                                        <a class="editmyProfile waves-effect" data-id="${row.srno}" title="Edit">
                                            <i class="fa fa-pencil font-16"></i>Edit
                                        </a>&nbsp;
                                        <label class="text-danger waves-effect" id="removeTool" data-id="${row.srno}">Remove
                                        </label>`;
                            },
                        },
                    ]
                });
                $(document).on('click', '.editmyProfile', function() {
                    var id = $(this).data('id');
                    // window.location.href = 'admin/edit-marketing-tool/' + id;;
                    window.location.href = "{{ url('admin/edit-marketing-tool') }}/" + id;
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

        $(document).on('click', '#removeTool', function() {
            var id = $(this).data('id');
            var csrf_token = "{{ csrf_token() }}";
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to remove this tool data?',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.value) {
                    var data = {
                        tool_id: id
                    };
                    $.ajax({
                        url: "{{ url('admin/remove-marketing-tool') }}",
                        method: "POST",
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        data: data,
                        success: function(resp) {
                            if (resp.code == 200) {
                                toastr.success(resp.message);
                                // location.reload();
                                $('#marketing-tool-report').DataTable().ajax.reload();
                            } else {
                                toastr.error(resp.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            toastr.error('An error occurred while processing your request.');
                        }
                    });
                }
            });
        });

        function exportToExcel() {
            var csrf_token = "{{ csrf_token() }}";
            var data = {
                id: $("#user_id").val(),
                frm_date: $("#frm_date").val(),
                to_date: $("#to_date").val(),
                action: "export",
                responseType: "blob",
            };

            $.ajax({
                url: "{{ url('admin/marketing-tool-report') }}",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                data: data,
                dataType: 'json',
                success: function(resp) {
                    if (resp.code === 200) {
                        var mystring = resp.data.data;
                        // console.log(resp.data.data);
                        var myblob = new Blob([mystring], {
                            type: 'text/plain'
                        });

                        var fileURL = window.URL.createObjectURL(new Blob([myblob]));
                        var fileLink = document.createElement('a');

                        fileLink.href = fileURL;
                        fileLink.setAttribute('download', 'MarketingToolReport.xls');
                        document.body.appendChild(fileLink);

                        fileLink.click();
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(textStatus, errorThrown);
                }
            });
        }

        $("#onResetClick").click(function() {
            $("#searchForm").trigger("reset");
            var startDate = $("#frm_date").val("");
            var endDate = $("#to_date").val("");
            var user_id = $("#user_id").val("");
            $('#marketing-tool-report').DataTable().ajax.reload();
        });
    </script>
@endsection
