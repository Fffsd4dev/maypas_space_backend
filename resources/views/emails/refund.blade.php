<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Refund Confirmation</title>

    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            color:#333;
            font-size:13px;
            line-height:1.5;
        }

        .container{
            width:100%;
            border:1px solid #ddd;
            padding:25px;
        }

        .header{
            margin-bottom:20px;
        }

        .table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        .table th,
        .table td{
            border:1px solid #ddd;
            padding:10px;
            text-align:left;
        }

        .table th{
            background:#f5f5f5;
        }

        .text-right{
            text-align:right;
        }

        .total{
            margin-top:20px;
            font-size:15px;
            font-weight:bold;
        }

        .footer{
            margin-top:35px;
            font-size:12px;
            color:#666;
        }
    </style>

</head>

<body>

<div class="container">

    <div class="header">
        <h2>{{ $invoice['company_name'] }}</h2>
        <h3>Refund Confirmation</h3>

        <p>
            Refund Invoice:
            <strong>{{ $invoice['invoice_ref'] }}</strong>
        </p>

        <p>
            Date:
            {{ now()->format('F d, Y h:i A') }}
        </p>
    </div>

    <p>Hello <strong>{{ $invoice['customer_name'] }}</strong>,</p>

    <p>
        Your refund has been processed successfully.
    </p>

    <table class="table">

        <tr>
            <th>Space</th>
            <td>{{ $invoice['space_name'] }}</td>
        </tr>

        <tr>
            <th>Category</th>
            <td>{{ $invoice['space_category'] }}</td>
        </tr>

        <tr>
            <th>Booking Type</th>
            <td>{{ ucfirst($invoice['booking_type']) }}</td>
        </tr>

        <tr>
            <th>Original Space Fee</th>
            <td>&#8358;{{ number_format($invoice['space_fee'],2) }}</td>
        </tr>

        <tr>
            <th>Total Refund</th>
            <td>
                <strong>
                    &#8358;{{ number_format($invoice['refund_amount'],2) }}
                </strong>
            </td>
        </tr>

    </table>

    @if(!empty($invoice['refunded_items']))

        <h3>Refunded Items</h3>

        <table class="table">

            <thead>

            <tr>
                <th>Payment</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>

            </thead>

            <tbody>

            @foreach($invoice['refunded_items'] as $item)

                <tr>

                    <td>{{ $item['payment_name'] }}</td>

                    <td>
                        &#8358;{{ number_format($item['fee'],2) }}
                    </td>

                    <td>
                        {{ ucfirst($item['payment_status']) }}
                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    @endif

    <div class="footer">

        <p>
            If you have any questions regarding this refund, kindly contact
            <strong>{{ $invoice['company_name'] }}</strong>.
        </p>

        <p>
            This refund confirmation was generated automatically.
        </p>

        <p>
            Thank you for choosing {{ $invoice['company_name'] }}.
        </p>

    </div>

</div>

</body>
</html>