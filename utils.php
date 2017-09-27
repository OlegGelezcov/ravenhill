<?php

function resolve_parameter($parameter_name) {
    if(isset($_GET[$parameter_name])) {
        return $_GET[$parameter_name];
    } elseif(isset ($_POST[$parameter_name])) {
        return $_POST[$parameter_name];
    }
    return '';
}

function resolve_int_parameter($parameter_name) {
    $str_param = resolve_parameter($parameter_name);
    if(empty($str_param)) {
        return 0;
    } else {
        return intval($str_param);
    }
}


?>

