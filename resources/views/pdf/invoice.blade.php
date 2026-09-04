<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>فاتورة</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; }
    .header { text-align: center; margin-bottom: 20px; }
    .items { width: 100%; border-collapse: collapse; }
    .items th, .items td { border: 1px solid #ddd; padding: 8px; }
  </style>
</head>
<body>
  <div class="header">
    <h1>فاتورة</h1>
  </div>

  <p>العميل: {{ $name ?? 'عميل' }}</p>
  <p>التاريخ: {{ $date ?? now()->toDateString() }}</p>

  <table class="items">
    <thead>
      <tr>
        <th>المنتج</th>
        <th>الكمية</th>
        <th>السعر</th>
      </tr>
    </thead>
    <tbody>
    @foreach($items ?? [] as $item)
      <tr>
        <td>{{ $item['name'] }}</td>
        <td>{{ $item['qty'] }}</td>
        <td>{{ $item['price'] }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <p>المجموع: {{ $total ?? '0.00' }}</p>
</body>
</html>
