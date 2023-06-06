<div class="admin-nav-header">
    <a href="#" class="admin-brand-logo">
        <img src="{{asset('admin-assets/images/icon.png')}}" class="img-fluid">
    </a>
    <div class="admin-nav-control">
        <div class="hamburger">
            <span class="line"></span><span class="line"></span><span class="line"></span>
        </div>
    </div>
</div>

<div class="admin-header-top">
    <div class="header-content">
        <nav class="admin-navbar navbar-expand">
            <div class="collapse navbar-collapse justify-content-between">
                <div class="header-left">
                    <div class="dashboard_bar"></div>
                </div>
                <div class="header-right">
                    <li>
                        <span class="btn btn-light mt-4" id="logout-btn">Logout</span>
                    </li>
                </div>
            </div>
        </nav>
    </div>
</div>
<div class="deznav">
    <div class="deznav-scroll">
        <div id="sidebar-menu" style="overflow: hidden !important">
            
        </div>
    </div>
</div>
<script>
    var showMenu = "0";
    var path = window.location.href;
    var lastPart = path.substr(path.indexOf("admin/") + 6);
    $(document).ready(function() {

        var navigationurl = "{{ url('/admin/navigations')}}";
        //var navigationurl = "{{ url('/admin/navigations')}}";

        $.ajax({
            url: navigationurl,
            method: 'GET',
            data: {},
            success: function(resp) {
                var navigations2 = resp.data;
                var $container = $('#sidebar-menu');
                var $navList = $('<ul></ul>');

                $.each(navigations2, function(index, nav) {
                    var $navItem = $('<li id="nav'+index+'"></li>').addClass('has_sub')/*.addClass('nav-active')*/;
                    var $navLink = $('<a></a>').addClass('waves-effect');
                    if (nav.childmenu.length > 0) {
                        // $navLink.attr('href', '');
                    } else {
                        $navLink.attr('href', '/' + nav.parentmenu.parent_path);
                    }
                    $navItem.attr('onclick', 'addExpandClass(' + index + ')');
                    var $navIcon = $('<i></i>').addClass('fa fa-money');
                    var $navText = $('<span></span>').text(nav.parentmenu.parent_menu);
                    var $navRight = $('<span></span>').addClass('pull-right');
                    if (nav.childmenu.length > 0) {
                        $navRight.append($('<i></i>').addClass('mdi mdi-chevron-right'));
                    }
                    $navLink.append($navIcon).append($navText).append($navRight);

                    var $subMenu = $('<ul></ul>').addClass('list-unstyled');
                    if (nav.childmenu.length > 0) {
                        $.each(nav.childmenu, function(i, sub_nav) {
                            var $subNavItem = $('<li></li>');
                            var $subNavLink = $('<a></a>').attr('href', base_url + '/admin' + '/' + sub_nav.path).text(sub_nav.menu);
                            if (lastPart === sub_nav.path) {
                                $subNavItem.addClass('active');
                                $navItem.addClass('nav-active');
                            }
                            $subNavItem.append($subNavLink);
                            $subMenu.append($subNavItem);
                        });
                    }
                    $navItem.append($navLink).append($subMenu);
                    $navList.append($navItem);
                });
                $container.append($navList);
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    });

    function addExpandClass(element) {
        var $navItem = $('#nav'+element);
        $navItem.attr('onclick', 'addCollapsClass(' + element + ')');
        $navItem.addClass('nav-active');

    }
    function addCollapsClass(element) {
        var $navItem = $('#nav'+element);
        $navItem.attr('onclick', 'addExpandClass(' + element + ')');
        $navItem.removeClass('nav-active');
    }

</script>
