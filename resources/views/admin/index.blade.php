@extends('admin.layouts.admin')

@section('title', trans('cashfreepayment::admin.title'))

@section('content')
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.settings.update', 'payments') }}" method="POST">
                @csrf

                <h3>Cashfree API Credentials</h3>

                <div class="mb-3">
                    <label for="app-id" class="form-label">App ID</label>
                    <input type="text" class="form-control @error('app-id') is-invalid @enderror"
                           id="app-id" name="app-id"
                           value="{{ old('app-id', $gateway->data['app-id'] ?? '') }}" required>
                    @error('app-id')
                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="secret-key" class="form-label">Secret Key</label>
                    <input type="password" class="form-control @error('secret-key') is-invalid @enderror"
                           id="secret-key" name="secret-key"
                           value="{{ old('secret-key', $gateway->data['secret-key'] ?? '') }}" required>
                    @error('secret-key')
                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <h3>Exchange Rate API</h3>

                <div class="mb-3">
                    <label for="exchange-rate-api-key" class="form-label">Exchange Rate API Key</label>
                    <input type="password" class="form-control @error('exchange-rate-api-key') is-invalid @enderror"
                           id="exchange-rate-api-key" name="exchange-rate-api-key"
                           value="{{ old('exchange-rate-api-key', $gateway->data['exchange-rate-api-key'] ?? '') }}">
                    @error('exchange-rate-api-key')
                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    {{ trans('messages.actions.save') }}
                </button>
            </form>
        </div>
    </div>
@endsection
