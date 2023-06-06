@extends('layouts.user_type.admin-app')
@section('content')

<div class="row">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="card-title">Add Power to upline Report</h4>
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
                                  <input type="date" class="admin-form-control" name="frm_date" placeholder="From Date" id="frm_date" />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="form-group">
                              <label>To Date</label>
                              <div>
                                <div class="input-group">
                                  <input type="date" class="admin-form-control" name="to_date" placeholder="To Date" id="to_date" />
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="form-group">
                              <label>User Id</label>
                              <input class="admin-form-control" onkeyup="checkUserExisted(this.value)" placeholder="Enter user id" type="text" id="to_user_id" />
                              <p class="text-success" id="isAvailable"></p>
                              <span class="text-success" id="usernameInfo"></span>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-md-12">
                              <div class="text-center">
                                <button type="button" class="btn btn-primary waves-effect waves-light ml-4" id="onSearchClick">
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
                      <!-- panel-body -->
                    </div>
                    <!-- panel -->
                  </div>
                  <!-- col -->
                </div>
              </form>
              
            <div class="admin-card-body">
                <div class="table-responsive admin-table">
                    <table id="binary-income-report" class="display nowrap" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sr.No</th>
                                <th>User</th>
                                <th>Upline User</th>
                                <th>Action</th>
                                <th>Business Amount</th>
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

<script src="https://code.jquery.com/jquery-3.6.4.js"></script>

<script>
    $(document).ready(function() {
        getLevelIncome();
    });

    function getLevelIncome() {
        let i = 1;
        var csrf_token = "{{ csrf_token() }}";

        setTimeout(function() {
            const table = $("#binary-income-report").DataTable({
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
                    url: '{{ url('admin/manage-power/business-upline-report') }}',
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    },
                    data: function(d) {
                        i = 0;
                        i = d.start + 1;
                        let params = {
                            id: $("#user_id").val(),
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
                columns: [
      { 
        render: function () {
          return i++;
        },
      },
      { data:"user" },
      { data:"uplineuser" },
      { 
        render: function (data, type, row) {
          if (row.type == '1') {
            return 'Add Business';
          } else if (row.type == '2') {
            return 'Add business upto ';
          } else if (row.type == '3') {
            return 'Remove Business';
          } else if (row.type == '4') {
            return 'Remove business upto Admin';
          } else {
            return '-';
          }
        },
      },
      { data:"power_bv" },
      { data:"position" },
      { data:"before_lbv" },
      { data:"before_rbv" },
      { data:"after_lbv" },
      { data:"after_rbv" },
      { data:"before_curr_lbv" },
      { data:"before_curr_rbv" },
      { data:"after_curr_lbv" },
      { data:"after_curr_rbv" },
      { 
        data:"entry_time",

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

    function changeUserRoiStatus(id) {
        new Swal({
            title: "Are you sure?",
            text: "You want to change ROI status of this user",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes",
        }).then((result) => {
            if (result.value) {
                var csrf_token = "{{ csrf_token() }}";
                $.ajax({
                    type: "POST",
                    url: "{{ url('admin/topuproistop') }}",
                    headers: {
                        'X-CSRF-TOKEN': csrf_token
                    },
                    data: {
                        sr_no: id,
                        // status: status,
                    },
                    success: function(resp) {
                        if (resp.code == 200) {
                            toastr.success(resp.message);
                            $('#binary-income-report').DataTable().ajax.reload();
                        } else {
                            toastr.error(resp.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    },
                });
            }
        });
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
            url: '{{ url('admin/manage-power/business-upline-report') }}',
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
                fileLink.setAttribute('download', 'Upline-Power-Report.xls');
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
        $('#binary-income-report').DataTable().ajax.reload();
    });
</script>

@endsection