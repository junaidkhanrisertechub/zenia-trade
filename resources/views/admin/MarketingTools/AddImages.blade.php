@extends('layouts.user_type.admin-app')
@section('content')
    <div class="row">
       
        <div class="col-6 mx-auto">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h4 class="card-title">Add Images</h4>
                </div>
                <div class="admin-card-body">
                    <form class="row g-3" id="myform" method="post" action="{{ url('admin/add-marketing-tool') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="text" class="d-none" id="tool_type" name="tool_type" value="1" />
                      
                        <div class="form-group col-12">
                            <label for="market_toolimages">Choose Images</label>
                            <input type="file" class="admin-form-control" id="market_toolimages"
                                value="{{ old('market_tool') }}" name="market_toolimages[]" placeholder="Images"
                                accept="image/png, image/jpeg, image/jpg" multiple />
                        </div>

                        <p class="text-danger">Note :- You can upload only 30 Images at once attempt.</p>
                        <div class="form-group col-12 text-center">
                            <button type="submit" class="btn btn-rounded btn-outline-primary" id="selftopup">
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

        const inputElement = document.getElementById("market_toolimages");
        inputElement.addEventListener("change", handleFiles, false);

        function handleFiles() {
            const fileList = this.files;
            if (fileList.length > 30) {
                toastr.error("You can only upload up to 30 images.");
                this.value = "";
            }
        }
    </script>
@endsection
