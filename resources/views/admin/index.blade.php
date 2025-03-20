@extends('admin.layouts.admin')

@section('title', trans('cashfreepayment::admin.title'))

@section('content')
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.settings.update', 'payments') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="mb-3 col-md-6">
                        <label class="form-label" for="currencySelect">{{ trans('shop::messages.fields.currency') }}</label>
                        <select class="form-select @error('currency') is-invalid @enderror" id="currencySelect" name="currency">
                            @foreach($currencies as $code => $currency)
                                <option value="{{ $code }}" @selected($code === ($gateway->data['currency'] ?? setting('shop.currency', 'USD')))>
                                    {{ $code }} - {{ $currency }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h3>Cashfree API Credentials</h3>

                <div class="mb-3">
                    <label class="form-label" for="appIdInput">App ID</label>
                    <input type="text" class="form-control @error('app-id') is-invalid @enderror" id="appIdInput" name="cashfree-gateway[app-id]" value="{{ $gateway->data['app-id'] ?? '' }}">
                    
                    @error('app-id')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="secretKeyInput">Secret Key</label>
                    <input type="password" class="form-control @error('secret-key') is-invalid @enderror" id="secretKeyInput" name="cashfree-gateway[secret-key]" value="{{ $gateway->data['secret-key'] ?? '' }}">
                    
                    @error('secret-key')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <h3>Currency Conversion</h3>
                <p class="text-muted">Cashfree primarily supports INR currency. If you need to use other currencies, please provide an Exchange Rate API key below.</p>

                <div class="mb-3">
                    <label class="form-label" for="exchangeRateApiKeyInput">Exchange Rate API Key</label>
                    <input type="password" class="form-control @error('exchange-rate-api-key') is-invalid @enderror" id="exchangeRateApiKeyInput" name="cashfree-gateway[exchange-rate-api-key]" value="{{ $gateway->data['exchange-rate-api-key'] ?? '' }}">
                    <small class="form-text text-muted">Sign up for a free API key at <a href="https://www.exchangerate-api.com/" target="_blank">exchangerate-api.com</a></small>
                    
                    @error('exchange-rate-api-key')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> {{ trans('messages.actions.save') }}
                </button>
            </form>
        </div>
    </div>
@endsection
