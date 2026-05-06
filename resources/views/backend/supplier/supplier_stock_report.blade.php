@extends('backend.layout.main')

@section('content')
<section>
    <div class="container-fluid">
        <h4 class="mb-3">
            {{ __('Remaining Stock for Supplier:') }} <strong>{{ $supplier->name }}</strong>
        </h4>
        <div class="table-responsive">
            <table id="supplier-stock-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Remaining Qty') }}</th>
                        <th>{{ __('Unit Cost') }}</th>
                        <th>{{ __('Remaining Cost Value') }}</th>
                        <th>{{ __('Unit Price') }}</th>
                        <th>{{ __('Remaining Price Value') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stock_list as $key => $item)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td><span class="badge badge-info">{{ intval($item->remaining_qty) }}</span></td>
                        <td>{{ intval($item->weighted_unit_cost) }}</td>
                        <td>{{ intval($item->remaining_cost_value) }}</td>
                        <td>{{ intval($item->unit_price) }}</td>
                        <td>{{ intval($item->remaining_price_value) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right">{{ __('Total') }}</th>
                        <th id="footer-remaining">0</th>
                        <th id="footer-unit-cost"></th>
                        <th id="footer-unit-cost-value">0</th>
                        <th id="footer-unit-price"></th>
                        <th id="footer-unit-price-value">0</th>
                    </tr>
                </tfoot>
            </table>

        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#supplier-stock-table').DataTable({
            "order": [],
            "pageLength": 50, // ← this sets the default to 50 rows per page
            "footerCallback": function(row, data, start, end, display) {
                var api = this.api();

                function intVal(i) {
                    return typeof i === 'string' ?
                        parseInt(i.replace(/[\$,]/g, '')) || 0 :
                        typeof i === 'number' ?
                        i : 0;
                }

                let totalCost = 0,
                totalPrice = 0,
                    totalRemaining = 0,
                    totalCostValue = 0,
                    totalPriceValue = 0;  

                api.column(2).data().each(function(value) {
                    totalRemaining += intVal($(value).text() || value);
                });

                api.column(3).data().each(function(value) {
                    totalCost += intVal(value);
                });
                api.column(4).data().each(function(value) {
                    totalCostValue += intVal(value);
                });
                api.column(5).data().each(function(value) {
                    totalPrice += intVal(value);
                });
                api.column(6).data().each(function(value) {
                    totalPriceValue += intVal(value);
                });
                // Update footer cells 
                $('#footer-remaining').html(totalRemaining);
                $('#footer-unit-cost').html(totalCost);                
                $('#footer-unit-cost-value').html(totalCostValue);
                $('#footer-unit-price').html(totalPrice);
                $('#footer-unit-price-value').html(totalPriceValue);
            },
            'language': {
                'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
                "info": '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
                "search": '{{trans("file.Search")}}',
                'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
                }
            },
            dom: '<"row"lfB>rtip',
            buttons: [{
                    extend: 'pdf',
                    text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="dripicons-document-new"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i title="print" class="fa fa-print"></i>',
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });
    });
</script>
@endpush