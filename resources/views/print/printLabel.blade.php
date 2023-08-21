<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Print - Label IMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        td {
            padding: 1px !important;
        }

        html,
        body {
            width: 21cm;
            margin-left: .1cm;
        }

        @page {
            size: A4;
            margin: 0.5cm 0.2cm;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
                color: #000;
                background-color: #fff;
            }

            .col-6,
            .items {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="row">
        @foreach ($items as $item)
            @php($qrData = $item->material_stock->material->number . '#' . $item->material_stock->material->name . '#' . $item->purchase_order->number . '#' . $item->material_stock->project->code . '#' . $item->material_stock->warehouse->code)
            <div class="col-6 p-1">
                <div class="d-flex align-items-center border items">
                    <div class="text-center">
                        <img src="{{ (new \chillerlan\QRCode\QRCode())->render($qrData) }}" style="height: 135px;width: auto;">
                    </div>
                    <div class="border-start" style="font-size:14px;width:100%">
                        <div class="text-center">
                            <h5>LABEL MATERIAL</h5>
                        </div>
                        <div>
                            <table class="table table-bordered m-0">
                                <tr>
                                    <td nowrap class="border-start-0">
                                        PO Number :
                                    </td>
                                    <td class="text-center border-end-0">
                                        {{ $item->purchase_order->number }}
                                    </td>
                                </tr>
                                <tr>
                                    <td nowrap class="border-start-0">
                                        Warehouse ID :
                                    </td>
                                    <td class="text-center border-end-0">
                                        {{ $item->material_stock->warehouse->code }}
                                    </td>
                                </tr>
                                <tr>
                                    <td nowrap class="border-start-0">
                                        Project Code :
                                    </td>
                                    <td class="text-center border-end-0">
                                        {{ $item->material_stock->project->code }}
                                    </td>
                                </tr>
                                <tr>
                                    <td nowrap class="border-start-0">
                                        Material Number :
                                    </td>
                                    <td class="text-center border-end-0">
                                        {{ $item->material_stock->material->number }}
                                    </td>
                                </tr>
                                <tr class="border-bottom-0">
                                    <td nowrap class="border-start-0">
                                        Material Name :
                                    </td>
                                    <td class="text-center border-end-0">
                                        {{ substr($item->material_stock->material->name, 0, 20) }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <script>
        window.addEventListener('load', function() {
            window.print();
            setTimeout(() => {
                window.close();
            }, 2000);
        });
    </script>
</body>

</html>
