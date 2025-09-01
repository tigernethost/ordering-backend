@php
    $user = backpack_auth()->user();
    $isAdmin = $user->hasRole('Administrator');
    $isManager = $user->hasRole('Branch Manager');
@endphp

{{-- Dashboard --}}
@if ($isAdmin || $isManager)
    <li class="nav-item">
        <a class="nav-link" href="{{ backpack_url('dashboard') }}">
            <i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}
        </a>
    </li>
@endif

{{-- Human Resources --}}
@php
    $canAccessHR = $user->can('read employee') || 
                   $user->can('read employee-biometric') || 
                   $user->can('read attendance-log');
@endphp
@if ($isAdmin && $canAccessHR)
    <x-backpack::menu-dropdown title="Human Resources" icon="la la-group">
        @if($user->can('read employee'))
            <x-backpack::menu-dropdown-item title="Employees" icon="la la-user" :link="backpack_url('employee')" />
        @endif
        @if(config('biometrics.enabled') && $user->can('read employee-biometric'))
            <x-backpack::menu-dropdown-item title="Employee Biometrics" icon="la la-fingerprint" :link="backpack_url('employee-biometric')" />
        @endif
        @if(config('biometrics.enabled') && $user->can('read attendance-log'))
            <x-backpack::menu-dropdown-item title="Attendance Logs" icon="la la-clock" :link="backpack_url('attendance-log')" />
        @endif
        @if(config('biometrics.enabled'))
            <x-backpack::menu-dropdown-item title="Human Resource Dashboard" icon="la la-question" :link="backpack_url('human-resource')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

{{-- Administration --}}
@php
    $canAccessAdmin = $user->can('read department') || 
                      $user->can('read device') || 
                      $user->can('read branch') || 
                      $user->can('read branch-order-intervals');
@endphp
@if ($isAdmin && $canAccessAdmin)
    <x-backpack::menu-dropdown title="Administration" icon="la la-cogs">
        @if($user->can('read department'))
            <x-backpack::menu-dropdown-item title="Departments" icon="la la-sitemap" :link="backpack_url('department')" />
        @endif
        @if(config('biometrics.enabled') && $user->can('read device'))
            <x-backpack::menu-dropdown-item title="Devices" icon="la la-desktop" :link="backpack_url('device')" />
        @endif
        @if($user->can('read branch'))
            <x-backpack::menu-dropdown-item title="Branches" icon="la la-building" :link="backpack_url('branch')" />
        @endif
        @if($user->can('read branch-order-intervals'))
            <x-backpack::menu-dropdown-item title="Branch Order Intervals" icon="la la-clock" :link="backpack_url('branch-order-intervals')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

{{-- System --}}
@php
    $canAccessSystem = $user->can('read user') || 
                       $user->can('read role') || 
                       $user->can('read permission');
@endphp
@if ($isAdmin && $canAccessSystem)
    <x-backpack::menu-dropdown title="System" icon="la la-gear">
        @if($user->can('read user'))
            <x-backpack::menu-dropdown-item title="Users" icon="la la-user" :link="backpack_url('user')" />
        @endif
        @if($user->can('read role'))
            <x-backpack::menu-dropdown-item title="Roles" icon="la la-group" :link="backpack_url('role')" />
        @endif
        @if($user->can('read permission'))
            <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

{{-- Products --}}
@php
    $canAccessProducts = $user->can('read product') || 
                         $user->can('read category') || 
                         $user->can('read special-menu');
@endphp
@if ($isAdmin && $canAccessProducts)
    <x-backpack::menu-dropdown title="Products" icon="la la-box">
        @if($user->can('read product'))
            <x-backpack::menu-dropdown-item title="Products" icon="la la-box" :link="backpack_url('product')" />
        @endif
        @if($user->can('read category'))
            <x-backpack::menu-dropdown-item title="Categories" icon="la la-tags" :link="backpack_url('category')" />
        @endif
        @if($user->can('read special-menu'))
            <x-backpack::menu-dropdown-item title="Special Menus" icon="la la-list" :link="backpack_url('special-menu')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

{{-- Orders (Branch Manager only) --}}
@php
    $canAccessOrders = $user->can('read order') || 
                       $user->can('read customer') || 
                       $user->can('read order-report') || 
                       $user->can('read sales-report');
@endphp
@if (($isAdmin || $isManager) && $canAccessOrders)
    <x-backpack::menu-dropdown title="Orders" icon="la la-shopping-cart">
        @if($user->can('read order'))
            <x-backpack::menu-dropdown-item title="Orders" icon="la la-receipt" :link="backpack_url('order')" />
        @endif
        @if($user->can('read customer'))
            <x-backpack::menu-dropdown-item title="Customers" icon="la la-users" :link="backpack_url('customer')" />
        @endif
        @if($user->can('read order-report'))
            <x-backpack::menu-dropdown-item title="Order Reports" icon="la la-file-invoice" :link="backpack_url('order-report')" />
        @endif
        @if($user->can('read sales-report'))
            <x-backpack::menu-dropdown-item title="Sales Reports" icon="la la-chart-line" :link="backpack_url('sales-report')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

{{-- Payment --}}
@php
    $canAccessPayment = $user->can('read payment-category') || 
                        $user->can('read payment-method');
@endphp
@if (($isAdmin || $isManager) && $canAccessOrders)
    <x-backpack::menu-dropdown title="Payment" icon="la la-credit-card">
        @if($user->can('read payment-category'))
            <x-backpack::menu-dropdown-item title="Payment Categories" icon="la la-list" :link="backpack_url('payment-category')" />
        @endif
        @if($user->can('read payment-method'))
            <x-backpack::menu-dropdown-item title="Payment Methods" icon="la la-wallet" :link="backpack_url('payment-method')" />
        @endif
    </x-backpack::menu-dropdown>
@endif

{{-- Payroll (Admin only) --}}
@if ($isAdmin && config('biometrics.enabled'))
    <x-backpack::menu-item 
        title="Payroll" 
        icon="la la-key"
        :link="env('HR_PAYROLL_URL') . '/auth/login?token=' . $user->createToken('Payroll')->accessToken . '&source=makimura'"
        target="_blank" />
@endif
