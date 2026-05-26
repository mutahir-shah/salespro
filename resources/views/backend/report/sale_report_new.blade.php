@extends('backend.layout.main')

@section('content')

<x-error-message key="not_permitted" />

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">{{__('db.Sale Report')}}</h3>
            </div>
            <form action="{{ route('report.sale.list') }}" method="POST">
                @csrf
                <div class="row mb-3 product-report-filter">
                    <div class="col-md-3 offset-md-2 mt-3">
                        <div class="form-group top-fields">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Your Date')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <div class="input-group">
                                    <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                    <input type="hidden" name="start_date" value="{{$start_date}}" />
                                    <input type="hidden" name="end_date" value="{{$end_date}}" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="form-group top-fields">
                            <label class="d-tc mt-2"><strong>{{__('db.Choose Warehouse')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <select name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{__('db.All Warehouse')}}</option>
                                    @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <div class="form-group top-fields">
                            <label class="d-tc mt-2"><strong>{{__('db.category')}}</strong> &nbsp;</label>
                            <div class="d-tc">
                                <select name="category_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                                    <option value="0">{{__('db.All Category')}}</option>
                                    @foreach($categories_list as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 text-center mt-3">
                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">{{__('db.submit')}}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive mt-3">
       <table id="report-table" class="table table-hover table-bordered" style="width: 100%">
    <thead class="thead-dark">
        <tr>
            <th class="not-exported">#</th>
            <th>{{__('Reference')}}</th>
            <th>{{__('Product')}}</th>
            <th>{{__('Customer')}}</th>
            <th>{{__('Biller')}}</th>
            <th>{{__('Warehouse')}}</th>
            <th>{{__('Quantity')}}</th>
            <th>{{__('price')}}</th>
            <th>{{__('Total')}}</th>
            <th class="not-exported text-center">{{__('db.action')}}</th>
            @foreach($custom_fields as $field)
                <th>{{ $field }}</th>
            @endforeach
        </tr>
    </thead>

    <tfoot class="tfoot active">
        <tr>
            <th></th>                    {{-- 0: # --}}
            <th>{{__('db.Total')}}</th> {{-- 1: reference_no --}}
            <th></th>                    {{-- 2: product_name --}}
            <th></th>                    {{-- 3: customer_name --}}
            <th></th>                    {{-- 4: biller_name --}}
            <th></th>                    {{-- 5: warehouse_name --}}
            <th></th>                    {{-- 6: qty (summed) --}}
            <th></th>                    {{-- 7: net_unit_price (summed) --}}
            <th></th>                    {{-- 8: total (summed) --}}
            <th></th>                    {{-- 9: action --}}
            @foreach($custom_fields as $field)
                <th></th>
            @endforeach
        </tr>
    </tfoot>
</table>
    </div>
</section>

{{-- ============================================================
     DETAIL + RETURN MODAL
     ============================================================ --}}
<div id="modal-sale-detail" class="modal fade text-left" tabindex="-1" role="dialog" aria-labelledby="modalSaleDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalSaleDetailLabel">
                    <i class="fa fa-file-text-o"></i> &nbsp; Sale Detail &amp; Return
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body" id="modal-detail-body">

                {{-- Loading spinner (shown while AJAX runs) --}}
                <div id="detail-loading" class="text-center py-4">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2 text-muted">Loading details…</p>
                </div>

                {{-- Content (hidden until data loads) --}}
                <div id="detail-content" style="display:none;">

                    {{-- Hidden IDs --}}
                    <input type="hidden" id="ret-sale-id">
                    <input type="hidden" id="ret-product-id">

                    {{-- ── Row 1: Sale info + Customer info ── --}}
                    <div class="row">
                        {{-- Sale Information card --}}
                        <div class="col-md-6">
                            <div class="card card-outline-secondary mb-3">
                                <div class="card-header py-2 bg-light">
                                    <strong><i class="fa fa-shopping-cart"></i> Sale Information</strong>
                                </div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width:45%">Reference No</td>
                                            <td><span id="d-reference-no" class="font-weight-bold"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Sale Date</td>
                                            <td><span id="d-sale-date"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Warehouse</td>
                                            <td><span id="d-warehouse"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Biller</td>
                                            <td><span id="d-biller"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Payment Status</td>
                                            <td><span id="d-payment-status"></span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Customer Information card --}}
                        <div class="col-md-6">
                            <div class="card card-outline-secondary mb-3">
                                <div class="card-header py-2 bg-light">
                                    <strong><i class="fa fa-user"></i> Customer Information</strong>
                                </div>
                                <div class="card-body py-2">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width:45%">Name</td>
                                            <td><span id="d-customer-name"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Phone</td>
                                            <td><span id="d-customer-phone"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email</td>
                                            <td><span id="d-customer-email"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Company</td>
                                            <td><span id="d-customer-company"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Address</td>
                                            <td><span id="d-customer-address"></span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Row 2: Product details ── --}}
                    <div class="card card-outline-secondary mb-3">
                        <div class="card-header py-2 bg-light">
                            <strong><i class="fa fa-cube"></i> Product Details</strong>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <div class="info-box bg-light border">
                                        <div class="info-box-content text-center py-2">
                                            <span class="text-muted small">Product Name</span>
                                            <h6 class="mb-0" id="d-product-name">—</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <div class="info-box bg-light border">
                                        <div class="info-box-content text-center py-2">
                                            <span class="text-muted small">Product Code</span>
                                            <h6 class="mb-0" id="d-product-code">—</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <div class="info-box bg-light border">
                                        <div class="info-box-content text-center py-2">
                                            <span class="text-muted small">Category</span>
                                            <h6 class="mb-0" id="d-product-category">—</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-2">
                                    <div class="info-box bg-light border">
                                        <div class="info-box-content text-center py-2">
                                            <span class="text-muted small">Unit</span>
                                            <h6 class="mb-0" id="d-product-unit">—</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Numeric stats row --}}
                            <div class="row mt-2">
                                <div class="col-md-2 col-sm-4 mb-2">
                                    <div class="card text-center border-primary">
                                        <div class="card-body py-2 px-1">
                                            <div class="text-muted small">Qty Sold</div>
                                            <h5 class="text-primary mb-0" id="d-qty">—</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 mb-2">
                                    <div class="card text-center border-warning">
                                        <div class="card-body py-2 px-1">
                                            <div class="text-muted small">Returned</div>
                                            <h5 class="text-warning mb-0" id="d-return-qty">—</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 mb-2">
                                    <div class="card text-center border-success">
                                        <div class="card-body py-2 px-1">
                                            <div class="text-muted small">Remaining</div>
                                            <h5 class="text-success mb-0" id="d-remaining-qty">—</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 mb-2">
                                    <div class="card text-center border-info">
                                        <div class="card-body py-2 px-1">
                                            <div class="text-muted small">Unit Price</div>
                                            <h5 class="text-info mb-0" id="d-unit-price">—</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 mb-2">
                                    <div class="card text-center border-secondary">
                                        <div class="card-body py-2 px-1">
                                            <div class="text-muted small">Discount</div>
                                            <h5 class="text-secondary mb-0" id="d-discount">—</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 mb-2">
                                    <div class="card text-center border-dark">
                                        <div class="card-body py-2 px-1">
                                            <div class="text-muted small">Total</div>
                                            <h5 class="mb-0" id="d-total">—</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Profit / Tax / Commission --}}
                            <div class="row mt-1">
                                <div class="col-md-4 col-sm-4 mb-2">
                                    <div class="card text-center bg-success text-white">
                                        <div class="card-body py-2">
                                            <div class="small">Profit</div>
                                            <h5 class="mb-0" id="d-profit">—</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 mb-2">
                                    <div class="card text-center bg-info text-white">
                                        <div class="card-body py-2">
                                            <div class="small">Tax</div>
                                            <h5 class="mb-0" id="d-tax">—</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 mb-2">
                                    <div class="card text-center bg-warning text-white">
                                        <div class="card-body py-2">
                                            <div class="small">Commission</div>
                                            <h5 class="mb-0" id="d-commission">—</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Row 3: Return form ── --}}
                    <div class="card card-outline-danger mb-0" id="return-form-card">
                        <div class="card-header py-2 bg-danger text-white">
                            <strong><i class="fa fa-undo"></i> Process Return</strong>
                            <small class="ml-2">(max returnable: <span id="d-max-return" class="font-weight-bold">0</span>)</small>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">Return Date</label>
                                        <input type="text"
                                               id="ret-created-at"
                                               name="created_at"
                                               class="form-control date"
                                               placeholder="DD-MM-YYYY"
                                               value="{{ date('d-m-Y') }}" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label class="small font-weight-bold">Quantity to Return</label>
                                        <input type="number"
                                               id="ret-quantity"
                                               class="form-control"
                                               min="1"
                                               value="1"
                                               required />
                                        <small class="text-danger d-none" id="ret-qty-error">Exceeds returnable quantity.</small>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-group mb-2 w-100">
                                        <label class="small">&nbsp;</label>
                                        <div>
                                            <strong class="small text-muted">Refund Amount:</strong>
                                            <h5 class="text-danger mb-0" id="d-refund-preview">—</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="ret-success-msg" class="alert alert-success d-none mt-2 mb-0">
                                <i class="fa fa-check"></i> Return processed successfully.
                            </div>
                            <div id="ret-error-msg" class="alert alert-danger d-none mt-2 mb-0"></div>
                        </div>
                    </div>

                </div>{{-- /detail-content --}}
            </div>{{-- /modal-body --}}

            {{-- Footer --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Close
                </button>

                <button type="button" class="btn btn-info" id="btn-process-exchange">
                    <i class="fa fa-undo"></i> Exchange for Same Product
                </button>

                <button type="button" class="btn btn-danger" id="btn-process-return" disabled>
                    <i class="fa fa-undo"></i> Process Return
                </button>
            </div>

        </div>
    </div>
</div>
{{-- /modal --}}

@endsection


@push('scripts')
<script type="text/javascript">
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ── Selectpicker pre-selection ──────────────────────────────────────────
    $('.product-report-filter select[name="warehouse_id"]').val(<?php echo json_encode($warehouse_id); ?>);
    $('.product-report-filter select[name="category_id"]').val(<?php echo json_encode($category_id); ?>);
    $('.selectpicker').selectpicker('refresh');

    // ── DataTable ───────────────────────────────────────────────────────────
    var decimalPlaces = {{ $general_setting->decimal }};

    var table = $('#report-table').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    ajax: {
        url: "{{ route('report.sale.data1') }}",
        data: function (d) {
            d.start_date   = $('input[name=start_date]').val();
            d.end_date     = $('input[name=end_date]').val();
            d.warehouse_id = $('select[name=warehouse_id]').val();
            d.category_id  = $('select[name=category_id]').val();
        }
    },
    columns: [
        { data: 'DT_RowIndex',    orderable: false, searchable: false }, // 0
        { data: 'reference_no' },                                        // 1
        { data: 'product_name' },                                        // 2
        { data: 'customer_name' },                                       // 3
        { data: 'biller_name' },                                         // 4
        { data: 'warehouse_name' },                                      // 5
        { data: 'qty' },                                                 // 6
        { data: 'net_unit_price' },                                      // 7
        { data: 'total' },                                               // 8
        { data: 'action', orderable: false, searchable: false },         // 9
        @foreach($field_names as $field)
            { data: '{{ $field }}' },
        @endforeach
    ],

    // Correct indices: qty=6, net_unit_price=7, total=8
    drawCallback: function () {
        var dt = this.api();
        [6, 7, 8].forEach(function (colIdx) {
            $(dt.column(colIdx).footer()).html(
                dt.column(colIdx, { page: 'current' }).data().reduce(function (a, b) {
                    return (parseFloat(a) || 0) + (parseFloat(b) || 0);
                }, 0).toFixed(decimalPlaces)
            );
        });
    }
});
 
    // ── Open detail modal ───────────────────────────────────────────────────
    $(document).on('click', '.fetchProductDetails', function () {
        var saleId    = $(this).data('sale_id');
        var productId = $(this).data('product_id');

        // Reset modal state
        $('#detail-loading').show();
        $('#detail-content').hide();
        $('#btn-process-return').prop('disabled', true);
        $('#ret-success-msg').addClass('d-none');
        $('#ret-error-msg').addClass('d-none').text('');
        $('#ret-qty-error').addClass('d-none');

        $('#modal-sale-detail').modal('show');

        $.ajax({
            url:  "{{ route('report.get-product-details') }}",
            type: 'GET',
            data: { sale_id: saleId, product_id: productId },
            success: function (r) {
                var c  = r.customer;
                var p  = r.product;
                var s  = r.sale;
                var remaining = p.remaining_qty;

                // Hidden IDs
                $('#ret-sale-id').val(saleId);
                $('#ret-product-id').val(productId);

                // Sale info
                $('#d-reference-no').text(s.reference_no);
                $('#d-sale-date').text(s.created_at);
                $('#d-warehouse').text(s.warehouse);
                $('#d-biller').text(s.biller);
                $('#d-payment-status').html(s.payment_status_badge);

                // Customer info
                $('#d-customer-name').text(c.name    || '—');
                $('#d-customer-phone').text(c.phone   || '—');
                $('#d-customer-email').text(c.email   || '—');
                $('#d-customer-company').text(c.company || '—');
                $('#d-customer-address').text(c.address || '—');

                // Product info
                $('#d-product-name').text(p.name);
                $('#d-product-code').text(p.code);
                $('#d-product-category').text(p.category || '—');
                $('#d-product-unit').text(p.unit || '—');

                // Numeric stats
                $('#d-qty').text(p.qty);
                $('#d-return-qty').text(p.return_qty);
                $('#d-remaining-qty').text(remaining);
                $('#d-unit-price').text(p.net_unit_price);
                $('#d-discount').text(p.discount);
                $('#d-total').text(p.total);
                $('#d-profit').text(p.profit);
                $('#d-tax').text(p.tax);
                $('#d-commission').text(p.commission);

                // Return form
                $('#d-max-return').text(remaining);
                $('#ret-quantity').val(remaining).attr('max', remaining);
                updateRefundPreview(remaining, p.net_unit_price);

                // Disable return form if nothing left to return
                if (remaining <= 0) {
                    $('#return-form-card').addClass('d-none');
                    $('#btn-process-return').prop('disabled', true);
                } else {
                    $('#return-form-card').removeClass('d-none');
                    $('#btn-process-return').prop('disabled', false);
                }

                $('#detail-loading').hide();
                $('#detail-content').show();
            },
            error: function () {
                $('#detail-loading').html(
                    '<div class="alert alert-danger">Failed to load details. Please try again.</div>'
                );
            }
        });
    });

     $(document).on('click', '#btn-process-exchange', function () {
        $('#modal-sale-detail').modal('hide');
        $('#expense-modal').modal('show');
        $('input[name="reference_no"]').val($('#d-reference-no').text());
     });

    // ── Live refund preview ─────────────────────────────────────────────────
    function updateRefundPreview(qty, unitPrice) {
        var refund = (parseFloat(qty) || 0) * (parseFloat(unitPrice) || 0);
        $('#d-refund-preview').text(refund.toFixed(decimalPlaces));
    }

    $(document).on('input', '#ret-quantity', function () {
        var max      = parseInt($('#d-max-return').text()) || 0;
        var entered  = parseInt($(this).val()) || 0;
        var unitPrice = parseFloat($('#d-unit-price').text()) || 0;

        if (entered > max || entered < 1) {
            $('#ret-qty-error').removeClass('d-none');
            $('#btn-process-return').prop('disabled', true);
            $('#d-refund-preview').text('—');
        } else {
            $('#ret-qty-error').addClass('d-none');
            $('#btn-process-return').prop('disabled', false);
            updateRefundPreview(entered, unitPrice);
        }
    });

    // ── Process return ──────────────────────────────────────────────────────
    $(document).on('click', '#btn-process-return', function () {
        var saleId    = $('#ret-sale-id').val();
        var productId = $('#ret-product-id').val();
        var qty       = $('#ret-quantity').val();
        var date      = $('#ret-created-at').val();

        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing…');
        $('#ret-success-msg').addClass('d-none');
        $('#ret-error-msg').addClass('d-none').text('');

        $.ajax({
            url:  "{{ route('report.process-return') }}",
            type: 'POST',
            data: {
                sale_id:           saleId,
                product_id:        productId,
                quantity_returned: qty,
                created_at:        date
            },
            success: function (response) {
                $('#ret-success-msg').removeClass('d-none');
                $('#return-form-card').addClass('d-none');
                $('#btn-process-return').prop('disabled', true).html('<i class="fa fa-undo"></i> Process Return');

                // Update the displayed return/remaining values
                var newReturnQty    = parseInt($('#d-return-qty').text()) + parseInt(qty);
                var newRemainingQty = parseInt($('#d-remaining-qty').text()) - parseInt(qty);
                $('#d-return-qty').text(newReturnQty);
                $('#d-remaining-qty').text(newRemainingQty);

                // Refresh the DataTable row so the report updates immediately
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : 'Failed to process return. Please try again.';
                $('#ret-error-msg').removeClass('d-none').text(msg);
                $('#btn-process-return').prop('disabled', false).html('<i class="fa fa-undo"></i> Process Return');
            }
        });
    });

</script>
@endpush