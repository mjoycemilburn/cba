<?php

function connect_to_database()
{
    global $con, $url_root;

    if (($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1')) {
        $url_root = '../../';
    } else {
        $current_directory_root = $_SERVER['DOCUMENT_ROOT']; // one level above current directory
        // remove everything after and including "public_html"

        $pieces = explode('public_html', $current_directory_root);
        $url_root = $pieces[0];
    }

    require($url_root . "connect_cbadb.php");
}

function disconnect_from_database()
{
    global $con, $url_root;

    require($url_root . "disconnect_cbadb.php");
}

function sql_result_for_location($sql, $location)
{
    global $con, $page_title;

    $result = mysqli_query($con, $sql);

    if (!$result) {
        echo "Oops - database access %failed%. in $page_title location $location. Error details follow : " . mysqli_error($con);

        $sql = "ROLLBACK";
        $result = mysqli_query($con, $sql);

        disconnect_from_database();
        exit(1);
    }

    return $result;
}

# Prepare a string in for submission to SQL

function prepareStringforSQL($input)
{

    # " must be "escaped" to be acceptable in SQL commands
    # ' must be changed to '' to be acceptable in SQL commands
    # You might have thought we would do this at the outset before these characters reached "helpers"
    # but the problem is that the code ' gets turned back into ' on its way through POST

    $output = $input;

    $output = str_replace('"', '\"', $output);
    $output = str_replace("'", "''", $output);

    return $output;
}


