{% extends 'base.html.twig' %}

{% block title %}Reset your password{% endblock %}

{% block body %}
{% for message in app.flashes('reset_password_error') %}
    <section class="flash-error"><p>{{ message }}</p></section>
{% endfor %}
    <h1>Reset your password</h1>

{{ form_start(requestForm) }}
{{ form_row(requestForm.<?= $email_field ?>) }}
    <small>
        Enter your email address and we we will send you a
        link to reset your password.
    </small>

    {{ form_start(requestForm) }}
    {{ form_row(requestForm.email) }}

    <button class="btn btn-primary">Send password reset email</button>
{{ form_end(requestForm) }}
{% endblock %}