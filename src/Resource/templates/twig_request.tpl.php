{% extends 'base.html.twig' %}

{% block title %}Recover your password{% endblock %}

{% block body %}
{% for message in app.flashes('reset_password_error') %}
<section class="flash-error"><p>{{ message }}</p></section>
{% endfor %}
<h1>Recover your password</h1>

{{ form_start(requestForm) }}
{{ form_row(requestForm.email) }}

<button class="btn btn-primary">Send e-mail</button>
{{ form_end(requestForm) }}
{% endblock %}