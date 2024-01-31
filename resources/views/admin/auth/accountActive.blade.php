<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100,300;400;600;700&display=swap');
        body{font-family: Poppins, sans-serif; color: #443404; }
        h1, h2, h3, h4, h5, h6{font-family: Poppins, sans-serif; font-weight: 600;}
        h1{font-size: 38px;}
        h3{font-weight: 400;}
        .no-list{list-style: none; margin: 0 ; padding: 0;}
        a{color: #e46d29;}

        .basecolor1{color: #e46d29;}
        #main{background: url('../img/email/icons-bg.png') repeat-x center top; background-size: 100% auto;}

        #logo{padding-top: 60px;}
        #logo img{max-height: 80px;}
        #logo:after{content:""; display: block; width: 40px; height: 3px; background: #f6f2ef; margin: 12px auto 20px;}

        .page-thankyou{ max-width: 760px; margin: 0 auto; padding: 0 15px; text-align: center; }
        .page-thankyou .thankyou_icon{margin: 0 auto;}
        .page-thankyou .thankyou_icon img{max-height: 140px;}
        .page-thankyou .thankyou_icon .cnt{}
        .page-thankyou .hgroup{}
        .page-thankyou .hgroup:after{content:""; display: block; width: 40px; height: 3px; background: #f6f2ef; margin: 12px auto 20px;}
        .page-thankyou .hgroup h1{color: #e46d29; margin: 10px 0 10px;}
        .page-thankyou .hgroup h3{margin: 0;}

        .page-thankyou .cnt h4{margin: 0 0 8px;}
        .page-thankyou .company_details{background:#faf7f5; border-radius: 10px;}
        .page-thankyou .company_details li{ margin: 0; padding: 0; padding: 12px 10px; }
        .page-thankyou .company_details li:not(:last-child){border-bottom: solid 1px #f2ece9;}
    </style>
</head>
<body>
<main id="main">
    <div class="page-thankyou">



        <div id="logo"><img src="     {!! asset('assets/admin/img/email/logo.png') !!}" alt=""></div>
        <div class="thankyou_icon">
            <img src="  {!! asset('assets/admin/img/email/success_icon.png') !!}" alt="">

        </div>
        <div class="hgroup">
            <h1>Thank you</h1>
            <h3>Your request is under review, we will get back to you shortly.</h3>
        </div>

        <div class="cnt">
            <h4 class="basecolor1">Get in touch with us</h4>
            <ul class="no-list company_details">
                <!-- <li>7075 Tomken Rd Mississauga, ON L5S 1R7</li> -->
                <li><a href="mailto:support@joeyco.com">joey@joeyco.com</a></li>
                <li><a href="tel:+18559090053">1(855) 909-0053</a></li>
            </ul>
        </div>
    </div>
</main>
</body>
</html>