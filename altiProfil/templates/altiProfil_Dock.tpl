<script type="text/javascript">

    var URLAJAXALTICOORD = "{jurl 'altiProfil~ajax:getAlti'}";
    var URLAJAXALTIPROFIL = "{jurl 'altiProfil~ajax:getProfil'}";
    var ALTI_LOCALE = "{$j_locale}";
    var LOCALES_ALTI_HELP_START = "{@altiProfil~altiProfil.help.start@}";
    var LOCALES_ALTI_HELP_END = "{@altiProfil~altiProfil.help.end@}";
    var LOCALES_ALTI_HELP_AGAIN = "{@altiProfil~altiProfil.help.again@}";
    var LOCALES_ALTI_HIGH = "{@altiProfil~altiProfil.alti.high@}";
    var LOCALES_ALTI_LOW = "{@altiProfil~altiProfil.alti.low@}";
    var LOCALES_ALTI_GAIN = "{@altiProfil~altiProfil.alti.gain@}";
    var LOCALES_ALTI_LOSS = "{@altiProfil~altiProfil.alti.loss@}";
    var LOCALES_ALTI_RESOLUTION = "{@altiProfil~altiProfil.alti.resolution@}";
    var LOCALES_ALTI_SLOPE = "{@altiProfil~altiProfil.alti.slope@}";
    var LOCALES_ALTI_ERROR_PROFIL = "{@altiProfil~altiProfil.alti.error.profil@}";
    var LOCALES_ALTI_ERROR_REQUEST = "{@altiProfil~altiProfil.alti.error.request@}";
    var ALTI_PROVIDER = "{$altiProvider}";
    var LOCALES_ALTI_UNIT_ABRV = '';

    {if $altiProvider == "database"}
      {if $profilUnit == "DEGREES"}
        var LOCALES_ALTI_UNIT = "{@altiProfil~altiProfil.alti.degrees@}";
        var LOCALES_ALTI_UNIT_ABRV = '°';
      {elseif $profilUnit == "PERCENT"}
        var LOCALES_ALTI_UNIT = "{@altiProfil~altiProfil.alti.percent@}";
        var LOCALES_ALTI_UNIT_ABRV = '%';
      {/if}
    {/if}
    var LOCALES_ALTI_MEAN = "{@altiProfil~altiProfil.alti.mean@}";
    var LOCALES_ALTI_DATASOURCE = "{@altiProfil~altiProfil.alti.datasource@}";
</script>


<div id="altiProfil" class="altiProfil">
    {if $dock == 'minidock'}
      <h3>
        <span class="title">
          <button id="profil-stop" class="btn btn-mini btn-error btn-link" title="{@view~map.toolbar.content.stop@}">×</button>
          <span class="icon"></span>
          <span class="text">&nbsp;{@altiProfil~altiProfil.dock.title@}&nbsp;</span>
        </span>
      </h3>
    {/if}

    <div class="menu-content">
      <p id="altiProfil-help" class="ap-help"></p>

      <div id="altiProfil-ends" class="ap-ends" style="display:none">
        <div class="ap-end" title="{@altiProfil~altiProfil.alti.point1@}">
          <span class="ap-pin"><b>1</b></span><span id="alt-p1"></span>
        </div>
        <div class="ap-dist" title="{@altiProfil~altiProfil.alti.distance@}">
          <span id="altiProfil-distance"></span>
        </div>
        <div class="ap-end ap-end--b" title="{@altiProfil~altiProfil.alti.point2@}">
          <span id="alt-p2"></span><span class="ap-pin"><b>2</b></span>
        </div>
        <div id="altiProfil-coord-p1" class="ap-coord" title="{@altiProfil~altiProfil.alti.coords@}"></div>
        <div></div>
        <div id="altiProfil-coord-p2" class="ap-coord ap-coord--b" title="{@altiProfil~altiProfil.alti.coords@}"></div>
      </div>

      <div id="altiProfil-chart" style="display:none">
        <div class="spinner"></div>
        <div id="altiProfil-chart-container"></div>
        <div id="altiProfil-result">
          <dl id="altiProfil-stats" class="ap-stats"></dl>
          <p id="altiProfil-slope" class="ap-note"></p>
          <p id="altiProfil-source" class="ap-note"></p>
        </div>
      </div>

      <p id="altiProfil-error" class="ap-error" role="alert"></p>
    </div>
</div>
