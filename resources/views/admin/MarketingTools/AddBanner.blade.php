@extends('layouts.user_type.admin-app')
@section('content')


<div class="row">
        <div class="admin-card-button" v-if="otpstatus == 1">
          <button type="button" class="btn btn-primary waves-effect waves-light" onclick="sendAdminOtp()">Send Otp </button> 
          <p>Note :- Otp Valid 2 Hours</p>
        </div>
        <div class="col-6 mx-auto">
          <div class="admin-card">
            <div class="admin-card-header">
              <h4 class="card-title">Add Banner</h4>
            </div>           
            <div class="admin-card-body">
              <form class="row g-3"  id="myform" method="post" action="{{url('admin/add-marketing-tool')}}" enctype="multipart/form-data">
                @csrf
                <input type="text" class="d-none" id="tool_type" name="tool_type" value="1" /> 
                <div class="form-group col-12">
                  <label for="tool_name">Name</label>
                  <input
                    type="text"
                    class="admin-form-control"
                    id="tool_name"
                    name="tool_name"
                    value="{{ old('tool_name') }}" 
                    placeholder="Name"
                    required                
                  />
                <div class="tooltip1">
                  <span class="text-danger" id="name-err">               
                      </span>
                  </div>
                </div>
                <div class="form-group col-12">
                  <label for="market_tool">Choose Banner</label>
                  <input
                    type="file"
                    class="admin-form-control"
                    id="market_tool"
                    value="{{ old('market_tool') }}" 
                    name="market_tool"
                    placeholder="Banner"
                    accept="image/png, image/jpeg, image/jpg" 
                    required
                  />
                </div>
                
                <div class="form-group col-12"  v-if="otpstatus == 1">
                  <label>OTP</label>
                  <input
                    type="text"
                    class="admin-form-control"
                    id="otp"
                    placeholder="Enter Otp"
                    name="otp"
                    data-vv-as="OTP"
                    required
                    maxlength="15"
                  />
                </div>
                <div class="form-group col-12 text-center">
                  <button
                    type="submit"
                    class="btn btn-rounded btn-outline-primary" id="selftopup">
                    Submit
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
      <script>
            var csrf_token = "{{ csrf_token() }}";

            $(document).ready(function() {
            // $("#tool_name").on("input", function() {
            //     const fullname = $(this).val();
            //     var fullnamel = fullname.replace(/ /g, "");
            //     if (fullname == "") {
            //     $('#selftopup').prop('disabled', true);
            //     $("#name-err").html("Name should not be blank.");
            //     } else if (fullnamel.length < 10) {
            //     $("#name-err").html("Name must be at least 10 characters long");
            //     $('#selftopup').prop('disabled', true);
            //     } else {
            //     $("#name-err").html("");
            //     $('#selftopup').prop('disabled', false);
            //     }
            // });
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


@endsection