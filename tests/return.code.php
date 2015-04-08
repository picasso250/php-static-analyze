<?php

function return_null()
{
    return null;
}
function only_return()
{
    return;
}
function no_return()
{
    $a = 1;
}
function two_return()
{
    return false;
    return null;
}

// will null
function if_return() {
    if ($a) {
        return true;
    } else {
        return false;
    }
}
function while_return() {
    while ($a) {
        return true;
    }
}
