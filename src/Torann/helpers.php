<?php

if ( ! function_exists('honeypot'))
{
    /**
     * Generate honeypot form input.
     *
     * @param  string  $honey_name
     * @return string
     */
    function honeypot($honey_name)
    {
        // Create element ID
        $honey_id = preg_replace('~[^-\w]+~', '', trim(preg_replace('~[^\\pL\d]+~u', '_', $honey_name), '_'));

        return "<div id=\"{$honey_id}_wrap\" style=\"display:none;\">\n<input id=\"{$honey_id}\" name=\"{$honey_name}\" type=\"text\" value=\"\">\n</div>";
    }
}