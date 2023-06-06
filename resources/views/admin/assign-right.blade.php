@extends('layouts.user_type.admin-app')
@section('content')
      <div class="row">
        <div class="col-12 mx-auto">
          <div class="admin-card">
            <div class="admin-card-header">
              <h4 class="card-title">Add Assign Rights</h4>
            </div>
            <div class="admin-card-body">
              <div class="col-md-6 col-md-offset-3 col-xs-12">
                <div class="form-group">
                  <label>Select Sub Admin</label>
                  <select
                    class="form-control"
                    name="department"
                    id="subadmin"
                    onchange="onSelectAdminClick()">
                    <option value="">Select SubAdmin</option>
                    @foreach($arrSubadmins as $sub)
                    <option
                      value="{{$sub->id}}">
                      {{$sub->user_id}} ( {{ $sub->fullname }} )
                    </option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="admin-card">
        <div class="admin-card-body">
          <div class="col-md-12">
            <div class="row">
              <div class="col-md-offset-3 col-md-10">
                <div class="table-responsive">
                  <table class="table table-striped table-bordered">
                    <tbody>
                      <tr>
                        <td>
                          <strong>Name</strong>
                        <td id="fullname"></td>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <strong>Mobile No</strong>
                        </td>
                        <td id="mobile"></td>
                      </tr>
                      <tr>
                        <td>
                          <strong>Email Id</strong>
                        </td>
                        <td id="email"></td>
                      </tr>
                      <tr>
                        <td>
                          <strong>Sub Admin ID</strong>
                        </td>
                        <td id="user_id"></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="admin-card">
        <div class="admin-card-body">
          <div class="col-md-offset-3 col-md-12">
            <div class="panel panel-primary">
              <div class="panel-body masonry">
                <form>
                  <div class="row">
                    <div class="panel panel-color panel-primary" id="arrSubadminsNavigations">
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <div class="text-center">
              <button
                class="btn btn-primary waves-effect waves-light"
                type="button"
                onclick="updateAssignRights()"
              >
                Update Rights
              </button>
            </div>
          </div>
        </div>
      </div>
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://rawgit.com/moment/moment/2.2.1/min/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
  var arrSubadmins;
  $(document).ready(function () {
   // var auth = '{{Auth::user()->user_id}}';
  $.ajax({
    url: "{{ url('admin/getsubadmins') }}",
    method: 'GET',
    success: function(resp) {
      if (resp.code === 200) {
        arrSubadmins = resp.data;
      } 
    },
     error: function(xhr, status, error) {
        console.log(xhr.responseText);
    }
  });
  });
function onSelectAdminClick() {
  var selctedUser = $('#subadmin').val();
  if(selctedUser == ''){
    $('#fullname').text('');
    $('#mobile').text('');
    $('#email').text('');
    $('#user_id').text('');
    $('#arrSubadminsNavigations').hide();
    return false;
  }
      $('#arrSubadminsNavigations').show();
      for (let i = 0; i < arrSubadmins.length; i++) {
        if (arrSubadmins[i].id == selctedUser) {
          var subadminDetails = arrSubadmins[i];
          $('#fullname').text(subadminDetails.fullname);
          $('#mobile').text(subadminDetails.mobile);
          $('#email').text(subadminDetails.email);
          $('#user_id').text(subadminDetails.user_id);
          break;
        }
      }
      getSubadminNavigation();
    }
function getSubadminNavigation() {
var csrf_token = "{{ csrf_token() }}";
var arrSubadminsNavigations;
var html = '';
var div = '';
var username = $('#subadmin').val();   
  $.ajax({
    url: "{{ url('admin/getsubadminnavigation') }}",
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrf_token
    },
    data: {
        id: username
    },
    success: function(resp) {
    if (resp.code === 200) {
  const arrSubadminsNavigations = resp.data.navigations;
  let div = "";
  arrSubadminsNavigations.forEach(function(subNav) {
    let subdiv = "";
    subNav.childmenu.forEach(function(childData) {
      subdiv += '<div class="form-check checkbox-primary"><input name="arrNavigationId[]" type="checkbox" value="'+childData.id+'"><label>' + childData.menu + '</label></div>';
    });
    div += '<div class="panel-heading"><h3 class="panel-title">' + subNav.parentmenu.parent_menu + '</h3></div><div class="admin-card-body panel-border"><div class="form-group">' + subdiv + '</div></div>';
  });
  $('#arrSubadminsNavigations').html(div);
  // console.log(div);
}
    },
     error: function(xhr, status, error) {
        console.log(xhr.responseText);
    }
  });
}

function updateAssignRights() {
    var user_id = $('#subadmin').val();
    var csrf_token = "{{ csrf_token() }}";
    var arrNavigationId = $("input[name='arrNavigationId[]']:checked")
    .map(function() {
      return parseInt($(this).val());
    })
    .get()
    .join();
  var data = {
    navigations: {
      arrData: arrNavigationId
    },
    id: user_id,
  };
    $.ajax({
    url: "{{ url('admin/assignrights') }}",
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrf_token
    },
    data: data,
    success: function(resp) {
      if (resp.code === 200) {
        // toastr.success(resp.message);
        window.location.replace("{{url('admin/sub-admin/create-sub-admin')}}");
      } 
    },
     error: function(xhr, status, error) {
        console.log(xhr.responseText);
    }
    });
    }

</script>