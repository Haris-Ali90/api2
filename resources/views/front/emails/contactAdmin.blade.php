<p><b>Dear Admin,</b></p>

<p>You have received contact query user details are given bellow:</p>

<p>
	<strong>Name:</strong> 		{{ $data['name'] }} <br/>
	<strong>Email:</strong> 	{{ $data['email'] }} <br/>
	<strong>Phone:</strong> 	@if(isset($data['phone'])){{ $data['phone'] }} @endif <br/>
	<strong>Message:</strong> 	{!! $data['description'] !!} <br/>
</p>

<p>Thanks,</p>
<p>
    <b>Best Regards,</b>
    <br />
    {{ $siteSettings->site_title }}
</p>
