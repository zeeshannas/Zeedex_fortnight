<?php
/** @var string $skin */
/** @var GroceryCrud\Core\Layout $this */
?><script>
    window.GroceryCrudConfiguration = window.GroceryCrudConfiguration || {};

    Object.assign(window.GroceryCrudConfiguration, {
        assetsUrl: '<?php echo $this->_assetsFolder; ?>',
        autoLoad: <?php echo ($this->autoLoadFrontend ? 'true' : 'false'); ?>,
    });

    <?php if (isset($is_development_environment) && $is_development_environment === true) { ?>
        setTimeout(() => {
            // Search for class name ".gc-hidden-message"
            const gcHiddenMessage = document.querySelector('.gc-hidden-message');
            const gcGifLoader = document.querySelector('.gc-gif-loader');
            if (gcHiddenMessage) {
                gcHiddenMessage.style.display = 'block';
            }
            if (gcGifLoader) {
                gcGifLoader.remove();
            }
        }, 4000);
    <?php } ?>

    </script>

<div class="grocery-crud" data-api-url="<?php echo $this->getApiUrl(); ?>"<?php
    echo " ";
?>data-landing-page-url="<?php echo $this->getApiUrl(); ?>"<?php
    echo " ";
?>data-theme="<?php echo $this->getThemeName();?>"<?php
    echo "";
?>data-unique-id="<?php echo $this->getUniqueId(); ?>"<?php
        if ($skin !== null) {
            echo " data-skin=\"{$skin}\"";
        }

        // Only show the extra data when we want to disable this configuration
        // Default variable in the frontend is true (e.g. when it is not defined)
        if (isset($load_css_theme) && $load_css_theme === false) {
            echo " data-load-css-theme=\"false\"";
        }
        if (isset($load_css_icons) && $load_css_icons === false) {
            echo " data-load-css-icons=\"false\"";
        }
        if (isset($load_css_third_party) && $load_css_third_party === false) {
            echo " data-load-css-third-party=\"false\"";
        }

        if (isset($remember_state) && $remember_state === false) {
            echo " data-remember-state=\"false\"";
        }

        if (isset($remember_filtering) && $remember_filtering === false) {
            echo " data-remember-filtering=\"false\"";
        }

        if (isset($publish_events) && $publish_events === true) {
            echo " data-publish-events=\"true\"";
        }
    ?>><?php if (isset($is_development_environment) && $is_development_environment === true) {
        echo "\n    ";
        ?><div style="padding: 10px; border: 1px solid #aaa; border-radius: 10px;margin-bottom: 20px;display: none;" class="gc-hidden-message">
        Ooooops, something went wrong! If you can see this message, this is probably a misconfiguration in Grocery CRUD Enterprise!
    </div>
        <div class="gc-gif-loader" style="width: 16px; height: 16px; background-repeat: no-repeat; background-image: url('data:image/gif;base64,R0lGODlhEAAQAPcAAAAAAAEBAQICAgMDAwQEBAUFBQYGBgcHBwgICAkJCQoKCgsLCwwMDA0NDQ4ODg8PDxAQEBERERISEhMTExQUFBUVFRYWFhcXFxgYGBkZGRoaGhsbGxwcHB0dHR4eHh8fHyAgICEhISIiIiMjIyQkJCUlJSYmJicnJygoKCkpKSoqKisrKywsLC0tLS4uLi8vLzAwMDExMTIyMjMzMzQ0NDU1NTY2Njc3Nzg4ODk5OTo6Ojs7Ozw8PD09PT4+Pj8/P0BAQEFBQUJCQkNDQ0REREVFRUZGRkdHR0hISElJSUpKSktLS0xMTE1NTU5OTk9PT1BQUFFRUVJSUlNTU1RUVFVVVVZWVldXV1hYWFlZWVpaWltbW1xcXF1dXV5eXl9fX2BgYGFhYWJiYmNjY2RkZGVlZWZmZmdnZ2hoaGlpaWpqamtra2xsbG1tbW5ubm9vb3BwcHFxcXJycnNzc3R0dHV1dXZ2dnd3d3h4eHl5eXp6ent7e3x8fH19fX5+fn9/f4CAgIGBgYKCgoODg4SEhIWFhYaGhoeHh4iIiImJiYqKiouLi4yMjI2NjY6Ojo+Pj5CQkJGRkZKSkpOTk5SUlJWVlZaWlpeXl5iYmJmZmZqampubm5ycnJ2dnZ6enp+fn6CgoKGhoaKioqOjo6SkpKWlpaampqenp6ioqKmpqaqqqqurq6ysrK2tra6urq+vr7CwsLGxsbKysrOzs7S0tLW1tba2tre3t7i4uLm5ubq6uru7u7y8vL29vb6+vr+/v8DAwMHBwcLCwsPDw8TExMXFxcbGxsfHx8jIyMnJycrKysvLy8zMzM3Nzc7Ozs/Pz9DQ0NHR0dLS0tPT09TU1NXV1dbW1tfX19jY2NnZ2dra2tvb29zc3N3d3d7e3t/f3+Dg4OHh4eLi4uPj4+Tk5OXl5ebm5ufn5+jo6Onp6erq6uvr6+zs7O3t7e7u7u/v7/Dw8PHx8fLy8vPz8/T09PX19fb29vf39/j4+Pn5+fr6+vv7+/z8/P39/f7+/v///yH/C05FVFNDQVBFMi4wAwEAAAAh+QQIBgD/ACwAAAAAEAAQAAAIlgD/CcQnDd6/c1ycHJonsOG/dHuUVPunL1svVvf++Wvob9UZa/wcDmRlrqE9hiIF1lsz5x49fClFZlPyLNGqmA77WYPnhBfOlFqw/Wy47xw5fUMFetuStKGwJfKu9UvqKlC0JdeS3ot3zw4aejjt5WtoDpW9lP24wXG1UeBGfLCCddv3DxuTN+RSziu0RMu5f/CYnRUYEAAh+QQIBgD/ACwAAAAAEAAQAAAIlwD/CdyXTI+Zcv/MKSJ2T6DDf/T0+AHWkJ2hIpP2Odzn7189jQ75ZXvW8R8+S88eqhRIb18xItZWPrTHh5giQiBl/uM3SA65dTofEiMTVOU9c+caFnV4BtjSf+7K3fHDr2g/R4SM3aFXlJuRXvvmldQJrZE9gf6kaavqsF9Df/o2UiqyqJ1JY4f+1Ft571chhOfIwAmWMyAAIfkECAYA/wAsAAAAABAAEAAACJAA/wkUmA6NEjHo/vHLN1CgOnL/8DWbxm3fP2KAyg08V0ZSv4YCwZ0Jk+6fPkFjzoEcqI7SOIXBvK1s+HGmzXzo1Fm0OTAaGTLceA7EduTINKECv40Zswzpv33pzN1zSlPouGEWzV1SZ5Mdmzz4/qkDg0bcyn6XuIQbSI6PsKfgsEljKM4cyHwW1ZVJUqZkw4AAOw==')"></div>

<?php } ?></div><?php
include(realpath(dirname ( __FILE__ ) . '/build/assets.php'));

if (isset($display_js_files_in_output) && $display_js_files_in_output === true) {
    echo "\n";
    $jsFiles = $this->getJavaScriptFiles();
    foreach ($jsFiles as $jsFile) {
        echo "<script src=\"{$jsFile}\"></script>\n";
    }
    // Since we have already included the JavaScript files in the output
    // we can clear them from the js files array so we will not have them twice accidentally
    $this->clearJavaScriptFiles();
} ?>

