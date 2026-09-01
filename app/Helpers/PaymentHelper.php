<?php

if(!function_exists('baseUrlFormat')){
    function baseUrlFormat($url){
        return rtrim($url, '/');
    }
}