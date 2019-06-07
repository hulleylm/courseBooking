<!DOCTYPE html>
<html>
<head>
  <title>Book a course</title>
</head>
<body>
  <h1>Comunity college courses</h1>

  <?php

  // courseList() shows the dropdown menu for course selection.
  //It is populated with data from the database
  function courseList($pdo) {

    // mysql query which gets all values from the courses table
    $stmt= $pdo->query("SELECT courseName FROM courses ORDER BY courseName");

    // setting up the drop down menu and starting the form
    echo "Select a course: <br>";
    echo "<form name= 'selectCourse' action='' method= 'POST'>";
    echo "<select name='selectCourse' onChange= 'document.selectCourse.submit()'>";
    echo "<option value='None'>Select a course</option>";

    // initialise an empty array which later ensures the course names
    // don't show up multiple times
    $courseNames= array();

    // foreach loop outputs each query as an option in the dropdown
    foreach ($stmt as $row) {

      // if the course name is already in the list, don't add it again
      if (in_array($row['courseName'], $courseNames)) {
        $courseNames[]= $row['courseName'];

      // if the course name is not already an option, add it
      } else {
        $courseNames[]= $row['courseName'];
        $option= $row['courseName'];
        echo "<option value='{$option}'";

        // this stops the drop down box from immediately resetting the value
        if (isset($_POST['selectCourse'])) {
          if ($_POST['selectCourse']== $option) {
            echo 'selected';
          }
        }
        echo ">{$option}</option>";
      }
    }
    // end the drop down menu
    echo "</select>";
  }

  // timeList() shows the dropdown menu for time selection based on the courses
  // selected. It is populated with data from the database
  function timeList($pdo, $chosenCourse) {
    // a prepared statement makes the site more secure
    $timeTpl = "SELECT courseTime FROM courses  WHERE courseName=:courseName";
    $timeStmt = $pdo->prepare($timeTpl);
    $timeStmt->bindValue(':courseName', $chosenCourse);
    $timeStmt->execute();

    // set up a drop down menu to display the time options for the chosen course
    echo "<br><br>Select a time: <br>";
    echo "<select name='selectTime' onChange= 'document.selectCourse.submit()'>";
    echo "<option value='None'>Select a time</option>";

    // foreach loop outputs each query as an option in the dropdown
    foreach ($timeStmt as $row) {
      $option= $row['courseTime'];
      echo "<option value='{$option}'";

      if (isset($_POST['selectTime'])) {

        if ($_POST['selectTime']== $option) {
          echo 'selected';
        }
      }
      echo ">{$option}</option>";
    }
    echo "</select>";
  }

  // this function retrieves the ID of the selected course based on the chosen
  // day and time.
  function findCourseID($pdo, $chosenCourse, $chosenTime) {

    // prepare statement to retrieve course ID based on name and time
    $courseTpl = "SELECT courseID FROM courses  WHERE
    courseName=:courseName AND courseTime=:courseTime";
    $courseStmt = $pdo->prepare($courseTpl);
    $courseStmt->bindValue(':courseName', $chosenCourse);
    $courseStmt->bindValue(':courseTime', $chosenTime);
    $courseStmt->execute();

    // store returned courseID as $courseID
    while ($row = $courseStmt->fetch()) {
      $courseID = $row['courseID'];
    }
    return $courseID;
  }

  // function to output the input forms for the booking
  function nameAndNumber() {
    echo "Name: <input type='text' name='name'><br>";
    echo "<br>Number: <input type='text' name='phoneNumber'><br><br>";
    echo "<input type='submit' name= 'submitBooking' value='Submit booking'>";
    echo "</form>";
  }

  // validate the name input
  function testName($input) {
    $nameRegex = "/(^[^A-Za-z])|[^A-Za-z\'\-\s]|(-{2,})|(\'{2,})|([-\']{2,})/";
    if ((strlen($input) < 1) | (strlen($input) >= 50)) {
      return 0;
    }
    if (!preg_match($nameRegex, $input)) {
      $input = addslashes($input);
    } else {
      $input = 0;
    }
    return $input;
  }

  // validate the phone nuber input
  function testNumber($input) {
    if ((strlen($input) < 9) | (strlen($input) > 10)) {
      return 0;
    }
    if (!preg_match($numRegex, $input)) {
      $input = preg_replace('/\s/', '', $input);
    } else {
      $input = 0;
    }
    return $input;
  }

  // query the database to check there are enough spaces left on the course
  function checkCapacity($pdo, $courseID) {
    $capacityTpl = "SELECT capacity FROM courses  WHERE
    courseID=:courseID";
    $capacityStmt = $pdo->prepare($capacityTpl);
    $capacityStmt->bindValue(':courseID', $courseID);
    $capacityStmt->execute();

    while ($row = $capacityStmt->fetch()) {
      $capacity = $row['capacity'];
    }

    return $capacity;
  }

  // update the capacity of the course
  function changeCapacity($pdo, $courseID) {
    $updateTpl = "UPDATE courses SET capacity=capacity-1 WHERE courseID=:courseID";
    $updateStmt = $pdo->prepare($updateTpl);
    $updateStmt->bindValue(':courseID', $courseID);
    $updateStmt->execute();
  }

  // Insert the data into a new row of the database
  function insertData($pdo, $name, $phoneNumber, $courseID, $chosenCourse,
   $chosenTime) {

     //generate a uniqueID by which to identify the booking
    $userId = uniqid();

    //Add a new row to the dtabase containing the booking information
    $bookingTpl = "INSERT INTO bookings VALUES
      ('$userId', '$courseID', '$name', '$phoneNumber')";
    $bookingStmt = $pdo->prepare($bookingTpl);
    $bookingStmt->execute();
    echo "<br>Hi $name, you have booked onto $chosenCourse: $chosenTime";
  }

  // initial information needed to connect to database
  $db_hostname = "student.csc.liv.ac.uk";
  $db_database = "sglhulle";
  $db_username = "sglhulle";
  $db_password = "bl00catf1sh";
  $db_charset = "utf8mb4";

  $dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$db_charset";
  $opt = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
  );

  try {
    $pdo= new PDO($dsn,$db_username,$db_password,$opt);

    $chosenCourse = '';
    $chosenTime = '';

    // run courseList function to show a drop down list of available courses
    courseList($pdo);

    // if a course has been selected, assign the chosen course to $chosenCourse
    if (isset($_POST['selectCourse'])) {
      $chosenCourse = $_POST['selectCourse'];
    }

    // run timeList function to show a drop down list of available
    // times for the selected course
    timeList($pdo, $chosenCourse);

    // if a time has been selected, assign the chosen course to $chosenTime
    if (isset($_POST['selectTime'])) {
      $chosenTime = $_POST['selectTime'];
    }

    // run findCourseId function to retrieve the course ID associated with
    // the chosen course name and time
    $courseID = findCourseID ($pdo, $chosenCourse, $chosenTime);

    echo "<h3>Enter your details here:</h3>";

    // run nameAndNumber function to output the form that collects the user's
    // name and number
    $name = '';
    $phoneNumber = '';
    nameAndNumber();

    // check wether the submit button has been pressed
    if (isset($_POST['submitBooking'])) {
      $name = testName($_POST['name']);
      $phoneNumber = ($_POST['phoneNumber']);
      if (checkCapacity($pdo, $courseID) < 1) {
        echo "<br>fully booked, please try again";
      } elseif ($name == "0") {
        echo "<br>please enter a valid name";
      } elseif (testNumber($phoneNumber) == "0") {
        echo "<br>please enter a valid number";
      } else {
        //if all inputs are valid, update the database with the new booking
        changeCapacity($pdo, $courseID);
        insertData($pdo, $name, $phoneNumber, $courseID, $chosenCourse,
         $chosenTime);
      }
    }

    $pdo = NULL;
  }

  catch (PDOException $e) {
    exit("PDO Error: ".$e->getMessage()."<br>");
  }
  ?>
</body>
</html>
