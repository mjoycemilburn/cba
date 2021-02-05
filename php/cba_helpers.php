<?php

// set headers to NOT cache the page
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

require('../includes/cba_functions.php');

// Uncomment the following 'require' line if you want to run the helper file in xdebug
// with the POST settings in testcode.php

//require('testcode.php');

// As directed by helper_type :
//
// 'build_events_table'                 -  return code to display the upcoming events for a month wrt the
//                                         following parameters:
//                                              events_table_type (CBA National/CBA Are/Local Society)
//                                              society_id
//                                              area_id
//                                              month_offset (defines the 'time window' for the table wrt 'today')


$page_title = 'cba_helpers';

date_default_timezone_set('Europe/London');

connect_to_database();

# Load the utf8 character set to enable accented characters to be stored (they go up otherwise
# as diamond characters with a central question mark). See notes at the top of text_manageent.html

if (!mysqli_set_charset($con, "utf8")) {
    echo "Error loading character set utf8: %s\n", mysqli_error($con);
    disconnect_from_database();
    exit(1);
}

// get helper-request

$helper_type = $_POST['helper_type'];

//////////////////////////////////////////  build_events_table ////////////////////////////////////////

if ($helper_type == "build_events_table") {
    $events_table_type = $_POST['events_table_type'];
    $month_offset = $_POST['month_offset'];
    $target_society_id = $_POST['target_society_id'];
    $target_area_id = $_POST['target_area_id'];

    // build the target "month window" of the events select

    $month_offset_plus = $month_offset + 1;

    // special month_offset value of 100 used to show (effectively) all events on a local society page
    if ($events_table_type === "Local Society") {
        $time_start = strtotime('+0 month', time());
        $time_finish = strtotime('+100 month', time());
    } else {
        $time_start = strtotime('+' . $month_offset . ' month', time());
        $time_finish = strtotime('+' . $month_offset_plus . ' month', time());
    }

    $formatted_time_start = date("Y-m-d 00:00:00", $time_start);
    $formatted_time_finish = date("Y-m-d 00:00:00", $time_finish);

    // build society pick list and lookup list

    $sql = "SELECT * FROM cba_societies";

    $result = sql_result_for_location($sql, 1);

    $society_picklist = "<select id='societypicklist' size='1' style = 'max-width: 12rem;'
                            onchange = 'changeSociety();'>";

    if ($target_society_id == 0) {
        $society_picklist .= "<option selected value=0>All</option>;";
    } else {
        $society_picklist .= "<option value=0>All</option>;";
    }

    $societies = [];

    while ($row = mysqli_fetch_array($result)) {

        // add to picklist button

        if ($target_society_id == $row['society_id']) {
            $society_picklist .= "<option selected value=" . $row['society_id'] . ">" . $row['society_name'] . "</option>;";
        } else {
            $society_picklist .= "<option value=" . $row['society_id'] . ">" . $row['society_name'] . "</option>;";
        }
        // add to reference list array

        array_push($societies, (object)[
        'society_id' => $row['society_id'],
        'society_name' => $row['society_name'],
        'society_website_url' => $row['society_website_url'],
        'society_contact_email_address' => $row['society_contact_email_address']
        ]);
    }

    $society_picklist .= "</select>";

    // build area pick list and lookup list

    $sql = "SELECT * FROM cba_areas";

    $result = sql_result_for_location($sql, 2);

    $area_picklist = "<select id='areapicklist' size='1' style = 'max-width: 15rem;'
                            onchange = 'changeArea();'>";

    if ($target_area_id == 0) {
        $area_picklist .= "<option selected value=0>All</option>;";
    } else {
        $area_picklist .= "<option value=0>All</option>;";
    }

    $areas = [];

    while ($row = mysqli_fetch_array($result)) {

        // add to picklist button

        if ($target_area_id == $row['area_id']) {
            $area_picklist .= "<option selected value=" . $row['area_id'] . ">" . $row['area_name'] . "</option>;";
        } else {
            $area_picklist .= "<option value=" . $row['area_id'] . ">" . $row['area_name'] . "</option>;";
        }
        // add to reference list array

        array_push($areas, (object)[
        'area_id' => $row['area_id'],
        'area_name' => $row['area_name']
        ]);
    }

    $area_picklist .= "</select>";


    // build an appropriate header for the events display

    $header = '';
    if ($events_table_type === "CBA National") {
        $header =  "<div style='width: 95%; margin-left:3vw; margin-right: 3vw; text-align: center;'>
                        <img style='width: 100%;' src='imgs/cba_header.jpg'>
                    </div>
                    <div class='container'>";
    }

    if ($events_table_type === "CBA Area") {

        // get the area name corresponding to the given area_id

        $index = array_search($target_area_id, array_column($areas, 'area_id'));
        $area_name = $areas[$index]->area_name;

        $header =  "<div style='width: 95%; margin-left:3vw; margin-right: 3vw; margin-bottom: 3vh; padding: 3vh; text-align: center; background: #159B9A;'>
                        <h1>Upcoming Events for : </h1>
                        <h1>" . $area_name . "</h1>
                    </div>
                    <div class='container'>";
    }

    if ($events_table_type === "Local Society") {

        // get the society name corresponding to the given society_id

        $index = array_search($target_society_id, array_column($societies, 'society_id'));
        $society_name = $societies[$index]->society_name;

        $header =  "<div style='width: 95%; margin-left:3vw; margin-right: 3vw; margin-bottom: 3vh; padding: 3vh; text-align: center; background: #159B9A;'>
                        <h1>Upcoming Events for : </h1>
                        <h1>" . $society_name . "</h1>
                    </div>
                    <div class='container'>";
    }

    $return = $header;
    
    // build a button line for display at both top and bottom of the events table

    $button_line = '';


    if ($events_table_type === "CBA National") {
        $button_line = "
            <div class = 'row' style = 'margin-bottom: 2vh; font-weight: bold; text-align: center;'>
                <span class = 'col-xs-12 col-md-4'>By Society: " . $society_picklist . "&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <span class = 'col-xs-12 col-md-4'>By Area: " . $area_picklist . "&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <span class = 'col-xs-12 col-md-4'>By Time : 
                <span onclick = 'moveEventDisplayMonth(+1);' style = 'border: 1px solid black; cursor: pointer;' title = 'Advance the event window by 1 month'>+1</span>&nbsp;/&nbsp;
                <span onclick = 'moveEventDisplayMonth(-1);' style = 'border: 1px solid black; cursor: pointer;' title = 'Retard the event window by 1 month'>-1</span>&nbsp;&nbsp;Month</span>
            </div>";
    }

    if ($events_table_type === "CBA Area") {
        $button_line = "
            <div class = 'row' style = 'margin-bottom: 2vh; font-weight: bold;'>
                <p style='margin-bottom: 0; margin-left: auto; margin-right: auto;'>By Time :
                <span onclick = 'moveEventDisplayMonth(+1);' style = 'border: 1px solid black; cursor: pointer;' title = 'Advance the event window by 1 month'>+1</span>&nbsp;/&nbsp;
                <span onclick = 'moveEventDisplayMonth(-1);' style = 'border: 1px solid black; cursor: pointer;' title = 'Retard the event window by 1 month'>-1</span>&nbsp;&nbsp;Month</p>
            </div>";
    }

    $return .= $button_line;
   
    if ($events_table_type === "CBA Area") {
        $sql = "SELECT * FROM cba_events WHERE 
                    event_published = 'Y' AND
                    event_is_preview = 'N' AND
                    event_datetime >= '$formatted_time_start' AND
                    event_datetime < '$formatted_time_finish' AND
                    society_id IN (SELECT 
                                    society_id
                                FROM
                                    cba_societies
                                WHERE
                                    area_id = '$target_area_id')
                ORDER BY event_datetime ASC, event_title ASC;";
    } else {
        $sql = "SELECT * FROM cba_events WHERE 
                    event_published = 'Y' AND
                    event_is_preview = 'N' AND
                    event_datetime >= '$formatted_time_start' AND
                    event_datetime < '$formatted_time_finish'";

        if ($target_society_id != 0) {
            $sql .= "AND society_id = '$target_society_id'";
        }

        if ($target_area_id != 0) {
            $sql .= "AND society_id IN (SELECT 
                                    society_id
                                FROM
                                    cba_societies
                                WHERE
                                    area_id = '$target_area_id')";
        }
        $sql .= "ORDER BY event_datetime ASC, event_title ASC;";
    }

    if ($events_table_type === "Preview") {
        $sql = "SELECT * FROM cba_events WHERE 
        society_id = '$target_society_id' AND
        event_is_preview = 'Y';";
    }

    $result = sql_result_for_location($sql, 3);

    $return .= "<div class = 'row' style='margin-bottom: 2vh;'>";

    // event entries are designed to be show 3-up on larger screen and 1-up on
    // smaller devices. We return the entries as a single bootstrap row, relying
    // on the library's software to  autmatically split this into separate rows
    // when it sees that 12 columns have been filled

    $modal_number = 0;

    while ($row = mysqli_fetch_array($result)) {
        $event_id = $row['event_id'];
        $society_id = $row['society_id'];

        // get the society name corresponding to the given society_id

        $index = array_search($society_id, array_column($societies, 'society_id'));
        $society_name = $societies[$index]->society_name;

        $event_datetime = $row['event_datetime'];

        // format the datetime

        $timestamp = strtotime($event_datetime);
        $formatted_event_date = date("l, jS M Y", $timestamp);
        $formatted_event_time = date("g.ia", $timestamp);
        $formatted_event_datetime = $formatted_event_date . " at " . $formatted_event_time;

        $event_type = $row['event_type'];
        $event_title = $row['event_title'];
        $trimmed_event_title = $event_title;
        if (strlen($event_title) > 70) {
            $trimmed_event_title = substr($event_title, 0, 69) . " ...";
        }
        $presenter = $row['presenter'];
        $synopsis = $row['synopsis'];

        // great textured background library on https://unsplash.com/backgrounds/art/texture

        $standard_background_overridden = $row['standard_background_overridden'];
        $event_published = $row['event_published'];

        if ($standard_background_overridden === "Y") {
            if ($events_table_type === "Preview") {
                $event_text_color = "white;";
                $event_background = "url(\"user_background_previews/" . $society_id . ".jpg?" . time() . "\");>";
            } else {
                $event_text_color = "white;";
                $event_background = "url(\"user_backgrounds/" . $event_id . ".jpg\");";
            }
        } else {
            $event_text_color = "black";
            $event_background = "url(\"system_backgrounds/" . $society_id . ".jpg\");";
        }

        // bootstrap gets confused if put margins directly on a col - need to define these inside the entry

        $modal_number++;
        $entry = "<div class='col-xs-12 col-md-4' style = 'cursor: pointer;'
                    title = 'Click to view event details' 
                    onclick = 'displayEventModal(" . $modal_number . ");'>
                    <div style = 'width: 100%; height: 35vh; margin: 3%; border: 1px solid black; padding: 4%; font-weight: bold;
                    background-image: " . $event_background . " background-position: center; background-size: cover;'>";

        // note that we've given the entry a fixed height, so they're all the same (tidy) - tho note content may
        // overflow if we don't take care in the insert/edit utility. ALso note we give each society its own
        // personal background - choose these with care so that both white and black text stands out. They need
        // to be subtle!

        $entry .= "<div style = 'position: absolute; top:10%;left: 10%;'>";
        $entry .= "<span style = 'color: mediumblue;'>". $society_name . "</span>";
        $entry .= "<p style = 'margin: 2%; font-size: x-large; font-weight: bold; color: " . $event_text_color . "'>" . $event_type  . " : "  . $trimmed_event_title . "</p>";
        $entry .= "</div>";
        
        // organise the layout so that the remaining text fills up from the bottom of the entry - thus nicely topped and tailed

        $entry .= "<div style='position: absolute;bottom: 7%;left: 10%; width: 80%; color: " . $event_text_color . "'>";
        $entry .= "<p style = 'margin: 2%;'>" . $presenter . "</p>";
        $entry .= "<p style = 'margin: 2%;'>" . $formatted_event_datetime . "</p>";

        $entry .= "</div></div></div>";

        $return .=  $entry;

        // build a modal for this entry

        $event_modal = '';

        $event_modal .= "<div class = 'modal fade' id = 'eventmodal" . $modal_number . "'>";
        $event_modal .= "<div class = 'modal-dialog modal-content modal-body'>";
 
        if ($standard_background_overridden === "Y") {
            if ($events_table_type === "Preview") {
                $event_modal .= "<img style='width: 100%;' src='user_background_previews/" . $society_id . ".jpg?" . time() . "'>";
            } else {
                $event_modal .= "<img style='width: 100%;' src='user_backgrounds/" . $event_id . ".jpg'>";
            }
        }

        $event_modal .= "<p style='text-align: center; margin-top: 1rem; font-weight: bold;'>" . $event_title . "</p>";
        $event_modal .= $synopsis;

        $event_modal .= "<p style='text-align: left; margin-top: 1rem; margin-bottom: .15rem;'><span style='font-weight: bold;'>Society website: </span>";
        $event_modal .= "<a href = '". $societies[$index]->society_website_url . "' target='_blank'>". $societies[$index]->society_website_url . "</a></p>";

        $event_modal .= "<p style='text-align: left; margin-top: 0;'><span style='font-weight: bold;'>Contact address: </span>";
        $event_modal .= "<a href = 'mailto:". $societies[$index]->society_contact_email_address . " target='_blank'>". $societies[$index]->society_contact_email_address . "</a></p>";


        $event_modal .= "</div></div>";

        $return .= $event_modal;
    }

    $return .= "</div>";
    
    $return .= $button_line;

    echo $return;
}

disconnect_from_database();
