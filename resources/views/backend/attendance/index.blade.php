@extends('backend.layout.main')
@section('content')

@push('css')
@if (!config('database.connections.saleprosaas_landlord'))
<link rel="preload" href="<?php echo asset('vendor/jquery-timepicker/jquery.timepicker.min.css'); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link href="<?php echo asset('vendor/jquery-timepicker/jquery.timepicker.min.css'); ?>" rel="stylesheet">
</noscript>
@else
<link rel="preload" href="<?php echo asset('../../vendor/jquery-timepicker/jquery.timepicker.min.css'); ?>" as="style"
    onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link href="<?php echo asset('../../vendor/jquery-timepicker/jquery.timepicker.min.css'); ?>" rel="stylesheet">
</noscript>
@endif
@endpush

<x-success-message key="message" />
<x-error-message key="not_permitted" />

<section>
    <div class="container-fluid mt-4">

        <!-- Buttons -->
        <div class="mb-3 d-flex">
            <button class="btn btn-info mr-2" data-toggle="modal" data-target="#createModal">
                <i class="dripicons-plus"></i> {{ __('db.Add Attendance') }}
            </button>

            <button class="btn btn-secondary mr-2" type="button" data-toggle="collapse" data-target="#importCsv"
                aria-expanded="false" aria-controls="importCsv">
                <i class="fa fa-upload"></i> {{ __('db.Import CSV') }}
            </button>

            <!-- Filter Button -->
            <button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#filterSection"
                aria-expanded="false" aria-controls="filterSection">
                <i class="fa fa-filter"></i> {{ __('db.Filter') }}
            </button>
        </div>

        <!-- Filter Collapsible -->
        <div class="collapse mb-4" id="filterSection">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <form id="attendanceFilterForm">
                        <div class="row">

                            <!-- Date -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.date') }}</label>
                                <input type="date" name="date" class="form-control" id="filterDate">
                            </div>

                            <!-- Employee -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.Employee') }}</label>
                                <select name="employee_id" class="form-control selectpicker" id="filterEmployee" data-live-search="true" title="Select Employee...">
                                    @foreach($lims_employee_list as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.Status') }}</label>
                                <select name="status" class="form-control" id="filterStatus">
                                    <option value="">All</option>
                                    <option value="Present">{{ __('db.Present') }}</option>
                                    <option value="Absent">{{ __('db.Absent') }}</option>
                                    <option value="Leave">{{ __('db.Leave') }}</option>
                                </select>
                            </div>

                            <!-- Warehouse -->
                            <div class="col-md-3 mb-3">
                                <label>{{ __('db.Warehouse') }}</label>
                                <select name="warehouse" class="form-control selectpicker" id="filterWarehouse" data-live-search="true" title="Select Warehouse...">
                                    <option value="">All</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import CSV Collapsible -->
        <div class="collapse mb-4" id="importCsv">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <form action="{{ route('attendances.importDeviceCsv') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>{{ __('db.Attendance Device Date Format') }}</label>
                                <select name="Attendance_Device_date_format" class="form-control">
                                    <option value="">Select</option>
                                    <option value="d/m/Y">dd/mm/yyyy(23/05/2022)</option>
                                    <option value="m/d/Y">mm/dd/yyyy(05/23/2022)</option>
                                    <option value="Y/m/d">yyyy/mm/dd(2022/05/23)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ __('db.Upload File') }}</label>
                                <input type="file" class="form-control-file" name="file" accept=".xlsx, .xls, .csv">
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fa fa-check-square-o"></i> {{ __('db.Save') }}
                                </button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                        </div>
                        <small class="text-muted">
                            * CSV file date format must match selected format.<br>
                            * Do not change first line or column order.<br>
                            * Max file size 2MB.
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="attendance-table" class="table">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{ __('db.date') }}</th>
                    <th>{{ __('db.Employee') }}</th>
                    <th>{{ __('db.CheckIn') }}</th>
                    <th>{{ __('db.CheckOut') }}</th>
                    <th>Total Hours</th>
                    <th>{{ __('db.status') }}</th>
                    <th>{{ __('db.Created By') }}</th>
                    <th class="not-exported">{{ __('db.action') }}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

</section>

<div id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Attendance')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                <form action="{{ route('attendance.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{__('db.Employee')}} *</label>
                            <select class="form-control selectpicker" name="employee_id[]" required data-live-search="true" data-live-search-style="begins" title="Select Employee..." multiple>
                                @foreach($lims_employee_list as $employee)
                                <option value="{{$employee->id}}">{{$employee->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.date')}} *</label>
                            <input type="text" name="date" class="form-control date" value="{{date($general_setting->date_format)}}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.CheckIn')}} *</label>
                            <input type="text" id="checkin" name="checkin" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->checkin}}@endif" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{__('db.CheckOut')}} *</label>
                            <input type="text" id="checkout" name="checkout" class="form-control" value="@if($lims_hrm_setting_data){{$lims_hrm_setting_data->checkout}}@endif" required>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>{{__('db.Note')}}</label>
                            <textarea name="note" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Edit Attendance Modal -->
<div id="editModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('db.Edit Attendance') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editAttendanceForm" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.Employee') }}</label>
                            <input type="text" id="edit_employee_name" class="form-control" disabled>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ __('db.date') }}</label>
                            <input type="text" id="edit_date" class="form-control" disabled>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ __('db.CheckIn') }}</label>
                            <input type="text" id="edit_checkin" class="form-control"
                                disabled
                                style="background:#f5f5f5; cursor:not-allowed;">
                            <small class="text-muted">Check-in cannot be changed.</small>
                        </div>

                        <div class="col-md-6 form-group">
                            <label>{{ __('db.CheckOut') }} *</label>
                            <input type="text" id="edit_checkout" name="checkout"
                                class="form-control" required
                                placeholder="Enter checkout time">
                        </div>

                        <div class="col-md-12 form-group">
                            <label>{{ __('db.Note') }}</label>
                            <textarea name="note" id="edit_note" rows="3" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            {{ __('db.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@if (!config('database.connections.saleprosaas_landlord'))
<script type="text/javascript" src="<?php echo asset('vendor/jquery/jquery.timepicker.min.js'); ?>"></script>
@else
<script type="text/javascript" src="<?php echo asset('../../vendor/jquery/jquery.timepicker.min.js'); ?>"></script>
@endif
<script type="text/javascript">
    $("ul#hrm").siblings('a').attr('aria-expanded', 'true');
    $("ul#hrm").addClass("show");
    $("ul#hrm #attendance-menu").addClass("active");

    function confirmDelete() {
        return confirm("Are you sure want to delete?");
    }

    var date = $('.date');
    date.datepicker({
        format: "dd-mm-yyyy",
        autoclose: true,
        todayHighlight: true
    });

    $('#checkin, #checkout').timepicker({
        'step': 15,
    });

    // Initialize timepicker on edit checkout field
    $('#edit_checkout').timepicker({
        'step': 15
    });

    // Open edit modal
    $(document).on('click', '.edit-attendance-btn', function() {
        var id = $(this).data('id');
        var date = $(this).data('date');
        var employee = $(this).data('employee');
        var checkin = $(this).data('checkin');
        var checkout = $(this).data('checkout');

        // Set form action dynamically
        $('#editAttendanceForm').attr('action', '/attendance/' + id);

        // Populate fields
        $('#edit_date').val(date);
        $('#edit_employee_name').val(employee);
        $('#edit_checkin').val(checkin);
        $('#edit_checkout').val(checkout || '');
        $('#edit_note').val('');

        $('#editModal').modal('show');
    });

    // After edit form submit reload datatable
    $('#editAttendanceForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.ajax({
            type: 'POST', // Laravel tunnels PUT via _method
            url: url,
            data: data,
            success: function(response) {
                $('#editModal').modal('hide');
                table.ajax.reload();
            },
            error: function(xhr) {
                alert('Error updating attendance. Please try again.');
                console.error(xhr.responseText);
            }
        });
    });


    var table = $('#attendance-table').DataTable({
        processing: true,
        serverSide: true,
        order: [
            [1, 'desc']
        ],
        ajax: {
            url: '{{ route("attendance.datatable") }}',
            type: 'GET',
            data: function(d) {
                d.date = $('#filterDate').val();
                d.employee_id = $('#filterEmployee').val();
                d.status = $('#filterStatus').val();
                d.warehouse = $('#filterWarehouse').val();
            }
        },
        columns: [{
                data: null,
                orderable: false,
                className: 'not-exported'
            },
            {
                data: 'date_formatted',
                name: 'date'
            },
            {
                data: 'employee_name',
                name: 'employee_name'
            },
            {
                data: 'checkin',
                name: 'checkin',
                orderable: false
            },
            {
                data: 'checkout',
                name: 'checkout',
                orderable: false,
                render: function(data) {
                    if (!data) {
                        return '<span class="badge badge-warning">Pending</span>';
                    }
                    return data;
                }
            },
            {
                data: 'total_hours',
                name: 'total_hours',
                orderable: false
            },
            {
                data: 'status',
                name: 'status',
                render: function(data) {
                    return data ?
                        '<span class="badge badge-success">{{ __("db.Present") }}</span>' :
                        '<span class="badge badge-danger">{{ __("db.Late") }}</span>';
                }
            },
            {
                data: 'user_name',
                name: 'user_name'
            },
            {
                data: null,
                orderable: false,
                className: 'not-exported',
                render: function(data) {
                    var editBtn = `<button type="button" class="btn btn-sm btn-info edit-attendance-btn"
                data-id="${data.id}"
                data-date="${data.date}"
                data-employee="${data.employee_name}"
                data-checkin="${data.checkin}"
                data-checkout="${data.checkout ?? ''}"
                data-note="">
                <i class="dripicons-pencil"></i>
            </button> `;

                    var deleteBtn = `<form action="/attendance/delete/${data.date}/${data.employee_id}"
                method="post" style="display:inline;">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" class="btn btn-sm btn-danger"
                    onclick="return confirmDelete()">
                    <i class="dripicons-trash"></i>
                </button>
            </form>`;

                    return `<div class="btn-group">${editBtn} ${deleteBtn}</div>`;
                }
            }
        ],
        'language': {
            'lengthMenu': '_MENU_ {{__("db.records per page")}}',
            "info": '<small>{{__("db.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search": '{{__("db.Search")}}',
            'paginate': {
                'previous': '<i class="dripicons-chevron-left"></i>',
                'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        columnDefs: [{
                orderable: false,
                targets: [0, 6]
            },
            {
                render: function(data, type, row, meta) {
                    if (type === 'display') {
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }
                    return data;
                },
                checkboxes: {
                    selectRow: true,
                    selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                targets: [0]
            }
        ],
        select: {
            style: 'multi',
            selector: 'td:first-child'
        },
        lengthMenu: [
            [10, 25, 50, -1],
            [10, 25, 50, 'All']
        ],
        dom: '<"row"lfB>rtip',
        buttons: [{
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                }
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function(e, dt, node, config) {
                    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
                    if (user_verified != '1') {
                        alert('This feature is disabled for demo!');
                        return;
                    }

                    var rows_selected = dt.column(0).checkboxes.selected();
                    var attendance_selected = [];
                    $.each(rows_selected, function(index, rowId) {
                        var rowData = dt.row(rowId).data();
                        attendance_selected.push([rowData.date, rowData.employee_id]);
                    });

                    if (!attendance_selected.length) {
                        alert('Nothing is selected!');
                        return;
                    }

                    if (confirm('Are you sure you want to delete?')) {
                        $.ajax({
                            type: 'POST',
                            url: 'attendance/deletebyselection',
                            data: {
                                attendanceSelectedArray: attendance_selected,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(data) {
                                alert(data);
                                table.ajax.reload();
                            }
                        });
                    }
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            }
        ],
        // Set row data attributes for delete-by-selection to still work
        createdRow: function(row, data) {
            $(row).attr('data-date', data.date);
            $(row).attr('data-employee_id', data.employee_id);
            $(row).attr('data-warehouse', data.warehouse_id ?? '');
        }
    });

    // Filters trigger server reload
    $('#filterDate, #filterEmployee, #filterStatus, #filterWarehouse').on('change', function() {
        table.ajax.reload();
    });
    // Attendance Filter
    $('#filterDate, #filterEmployee, #filterStatus, #filterWarehouse').on('change', function() {
        var date = $('#filterDate').val();
        var employee_id = $('#filterEmployee').val();
        var status = $('#filterStatus').val();
        var warehouse = $('#filterWarehouse').val();

        table.rows().every(function() {
            var data = this.node();
            var show = true;

            if (date) show = show && ($(data).data('date') === date);
            if (employee_id && employee_id.length > 0) show = show && (employee_id.includes($(data).data('employee_id').toString()));

            if (status) {
                var statusText = $(data).find('td:eq(4)').text().trim();
                show = show && ((status === 'Present' && statusText === '{{ __("db.Present") }}') ||
                    (status === 'Absent' && statusText === '{{ __("db.Absent") }}') ||
                    (status === 'Leave' && statusText === '{{ __("db.Leave") }}'));
            }

            if (warehouse) show = show && ($(data).data('warehouse').toString() === warehouse);

            if (show) $(data).show();
            else $(data).hide();
        });
    });
</script>
@endpush