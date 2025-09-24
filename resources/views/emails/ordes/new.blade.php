<!doctype html>
<html>
<head><meta charset="utf-8"></head>
<body>
  <h2>New Order #{{ $order->id }}</h2>
  <p>Product: {{ $order->product->title }}</p>
  <p>Buyer: {{ $order->buyer_name }} ({{ $order->buyer_phone }})</p>
  <p>Message: {{ $order->message }}</p>
  <p>View in admin: {{ config('app.url') }}/admin/orders/{{ $order->id }}</p>
</body>
</html>
