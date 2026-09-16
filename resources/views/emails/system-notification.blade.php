<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }}</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f5f7fb;
    font-family: Arial, Helvetica, sans-serif;
    color: #1e2740;
">
    <div style="padding: 40px 20px;">

        <div style="
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
        ">

            <div style="
                padding: 24px 30px;
                background-color: #4f46e5;
                color: #ffffff;
            ">
                <h2 style="margin: 0;">
                    Entouche Inventory
                </h2>
            </div>

            <div style="padding: 30px;">

                <h2 style="
                    margin-top: 0;
                    margin-bottom: 16px;
                    color: #1e2740;
                ">
                    {{ $title }}
                </h2>

                <p style="
                    margin: 0;
                    font-size: 15px;
                    line-height: 1.7;
                    color: #5f6b85;
                ">
                    {{ $messageText }}
                </p>

            </div>

            <div style="
                border-top: 1px solid #eeeeee;
                padding: 20px 30px;
            ">
                <p style="
                    margin: 0;
                    font-size: 12px;
                    color: #9ca3af;
                ">
                    This is an automated notification from
                    {{ config('app.name') }}.
                </p>
            </div>

        </div>

    </div>
</body>

</html>