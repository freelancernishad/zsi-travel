<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $data->id }}</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 90%;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            /* box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); */
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            color: #000000;
            position: relative; /* Make header container relative */
        }
        .header img {
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 26px;
            margin: 0;
        }
        .header p {
            font-size: 18px;
            margin: 0;
        }

        /* Position QR Code */
        .qr-code {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
        }

        /* Customer and Company Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table th,
        .details-table td {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .details-table th {
            background-color: #f0f4f8;
        }
        .customer-column, .company-column {
            width: 50%;
            padding: 10px;
        }

        /* Table Info */
        .table-info {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        .table-info th, .table-info td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table-info th {
            background-color: #f0f4f8;
        }
        .table-info td {
            font-size: 14px;
        }

        /* Addon Section */
        .addon-section {
            margin: 20px 0;
        }

        /* Price Details */
        .total-row td {
            font-weight: bold;
            font-size: 16px;
        }
        .total-price {
            text-align: right;
            font-size: 16px;
            padding-right: 15px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 10px;
            background-color: #f0f4f8;
            margin-top: 20px;
            color: #666;
        }

        .footer p {
            font-size: 12px;
            margin: 5px 0;
        }

        /* Start and End Dates Styling */
        .package-dates {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        .package-dates div {
            width: 48%;
        }
        .package-dates span {
            font-weight: bold;
        }
        .package-dates p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Header -->
        <div class="header">

            <table width="100%">

                <tr>
                    <td  style="text-align: left">
                        <img src="https://marketing.zsi.ai/_next/image?url=%2FLogo.png&w=256&q=75" width="140px" alt="Company Logo">
                        <h1>Invoice</h1>
                        <p>Invoice #{{ $data->id }}</p>

                    </td>
                    <td style="text-align: right">

                        @php
                            $qrurl = url("package/invoice/$data->id");
                        @endphp
                        <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ $qrurl }}&size=80x80" class="qr-code" alt="QR Code">

                    </td>
                </tr>

            </table>






        </div>

        <!-- Customer and Company Details Table -->
        <table class="details-table">
            <tr>

                <td class="company-column">
                    <h3></h3>
                    <span>Zsi Marketing</span><br>
                    <span>marketing@zsi.ai</span><br>
                    <span>74-09 37th Avenue</span><br>
                    <span>Suite 2038, Jackson Heights</span><br>
                    <span>NY 11372</span>
                </td>

                <td class="customer-column">
                    <h3>Customer Details</h3>
                    @if($data->user->name)
                        <span>{{ $data->user->name }}</span><br>
                    @endif
                    @if($data->user->email)
                        <span>{{ $data->user->email }}</span><br>
                    @endif
                    @if($data->user->phone)
                        <span>{{ $data->user->phone }}</span><br>
                    @endif
                    @if($data->user->city || $data->user->state || $data->user->country)
                        <span>{{ $data->user->city }}, {{ $data->user->state }}, {{ $data->user->country }}</span><br>
                    @endif
                    @if($data->business_name)
                        <span>{{ $data->business_name }}</span>
                    @endif
                </td>



            </tr>
        </table>

        <!-- Package Details -->
        <div class="addon-section">
            <h3>Package Details</h3>
            <table class="table-info">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $data->package->name }}</td>
                        <td>${{ $data->package->price }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Start Date and End Date -->
            <div class="package-dates">
                <div>
                    <p><span>Start Date:</span> {{ \Carbon\Carbon::parse($data->started_at)->format('M d, Y') }}</p>
                </div>
                <div>
                    <p><span>Expired Date:</span> {{ \Carbon\Carbon::parse($data->ends_at)->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <!-- Addons Table -->
        <h3>Add-on</h3>
        <table class="table-info">
            <thead>
                <tr>
                    <th>Add-on Name</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data->addons as $addon)
                    <tr>
                        <td>{{ $addon->addon->addon_name }}</td>
                        <td>${{ $addon->addon->price }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Total Price -->
        <table class="table-info">
            <tr class="total-row">
                <td class="total-price">Total Price:</td>
                <td>${{ $data->package->price + $data->addons->sum(function($addon) { return $addon->addon->price; }) }}</td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions, please contact us at marketing@zsi.ai</p>
        </div>
    </div>

</body>
</html>
