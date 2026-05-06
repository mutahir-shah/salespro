@extends('backend.layout.main')
@section('content')
<style type="text/css">
    .btn-icon i {
        margin-right: 5px
    }

    .top-fields {
        margin-top: 10px;
        position: relative;
    }

    .top-fields label {
        font-size: 11px;
        font-weight: 600;
        margin-left: 10px;
        padding: 0 3px;
        position: absolute;
        top: -8px;
        z-index: 9;
    }

    .top-fields input {
        font-size: 13px;
        height: 45px
    }

    .filter-section {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .stats-box {
        background: #fff;
        border: 1px solid #e3e6f0;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }

    .stat-item {
        display: inline-block;
        margin-right: 30px;
    }

    .stat-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        font-weight: 600;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #28a745;
    }
</style>
<x-success-message key="message" />
<x-error-message key="error" />
<section class="forms">
    <div class="container-fluid">
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">{{__('Biller Commissions')}}</h3>
            </div>
            <div class="card-body">
                <!-- Filter Section --> 
                    <form method="GET">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="date">{{__('Date')}}</label>
                                    <div class="input-group">
                                        <input type="date" name="date" id="date" value="{{ $date }}" class="form-control" style="margin-left: 8px;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="biller_id">{{__('Biller')}}</label>
                                    <div class="input-group">
                                        <select name="biller_id" id="biller_id" class="form-control" style="margin-left: 8px;">
                                            <option value="">{{__('All Billers')}}</option>
                                            @foreach($billers as $biller)
                                            <option value="{{ $biller->id }}" {{ $billerId == $biller->id ? 'selected' : '' }}>
                                                {{ $biller->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary" style="margin-left: 8px;">
                                    <i class="dripicons-search"></i> {{__('Filter')}}
                                </button>
                            </div>
                        </div>
                    </form>
             
                    


                <!-- Statistics Section -->
                @if($commissions->count() > 0)
                <div class="stats-box">
                    <div class="stat-item">
                        <div class="stat-label">{{__('Total Commission')}}</div>
                        <div class="stat-value">{{ number_format($totalCommission, 2) }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">{{__('Unpaid Commission')}}</div>
                        <div class="stat-value" style="color: #ffc107;">{{ number_format($totalUnpaidCommission, 2) }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">{{__('Records')}}</div>
                        <div class="stat-value" style="color: #007bff;">{{ $commissions->count() }}</div>
                    </div>
                </div>
                @endif
                <form id="pay-commissions-form" method="POST" action="{{ route('biller.commissions.pay') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all"></th>
                                    <th>{{__('Sale Reference')}}</th>
                                    <th>{{__('Biller')}}</th>
                                    <th>{{__('Total Items')}}</th>
                                    <th>{{__('Total Profit')}}</th>
                                    <th>{{__('Commission Amount')}}</th>
                                    <th>{{__('Paid Amount')}}</th>
                                    <th>{{__('Status')}}</th>
                                    <th>{{__('Calculated At')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commissions as $commission)
                                <tr>
                                    <td>
                                        @if(!$commission->is_paid)
                                        <input type="checkbox" name="commission_ids[]" value="{{ $commission->id }}" class="commission-checkbox" data-amount="{{ $commission->commission_amount }}" data-row-index="{{ $loop->index }}">
                                        @endif
                                    </td>
                                    <td>{{ $commission->sale->reference_no ?? 'N/A' }}</td>
                                    <td>{{ $commission->biller->name ?? 'N/A' }}</td>
                                    <td>{{ $commission->total_items }}</td>
                                    <td>{{ number_format($commission->total_profit, 2) }}</td>
                                    <td class="commission-amount-cell"><strong>{{ number_format($commission->commission_amount, 2) }}</strong></td>
                                    <td>{{ number_format($commission->paid_amount, 2) }}</td>
                                    <td>
                                        @if($commission->is_paid)
                                        <span class="badge badge-success">{{__('Paid')}}</span>
                                        @else
                                        <span class="badge badge-warning">{{__('Unpaid')}}</span>
                                        @endif
                                    </td>
                                    <td>{{ $commission->calculated_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">{{__('No commissions found for this date')}}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($commissions->where('is_paid', false)->count() > 0)
                    <div class="mt-4 p-3" style="background: #f8f9fa; border-radius: 5px;">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="paid_at">{{__('Payment Date & Time')}}</label>
                                    <input type="datetime-local" name="paid_at" id="paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-success btn-block" onclick="return confirmPayment();">
                                        <i class="dripicons-checkmark"></i> {{__('Pay Selected Commissions')}}
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{__('Selected Amount')}}</label>
                                    <div class="form-control" style="background: #fff; border-color: #28a745; color: #28a745; font-weight: bold; font-size: 16px;" id="selected-amount">0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</section>


@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        console.log('Document Ready - Initializing commission script');

        function calculateSelectedAmount() {
            let total = 0;
            let checkedCount = 0;
            $('.commission-checkbox:checked').each(function() {
                // Try to get amount from data attribute first
                let amount = parseFloat($(this).data('amount'));
                // If data attribute is not working, try to extract from the table cell
                if (isNaN(amount) || amount === 0) {
                    let amountText = $(this).closest('tr').find('.commission-amount-cell').text().trim();
                    amountText = amountText.replace(/[^\d.-]/g, ''); // Remove non-numeric characters
                    amount = parseFloat(amountText);
                    console.log('Extracted from cell:', amountText, 'Parsed as:', amount);
                }

                console.log('Checkbox amount:', amount, 'isNaN:', isNaN(amount));
                if (!isNaN(amount) && amount > 0) {
                    total += amount;
                    checkedCount++;
                }
            });
            console.log('Total calculated:', total, 'Count:', checkedCount);
            $('#selected-amount').text(total.toFixed(2));
        }
        // Initial calculation on page load
        console.log('Commission checkboxes found:', $('.commission-checkbox').length);
        calculateSelectedAmount();
        $('#select-all').on('change', function() {
            console.log('Select All clicked');
            $('.commission-checkbox').prop('checked', $(this).prop('checked'));
            calculateSelectedAmount();
        });
        $('.commission-checkbox').on('change', function() {
            console.log('Individual checkbox changed');
            calculateSelectedAmount();
            if ($('.commission-checkbox:checked').length === $('.commission-checkbox').length) {
                $('#select-all').prop('checked', true);
            } else {
                $('#select-all').prop('checked', false);
            }
        });
    });

    function confirmPayment() {
        const selectedAmount = $('#selected-amount').text();
        const selectedCount = $('.commission-checkbox:checked').length;

        if (selectedCount === 0) {
            alert('Please select at least one commission to pay');
            return false;
        }
        const confirmed = confirm(`Pay ${selectedCount} commission(s) with total amount: ${selectedAmount}?`);
        return confirmed;
    }
</script>

@endpush