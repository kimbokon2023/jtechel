<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* sql/profiling_chart.twig */
class __TwigTemplate_bb48f9001a538bc2b4fcf7de02ede2e9843694cd6216848e2d4c37dc48308143 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<fieldset>
  <legend>";
        // line 2
        echo _gettext("Profiling");
        echo "</legend>
  <div class=\"floatleft\">
    <h3>";
        // line 4
        echo _gettext("Detailed profile");
        echo "</h3>
    <table id=\"profiletable\">
      <thead>
      <tr>
        <th>
          ";
        // line 9
        echo _gettext("Order");
        // line 10
        echo "          <div class=\"sorticon\"></div>
        </th>
        <th>
          ";
        // line 13
        echo _gettext("State");
        // line 14
        echo "          ";
        echo PhpMyAdmin\Util::showMySQLDocu("general-thread-states");
        echo "
          <div class=\"sorticon\"></div>
        </th>
        <th>
          ";
        // line 18
        echo _gettext("Time");
        // line 19
        echo "          <div class=\"sorticon\"></div>
        </th>
      </tr>
      </thead>
      <tbody>
        ";
        // line 24
        echo ($context["detailed_table"] ?? null);
        echo "
      </tbody>
    </table>
  </div>

  <div class=\"floatleft\">
    <h3>";
        // line 30
        echo _gettext("Summary by state");
        echo "</h3>
    <table id=\"profilesummarytable\">
      <thead>
      <tr>
        <th>
          ";
        // line 35
        echo _gettext("State");
        // line 36
        echo "          ";
        echo PhpMyAdmin\Util::showMySQLDocu("general-thread-states");
        echo "
          <div class=\"sorticon\"></div>
        </th>
        <th>
          ";
        // line 40
        echo _gettext("Total Time");
        // line 41
        echo "          <div class=\"sorticon\"></div>
        </th>
        <th>
          ";
        // line 44
        echo _gettext("% Time");
        // line 45
        echo "          <div class=\"sorticon\"></div>
        </th>
        <th>
          ";
        // line 48
        echo _gettext("Calls");
        // line 49
        echo "          <div class=\"sorticon\"></div>
        </th>
        <th>
          ";
        // line 52
        echo _gettext("ø Time");
        // line 53
        echo "          <div class=\"sorticon\"></div>
        </th>
      </tr>
      </thead>
      <tbody>
        ";
        // line 58
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["states"] ?? null));
        foreach ($context['_seq'] as $context["name"] => $context["stats"]) {
            // line 59
            echo "          <tr>
            <td>";
            // line 60
            echo twig_escape_filter($this->env, $context["name"], "html", null, true);
            echo "</td>
            <td align=\"right\">
              ";
            // line 62
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::formatNumber((($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = $context["stats"]) && is_array($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4) || $__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 instanceof ArrayAccess ? ($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4["total_time"] ?? null) : null), 3, 1), "html", null, true);
            echo "s
              <span class=\"rawvalue hide\">";
            // line 63
            echo twig_escape_filter($this->env, (($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = $context["stats"]) && is_array($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144) || $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 instanceof ArrayAccess ? ($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144["total_time"] ?? null) : null), "html", null, true);
            echo "</span>
            </td>
            <td align=\"right\">
              ";
            // line 66
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::formatNumber((100 * ((($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b = $context["stats"]) && is_array($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b) || $__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b instanceof ArrayAccess ? ($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b["total_time"] ?? null) : null) / ($context["total_time"] ?? null))), 0, 2), "html", null, true);
            echo "%
            </td>
            <td align=\"right\">";
            // line 68
            echo twig_escape_filter($this->env, (($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 = $context["stats"]) && is_array($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002) || $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 instanceof ArrayAccess ? ($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002["calls"] ?? null) : null), "html", null, true);
            echo "</td>
            <td align=\"right\">
              ";
            // line 70
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::formatNumber(((($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 = $context["stats"]) && is_array($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4) || $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 instanceof ArrayAccess ? ($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4["total_time"] ?? null) : null) / (($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666 = $context["stats"]) && is_array($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666) || $__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666 instanceof ArrayAccess ? ($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666["calls"] ?? null) : null)), 3, 1), "html", null, true);
            echo "s
              <span class=\"rawvalue hide\">
                ";
            // line 72
            echo twig_escape_filter($this->env, twig_number_format_filter($this->env, ((($__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e = $context["stats"]) && is_array($__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e) || $__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e instanceof ArrayAccess ? ($__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e["total_time"] ?? null) : null) / (($__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52 = $context["stats"]) && is_array($__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52) || $__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52 instanceof ArrayAccess ? ($__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52["calls"] ?? null) : null)), 8, ".", ""), "html", null, true);
            echo "
              </span>
            </td>
          </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['name'], $context['stats'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 77
        echo "      </tbody>
    </table>

    <script type=\"text/javascript\">
      url_query = '";
        // line 81
        echo twig_escape_filter($this->env, ($context["url_query"] ?? null), "html", null, true);
        echo "';
    </script>
  </div>
  <div class='clearfloat'></div>

  <div id=\"profilingChartData\" class=\"hide\">
    ";
        // line 87
        echo twig_escape_filter($this->env, json_encode(($context["chart_json"] ?? null)), "html", null, true);
        echo "
  </div>
  <div id=\"profilingchart\" class=\"hide\"></div>

  <script type=\"text/javascript\">
    AJAX.registerOnload('sql.js', function () {
      Sql.makeProfilingChart();
      Sql.initProfilingTables();
    });
  </script>
</fieldset>
";
    }

    public function getTemplateName()
    {
        return "sql/profiling_chart.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  199 => 87,  190 => 81,  184 => 77,  173 => 72,  168 => 70,  163 => 68,  158 => 66,  152 => 63,  148 => 62,  143 => 60,  140 => 59,  136 => 58,  129 => 53,  127 => 52,  122 => 49,  120 => 48,  115 => 45,  113 => 44,  108 => 41,  106 => 40,  98 => 36,  96 => 35,  88 => 30,  79 => 24,  72 => 19,  70 => 18,  62 => 14,  60 => 13,  55 => 10,  53 => 9,  45 => 4,  40 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "sql/profiling_chart.twig", "/mirae8440/www/dbeditor/templates/sql/profiling_chart.twig");
    }
}
