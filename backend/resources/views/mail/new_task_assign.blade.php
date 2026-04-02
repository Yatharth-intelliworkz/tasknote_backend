<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Task Assign</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
</head>

<body>
    <div style="margin: 0 auto; 
                 padding: 0; 
                max-width: 600px;">

        <div style=" background-color: white;text-align: center">
            <img src="https://app.tasknote.in/backend/public/mail_files/logo.png" width="198px" alt="" />
        </div>
        <div style=" background-color: white;text-align: center;margin-top: 20px;">
            <img src="https://app.tasknote.in/backend/public/mail_files/group.png" width="100%" alt="" />
        </div>

        <div >
            <h1 style="font-family: Poppins;
                    font-size: 16px;
                     font-weight: 600;
                     width: 100%;
                     margin-top: 32px;
                     margin-bottom: 13px;
                      ">Hi Team,
            </h1>
            <p style="font-family: Poppins;
                    font-size: 14px;
                    width: 500px;
                    margin-bottom: 24px;
                    ">You've been assigned a new task as detailed below, <b>created by {{ $info['created'] }}.</b>
            </p>
            <div class="table-responsive">
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
                        <td style="border: 1px solid #DDD;border-top-left-radius: 10px;padding: 9px 0 8px 24px;width: 40%;">Task
                            Name</td>
                        <td style="border: 1px solid #DDD;border-top-right-radius: 10px;padding: 9px 0 8px 21px;">
                        {{ $info['taskName'] }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr style=" border: 1px solid #DDD;">
                        <td style=" border: 1px solid #DDD; padding: 10px 0 8px 24px;">Created By</td>
                        <td style=" border: 1px solid #DDD;padding: 10px 0 8px 21px;">{{ $info['created'] }}</td>
                    </tr>
                    <tr>
                        <td style=" border: 1px solid #DDD;padding:9px 0 8px 21px;">Due Date</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['dueDate'] }}</td>
                    </tr>
                    <tr>
                        <td style=" border: 1px solid #DDD;padding:9px 0 8px 21px;">Assigned To</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['assignName'] }}</td>
                    </tr>
                    <tr>
                        <td style=" border: 1px solid #DDD;padding:9px 0 8px 21px;">Priority</td>
                        <td style=" border: 1px solid #DDD;padding: 9px 0 8px 21px;">{{ $info['taskPriority'] }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td style=" border: 1px solid #DDD;border-bottom-left-radius: 10px;padding:9px 0 8px 21px;">
                            Date
                            and Time</td>
                        <td
                            style=" border: 1px solid #DDD;border-bottom-right-radius: 10px;padding: 9px 0 8px 21px;">
                            {{ $info['cratedDate'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

            <!--<a style="color: #FFF;-->
            <!--font-family: Poppins;-->
            <!--text-decoration: none;-->
            <!--font-size: 14px;-->
            <!--font-weight: 600;-->
            <!--line-height: normal;-->
            <!--border-radius: 5px;-->
            <!--background: #1C1F66;-->
            <!--padding: 8px 19px 7px 19px;-->
            <!--border: none;-->
            <!--margin: 0 0 29px 238px;-->
            <!--" type="button">-->
            <!--    View Task</a>-->

            <p style="width: 500px;
                    color: #333;
                    font-family: Poppins;
                    font-size: 14px;
                    font-style: normal;
                    font-weight: 400;
                    line-height: 22px;
                    margin-bottom: 49px;">
                If you encounter difficulties viewing tasks or experience any issues,
                please contact us via email at:
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