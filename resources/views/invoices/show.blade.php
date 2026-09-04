<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #0a0f9a; padding: 8px; }
        th { background: #f3f3f3; }
    </style>
</head>
    <body>
        <div class="header">
            <h1>#{{$order->id}}</h1>
            <p>Date: {{$order->created_at->format("Y-m-d")}}</p>
        </div>
        <p><strong>Customer:</strong>{{$order->user->name ?? "N\A"}}</p>
        <p><strong>Email:</strong>{{$order->user->Email ?? "N\A"}}</p>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->meal->name ?? 'Item' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->price }}</td>
                    <td>{{ $item->quantity * $item->price }}</td>
                </tr>
            @endforeach 
            </tbody>
        </table>
        <h3>Total: {{$order->total_amount}}</h3>
    </body>
</html>>