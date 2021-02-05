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
// 'build_event_overview                -   return code to display code to insert and edit events
//                                          records for a given society
//
// 'insert_event'                       -   insert a 'stub' for given society_id and date_time
//
// 'get_event_data'                     =   return the event data for given event_id as a json
//
// 'delete_event'                       -   delete the event for given event_id
//
// 'update_event'                       -   delete the event for given event_id
//
// 'prepare_event_preview'              -   create/update an event-preview record for given society_id
//
// 'upload_user_background_preview'     -   upload a jpg file into user_background_previews for given society_id
//                                          and event_id


$page_title = 'manager_helpers';

date_default_timezone_set('Europe/London');

/* check logged_in

session_start();

if (!isset($_SESSION['governors_user_logged_in_for_school_id'])) {
    echo "%timed_out%";
    exit(0);
} else {
    $school_id = $_SESSION['governors_user_logged_in_for_school_id'];
}
*/

// connect to the events database

connect_to_database();

// get helper-request

$helper_type = $_POST['helper_type'];

#####################  build_event_overview ####################

if ($helper_type === "build_event_overview") {
    $society_id = $_POST['society_id'];

    // get the society name

    $sql = "SELECT * FROM cba_societies WHERE
                    society_id = '$society_id';";

    $result = sql_result_for_location($sql, 1);
    $row = mysqli_fetch_array($result);
    $society_name = $row['society_name'];

    $return = "
        <div style = 'text-align: center;'>
            <h2>CBA Events Database Update for " . $society_name . "</h2>";

    // Generate tables to insert and edit records in the cba_events database for the
    // given society. Start by building a message row

    $return .= "<p id = 'messagearea' style = 'height: 1.5rem; font-weight: bold; text-align: center;  margin-top: 2vh; margin-bottom: 2vh;'>&nbsp;</p>";

    // Build an event insert row

    $return .= "
    <div style = 'width: 95%; margin-right: auto; margin-left: auto; border: 1px solid black; padding-bottom: 2vw; background: whitesmoke;'>
        <p style = 'font-weight: bold; margin-top: .5rem; margin-bottom: 1rem;'>Insert Event Panel</p> 
        <form  enctype='multipart/form-data' method='post' name='event0'> 

            <label for ='eventdate0'>&nbsp;&nbsp;Event Date : </label>
            <input type='text' id='eventdate0' name = 'event_date' maxlength='8' size='8' autocomplete = 'off'
            placeholder = ''
                title = 'Click to enter the date of this event'
                onmousedown = 'applyDatepicker(\"eventdate0\");'>  
            <label for ='eventtime0'>&nbsp;&nbsp;Event Time : </label>
            <input type='time' id='eventtime0' name = 'event_time' autocomplete = 'off'
                value = '00:00';
                title = 'Click to enter the time of this event'> 

            <button id = 'eventinsertbutton' type='button'
                onclick = 'insertEvent();'
                title = 'Create new Event record'>Insert</button>
        </form>
    </div>
    <br>";

    # create an 'edit' row for each existing event for this society

    $return .= "
    <div style = 'width: 95%; margin-right: auto; margin-left: auto; border: 1px solid black; background: whitesmoke; overflow-y: auto;'>
        <div style = 'height: 40vh; margin-bottom: 2vw; overflow-y: auto;'>
            <p style = 'font-weight: bold; margin-top: .5rem; margin-bottom: 1rem;'>Edit Event Panel</p>";

    $sql = "SELECT * FROM cba_events WHERE
                    society_id = '$society_id' AND
                    event_is_preview =  'N'
                    ORDER BY event_datetime DESC;";

    $result = sql_result_for_location($sql, 2);

    $i = 0;
    while ($row = mysqli_fetch_array($result)) {
        $event_id = $row['event_id'];
        $event_datestring = $row['event_datetime'];
        $event_date = substr($event_datestring, 0, 10);
        $event_time = substr($event_datestring, 11, 5);
        $event_title = $row['event_title'];

        $i++;
        $return .= "
            <form  enctype='multipart/form-data' method='post' name='event" . $i ."'>
             
                <button id = 'eventextractbutton' type='button'
                    onclick = 'extractEvent();'
                    title = 'Pull a formatted copy of the data for this Event record into your Download folder'>Extract</button>

                <label for ='eventdate" . $i ."'>&nbsp;&nbsp;Event Date : </label>
                <input type='text' id='eventdate" . $i ."' maxlength='8' size='8' autocomplete = 'off'
                    value = " . $event_date . "
                    onclick = 'displayEventEditPanel(" . $event_id . ");'>  
                <label for ='eventtime" . $i ."'>&nbsp;&nbsp;Event Time : </label>
                <input type='time' id='eventtime" . $i ."' autocomplete = 'off'
                    value = " . $event_time . "
                    onclick = 'displayEventEditPanel(" . $event_id . ");'> 
                <label for ='eventtitle" . $i ."'>&nbsp;&nbsp;Event Title : </label>
                <input type='text'  id='eventtitle" . $i ."' maxlength='200' size='60' autocomplete = 'off'
                    value = '" . $event_title . "'
                    onclick = 'displayEventEditPanel(" . $event_id . ");'>    

                <button id = 'eventupdatebutton' type='button'
                    onclick = 'displayEventEditPanel(" . $event_id . ");'
                    title = 'Update this Event'>Edit</button>
                <button id = 'eventdeletebutton' type='button'
                    onclick = 'deleteEvent(" . $event_id . ");'
                    title = 'Delete this Event'>Delete</button>   
            </form>";
    }

    $return .= "</div></div></div>";
    echo $return;
}

#####################  get_event_data ####################

if ($helper_type == "get_event_data") {
    $event_id = $_POST['event_id'];

    $sql = "SELECT * FROM cba_events WHERE
                    event_id = '$event_id';";

    $result = sql_result_for_location($sql, 3);
    $row = mysqli_fetch_array($result);

    // place the  fields in an associative array

    $returns = array();

    $returns['societyId'] = $row['society_id'];
    $returns['eventDatetime'] = $row['event_datetime'];
    $returns['eventType'] = $row['event_type'];
    $returns['eventTitle'] = $row['event_title'];
    $returns['presenter'] = $row['presenter'];
    $returns['synopsis'] = $row['synopsis'];
    $returns['standardBackgroundOverridden'] = $row['standard_background_overridden'];
    $returns['eventPublished'] = $row['event_published'];

    $return = json_encode($returns);
 
    echo $return;

    // if there's a user background, copy the graphic in user_backgrounds for this
    // event into user_background_previews. We're now clear to muck around with 
    // both the non-grahic field and the grahic field as much as we want before
    // calling for a preview

    if ($row['standard_background_overridden'] === "Y") {
        if (!copy(
            "../user_backgrounds/" . $event_id . ".jpg",
            "../user_background_previews/" . $row['society_id'] . ".jpg"
        )) {
 
            $sql = "UPDATE cba_events SET
                        standard_background_overridden = 'N'
                    WHERE
                        event_id = '$event_id';";

            $result = sql_result_for_location($sql, 14);
        }
    }

}

#####################  insert_event ####################

if ($helper_type == "insert_event") {
    $society_id = $_POST['society_id'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];

    $event_datetime = $event_date . " " . $event_time . ":00";

    // check that a date has been selected

    if (substr($event_datetime, 0, 1) === ' ') {
        echo "Oops - you need to specify an event date and time";
        disconnect_from_database();
        exit(0);
    }

    // check that the proposed event is actually in the future!

    $midnight_tonight_datetime = date("Y-m-d 23:59:59", time());
    if ($event_datetime < $midnight_tonight_datetime) {
        echo "Oops - event date needs to specify a day in the future!";
        disconnect_from_database();
        exit(0);
    }

    // check that there's not already an event at this time for this society

    $sql = "SELECT * FROM cba_events WHERE
                    society_id = '$society_id' AND
                    event_datetime = '$event_datetime';";

    $result = sql_result_for_location($sql, 4);
    if (mysqli_num_rows($result) >= 1) {
        echo "Oops - you already have an event for this date-time";
        disconnect_from_database();
        exit(0);
    }
    
    // OK - insert the record

    $sql = "INSERT INTO cba_events (
                society_id,
                event_datetime, 
                event_type, 
                event_title, 
                presenter, 
                synopsis, 
                standard_background_overridden, 
                event_published,
                event_is_preview)
            VALUES (
                '$society_id',
                '$event_datetime',
                '',
                '',
                '',
                '',
                'N',
                'N',
                'N');";

    $result = sql_result_for_location($sql, 5);

    // return the event_id that has been created by the insert

    echo mysqli_insert_id($con);
}

#####################  upload_user_background_preview ####################

if ($helper_type === "upload_user_background_preview") {
    $society_id = $_POST['society_id'];
    $event_id = $_POST['event_id'];

    $upload_target = "../user_background_previews/" . $society_id . ".jpg";

    if (!move_uploaded_file($_FILES['user_background_filename'] ['tmp_name'], $upload_target)) {
        $sql = "UPDATE cba_events SET
                        standard_background_overridden = 'N'
                    WHERE
                        event_id = '$event_id';";

        $result = sql_result_for_location($sql, 6);
    }
}

#####################  update_event ####################

if ($helper_type == "update_event") {
    $society_id = $_POST['society_id'];
    $event_id = $_POST['event_id'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $event_type = $_POST['event_type'];
    $event_title = prepareStringforSQL($_POST['event_title']);
    $presenter = prepareStringforSQL($_POST['presenter']);
    $synopsis = prepareStringforSQL($_POST['synopsis']);
    $standard_background_overridden = $_POST['standard_background_overridden'];
    $event_published = $_POST['event_published'];

    $event_datetime = $event_date . " " . $event_time . ":00";

    $sql = "UPDATE cba_events SET
                event_datetime = '$event_datetime',
                event_type = '$event_type',
                event_title = '$event_title',
                presenter = '$presenter',
                synopsis = '$synopsis',
                standard_background_overridden = '$standard_background_overridden',
                event_published = '$event_published'
            WHERE
                event_id = '$event_id';";

    $result = sql_result_for_location($sql, 7);

    // if $_FILES["user_background_filename"] is set, then the society's updater may have uploaded a new
    // graphic to user_background_previews and we thus need to move this to user_backgrounds

    if (!empty($_FILES["user_background_filename"]['tmp_name'])) {
        $upload_target = "../user_background_previews/" . $society_id . ".jpg";

        if (!rename(
            "../user_background_previews/" . $society_id . ".jpg",
            "../user_backgrounds/" . $event_id . ".jpg"
        )) {
            $sql = "UPDATE cba_events SET
                        standard_background_overridden = 'N'
                    WHERE
                        event_id = '$event_id';";

            $result = sql_result_for_location($sql, 8);
        }
    }
}

#####################  delete_event ####################

if ($helper_type == "delete_event") {
    $event_id = $_POST['event_id'];
     
    $sql = "DELETE FROM cba_events WHERE
                    event_id = '$event_id';";

    $result = sql_result_for_location($sql, 97);

    echo "OK";
}

#####################  prepare_event_preview ####################

if ($helper_type == "prepare_event_preview") {
    $society_id = $_POST['society_id'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $event_type = $_POST['event_type'];
    $event_title = $_POST['event_title'];
    $presenter = $_POST['presenter'];
    $synopsis = $_POST['synopsis'];
    $standard_background_overridden = $_POST['standard_background_overridden'];
    $event_published = $_POST['event_published'];

    $event_datetime = $event_date . " " . $event_time . ":00";

    // see if a preview record exists yet for this society

    $result = sql_result_for_location('START TRANSACTION', 10);

    $preview_exists = "N";
    $sql = "SELECT * FROM cba_events WHERE
                    society_id = '$society_id' AND
                    event_is_preview = 'Y';";

    $result = sql_result_for_location($sql, 11);
    if (mysqli_num_rows($result) >= 1) {
        $preview_exists = "Y";
    }

    if ($preview_exists === "N") {
        // create a preview record with the values for the current event display panel

        $sql = "INSERT INTO cba_events (
                society_id,
                event_datetime, 
                event_type, 
                event_title, 
                presenter, 
                synopsis, 
                standard_background_overridden, 
                event_published,
                event_is_preview)
            VALUES (
                '$society_id',
                '$event_datetime',
                '$event_type',
                '$event_title',
                '$presenter',
                '$synopsis',
                'standard_background_overridden',
                '$event_published',
                'Y');";

        $result = sql_result_for_location($sql, 12);
    } else {
        // edit the existing record
        $sql = "UPDATE cba_events SET
                event_datetime = '$event_datetime',
                event_type = '$event_type',
                event_title = '$event_title',
                presenter = '$presenter',
                synopsis = '$synopsis',
                standard_background_overridden = '$standard_background_overridden', 
                event_published = '$event_published'
            WHERE
                society_id = '$society_id' AND
                event_is_preview = 'Y';";

        $result = sql_result_for_location($sql, 13);
    }

    $result = sql_result_for_location('COMMIT', 15);
}

disconnect_from_database();
