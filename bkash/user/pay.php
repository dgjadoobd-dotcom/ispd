<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white p-6">

<h2>Make Payment</h2>

<select id="provider"></select>
<input id="amount" placeholder="Amount">
<input id="trxid" placeholder="Transaction ID">
<input id="name" placeholder="Your Name">

<button onclick="pay()" class="bg-blue-500 p-2">Submit</button>

<script>

let numbers=[];

fetch('../api/payment_numbers/read.php')
.then(r=>r.json())
.then(d=>{
numbers=d;
let opt='';
d.forEach(x=>{
opt+=`<option value="${x.number}">
${x.provider} - ${x.number}
</option>`;
});
provider.innerHTML=opt;
});

function pay(){
fetch('../api/transactions/create.php',{
method:'POST',
body:JSON.stringify({
name:name.value,
amount:amount.value,
provider:'bkash',
number:provider.value,
trxid:trxid.value
})
}).then(()=>alert("Submitted"));
}
</script>

</body>
</html>