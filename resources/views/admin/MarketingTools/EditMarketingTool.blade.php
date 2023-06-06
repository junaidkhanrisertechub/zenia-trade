@extends('layouts.user_type.admin-app')
@section('content')
 <div class="row">
  <div class="admin-card-button">
    <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp()">Send Otp </button> 
    <p>Note :- Otp Valid 2 Hours</p>
  </div>
  <div class="col-6 mx-auto">
    <div class="admin-card">
      <div class="admin-card-header">
        <h4 class="card-title">Edit Marketing Tool</h4>
      </div>     
      <div class="admin-card-body">
        <form class="row g-3" method="post" action="{{url('admin/update-marketing-tool')}}">   
         @csrf      
         <input type="text" class="d-none" id="tool_id" name="tool_id"/> 
          <div class="form-group col-12">
            <label for="tool_name">Name</label>
            <input
              type="text"
              class="admin-form-control"
              id="tool_name"
              name="tool_name"
              placeholder="Name"             
            />
          <div class="tooltip1">
            <span class="text-danger" id="name-err">               
                </span>
            </div>
          </div>          
          <div class="form-group col-12"  v-if="otpstatus == 1">
            <label>OTP</label>
            <input
              type="text"
              class="admin-form-control"
              id="otp"
              placeholder="Enter Otp"
              name="otp"
              data-vv-as="OTP "
            />
          </div>
          <div class="form-group col-12 text-center">
            <button type="submit" id="selftopup" class="btn btn-rounded btn-outline-primary">
              Submit
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="http://code.jquery.com/jquery-latest.js"></script>
<script>
var csrf_token = "{{ csrf_token() }}";

$(document).ready(function() {
  var path = window.location.pathname;
  var id = path.substring(path.lastIndexOf('/') + 1);
  $.ajax({
    url: "{{url('/admin/get-tool-details')}}",
    type: 'POST',
    data: {id: id},
    headers: {
    'X-CSRF-TOKEN': csrf_token
    },
    success: function(resp) {
      if(resp.code === 200) {
        var editTool = {
          id: resp.data.toolData.id,
          tool_name: resp.data.toolData.tool_name,
          tool_type: resp.data.toolData.tool_type
        };
        $('#tool_name').val(editTool.tool_name);
        $('#tool_id').val(editTool.id);
        // do something with the editTool object
      } else {
        toastr.error(resp.data.message);
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      toastr.error('An error occurred while processing your request.');
    }
  });

$("#tool_name").on("input", function() {
    const fullname = $(this).val();
    var fullnamel = fullname.replace(/ /g, "");
    if (fullname == "") {
    $('#selftopup').prop('disabled', true);
    $("#name-err").html("Name should not be blank.");
    } else if (fullnamel.length < 10) {
    $("#name-err").html("Name must be at least 10 characters long");
    $('#selftopup').prop('disabled', true);
    } else {
    $("#name-err").html("");
    $('#selftopup').prop('disabled', false);
    }
});
});
function sendAdminOtp() {
  var data = {type: 4};
  $.ajax({
      url: "{{url('/admin/send-otp-withdraw-mail')}}", // Replace with your API endpoint
      type: 'POST',
      data: data,
      headers: {

      'X-CSRF-TOKEN': csrf_token

      },
      success: function(resp) {
      if (resp.code === 200) {
          toastr.success(resp.message);
      } else {
          toastr.error(resp.message);
      }
      },
      error: function(jqXHR, textStatus, errorThrown) {
      toastr.error('Error: ' + errorThrown);
      }
  });
  }
</script>


