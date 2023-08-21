<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.=w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="margin: 0; padding: 0;">

<head>
    <meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width, height=device-height">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:300,400,500,700|Google+Sans:400,500,700">
</head>

<body bgcolor="#FFFFFF" style="font-family:'Roboto', sans-serif;width:100%!important;height:100%;font-size:14px;color:#404040;margin:0;padding:0">
    <table style="max-width:100%;border-collapse:collapse;border-spacing:0;width:100%;background-color:transparent;margin:0;padding:0" bgcolor="transparent">
        <tbody>
            <tr style="margin:0;padding:0">
                <td style="margin:0;padding:0"></td>
                <td style="display:block!important;max-width:720px!important;clear:both!important;margin:0 auto;padding:0" bgcolor="#FFFFFF">
                    <div style="max-width:100%;display:block;border-collapse:collapse;margin:0 auto;border:1px solid #e7e7e7">
                        <div style="padding:20px 22px;">
                            <div style="text-align: justify;margin-top:20px;">
                                <p>
                                    Hi {{ $name }},<br>
                                    Here is information about materials that has reached the stock alert.
                                </p>
                                @foreach ($datas as $data)
                                    <h3>In {{ $data['type'] }} warehouse</h3>
                                    <table border="1" cellpadding="5" style="border-collapse:collapse;width:100%;">
                                        <thead>
                                            <tr>
                                                <th>Warehouse name</th>
                                                <th>Material Number</th>
                                                <th>Material Name</th>
                                                <th>Minimun Stock In {{ $data['type'] }} WH</th>
                                                <th>Current Stock</th>
                                                <th>UoM</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data['items'] as $item)
                                                @foreach ($item['warehouse'] as $wh)
                                                    <tr>
                                                        <td>{{ $wh['name'] }}</td>
                                                        <td>{{ $item['number'] }}</td>
                                                        <td>{{ $item['name'] }}</td>
                                                        <td>{{ $item['minimum_stock'][$data['type']] ?? 0 }}</td>
                                                        <td>{{ $wh['stock'] }}</td>
                                                        <td>{{ $item['uom'] }}</td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <br>
                                @endforeach
                                <div style="text-align: center">
                                    <a target="_blank" href="{{ config('app.url') }}" style="color:#fff;background: radial-gradient(62.98% 37.5% at 50% 50%, #056CA2 0%, #0F366C 100%);background-color:#0F366C;padding:10px 15px;text-decoration: none;border-radius:5px;">
                                        For more information click here
                                    </a>
                                </div>
                                <br>
                            </div>
                        </div>
                        <div style="padding:10px 20px">
                            <p style="font-size:12px;color:#999;padding:10px 0;margin:0;border-top:1px solid #e0e0e0">
                                This email is generated automatically. Please do not send replies to this email.
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
