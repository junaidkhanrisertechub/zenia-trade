@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="page-title">Power BV Report</h4>
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
                                                        <input type="date" class="admin-form-control" name="frm_date" placeholder="From Date" id="frm_date" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>To Date</label>
                                                <div>
                                                    <div class="input-group">
                                                        <input type="date" class="admin-form-control" name="to_date" placeholder="To Date" id="to_date" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>User Id</label>
                                                <input class="admin-form-control" onkeyup="checkUserExisted(this.value)" placeholder="Enter user id" type="text" id="user_id" />
                                            </div>
                                        </div>
    
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <div class="text-center">
                                                    <button type="button" class="btn btn-primary  waves-effect waves-light ml-4" id="onSearchClick">
                                                        Search
                                                    </button>
                                                    <button type="button" class="btn btn-info waves-effect waves-light ml-4" onclick="exportToExcel()">
                                                        Export To Excel
                                                    </button>
                                                    <button type="button" class="btn btn-dark waves-effect waves-light ml-4" id="onResetClick">
                                                        Reset
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="admin-card-body">
                    <div class="table-responsive admin-table">
                        <table v-once id="manage-user-report" class="display nowrap" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Sr.No</th>
                                    <th>User Id</th>
                                    <th>Full Name</th>
                                    <th>Power Business</th>
                                    <th>Position</th>
                                    <th>Before Left Business</th>
                                    <th>Before Right Business</th>
                                    <th>After Left Business</th>
                                    <th>After Right Business </th>
                                    <th>Before Curr Amt left Business</th>
                                    <th>Before Curr Amt Rght Business</th>
                                    <th>After Curr Amt left Business</th>
                                    <th>After Curr Amt Rght Business</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        $(document).ready(function() {
            powerReport();
        });

  

        function powerReport() {
            let i = 0;
            var csrf_token = "{{ csrf_token() }}";
            setTimeout(function() {
                const table = $('#manage-user-report').DataTable({
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
                        [10, 50, 100],
                    ],
                    buttons: [
                        "pageLength",
                    ],
                    ajax: {
                        url: '{{ url('admin/power-report') }}',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf_token
                        },
                        data: function(d) {
                            i = 0;
                            i = d.start + 1;
                            let params = {
                                frm_date: $('#frm_date').val(),
                                to_date: $('#to_date').val(),
                                user_id: $('#user_id').val(),
                            };
                            Object.assign(d, params);
                            return d;
                        },
                        
                        dataSrc: function(json) {
                            if (json.code === 200) {
                                json['recordsFiltered'] = json.data.recordsFiltered;
                                json['recordsTotal'] = json.data.recordsTotal;
                                return json.data.records;
                            } else if (json.code === 401 || json.code === 403) {
                                localStorage.removeItem("admin_token");
                                location.reload();
                            } else {
                                $('#totalSum').html(0);
                                json['recordsFiltered'] = 0;
                                json['recordsTotal'] = 0;
                                return json;
                            }
                        }
                    },
                    columns: [{
                            render: function() {
                                return i++;
                            },
                        },
                        {
                            data: 'user'
                        },
                        {
                            data: 'fullname'
                        },
                        {
                            data: 'power_bv'
                        },
                        {
                            render: function(data, type, row) {
                                if (row.position == 1) {
                                    return 'Left';
                                } else {
                                    return 'Right';
                                }
                            }
                        },
                        {
                            data: 'before_lbv'
                        },
                        {
                            data: 'before_rbv'
                        },
                        {
                            data: 'after_lbv'
                        },
                        {
                            data: 'after_rbv'
                        },
                        {
                            data: 'before_curr_lbv'
                        },
                        {
                            data: 'before_curr_rbv'
                        },
                        {
                            data: 'after_curr_lbv'
                        },
                        {
                            data: 'after_curr_rbv'
                        },
                        {
                            render: function(data, type, row) {
                                if (row.entry_time === null || row.entry_time === undefined || row
                                    .entry_time === '') {
                                    return `-`;
                                } else {
                                    // return moment(String(row.entry_time)).format('YYYY-MM-DD');
                                    return row.entry_time;
                                }
                            }
                        },
                    ]
                });
                $('#onSearchClick').click(function() {
                    table.ajax.reload();
                });
                $('#onResetClick').click(function() {
                    $('#searchForm').trigger("reset");
                    table.ajax.reload();
                });
            }, 0);
        }





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
                url: '{{ url('admin/power-report') }}',
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': csrf_token
                },
                data: data,
                dataType: 'json',
                success: function(resp) {
                    var mystring = resp.data.data;
                    var myblob = new Blob([mystring], {
                        type: 'text/plain'
                    });

                    var fileURL = window.URL.createObjectURL(new Blob([myblob]));
                    var fileLink = document.createElement('a');

                    fileLink.href = fileURL;
                    fileLink.setAttribute('download', 'Power-Report.xls');
                    document.body.appendChild(fileLink);

                    fileLink.click();
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
            $('#manage-user-report').DataTable().ajax.reload();
        });
    </script>
@endsection
