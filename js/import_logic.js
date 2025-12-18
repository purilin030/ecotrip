let parsedRows = [];
let hasErrors = false;

// Event Listener for File Upload
const fileInput = document.getElementById('csvFileInput');
if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            parseCSV(event.target.result);
        };
        reader.readAsText(file);
    });
}

function parseCSV(text) {
    const lines = text.split('\n').filter(line => line.trim() !== '');
    
    parsedRows = [];
    hasErrors = false;
    let validCount = 0;
    let errorCount = 0;
    
    const tbody = document.getElementById('previewTableBody');
    tbody.innerHTML = ''; 

    // Prepare Validation Arrays (LowerCase for case-insensitive check)
    // validCategories and validCities come from PHP
    const validCatsLower = (typeof validCategories !== 'undefined' ? validCategories : []).map(c => c.toLowerCase());
    const validCitiesLower = (typeof validCities !== 'undefined' ? validCities : []).map(c => c.toLowerCase());

    // Start loop at 1 to skip Header Row
    for (let i = 1; i < lines.length; i++) {
        // Handle CSV split (handles simple commas, removes quotes)
        const cols = lines[i].split(',').map(c => c.trim().replace(/^"|"$/g, '')); 

        if (cols.length < 5) continue; 

        const rowData = {
            Title: cols[0] || '',
            Category: cols[1] || '',
            Points: cols[2] || '0',
            Preview: cols[3] || '',
            City: cols[4] || '',
            Difficulty: cols[5] || 'Medium',
            Start: (cols[6] || '').trim(), 
            End: (cols[7] || '').trim(),
            Status: cols[8] || 'Draft',
            Detailed: cols[9] || '',
            Photo: cols[10] || ''
        };

        // --- VALIDATION LOGIC ---
        let statusHtml = '<span class="text-green-600 font-bold">OK</span>';
        let rowError = false;

        // 1. Check Required Fields
        if (!rowData.Title || !rowData.Start || !rowData.End) {
            statusHtml = '<span class="text-red-500 font-bold">Missing Fields</span>';
            rowError = true;
        }
        // 2. Check Category (Case Insensitive)
        else if (validCatsLower.length > 0 && !validCatsLower.includes(rowData.Category.toLowerCase())) {
            statusHtml = `<span class="text-red-500 font-bold">Invalid Cat</span>`;
            rowError = true;
        }
        // 3. Check City (Case Insensitive)
        else if (validCitiesLower.length > 0 && !validCitiesLower.includes(rowData.City.toLowerCase())) {
            statusHtml = `<span class="text-red-500 font-bold">Invalid City</span>`;
            rowError = true;
        }
        
        if (rowError) {
            hasErrors = true;
            errorCount++;
        } else {
            validCount++;
            parsedRows.push(rowData);
        }

        // --- RENDER TABLE ROW ---
        const tr = document.createElement('tr');
        if (rowError) tr.classList.add('bg-red-50');

        tr.innerHTML = `
            <td class="p-3 border-b text-gray-500">${i}</td>
            <td class="p-3 border-b font-medium text-gray-900">${rowData.Title}</td>
            <td class="p-3 border-b">${rowData.Category}</td>
            <td class="p-3 border-b">${rowData.Points}</td>
            <td class="p-3 border-b truncate max-w-[120px]" title="${rowData.Preview}">${rowData.Preview}</td>
            <td class="p-3 border-b">${rowData.City}</td>
            <td class="p-3 border-b"><span class="capitalize">${rowData.Difficulty}</span></td>
            <td class="p-3 border-b">${rowData.Start}</td>
            <td class="p-3 border-b">${rowData.End}</td>
            <td class="p-3 border-b"><span class="px-2 py-0.5 rounded text-xs bg-gray-200">${rowData.Status}</span></td>
            <td class="p-3 border-b truncate max-w-[120px]">${rowData.Detailed}</td>
            <td class="p-3 border-b text-xs text-gray-500 truncate max-w-[80px]">${rowData.Photo}</td>
            <td class="p-3 border-b sticky right-0 bg-white border-l text-center">${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    }

    // Update Counters
    const validCountEl = document.getElementById('validCount');
    if(validCountEl) validCountEl.textContent = validCount;
    
    const errorCountEl = document.getElementById('errorCount');
    if(errorCountEl) errorCountEl.textContent = errorCount;

    // Enable/Disable Button
    const importBtn = document.getElementById('importBtn');
    if (importBtn) {
        if (validCount > 0) {
            importBtn.disabled = false;
            importBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            importBtn.classList.add('hover:bg-green-700');
            importBtn.innerText = `Import ${validCount} Valid Rows`;
        } else {
            importBtn.disabled = true;
            importBtn.classList.add('opacity-50', 'cursor-not-allowed');
            importBtn.classList.remove('hover:bg-green-700');
            importBtn.innerText = "Import Challenges";
        }
    }
}

function processImport() {
    if (parsedRows.length === 0) return;

    const btn = document.getElementById('importBtn');
    const originalText = btn.innerText;
    btn.innerText = "Importing...";
    btn.disabled = true;

    fetch('csv_handler.php?action=import', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rows: parsedRows }) 
    })
    .then(response => response.text()) 
    .then(text => {
        try {
            return JSON.parse(text); 
        } catch (e) {
            // This catches PHP Fatal Errors (HTML)
            const errorSnippet = text.substring(0, 300).replace(/<[^>]*>?/gm, ''); 
            throw new Error("Server returned non-JSON data: " + errorSnippet);
        }
    })
    .then(data => {
        btn.innerText = originalText;
        btn.disabled = false;

        if (data.status === 'success') {
            if (data.errors && data.errors.length > 0) {
                alert(`${data.message}\n\nWarning - Some rows failed on server:\n` + data.errors.join('\n'));
                window.location.href = 'manage_challenge.php';
            } else {
                alert(data.message);
                window.location.href = 'manage_challenge.php';
            }
        } else {
            alert('Import Error: ' + (data.message || JSON.stringify(data)));
        }
    })
    .catch(err => {
        console.error("Fetch Error:", err);
        alert('System Error:\n' + err.message);
        btn.innerText = originalText;
        btn.disabled = false;
    });
}