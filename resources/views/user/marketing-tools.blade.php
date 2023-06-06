@extends('layouts.user_type.auth-app')
@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
        integrity="sha512-r4P4/74y7PPu0tdbgHy1tTzTtBhZt07Evgb//O9ik99aL+gSwE3hKpynzHJm+e1dx2FmZcOovhWY9X5vBIBQg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script async defer src="https://platform.twitter.com/widgets.js"></script>


    <style type="text/css">
        .tab-content.newborder.iframeclass iframe {
            width: 100%;
            margin-bottom: 10px;
        }

        .shareIcon {
            position: relative !important;
        }

        iframe {
            border: 0;
            /*   width: 250px !important;*/
            /*   height: auto;*/
        }
    </style>
    <div class="page-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <nav aria-label="breadcrumb ms-3">
                        <!-- <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6">
                                          <li class="breadcrumb-item text-sm">
                                            <a class="opacity-5 text-dark" href="javascript:;">Pages</a>
                                          </li>
                                          <li
                                            class="breadcrumb-item text-sm text-dark active"
                                            aria-current="page"
                                          >
                                            Fund Wallet
                                          </li>
                                          </ol> -->
                        <h6 class="font-weight-bolder mb-0">
                            Marketing Tools
                        </h6>
                    </nav>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  active" id="3" data-bs-toggle="pill"
                                data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                                aria-selected="true">Creatives</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  " id="4" data-bs-toggle="pill" data-bs-target="#pills-profile"
                                type="button" role="tab" aria-controls="pills-profile"
                                aria-selected="false">Presentation</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  " id="1" data-bs-toggle="pill" data-bs-target="#pills-Banners"
                                type="button" role="tab" aria-controls="pills-Banners"
                                aria-selected="false">Banners</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link  " id="2" data-bs-toggle="pill" data-bs-target="#pills-Videos"
                                type="button" role="tab" aria-controls="pills-Videos"
                                aria-selected="false">Videos</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                            aria-labelledby="pills-home-tab" tabindex="0">
                            <div class="row Creatives">
                                @foreach ($arrData as $data)
                                    @if ($data->tool_type == 3)
                                        <div class="col-md-3 mb-4">
                                            @if (strstr($data->tool_url, 'www.youtube.com'))
                                                <div style="margin:0 0px;">{!! $data->tool_url !!}</div>
                                            @else
                                                {{-- <a href="{{ $data->tool_url }}" target="blank_" download> --}}
                                                <div class="shareIcon">
                                                    <div class="social-share">
                                                        <label class="toggle" for="creative{{ $data->srno }}">
                                                            <input type="checkbox" id="creative{{ $data->srno }}" />
                                                            <div class="btn">
                                                                <i class="fa fa-share-alt"></i>
                                                                <i class="fa fa-times"></i>
                                                                <div class="social">
                                                                    <a href="https://www.facebook.com/dialog/share?app_id=87741124305&href={{ $data->tool_url }}"
                                                                        target="_blank">
                                                                        <i class="fa-brands fa-square-facebook"
                                                                            aria-hidden="true"></i>
                                                                    </a>
                                                                    
                                                                    <a href="https://api.whatsapp.com/send?phone=&text={{ $data->tool_url }}&source=&data="
                                                                        target="_blank">
                                                                        <i class="fa-brands fa-square-whatsapp"></i>
                                                                    </a>
                                                                    
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <a href="{{ $data->tool_url }}" class="btn btn-light" download>
                                                        <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                                <a href="{{ $data->tool_url }}" target="blank_" download>
                                                    <img src="{{ $data->tool_url }}" class="img-fluid">
                                                </a>
                                                <!-- <img src="../images/marketing-tool/download.png" class="downloadIcon"> -->
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                        </div>
                        <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                            aria-labelledby="pills-profile-tab" tabindex="0">
                            <ul class="nav nav-pills mb-3 center-me" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation1">
                                    <button class="nav-link active front-btn" id="42" data-bs-toggle="pill"
                                        data-bs-target="#pills-home11" type="button" role="tab"
                                        aria-controls="pills-home11" aria-selected="true">Founder & CEO</button>
                                </li>
                                <li class="nav-item" role="presentation1">
                                    <button class="nav-link front-btn" id="4" data-bs-toggle="pill"
                                        data-bs-target="#pills-home22" type="button" role="tab"
                                        aria-controls="pills-home22" aria-selected="false">Business Presentation
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content newborder" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-home11" role="tabpanel"
                                    aria-labelledby="pills-home-tab">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h2>Founder & CEO </h2>
                                        </div>
                                        @foreach ($arrData as $data)
                                            @if ($data->tool_type == 42)
                                                <div class="col-md-3">
                                                    <!-- <div v-html="video.tool_url" style="width: 100%;"></div> -->
                                                    <a href="{{ $data->tool_url }}" class="card p-3" target="_blank">
                                                        <img src="{{ asset('images/marketing-tool/ceo.png') }}"
                                                            class="img-fluid">
                                                        <div class="PresentationHead">
                                                            {{ $data->tool_name }}
                                                            <i class="fa-solid fa-file-arrow-down"></i>
                                                        </div>
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-home22" role="tabpanel"
                                    aria-labelledby="pills-profile-tab">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h2>Business Presentation</h2>
                                        </div>
                                        @foreach ($arrData as $data)
                                            @if ($data->tool_type == 4)
                                                <div class="col-md-3">
                                                    <!-- <div v-html="video.tool_url" style="width: 100%;"></div> -->
                                                    <a href="{{ $data->tool_url }}" class="card p-3" target="_blank">
                                                        <img src="{{ asset('images/marketing-tool/presentation.png') }}"
                                                            class="img-fluid">
                                                        <div class="PresentationHead">
                                                            {{ $data->tool_name }}
                                                            <i class="fa-solid fa-file-arrow-down"></i>
                                                        </div>
                                                    </a>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-Banners" role="tabpanel"
                            aria-labelledby="pills-Banners-tab" tabindex="0">
                            <div class="row">
                                @foreach ($arrData as $data)
                                    @if ($data->tool_type == 1)
                                        <div class="col-md-4">
                                            {{-- <img src="{{ $data->tool_url }}" class="img-fluid"> --}}
                                            {{-- <a href="{{ $data->tool_url }}" class="card p-1" download> --}}
                                            <div class="shareIcon">
                                                <div class="social-share">
                                                    <label class="toggle" for="banner{{ $data->srno }}">
                                                        <input type="checkbox" id="banner{{ $data->srno }}" />
                                                        <div class="btn">
                                                            <i class="fa fa-share-alt"></i>
                                                            <i class="fa fa-times"></i>
                                                            <div class="social">
                                                                <a href="https://www.facebook.com/dialog/share?app_id=87741124305&href={{ $data->tool_url }}"
                                                                    target="_blank"><i class="fa fa-facebook"></i></a>
                                                                <a href="https://api.whatsapp.com/send?phone=&text=encodeURIComponent({{ $data->tool_url }})&source=&data="
                                                                    target="_blank"><i class="fa fa-whatsapp"></i></a>
                                                             
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                                <a href="{{ $data->tool_url }}" class="btn btn-light" download>
                                                    <i class="fa-solid fa-arrow-down"></i>
                                                </a>
                                            </div>
                                            <a href="{{ $data->tool_url }}" target="blank_" download>
                                                <img src="{{ $data->tool_url }}" class="img-fluid">
                                                <!-- <img src="user-assets/images/marketing-tool/download.png" class="downloadIcon"> -->
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-Videos" role="tabpanel" aria-labelledby="pills-Videos-tab"
                            tabindex="0">
                            <ul class="nav nav-pills mb-3 center-me" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active front-btn" id="2" data-bs-toggle="pill"
                                        data-bs-target="#pills-video1" type="button" role="tab"
                                        aria-controls="pills-video1" aria-selected="true">Business Presentation
                                        Videos</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link front-btn" id="22" data-bs-toggle="pill"
                                        data-bs-target="#pills-video2" type="button" role="tab"
                                        aria-controls="pills-video2" aria-selected="false">Tutorial Videos</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link front-btn" id="23" data-bs-toggle="pill"
                                        data-bs-target="#pills-video3" type="button" role="tab"
                                        aria-controls="pills-video3" aria-selected="false">Promo Videos</button>
                                </li>
                            </ul>
                            <div class="tab-content newborder iframeclass" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-video1" role="tabpanel"
                                    aria-labelledby="pills-home-tab">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h2>Business Presentation Videos</h2>
                                        </div>
                                        <!-- <iframe width="560" height="315" src="https://www.youtube.com/embed/FW4DFRsRjj4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe> -->
                                        @foreach ($arrData as $data)
                                            @if ($data->tool_type == 2)
                                                <div class="col-md-6" v-for="video in videosBusiness"
                                                    v-bind:key="{{ $data->srno }}">
                                                    <!-- <div v-html="video.tool_url" style="width: 100%;"></div> -->
                                                    @if (strstr($data->tool_url, '<iframe'))
                                                        {!! $data->tool_url !!}
                                                    @else
                                                        <video width="100%" height="400" controls>
                                                            <source src="{{ $data->tool_url }}"
                                                                type="`video/${String({{ $data->tool_url }}).split('.')[String({{ $data->tool_url }}).split('.').length - 1]}`">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-video2" role="tabpanel"
                                    aria-labelledby="pills-profile-tab">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h2>Tutorial Videos</h2>
                                        </div>
                                        <!-- <iframe width="560" height="315" src="https://www.youtube.com/embed/FW4DFRsRjj4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe> -->
                                        @foreach ($arrData as $data)
                                            @if ($data->tool_type == 22)
                                                <div class="col-md-6" v-for="video in videosTutorial"
                                                    v-bind:key="{{ $data->srno }}">
                                                    <!-- <div v-html="video.tool_url" style="width: 100%;"></div> -->
                                                    @if (strstr($data->tool_url, '<iframe'))
                                                        {!! $data->tool_url !!}
                                                    @else
                                                        <video width="100%" height="400" controls>
                                                            <source src="{{ $data->tool_url }}"
                                                                type="`video/${String({{ $data->tool_url }}l).split('.')[String({{ $data->tool_url }}).split('.').length - 1]}`">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-video3" role="tabpanel"
                                    aria-labelledby="pills-contact-tab">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h2>Promo Videos</h2>
                                        </div>
                                        <!-- <iframe width="560" height="315" src="https://www.youtube.com/embed/FW4DFRsRjj4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe> -->
                                        @foreach ($arrData as $data)
                                            @if ($data->tool_type == 23)
                                                <div class="col-md-6" v-for="video in videosPromo"
                                                    v-bind:key="{{ $data->srno }}">
                                                    <!-- <div v-html="{{ $data->tool_url }}" style="width: 100%;"></div> -->
                                                    @if (strstr($data->tool_url, '<iframe'))
                                                        {!! $data->tool_url !!}
                                                    @else
                                                        <video width="100%" height="400" controls>
                                                            <source src="{{ $data->tool_url }}"
                                                                type="video/{String({{ $data->tool_url }}).split('.')[String({{ $data->tool_url }}).split('.').length - 1]}">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Button trigger modal -->
        <!-- <button
                              type="button"
                              class="btn btn-primary"
                              data-bs-toggle="modal"
                              data-bs-target="#exampleModal"
                              >
                              Enter OTP
                              </button> -->
        <!-- Modal -->
        <div class="modal fade" id="editBankDetailsmodal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Enter OTP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row align-items-center justify-content-center">
                            <div class="col-md-3">
                                <img src="{{ asset('images/otp.png') }}" class="img-fluid" />
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12">
                                        <!-- <input
                                                      type="text"
                                                      class="form-control"
                                                      placeholder="Enter OTP"
                                                      /> -->
                                        <input type="text" v-model="otp" class="form-control"
                                            placeholder="Enter OTP"
                                            onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57"
                                            maxlength="6" />
                                    </div>
                                    <div class="col-md-12 mt-3" v-if="google2fa_status=='enable'">
                                        <input type="text" name="2fa-otp" class="form-control w1000"
                                            placeholder="Enter G2FA OTP" v-model="otp_2fa" maxlength="6"
                                            onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 48 && event.charCode <= 57">
                                        <!-- <input type="text" class="form-control" placeholder="Enter OTP"> -->
                                    </div>
                                    <!-- <div class="col-md-12 text-center mt-3">
                                                   <button class="btn bg-gradient-primary">Resend</button>
                                                   </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <!-- <button type="button" class="btn btn-light">Submit</button> -->
                        <button type="button" class="btn btn-light" @click="transferFund()" data-bs-dismiss="modal">
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function getTools(getid) {
            window.location.href = "{{ url('/marketing-tools') }}?tool_type=" + getid;
            //     var tool_type = getid;
            //  console.log(tool_type)
            //     var csrf_token = "{{ csrf_token() }}";
            //     $.ajax({

            //         url: "{{ url('/marketing-tools') }}",
            //         type: 'POST',
            //         data    :{ tool_type:tool_type },
            //         headers: {
            //             'X-CSRF-TOKEN': csrf_token
            //         },
            //         success: function(response) {
            //             console.log(response);
            //         },

            //         error: function(xhr, status, error) {
            //             console.log(error);
            //         }

            //     });

        }
    </script>
@endsection
