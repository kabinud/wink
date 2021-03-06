<?php
    require_once dirname(__FILE__).'/src/inc/config.php';
    require_once dirname(__FILE__).'/src/inc/functions.inc.php';
    
    $styles = wink_get_themes(WINK_DIR_THEMES);
    $default_style = 'default';
?>
<!DOCTYPE html>
<html>
    <head>
        <title>The wink builder</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        
        <link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700" >
        
        <link rel="stylesheet" type="text/css" href="web/css/main.css" />
    </head>
    <body class="homepage">
        <div id="main">
            <div id="header" class="bg_white">
                <a title="Wink Toolkit" href="http://www.winktoolkit.org">
                    <img src="web/img/wink_header_logo.png" alt="winktoolkit logo">
				</a>
                <h1 id="header_title">
                    Your Wink builder
                </h1>
            </div>
            
            <div id="main_content" class="bg_white">
                
                <p class="description">
                    Use this tool to generate your custom Wink build containing
                    only the modules your web app needs.
                </p>
                
                <p id="error" class="msg_error" style="display: none">
                </p>
                
                <div id="flash" style="display: none;">
                    <h3>Result ...</h3>
                    <div class="action">
                        <div class="loader">
                            <img src="web/img/ajax-loader.gif" alt="loading" title="loading" id="img_loading" />
                            Please wait, the build is in progress...
                        </div>
                        <div class="result">
                            <ul id="source_files" class="bg_white"></ul>
                            <span class="big">Or</span>
                            <button type="button" class="btn" id="dl">Download ZIP</button>
                        </div>
                    </div>
                </div>
                
                <form id="form" action="./procs/builder.php" method="get" onsubmit="return false;">
                    <h3>Environment ... </h3>
                    <div class="action" id="style">
                        <label>Theme: </label>
                        <div class="fields">
                            <select name="css_theme">
                                <?php foreach($styles as $styleName) : ?>
                                <option value="<?php echo $styleName; ?>"
                                <?php if($styleName == $default_style) echo 'selected="selected"'; ?>>
                                    <?php echo ucfirst($styleName); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <label>Languages: </label>
                        <div class="fields">
                            <ul>
                                <li class="checkable">
                                    <input type="checkbox" name="languages[]" value="en_EN" checked />
                                    <label>English</label>
                                </li>
                                <li class="checkable">
                                    <input type="checkbox" name="languages[]" value="fr_FR" />
                                    <label>French</label>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <h3>Modules ...</h3>
                    <div class="action" id="modules">
                        <label>Modules: </label>
                        <div class="fields" id="tree">
                            Loading Wink modules...
                        </div>
                    </div>
                    <div class="action btn">
                        <input type="submit" class="btn" id="btn_generate" value="Generate" />
                    </div>
                </form>
            </div>
        </div>
        
        <!-- CORE -->
		<!-- Can be replaced by wink.min.js -->
		<script type="text/javascript" src="../../_amd/js/amd.js"></script>
		<script type="text/javascript" src="../../_base/_base/js/base.js"></script>
		<script type="text/javascript" src="../../_base/error/js/error.js"></script>
		<script type="text/javascript" src="../../_base/json/js/json.js"></script>
		<script type="text/javascript" src="../../_base/ua/js/ua.js"></script>
		<script type="text/javascript" src="../../_base/topics/js/topics.js"></script>
		<script type="text/javascript" src="../../_base/_feat/js/feat.js"></script>
		<script type="text/javascript" src="../../_base/_feat/js/feat_json.js"></script>
		<script type="text/javascript" src="../../_base/_feat/js/feat_css.js"></script>
		<script type="text/javascript" src="../../_base/_feat/js/feat_event.js"></script>
		<script type="text/javascript" src="../../_base/_feat/js/feat_dom.js"></script>
		<script type="text/javascript" src="../../fx/_xy/js/2dfx.js"></script>
		<script type="text/javascript" src="../../math/_basics/js/basics.js"></script>
		<script type="text/javascript" src="../../net/xhr/js/xhr.js"></script>
		<script type="text/javascript" src="../../ui/xy/layer/js/layer.js"></script>
		<script type="text/javascript" src="../../ux/event/js/event.js"></script>
		<script type="text/javascript" src="../../ux/touch/js/touch.js"></script>
		<!-- END CORE -->
        
        <script type="text/javascript" src="web/js/functions.js"></script>
        <script type="text/javascript">
            /**
             * The result on the loading of the modules
             * 
             * @param result
             */
            var onLoadModuleSuccess = function(result) {
                var json = wink.json.parse(result.xhrObject.response),
                    modules = json.result;

                buildDomLevels(modules, wink.byId('tree'));

                // Add click event for the checkable li
                var list_checkable = wink.query('li.checkable > label');
                for(var i=0, l=list_checkable.length; i<l; i++) {
                    var elem = list_checkable[i];
                    Vinke.addEvent(elem, 'click', function() {
                        var checkbox = wink.query('input[type="checkbox"]', this.parentNode)[0];
                        checkbox.checked = !checkbox.checked

                        if(checkbox.name.match(/module/i)) {
                            var type = (checkbox.checked) ? 'check' : 'uncheck';
                            wink.publish('/event/'+type+'/input', [checkbox.value, modules]);
                        }
                    });
                }
            };

            // Load all Wink modules
            var xhr = new wink.Xhr();
            xhr.sendData('./src/procs/load_modules.php', null, 'GET', onLoadModuleSuccess, function() {
                display_error('An error has occurred on the modules loading');
            }, null);

            // Manage the submit form
            Vinke.addEvent(wink.byId('form'), 'submit', function(evt) {
                var e = evt || window.event,
                    form = e.target,
                    queryString = [];

                // required options
                queryString.push({name: 'format', value: 'json'});

                // Get theme
                queryString.push({name: 'theme', value: form.css_theme.value});

                // Get languages
                Vinke.each(wink.query('input[name="languages[]"]', form), function(checkbox) {
                    if(checkbox.checked)
                        queryString.push({name: 'languages[]', value: checkbox.value});
                });

                // Get modules
                var list_input = wink.query('input[name="module[]"]', form);
                for(var i=0, l=list_input.length; i<l; i++) {
                    var input = list_input[i];
                    if((input.type.toLowerCase() == 'checkbox' && input.checked && !input.disabled)
                    || (input.type.toLowerCase() == 'hidden'))
                        queryString.push({name: 'modules[]', value: input.value});
                }

                onLoadingSave();
                xhr.sendData('./src/procs/builder.php', queryString, 'GET', onSuccessSave, function(result) {
                    display_error('An error has occurred on the save modules');
                }, null)

                e.preventDefault();
            });
        </script>
    </body>
</html>
