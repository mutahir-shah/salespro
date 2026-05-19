@foreach ($lims_product_sale_data as $key => $product_sale)
@php
$product = DB::table('products')->find($product_sale->product_id);
if(!$product) continue;
// FIX 1: Only show qty that hasn't already been returned
$qty = $product_sale->qty - ($product_sale->return_qty ?? 0);
if($qty <= 0) continue; // skip fully-returned items
    $tax=DB::table('taxes')->where('rate', $product_sale->tax_rate)->first();
    $unit = DB::table('units')->find($product_sale->sale_unit_id);
    @endphp

   <tr class="product-row return-product-row"
    data-unit-operator="{{ $product_sale->unit_operator ?? '*' }}"
    data-unit-operation-value="{{ $product_sale->unit_operation_value ?? 1 }}"
    data-price="{{ $product_sale->net_unit_price }}">
        {{-- Product name --}}
        <td class="product-title">
            <strong>{{ $product->name }}</strong><br>
            <span>{{ $product->code }}</span>

            <input type="hidden" class="product-code" name="product_code[]" value="{{ $product->code }}">
            <input type="hidden" class="product-id" name="product_id[]" value="{{ $product->id }}">
            <input type="hidden" class="product-sale-id" name="product_sale_id[]" value="{{ $product_sale->id }}">
            <input type="hidden" class="product_type" name="product_type[]" value="{{ $product->type ?? 'standard' }}">
            <input type="hidden" class="sale-unit-operator" value="{{ $unit->operator ?? '*' }}">
            <input type="hidden" class="sale-unit-operation-value" value="{{ $unit->operation_value ?? 1 }}">

            {{-- FIX 2: batch hidden so processReturnProduct() resolves batch stock correctly --}}
            <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="{{ $product_sale->product_batch_id ?? '' }}">

            <input type="hidden" class="unit-operator"
       value="{{ $product_sale->unit_operator ?? '*' }}">

<input type="hidden" class="unit-operation-value"
       value="{{ $product_sale->unit_operation_value ?? 1 }}">
        </td>

        {{-- Quantity — use +/- buttons so the existing JS qty handlers fire correctly --}}
        <td>
            <div class="input-group" style="width:120px">
                <span class="input-group-btn">
                    <button type="button" class="btn btn-default minus" style="padding:4px 8px">
                        <i class="dripicons-minus"></i>
                    </button>
                </span>
                <input type="text"
                    name="qty[]"
                    class="form-control qty numkey input-number"
                    style="max-width:45px;text-align:center;font-size:13px;padding:0"
                    step="any"
                    value="{{ $qty }}"
                    max="{{ $qty }}"
                    required>
                <span class="input-group-btn">
                    <button type="button" class="btn btn-default plus" style="padding:4px 8px">
                        <i class="dripicons-plus"></i>
                    </button>
                </span>
            </div>
        </td>

        <td class="product-price">
    <span class="product-price-text">
        {{ number_format($product_sale->net_unit_price, $general_setting->decimal) }}
    </span>
</td>

        {{-- FIX 3: discount column shows total line discount, not per-unit --}}
        <td class="discount">
            {{ number_format($product_sale->discount * $qty, $general_setting->decimal) }}
        </td>

        <td class="tax">
            {{ number_format($product_sale->tax, $general_setting->decimal) }}
        </td>

        <td class="sub-total">
    <span class="sub-total-text">
        {{ number_format($product_sale->total, $general_setting->decimal) }}
    </span>
</td>

        {{-- FIX 4: checked by default so exchange value populates immediately on load --}}
        <td class="is-exchange text-center" style="vertical-align:middle">
            <input type="checkbox"
                id="exchange_{{ $product->code }}"
                name="is_exchange[]"
                class="exchange-checkbox"
                checked
                value="{{ $product->code }}"
                onchange="calculateExchangeValue()"
                style="width:18px;height:18px;cursor:pointer">
        </td>

        {{-- ===== ALL REQUIRED HIDDEN FIELDS ===== --}}

        {{-- FIX 5 (was the #1 controller bug): value MUST be "return" — controller checks === 'return' --}}
        <input type="hidden" name="type[]" value="return">

        <input type="hidden" class="sale-unit" name="sale_unit[]" value="{{ $unit->unit_name ?? 'n/a' }}">
        <input type="hidden" class="net_unit_price" name="net_unit_price[]" value="{{ $product_sale->net_unit_price }}">

        {{-- FIX 3: discount[] must match what is displayed — total line discount --}}
        <input type="hidden" class="discount-value" name="discount[]" value="{{ $product_sale->discount * $qty }}">

        <input type="hidden" class="tax-rate" name="tax_rate[]" value="{{ $product_sale->tax_rate }}">
        <input type="hidden" class="tax-name" value="{{ $tax->name ?? 'No Tax' }}">
        <input type="hidden" class="tax-method" value="{{ $product->tax_method ?? 1 }}">
        <input type="hidden" class="tax-value" name="tax[]" value="{{ $product_sale->tax }}">
        <input type="hidden" class="subtotal-value" name="subtotal[]" value="{{ $product_sale->total }}">
        <input type="hidden" class="imei-number" name="imei_number[]" value="{{ $product_sale->imei_number ?? '' }}">
    </tr>
    @endforeach