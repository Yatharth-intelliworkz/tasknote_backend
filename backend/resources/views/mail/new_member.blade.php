<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Completed</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
</head>

<body>
    <div style="margin: 0 auto; 
                 padding: 38px 30px 44px 49px; 
                max-width: 600px;">

        <div style=" background-color: white;text-align: center">
            <img src="https://app.tasknote.in/backend/public/mail_files/logo.png" width="198px" alt="" />
        </div>
        <div style=" background-color: white;text-align: center;margin-top: 20px;">
            <img src="https://app.tasknote.in/backend/public/mail_files/group.png" width="540px" alt="" />
        </div>

        <div style="padding-left: 40px;">
            <h1 style="font-family: Poppins;
                    font-size: 16px;
                     font-weight: 600;
                     width: 100%;
                     margin-top: 32px;
                     margin-bottom: 13px;
                      ">Hi {{ $info['mainName'] }},
            </h1>
            <p style="color: #333;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
            width: 535px;">
                You have been added as a member by <b>{{ $info['createdBy'] }}</b>. Below are the details
            </p>
			<p style="color: #333;
            font-family: Poppins;
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 22px;
            width: 535px;">
                Please find your login ID and password to access and experience the beauty of our TaskNote.
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
                            Name :</td>
                        <td style="border: 1px solid #DDD;border-top-right-radius: 10px;padding: 9px 0 8px 21px;">
                        {{ $info['mainName'] }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr style=" border: 1px solid #DDD;">
                        <td style=" border: 1px solid #DDD; padding: 9px 0 8px 24px;">Email :</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['email'] }}</td>
                    </tr>
                    
                    
                </tbody>
                <tbody>
                    <tr style=" border: 1px solid #DDD;">
                        <td style=" border: 1px solid #DDD; padding: 9px 0 8px 24px;">Password :</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['password'] }}</td>
                    </tr>
                    
                    
                </tbody>
                <tfoot>
                    <tr>
                        <td style=" border: 1px solid #DDD;padding:9px 0 8px 21px;">Mobile Number :</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['phone_no'] }}</td>
                    </tr>
                </tfoot>
            </table>
            <p style="width: 500px;
                    color: #333;
                    font-family: Poppins;
                    font-size: 14px;
                    font-style: normal;
                    font-weight: 400;
                    line-height: 22px;
                    margin-top: 30px;
                    margin-bottom: 49px;">
                If you encounter difficulties viewing tasks or experience any issues, please contact us via email at:
                <a style="color: #1C1F66;
             font-family: Poppins;
             font-size: 14px;
             font-style: normal;
             font-weight: 600;
             line-height: 22px;
             text-decoration: none;
              " href="maito:hello@tasknote.in">hello@tasknote.in</a>
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