<div id="clearDueModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('supplier.clearDue') }}">
                @csrf
                <div class="modal-header">
                    <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Clear Due')}}</h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('Remaining Dues')}}</label>
                                <input type="text" name="paying_amount" class="form-control numkey" step="any" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="hidden" name="supplier_id">
                                <input type="text" name="change" value="0" id="change">
                                <label>{{trans('Paying Amount')}} *</label>
                                <input type="number" name="amount" step="any" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mt-1">
                            <label>{{trans('Remaining Dues After Paying')}} : </label>
                            <p class="change ml-2">{{number_format(0, $general_setting->decimal, '.', '')}}</p>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Paid By')}}</label>
                                <select name="paying_method" class="form-control">
                                    <option value="Account Transfer">Account Transfer</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Easy Pasa">Easy Pasa</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div id="cheque" style="display: none;">
                                <div class="form-group">
                                    <label>{{trans('Cheque Number')}} *</label>
                                    <input type="text" name="cheque_no" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Account')}}</label>
                                <select class="form-control selectpicker" name="account_id">
                                    @foreach($lims_account_list as $account)
                                    @if($account->is_default)
                                    <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @else
                                    <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                    @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{trans('file.Date')}}</label>
                                <input type="date" name="created_at" value="" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{trans('file.Note')}}</label>
                                <textarea name="note" rows="4" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary" id="submit-button">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>