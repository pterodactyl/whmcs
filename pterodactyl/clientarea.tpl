<div class="row">
    <div class="col-xs-6">
        <a href="{$serviceurl}" class="btn btn-block btn-success">Go to Panel</a>
        <p onclick="copyUUID()" style="cursor: pointer;" > <span style="background-color: #008CBA; border: none; color: white; padding: 15px 32px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 20px 4px 4px 20px; cursor: pointer;">L'id de votre Service : {$uuid}</span></p>
    </div>
</div>

<script>
function copyUUID() {
  navigator.clipboard.writeText('{$uuid}');
  alert("L'UUID a été copié !");
}
</script>
