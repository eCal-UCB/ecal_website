<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

    <script src="d3.js"></script>
    <script src="jquery-1.11.3.js"></script>
    <script src="saveSvgAsPng-gh-pages/saveSvgAsPng.js"></script>

    <style>
        body{

            font: 12px sans-serif;

        }

        path{

            stroke: steelblue;
            stroke-width: 2;
            fill: none;

        }

        .axis path,
        .axis line {
            fill: none;
            stroke: black;
            stroke-width: 1;
            shape-rendering: crispEdges;
        }

        .axis text {

            font-family: sans-serif;
            font-size: 12px;
        }

        .line {
            fill: none;
            stroke: steelblue;
            stroke-width: 1.5px;
        }
    </style>

    <head>
        <title>Scott Moura</title>
        <meta http-equiv="content-type"
              content="text/html; charset=utf-8"/>
        <link rel="stylesheet" href="smoura-cee.css" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">
        <link rel="icon" href="img/cal-icon.png" type="image/png" />
        <link rel="import" href="include/analytics.html"></script>


        <script>

            ////////////////////////////////////////////////////////////////////////////////
            //////////////////////////////  parse function  ////////////////////////////////
            ////////////////////////////////////////////////////////////////////////////////

            //INPUT: The location of the file to be parsed (as a string!)
            //OUTPUT: An array of objects. Each object contains a Voltage, Current,
            //        Time, and Temperature value.

            function parseCSV(fileName) {

                //Load the file data into string contents. Once file contents are in a string,
                //parse through it to graph

                var lines = null;
                var scriptUrl = fileName;
                $.ajax({
                    url: 'CSV_Data_Files/'.concat(fileName),
                    type: 'get',
                    dataType: 'text',
                    async: false,
                    success: function (data) {

                        lines = data.split("\n");

                    }

                });

            //we now create an array of objects. Each object has a Time, Voltage,
            //Current, and Temperature value. The very last entry of this array
            //will contain unit information, as well as file name and whether or
            //not temperature was plotted for the experiment.

            var Data = [];
            var temperature = 0;
            var previousTemperature = 0;
            var tempNotAvailable = 1; //if 1 then there were no temperature measurements taken

            //split the string by lines, and store each line as an entry
            //of an array.

            //use lineIdx to identify which row number the
            //header is on.

            var lineIdx = 0;

            //run a simple search for the row that contains the header info.
            //The first term of the header row is always "Test"

            while (lines[lineIdx].split(",")[0] !== "Test") {

                lineIdx = lineIdx + 1;
            }

            //now that we know where the header row is, we can parse for
            //all the metadata.

            var i=0;
            var row=lines[i].split(","); //put all comma separated entries into array slots
            var testNumber=0;
            var date="";
            var cellType="";
            var chemistry="";
            var description="";

            while(i<lineIdx){

                if(row[0]==="Test:"){

                    testNumber=+row[1];

                }

                if(row[0]==="Start Time:"){

                    date=row[1];

                }

                if(row[0]==="Product ID:"){

                    cellType=row[1];

                }

		//NOTE: This must eventually be fixed. A convention needs to be agreed upon for how to include chemistry information
		//Then we should parse for that information. As of now, this is a dummy bit of code, and the chemistry must be included manually.

                if(row[0]==="TestRegime Version:"){

                    chemistry=row[1];

                }

                if(row[0]==="Test Description:"){

                    description=row[1];

                }

                i=i+1;
                row=lines[i].split(","); //update row

            }

            //Split the header row into an array of entries.
            //each entry is split up based on the comma delimiter.

            var headerRow = lines[lineIdx].split(",");

            //Use the rowIdx variable to traverse through the header row,
            //and then store the appropriate header indexes in timeIdx,
            //volageIdx, currentIdx, and tempIdx.

            var rowIdx = 0;
            var timeIdx = 0;
            var voltageIdx = 0;
            var currentIdx = 0;
            var tempIdx = 0;
            var units = [];

            while (rowIdx < headerRow.length) {

                if ((headerRow[rowIdx] === "Total Time (Seconds)") || (headerRow[rowIdx] === "Total Time (Hours)")) {

                    timeIdx = rowIdx;

                    if (headerRow[timeIdx] === "Total Time (Seconds)") {

                        units[timeIdx] = ("Seconds");

                    }

                    else {

                        units[timeIdx] = ("Hours");

                    }

                }

                if ((headerRow[rowIdx] === "Voltage (mV)") || (headerRow[rowIdx] === "Voltage (V)")) {

                    voltageIdx = rowIdx;

                    if (headerRow[voltageIdx] === "Voltage (mV)") {

                        units[voltageIdx] = ("mV");

                    }

                    else {

                        units[voltageIdx] = ("V");

                    }

                }

                if ((headerRow[rowIdx] === "Current (mA)") || (headerRow[rowIdx] === "Current (A)")) {

                    currentIdx = rowIdx;

                    if (headerRow[currentIdx] === "Current (mA)") {

                        units[currentIdx] = ("mA");

                    }

                    else {

                        units[currentIdx] = ("A");

                    }

                }

                //INCLUDE ALL TEMP OPTIONS!!!

                if ((headerRow[rowIdx].search("Temperature") !== -1)||(headerRow[rowIdx].search("TC1") !== -1)||(headerRow[rowIdx].search("Trs") !== -1)) {

                    tempIdx = rowIdx;

                }

                //move on to next row.

                rowIdx = rowIdx + 1;

            }

            //Remove some unnecessary lines.

            lines.splice(0, lineIdx + 1);

            //use l to store the length of the Data array

            var l = (lines.length) - 1;

            for (var i = 0; i < l; i++) {

                //Temperature is a problem since values are sparsely recorded.
                //Temperature readings aren't taken with each sample...
                //As a trivial solution, if there is no temp value recorded at a
                //particular time instance, assume it is the
                //same as the previous instance.

                if (lines[i].split(",")[tempIdx] === "") {

                    temperature = previousTemperature;

                }

                else {

                    temperature = lines[i].split(",")[tempIdx];
                    previousTemperature = temperature;
                    tempNotAvailable = 0; //Temp HAS been recorded.

                }

                Data.push({
                    Time: lines[i].split(",")[timeIdx],
                    Voltage: lines[i].split(",")[voltageIdx],
                    Current: lines[i].split(",")[currentIdx],
                    Temperature: temperature

                });


            }

            Data.push({

                tempNotPlottable: tempNotAvailable,
                fname: fileName, //so we can pass the filename on to the next function call
                timeUnit: units[timeIdx],
                voltageUnit: units[voltageIdx],
                currentUnit: units[currentIdx],
                Test_Number: testNumber,
                Date: date,
                Cell_Type: cellType,
                Chemistry: chemistry,
                Description: description

            });

            return Data;

        }

            //////////////////////////////////////////////////////////////////////////////////
            ////////////////////////////////  plot function  /////////////////////////////////
            //////////////////////////////////////////////////////////////////////////////////
            //
            //        //INPUT: 1) The array of objects, which holds the data to be plotted
            //        //       2) The quantity we wish to plot (Voltage, Current, or Temperature)
            //        //OUTPUT: none

            function plot(Data, quantity) {

                var l = Data.length - 1;
                var fname = Data[l].fname.toString(); //a variable to hold the file name
                fname = fname.substring(0, fname.length - 4); //remove the ".CSV" from the string

                //Create variables to make life simpler.

                var MARGINS = {top: 30, right: 20, bottom: 30, left: 50},
                WIDTH = 1325 - MARGINS.left - MARGINS.right,
                        HEIGHT = 650 - MARGINS.top - MARGINS.bottom;

                var x = d3.scale.linear().range([0, WIDTH]);

                var y = d3.scale.linear().range([HEIGHT, MARGINS.top]);

                var xAxis = d3.svg.axis().scale(x)
                        .orient("bottom");

                var yAxis = d3.svg.axis().scale(y)
                        .orient("left");

                var unit; //stores the unit of the variable

                if (quantity === "Current") {

                    var lineFunc = d3.svg.line()
                            .x(function (d) {
                                return x(+d.Time);
                            })
                            .y(function (d) {
                                return y(+d.Current);
                            })
                            .interpolate('linear');

                    var canvas = d3.select("body")
                            .append("svg")
                            .attr("id", "visualisation" + Data[l].fname + quantity.toString()[0])
                            .attr("width", WIDTH + MARGINS.left + MARGINS.right)
                            .attr("height", HEIGHT + MARGINS.top + MARGINS.bottom);

                    canvas.append("g")
                            .attr("transform", "translate(" + MARGINS.left + "," + MARGINS.top + ")");

                    x.domain(d3.extent(Data, function (d) {
                        return +d.Time;
                    }));

                    y.domain(d3.extent(Data, function (d) {
                        return +d.Current;
                    }));

                    unit = Data[l].currentUnit;

                }

                else if (quantity === "Voltage") {

                    var lineFunc = d3.svg.line()
                            .x(function (d) {
                                return x(+d.Time);
                            })
                            .y(function (d) {
                                return y(+d.Voltage);
                            })
                            .interpolate('linear');

                    var canvas = d3.select("body")
                            .append("svg")
                            .attr("id", "visualisation" + Data[l].fname + quantity.toString()[0])
                            .attr("width", WIDTH + MARGINS.left + MARGINS.right)
                            .attr("height", HEIGHT + MARGINS.top + MARGINS.bottom);

                    canvas.append("g")
                            .attr("transform", "translate(" + MARGINS.left + "," + MARGINS.top + ")");

                    x.domain(d3.extent(Data, function (d) {
                        return +d.Time;
                    }));

                    y.domain(d3.extent(Data, function (d) {
                        return +d.Voltage;
                    }));

                    unit = Data[l].voltageUnit;

                }

                else if (quantity === "Temperature") {

                    //NEED TO FIX THIS CONDITION!!!
                    //window.alert(Data[l].tempNotPlottable);
                    //if ((Data[l].tempNotPlottable === 0)||(Data[l].tempNotPlottable === 1)) {
                    if(true){
                        var lineFunc = d3.svg.line()
                                .x(function (d) {
                                    return x(+d.Time);
                                })
                                .y(function (d) {
                                    return y(+d.Temperature);
                                })
                                .interpolate('linear');

                        var canvas = d3.select("body")
                                .append("svg")
                                .attr("id", "visualisation" + Data[l].fname + quantity.toString()[0])
                                .attr("width", WIDTH + MARGINS.left + MARGINS.right)
                                .attr("height", HEIGHT + MARGINS.top + MARGINS.bottom);

                        canvas.append("g")
                                .attr("transform", "translate(" + MARGINS.left + "," + MARGINS.top + ")");

                        x.domain(d3.extent(Data, function (d) {
                            return +d.Time;
                        }));

                        y.domain(d3.extent(Data, function (d) {
                            return +d.Temperature;
                        }));

                        unit = "°C";

                    }

                    else {

                        //do nothing

                    }

                }

                //create the svg element.

                canvas.append('path')
                        .attr("class", "line")
                        .attr('transform', 'translate(' + MARGINS.left + ',0)')
                        .attr('d', lineFunc(Data));

                //add the x axis

                canvas.append("g")
                        .attr('class', 'x axis')
                        .attr('transform', 'translate(' + MARGINS.left + ',' + HEIGHT + ')')
                        .call(xAxis);

                // Add the text label for the x axis

                canvas.append("text")
                        .attr("transform", "translate(" + (WIDTH / 2) + " ," + (HEIGHT + MARGINS.bottom) + ")")
                        .style("text-anchor", "middle")
                        .style("font-size", "12px")
                        .text("Time (" + Data[l].timeUnit + ")");

                // Add the y axis and its label

                canvas.append("g")
                        .attr("class", "y axis")
                        .attr('transform', 'translate(' + MARGINS.left + ',0 )')
                        .call(yAxis)
                        .append("text")
                        .attr("transform", "rotate(-90)")
                        .attr("y", 0 - (MARGINS.left))
                        .attr("x", (0 - (HEIGHT / 2)))
                        .attr("dy", ".71em")
                        .style("text-anchor", "middle")
                        .text(quantity + " (" + unit + ")");

                //Now we will save the visualization as a png file
                //The agreed upon filname format is:
                // Test<test number><first letter of quantity (upper case)>

                fname = fname.concat(quantity.toString()[0]);
                saveSvgAsPng(document.getElementById("visualisation" + Data[l].fname + quantity.toString()[0]), fname);

                //Delete the svg to clear up memory.

                d3.select("svg").remove();


            }
            ////Delete File////
            function deleteFile(fileName) {

                 $.post("deleteAux.php",
                         {
                             fileName: "CSV_Data_Files/".concat(fileName.toString())
                         },
                         function(){

                             //required callback function that does nothing
                         }
                       );



             }

            //update the database

            function fillDatabase(Test_Number,Date,Cell_Type,Chemistry,Description) {



            $.post("databaseAux.php",
                    {
                        Test_Number: Test_Number,
                        Date: Date,
                        Cell_Type: Cell_Type,
                        Chemistry: Chemistry,
                        Description: Description

                    },

            function () {
                //A callback function that does nothing here

            });



        }



        </script>

    </head>
        <body>
<!--<script type="text/javascript" src="include/analytics.js"></script>-->
            <div id="content">

                <script type="text/javascript" src="include/header.js"></script>

                <div id="main">

                  <div id="title-line"><div id="title">
                  Software
                  </div></div>
                  <div style="clear:both;"></div>
                  <br/>


                  <div id="title-line"><div id="section">
                  SPMeT
                  </div></div>
                  <div style="clear:both;"></div>
                  <i>Single Particle Model with Electrolyte and Temperature: An electrochemical battery model</i><br/>

                  <div style="float:left; margin:8px; width:50%;">
                    <img src="img/SPMe.png" width="400px"/><br/>
                  </div>
                  <div style="float:left; padding-top: 10px; width:48%;">
                    The code is open source and available at:<br/>
                    <a href="https://scott-moura.github.io/SPMeT/"><img class="photo" src="img/GitHub-Logo.png" width="100px" style="padding:5px"/></a><br/>
                  </div>

                  <div style="clear:both;"></div>
                  <br/>



                  <div id="title-line"><div id="section">
                  FastDFN
                  </div></div>
                  <div style="clear:both;"></div>
                  <i>Doyle-Fuller-Newman (DFN) Electrochemical-Thermal Battery Model Simulator</i><br/>

                  <div style="float:left; margin:8px; width:50%;">
                    <img src="img/dfn_states_freeze_NEW.png" width="100%"/><br/>
                  </div>
                  <div style="float:left; padding-top: 10px; width:48%;">
                    The code is open source and available at:<br/>
                    <a href="https://github.com/scott-moura/fastDFN"><img class="photo" src="img/GitHub-Logo.png" width="100px" style="padding:5px"/></a><br/>
                  </div>

                  <div style="clear:both;"></div>
                  <br/>


                  <div id="title-line"><div id="section">
                  <a href="http://wallflower.cc/">Wallflower.cc</a>
                  </div></div>
                  <div style="clear:both;"></div>
                  <i>An Internet-of-Things (IoT) Platform</i><br/>

                  <div style="float:left; margin:8px; width:50%;">
                    <img src="img/network.png" width="100%"/><br/>
                  </div>
                  <div style="float:left; padding-top: 10px; width:48%;">


                    The code is open source and available at:<br/>
                    <a href="https://github.com/wallflowercc/wallflower-pico"><img class="photo" src="img/GitHub-Logo.png" width="100px" style="padding:5px"/></a><br/>
                  </div>

                  <div style="clear:both;"></div>
                  <br/>



                  <div id="title-line"><div id="title">
                  Battery Test Data
                  </div></div>
                  <div style="clear:both;"></div>
                  <br/>

                        <table width="600" border="1" cellpadding="1" cellspacing="1">
                            <tr>
                                <th><a href="data.php?sort=Test_Number">Test Number</a></th>
                                <th><a href="data.php?sort=Date">Date</a></th>
                                <th><a href="data.php?sort=Cell_Type">Cell Type</a></th>
                                <th><a href="data.php?sort=Chemistry">Chemistry</a></th>
                                <th><a href="data.php?sort=Description">Description</a></th>
                                <th>Graph</th>
                                <th><a href="data.php?sort=Download">Download</a></th>
                            </tr>

                            <?php

                            ###Connect to MySQL; use "ecal" database, in which "metadata" table is located
                            $link = mysqli_connect('mysql', 'ecal', '4Pbc6Z5E8NHUZ80cg9fY');

                            if (!$link) {
                                $output = 'Unable to connect to the database server: '. mysqli_error($link);
                                include 'output.html.php';
                                exit();
                            }
                            if (!mysqli_set_charset($link, 'utf8')) {
                                $output = 'Unable to set database connection encoding.';
                                include 'output.html.php';
                                exit();
                            }
                            if (!mysqli_select_db($link, 'ecal')) {
                                $output = 'Unable to locate the ecal database.';
                                include 'output.html.php';
                                exit();
                            }

///////////////////////////////////////////Only runs if IP Address matches the eCAL lab computer////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            if ($_SERVER['REMOTE_ADDR'] == '169.229.157.2') {

                            ####Load from .csv files into metadata table
                            $count = 0;
                            ?>
                            <script>
                                var jcount = 0;
                                var domTarget = "dom-target";
                            </script>
                            <?php
                            #CSV Data Files are made available on OCF Server (after manual transfer from Lab Computer using Cyberduck)
                            foreach (glob("CSV_Data_Files/*.csv") as $file_name) {

                                $File_Number = substr($file_name, 19, strlen($file_name) - 23);
                                $count = $count + 1;

                                #GRAPH GENERATION
                                ?>
                                <div id="dom-target<?php echo $count; ?>" style="display: none;">
                                    <?php
                                    $output = substr($file_name, 15, strlen($file_name) - 15);
                                    echo htmlspecialchars($output); /* You have to escape because the result
                                      will not be valid HTML otherwise. */
                                    ?>
                                </div>
                                <script>

                                    jcount = jcount + 1;
                                    domTarget = "dom-target";
                                    domTarget = domTarget.concat(jcount.toString());
                                    var div = document.getElementById(domTarget);
                                    var fileName = div.textContent;
                                    var idx1 = fileName.search('Test');
                                    var idx2 = fileName.search(".csv");
                                    fileName = fileName.substring(idx1, idx2 + 4);
                                    var Data = parseCSV(fileName.toString());
                                    var l=(Data.length)-1;
                                    fillDatabase(Data[l].Test_Number,Data[l].Date,Data[l].Cell_Type,Data[l].Model,Data[l].Description);


                                    plot(Data, "Current");
                                    plot(Data, "Voltage");
                                    plot(Data, "Temperature");
                                    deleteFile(fileName);
                                    location.reload(true);


                                </script>

                                <?php
                                   break;
                            }
                            ?>
                            <?php
                            }
///////////////////////////////////////////End of IP address restriction to Lab Computer////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                            ##create graph URLs for metadata table.
                            //Since the graphs are manually uploaded via Cyberduck, the graph upload is not a sequential part of the program.
                            //Upon any pageload, do this for all database entries:
                            //If a database entry does NOT have a voltage plot link, then search the graphs folder for a V,I, and T graph of that test number.
                            //Go through each element of the database and see if there is an entry for a voltage link

                            $check = "SELECT * FROM metadata";
                            $result3 = mysqli_query($link, $check);
                            if (!$result3) {
                                $error = 'Error fetching metadata: ' . mysqli_error($link);
                                include 'error.html.php';
                                exit();
                            }

                            while ($row = mysqli_fetch_array($result3)) {
                                //If there is no Graph entry for this row,
                                //search the graphs folder for the appropriate graphs.
                                if (empty($row['Graph'])) {
                                    $File_Number = $row['Test_Number'];
                                    $Graph = 'NULL';
                                    if (file_exists("/home/e/ec/ecal/public_html/Graphs/Test" . $File_Number . "C.png")) {
                                        $Graph = $Graph . '<a href="Graphs/Test' . $File_Number . 'C.png">Current</a> / ';
                                    }
                                    if (file_exists("/home/e/ec/ecal/public_html/Graphs/Test" . $File_Number . "V.png")) {
                                        $Graph = $Graph . '<a href="Graphs/Test' . $File_Number . 'V.png">Voltage</a> / ';
                                    }
                                    if (file_exists("/home/e/ec/ecal/public_html/Graphs/Test" . $File_Number . "T.png")) {
                                        $Graph = $Graph . '<a href="Graphs/Test' . $File_Number . 'T.png">Temperature</a> / ';
                                    }
                                    if (strlen($Graph) >= 4) {
                                        $Graph = substr($Graph, 4, strlen($Graph) - 7);
                                    }

                                    #Load Graph into metadata table
                                    $updateGraph = "UPDATE metadata
                                                SET Graph = '" . $Graph . "'
                                                WHERE Test_Number='" . $row['Test_Number'] . "'";

                                    $success = mysqli_query($link, $updateGraph);
                                    if (!$success) {
                                        $error = 'Error updating graph URLs: ' . mysqli_error($link);
                                        include 'error.html.php';
                                        exit();
                                    }
                                }
                            }

                            ###Delete duplicates (same Test_Number) from MySQL "metadata" table
                            $deleteDuplicates = "DELETE n1 FROM metadata n1, metadata n2 "
                                    . "WHERE n1.id > n2.id AND n1.Test_Number = n2.Test_Number";
                            $success = mysqli_query($link, $deleteDuplicates);
                            if (!$success) {
                                $error = 'Error deleting duplicate entries: ' . mysqli_error($link);
                                include 'error.html.php';
                                exit();
                            }


                            ###Displaying MySQL "metadata" table as HTML content on website
                            #load the MySQL "metadata" table into a php array variable called $result
                            $query = "SELECT Test_Number, Date, Cell_Type, Chemistry, Description, Graph, Download FROM metadata ORDER BY Test_Number";
                            $sortquery = "SELECT Test_Number, Date, Cell_Type, Chemistry, Description, Graph, Download FROM metadata";
                            if (isset($_GET['sort'])) {
                                if ($_GET['sort'] == 'Test_Number') {
                                    $query = $sortquery . ' ORDER BY Test_Number';
                                } elseif ($_GET['sort'] == 'Date') {
                                    $query = $sortquery . ' ORDER BY Date';
                                } elseif ($_GET['sort'] == 'Cell_Type') {
                                    $query = $sortquery . ' ORDER BY Cell_Type';
                                } elseif ($_GET['sort'] == 'Chemistry') {
                                    $query = $sortquery . ' ORDER BY Chemistry';
                                } elseif ($_GET['sort'] == 'Description') {
                                    $query = $sortquery . ' ORDER BY Description';
                                } elseif ($_GET['sort'] == 'Download') {
                                    $query = $sortquery . ' ORDER BY Download';
                                }
                            }

                            $result = mysqli_query($link, $query);
                            if (!$result) {
                                $error = 'Error fetching metadata: ' . mysqli_error($link);
                                include 'error.html.php';
                                exit();
                            }

                            #make HTML table to display on webpage
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['Test_Number'] . "</td>";
                                echo "<td>" . $row['Date'] . "</td>";
                                echo "<td>" . $row['Cell_Type'] . "</td>";
                                echo "<td>" . $row['Chemistry'] . "</td>";
                                echo "<td>" . $row['Description'] . "</td>";
                                echo "<td>" . $row['Graph'] . "</td>";
                                echo "<td>" . $row['Download'] . "</td>";
                                echo "</tr>";
                            }
                            mysqli_close($link);
                            ?>
                        </table>
                    </p>
                </div>
                <script type="text/javascript" src="include/footer.js"></script>
            </div>
        </body>
</html>
