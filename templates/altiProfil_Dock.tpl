<script type="text/javascript">
  var URLAJAXALTICOORD = "{jurl 'altiProfil~ajax:getAlti'}";
  var URLAJAXALTIPROFIL = "{jurl 'altiProfil~ajax:getProfil'}";
  var LOCALES_SLOPE = "{@altiProfil~altiProfil.alti.slope@}";
  var LOCALES_SLOPE_DEGREES = "{@altiProfil~altiProfil.alti.degrees@}";
  var LOCALES_MEAN = "{@altiProfil~altiProfil.alti.mean@}";  
  var LOCALES_DATASOURCE = "{@altiProfil~altiProfil.alti.datasource@}";  
</script>
<div id="altiProfil">
  <div class="menu-content">
    {@altiProfil~altiProfil.dock.help@} 
    <p>
      {@altiProfil~altiProfil.alti.point1@} : <span id="alt-p1"></span> <br/>
      {@altiProfil~altiProfil.alti.point2@} : <span id="alt-p2"></span>
    </p>
    <div id="profil-chart">
      <div class="spinner"></div>
      <div id="profil-chart-container"></div>
    </div>  
  </div>
</div>