<?php error_reporting(E_ERROR | E_PARSE);
session_start();

$password = "password";

if(isset($_POST['submit_pass']) && isset($_POST['pass']) && $_POST['pass'] != "") {
	if($_POST['pass'] == $password) {
		$_SESSION['password'] = $_POST['pass'];
	} else {
		$error = "Incorrect Pssword";
	}
}

if(isset($_POST['page_logout'])) {
	unset($_SESSION['password']);
}

if($_SESSION['password'] == $password) {
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "query_engine";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);

	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	// echo "Connected successfully";
	// upload file
	if(isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
		$errors = array();
		$file_name = $_FILES['file']['name'];
		$file_size = $_FILES['file']['size'];
		$file_tmp = $_FILES['file']['tmp_name'];
		$file_type = $_FILES['file']['type'];
		$file_ext = strtolower(end(explode('.', $_FILES['file']['name'])));

		$extensions = array("xlsx", "xls");

		if(in_array($file_ext, $extensions) === false) {
			$errors[] = "extension not allowed, please choose a XLSX or XLS file.";
		}

		/* if($file_size > 2097152) {
			$errors[] = 'File size must be excately 2 MB';
		} */

		if(empty($errors) == true) {
			move_uploaded_file($file_tmp, "../uploads/" . $file_name);

			$file_name_array = explode(" ", $file_name);

			// read file
			// (A) PHPSPREADSHEET TO LOAD EXCEL FILE
			require "../vendor/autoload.php";

			// sql to create table
			$sql = "CREATE TABLE " . $file_name_array[0] . " (
							id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL,
							newic VARCHAR(50) NOT NULL,
							oldic VARCHAR(50),
							phone1 VARCHAR(50) NOT NULL,
							phone2 VARCHAR(50),
							phone3 VARCHAR(50),
							phone4 VARCHAR(50),
							phone5 VARCHAR(50),
							address TEXT,
							category VARCHAR(50),
							created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
							FULLTEXT(newic, oldic, phone1, phone2, phone3, phone4, phone5)
						)";

			if ($conn->query($sql) === TRUE) {
				// echo "Table " . $file_name_array[0] . " created successfully";
			} else {
				die("Error creating table: " . $conn->error);
			}

			// $dir = "../uploads/*";

			// Open a known directory, and proceed to read its contents
			// foreach(glob($dir) as $file_name) {
				$ext = pathinfo($file_name, PATHINFO_EXTENSION);
				if($ext == "xlsx")
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				else if($ext == "xls")
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
				$spreadsheet = $reader->load("../uploads/" . $file_name);

				// $foundInCells = array();
				
				// (B) COUNT NUMBER OF WORKSHEETS
				$allsheets = $spreadsheet->getSheetCount();
				
				// (C) LOOP THROUGH ALL WORKSHEETS
				for ($i = 0; $i < $allsheets; $i++) {
					// (C1) GET WORKSHEET
					$worksheet = $spreadsheet->getSheet($i);
					// $wsTitle = $worksheet->getTitle();
					
					// (C2) LOOP THROUGH ROWS OF CURRENT WORKSHEET
					$r = 0;
					foreach ($worksheet->getRowIterator() as $row) {
						// skip 1st row
						if($r > 0) {
							// (C3) READ CELLS
							$cellIterator = $row->getCellIterator();
							$cellIterator->setIterateOnlyExistingCells(false);
							$value = array();
							foreach ($cellIterator as $cell) {
								$value[] = $cell->getFormattedValue();
							}

							$value[] = $file_name_array[0]; // insert category/database in last column

							$sql = "INSERT INTO " . $file_name_array[0] . " ( name, newic, oldic, phone1, phone2, phone3, phone4, phone5, address, category ) 
										VALUES 
										( '" . implode("', '", $value) . "' )";
							$conn->query($sql);
						}

						$r++;
					}
				}
			// }

			echo '<div class="row"><div class="col-sm">&nbsp;</div><div class="alert alert-success" role="alert">Success</div></div>';

			// unlink("../uploads/" . $file_name);
		} else {
			print_r($errors);
		}
	}

	$search_result = array(); // initialize array for individual search or bulk search

	// search
	if(isset($_POST['ic']) && $_POST['ic'] != "" || isset($_POST['phone']) && $_POST['phone'] != "") {
		$ic = (isset($_POST['ic'])) ? $_POST['ic'] : "";
		$phone = (isset($_POST['phone'])) ? $_POST['phone'] : "";

		$ri = 0;
		if($tables = $conn->query("SHOW TABLES")) {
			if($tables->num_rows > 0) {
				while ($table = $tables->fetch_all()) {
					foreach ($table as $t) {
						// $sql = "SELECT * FROM " . $table[0] . " WHERE MATCH(newic, oldic) AGAINST('" . $ic . "' IN NATURAL LANGUAGE MODE) AND MATCH(phone1, phone2, phone3, phone4, phone5) AGAINST('" . $phone . "' IN NATURAL LANGUAGE MODE)";
						if($ic != "" && $phone == "")
							$sql = "SELECT * FROM " . $t[0] . " WHERE CONCAT_WS('', newic, oldic) LIKE '%" . $ic . "%'";
						else if($ic == "" && $phone != "")
							$sql = "SELECT * FROM " . $t[0] . " WHERE CONCAT_WS('', phone1, phone2, phone3, phone4, phone5) LIKE '%" . $phone . "%'";
						else
							$sql = "SELECT * FROM " . $t[0] . " WHERE CONCAT_WS('', newic, oldic) LIKE '%" . $ic . "%' AND CONCAT_WS('', phone1, phone2, phone3, phone4, phone5) LIKE '%" . $phone . "%'";
						
						$result = $conn->query($sql);
						if($result->num_rows > 0) {
							while ($row = $result->fetch_assoc()) {
								$search_result[$ri]['name'] = $row['name'];
								$search_result[$ri]['newic'] = $row['newic'];
								$search_result[$ri]['oldic'] = $row['oldic'];
								$search_result[$ri]['phone1'] = $row['phone1'];
								$search_result[$ri]['phone2'] = $row['phone2'];
								$search_result[$ri]['phone3'] = $row['phone3'];
								$search_result[$ri]['phone4'] = $row['phone4'];
								$search_result[$ri]['phone5'] = $row['phone5'];
								$search_result[$ri]['address'] = $row['address'];
								$search_result[$ri]['category'] = $row['category'];

								$ri++;
							}
						}
					}
				}
			}
		} else {
			die($conn->error);
		}
	}

	// bulk search
	if(isset($_FILES['bulk']) && !empty($_FILES['bulk']['name'])) {
		$errors = array();
		$bulk_name = $_FILES['bulk']['name'];
		$bulk_size = $_FILES['bulk']['size'];
		$bulk_tmp = $_FILES['bulk']['tmp_name'];
		$bulk_type = $_FILES['bulk']['type'];
		$bulk_ext = strtolower(end(explode('.', $_FILES['bulk']['name'])));

		$extensions = array("xlsx", "xls");

		if(in_array($bulk_ext, $extensions) === false) {
			$errors[] = "extension not allowed, please choose a XLSX or XLS file.";
		}

		/* if($file_size > 2097152) {
			$errors[] = 'File size must be excately 2 MB';
		} */

		if(empty($errors) == true) {
			move_uploaded_file($bulk_tmp, "../uploads/search/" . $bulk_name);

			$bulk_name_array = explode(" ", $bulk_name);

			// read file
			// (A) PHPSPREADSHEET TO LOAD EXCEL FILE
			require "../vendor/autoload.php";

			/* // init new spreadsheet for export
			$newspreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$activesheet = $newspreadsheet->getActiveSheet();
			$activesheet->fromArray([array('Name', 'New IC', 'Old IC', 'Phone 1', 'Phone 2', 'Phone 3', 'Phone 4', 'Phone 5', 'Address', 'Category (Database)')], NULL, 'A1');
			*/

			// $dir = "../uploads/*";

			// Open a known directory, and proceed to read its contents
			// foreach(glob($dir) as $file_name) {
				$ext = pathinfo($bulk_name, PATHINFO_EXTENSION);
				if($ext == "xlsx")
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
				else if($ext == "xls")
					$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
				$spreadsheet = $reader->load("../uploads/" . $bulk_name);

				// $foundInCells = array();
				
				// (B) COUNT NUMBER OF WORKSHEETS
				$allsheets = $spreadsheet->getSheetCount();
				
				$ridx = 0;
				// (C) LOOP THROUGH ALL WORKSHEETS
				for ($i = 0; $i < $allsheets; $i++) {
					// (C1) GET WORKSHEET
					$worksheet = $spreadsheet->getSheet($i);
					// $wsTitle = $worksheet->getTitle();

					// Get the highest row number
					$highestRow = $worksheet->getHighestRow();

					// Define the chunk size
					$chunkSize = 10000;
					
					// Loop through the rows in chunks
					for ($startRow = 1; $startRow <= $highestRow; $startRow += $chunkSize) {
					    $endRow = $startRow + $chunkSize - 1;
					    if ($endRow > $highestRow) {
					        $endRow = $highestRow;
					    }

					    // Build the range of rows to process
					    $range = 'A' . $startRow . ':B' . $endRow;

					    // Get the values for the range
					    $columnValues = $worksheet->rangeToArray($range, null, true, true, true);

					    // Process the search query for each row in the range

						// (C2) LOOP THROUGH ROWS OF CURRENT WORKSHEET
						$r = 0;
						// foreach ($worksheet->getRowIterator() as $row) {
						foreach ($columnValues as $row => $cell) {
							// skip 1st row
							if($r > 0) {
								// (C3) READ CELLS
								// $cellIterator = $row->getCellIterator();
								// $cellIterator->setIterateOnlyExistingCells(false);
								// $bulks = array();
								// foreach ($cellIterator as $cell) {
								//	 $bulks[] = $cell->getFormattedValue();
								// }
								$name = $cell['A'];
								$ic = $cell['B'];
								// var_dump($bulks); exit();
								if($tables = $conn->query("SHOW TABLES")) {
									if($tables->num_rows > 0) {
										while ($table = $tables->fetch_all()) { // var_dump($table);
											foreach ($table as $t) {
												// $sql = "SELECT * FROM " . $table[0] . " WHERE MATCH(newic, oldic) AGAINST('" . $ic . "' IN NATURAL LANGUAGE MODE) AND MATCH(phone1, phone2, phone3, phone4, phone5) AGAINST('" . $phone . "' IN NATURAL LANGUAGE MODE)";
												// $sql = "SELECT * FROM " . $t[0] . " WHERE name LIKE '%" . $bulks[0] . "%' AND CONCAT_WS('', newic, oldic) LIKE '%" . $bulks[1] . "%'";
												$sql = "SELECT * FROM " . $t[0] . " WHERE name LIKE '%" . $name . "%' AND CONCAT_WS('', newic, oldic) LIKE '%" . $ic . "%'";
												
												$result = $conn->query($sql);
												if($result->num_rows > 0) {
													while ($row = $result->fetch_assoc()) {
														$search_result[$ridx]['name'] = $row['name'];
														$search_result[$ridx]['newic'] = $row['newic'];
														$search_result[$ridx]['oldic'] = $row['oldic'];
														$search_result[$ridx]['phone1'] = $row['phone1'];
														$search_result[$ridx]['phone2'] = $row['phone2'];
														$search_result[$ridx]['phone3'] = $row['phone3'];
														$search_result[$ridx]['phone4'] = $row['phone4'];
														$search_result[$ridx]['phone5'] = $row['phone5'];
														$search_result[$ridx]['address'] = $row['address'];
														$search_result[$ridx]['category'] = $row['category'];

														// $activesheet->fromArray([array($row['name'], $row['newic'], $row['oldic'], $row['phone1'], $row['phone2'], $row['phone3'], $row['phone4'], $row['phone5'], $row['address'], $row['category'])], NULL, 'A' . ($ridx + 1));

														$ridx++;
													}
												}
											}
										}

										// unlink("../uploads/" . $bulk_name);
									}
								} else {
									die($conn->error);
								}
							}

							$r++;
						}
					}
				}
			// }

			echo '<div class="row"><div class="col-sm">&nbsp;</div><div class="alert alert-success" role="alert">Success</div></div>';

			// redirect output to client browser
			// header('Content-Type: application/vnd.ms-excel'); // xls
			// header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // xlsx
			// header('Content-Disposition: attachment;filename="SmartQueryEngine_' . time() . '.xls"');
			// header('Cache-Control: max-age=0');

			// $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($newspreadsheet);
			// $writer->save('php://output');
			// exit;

			// header('Location: ' . $_SERVER['PHP_SELF']);
			// die;
		} else {
			print_r($errors);
		}
	}
?>
<html>
	<head>
		<title>Smart Query Engine</title>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css" integrity="sha512-SbiR/eusphKoMVVXysTKG/7VseWii+Y3FdHrt0EpKgpToZeemhqHeZeLWLhJutz/2ut2Vw1uQEj2MbRF+TVBUA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
		<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.css">
		<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.2/css/buttons.dataTables.min.css">

		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.3/jquery.min.js" integrity="sha512-STof4xm1wgkfm7heWqFJVn58Hm3EtS31XFaagaa8VMReCXAkQnJZ+jEy8PCC/iT18dFy95WcExNHFTqLyp72eQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.min.js" integrity="sha512-1/RvZTcCDEUjY/CypiMz+iqqtaoQfAITmNSJY17Myp4Ms5mdxPS5UV7iOfdZoxcGhzFbOm6sntTKJppjvuhg4g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
		<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
		<script src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
		<script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.html5.min.js"></script>
		<script src="https://cdn.datatables.net/buttons/2.3.2/js/buttons.print.min.js"></script>

		<style>
			/* Center the loader */
			#loader {
				position: absolute;
				left: 50%;
				top: 50%;
				z-index: 1;
				width: 120px;
				height: 120px;
				margin: -76px 0 0 -76px;
				border: 16px solid #f3f3f3;
				border-radius: 50%;
				border-top: 16px solid #3498db;
				-webkit-animation: spin 2s linear infinite;
				animation: spin 2s linear infinite;
			}

			@-webkit-keyframes spin {
				0% { -webkit-transform: rotate(0deg); }
				100% { -webkit-transform: rotate(360deg); }
			}

			@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}

			/* Add animation to "page content" */
			.animate-bottom {
				position: relative;
				-webkit-animation-name: animatebottom;
				-webkit-animation-duration: 1s;
				animation-name: animatebottom;
				animation-duration: 1s
			}

			@-webkit-keyframes animatebottom {
				from { bottom:-100px; opacity:0 } 
				to { bottom:0px; opacity:1 }
			}

			@keyframes animatebottom { 
				from{ bottom:-100px; opacity:0 } 
				to{ bottom:0; opacity:1 }
			}
		</style>
	</head>
	<body>
		<div id="loader" style="display: none;"></div>
		<div class="row">
			<div class="col-lg-12" style="margin-top: 20px; text-align: center;">
				<div class="alert alert-info" role="alert">
					<a href="" style="text-decoration: none; color: #000000;">Smart Query Engine</a>&emsp;<form method="post" action="" id="logout_form"><input type="submit" name="page_logout" value="LOGOUT" class="btn btn-danger" style="float: right;"></form>
				</div>
			</div>
			<div class="col-sm-1">&nbsp;</div>
			<div class="col-sm-2" style="margin-top: 20px;">
				<form class="row g-3" action="" method="POST" enctype="multipart/form-data">
					<div class="col-md-12">
						<input type="text" class="form-control" id="icno" name="ic" placeholder="IC No" value="<?php echo (isset($_POST['ic'])) ? $_POST['ic'] : ""; ?>">
					</div>
					<div class="col-md-12">
						<input type="text" class="form-control" id="phoneno" name="phone" placeholder="Phone No" value="<?php echo (isset($_POST['phone'])) ? $_POST['phone'] : ""; ?>">
					</div>
					<div class="col-12">
						<button type="submit" class="btn btn-primary">Search</button>
						<button type="button" class="btn btn-danger" onclick="window.location.href=''">Reset</button>
					</div>
				</form>
			</div>
			<div class="col-sm-4" style="margin-top: 20px;">
				<p style="color: red;"><i>* Upload data from excel files to database (<b>.XLSX or .XLS</b>)</i></p>
				<form action="" method="POST" enctype="multipart/form-data">
					<input type="file" name="file" class="alert alert-danger" />
					<input type="submit" class="btn btn-danger" />

					<ul>
						<li>Sent file: <?php echo (isset($_FILES['file'])) ? $_FILES['file']['name'] : ""; ?></li>
						<li>File size: <?php echo (isset($_FILES['file'])) ? $_FILES['file']['size'] : ""; ?></li>
					</ul>
				</form>
			</div>
			<div class="col-sm-4" style="margin-top: 20px;">
				<p style="color: green;"><i>* Bulk search (Name and/or IC) from excel file (<b>.XLSX or .XLS</b>)</i></p>
				<form action="" method="POST" enctype="multipart/form-data">
					<input type="file" name="bulk" class="alert alert-success" />
					<input type="submit" class="btn btn-success" />

					<ul>
						<li>Sent file: <?php echo (isset($_FILES['bulk'])) ? $_FILES['bulk']['name'] : ""; ?></li>
						<li>File size: <?php echo (isset($_FILES['bulk'])) ? $_FILES['bulk']['size'] : ""; ?></li>
					</ul>
				</form>
			</div>
			<div class="col-sm-1">&nbsp;</div>
			<div class="col-lg-12" style="margin-top: 20px; text-align: center;">
				<table id="tblCustomers" class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>Name</th>
							<th>New IC</th>
							<th>Old IC</th>
							<th>Phone 1</th>
							<th>Phone 2</th>
							<th>Phone 3</th>
							<th>Phone 4</th>
							<th>Phone 5</th>
							<th>Address</th>
							<th>Category (Database)</th>
						</tr>
					</thead>
					<tbody>
						<?php
						if(!empty($search_result)) { // var_dump($search_result);
							foreach($search_result as $search) {
								?>
								<tr>
									<td><?php echo $search['name']; ?></td>
									<td><?php echo $search['newic']; ?></td>
									<td><?php echo $search['oldic']; ?></td>
									<td><?php echo $search['phone1']; ?></td>
									<td><?php echo $search['phone2']; ?></td>
									<td><?php echo $search['phone3']; ?></td>
									<td><?php echo $search['phone4']; ?></td>
									<td><?php echo $search['phone5']; ?></td>
									<td><?php echo $search['address']; ?></td>
									<td><?php echo $search['category']; ?></td>
								</tr>
								<?php
							}
						?>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>

		<script>
			$(document).ready(function () {
				$('#tblCustomers').DataTable({
					// scrollY: '480px',
					// scrollCollapse: true,
					paging: true,
					dom: 'Bfrtip',
					buttons: [
						'excel', 'print'
					]
				});

				$("form").submit(function() {
					$('.row').css('opacity', '0.3');
					$('#loader').show();
				});
			});
		</script>
	</body>
</html>
<?php } else { ?>
<html>
	<head>
		<title>Smart Query Engine</title>
		<style>
			body {
				margin:0 auto;
				padding:0px;
				text-align:center;
				width:100%;
				font-family: "Myriad Pro","Helvetica Neue",Helvetica,Arial,Sans-Serif;
				background-color:#8A4B08;
			}

			#wrapper {
				margin:0 auto;
				padding:0px;
				text-align:center;
				width:995px;
			}

			#wrapper h1 {
				margin-top:50px;
				font-size:45px;
				color:white;
			}

			#wrapper p {
				font-size:16px;
			}

			#logout_form input[type="submit"] {
				width:250px;
				margin-top:10px;
				height:40px;
				font-size:16px;
				background:none;
				border:2px solid white;
				color:white;
			}

			#login_form {
				margin-top:200px;
				background-color:white;
				width:350px;
				margin-left:310px;
				padding:20px;
				box-sizing:border-box;
				box-shadow:0px 0px 10px 0px #3B240B;
			}

			#login_form h1 {
				margin:0px;
				font-size:25px;
				color:#8A4B08;
			}

			#login_form input[type="password"] {
				width:250px;
				margin-top:10px;
				height:40px;
				padding-left:10px;
				font-size:16px;
			}

			#login_form input[type="submit"] {
				width:250px;
				margin-top:10px;
				height:40px;
				font-size:16px;
				background-color:#8A4B08;
				border:none;
				box-shadow:0px 4px 0px 0px #61380B;
				color:white;
				border-radius:3px;
			}

			#login_form p {
				margin:0px;
				margin-top:15px;
				color:#8A4B08;
				font-size:17px;
				font-weight:bold;
			}
		</style>
	</head>
	<body>
		<div id="wrapper">
			<form method="post" action="" id="login_form">
				<h1>LOGIN TO PROCEED</h1>
				<input type="password" name="pass" placeholder="********">
				<input type="submit" name="submit_pass" value="LOGIN">
				<p><font style="color: red;"><?php echo $error; ?></font></p>
			</form>
		</div>
	</body>
</html>
<?php } ?>