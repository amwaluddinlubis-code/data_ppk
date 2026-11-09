<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
    <style>

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 12px;
            line-height: 16px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-spacing: 0;
        }

        .invoice-box table td {
            padding: 0;
            vertical-align: middle;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: center;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 0;
        }

        .invoice-box table tr.top table td>.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr>.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr>.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td{
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item>.last td {
            border-bottom: none;
        }

        .invoice-box table tr>.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }
        .text-c {
            text-align: center;
        }
        .text-r {
             text-align: right;
         }

        /** RTL **/
        .rtl {
            direction: rtl;
            font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        }

        .rtl table {
            text-align: right;
        }

        .rtl table tr td:nth-child(2) {
            text-align: left;
        }

        /** Bootstraps Table CSS **/
        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 1rem;
        }

        .table th,
        .table td {
            padding: 0.1rem;
            vertical-align: middle;
            border-top: 1px solid #eceeef;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #eceeef;
        }

        .table tbody + tbody {
            border-top: 2px solid #eceeef;
        }

        .table .table {
            background-color: #fff;
        }

        .table-sm th,
        .table-sm td {
            padding: 0.1rem 0.8rem;
        }

        .table-bordered {
            border: 1px solid #eceeef;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #eceeef;
        }

        .table-bordered thead th,
        .table-bordered thead td {
            border-bottom-width: 2px;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .table-active,
        .table-active > th,
        .table-active > td {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .table-hover .table-active:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .table-hover .table-active:hover > td,
        .table-hover .table-active:hover > th {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .table-success,
        .table-success > th,
        .table-success > td {
            background-color: #dff0d8;
        }

        .table-hover .table-success:hover {
            background-color: #d0e9c6;
        }

        .table-hover .table-success:hover > td,
        .table-hover .table-success:hover > th {
            background-color: #d0e9c6;
        }

        .table-info,
        .table-info > th,
        .table-info > td {
            background-color: #d9edf7;
        }

        .table-hover .table-info:hover {
            background-color: #c4e3f3;
        }

        .table-hover .table-info:hover > td,
        .table-hover .table-info:hover > th {
            background-color: #c4e3f3;
        }

        .table-warning,
        .table-warning > th,
        .table-warning > td {
            background-color: #fcf8e3;
        }

        .table-hover .table-warning:hover {
            background-color: #faf2cc;
        }

        .table-hover .table-warning:hover > td,
        .table-hover .table-warning:hover > th {
            background-color: #faf2cc;
        }

        .table-danger,
        .table-danger > th,
        .table-danger > td {
            background-color: #f2dede;
        }

        .table-hover .table-danger:hover {
            background-color: #ebcccc;
        }

        .table-hover .table-danger:hover > td,
        .table-hover .table-danger:hover > th {
            background-color: #ebcccc;
        }

        .thead-inverse th {
            color: #fff;
            background-color: #292b2c;
        }

        .thead-default th {
            color: #464a4c;
            background-color: #eceeef;
        }

        .table-inverse {
            color: #fff;
            background-color: #292b2c;
        }

        .table-inverse th,
        .table-inverse td,
        .table-inverse thead th {
            border-color: #fff;
        }

        .table-inverse.table-bordered {
            border: 0;
        }

        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -ms-overflow-style: -ms-autohiding-scrollbar;
        }

        .table-responsive.table-bordered {
            border: 0;
        }
    </style>



</head>
<body>
<!-- Site wrapper -->
<div class="container">

    <div class="invoice-box">
        <div>
            <h3><?= $title?></h3>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                <tr >
                    <th width="180px">PROVINSI</th>
                    <th>: SUMATERA UTARA</th>
                </tr>
                <tr>
                    <th>KABUPATEN/KOTA</th>
                    <th>: MANDAILING NATAL</th>
                </tr>
                <tr>
                    <th>KECAMATAN</th>
                    <th>: RANTO BAEK</th>
                </tr>
                <tr>
                    <th>DESA / KELURAHAN</th>
                    <th>: <?= $desa?></th>
                </tr>

                <tr>
                    <th>TPS</th>
                    <th>: 00<?= $tps?></th>
                </tr>
                </thead>
            </table>
            <table class="table table-striped table-sm table-condensed">
                <thead class="thead-light table-head-fixed">
                <tr>
                    <th class="text-c" width="30px">NO</th>
                    <th class="text-c">NKK</th>
                    <th class="text-c">NAMA</th>
                    <th class="text-c">L/P</th>
                    <th class="text-c">UMUR</th>
                    <th class="text-c">STATUS</th>
                    <th class="text-c">KET</th>
                </tr>
                </thead>

                <tbody>
                <?php $i = 0 ?>
                <?php foreach ($dtTable as $pms) { ?>
                    <?= "<tr>" ?>
                    <?= "<th class='text-r'>" . $i += 1 ?>
                    <?= "<td class='text-c'>" . $pms['NKKENCODE'] . "</td>" ?>
                    <?= "<td>" . $pms['NAMA'] . "</td>" ?>
                    <?= "<td class='text-c'>" . $pms['JK'] . "</td>" ?>
                    <?= "<td class='text-c'>" . $pms['UMUR'] . " THN</td>" ?>
                    <?= "<td class='text-c'>" . $pms['STATUS'] . " </td>" ?>
                    <?= "<td class='text-c'>" . $pms['KET'] . " </td>" ?>

                    <?= "</tr>" ?>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
