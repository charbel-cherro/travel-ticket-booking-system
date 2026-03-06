<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="page">

<div class="page-header">
<h2>Manage Destinations (UI only)</h2>
<p class="muted">Add, update, delete destinations.</p>
</div>

<div class="panel">

<div class="form">

<div class="form-row">

<div class="form-group">
<label>City</label>
<input type="text" id="city" placeholder="e.g., Paris">
</div>

<div class="form-group">
<label>Country</label>
<input type="text" id="country" placeholder="e.g., France">
</div>

</div>

<div class="form-row">

<div class="form-group">
<label>Price</label>
<input type="number" id="price" placeholder="e.g., 420">
</div>

<div class="form-group">
<label>Available Seats</label>
<input type="number" id="seats" placeholder="e.g., 30">
</div>

</div>

<button class="btn-primary" onclick="addDestination()">Add Destination</button>

</div>

</div>


<div class="panel mt">

<h3>Destination List</h3>

<table class="table">

<thead>
<tr>
<th>City</th>
<th>Country</th>
<th>Price</th>
<th>Seats</th>
<th>Actions</th>
</tr>
</thead>

<tbody id="destinationTable">

<tr>
<td>Paris</td>
<td>France</td>
<td>$420</td>
<td>30</td>
<td>
<button class="btn-small">Edit</button>
<button class="btn-small">Delete</button>
</td>
</tr>

<tr>
<td>Tokyo</td>
<td>Japan</td>
<td>$490</td>
<td>25</td>
<td>
<button class="btn-small">Edit</button>
<button class="btn-small">Delete</button>
</td>
</tr>

</tbody>

</table>

</div>

</section>

<script>

function addDestination(){

const city = document.getElementById("city").value
const country = document.getElementById("country").value
const price = document.getElementById("price").value
const seats = document.getElementById("seats").value

if(city === "" || country === "" || price === "" || seats === ""){
alert("Please fill all fields")
return
}

const table = document.getElementById("destinationTable")

const row = `
<tr>
<td>${city}</td>
<td>${country}</td>
<td>$${price}</td>
<td>${seats}</td>
<td>
<button class="btn-small">Edit</button>
<button class="btn-small">Delete</button>
</td>
</tr>
`

table.innerHTML += row

document.getElementById("city").value = ""
document.getElementById("country").value = ""
document.getElementById("price").value = ""
document.getElementById("seats").value = ""

}

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>