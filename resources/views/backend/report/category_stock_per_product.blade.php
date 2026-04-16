@extends('backend.layout.main') @section('content')

<section class="forms">
 
    <div class="table-responsive mb-4">
        <table id="categoryStockTable" class="table table-hover">
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
        </table>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $(function() {

        let table = $('#categoryStockTable').DataTable({processing: true,serverSide: true,
            ajax: {
                url: "{{ route('report.category.stock.datatable') }}",
                data: function(d) {
                    d.warehouse_id = $('#warehouse_id').val();
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
            ]
        });

    });
</script>
@endpush