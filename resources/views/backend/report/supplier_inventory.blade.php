@extends('backend.layout.main') @section('content')

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="text-center">Supplier Inventory Report</h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label>Filter by Supplier</label>
                        <select id="supplier_id" class="form-control">
                            <option value="">All Suppliers</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{$supplier->id}}">
                                {{$supplier->name}}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Filter by Product</label>
                        <select id="product_id" class="form-control">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                            <option value="{{$product->id}}">
                                {{$product->name}}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button id="filter" class="btn btn-primary">
                            Load Report
                        </button>
                    </div>



                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-4">
        <table id="report-table"
            class="table table-bordered">

            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Product</th>
                    <th>Total Purchased</th>
                    <th>Remaining Qty</th>
                    <th>Average Cost</th>
                    <th>Stock Value</th>
                </tr>
            </thead>

        </table>

    </div>
</section>

@endsection

@push('scripts')
<!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"> -->

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    let table = $('#report-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{route('report.supplier.inventory.data')}}",
            data: function(d) {
                d.supplier_id = $('#supplier_id').val();
                d.product_id = $('#product_id').val();
            }
        },
        columns: [{
                data: 'supplier'
            },
            {
                data: 'product'
            },
            {
                data: 'total_purchased'
            },
            {
                data: 'remaining_qty'
            },
            {
                data: 'avg_cost'
            },
            {
                data: 'remaining_stock_value'
            },
        ]
    });

    $('#filter').click(function() {
        table.ajax.reload();
    });
</script>
@endpush