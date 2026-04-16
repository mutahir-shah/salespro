@extends('backend.layout.main') @section('content')

<section>
    @if (in_array('employees-add', $all_permission))
    <div class="container-fluid">
        <a href="{{ route('employees.create') }}" class="btn btn-info"><i class="dripicons-plus"></i>
            {{ __('db.Add Employee') }}</a>
    </div>
    @endif

    <div class="container-fluid py-3">

        {{-- ── Header ── --}}
        <div class="report-header">
            <a href="{{ url()->previous() }}" class="btn-back">
                <i class="fa fa-arrow-left"></i> Back
            </a>

            <button class="btn-pay-all" id="btnPayAll" disabled>
                <i class="fa fa-money-bill-wave"></i> Pay Commission
            </button>

            <span class="emp-select-label">Select Employee</span>
            <select class="emp-select" id="employeeSelect">
                <option value="">— Choose —</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="table-responsive">
            <table id="commissionTable" class="table table-bordered table-hover w-100">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>Product Name</th>
                        <th>Product Cost</th>
                        <th>Product Sales Price</th>
                        <th>Quantity Sold</th>
                        <th>Total Sale</th>
                        <th>Profit</th>
                        <th>Sales Man Commission</th>
                        <th>Commission Status</th>
                        <th>Commission Date</th>
                        <th>Sale Date</th>
                        <th>Sale Reference</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
</section>


@endsection

@push('scripts')
<script>
    $(function() {

        // ── Init DataTable ──────────────────────────────────────────────
        var table = $('#commissionTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("reports.employee-commission.data") }}',
                data: function(d) {
                    d.employee_id = $('#employeeSelect').val();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        // No employee selected yet — clear table gracefully
                        table.clear().draw();
                    }
                }
            },
            columns: [
                // checkbox column — not sortable
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        return '<input type="checkbox" class="row-check" data-id="' + row.commission_id + '" data-paid="' + row.is_paid + '">';
                    }
                },
                {
                    data: 'product_name'
                },
                {
                    data: 'product_cost',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'product_sales_price',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'qty_sold'
                },
                {
                    data: 'total_sale',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'profit',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'salesman_commission',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'commission_status'
                },
                {
                    data: 'commission_date'
                },
                {
                    data: 'sale_date'
                },
                {
                    data: 'sale_reference'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                },
            ],
            dom: "<'row'<'col-sm-3'l><'col-sm-5 text-center'B><'col-sm-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [{
                    extend: 'pdf',
                    text: '<i class="fa fa-file-pdf"></i>',
                    className: 'btn btn-sm btn-danger',
                    titleAttr: 'Export PDF'
                },
                {
                    extend: 'csv',
                    text: '<i class="fa fa-file-csv"></i>',
                    className: 'btn btn-sm btn-secondary',
                    titleAttr: 'Export CSV'
                },
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel"></i>',
                    className: 'btn btn-sm btn-success',
                    titleAttr: 'Export Excel'
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i>',
                    className: 'btn btn-sm btn-info',
                    titleAttr: 'Print'
                },
                {
                    text: '<i class="fa fa-eye"></i>',
                    className: 'btn btn-sm btn-purple',
                    titleAttr: 'Column Visibility',
                    extend: 'colvis'
                },
            ],
            language: {
                processing: '<div class="spinner-border text-danger" role="status"></div>',
            },
            order: [
                [10, 'desc']
            ], // default sort: sale date desc
        });

        // ── Reload on employee change ───────────────────────────────────
        $('#employeeSelect').on('change', function() {
            var hasEmployee = $(this).val() !== '';
            $('#btnPayAll').prop('disabled', !hasEmployee);
            table.ajax.reload();
        });

        // ── Check-all checkbox ─────────────────────────────────────────
        $('#checkAll').on('change', function() {
            var checked = this.checked;
            $('#commissionTable tbody .row-check').each(function() {
                if ($(this).data('paid') == 0) { // only unpaid rows
                    $(this).prop('checked', checked);
                }
            });
        });

        // ── Pay Commission (single row via action dropdown) ────────────
        $(document).on('click', '.pay-commission-btn', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            var commissionId = $(this).data('commission-id');

            if (!confirm('Mark this commission as paid?')) return;

            $.post(url, {
                _token: '{{ csrf_token() }}'
            }, function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.message);
                }
            }).fail(function() {
                toastr.error('Something went wrong.');
            });
        });

        // ── Pay All button ─────────────────────────────────────────────
        $('#btnPayAll').on('click', function() {
            var empId = $('#employeeSelect').val();
            if (!empId) return;

            if (!confirm('Pay ALL unpaid commissions for this employee?')) return;

            $.post('{{ route("reports.employee-commission.pay-all") }}', {
                _token: '{{ csrf_token() }}',
                employee_id: empId,
            }, function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.message);
                }
            }).fail(function() {
                toastr.error('Something went wrong.');
            });
        });

    });
</script>
@endpush