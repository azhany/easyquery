<?php error_reporting(E_ERROR | E_PARSE);
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
					<a href="" style="text-decoration: none; color: #000000;">Smart Query Engine</a>
				</div>
			</div>
			<div class="col-sm-3">&nbsp;</div>
			<div class="col-sm" style="margin-top: 20px;">
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
			<div class="col-sm-3">&nbsp;</div>
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