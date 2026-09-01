<link rel="stylesheet" href="{{ url('backend/assets/plugins/DataTables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ url('backend/assets/plugins/DataTables/responsive.dataTables.min.css') }}">
<link rel="stylesheet" href="{{ url('backend/assets/plugins/tippyjs/tippy.css') }}">
<link rel="stylesheet" href="{{ url('backend/assets/css/custome-datatable.css') }}">
<link rel="stylesheet" href="{{ url('backend/assets/css/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ url('backend/assets/css/select2.min.css') }}">
<style>
    table.dataTable.nowrap th[title="Action"] {
        text-align: end !important;
    }

    .dataTables_wrapper .table-responsive {
        overflow: visible !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before,
    table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before {
        border: none !important;
        margin-left: 5px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Column Visibility button css */

    div.dt-button-collection {
        right: 50px !important;
        background: var(--default-body-bg-color) !important;
    }

    li.dt-button.buttons-columnVisibility,
    li.dt-button.buttons-columnVisibility.dt-button-active-a {
        background: transparent !important;

    }

    /*
    [data-bs-theme="dark"] div.dt-button-collection .dropdown-menu {
        background: #414141 !important;
    } */

    /* ড্রপডাউনের প্রথম বাটন (ইনডেক্স) যদি ফাঁকা থাকে তবে সেখানে "Select" লেখা দেখাবে */
    div.dt-button-collection button.dt-button[data-cv-idx="0"] {
        position: relative;
    }

    div.dt-button-collection button.dt-button[data-cv-idx="0"]:empty::after {
        content: " Select All";
        font-size: 14px;
        color: #444;
    }

    /* a.dropdown-item{
        padding: 0px !important;
    } */

    /* ul.action_button_set li{
        padding: 5px 12px !important;
    } */

    .tippy-box {
        border-radius: .5rem;
    }

    .tippy-content {
        padding: 0;
    }

    .tippy-box .dropdown-menu {
        display: block !important;
        position: static !important;
        margin: 0;
        border: 0;
        box-shadow: none;
        min-width: 180px;
    }

    .tippy-box .dropdown-item {
        padding: .55rem .9rem;
        font-size: .875rem;
    }

    .tippy-box .dropdown-divider {
        margin: .25rem 0;
    }
</style>
