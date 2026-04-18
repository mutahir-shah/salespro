@extends('backend.layout.main')

@section('content')

<section class="forms">
    <div class="container-fluid">
        <div class="card">

            <div class="card-header mt-2">
                <h3 class="text-center">Category Stock Report</h3>
            </div>

            {{-- FILTERS --}}
            <div class="row mb-3 product-report-filter">

                {{-- DATE FILTER --}}
                <div class="col-md-3 offset-md-2 mt-3">
                    <div class="form-group top-fields">
                        <label><strong>Choose Your Date</strong></label>

                        <div class="input-group">
                            <input type="text"
                                class="daterangepicker-field form-control"
                                id="date_range" />

                            <input type="hidden" id="starting_date">
                            <input type="hidden" id="ending_date">
                        </div>
                    </div>
                </div>

                {{-- WAREHOUSE --}}
                <div class="col-md-3 mt-3">
                    <div class="form-group top-fields">
                        <label><strong>Choose Warehouse</strong></label>

                        <select id="warehouse_id"
                            class="selectpicker form-control"
                            data-live-search="true">

                            <option value="">All Warehouse</option>

                            @foreach($lims_warehouse_list as $warehouse)
                            <option value="{{$warehouse->id}}">
                                {{$warehouse->name}}
                            </option>
                            @endforeach

                        </select>
                    </div>
                </div>

                {{-- CATEGORY --}}
                <div class="col-md-3 mt-3">
                    <div class="form-group top-fields">
                        <label><strong>Category</strong></label>

                        <select id="category_id"
                            class="selectpicker form-control"
                            data-live-search="true">

                            <option value="">All Category</option>

                            @foreach($categories_list as $category)
                            <option value="{{$category->id}}">
                                {{$category->name}}
                            </option>
                            @endforeach

                        </select>
                    </div>
                </div>

                {{-- SUBMIT --}}
                <div class="col-md-12 text-center mt-3">
                    <button id="filterBtn" class="btn btn-primary">
                        Submit
                    </button>
                </div>

            </div>

        </div>
    </div>

    {{-- TABLE --}}
    <div class="table-responsive">
        <table id="categoryStockTable"
            class="table table-hover"
            style="width:100%">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category Name</th>
                    <th>Cost</th>
                    <th>Sales Price</th>
                    <th>Remaining Quantity</th>
                    <th>Total Cost Price</th>
                    <th>Total Sales Price</th>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th>Total</th>
                    <th></th>
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

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Date Range Picker
    |--------------------------------------------------------------------------
    */

    $('.daterangepicker-field').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY'
        }
    }, function(start, end) {
        $('#starting_date').val(start.format('DD/MM/YYYY'));
        $('#ending_date').val(end.format('DD/MM/YYYY'));
    });


    $('.selectpicker').selectpicker('refresh');


    /*
    |--------------------------------------------------------------------------
    | DATATABLE
    |--------------------------------------------------------------------------
    */

    let table = $('#categoryStockTable').DataTable({

        processing: true,
        serverSide: true,

        ajax: {
            url: "{{ route('report.category.stock.datatable') }}",
            type: "POST",
            data: function(d) {

                d.warehouse_id = $('#warehouse_id').val();
                d.category_id = $('#category_id').val();
                d.starting_date = $('#starting_date').val();
                d.ending_date = $('#ending_date').val();
            }
        },

        columns: [{
                data: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'product_name'
            },
            {
                data: 'category_name'
            },
            {
                data: 'cost'
            },
            {
                data: 'price'
            },
            {
                data: 'remaining_quantity'
            },
            {
                data: 'total_cost_price'
            },
            {
                data: 'total_sales_price'
            },
        ],

        order: [
            [5, 'desc']
        ],

        dom: '<"row"lfB>rtip',

        buttons: [
            'excel',
            'csv',
            'pdf',
            'print',
            'colvis'
        ],

        drawCallback: function() {

            let api = this.api();

            $(api.column(5).footer()).html(
                api.column(5, {
                    page: 'current'
                }).data().sum().toFixed(2)
            );

            $(api.column(6).footer()).html(
                api.column(6, {
                    page: 'current'
                }).data().sum().toFixed(2)
            );

            $(api.column(7).footer()).html(
                api.column(7, {
                    page: 'current'
                }).data().sum().toFixed(2)
            );
        }

    });


    /*
    |--------------------------------------------------------------------------
    | FILTER RELOAD
    |--------------------------------------------------------------------------
    */

    $('#filterBtn').on('click', function() {
        table.ajax.reload();
    });
</script>

@endpush