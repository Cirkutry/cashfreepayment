@extends('layouts.app')

@section('title', trans('shop::messages.payment.title'))

@section('content')
<div class="container content">
    <h1>{{ trans('shop::messages.payment.title') }}</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <p class="mb-3">{{ trans('cashfreepayment::messages.payment.info') }}</p>
                   
                    <div class="d-flex justify-content-center my-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                   
                    <p>{{ trans('cashfreepayment::messages.payment.processing') }}</p>
                   
                    <div id="payment-container">
                        <button id="renderBtn" class="btn btn-primary" style="display: none;">Pay Now</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cashfree = Cashfree({
                mode: "{{ $cashfreeMode }}",
            });
            
            const checkoutOptions = {
                paymentSessionId: "{{ $paymentSessionId }}",
                redirectTarget: "_self",
            };
            
            // Auto-initialize payment
            setTimeout(function() {
                cashfree.checkout(checkoutOptions);
            }, 1000);
            
            // Fallback button in case auto-redirect fails
            setTimeout(function() {
                document.getElementById('renderBtn').style.display = 'inline-block';
                document.querySelector('.spinner-border').style.display = 'none';
            }, 3000);
            
            document.getElementById("renderBtn").addEventListener("click", () => {
                cashfree.checkout(checkoutOptions);
            });
        });
    </script>
@endpush
@endsection
