<!DOCTYPE html>
<html>

<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-900 text-white p-6">

    <h2 class="text-xl mb-4">Admin Panel</h2>

    <input id="number" placeholder="Number" class="p-2 bg-gray-700">
    <select id="provider" class="p-2 bg-gray-700">
        <option>bkash</option>
        <option>nagad</option>
        <option>rocket</option>
    </select>

    <button onclick="add()" class="bg-green-500 px-3 py-1">Add</button>

    <div id="list"></div>

    <script>
        function load() {
            fetch('../api/payment_numbers/read.php')
                .then(r => r.json())
                .then(d => {
                    let html = '';
                    d.forEach(x => {
                        html += `
<div class="p-2 border">
${x.provider} - ${x.number}
<img src="${x.qr_url}" width="60">
<button onclick="del(${x.id})">X</button>
</div>`;
                    });
                    document.getElementById('list').innerHTML = html;
                });
        }

        function add() {
            fetch('../api/payment_numbers/create.php', {
                method: 'POST',
                body: JSON.stringify({
                    provider: provider.value,
                    account_type: 'merchant',
                    number: number.value,
                    name: 'Admin',
                    api_key: '123'
                })
            }).then(load);
        }

        function del(id) {
            fetch('../api/payment_numbers/delete.php?id=' + id).then(load);
        }

        load();
    </script>

</body>

</html>