<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
        <div style=" background-color: white; text-align: center;">
            <img src="https://app.tasknote.in/backend/public/mail_files/logo.png" width="198px" alt="" />
        </div>

        <div style=" background-color: white; text-align: center;margin-top: 20px;">
            <img src="https://app.tasknote.in/backend/public/mail_files/group.png" width="540px" alt="" />
        </div>

        <div style="margin-left: 50px;">
            <h1 style="font-family: Poppins;
            font-size: 16px;
             font-weight: 600;
             width: 100%;
             margin-top: 32px;
             margin-bottom: 13px;">
             Hi {{ $info['userName'] }},
            </h1>

            <p style="color: #333;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
            width: 500px;">
                You are receiving this email because a request was made to reset the password for your account. 
				If you did not make this request, please contact us immediately.
            </p>
			<p style="color: #333;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
            width: 500px;">
                Your password has been reset successfully. Your new password is : <b>{{ $info['password'] }}</b>
            </p>
            <p style="width: 500px;
                    color: #333;
                    font-family: Poppins;
                    font-size: 14px;
                    font-style: normal;
                    font-weight: 400;
                    line-height: 22px;
                    margin-bottom: 49px;">
                If you encounter any issues with signing in after following these steps or if you have any concerns
                regarding your account, please don't hesitate to reach out to us via email at:
                <a style="color: #1C1F66;
             font-family: Poppins;
             font-size: 14px;
             font-style: normal;
             font-weight: 600;
             line-height: 22px;
             text-decoration: none;
              " href="mailto:hello@tasknote.in">hello@tasknote.in</a>
            </p>

            <h1 style="color: #333;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
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