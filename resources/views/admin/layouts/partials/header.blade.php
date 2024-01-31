<!-- BEGIN TOP NAVIGATION BAR -->
<div class="header-inner">
    <!-- BEGIN LOGO -->
    <a class="navbar-brand" href="{{ route('dashboard.index') }}">

       {{-- @if (isset($siteSettings->site_logo) && adminHasAssets($siteSettings->site_logo))
            <img src="{!! asset(uploadsDir().$siteSettings->site_logo) !!}" alt="Web Builder" class="img-responsive" />
        @else--}}


        <img src="{!! asset('assets/admin/logo.png') !!}" alt="Web Builder" class="img-responsive main-logo" />
        {{--@endif--}}

    </a>
    <!-- END LOGO -->
    <!-- BEGIN RESPONSIVE MENU TOGGLER -->
    <a href="javascript:;" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <img src="{{ asset('assets/admin/img/menu-toggler.png') }}" alt=""/>
    </a>
    <!-- END RESPONSIVE MENU TOGGLER -->
    <!-- BEGIN TOP NAVIGATION MENU -->
    <ul class="nav navbar-nav pull-right">
        <!-- BEGIN NOTIFICATION DROPDOWN -->
            {{--<li class="dropdown" id="header_notification_bar">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                    <i class="fa fa-warning"></i>
                    <span class="badge">
                        0
                    </span>
                </a>
                <ul class="dropdown-menu extended notification">
                    <!-- <li>
                        <p>
                             You have 14 new notifications
                        </p>
                    </li> -->
                    <li>
                        <ul class="dropdown-menu-list scroller" style="height: 250px;">
                            <li>
                            <li>
                                <a href="#">
                                     Expired Offers
                                    <span class="label label-danger pull-right">0</span>
                                    <!-- <span class="time">
                                         Just now
                                    </span> -->
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>
            </li>--}}
            <!-- END NOTIFICATION DROPDOWN -->



        <!-- BEGIN USER LOGIN DROPDOWN -->
        <li class="dropdown user">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                <img alt="admin avatar" width="29px" height="29px" src=" {{ Auth::user()->profile_picture}}"/>
                <span class="username">
                        {{ Auth::user()->full_name}}
                    </span>
                <i class="fa fa-angle-down"></i>
            </a>
            <ul class="dropdown-menu">
                @if(can_access_route('users.edit-profile',$userPermissoins))
                <li>
                    {{--<a href="{{ route('backend/users/'.Auth::user()->id.'/edit') }}">--}}
                    <a href="{{ route('users.edit-profile') }}">
                        <i class="fa fa-user"></i> Edit Profile
                    </a>
                </li>
                @endif
                    @if(can_access_route('users.change-password',$userPermissoins))
                <li>
                    <a href="{{ route('users.change-password') }}">
                        <i class="fa fa-lock"></i> Change Password
                    </a>
                </li>
                    @endif
                <li>
                    <a href="{{ route('logout') }}" id="header-logout-link">
                        <i class="fa fa-sign-out"></i> {{ __('Logout') }}
                    </a>
                </li>
            </ul>
        </li>
        <!-- END USER LOGIN DROPDOWN -->
    </ul>
    <!-- END TOP NAVIGATION MENU -->
</div>
<!-- END TOP NAVIGATION BAR -->
