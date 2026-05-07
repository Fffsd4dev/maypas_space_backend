<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Notification</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f6f9fc;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            max-width: 650px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 30px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 120px;
            margin-bottom: 10px;
        }

        .header h2 {
            color: #007bff;
            margin: 5px 0;
        }

        .details {
            margin-bottom: 25px;
        }

        .details p {
            margin: 5px 0;
            font-size: 15px;
        }

        .table-container {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 25px;
        }

        .table-container th, .table-container td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        .table-container th {
            background-color: #007bff;
            color: white;
        }

        .total {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .note {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 30px;
        }

        .social-icons a {
            margin: 0 5px;
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>
    
    @php
        $brand_data = $invoice_data['brand_data'];
        $user = $invoice_data['user'];
        $apartment_unit = $invoice_data['apartment_unit'];
        $invoice_ref = $invoice_data['invoice_ref'];
        $payment_info = $invoice_data['payment_info'];
        $bank_data = $invoice_data['bank_data'];


        $addresses = json_decode($brand_data->addresses ?? '[]', true);
        $phones = json_decode($brand_data->phones ?? '[]', true);
        $social_links = json_decode($brand_data->social_links ?? '[]', true);
    @endphp

    <div class="email-wrapper">
        <div class="header">
            @if(!empty($brand_data->logo))
                <img src="{{ asset($brand_data->logo) }}" alt="{{ $brand_data->name }} Logo">
            @endif
            <h2>{{ $brand_data->name ?? 'Our Company' }}</h2>
            <p><strong>Invoice Reference:</strong> {{ $invoice_ref }}</p>
        </div>

        <div class="details">
            <p><strong>Customer:</strong> {{ $user->first_name }} {{ $user->middle_name ?? '' }} {{ $user->last_name ?? '' }}</p>
            <p><strong>Email:</strong> {{ $user->email ?? 'N/A' }}</p>
            <p><strong>Apartment:</strong> {{ ucfirst($apartment_unit->apartment->name ?? 'N/A') }}</p>
            <p><strong>Location:</strong> {{ ucfirst($apartment_unit->apartment->location ?? 'N/A') }}</p>
            <p><strong>Category:</strong> {{ ucfirst($apartment_unit->apartment->category->name ?? 'N/A') }}</p>
            <p><strong>Date:</strong> {{ now()->format('d M Y') }}</p>
        </div>

        <table class="table-container">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Fee Name</th>
                    <th>Amount (₦)</th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach ($payment_info as $index => $payment)
                    @php $total += $payment['payment_fee']; @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $payment['payment_name'] }}</td>
                        <td>{{ number_format($payment['payment_fee'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="total">Total: ₦{{ number_format($total, 2) }}</p>

        @if($bank_data)
            <hr>
            <p class="note">
                <strong>Payment Instructions:</strong><br>
                Kindly make payment using the provided account details below:
                <br><br>
                <strong>Bank Name:</strong> {{ $bank_data->bank_name ?? 'N/A' }}<br>
                <strong>Account Number:</strong> {{ $bank_data->bank_account_number ?? 'N/A' }}
            </p>
        @endif

        <div class="footer">
            <p>Thank you for choosing <strong>{{ $brand_data->name ?? 'our service' }}</strong>.</p>

            @if(!empty($phones))
                <p>Contact us: {{ implode(', ', $phones) }}</p>
            @endif

            @if(!empty($addresses))
                <p>Address: {{ implode(', ', $addresses) }}</p>
            @endif

            @if(!empty($social_links))
                <div class="social-icons">
                    <p>Follow us:</p>
                  @foreach($social_links as $index => $link)
    <a href="{{ $link }}" target="_blank">Social {{ strtolower(parse_url($link, PHP_URL_HOST)) }}</a>
@endforeach
                </div>
            @endif

            <p>&copy; {{ date('Y') }} {{ $brand_data->name ?? 'Our Company' }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
