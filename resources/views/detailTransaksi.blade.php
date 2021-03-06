@extends('Template')

@section('Title')
Detail Transaksi
@endsection

@section('Content')
@include('Navbar')
<div class="jumbotron" style="border: 1px solid black;width:90%;margin:auto;margin-top:1%;background-color:white">
    @php
        $subtotal = 0;
    @endphp
    <h2 align='center'>Detail Order</h2>
    <form action="/History">
        <button class="btn btn-info">Back To History Transaksi</button>
    </form><br>
    <table class='table display' id='displayDetail'>
        <thead class=''>
            <td>Nama Baju</td>
            <td>Harga</td>
            <td>Jumlah</td>
            <td>Subtotal</td>
        </thead>
        <tbody>
            @foreach ($data['trans'] as $item)
                <tr>
                    <td>{{$item->nama_barang}}</td>
                    <td>{{"Rp. ".number_format($item->harga,0,',','.')}}</td>
                    <td>{{$item->qty}}</td>
                    <td>{{"Rp. ".number_format($item->subtotal,0,',','.')}}</td>
                    @php
                        $subtotal = $subtotal + $item->subtotal;
                    @endphp
                </tr>
            @endforeach
        </tbody>
    </table>
    <h4>Sub Total : {{"Rp. ".number_format($subtotal,0,',','.')}}</h4>
    <h4>Biaya Ongkir : {{"Rp. " . number_format(DB::table('h_jual')->where('id_hjual',$item->id_hjual)->value('grand_total')+DB::table('h_jual')->where('id_hjual',$item->id_hjual)->value('diskon') - $subtotal)}}</h3>
    <h4>Discount : {{"Rp. ".number_format(DB::table('h_jual')->where('id_hjual',$item->id_hjual)->value('diskon'),0,',','.')}}</h4>
    <h3>Grand Total : {{"Rp. ".number_format(DB::table('h_jual')->where('id_hjual',$item->id_hjual)->value('grand_total'),0,',','.')}}</h4>
</div>
<script>
    $(document).ready(function(){
        $("#displayDetail").DataTable();
    })
</script>
@endsection
