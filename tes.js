const id = "1JN202406001086";

fetch(`http://localhost/api/v1/ont/lookup?id=${encodeURIComponent(id)}&wifi=1`, {
    method: 'GET',
    headers: {
        'Accept': 'application/json'
    }
})
.then(async response => {
    const data = await response.json();
    console.log('HTTP:', response.status);
    console.log('Response:', data);
})
.catch(error => console.error('[JANETA] API Error:', error));