 <!-- Right Sidebar -->
 <div class="right-bar">
    <div data-simplebar class="h-100">
        <div class="rightbar-title d-flex align-items-center p-3">
            <h5 class="m-0 me-2">Referral Links</h5>
            <a href="javascript:void(0);" class="right-bar-toggle-close ms-auto">
                <i class="fa-solid fa-xmark"></i>
            </a>
        </div>
        <hr class="m-0" />
        <div class="p-4 text-center">
            <h6 class="mb-3">Left Referral Link</h6>
            <input type="text" class="form-control" readonly id="referral-left"
            value="{{ url('/sign-up?ref_id=' . Auth::user()->user_id . '&position=1') }}">
            <div class="mt-2">
                <button type="button" class="btn btn-primary" id="refcopy1"
                onclick="myFunctionRefLeft()">
                    <i class="fa-regular fa-copy"></i> Copy
                </button>
            </div>
            <h6 class="mt-4 mb-3">Right Referral Link</h6>
            <input type="text" class="form-control" readonly id="myRightInput"
            value="{{ url('/sign-up?ref_id=' . Auth::user()->user_id . '&position=2') }}">
            <div class="mt-2">
                <button type="button" class="btn btn-primary" id="right-refcopy" onclick="myFunctionRefRight()">
                    <i class="fa-regular fa-copy"></i> Copy
                </button>
            </div>
            <img src="{{ asset('user-assets/images/Referral-link.png') }}" class="img-fluid mt-3">
            <div id="sidebar-setting"></div>
        </div>
    </div>
</div>
<div class="rightbar-overlay"></div>