<?php

namespace App\Services\Menu;

use Illuminate\Support\Facades\Auth;
use Spatie\Menu\Laravel\Link;
use Spatie\Menu\Laravel\Menu;

/**
 * Class AdminMenu
 *
 * 
 */
class AdminMenu
{
    public function register()
    {
        Menu::macro('admin', function () {
            /*getting user permissions*/

          //  $userPermissoins = Auth::user()->getPermissions();

            return Menu::new()

                ->addClass('page-sidebar-menu')
                ->setAttribute('data-keep-expanded', 'false')
                ->setAttribute('data-auto-scroll', 'true')
                ->setAttribute('data-slide-speed', '200')
                ->html('<div class="sidebar-toggler hidden-phone"></div>')

                ->add(Link::toRoute(
                    'dashboard.index',
                    '<i class="fa fa-home"></i> <span class="title">Dashboard</span>'
                )
                ->addParentClass('start'))

//                ->submenuIf(can_access_route(['role.index','role.create'],$userPermissoins),'
//                    <a href="javascript:;">
//                        <i class="fa fa-tasks"></i>
//                        <span class="title">Roles </span>
//                        <span class="arrow open"></span>
//                        <!--<span class="selected"></span>-->
//                    </a>
//                    ',
//                    Menu::new()
//                        ->addClass('sub-menu')
//                        ->addIf(can_access_route('role.index',$userPermissoins),
//                            Link::toRoute('role.index', '<span class="title">Role List</span>'))
//                        ->addIf(can_access_route('role.create',$userPermissoins),
//                            Link::toRoute('role.create', '<span class="title">Add Role</span>'))
//                )
//                ->submenuIf(can_access_route(['sub-admin.index', 'sub-admin.create'], $userPermissoins), '
//                    <a href="javascript:;">
//                        <i class="fa fa-users"></i>
//                        <span class="title">Sub Admins </span>
//                        <span class="arrow open"></span>
//                        <!--<span class="selected"></span>-->
//                    </a>
//                    ',
//                    Menu::new()
//                        ->addClass('sub-menu')
//                        ->addIf(can_access_route('sub-admin.index', $userPermissoins),
//                            Link::toRoute('sub-admin.index', '<span class="title">Sub Admins List</span>'))
//                        ->addIf(can_access_route('sub-admin.create', $userPermissoins),
//                            Link::toRoute('sub-admin.create', '<span class="title">Add Sub Admin</span>'))
//                )

//                ->submenuIf(can_access_route(['customer.index', 'customer.create'],$userPermissoins),'
//                    <a href="javascript:;">
//                        <i class="fa fa fa-user"></i>
//                        <span class="title">Customer </span>
//                        <span class="arrow open"></span>
//                        <!--<span class="selected"></span>-->
//                    </a>
//                    ',
//                    Menu::new()
//                        ->addClass('sub-menu')
//                        ->addIf(can_access_route('customer.index',$userPermissoins),
//                            Link::toRoute('customer.index', '<span class="title">Customers List</span>'))
//                       /* ->addIf(can_access_route('customer.create',$userPermissoins),
//                            Link::toRoute('customer.create', '<span class="title">Add Customer</span>'))*/
//                )



//                ->submenuIf(can_access_route(['banner.index','banner.create'],$userPermissoins),'
//                    <a href="javascript:;">
//                        <i class="fa fa-picture-o"></i>
//                        <span class="title">Banner </span>
//                        <span class="arrow open"></span>
//                        <!--<span class="selected"></span>-->
//                    </a>
//                    ',
//                    Menu::new()
//                        ->addClass('sub-menu')
//                        ->addIf(can_access_route('banner.index',$userPermissoins),
//                            Link::toRoute('banner.index', '<span class="title">Banner List</span>'))
//                        ->addIf(can_access_route('banner.create',$userPermissoins),
//                            Link::toRoute('banner.create', '<span class="title">Banner Add</span>'))
//                )
//
//                ->submenuIf(can_access_route(['cms.index'],$userPermissoins),'
//                    <a href="javascript:;">
//                        <i class="fa fa-file"></i>
//                        <span class="title">CMS</span>
//                        <span class="arrow open"></span>
//                        <!--<span class="selected"></span>-->
//                    </a>
//                    ',
//                    Menu::new()
//                        ->addClass('sub-menu')
//                        ->addIf(can_access_route('cms.index',$userPermissoins),
//                            Link::toRoute('cms.index', '<span class="title">CMS List</span>'))
//                )

      /*        ->submenuIf(can_access_route(['customer-form.index'],$userPermissoins),'
                    <a href="javascript:;">
                        <i class="fa fa-bell"></i>
                        <span class="title">Notification </span>
                        <span class="arrow open"></span>
                        <!--<span class="selected"></span>-->
                    </a>
                    ',
                    Menu::new()
                        ->addClass('sub-menu')
                        ->addIf(can_access_route('customer-form.index',$userPermissoins),
                            Link::toRoute('customer-form.index', '<span class="title">Notification</span>'))
                )*/
//
//                  ->submenuIf(can_access_route(['contact-us.index'], $userPermissoins), '
//                                <a href="javascript:;">
//                                    <i class="fa fa-phone"></i>
//                                    <span class="title">Contact Us</span>
//                                    <span class="arrow open"></span>
//                                    <!--<span class="selected"></span>-->
//                                </a>
//                                ',
//                      Menu::new()
//                          ->addClass('sub-menu')
//                          ->addIf(can_access_route('contact-us.index', $userPermissoins),
//                              Link::toRoute('contact-us.index', '<span class="title">Contacts List</span>'))
//
//                  )
//                ->addIf(can_access_route('users.change-password', $userPermissoins),(Link::toRoute(
//                    'users.change-password',
//                    '<i class="fa fa-lock"></i> <span class="title">Change Password</span>'
//                )))





                ->add(Link::toRoute(
                    'logout',
                    '<i class="fa fa-sign-out"></i> <span class="title">Logout</span>'
                )
                    ->setAttribute('id', 'leftnav-logout-link'))
                ->setActiveFromRequest();
        });
    }
}
