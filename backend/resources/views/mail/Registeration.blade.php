<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registeration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
</head>

<body>
    <div style="margin: 0 auto;
                padding: 38px 30px 44px 30px; 
                max-width: 600px;">
        <div style=" background-color: white;
         text-align: center;
         ">
            <img src="https://app.tasknote.in/backend/public/mail_files/logo.png" width="198px" alt="" />
        </div>

        <div style=" background-color: white; text-align: center;margin-top: 20px;">
            <img src="https://app.tasknote.in/backend/public/mail_files/group.png" width="540px" alt="" />
        </div>

        <div style="margin-left: 50px;">

            <p style="color: #333;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
            width: 500px;">
                Thank you for Registration.
            </p>

            <p style="width: 500px;
                    color: #333;
                    font-family: Poppins;
                    font-size: 14px;
                    font-style: normal;
                    font-weight: 400;
                    line-height: 22px;
                    margin-bottom: 49px;">
                In case you are unable to access your account or face any problems please email us on 
                <a style="color: #1C1F66;
             font-family: Poppins;
             font-size: 14px;
             font-style: normal;
             font-weight: 600;
             line-height: 22px;
             text-decoration: none;
              " href="mailto:hello@tasknote.in">hello@tasknote.in</a>
            </p>
            <table style="border-radius: 10px;
                        border: 1px solid #DDD;
                        border-spacing: 0px;       
                        overflow: hidden;
                        color: #333;
                        font-family: Poppins;
                        font-size: 12px;
                        font-style: normal;
                        font-weight: 400;
                        line-height: 22px;
                        margin-bottom: 30px;
                        width: 100%;
                        ">
                <thead>

                    <tr>
                        <td style="border: 1px solid #DDD;border-top-left-radius: 10px;padding: 9px 0 8px 24px;width: 40%;">
                            User Name :</td>
                        <td style="border: 1px solid #DDD;border-top-right-radius: 10px;padding: 9px 0 8px 21px;">{{ $info['name'] }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr style=" border: 1px solid #DDD;">
                        <td style=" border: 1px solid #DDD; padding: 9px 0 8px 24px;">Email :</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['email'] }}</td>
                    </tr>
                    
                    
                </tbody>
                <tfoot>
                    <tr>
                        <td style=" border: 1px solid #DDD;padding:9px 0 8px 21px;">Mobile Number :</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['phone_no'] }}</td>
                    </tr>
                </tfoot>
            </table>

            <h1 style="color: #333;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
            margin-top: 50px;
            margin-bottom: 0;
               ">Thanks,</h1>
            <h1 style="color: #111;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 600;
            line-height: 22px;
            margin-top: 0;
              ">Tasknote Team</h1>


        </div>



    </div>

</body>

</html>