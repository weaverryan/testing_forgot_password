<h1>Hi!</h1>

<p>
    To reset your password, please visit
    <a href="{{ url('app_reset_password', {tokenAndSelector: resetToken.token}) }}">here</a>
    This link will expire in {{ resetToken.ExpiresAt|date('Y-m-d') }}TODO - convert to hours! hours.
</p>

<p>
    Cheers!
</p>