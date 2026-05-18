@extends('backend.layout.main')
@section('content')
    @push('css')
        <style>
            @media print {
                .hidden-print {
                    display: none !important;
                }
            }

            #product-results-container {
                background: #f5f6f7;
                position: absolute;
                overflow: hidden;
                max-height: 300px;
                overflow-y: auto;
                padding-top: 10px;
                top: 40px;
                width: 100%;
                z-index: 999
            }

            #sale_product-results-container {
                background: #f5f6f7;
                position: absolute;
                overflow: hidden;
                max-height: 300px;
                overflow-y: auto;
                padding-top: 10px;
                top: 40px;
                width: 100%;
                z-index: 999
            }

            #product-results-container .product-img,
            #sale_product-results-container .product-img {
                border-radius: 3px;
                color: #7c5cc4;
                font-size: 13px;
                padding-top: 7px;
                padding-bottom: 7px;
                text-align: left;
                padding-left: 8px;
                cursor: pointer;
            }

            #product-results-container .product-img:hover,
            #sale_product-results-container .product-img:hover {
                background-color: #7c5cc4;
                color: #FFF
            }

            .return-product-row {
                background-color: #fff3cd !important;
                border-left: 3px solid #ff9800;
            }

            .exchange-checkbox {
                width: 20px;
                height: 20px;
                cursor: pointer;
            }
        </style>
    @endpush

    <x-error-message key="not_permitted" />
    <x-error-message key="error" />

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <?php $authUser = Auth::user()->role_id; ?>

    <section id="pos-layout" class="forms hidden-print">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4>{{ __('db.Exchange Sale') }}</h4>
                        </div>
                        <div class="card-body">
                            <p class="italic">
                                <small>{{ __('db.The field labels marked with * are required input fields') }}.</small>
                            </p>

                            <form action="{{ route('exchange.store') }}" method="POST"
                                  enctype="multipart/form-data" id="payment-form">
                                @csrf

                                <div class="row">
                                    <div class="col-md-12">

                                        {{-- ===== TOP FIELDS ===== --}}
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.date') }}</label>
                                                    @can('change_sale_date')
                                                        <input type="text" name="created_at" class="form-control date"
                                                               value="{{ date($general_setting->date_format, strtotime(now()->toDateString())) }}" />
                                                    @else
                                                        <input type="text" name="created_at" class="form-control date"
                                                               value="{{ date($general_setting->date_format, strtotime(now()->toDateString())) }}"
                                                               readonly />
                                                    @endcan
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.reference') }}</label>
                                                    {{-- Shows the original sale reference after search --}}
                                                    <p id="sale-reference-display">
                                                        <strong>{{ __('db.Search for a sale above') }}</strong>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.customer') }} *</label>
                                                    <select required name="customer_id"
                                                            class="selectpicker form-control"
                                                            data-live-search="true" id="customer_id"
                                                            title="Select customer...">
                                                        @foreach ($lims_customer_list as $customer)
                                                            <option value="{{ $customer->id }}">
                                                                {{ $customer->name . ' (' . $customer->phone_number . ')' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <x-validation-error fieldName="customer_id" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Warehouse') }} *</label>
                                                    <select required id="warehouse_id" name="warehouse_id"
                                                            class="selectpicker form-control"
                                                            data-live-search="true"
                                                            title="Select warehouse...">
                                                        @foreach ($lims_warehouse_list as $warehouse)
                                                            <option value="{{ $warehouse->id }}">
                                                                {{ $warehouse->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <x-validation-error fieldName="warehouse_id" />
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Biller') }} *</label>
                                                    <select required name="biller_id"
                                                            class="selectpicker form-control"
                                                            data-live-search="true"
                                                            title="Select Biller...">
                                                        @foreach ($lims_biller_list as $biller)
                                                            <option value="{{ $biller->id }}">
                                                                {{ $biller->name . ' (' . $biller->company_name . ')' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label fw-semibold">{{ __('db.Account') }} *</label>
                                                    <select class="form-control selectpicker" name="account_id">
                                                        @foreach ($lims_account_list as $account)
                                                            <option value="{{ $account->id }}"
                                                                {{ $account->is_default ? 'selected' : '' }}>
                                                                {{ $account->name }} [{{ $account->account_no }}]
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>{{-- /top fields row --}}

                                        {{-- ===== SEARCH BY REFERENCE ===== --}}
                                        <h3>{{ __('db.search_by_reference_no_or_Product') }}</h3>
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <label>{{ __('db.Search By Reference Number') }}</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control"
                                                           id="sale_product_search"
                                                           placeholder="Enter reference number">
                                                    <button class="btn btn-primary" type="button" id="saleSearchBtn">
                                                        <i class="fa fa-search"></i> Search
                                                    </button>
                                                </div>
                                                {{-- FIX: sale_id is set here, updated via JS after search --}}
                                                <input type="hidden" name="sale_id" id="sale_id_input" value="">
                                            </div>

                                            <div class="col-md-8">
                                                <label>{{ __('db.Search Return Product') }}</label>
                                                <div class="search-box form-group mb-2" style="position:relative">
                                                    <div class="input-group pos">
                                                        <input style="border: 1px solid #7c5cc4;" type="text"
                                                               name="sale_product_code_name"
                                                               id="sale-product-search-input"
                                                               placeholder="Scan/Search return product by name/code/IMEI"
                                                               class="form-control" />
                                                        <button type="button" class="btn btn-primary"
                                                                onclick="barcodeSale()">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                 height="16" fill="currentColor" class="bi bi-upc"
                                                                 viewBox="0 0 16 16">
                                                                <path d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div id="sale_product-results-container"></div>
                                                    <div id="no-results-message-sale"
                                                         style="background-color:#f5f6f7;color:#666;margin-top:5px;padding:3px 5px;display:none;">
                                                        No results found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== RETURN PRODUCTS TABLE ===== --}}
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5>{{ __('db.Return Products') }}</h5>
                                                <div class="table-responsive mt-2">
                                                    <table id="sale-product-table"
                                                           class="table table-hover return-order-list">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('db.name') }}</th>
                                                                <th>{{ __('db.Quantity') }}</th>
                                                                <th>{{ __('db.Net Unit Price') }}</th>
                                                                <th>{{ __('db.Discount') }}</th>
                                                                <th>{{ __('db.Tax') }}</th>
                                                                <th>{{ __('db.Subtotal') }}</th>
                                                                <th>{{ __('db.Exchange') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== NEW PRODUCT SEARCH ===== --}}
                                        <div class="row mt-3">
                                            <div class="col-md-12">
                                                <label>{{ __('db.Select New Product') }}</label>
                                                <div class="search-box form-group mb-2" style="position:relative">
                                                    <div class="input-group pos">
                                                        <input style="border: 1px solid #7c5cc4;" type="text"
                                                               name="product_code_name" id="product-search-input"
                                                               placeholder="Scan/Search product by name/code/IMEI"
                                                               class="form-control" />
                                                        <button type="button" class="btn btn-primary"
                                                                onclick="barcodeNew()">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                 height="16" fill="currentColor" class="bi bi-upc"
                                                                 viewBox="0 0 16 16">
                                                                <path d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div id="product-results-container"></div>
                                                    <div id="no-results-message"
                                                         style="background-color:#f5f6f7;color:#666;margin-top:5px;padding:3px 5px;display:none;">
                                                        No results found
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== NEW PRODUCTS TABLE ===== --}}
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5>{{ __('db.New Products') }}</h5>
                                                <div class="table-responsive mt-2">
                                                    <table id="myTable" class="table table-hover order-list">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('db.name') }}</th>
                                                                <th>{{ __('db.Quantity') }}</th>
                                                                <th>{{ __('db.Net Unit Price') }}</th>
                                                                <th>{{ __('db.Discount') }}</th>
                                                                <th>{{ __('db.Tax') }}</th>
                                                                <th>{{ __('db.Subtotal') }}</th>
                                                                <th><i class="dripicons-trash"></i></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== TOTALS HIDDEN INPUTS (single set, updated by JS) ===== --}}
                                        {{-- FIX: removed duplicate hidden input blocks — only ONE set needed --}}
                                        <input type="hidden" name="total_qty"      value="0" />
                                        <input type="hidden" name="total_discount" value="0" />
                                        <input type="hidden" name="total_tax"      value="0" />
                                        <input type="hidden" name="total_price"    value="0" />
                                        <input type="hidden" name="item"           value="0" />
                                        <input type="hidden" name="order_tax"      value="0" />
                                        <input type="hidden" name="grand_total"    value="0" />
                                        <input type="hidden" name="order_discount" value="0" />

                                        @if (@$lims_sale_data->coupon_id)
                                            @php $coupon_data = DB::table('coupons')->find($lims_sale_data->coupon_id); @endphp
                                            <input type="hidden" name="coupon_active"         value="1" />
                                            <input type="hidden" name="coupon_type"           value="{{ $coupon_data->type }}" />
                                            <input type="hidden" name="coupon_amount"         value="{{ $coupon_data->amount }}" />
                                            <input type="hidden" name="coupon_minimum_amount" value="{{ $coupon_data->minimum_amount }}" />
                                            <input type="hidden" name="coupon_discount"       value="{{ @$lims_sale_data->coupon_discount }}">
                                        @else
                                            <input type="hidden" name="coupon_active" value="0" />
                                            <input type="hidden" name="coupon_discount" value="0" />
                                        @endif

                                        {{-- ===== ORDER TAX / DOCUMENT / DISCOUNT ===== --}}
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Order Tax') }}</label>
                                                    <select class="form-control" name="order_tax_rate">
                                                        <option value="0">No Tax</option>
                                                        @foreach ($lims_tax_list as $tax)
                                                            <option value="{{ $tax->rate }}">{{ $tax->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Attach Document') }}
                                                        <i class="dripicons-question" data-toggle="tooltip"
                                                           title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported"></i>
                                                    </label>
                                                    <input type="file" name="document" class="form-control" />
                                                    @if ($errors->has('extension'))
                                                        <span class="text-danger"><strong>{{ $errors->first('extension') }}</strong></span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>{{ __('db.Return Discount') }}</label>
                                                    <input type="number" name="total_sale_discount"
                                                           id="discount_value" class="form-control"
                                                           value="0" min="0" step="any" />
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== NOTES ===== --}}
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('db.Return Note') }}</label>
                                                    <textarea rows="4" class="form-control" name="return_note"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>{{ __('db.Staff Note') }}</label>
                                                    <textarea rows="4" class="form-control" name="staff_note"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- ===== EXCHANGE CALCULATION SUMMARY ===== --}}
                                        <table class="table table-bordered exchange-totals mt-2">
                                            <tbody>
                                                <tr style="background-color:#e3f2fd;">
                                                    <td colspan="4" style="text-align:center;font-weight:bold;color:#1976d2;">
                                                        EXCHANGE CALCULATION
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Return Value (Exchange)</strong><br>
                                                        <span id="exchange-value" style="color:#d32f2f;font-weight:bold;">0.00</span>
                                                    </td>
                                                    <td>
                                                        <strong>New Products Total</strong><br>
                                                        <span id="new-products-total" style="color:#388e3c;font-weight:bold;">0.00</span>
                                                    </td>
                                                    <td>
                                                        <strong>Shipping</strong><br>
                                                        <span id="exchange-shipping" style="color:#f57c00;font-weight:bold;">0.00</span>
                                                    </td>
                                                    <td>
                                                        <strong>New Grand Total</strong><br>
                                                        <span id="new-grand-total" style="color:#1565c0;font-weight:bold;">0.00</span>
                                                    </td>
                                                </tr>
                                                <tr style="background-color:#fff3e0;">
                                                    <td colspan="2" style="text-align:right;font-size:16px;font-weight:bold;">
                                                        BALANCE (Customer Payment):
                                                    </td>
                                                    <td colspan="2">
                                                        <input id="amount"       type="hidden" value="0" name="amount" />
                                                        <input id="payment-type" type="hidden" value="none" name="payment_type" />
                                                        <span id="balance-display" style="font-size:18px;font-weight:bold;color:#1565c0;">0.00</span>
                                                        <span id="balance-status" style="margin-left:15px;font-weight:bold;font-size:14px;"></span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <div class="form-group mt-3">
                                            <input type="hidden" name="draft" value="0" />
                                            <button id="submit-button" type="submit" class="btn btn-primary">
                                                {{ __('db.submit') }}
                                            </button>
                                        </div>

                                    </div>{{-- /col-md-12 --}}
                                </div>{{-- /row --}}
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== EDIT PRODUCT MODAL ===== --}}
        <div id="editModal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
            <div role="document" class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="modal_header" class="modal-title"></h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                            <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="row modal-element">
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Quantity') }}</label>
                                    <input type="number" step="any" name="edit_qty" class="form-control numkey">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Unit Discount') }}</label>
                                    <input type="number" name="edit_discount" class="form-control numkey">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Price Option') }}</label>
                                    <select class="form-control selectpicker" name="price_option"></select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Unit Price') }}</label>
                                    <input type="number" name="edit_unit_price" class="form-control numkey" step="any">
                                </div>
                                <?php
                                    $tax_name_all = ['No Tax'];
                                    $tax_rate_all = [0];
                                    foreach ($lims_tax_list as $tax) {
                                        $tax_name_all[] = $tax->name;
                                        $tax_rate_all[] = $tax->rate;
                                    }
                                ?>
                                <div class="col-md-4 form-group">
                                    <label>{{ __('db.Tax Rate') }}</label>
                                    <select name="edit_tax_rate" class="form-control selectpicker">
                                        @foreach ($tax_name_all as $key => $name)
                                            <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="edit_unit" class="col-md-4 form-group">
                                    <label>{{ __('db.Product Unit') }}</label>
                                    <select name="edit_unit" class="form-control selectpicker"></select>
                                </div>
                            </div>
                            <button type="button" name="update_btn" class="btn btn-primary">
                                {{ __('db.update') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="print-layout"></section>

    {{-- QR Scanner --}}
    <div style="width:100%;max-width:350px;position:fixed;top:5%;left:50%;transform:translateX(-50%);z-index:999">
        <button type="button" class="btn btn-danger" id="closeScannerBtn" style="display:none">X</button>
        <div id="reader" style="width:100%;"></div>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    // ─────────────────────────────────────────────
    // GLOBALS
    // ─────────────────────────────────────────────
    const doneTypingInterval = 300;

    const $newProductInput    = $('#product-search-input');
    const $newProductResults  = $('#product-results-container');
    const $saleProductInput   = $('#sale-product-search-input');
    const $saleProductResults = $('#sale_product-results-container');
    const $noResults          = $('#no-results-message');
    const $noResultsSale      = $('#no-results-message-sale');

    var currency         = <?php echo json_encode($currency); ?>;
    var currencyChange   = false;
    var without_stock    = <?php echo json_encode($general_setting->without_stock); ?>;
    var authUser         = <?php echo json_encode($authUser); ?>;
    var decimal          = <?php echo json_encode($general_setting->decimal); ?>;
    var role_id          = <?php echo json_encode(Auth::user()->role_id); ?>;

    // FIX: ensure exchange_rate is always a valid number
    if (!currency['exchange_rate'] || isNaN(currency['exchange_rate'])) {
        currency['exchange_rate'] = 1;
    }

    var exchangeValue    = 0;
    var newProductsTotal = 0;

    // JS arrays tracking each row
    var product_price          = [];
    var wholesale_price        = [];
    var cost                   = [];
    var product_discount       = [];
    var tax_rate               = [];
    var tax_name               = [];
    var tax_method             = [];
    var unit_name              = [];
    var unit_operator          = [];
    var unit_operation_value   = [];
    var is_imei                = [];
    var is_variant             = [];
    var temp_unit_name         = [];
    var temp_unit_operator     = [];
    var temp_unit_operation_value = [];

    var rowindex;
    var customer_group_rate = 0;
    var row_product_price;
    var pos;

    // ─────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────
    function clearResults(type = 'new') {
        if (type === 'return') {
            $saleProductResults.empty().css('padding', '0');
            $noResultsSale.hide();
        } else {
            $newProductResults.empty().css('padding', '0');
            $noResults.hide();
        }
    }

    function setCustomerGroupRate(id) {
        if (!id) return;
        $.get('{{ url('sales/getcustomergroup') }}/' + id, function(data) {
            customer_group_rate = parseFloat(data) / 100 || 0;
        });
    }

    // ─────────────────────────────────────────────
    // DOCUMENT READY
    // ─────────────────────────────────────────────
    $(document).ready(function () {

        calculateTotal();
        $('#product-search-input').focus();

        var customer_id = $('#customer_id').val();
        if (customer_id) setCustomerGroupRate(customer_id);

        $('.selectpicker').selectpicker({ style: 'btn-link' });
        $('[data-toggle="tooltip"]').tooltip();

        $('select[name="customer_id"]').on('change', function () {
            setCustomerGroupRate($(this).val());
        });

        let typingTimer;

        // ── Product search (new) ──
        $newProductInput.on('input', function () {
            const value = $(this).val().trim();
            if (value.length >= 3) {
                click = 0;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => searchProducts(value, 'new'), doneTypingInterval);
            } else {
                clearResults('new');
            }
        });
        $newProductInput.on('paste', function (e) {
            const pasted = (e.originalEvent || e).clipboardData.getData('text');
            if (pasted.length >= 3) { click = 0; searchProducts(pasted.trim(), 'new'); }
        });

        // ── Product search (return) ──
        $saleProductInput.on('input', function () {
            const value = $(this).val().trim();
            if (value.length >= 3) {
                click = 0;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => searchProducts(value, 'return'), doneTypingInterval);
            } else {
                clearResults('return');
            }
        });
        $saleProductInput.on('paste', function (e) {
            const pasted = (e.originalEvent || e).clipboardData.getData('text');
            if (pasted.length >= 3) { click = 0; searchProducts(pasted.trim(), 'return'); }
        });

        // ── Close dropdowns on outside click ──
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#product-results-container, #product-search-input').length)
                clearResults('new');
            if (!$(e.target).closest('#sale_product-results-container, #sale-product-search-input').length)
                clearResults('return');
        });
    });

    var click = 0;

    // ─────────────────────────────────────────────
    // PRODUCT SEARCH (AJAX)
    // ─────────────────────────────────────────────
    function searchProducts(search, type = 'new') {
        var $resultsContainer = (type === 'return') ? $saleProductResults : $newProductResults;
        var $noResultsMsg     = (type === 'return') ? $noResultsSale      : $noResults;

        $resultsContainer.css('padding', '0 10px 15px').html(
            '<div style="padding:10px;text-align:center"><i class="fa fa-spinner fa-spin"></i> Searching...</div>'
        );
        $noResultsMsg.hide();

        var warehouse_id = $('#warehouse_id').val();
        if (!warehouse_id) { alert('Please select a warehouse first'); return; }

        $.ajax({
            url: '{{ route('sales.search') }}',
            type: 'GET',
            data: { warehouse_id: warehouse_id, search: search },
            success: function (data) {
                $resultsContainer.empty();
                if (data.length > 0) {
                    $noResultsMsg.hide();
                    data.forEach(function (product) {
                        let productHtml = '';
                        let displayStock = (authUser > 2) ? '' : ` | ${product.qty} {{ __('db.In Stock') }}`;
                        var batch_id = product.product_batch_id || '';

                        if (product.is_imei == 1 || product.is_imei === true) {
                            let exists = false;
                            $('.imei-number').each(function () {
                                if ($(this).val().split(',').includes(product.imei_number)) {
                                    exists = true; return false;
                                }
                            });
                            if (!exists && product.imei_number && product.imei_number.length > 0) {
                                productHtml = `<div class="product-img"
                                    data-code="${product.code}" data-qty="${product.qty}"
                                    data-imei="${product.imei_number}" data-embedded="${product.is_embeded}"
                                    data-batch="${batch_id}" data-type="${type}" data-price="${product.price}">
                                    ${product.name} (${product.code}) | ${product.price} | IMEI: ${product.imei_number}
                                </div>`;
                            } else { $noResultsMsg.show(); }
                        } else if (product.product_batch_id != null) {
                            if (parseInt(product.qty) > 0) {
                                var expiredClass = '';
                                if (!product.expired_date || product.expired_date == 0) {
                                    product.expired_date = "{{ __('db.expired') }}";
                                    expiredClass = 'expired';
                                }
                                productHtml = `<div class="product-img ${expiredClass}"
                                    data-code="${product.code}" data-qty="${product.qty}"
                                    data-imei="${product.is_imei}" data-embedded="${product.is_embeded}"
                                    data-type="${type}" data-batch="${batch_id}" data-price="${product.price}">
                                    ${product.name} (${product.code}) - ${product.expired_date} | ${product.price} ${displayStock}
                                </div>`;
                            }
                        } else {
                            productHtml = `<div class="product-img"
                                data-code="${product.code}" data-qty="${product.qty}"
                                data-imei="${product.is_imei}" data-embedded="${product.is_embeded}"
                                data-type="${type}" data-batch="${batch_id}" data-price="${product.price}">
                                ${product.name} (${product.code}) | ${product.price} ${displayStock}
                            </div>`;
                        }

                        $resultsContainer.append(productHtml);
                    });

                    $resultsContainer.find('.product-img').on('click', function () {
                        clearResults(type);
                    });

                    // Auto-click single result
                    if (data.length === 1 && click === 0) {
                        $resultsContainer.find('.product-img').first().trigger('click');
                        clearResults(type);
                        click = 1;
                    }
                } else {
                    clearResults(type);
                    $noResultsMsg.show();
                }
            },
            error: function () { $noResultsMsg.text('Error searching products.').show(); }
        });
    }

    // ─────────────────────────────────────────────
    // REFERENCE SEARCH
    // ─────────────────────────────────────────────
    $('#saleSearchBtn').on('click', function () {
        var reference = $('#sale_product_search').val().trim();
        if (!reference) { alert('Please enter a reference number'); return; }

        $.ajax({
            url: "{{ route('sale.exchange.search') }}",
            type: "GET",
            data: { reference: reference },
            success: function (res) {
                if (res.status) {
                    $('#sale-product-table tbody').html(res.html);
                    // FIX: update hidden sale_id with the one returned from controller
                    $('#sale_id_input').val(res.sale_id);
                    $('#sale-reference-display').html('<strong>' + reference + '</strong>');
                    calculateExchangeValue();
                } else {
                    alert(res.message);
                }
            },
            error: function () { alert('Error searching for reference number.'); }
        });
    });

    // ─────────────────────────────────────────────
    // EXCHANGE CALCULATION
    // ─────────────────────────────────────────────
    function calculateExchangeValue() {
        exchangeValue = 0;
        $('input.exchange-checkbox:checked').each(function () {
            var subtotal = parseFloat($(this).closest('tr').find('.sub-total').text()) || 0;
            exchangeValue += subtotal;
        });
        $('#exchange-value').text(exchangeValue.toFixed(decimal));
        calculateNewProductsTotal();
    }


    function calculateExchangeValue() {
    exchangeValue = 0;

    $('input.exchange-checkbox:checked').each(function () {

        var subtotalText = $(this)
            .closest('tr')
            .find('.sub-total')
            .text();

        // remove commas
        subtotalText = subtotalText.replace(/,/g, '');

        var subtotal = parseFloat(subtotalText) || 0;

        exchangeValue += subtotal;
    });

    $('#exchange-value').text(exchangeValue.toFixed(decimal));

    calculateNewProductsTotal();
}

    function calculateNewProductsTotal() {
        newProductsTotal = 0;
        $('table.order-list tbody tr').each(function () {
            newProductsTotal += parseFloat($(this).find('.sub-total').text()) || 0;
        });
        $('#new-products-total').text(newProductsTotal.toFixed(decimal));
        calculateExchangeBalance();
        // FIX: no recursive call back to calculateExchangeValue() here
    }

    function calculateExchangeBalance() {
        var shippingCost = parseFloat($('input[name="shipping_cost"]').val()) || 0;
        var grandTotal   = newProductsTotal + shippingCost;
        var balance      = grandTotal - exchangeValue;

        $('#exchange-shipping').text(shippingCost.toFixed(decimal));
        $('#new-grand-total').text(grandTotal.toFixed(decimal));

        var absBalance = Math.abs(balance).toFixed(decimal);
        $('#balance-display').text(absBalance);
        $('#amount').val(absBalance);

        if (balance > 0) {
            $('#balance-display').css('color', '#d32f2f');
            $('#balance-status').text('(Customer Pays)').css('color', '#d32f2f');
            $('#payment-type').val('receive');
        } else if (balance < 0) {
            $('#balance-display').css('color', '#388e3c');
            $('#balance-status').text('(Refund to Customer)').css('color', '#388e3c');
            $('#payment-type').val('pay');
        } else {
            $('#balance-display').css('color', '#1565c0');
            $('#balance-status').text('(No Balance)').css('color', '#1565c0');
            $('#payment-type').val('none');
        }
    }

    $(document).on('change', 'input.exchange-checkbox', calculateExchangeValue);
    $('input[name="shipping_cost"]').on('blur change', calculateExchangeBalance);
    $('select[name="order_tax_rate"]').on('change', function () { calculateGrandTotal(); calculateExchangeBalance(); });

    // ─────────────────────────────────────────────
    // CALCULATE TOTAL (fixed selectors)
    // ─────────────────────────────────────────────
    function calculateTotal() {
        var total_qty = 0;
        $('table.order-list tbody .qty, table.return-order-list tbody .qty').each(function () {
            total_qty += parseFloat($(this).val()) || 0;
        });
        $('input[name="total_qty"]').val(total_qty);

        var total_discount = 0;
        $('table.order-list tbody .discount-value, table.return-order-list tbody .discount-value').each(function () {
            total_discount += parseFloat($(this).val()) || 0;
        });
        $('input[name="total_discount"]').val(total_discount.toFixed(decimal));

        var total_tax = 0;
        $('.tax-value').each(function () { total_tax += parseFloat($(this).val()) || 0; });
        $('input[name="total_tax"]').val(total_tax.toFixed(decimal));

        var total = 0;
        $('.sub-total').each(function () { total += parseFloat($(this).text()) || 0; });
        $('input[name="total_price"]').val(total.toFixed(decimal));

        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        var subtotal            = parseFloat($('input[name="total_price"]').val()) || 0;
        var order_tax_rate      = parseFloat($('select[name="order_tax_rate"]').val()) || 0;
        var order_discount_type = $('select[name="order_discount_type"]').val();
        var order_discount_val  = parseFloat($('input[name="order_discount_value"]').val()) || 0;
        var shipping_cost       = parseFloat($('input[name="shipping_cost"]').val()) || 0;
        var coupon_discount     = parseFloat($('input[name="coupon_discount"]').val()) || 0;

        var order_discount = (order_discount_type === 'Flat')
            ? order_discount_val
            : subtotal * (order_discount_val / 100);

        var order_tax   = (subtotal - order_discount) * (order_tax_rate / 100);
        var grand_total = (subtotal + order_tax + shipping_cost) - order_discount - coupon_discount;

        $('input[name="order_tax"]').val(order_tax.toFixed(decimal));
        $('input[name="grand_total"]').val(grand_total.toFixed(decimal));
        $('input[name="item"]').val($('table.order-list tbody tr').length);

        currencyChange = false;
    }

    // ─────────────────────────────────────────────
    // PRODUCT CLICK → SEARCH → ADD ROW
    // ─────────────────────────────────────────────
    $(document).on('click', '.product-img', function () {
        productSearch($(this).data());
    });

    function productSearch(data) {
        if (data.embedded == 1) {
            alert('{{ __('db.This product has been added using the weight scale machine.') }}');
            return;
        }

        var item_code     = data.code;
        var product_type  = data.type;
        var pre_qty       = 0;
        var flag          = true;
        var tableSelector = (product_type === 'return') ? 'table.return-order-list' : 'table.order-list';

        $('.product-code').each(function (i) {
            if ($(this).val().trim() == item_code) {
                rowindex = i;
                if (data.imei && data.imei != 'null' && data.imei != '') {
                    var existing = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .imei-number').val();
                    if (existing && existing.split(',').includes(data.imei)) {
                        alert('Same IMEI or serial number is not allowed!');
                        flag = false;
                        $('#product-search-input, #sale-product-search-input').val('');
                        return false;
                    }
                }
                pre_qty = parseFloat($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) || 0;
            }
        });

        if (!flag) return;

        var product = {
            code: data.code, qty: data.qty,
            pre_qty: pre_qty + 1, imei: data.imei,
            embedded: data.embedded, batch: data.batch,
            price: data.price, customer_id: $('#customer_id').val()
        };

        $.ajax({
            type: 'GET', async: false,
            url: '{{ url('sales/lims_product_search') }}',
            data: { data: product },
            success: function (data) {
                if (data[23]) { data[15] = 1; pre_qty = 0; }

                if (pre_qty > 0 && data[21]) {
                    var old_batch = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-no').val();
                    if (old_batch && old_batch != data[22]) { pre_qty = 0; data[15] = 1; }
                }

                $("input[name='product_code_name'], input[name='sale_product_code_name']").val('');

                if (pre_qty > 0) {
                    var qty = data[15];
                    $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                    product_price[rowindex] = parseFloat(data[2] * currency['exchange_rate'])
                        + parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate);
                    checkDiscount(String(qty), true, tableSelector);

                    if (data[18] && data[18] != 'null' && data[18] != '') {
                        var existing = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();
                        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number')
                            .val(existing ? existing + ',' + data[18] : data[18]);
                    }
                } else {
                    addNewProduct(data, tableSelector);
                }
            }
        });
    }

    function addNewProduct(data, tableSelector) {
        var sale_type = (tableSelector === 'table.return-order-list') ? 'return' : 'new';

        temp_unit_name = (data[6] || 'n/a,').split(',');
        pos = -1; // new row, not pre-existing

        var cols = '';

        // Product name cell
        if (authUser > 2) {
            cols += '<td class="product-title"><strong>' + data[0] + '</strong><br><span>' + data[1] + '</span>';
        } else {
            var stockDisplay = '';
            if ((data[20] == 'standard' || data[20] == 'combo') && (!data[18] || data[18] == 'null')) {
                stockDisplay = ' | {{ __('db.In Stock') }}: <span class="in-stock">' + data[19] + '</span>';
            }
            cols += '<td class="product-title"><strong class="edit-product btn btn-link pl-0 pr-0" data-toggle="modal" data-target="#editModal">'
                + data[0] + ' <i class="dripicons-document-edit"></i></strong><br><span>' + data[1] + '</span>' + stockDisplay;
        }

        if (data[12]) {
            cols += '<br><input type="text" class="form-control batch-no" style="font-size:13px;height:30px" value="' + (data[22] || '') + '" required/>'
                + '<input type="hidden" class="product-batch-id" name="product_batch_id[]" value="' + (data[21] || '') + '"/>';
        } else {
            cols += '<input type="hidden" class="product-batch-id" name="product_batch_id[]" value=""/>';
        }
        cols += '</td>';

        // Qty cell
        cols += '<td><div class="input-group"><span class="input-group-btn">';
        if (!data[18] || data[18] == 'null') {
            cols += '<button type="button" class="btn btn-default minus mr-1" style="padding:5px 8px"><i class="dripicons-minus"></i></button>';
        }
        cols += '</span><input type="text" name="qty[]" class="form-control qty numkey input-number"'
            + ' style="max-width:50px;text-align:center;font-size:13px;padding:0" step="any" value="' + data[15]
            + '" max="' + data[19] + '" required><span class="input-group-btn">';
        if (!data[18] || data[18] == 'null') {
            cols += '<button type="button" class="btn btn-default plus ml-1" style="padding:5px 8px"><i class="dripicons-plus"></i></button>';
        }
        cols += '</span></div></td>';

        cols += '<td class="product-price"></td>';
        cols += '<td class="discount">0.00</td>';
        cols += '<td class="tax">0.00</td>';
        cols += '<td class="sub-total"></td>';

        // Last column: exchange checkbox OR delete button
        if (tableSelector === 'table.return-order-list') {
            cols += '<td class="is-exchange" style="text-align:center;vertical-align:middle">'
                + '<input type="checkbox" name="is_exchange[]" class="exchange-checkbox" checked'
                + ' value="' + data[1] + '" onchange="calculateExchangeValue()"'
                + ' style="width:18px;height:18px;cursor:pointer"></td>';
        } else {
            cols += '<td><button type="button" class="ibtnDel btn btn-danger btn-sm"><i class="dripicons-trash"></i></button></td>';
        }

        // All hidden inputs
        cols += '<input type="hidden" class="product-code"             name="product_code[]"    value="' + data[1] + '"/>';
        cols += '<input type="hidden" class="product-id"               name="product_id[]"      value="' + data[9] + '"/>';
        cols += '<input type="hidden" class="product_type"             name="product_type[]"    value="' + data[20] + '"/>';
        cols += '<input type="hidden" class="product_price" />';
        cols += '<input type="hidden" class="sale-unit"                name="sale_unit[]"       value="' + (temp_unit_name[0] || 'n/a') + '"/>';
        cols += '<input type="hidden" class="net_unit_price"           name="net_unit_price[]" />';
        cols += '<input type="hidden" class="discount-value"           name="discount[]" />';
        cols += '<input type="hidden" class="tax-rate"                 name="tax_rate[]"        value="' + data[3] + '"/>';
        cols += '<input type="hidden" class="tax-value"                name="tax[]" />';
        cols += '<input type="hidden" class="tax-name"                 value="' + data[4] + '"/>';
        cols += '<input type="hidden" class="tax-method"               value="' + data[5] + '"/>';
        cols += '<input type="hidden" class="sale-unit-operator"       value="' + data[7] + '"/>';
        cols += '<input type="hidden" class="sale-unit-operation-value" value="' + data[8] + '"/>';
        cols += '<input type="hidden" class="subtotal-value"           name="subtotal[]" />';
        cols += '<input type="hidden" name="type[]"                    value="' + sale_type + '"/>';
        cols += '<input type="hidden" class="product-sale-id"          name="product_sale_id[]" value=""/>';
        cols += '<input type="hidden" class="imei-number"              name="imei_number[]"     value="' + ((data[18] && data[18] != 'null') ? data[18] : '') + '"/>';

        var newRow = $('<tr>').append(cols);
        $(tableSelector + ' tbody').prepend(newRow);
        rowindex = newRow.index();

        var basePrice = parseFloat(data[2] * currency['exchange_rate'])
            + parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate);

        product_price.splice(rowindex, 0, basePrice);
        wholesale_price.splice(rowindex, 0, data[16]
            ? parseFloat(data[16] * currency['exchange_rate']) + parseFloat(data[16] * currency['exchange_rate'] * customer_group_rate)
            : 0);
        cost.splice(rowindex, 0, parseFloat(data[17] * currency['exchange_rate']) || 0);
        product_discount.splice(rowindex, 0, 0);
        tax_rate.splice(rowindex, 0, parseFloat(data[3]) || 0);
        tax_name.splice(rowindex, 0, data[4]);
        tax_method.splice(rowindex, 0, data[5]);
        unit_name.splice(rowindex, 0, data[6]);
        unit_operator.splice(rowindex, 0, data[7]);
        unit_operation_value.splice(rowindex, 0, data[8]);
        is_imei.splice(rowindex, 0, data[13]);
        is_variant.splice(rowindex, 0, data[14]);

        checkQuantity(data[15], true, tableSelector);
        checkDiscount(data[15], true, tableSelector);
    }

    // ─────────────────────────────────────────────
    // ROW QUANTITY / DISCOUNT / PRICE CALCULATIONS
    // ─────────────────────────────────────────────
    function checkDiscount(qty, flag, tableSelector = 'table.order-list', price = 0) {
        var customer_id = $('#customer_id').val();
        var warehouse_id = $('#warehouse_id').val();
        var product_id  = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();

        $.ajax({
            type: 'GET', async: false,
            url: '{{ url('/') }}/sales/check-discount?qty=' + qty + '&customer_id=' + customer_id
                + '&product_id=' + product_id + '&warehouse_id=' + warehouse_id,
            success: function (data) {
                if (!product_price[rowindex] || isNaN(product_price[rowindex])) {
                    product_price[rowindex] = parseFloat(
                        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_price').val()
                    ) || 0;
                }
                product_price[rowindex] = parseFloat(product_price[rowindex] * currency['exchange_rate'])
                    + parseFloat(product_price[rowindex] * currency['exchange_rate'] * customer_group_rate);

                // data[2] = discount amount — update display total
                var productDiscount = parseFloat($('#discount').text()) || 0;
                if (flag === true)        $('#discount').text((productDiscount + data[2]).toFixed(decimal));
                else if (flag === false)  $('#discount').text((productDiscount - data[2] * qty).toFixed(decimal));
                else if (flag === 'input') $('#discount').text((productDiscount).toFixed(decimal));
                else                      $('#discount').text((productDiscount - data[2]).toFixed(decimal));
            }
        });

        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
        checkQuantity(String(qty), true, tableSelector);
    }

    function checkQuantity(sale_qty, flag, tableSelector = 'table.order-list') {
        var max_qty      = parseFloat($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').attr('max'));
        var product_type = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();

        if (without_stock == 'no') {
            if (product_type && (product_type.trim() == 'standard' || product_type.trim() == 'combo')) {
                var operator        = (unit_operator[rowindex] || '*,').split(',');
                var operation_value = (unit_operation_value[rowindex] || '1,').split(',');
                var total_qty       = (operator[0] == '*') ? sale_qty * operation_value[0] : sale_qty / operation_value[0];

                if (total_qty > max_qty && !isNaN(max_qty)) {
                    alert('Quantity exceeds stock quantity!');
                    if (flag) {
                        sale_qty = Math.max(0, sale_qty - 1);
                        checkQuantity(sale_qty, true, tableSelector);
                        return;
                    } else { edit(); return; }
                }
            }
        }

        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(sale_qty);
        if (!flag) $('#editModal').modal('hide');
        calculateRowProductData(sale_qty, tableSelector);
    }

    function unitConversion() {
        var sep = unit_operator[rowindex].indexOf(',');
        var row_op  = (sep > -1) ? unit_operator[rowindex].substring(0, sep) : unit_operator[rowindex];
        var row_val = (sep > -1) ? unit_operation_value[rowindex].substring(0, unit_operation_value[rowindex].indexOf(',')) : unit_operation_value[rowindex];

        row_product_price = (row_op == '*')
            ? product_price[rowindex] * parseFloat(row_val)
            : product_price[rowindex] / parseFloat(row_val);
    }

    function calculateRowProductData(quantity, tableSelector = 'table.order-list') {
        var current_product_type = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();

        if (current_product_type && current_product_type.trim() === 'standard') unitConversion();
        else row_product_price = product_price[rowindex] || 0;

        var disc      = parseFloat(product_discount[rowindex]) || 0;
        var t_rate    = parseFloat(tax_rate[rowindex]) || 0;
        var t_method  = tax_method[rowindex];
        var net_unit_price, taxAmt, sub_total, sub_total_unit;

        if (t_method == 1) {
            net_unit_price = row_product_price - disc;
            taxAmt         = net_unit_price * quantity * (t_rate / 100);
            sub_total      = net_unit_price * quantity + taxAmt;
            sub_total_unit = quantity ? sub_total / quantity : sub_total;
        } else {
            sub_total_unit = row_product_price - disc;
            net_unit_price = (100 / (100 + t_rate)) * sub_total_unit;
            taxAmt         = (sub_total_unit - net_unit_price) * quantity;
            sub_total      = sub_total_unit * quantity;
        }

        var topping_price = parseFloat($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.topping-price').val() * quantity) || 0;

        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((disc * quantity).toFixed(decimal));
        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(t_rate.toFixed(decimal));
        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price.toFixed(decimal));
        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(taxAmt.toFixed(decimal));
        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-price').text(sub_total_unit.toFixed(decimal));
        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text((sub_total + topping_price).toFixed(decimal));
        $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val((sub_total + topping_price).toFixed(decimal));

        calculateTotal();
        calculateNewProductsTotal();
    }

    // ─────────────────────────────────────────────
    // +/- BUTTONS
    // ─────────────────────────────────────────────
    $(document).on('click', 'table .plus', function () {
        rowindex = $(this).closest('tr').index();
        var tableSelector = $(this).closest('table').hasClass('return-order-list') ? 'table.return-order-list' : 'table.order-list';
        var qty     = parseFloat($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) || 0;
        var max_qty = parseFloat($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').attr('max'));
        if (!isNaN(max_qty) && qty >= max_qty) { alert('Quantity cannot exceed available stock (' + max_qty + ').'); return; }
        qty++;
        is_variant[rowindex] ? checkQuantity(String(qty), true, tableSelector) : checkDiscount(qty, true, tableSelector);
    });

    $(document).on('click', 'table .minus', function () {
        rowindex = $(this).closest('tr').index();
        var tableSelector = $(this).closest('table').hasClass('return-order-list') ? 'table.return-order-list' : 'table.order-list';
        var qty = parseFloat($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) - 1;
        if (qty < 1) { qty = 1; return; }
        is_variant[rowindex] ? checkQuantity(String(qty), true, tableSelector) : checkDiscount(qty, '3', tableSelector);
    });

    // ─────────────────────────────────────────────
    // DELETE ROW
    // ─────────────────────────────────────────────
    $(document).on('click', 'table tbody .ibtnDel', function () {
        rowindex = $(this).closest('tr').index();
        product_price.splice(rowindex, 1);
        wholesale_price.splice(rowindex, 1);
        product_discount.splice(rowindex, 1);
        tax_rate.splice(rowindex, 1);
        tax_name.splice(rowindex, 1);
        tax_method.splice(rowindex, 1);
        unit_name.splice(rowindex, 1);
        unit_operator.splice(rowindex, 1);
        unit_operation_value.splice(rowindex, 1);
        is_imei.splice(rowindex, 1);
        is_variant.splice(rowindex, 1);
        $(this).closest('tr').remove();
        calculateTotal();
        calculateNewProductsTotal();
        calculateExchangeValue();
    });

    // ─────────────────────────────────────────────
    // EDIT MODAL
    // ─────────────────────────────────────────────
    $('table.order-list, table.return-order-list').on('click', '.edit-product', function () {
        rowindex = $(this).closest('tr').index();
        edit();
    });

    function edit() {
        $('.imei-section').remove();
        var tableSelector = 'table.order-list';
        if ($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').length === 0)
            tableSelector = 'table.return-order-list';

        var product_type_val = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();

        // Populate IMEI list if needed
        if (is_imei[rowindex]) {
            var imeis = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();
            if (imeis) {
                var imeiArr  = [...new Set(imeis.split(','))];
                var htmlText = '<div class="col-md-8 form-group imei-section"><label>IMEI / Serial Numbers</label><div class="table-responsive"><table id="imei-table" class="table"><tbody>';
                imeiArr.forEach(function (v) {
                    htmlText += '<tr><td><input type="text" class="form-control imei-numbers" value="' + v + '"/></td><td><button type="button" class="imei-del btn btn-sm btn-danger">X</button></td></tr>';
                });
                htmlText += '</tbody></table></div></div>';
                $('#editModal .modal-element').append(htmlText);
            }
        }

        // Price option
        $('#editModal select[name=price_option]').empty()
            .append('<option value="' + product_price[rowindex] + '">' + product_price[rowindex] + '</option>');
        if (wholesale_price[rowindex] > 0)
            $('#editModal select[name=price_option]').append('<option value="' + wholesale_price[rowindex] + '">' + wholesale_price[rowindex] + '</option>');

        $('#modal_header').text($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ') td:first').text().trim());
        $('input[name="edit_qty"]').val($(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val());
        $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex] || 0).toFixed(decimal));

        var tax_name_all = <?php echo json_encode($tax_name_all); ?>;
        $('select[name="edit_tax_rate"]').val(tax_name_all.indexOf(tax_name[rowindex]));

        if (product_type_val == 'standard') {
            unitConversion();
            temp_unit_name            = (unit_name[rowindex] || '').split(',').filter(Boolean);
            temp_unit_operator        = (unit_operator[rowindex] || '').split(',').filter(Boolean);
            temp_unit_operation_value = (unit_operation_value[rowindex] || '').split(',').filter(Boolean);
            $('select[name="edit_unit"]').empty();
            temp_unit_name.forEach(function (v, k) {
                $('select[name="edit_unit"]').append('<option data-operator="' + temp_unit_operator[k] + '" data-operation-value="' + temp_unit_operation_value[k] + '" value="' + k + '">' + v + '</option>');
            });
            $('#edit_unit').show();
        } else {
            row_product_price = product_price[rowindex];
            $('#edit_unit').hide();
        }

        $('input[name="edit_unit_price"]').val((row_product_price || 0).toFixed(decimal));
        $('.selectpicker').selectpicker('refresh');
    }

    $('button[name="update_btn"]').on('click', function () {
        var tableSelector = ($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').length > 0)
            ? 'table.order-list' : 'table.return-order-list';

        var edit_qty        = parseFloat($('input[name="edit_qty"]').val());
        var edit_discount   = parseFloat($('input[name="edit_discount"]').val());
        var edit_unit_price = parseFloat($('input[name="edit_unit_price"]').val());

        if (edit_discount > edit_unit_price) { alert('Invalid Discount Input!'); return; }
        if (edit_qty < 1)                    { edit_qty = 1; $('input[name="edit_qty"]').val(1); }

        var tax_rate_all    = <?php echo json_encode($tax_rate_all); ?>;
        tax_rate[rowindex]  = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
        tax_name[rowindex]  = $('select[name="edit_tax_rate"] option:selected').text();

        var ptype = $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();
        product_discount[rowindex] = edit_discount;

        if (ptype == 'standard') {
            var row_op  = $('#edit_unit select').find(':selected').data('operator');
            var row_val = $('#edit_unit select').find(':selected').data('operation-value');
            product_price[rowindex] = (row_op == '*') ? edit_unit_price * row_val : edit_unit_price / row_val;

            var position = $('select[name="edit_unit"]').val();
            var tmp_op   = temp_unit_operator[position];
            var tmp_val  = temp_unit_operation_value[position];

            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[position]);
            temp_unit_name.splice(position, 1); temp_unit_operator.splice(position, 1); temp_unit_operation_value.splice(position, 1);
            temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
            temp_unit_operator.unshift(tmp_op); temp_unit_operation_value.unshift(tmp_val);

            unit_name[rowindex]            = temp_unit_name.join(',') + ',';
            unit_operator[rowindex]        = temp_unit_operator.join(',') + ',';
            unit_operation_value[rowindex] = temp_unit_operation_value.join(',') + ',';
        } else {
            product_price[rowindex] = edit_unit_price;
        }

        if (is_imei[rowindex]) {
            var imeis = '';
            $('#editModal .imei-numbers').each(function (i) { imeis += (i ? ',' : '') + $(this).val(); });
            $(tableSelector + ' tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeis);
        }

        checkDiscount(edit_qty, false, tableSelector);
        $('#editModal').modal('hide');
    });

    $(document).on('click', '#imei-table tbody .imei-del', function () {
        var tableSelector = ($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').length > 0)
            ? 'table.order-list' : 'table.return-order-list';
        var edit_qty = parseFloat($('input[name="edit_qty"]').val()) - 1;
        $('input[name="edit_qty"]').val(edit_qty);

        var target  = $(this).closest('tr').find('.imei-numbers').val();
        $(this).closest('tr').remove();

        var imeis   = $(tableSelector + ' tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();
        var updated = imeis.split(',').filter(i => i.trim() !== target.trim()).join(',');
        $(tableSelector + ' tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(updated);
        $(tableSelector + ' tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(edit_qty);

        if (edit_qty <= 0) { $('#editModal').modal('hide'); $(tableSelector + ' tr:eq(' + rowindex + ')').remove(); }
        checkDiscount(edit_qty, false, tableSelector);
        calculateTotal();
    });

    $('select[name=price_option]').on('change', function () { $('input[name=edit_unit_price]').val($(this).val()); });
    $('select[name="order_discount_type"]').on('change', calculateGrandTotal);
    $('input[name="order_discount_value"]').on('blur', calculateGrandTotal);
    $('input[name="shipping_cost"]').on('blur', function () { calculateGrandTotal(); calculateExchangeBalance(); });

    // ─────────────────────────────────────────────
    // FORM SUBMIT VALIDATION
    // ─────────────────────────────────────────────
    $('#payment-form').on('submit', function (e) {
        // Must have searched a sale
        if (!$('#sale_id_input').val()) {
            alert('Please search for a sale reference number first.');
            e.preventDefault(); return;
        }
        // Must have at least one new product
        if ($('table.order-list tbody tr').length < 1) {
            alert('Please add at least one new product.');
            e.preventDefault(); return;
        }
        // No empty qty
        var hasEmpty = false;
        $('table.order-list tbody .qty, table.return-order-list tbody .qty').each(function () {
            if (!$(this).val() || parseFloat($(this).val()) <= 0) hasEmpty = true;
        });
        if (hasEmpty) { alert('One of the products has an invalid quantity.'); e.preventDefault(); return; }

        $('#submit-button').prop('disabled', true);
        $('.batch-no').prop('disabled', false);
    });

    // Enter key — skip submit
    $(window).keydown(function (e) {
        if (e.which == 13) {
            var $targ = $(e.target);
            if (!$targ.is('textarea') && !$targ.is(':button,:submit')) {
                var focusNext = false;
                $(this).find(':input:visible:not([disabled],[readonly]), a').each(function () {
                    if (this === e.target) { focusNext = true; }
                    else if (focusNext) { $(this).focus(); return false; }
                });
                return false;
            }
        }
    });
</script>

{{-- QR Scanner --}}
<script>
    const closeScannerBtn = document.getElementById('closeScannerBtn');
    const html5Qrcode     = new Html5Qrcode('reader');
    let currentScannerType = 'new';

    function barcodeNew()  { currentScannerType = 'new';    startScanner(); }
    function barcodeSale() { currentScannerType = 'return'; startScanner(); }

    function startScanner() {
        html5Qrcode.start({ facingMode: 'environment' }, { fps: 30, qrbox: { width: 300, height: 100 } },
            function (decodedText) {
                if (currentScannerType === 'return')
                    document.getElementById('sale-product-search-input').value = decodedText;
                else
                    document.getElementById('product-search-input').value = decodedText;
                html5Qrcode.stop();
                closeScannerBtn.style.display = 'none';
            }
        );
        closeScannerBtn.style.display = 'inline-block';
    }

    closeScannerBtn.addEventListener('click', function () {
        html5Qrcode.stop();
        closeScannerBtn.style.display = 'none';
    });
</script>

<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush