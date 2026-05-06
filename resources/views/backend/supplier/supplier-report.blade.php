@extends('backend.layout.main')

@section('content')

@php
$selectedSupplierId = request()->input('supplier_id');
$selectedProductId = request()->input('product_id');
@endphp
<section>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Supplier Inventory Report</h3>
                    </div>

                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="supplier_filter">Filter by Supplier:</label>
                                <select class="form-control" id="supplier_filter">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ $selectedSupplierId == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="product_filter">Filter by Product:</label>
                                <select class="form-control" id="product_filter">
                                    <option value="">All Products</option>
                                    @if($selectedSupplierId && !empty($supplierProducts))
                                    @foreach($supplierProducts as $product)
                                    <option value="{{ $product->id }}"
                                        {{ $selectedProductId == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                    @else
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}"
                                        {{ $selectedProductId == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label><br>
                                <button type="button" class="btn btn-primary" id="load_report">Load Report</button>
                                <button type="button" class="btn btn-secondary" id="export_excel">Export Excel</button>
                            </div>
                        </div>

                        <!-- Loading indicator -->
                        <div id="loading" class="text-center" style="display: none;">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p>Loading supplier inventory report...</p>
                        </div>

                        <!-- Report Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="supplier_inventory_table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Supplier</th>
                                        <th>Product</th>
                                        <th>Total Purchased</th>
                                        <th>Remaining Qty</th>
                                        <th>Weighted Unit Cost</th>
                                        <th>Remaining Cost Value</th>
                                        <th>Unit Price</th>
                                        <th>Remaining Price Value</th>
                                        <th>Profit Margin</th>
                                    </tr>
                                </thead>
                                <tbody id="report_tbody">
                                    <tr>
                                        <td colspan="9" class="text-center">Click "Load Report" to generate supplier inventory report</td>
                                    </tr>
                                </tbody>
                                <tfoot id="report_footer" style="display: none;">
                                    <tr class="table-info font-weight-bold">
                                        <td colspan="3">TOTALS:</td>
                                        <td id="total_remaining_qty">0</td>
                                        <td>-</td>
                                        <td id="total_cost_value">0.00</td>
                                        <td>-</td>
                                        <td id="total_price_value">0.00</td>
                                        <td id="total_profit">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loadReportBtn = document.getElementById('load_report');
        const exportExcelBtn = document.getElementById('export_excel');
        const supplierFilter = document.getElementById('supplier_filter');
        const productFilter = document.getElementById('product_filter');
        const loading = document.getElementById('loading');
        const tableBody = document.getElementById('report_tbody');
        const tableFooter = document.getElementById('report_footer');

        // Store all products for "All Suppliers" option
        const allProducts = @json($products);

        loadReportBtn.addEventListener('click', loadReport);
        exportExcelBtn.addEventListener('click', exportToExcel);
        //supplierFilter.addEventListener('change', onSupplierChange);

        // Auto-load report if supplier is pre-selected from URL
         
        loadReport(); 

        function onSupplierChange() {
            const supplierId = supplierFilter.value;

            // Clear product filter
            productFilter.innerHTML = '<option value="">All Products</option>';

            if (supplierId) {
                // Load supplier-specific products
                fetch(`/api/supplier-products/${supplierId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.products.forEach(product => {
                                const option = document.createElement('option');
                                option.value = product.id;
                                option.textContent = product.name;
                                productFilter.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading supplier products:', error);
                        // Fallback to all products
                        loadAllProducts();
                    });
            } else {
                // Load all products
                loadAllProducts();
            }

            // Auto-load report when supplier changes
            setTimeout(loadReport, 100);
        }

        function loadAllProducts() {
            allProducts.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.name;
                productFilter.appendChild(option);
            });
        }

        function loadReport() {
            const supplierId = supplierFilter.value;
            const productId = productFilter.value;

            loading.style.display = 'block';
            tableBody.innerHTML = '';
            tableFooter.style.display = 'none';

            const params = new URLSearchParams();
            if (supplierId) params.append('supplier_id', supplierId);
            if (productId) params.append('product_id', productId);

            // Update URL without page reload
            const newUrl = new URL(window.location);
            if (supplierId) {
                newUrl.searchParams.set('supplier_id', supplierId);
            } else {
                newUrl.searchParams.delete('supplier_id');
            }
            if (productId) {
                newUrl.searchParams.set('product_id', productId);
            } else {
                newUrl.searchParams.delete('product_id');
            }
            window.history.pushState({}, '', newUrl);

            fetch(`{{ route('supplier.supplier-data') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    loading.style.display = 'none';

                    if (data.success && data.data.length > 0) {
                        displayReport(data.data);
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="9" class="text-center">No data found</td></tr>';
                    }
                })
                .catch(error => {
                    loading.style.display = 'none';
                    console.error('Error:', error);
                    tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading report</td></tr>';
                });
        }

        function displayReport(reportData) {
            let html = '';
            let totalRemainingQty = 0;
            let totalCostValue = 0;
            let totalPriceValue = 0;

            reportData.forEach(item => {
                const remainingQty = parseFloat(item.remaining_qty || 0);
                const weightedCost = parseFloat(item.weighted_cost || 0);
                const unitPrice = parseFloat(item.unit_price || 0);
                const costValue = parseFloat(item.remaining_cost_value || 0);
                const priceValue = parseFloat(item.remaining_price_value || 0);
                const profitMargin = priceValue - costValue;

                totalRemainingQty += remainingQty;
                totalCostValue += costValue;
                totalPriceValue += priceValue;

                html += `
                <tr>
                    <td>${item.supplier_name || ''}</td>
                    <td>${item.product_name || ''}</td>
                    <td class="text-right">${item.total_purchased || 0}</td>
                    <td class="text-right">${remainingQty.toFixed(0)}</td>
                    <td class="text-right">${weightedCost.toFixed(2)}</td>
                    <td class="text-right">${costValue.toFixed(2)}</td>
                    <td class="text-right">${unitPrice.toFixed(2)}</td>
                    <td class="text-right">${priceValue.toFixed(2)}</td>
                    <td class="text-right ${profitMargin >= 0 ? 'text-success' : 'text-danger'}">${profitMargin.toFixed(2)}</td>
                </tr>
            `;
            });

            tableBody.innerHTML = html;

            // Update footer totals
            document.getElementById('total_remaining_qty').textContent = totalRemainingQty.toFixed(0);
            document.getElementById('total_cost_value').textContent = totalCostValue.toFixed(2);
            document.getElementById('total_price_value').textContent = totalPriceValue.toFixed(2);
            document.getElementById('total_profit').textContent = (totalPriceValue - totalCostValue).toFixed(2);

            tableFooter.style.display = '';
        }

        function exportToExcel() {
            const supplierId = supplierFilter.value;
            const productId = productFilter.value;

            const params = new URLSearchParams();
            params.append('export', 'excel');
            if (supplierId) params.append('supplier_id', supplierId);
            if (productId) params.append('product_id', productId);

            window.open(`{{ route('supplier.supplier-data') }}?${params.toString()}`);
        }
    });
</script>
@endpush