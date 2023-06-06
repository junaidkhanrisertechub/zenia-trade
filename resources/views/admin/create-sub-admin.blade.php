@extends('layouts.user_type.admin-app')
@section('content')
<div class="row">
        <div class="col-12 mx-auto">
          <div class="admin-card">
            <div class="admin-card-header">
              <h4 class="card-title">Create Sub Admin</h4>
            </div>
            <div class="admin-card-body">
              <form method="post" action="{{url('admin/create/subadmin')}}">
                 @csrf
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>User Name (User Id)</label>
                      <input
                        name="user_id"
                        id="user_id"
                        class="admin-form-control no-space"
                        placeholder="Enter User Name"
                        type="text"
                        oninput="checkUser()"
                      />
                      <p id="isAvialable" class=""></p>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group"
                      style="position: relative"
                    >
                      <label>Password </label>
                      <input
                        name="password"
                        class="admin-form-control"
                        placeholder="Enter Password"
                        type="password" id="password"
                      />
                        <p id="passwordError" class=""></p>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Sub Admin Name</label>
                      <input
                        name="fullname"
                        class="admin-form-control"
                        placeholder="Enter Sub Admin Name"
                        type="text"
                      />
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Email Id</label>
                      <input
                        name="email"
                        class="
                          admin-form-control"
                        placeholder="Enter Email Id"
                        type="email"
                      />
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group" style="position: relative">
                      <label>Contact Number</label>
                      <input
                        name="mobile"
                        class="
                          admin-form-control"
                        id="mobile"
                        placeholder="Enter Contact Number"
                        type="text"
                      />
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Department</label>
                      <select
                        name="type"
                        class="admin-form-control">
                        <option selected="" value="">Select Department</option>
                        <option value="sub-admin">Sub Admin</option>
                      </select>
                    </div>
                  </div>
                  <span class="text-danger pb-lg-2">*Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ * <br>
                    *User ID must be lest 4 character
                  </span>
                </div>
                <div class="row">
                  <div class="col-md-12">
                    <div class="text-center">
                      <button
                        type="submit"
                        id="submit"
                        class="btn btn-rounded btn-outline-primary"
                      >Add Sub Admin</button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
            <div class="admin-card-body">
              <div class="table-responsive admin-table">
                <table
                  id="subadmin-report"
                  class="display nowrap"
                  style="width: 100%"
                >
                  <thead>
                    <tr>
                      <th>Sr.No</th>
                      <th>User ID</th>
                      <th>Full Name</th>
                      <th>Mobile No</th>
                      <th>Email Id</th>
                      <th>Department</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                </table>
              </div>
            </div>
          </div>
        </div>
        <!-- end row -->
      </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>
<script>

  $(document).ready(function() {
    const myInput = document.getElementById('user_id');
    myInput.onpaste = e => e.preventDefault();
    $('#user_id').on('keydown', function(event) {
      if (event.code === 'Space') {
        event.preventDefault();
        // $('#isAvialable').addClass('text-danger').removeClass('text-success').html('You can`t use space in User Name.');
        // $("#submit").attr('disabled',true);
      }
    });
  });

  function checkUser() {
    var csrf_token = "{{ csrf_token() }}";
   var username = $('#user_id').val();
   if (username == 'admin' || username == 'Admin' || username == 'ADMIN'){
    $('#isAvialable').addClass('text-danger').removeClass('text-success').html('This user id already exist');
    $('#submit').prop('disabled', true);
    return false;
    }
    if (username.length < 4) {
        $('#isAvialable').addClass('text-danger').removeClass('text-success').html('User ID must be lest 4 character');
        $("#submit").attr('disabled',true);
        return false;
    }else{
        $("#submit").attr('disabled',false);
    }
  $.ajax({
    url: "{{ url('admin/checkuserexist') }}",
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrf_token
    },
    data: {
        user_id: username
    },
    success: function(resp) {
      if (resp.code === 200) {
        $('#isAvialable').addClass('text-danger').removeClass('text-success').html('This user id already exist');
        $('#submit').prop('disabled', true);
      } else {
         $('#isAvialable').addClass('text-success').removeClass('text-danger').html('You can use this Name');
         $('#submit').prop('disabled', false);
      }
    },
     error: function(xhr, status, error) {
        console.log(xhr.responseText);
    }
  });
}


 $(document).ready(function () {
        var csrf_token = $('meta[name="csrf-token"]').attr('content');
        var i = 0;
        var reportsTable = $("#subadmin-report").DataTable({
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
                "excelHtml5",
                "pageLength",
            ],
            ajax: {
                url: '{{url('admin/getsubadminsdetails')}}',
                type: "POST",
                data: function (d) {
                    i = 0;
                    i = d.start + 1;
                    let params = {};
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
                        {
                            return i++;
                        }
                    },
                },
             { data: "user_id" },
            { data: "fullname" },
            { data: "mobile" },
            { data: "email" },
            { data: "type" },
            {
              data: "entry_time",
              render: function (data) {
                if (data === null || data === undefined || data === "") {
                  return "-";
                } else {
                  return moment(String(data)).format("YYYY-MM-DD");
                }
              },
            },
          ],
        });


     $('#password').on('input',function() {
         var password = $(this).val();
         if (!/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9!@#$*])[A-Za-z][A-Za-z\d!@#$*]{7,}$/.test(password)) {
             $('#passwordError').addClass('text-danger').html('Invalid password format');
             $("#submit").attr('disabled',true)
         }else{
             $('#passwordError').addClass('text-danger').html('');
             $("#submit").attr('disabled',false)
         }
     });
        });

</script>
