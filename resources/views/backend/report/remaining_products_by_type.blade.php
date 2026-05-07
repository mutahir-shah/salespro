@extends('backend.layout.main') @section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{trans('Products Report Remaining Packing Types')}}</h3>
            </div>
            <form id="report-form" action="{{ route('report.remainingproductsbytype') }}" method="GET">
                <div class="row mb-3">
                    <div class="col-md-2 mt-4"></div>
                    <div class="col-md-4 mt-4">
                        <div class="form-group row">
                            <label class="d-tc mt-2"><strong>{{trans('Choose Warehouse')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <input type="hidden" name="warehouse_id_hidden" value="{{$warehouse_id}}" />
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{trans('All Warehouse')}}</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                    <option value="{{$warehouse->id}}" {{ $warehouse_id ==$warehouse->id ?  'selected': '' }}>{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-4">
                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">{{trans('Search')}}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table id="report-table" class="table table-hover">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('Product Packing Type')}}</th>
                    <th>{{trans('Total Remaining Products')}}</th>
                    <th>{{trans('Total Cost Value')}}</th>
                    <th>{{trans('Total Price Value')}}</th>
                    <th>{{trans('Potential Profit')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $key => $category)
                <tr>
                    <td>{{$key}}</td>
                    <td>{{ $category->packing_type }}</td>
                    <td>{{ $category->total_remaining_products }}</td>
                    <td>{{ $category->total_cost_value }}</td>
                    <td>{{ round($category->total_price_value, 0) }}</td>
                    <td>{{ round($category->total_price_value - $category->total_cost_value, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('Total')}}</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $('#report-table').DataTable({
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{trans("records per page")}}',
            "info": '<small>{{trans("Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search": '{{trans("Search")}}',
            'paginate': {
                'previous': '<i class="dripicons-chevron-left"></i>',
                'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        'columnDefs': [{
                "orderable": false,
                'targets': 0
            },
            {
                'render': function(data, type, row, meta) {
                    if (type === 'display') {
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }
                    return data;
                },
                'checkboxes': {
                    'selectRow': true,
                    'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': {
            style: 'multi',
            selector: 'td:first-child'
        },
        'lengthMenu': [
            [10, 25, 50, -1],
            [10, 25, 50, "All"]
        ],
        'pageLength': 100,
        dom: '<"row"lfB>rtip',
        buttons: [
            // ... existing buttons ...
        ],
        footerCallback: function(row, data, start, end, display) {
            var api = this.api();
            // Remove the formatting to get integer data
            var intVal = function(i) {
                if (typeof i === 'string') {
                    return parseFloat(i.replace(/[\$,]/g, '')) || 0;
                }
                return typeof i === 'number' ? i : 0;
            };
            // Total remaining products
            var remainingTotal = api
                .column(2)
                .data()
                .reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Total cost value
            var costTotal = api
                .column(3)
                .data()
                .reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Total price value
            var priceTotal = api
                .column(4)
                .data()
                .reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Total profit
            var profitTotal = api
                .column(5)
                .data()
                .reduce(function(a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Update footer cells
            $(api.column(2).footer()).html(remainingTotal);
            $(api.column(3).footer()).html(costTotal);
            $(api.column(4).footer()).html(priceTotal);
            $(api.column(5).footer()).html(profitTotal);
        }
    });
</script>
@endpush