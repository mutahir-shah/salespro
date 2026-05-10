@extends('backend.layout.main')
@section('content')
<style>
    .btn-icon i {
        margin-right: 5px
    }

    .top-fields {
        margin-top: 10px;
        position: relative
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
        margin-right: 30px
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
        {{-- ================= CARD ================= --}}
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">{{__('Biller Commissions')}}</h3>
            </div>
            {{-- ================= FILTER FORM ================= --}}
            <div class="card-body">
                <form method="GET">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__('Date')}}</label>
                                <input type="date" name="date" value="{{$date}}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{__('Biller')}}</label>
                                <select name="biller_id" class="form-control">
                                    <option value="">{{__('All Billers')}}</option>
                                    @foreach($billers as $biller)
                                    <option value="{{ $biller->id }}" {{ $billerId == $biller->id ? 'selected' : '' }}>
                                        {{ $biller->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="dripicons-search"></i> {{__('Filter')}}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        {{-- ================= STATISTICS ================= --}}
        @if($commissions->count() > 0)
        <div class="stats-box">
            <div class="stat-item">
                <div class="stat-label">{{__('Total Commission')}}</div>
                <div class="stat-value">{{ number_format($totalCommission,2) }}</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">{{__('Unpaid Commission')}}</div>
                <div class="stat-value text-warning">
                {{ number_format($totalUnpaidCommission,2) }}
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">{{__('Records')}}</div>
                <div class="stat-value text-primary">
                    {{ $commissions->count() }}
                </div>
            </div>
        </div>
        @endif
        {{-- ================= PAY FORM ================= --}}
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
                                <input type="checkbox" name="commission_ids[]" value="{{ $commission->id }}"
                                    class="commission-checkbox"  data-amount="{{ $commission->commission_amount }}">
                                @endif
                            </td>
                            <td>{{ $commission->sale->reference_no ?? 'N/A' }}</td>
                            <td>{{ $commission->biller->name ?? 'N/A' }}</td>
                            <td>{{ $commission->total_items }}</td>
                            <td>{{ number_format($commission->total_profit,0) }}</td>
                            <td class="commission-amount-cell">
                                <strong>{{ number_format($commission->commission_amount,0) }}</strong>
                            </td>
                            <td>{{ number_format($commission->paid_amount,0) }}</td>
                            <td>
                                @if($commission->is_paid)
                                <span class="badge badge-success">{{__('Paid')}}</span>
                                @else
                                <span class="badge badge-warning">{{__('Unpaid')}}</span>
                                @endif
                            </td>
                            <td>
                                {{ $commission->calculated_at->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center"> {{__('No commissions found for this date')}}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- ================= PAYMENT SECTION ================= --}}
            @if($commissions->where('is_paid',false)->count() > 0)
            <div class="mt-4 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-3">
                        <label>{{__('Payment Date & Time')}}</label>
                        <input type="datetime-local" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-block" onclick="return confirmPayment();">
                            <i class="dripicons-checkmark"></i> {{__('Pay Selected Commissions')}}
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label>{{__('Selected Amount')}}</label>
                        <div id="selected-amount" class="form-control text-success fw-bold" style="font-size:16px;"> 0.00 </div>
                    </div>
                </div>
            </div>
            @endif
        </form>
    </div>
</section>
@endsection
@push('scripts')
<script>
    $(function() {
        function calculateSelectedAmount() {
            let total = 0;
            $('.commission-checkbox:checked').each(function() {
                let amount = parseFloat($(this).data('amount')) || 0;
                total += amount;
            });
            $('#selected-amount').text(total.toFixed(2));
        }
        calculateSelectedAmount();
        $('#select-all').on('change', function() {
            $('.commission-checkbox').prop('checked', this.checked);
            calculateSelectedAmount();
        });
        $('.commission-checkbox').on('change', function() {
            calculateSelectedAmount();
            $('#select-all').prop('checked', $('.commission-checkbox:checked').length === $('.commission-checkbox').length);
        });
    });

    function confirmPayment() {
        const count = $('.commission-checkbox:checked').length;
        const amount = $('#selected-amount').text();
        if (!count) {
            alert('Please select at least one commission');
            return false;
        }
        return confirm(`Pay ${count} commission(s) with total amount: ${amount}?`);
    }
</script>
@endpush